<?php
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../app/controllers/CheckoutController.php';
require_once __DIR__ . '/../app/services/CheckoutService.php';
require_once __DIR__ . '/../app/services/EmailService.php';

pickled_start_secure_session();
pickled_init_csrf();
$csrfToken = pickled_csrf_token();

pickled_require_login('resident/cart.php');
pickled_process_pending_booking_expiry();
pickled_restore_cart_for_user();

if (pickled_expire_cart_if_needed()) {
  header('Location: cart.php?expired=1');
  exit;
}

$pageTitle  = 'Cart - Pickled';
$activePage = 'cart.php';
$basePath   = '../';
$message = '';
$messageType = 'success';
$paymentMethods = CheckoutController::paymentMethods();
$selectedPayment = (string) ($_POST['payment_method'] ?? CheckoutController::defaultPaymentMethod());
$isSelectedPaymentValid = CheckoutController::isValidMethod($selectedPayment);
$isCheckout = isset($_GET['checkout']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout');

function cart_save_booking_phone(int $userId, string $phone): void {
  if ($userId <= 0) {
    throw new RuntimeException('Please enter a valid Philippine mobile number.');
  }
  $phone = validatePhonePH(str_replace(' ', '', $phone));

  $stmt = Database::connection()->prepare(
    'INSERT INTO user_profiles (user_id, phone, city, province, avatar)
     VALUES (:user_id, :phone, :city, :province, :avatar)
     ON DUPLICATE KEY UPDATE phone = VALUES(phone)'
  );
  $stmt->execute([
    'user_id' => $userId,
    'phone' => $phone,
    'city' => '',
    'province' => '',
    'avatar' => 'avatars/default.png',
  ]);
}

if (isset($_GET['added'])) {
  $message = 'Booking added to cart. Complete checkout before the timer ends.';
}
if (isset($_GET['expired'])) {
  $message = 'Your booking session has expired. Please select a schedule again.';
  $messageType = 'error';
}
if (isset($_GET['duplicate'])) {
  $message = 'That booking is already in your cart.';
  $messageType = 'warning';
}
if (isset($_GET['limit'])) {
  $message = 'Cart limit reached. Please complete checkout before adding more reservations.';
  $messageType = 'warning';
}
if (isset($_GET['full'])) {
  $message = 'That session is already full. Please choose another schedule.';
  $messageType = 'warning';
}
if (isset($_GET['capacity'])) {
  $message = 'This service has reached its maximum capacity for the selected schedule.';
  $messageType = 'warning';
}
if (isset($_GET['conflict'])) {
  $message = 'That court is already booked for the selected date and time. Please choose another schedule.';
  $messageType = 'warning';
}
if (isset($_GET['coach_unavailable'])) {
  $message = 'No coach is available for the selected date and time.';
  $messageType = 'warning';
}
if (isset($_GET['invalid'])) {
  $message = 'That cart action could not be completed. Please choose a valid schedule.';
  $messageType = 'warning';
}
if (isset($_GET['expired_schedule'])) {
  $message = 'This schedule is no longer available. Please select a future time slot.';
  $messageType = 'warning';
}
if (!empty($_SESSION['cart_removed_expired'])) {
  $message = 'One or more items in your cart are no longer available and were removed.';
  $messageType = 'warning';
  unset($_SESSION['cart_removed_expired']);
}
if (isset($_GET['updated'])) {
  $message = 'Cart item updated.';
}
if (isset($_GET['booked']) && !empty($_SESSION['last_booking'])) {
  $message = 'Booking created. Upload your payment receipt to complete review. Reference: ' . $_SESSION['last_booking']['reference'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrfToken = $_POST['csrf_token'] ?? null;
  if (!pickled_validate_csrf_token($csrfToken)) {
    $message = 'Invalid request. Please refresh and try again.';
    $messageType = 'error';
  } else {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
      $cartId = (int) ($_POST['cart_id'] ?? 0);
      (new CartService())->removeForUser((int) $_SESSION['user']['id'], $cartId);
      pickled_restore_cart_for_user();
      if (empty($_SESSION['cart'])) {
        (new CartService())->clearForUser((int) $_SESSION['user']['id']);
        $_SESSION['cart'] = [];
        pickled_clear_cart_timer();
      }
      header('Location: cart.php');
      exit;
    }

    if ($action === 'update_quantity') {
      $cartId = (int) ($_POST['cart_id'] ?? 0);
      try {
        $quantity = validatePositiveInt($_POST['quantity'] ?? 1, pickled_cart_limit());
      } catch (RuntimeException) {
        header('Location: cart.php?invalid=1');
        exit;
      }
      $result = pickled_update_cart_quantity($cartId, $quantity);
      header('Location: cart.php?' . ($result['ok'] ? 'updated=1' : $result['code'] . '=1'));
      exit;
    }

    if ($action === 'clear') {
      (new CartService())->clearForUser((int) $_SESSION['user']['id']);
      $_SESSION['cart'] = [];
      pickled_clear_cart_timer();
      header('Location: cart.php');
      exit;
    }

    if ($action === 'add_booking') {
      $variantId = trim((string) ($_POST['variant_id'] ?? ''));
      try {
        $quantity = validatePositiveInt($_POST['quantity'] ?? 1, pickled_cart_limit());
      } catch (RuntimeException) {
        $back = $_SERVER['HTTP_REFERER'] ?? 'courts.php#court-detail';
        $separator = str_contains($back, '?') ? '&' : '?';
        header('Location: ' . $back . $separator . 'cart_error=invalid');
        exit;
      }
      $customerPhone = str_replace(' ', '', (string) ($_POST['customer_phone'] ?? ''));
      $date = trim((string) ($_POST['booking_date'] ?? $_POST['date'] ?? (new DateTimeImmutable('+3 days'))->format('F j, Y')));
      $startTime = trim((string) ($_POST['start_time'] ?? ''));
      $endTime = trim((string) ($_POST['end_time'] ?? ''));
      $time = trim((string) ($_POST['time'] ?? ''));
      if ($time === '' && $startTime !== '' && $endTime !== '') {
        $time = $startTime . ' - ' . $endTime;
      }
      $coachUserId = empty($_POST['coach_user_id']) ? null : (int) $_POST['coach_user_id'];
      $sessionId = empty($_POST['session_id']) ? null : (int) $_POST['session_id'];
      try {
        $customerPhone = validatePhonePH($customerPhone);
      } catch (RuntimeException) {
        $back = $_SERVER['HTTP_REFERER'] ?? 'courts.php#court-detail';
        $separator = str_contains($back, '?') ? '&' : '?';
        header('Location: ' . $back . $separator . 'cart_error=phone');
        exit;
      }
      cart_save_booking_phone((int) $_SESSION['user']['id'], $customerPhone);
      error_log('Cart add POST handler. user_id=' . (int) ($_SESSION['user']['id'] ?? 0) . '; variant_id=' . $variantId . '; session_id=' . (string) ($sessionId ?? '') . '; booking_date=' . $date . '; start_time=' . $startTime . '; end_time=' . $endTime . '; time=' . $time . '; quantity=' . $quantity . '; coach_user_id=' . (string) ($coachUserId ?? ''));
      $result = pickled_add_to_cart($variantId, $quantity, $date, $time, $coachUserId, $sessionId);
      if ($result['ok']) {
        header('Location: cart.php?added=1');
      } else {
        $back = $_SERVER['HTTP_REFERER'] ?? 'courts.php#court-detail';
        $separator = str_contains($back, '?') ? '&' : '?';
        header('Location: ' . $back . $separator . 'cart_error=' . rawurlencode((string) $result['code']));
      }
      exit;
    }


  if ($action === 'checkout') {
    if (empty($_SESSION['cart'])) {
      $message = 'Your cart is empty. Add a booking before checkout.';
      $messageType = 'warning';
    } elseif (!$isSelectedPaymentValid) {
      $message = 'Please choose GCash as the payment method.';
      $messageType = 'warning';
    } elseif (empty($_POST['terms'])) {
      $message = 'Please agree to the booking terms before checkout.';
      $messageType = 'warning';
    } else {
      try {
        $_SESSION['last_booking'] = (new CheckoutService())->createBooking(
          (int) $_SESSION['user']['id'],
          $_SESSION['cart'],
          $_SESSION['user']['name'] ?? 'Guest',
          $selectedPayment,
          validateText($_POST['notes'] ?? '', false, 1000)
        );
      } catch (Throwable $e) {
        $message = $e instanceof RuntimeException ? $e->getMessage() : 'Your booking could not be completed right now. Please try again.';
        $messageType = 'warning';
        try {
          if (!empty($_SESSION['user']['email'])) {
            (new EmailService())->sendBookingIssue($_SESSION['user'], $message);
          }
        } catch (Throwable $emailError) {
          error_log('Checkout failure email failed: ' . $emailError->getMessage());
        }
      }

      if ($messageType !== 'warning') {
        (new CartService())->clearForUser((int) $_SESSION['user']['id']);
        $_SESSION['cart'] = [];
        pickled_clear_cart_timer();
        header('Location: cart.php?booked=1');
        exit;
      }
    }
  }
}
}

$cartItems = $_SESSION['cart'] ?? [];
$cartCount = pickled_cart_count();
$cartTotal = pickled_cart_total();
$selectedPayment = $isSelectedPaymentValid ? $selectedPayment : CheckoutController::defaultPaymentMethod();
$paymentFee = CheckoutController::feeFor($selectedPayment, $cartTotal);
$checkoutTotal = $cartTotal + $paymentFee;
$displayTotal = $isCheckout ? $checkoutTotal : $cartTotal;
$cartSecondsRemaining = !empty($_SESSION['cart_expires_at']) ? max(0, (int) $_SESSION['cart_expires_at'] - time()) : 0;
$member = pickled_is_member();
$waitlist = $_SESSION['waitlist'] ?? [];

$extraHead = '<link rel="stylesheet" href="../assets/css/cart.css?v=20260615a"/>';

include __DIR__ . '/../includes/header.php';
?>

<main class="cart-page">
  <div class="cart-shell">
    <div class="cart-top">
      <h1>Shopping Cart</h1>
      <div class="cart-top-links">
        <a href="booking.php">Booking status</a>
        <a href="courts.php#court-detail">Continue shopping</a>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="cart-message cart-message--<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <section class="cart-layout">
      <div>
        <?php if (empty($cartItems)): ?>
          <div class="empty-cart">
            <p>Your cart is empty. Browse Court Green, Court Pink, coaching, or social play to start a reservation.</p>
            <a class="btn btn-green btn-md" href="courts.php#court-detail">Book now</a>
          </div>
        <?php else: ?>
          <div class="cart-list">
            <?php foreach ($cartItems as $item): ?>
              <article class="cart-item">
                <img src="<?= htmlspecialchars($item['image'] ?? '../assets/img/Hero.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
                <div>
                  <h2><?= htmlspecialchars($item['court']) ?></h2>
                  <p><?= htmlspecialchars($item['name']) ?> · <?= htmlspecialchars($item['category']) ?></p>
                  <div class="cart-tags">
                    <span><?= htmlspecialchars($item['duration']) ?></span>
                    <span><?= htmlspecialchars((string) $item['quantity']) ?> participant<?= (int) $item['quantity'] === 1 ? '' : 's' ?></span>
                    <span><?= htmlspecialchars($item['availability']) ?></span>
                    <?php if (!empty($item['member_discount'])): ?><span>Member discount</span><?php endif; ?>
                  </div>
                  <p>Date: <?= htmlspecialchars($item['date']) ?>, <?= htmlspecialchars($item['time']) ?> (Asia/Manila)</p>
                  <?php if ($cartSecondsRemaining > 0): ?>
                    <p class="cart-expire">Reservation will expire in <span data-cart-time>--:--</span></p>
                  <?php endif; ?>
                  <form method="post">
                    <input type="hidden" name="action" value="update_quantity" />
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                    <input type="hidden" name="cart_id" value="<?= htmlspecialchars($item['id']) ?>" />
                    <label>
                      Participants
                      <input type="number" name="quantity" min="1" max="<?= htmlspecialchars((string) pickled_cart_limit()) ?>" value="<?= htmlspecialchars((string) $item['quantity']) ?>" />
                    </label>
                    <button class="cart-remove" type="submit">Update</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="action" value="remove" />
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                    <input type="hidden" name="cart_id" value="<?= htmlspecialchars($item['id']) ?>" />
                    <button class="cart-remove" type="submit">Remove</button>
                  </form>
                </div>
                <div class="cart-price">₱<?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['last_booking'])): ?>
          <section id="booking-status" class="confirmation">
            <h2>Booking Created</h2>
            <p>Reference: <?= htmlspecialchars($_SESSION['last_booking']['reference']) ?></p>
            <p>Status: <?= htmlspecialchars($_SESSION['last_booking']['status']) ?> · <?= htmlspecialchars($_SESSION['last_booking']['cancellation_policy']['label']) ?></p>
            <p>Customer: <?= htmlspecialchars($_SESSION['last_booking']['customer_name'] ?? 'Guest') ?></p>
            <p>Payment: <?= htmlspecialchars($_SESSION['last_booking']['payment_method'] ?? 'GCash') ?> · <?= htmlspecialchars($_SESSION['last_booking']['payment_status'] ?? 'pending') ?></p>
            <p>Amount due: ₱<?= number_format((float) ($_SESSION['last_booking']['total'] ?? 0), 2) ?></p>
            <p><a class="btn btn-green btn-sm" href="booking-details.php?id=<?= (int) ($_SESSION['last_booking']['id'] ?? 0) ?>">Upload payment receipt</a></p>
            <?php if (!empty($_SESSION['last_booking']['items'])): $confirmedItem = reset($_SESSION['last_booking']['items']); ?>
              <p>Reserved: <?= htmlspecialchars($confirmedItem['court'] ?? 'Court') ?> · <?= htmlspecialchars($confirmedItem['name'] ?? 'Session') ?> · <?= htmlspecialchars($confirmedItem['date'] ?? '') ?> <?= htmlspecialchars($confirmedItem['time'] ?? '') ?></p>
            <?php endif; ?>
          </section>
        <?php endif; ?>
      </div>

      <aside class="cart-panel">
        <div class="cart-total">
          <h2>Total</h2>
          <strong>₱<?= number_format($displayTotal, 2) ?></strong>
        </div>

        <?php if (!$isCheckout): ?>
        <div class="review-card">
          <h2>Cart Review</h2>
          <p>Review your selected court/session first. Payment options will appear after you proceed to checkout.</p>
          <div class="checkout-summary">
            <span><small>Items</small><strong><?= $cartCount ?></strong></span>
            <span><small>Subtotal</small><strong>₱<?= number_format($cartTotal, 2) ?></strong></span>
          </div>
          <a href="cart.php?checkout=1" class="<?= empty($cartItems) ? 'is-disabled' : '' ?>" <?= empty($cartItems) ? 'aria-disabled="true"' : '' ?>>Checkout</a>
          <?php if (!empty($cartItems)): ?>
            <form method="post">
              <input type="hidden" name="action" value="clear" />
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
              <button class="cart-remove" type="submit">Clear cart</button>
            </form>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <form method="post" class="checkout-card">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
          <h2>Order Special Instructions</h2>
          <textarea name="notes" placeholder="Notes for the PICKLED team" maxlength="1000"></textarea>
          <h2 class="checkout-card__payment-title">Payment Method</h2>
          <?php include __DIR__ . '/../includes/payment-methods.php'; ?>
          <div class="gcash-instructions">
            <h3>GCash Payment Details</h3>
            <div class="gcash-detail">
              <span>GCash Number</span>
              <strong id="checkoutGcashNumber">0917 123 4567</strong>
              <button type="button" data-copy-target="checkoutGcashNumber">Copy</button>
            </div>
            <div class="gcash-detail">
              <span>Account Name</span>
              <strong>PICKLED SPORTS CENTER</strong>
            </div>
            <div class="gcash-detail">
              <span>Payment Note</span>
              <strong>Use the booking reference number.</strong>
            </div>
            <ol>
              <li>Send payment through GCash.</li>
              <li>Use your booking reference as the payment note.</li>
              <li>Upload your receipt or screenshot.</li>
              <li>Wait for admin verification.</li>
            </ol>
          </div>
          <div class="checkout-summary">
            <span><small>Subtotal</small><strong data-subtotal="<?= htmlspecialchars((string) $cartTotal) ?>">₱<?= number_format($cartTotal, 2) ?></strong></span>
            <span><small>Payment fee</small><strong data-payment-fee>₱<?= number_format($paymentFee, 2) ?></strong></span>
            <span><small>Total</small><strong data-checkout-total>₱<?= number_format($checkoutTotal, 2) ?></strong></span>
          </div>
          <div class="policy">
            <p><strong>Cancellation &amp; Payment Policy:</strong></p>
            <p>- Pending unpaid bookings may be cancelled anytime before payment expires.</p>
            <p>- Unpaid bookings automatically expire after 30 minutes.</p>
            <p>- Confirmed bookings may be cancelled up to 24 hours before the scheduled time.</p>
            <p>- Cancellations after receipt upload or verified payment are subject to admin refund review.</p>
            <p>- Refunds, if approved, are processed manually through GCash.</p>
            <p>- Bookings within 24 hours of the scheduled time can no longer be cancelled.</p>
            <p>- No-show bookings are forfeited.</p>
          </div>
          <label class="terms"><input type="checkbox" name="terms" value="1" /> I agree with Terms & Conditions</label>
          <input type="hidden" name="action" value="checkout" />
          <button class="checkout-btn" type="submit" <?= empty($cartItems) ? 'disabled' : '' ?>>Create booking</button>
        </form>
        <?php endif; ?>

        <?php if ($waitlist): ?>
          <div class="waitlist-card">
            <h3>Waitlist</h3>
            <?php foreach ($waitlist as $entry): ?>
              <p><?= htmlspecialchars($entry['court']) ?> · <?= htmlspecialchars($entry['name']) ?> · Position <?= (int) $entry['position'] ?></p>
            <?php endforeach; ?>
            <p>You have 15 minutes to claim an available slot once notified.</p>
          </div>
        <?php endif; ?>
      </aside>
    </section>
  </div>
</main>

<?php if ($cartSecondsRemaining > 0): ?>
<script>
(function(){
  var outputs = document.querySelectorAll("[data-cart-time]");
  var expiresAt = <?= (int) ($_SESSION['cart_expires_at'] ?? 0) ?> * 1000;
  function tick(){
    var remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
    outputs.forEach(function(output){
      output.textContent = Math.floor(remaining / 60) + "m" + String(remaining % 60).padStart(2, "0") + "s";
    });
    if (remaining <= 0) window.location.href = "cart.php?expired=1";
    else window.setTimeout(tick, 1000);
  }
  tick();
})();
</script>
<?php endif; ?>

<script>
(function(){
  var subtotalNode = document.querySelector("[data-subtotal]");
  var feeNode = document.querySelector("[data-payment-fee]");
  var totalNode = document.querySelector("[data-checkout-total]");
  if (!subtotalNode || !feeNode || !totalNode) return;

  var subtotal = Number(subtotalNode.getAttribute("data-subtotal")) || 0;
  var formatter = new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" });

  document.querySelectorAll(".payment-option input").forEach(function(input){
    input.addEventListener("change", function(){
      document.querySelectorAll(".payment-option").forEach(function(option){ option.classList.remove("is-selected"); });
      var option = input.closest(".payment-option");
      option.classList.add("is-selected");
      var fee = subtotal * (Number(option.getAttribute("data-fee-rate")) || 0);
      feeNode.textContent = formatter.format(fee).replace("PHP", "₱");
      totalNode.textContent = formatter.format(subtotal + fee).replace("PHP", "₱");
    });
  });

  document.querySelectorAll("[data-copy-target]").forEach(function(button){
    button.addEventListener("click", function(){
      var target = document.getElementById(button.getAttribute("data-copy-target") || "");
      var value = target ? target.textContent.trim() : "";
      if (!value || !navigator.clipboard) return;
      navigator.clipboard.writeText(value).then(function(){
        button.textContent = "Copied";
        window.setTimeout(function(){ button.textContent = "Copy"; }, 1600);
      }).catch(function(){
        button.textContent = "Copy failed";
        window.setTimeout(function(){ button.textContent = "Copy"; }, 1600);
      });
    });
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
