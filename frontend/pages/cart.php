<?php
session_start();
require_once __DIR__ . '/../../backend/includes/booking_system.php';
require_once __DIR__ . '/../../backend/controllers/CheckoutController.php';

pickled_require_login('pages/cart.php');

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
if (isset($_GET['booked']) && !empty($_SESSION['last_booking'])) {
  $message = 'Booking confirmed. Reference: ' . $_SESSION['last_booking']['reference'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'remove') {
    $cartId = (string) ($_POST['cart_id'] ?? '');
    unset($_SESSION['cart'][$cartId]);
    if (empty($_SESSION['cart'])) {
      pickled_clear_cart_timer();
    }
    header('Location: cart.php');
    exit;
  }

  if ($action === 'clear') {
    $_SESSION['cart'] = [];
    pickled_clear_cart_timer();
    header('Location: cart.php');
    exit;
  }

  if ($action === 'add_custom') {
    $name = trim($_POST['name'] ?? '');
    $price = max(0, (float) ($_POST['price'] ?? 0));
    $description = trim($_POST['description'] ?? '');
    $quantity = max(1, min(1, (int) ($_POST['quantity'] ?? 1)));
    $cartId = 'custom-' . substr(sha1($name . '|' . $price . '|' . $description), 0, 14);

    if ($name === '' || $price <= 0) {
      header('Location: cart.php');
      exit;
    }

    if (isset($_SESSION['cart'][$cartId])) {
      header('Location: cart.php?duplicate=1');
      exit;
    }

    if (pickled_cart_count() + $quantity > PICKLED_CART_LIMIT) {
      header('Location: cart.php?limit=1');
      exit;
    }

    pickled_start_cart_timer();
    $_SESSION['cart'][$cartId] = [
      'id' => $cartId,
      'variant_id' => 'custom',
      'name' => $name,
      'court' => str_contains(strtoupper($description), 'PINK') ? 'Court Pink' : 'Court Green',
      'category' => str_contains(strtoupper($name . ' ' . $description), 'SOCIAL') || str_contains(strtoupper($name), 'MATCH') ? 'Social Play' : 'Court booking',
      'price' => pickled_member_discount($price),
      'base_price' => $price,
      'member_discount' => pickled_is_member(),
      'quantity' => $quantity,
      'duration' => $description ?: 'Selected session',
      'date' => (new DateTimeImmutable('+3 days'))->format('F j, Y'),
      'time' => 'Selected schedule',
      'participants' => $quantity,
      'availability' => 'Temporarily reserved',
      'description' => $description,
      'image' => '../assets/Images/Hero.jpg',
      'status' => 'Reserved in cart',
      'created_at' => date('Y-m-d H:i:s'),
    ];

    header('Location: cart.php?added=1');
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
      $items = $_SESSION['cart'];
      $subtotal = pickled_cart_total();
      $paymentFee = CheckoutController::feeFor($selectedPayment, $subtotal);
      $total = $subtotal + $paymentFee;
      $firstBookingDate = new DateTimeImmutable('+3 days');
      $policy = pickled_cancellation_policy($firstBookingDate->getTimestamp());

      $_SESSION['last_booking'] = [
        'reference' => 'PKL-' . strtoupper(substr(sha1(uniqid('', true)), 0, 8)),
        'items' => $items,
        'customer_name' => $_SESSION['user']['name'] ?? 'Guest',
        'subtotal' => $subtotal,
        'payment_fee' => $paymentFee,
        'total' => $total,
        'status' => $selectedPayment === 'cash' ? 'Pending Payment' : 'Confirmed',
        'payment_method' => CheckoutController::methodLabel($selectedPayment),
        'payment_status' => $selectedPayment === 'cash' ? 'pay on site' : 'paid demo checkout',
        'cancellation_policy' => $policy,
        'notes' => trim($_POST['notes'] ?? ''),
        'created_at' => date('Y-m-d H:i:s'),
      ];

      $_SESSION['cart'] = [];
      pickled_clear_cart_timer();
      header('Location: cart.php?booked=1');
      exit;
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

$extraHead = '<style>
  .cart-page{background:#f6efe1;min-height:100vh;color:#204f3b;padding:calc(var(--nav-h) + 36px) clamp(16px,4vw,56px) 80px}
  .cart-shell{max-width:1380px;margin:0 auto}
  .cart-top{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:36px}
  .cart-top h1{font-size:clamp(48px,8vw,96px);line-height:.86;text-transform:uppercase;color:#204f3b}
  .cart-top a{border:1px solid rgba(36,95,73,.2);border-radius:8px;background:#fff;padding:16px 26px;font-weight:900}
  .cart-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(360px,.62fr);gap:28px;align-items:start}
  .cart-list{display:grid;gap:16px}
  .cart-item{display:grid;grid-template-columns:170px minmax(0,1fr) auto;gap:22px;align-items:center;background:#fff;border:1px solid rgba(36,95,73,.16);border-radius:8px;padding:18px}
  .cart-item img{width:170px;height:130px;object-fit:cover;border-radius:8px}
  .cart-item h2{font-size:24px;text-transform:uppercase;margin-bottom:8px}
  .cart-item p{font-weight:800;line-height:1.45;color:#35634f}
  .cart-tags{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
  .cart-tags span{border:1px solid rgba(36,95,73,.22);border-radius:999px;padding:6px 9px;font-size:11px;font-weight:900;text-transform:uppercase}
  .cart-expire{color:#e60023!important;font-weight:900!important}
  .cart-price{text-align:right;font-weight:900;font-size:28px;color:#204f3b}
  .cart-remove{margin-top:16px;background:none;color:#e60023;text-decoration:underline;font-weight:900}
  .cart-panel{position:sticky;top:calc(var(--nav-h) + 24px);display:grid;gap:18px}
  .cart-total{background:#204f3b;color:#f5bad9;border-radius:8px;padding:28px;display:flex;justify-content:space-between;gap:18px;align-items:flex-start}
  .cart-total h2{font-size:clamp(32px,4vw,54px);text-transform:uppercase;line-height:1}
  .cart-total strong{font-size:clamp(32px,4vw,54px);line-height:1;color:#f5bad9}
  .checkout-card{background:#fff;border:1px solid rgba(36,95,73,.16);border-radius:8px;padding:24px}
  .checkout-card h2{font-size:28px;text-transform:uppercase;margin-bottom:16px}
  .checkout-card textarea{width:100%;min-height:150px;border:1px solid rgba(36,95,73,.2);border-radius:8px;padding:14px;resize:vertical;font:inherit;color:#204f3b}
  .payment-methods{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:14px 0 18px}
  .payment-option{display:grid;gap:4px;border:1px solid rgba(36,95,73,.18);border-radius:8px;padding:13px;background:#f8f4ea;cursor:pointer}
  .payment-option input{position:absolute;opacity:0;pointer-events:none}
  .payment-option span{font-weight:900;color:#204f3b}
  .payment-option small{font-weight:800;color:#5d745f}
  .payment-option:has(input:checked),.payment-option.is-selected{border-color:#204f3b;background:#e8ffe1;box-shadow:0 0 0 3px rgba(36,95,73,.08)}
  .checkout-summary{display:grid;gap:8px;margin:16px 0;padding:14px;border-radius:8px;background:#f8f4ea;font-weight:900}
  .checkout-summary span{display:flex;justify-content:space-between;gap:12px}
  .policy{font-weight:700;line-height:1.55;margin:18px 0;color:#35634f}
  .terms{display:flex;gap:10px;align-items:center;font-weight:900;margin-bottom:18px}
  .terms input{width:22px;height:22px}
  .checkout-btn{width:100%;background:#70e956;color:#10240e;border-radius:8px;padding:16px;font-size:18px;font-weight:900}
  .review-card{background:#fff;border:1px solid rgba(36,95,73,.16);border-radius:8px;padding:24px;display:grid;gap:14px}
  .review-card h2{font-size:28px;text-transform:uppercase}
  .review-card p{font-weight:800;color:#35634f;line-height:1.5}
  .review-card a{display:block;text-align:center;background:#70e956;color:#10240e;border-radius:8px;padding:16px;font-size:18px;font-weight:900}
  .review-card a.is-disabled{opacity:.45;pointer-events:none}
  .cart-message{margin-bottom:22px;padding:16px 18px;border-radius:8px;background:#e8ffe1;color:#245f49;font-weight:900}
  .cart-message--warning{background:#fff6cf;color:#6d4b00}.cart-message--error{background:#ffe4ef;color:#8f1d4f}
  .empty-cart{background:#fff;border:1px solid rgba(36,95,73,.16);border-radius:8px;padding:34px}
  .empty-cart p{font-size:18px;font-weight:800;margin-bottom:18px}
  .confirmation{background:#fff;border:1px solid rgba(36,95,73,.16);border-radius:8px;padding:24px;margin-top:18px}
  .confirmation h2{font-size:28px;text-transform:uppercase}.confirmation p{font-weight:800;margin-top:8px}
  .waitlist-card{background:#fff6fb;border:1px solid rgba(248,86,150,.25);border-radius:8px;padding:18px}
  .waitlist-card h3{text-transform:uppercase;margin-bottom:8px}.waitlist-card p{font-weight:800;line-height:1.45}
  @media(max-width:980px){.cart-layout{grid-template-columns:1fr}.cart-panel{position:static}.cart-item{grid-template-columns:130px 1fr}.cart-price{grid-column:1/-1;text-align:left}}
  @media(max-width:620px){.cart-top{display:grid}.cart-item{grid-template-columns:1fr}.cart-item img{width:100%;height:220px}.cart-total{display:grid}.payment-methods{grid-template-columns:1fr}}
</style>';

include '../includes/_header.php';
?>

<main class="cart-page">
  <div class="cart-shell">
    <div class="cart-top">
      <h1>Shopping Cart</h1>
      <a href="courts.php#court-detail">Continue shopping</a>
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
          <section class="confirmation">
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
          <h2>Order Special Instructions</h2>
          <textarea name="notes" placeholder="Notes for the PICKLED team"></textarea>
          <h2 style="margin-top:20px;">Payment Method</h2>
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

<?php include '../includes/_footer.php'; ?>
