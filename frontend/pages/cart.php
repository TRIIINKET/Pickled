<?php
require_once __DIR__ . '/../../backend/includes/booking_system.php';
require_once __DIR__ . '/../../backend/includes/security.php';
require_once __DIR__ . '/../../backend/controllers/CheckoutController.php';
require_once __DIR__ . '/../../backend/services/CheckoutService.php';

pickled_start_secure_session();
pickled_init_csrf();
$csrfToken = pickled_csrf_token();

pickled_require_login('pages/cart.php');
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
$selectedPayment = $_POST['payment_method'] ?? 'gcash';
$paymentMethods = CheckoutController::paymentMethods();
$isCheckout = isset($_GET['checkout']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout');

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
if (isset($_GET['booked']) && !empty($_SESSION['last_booking'])) {
  $message = 'Booking confirmed. Reference: ' . $_SESSION['last_booking']['reference'];
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
      unset($_SESSION['cart'][(string) $cartId]);
      if (empty($_SESSION['cart'])) {
        pickled_clear_cart_timer();
      }
      pickled_persist_cart_for_user();
      header('Location: cart.php');
      exit;
    }

    if ($action === 'clear') {
      $_SESSION['cart'] = [];
      (new CartService())->clearForUser((int) $_SESSION['user']['id']);
      pickled_clear_cart_timer();
      pickled_persist_cart_for_user();
      header('Location: cart.php');
      exit;
    }

    if ($action === 'add_booking') {
      $variantId = trim((string) ($_POST['variant_id'] ?? ''));
      $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
      $date = trim((string) ($_POST['date'] ?? (new DateTimeImmutable('+3 days'))->format('F j, Y')));
      $time = trim((string) ($_POST['time'] ?? 'Selected schedule'));
      $result = pickled_add_to_cart($variantId, $quantity, $date, $time);
      header('Location: cart.php?' . ($result['ok'] ? 'added=1' : $result['code'] . '=1'));
      exit;
    }


  if ($action === 'checkout') {
    if (empty($_SESSION['cart'])) {
      $message = 'Your cart is empty. Add a booking before checkout.';
      $messageType = 'warning';
    } elseif (!isset($paymentMethods[$selectedPayment])) {
      $message = 'Please choose a valid payment method.';
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
          trim($_POST['notes'] ?? '')
        );
      } catch (RuntimeException $e) {
        $message = $e->getMessage();
        $messageType = 'warning';
      }

      if ($messageType !== 'warning') {
        $_SESSION['cart'] = [];
        (new CartService())->clearForUser((int) $_SESSION['user']['id']);
        pickled_clear_cart_timer();
        pickled_persist_cart_for_user();
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
$paymentFee = CheckoutController::feeFor($selectedPayment, $cartTotal);
$checkoutTotal = $cartTotal + $paymentFee;
$displayTotal = $isCheckout ? $checkoutTotal : $cartTotal;
$cartSecondsRemaining = !empty($_SESSION['cart_expires_at']) ? max(0, (int) $_SESSION['cart_expires_at'] - time()) : 0;
$member = pickled_is_member();
$waitlist = $_SESSION['waitlist'] ?? [];

$extraHead = '<link rel="stylesheet" href="../css/cart.css?v=20260430d"/>';

include __DIR__ . '/../includes/_header.php';
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
                <img src="<?= htmlspecialchars($item['image'] ?? '../assets/Images/Hero.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
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
            <h2>Booking Confirmation</h2>
            <p>Reference: <?= htmlspecialchars($_SESSION['last_booking']['reference']) ?></p>
            <p>Status: <?= htmlspecialchars($_SESSION['last_booking']['status']) ?> · <?= htmlspecialchars($_SESSION['last_booking']['cancellation_policy']['label']) ?></p>
            <p>Customer: <?= htmlspecialchars($_SESSION['last_booking']['customer_name'] ?? 'Guest') ?></p>
            <p>Payment: <?= htmlspecialchars($_SESSION['last_booking']['payment_method'] ?? 'GCash') ?> · <?= htmlspecialchars($_SESSION['last_booking']['payment_status'] ?? 'pending') ?></p>
            <p>Total paid: ₱<?= number_format((float) ($_SESSION['last_booking']['total'] ?? 0), 2) ?></p>
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
        </div>
        <?php else: ?>
        <form method="post" class="checkout-card">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
          <h2>Order Special Instructions</h2>
          <textarea name="notes" placeholder="Notes for the PICKLED team"></textarea>
          <h2 class="checkout-card__payment-title">Payment Method</h2>
          <?php include __DIR__ . '/../components/payment-methods.php'; ?>
          <div class="checkout-summary">
            <span><small>Subtotal</small><strong data-subtotal="<?= htmlspecialchars((string) $cartTotal) ?>">₱<?= number_format($cartTotal, 2) ?></strong></span>
            <span><small>Payment fee</small><strong data-payment-fee>₱<?= number_format($paymentFee, 2) ?></strong></span>
            <span><small>Total</small><strong data-checkout-total>₱<?= number_format($checkoutTotal, 2) ?></strong></span>
          </div>
          <div class="policy">
            <p>- Cancel before 48 hours: full credit.</p>
            <p>- Late cancellation or no-show: booking forfeited.</p>
            <p>- Weather issues may be rescheduled by PICKLED staff.</p>
            <p>- <?= $member ? 'Member benefits applied: zero guest fee and discounted pricing.' : 'Create a member account for discounts, priority access, and zero guest fees.' ?></p>
          </div>
          <label class="terms"><input type="checkbox" name="terms" value="1" /> I agree with Terms & Conditions</label>
          <input type="hidden" name="action" value="checkout" />
          <button class="checkout-btn" type="submit" <?= empty($cartItems) ? 'disabled' : '' ?>>Pay now</button>
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
})();
</script>

<?php include __DIR__ . '/../includes/_footer.php'; ?>