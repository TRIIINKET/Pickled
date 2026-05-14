<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=cart.php');
    exit;
}

$pageTitle  = 'Shopping Cart - Pickled';
$activePage = 'cart.php';
$extraHead  = '<style>
  .cart-page { padding: calc(var(--nav-h) + 90px) 20px 80px; max-width: 1200px; margin: 0 auto; }
  .cart-hero__inner { max-width: 780px; margin: 0 auto 36px; text-align: center; }
  .cart-hero p { text-transform: uppercase; letter-spacing: .18em; color: #245f49; font-weight: 700; margin-bottom: 14px; }
  .cart-hero h1 { font-size: clamp(2.4rem, 4vw, 4.2rem); margin-bottom: 12px; }
  .cart-hero span { color: #4a4a4a; font-size: 1rem; line-height: 1.7; }
  .cart-layout { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(250px, .55fr); gap: 28px; align-items: start; }
  .product-grid { display: grid; gap: 22px; }
  .cart-products { position: sticky; top: calc(var(--nav-h) + 24px); }
  .cart-products h2 { font-size: 1rem; margin-bottom: 14px; }
  .product-card { border: 1px solid rgba(36,95,73,.12); border-radius: 14px; padding: 16px; background: #fff; }
  .product-card h3 { margin-bottom: 8px; font-size: 1rem; }
  .product-card p { margin-bottom: 12px; color: #4a4a4a; font-size: .9rem; line-height: 1.45; }
  .product-card strong { display: block; margin-bottom: 12px; font-size: 1rem; }
  .product-card label { display: block; margin-bottom: 14px; font-size: .95rem; color: #333; }
  .product-card input[type="number"] { width: 80px; padding: 8px 10px; border: 1px solid rgba(36,95,73,.16); border-radius: 8px; }
  .cart-summary-card { border: 1px solid rgba(36,95,73,.12); border-radius: 18px; padding: 28px; background: #fff; }
  .cart-summary-card h2 { margin-bottom: 8px; }
  .cart-summary-card p { margin-bottom: 20px; color: #4a4a4a; }
  .cart-list { list-style: none; padding: 0; margin: 0 0 20px; }
  .cart-list li { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(36,95,73,.08); }
  .cart-list li:last-child { border-bottom: none; }
  .cart-list strong { display: block; margin-bottom: 6px; }
  .cart-remove-form button { border: none; background: transparent; color: #bb2f6e; cursor: pointer; font-size: .95rem; }
  .cart-total { display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 700; margin-bottom: 18px; }
  .cart-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
  .cart-message { margin-bottom: 18px; padding: 14px 16px; border-radius: 12px; background: #e8ffe1; color: #245f49; font-weight: 700; }
  .empty-cart { padding: 18px 14px; background: #f7f5f0; border-radius: 14px; color: #4a4a4a; }
  @media (max-width: 940px) { .cart-layout { grid-template-columns: 1fr; } .cart-products { position: static; } }
</style>';

$products = [
  101 => [
    'name' => 'Court Green Open Session',
    'price' => 350,
    'description' => '2-hour open match-play session for pick-up games and friendly competition.',
  ],
  102 => [
    'name' => 'Beginner Clinic',
    'price' => 450,
    'description' => 'A guided beginner session that teaches serving, scoring, and basic rallies.',
  ],
  103 => [
    'name' => 'Private Coaching Hour',
    'price' => 900,
    'description' => 'One-on-one coaching with a registered pickleball coach.',
  ],
];

$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$cartMessage = '';

if (isset($_GET['booked']) && !empty($_SESSION['last_booking'])) {
  $cartMessage = 'Booking processed. Reference: ' . $_SESSION['last_booking']['reference'];
}
if (isset($_GET['added'])) {
  $cartMessage = 'Item added to cart.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add' && isset($_POST['product_id'])) {
    $productId = (int) $_POST['product_id'];
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    if (isset($products[$productId])) {
      if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = [
          'id' => $productId,
          'name' => $products[$productId]['name'],
          'price' => $products[$productId]['price'],
          'description' => $products[$productId]['description'],
          'quantity' => 0,
        ];
      }

      $_SESSION['cart'][$productId]['quantity'] += $quantity;
    }
  }

  if ($action === 'remove' && isset($_POST['product_id'])) {
    $productId = (string) $_POST['product_id'];
    unset($_SESSION['cart'][$productId]);
  }

  if ($action === 'clear') {
    $_SESSION['cart'] = [];
  }

  if ($action === 'add_custom') {
    $name = trim($_POST['name'] ?? '');
    $price = max(0, (float) ($_POST['price'] ?? 0));
    $description = trim($_POST['description'] ?? '');
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    if ($name !== '' && $price > 0) {
      $productId = 'custom-' . substr(sha1($name . '|' . $price . '|' . $description), 0, 12);
      if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = [
          'id' => $productId,
          'name' => $name,
          'price' => $price,
          'description' => $description,
          'quantity' => 0,
        ];
      }

      $_SESSION['cart'][$productId]['quantity'] += $quantity;
      header('Location: cart.php?added=1');
      exit;
    }
  }

  if ($action === 'checkout') {
    if (!empty($_SESSION['cart'])) {
      $cartTotal = array_reduce($_SESSION['cart'], function ($sum, $item) {
        return $sum + ($item['price'] * $item['quantity']);
      }, 0);

      $_SESSION['last_booking'] = [
        'reference' => 'BK-' . strtoupper(substr(sha1(uniqid('', true)), 0, 10)),
        'items' => $_SESSION['cart'],
        'total' => $cartTotal,
        'created_at' => date('Y-m-d H:i:s'),
      ];

      $_SESSION['cart'] = [];
      header('Location: cart.php?booked=1');
      exit;
    }

    $cartMessage = 'Your cart is empty. Add a session before booking.';
  }
}

$cartItems = $_SESSION['cart'];
$cartCount = array_sum(array_column($cartItems, 'quantity'));
$cartTotal = array_reduce($cartItems, function ($sum, $item) {
  return $sum + ($item['price'] * $item['quantity']);
}, 0);
include '_header.php';
?>

<main class="cart-page">
  <section class="cart-hero">
    <div class="cart-hero__inner">
      <h1>Cart</h1>
    </div>
  </section>

  <section class="cart-layout">
    <section class="cart-summary">
      <div class="cart-summary-card">
        <h2>Your cart</h2>
        <p><?= $cartCount ?> item<?= $cartCount === 1 ? '' : 's' ?> added</p>

        <?php if ($cartMessage): ?>
          <div class="cart-message"><?= htmlspecialchars($cartMessage) ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
          <div class="empty-cart">
            <p>Your cart is empty. Add a session or coaching package to get started.</p>
          </div>
          <div class="cart-actions" style="margin-top: 18px;">
            <a class="btn btn-green btn-sm" href="booking.php">Book Now</a>
          </div>
        <?php else: ?>
          <ul class="cart-list">
            <?php foreach ($cartItems as $item): ?>
              <li>
                <span>
                  <strong><?= htmlspecialchars($item['name']) ?></strong>
                  <span><?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></span>
                </span>
                <form method="post" class="cart-remove-form">
                  <input type="hidden" name="action" value="remove" />
                  <input type="hidden" name="product_id" value="<?= htmlspecialchars((string) $item['id']) ?>" />
                  <button type="submit">Remove</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="cart-total">
            <span>Total</span>
            <strong>₱<?= number_format($cartTotal, 2) ?></strong>
          </div>

          <div class="cart-actions">
            <form method="post">
              <input type="hidden" name="action" value="checkout" />
              <button class="btn btn-green btn-sm" type="submit">Book Now</button>
            </form>
            <form method="post">
              <input type="hidden" name="action" value="clear" />
              <button class="btn btn-ghost btn-sm" type="submit">Clear cart</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <aside class="cart-products">
      <h2>Available items</h2>
      <div class="product-grid">
        <?php foreach ($products as $id => $product): ?>
          <article class="product-card">
            <h3><?= htmlspecialchars($product['name']) ?></h3>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <strong>₱<?= number_format($product['price'], 2) ?></strong>
            <form method="post">
              <input type="hidden" name="action" value="add" />
              <input type="hidden" name="product_id" value="<?= $id ?>" />
              <label>
                Quantity
                <input type="number" name="quantity" value="1" min="1" />
              </label>
              <button class="btn btn-green btn-sm" type="submit">Add to cart</button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    </aside>
  </section>
</main>

<?php include '_footer.php'; ?>
