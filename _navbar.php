<?php
// _navbar.php - shared site navigation
$activePage = $activePage ?? '';
$links = [
  'index.php'       => 'Home',
  'courts.php'      => 'Courts',
  'social-play.php' => 'Social Play',
  'private.php'     => 'Private',
  'contact.php'     => 'Contact',
];
$lightLogoPages = ['courts.php', 'social-play.php', 'login.php'];
$whiteNavTextPages = ['courts.php', 'social-play.php'];
$logoImage = in_array($activePage, $lightLogoPages, true) ? 'Images/WM-LPink.png' : 'Images/WM-DGreen.png';
$navClasses = 'nav' . (in_array($activePage, $whiteNavTextPages, true) ? ' nav--white-actions' : '');
$loggedIn = !empty($_SESSION['user']);
$cartCount = !empty($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$bookNowHref = $loggedIn ? 'booking.php' : 'login.php?notice=booking&redirect=booking.php';
?>

<div class="promo-bar">Promotion - ₱250 Trial Class</div>

<nav class="<?= htmlspecialchars($navClasses) ?>" id="mainNav">
  <div class="nav-inner">
    <a href="index.php" class="logo">
      <img src="<?= htmlspecialchars($logoImage) ?>" alt="Pickled" class="logo-image" />
    </a>

    <div class="nav-links">
      <?php foreach ($links as $href => $label): ?>
        <a href="<?= htmlspecialchars($href) ?>" class="nav-link <?= $activePage === $href ? 'active' : '' ?>">
          <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="nav-right">
      <div class="nav-sep"></div>
      <a href="<?= htmlspecialchars($bookNowHref) ?>" class="btn btn-green btn-sm">Book Now</a>
      <a href="cart.php" class="nav-cart<?= $activePage === 'cart.php' ? ' active' : '' ?>" aria-label="Cart<?= $cartCount ? ' with ' . $cartCount . ' items' : '' ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="9" cy="20" r="1.8"></circle>
          <circle cx="18" cy="20" r="1.8"></circle>
          <path d="M3 4h2l2.3 10.6a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H6"></path>
        </svg>
        <?php if ($cartCount): ?>
          <span class="nav-cart__count"><?= $cartCount ?></span>
        <?php endif; ?>
      </a>
      <?php if ($loggedIn): ?>
        <span class="nav-user">Welcome, <?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['email'] ?? 'Member') ?></span>
        <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-ghost btn-sm">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
(function(){
  var nav = document.getElementById('mainNav');
  var promo = document.querySelector('.promo-bar');
  window.addEventListener('scroll', function(){
    var hidden = window.scrollY > 24;
    nav.classList.toggle('scrolled', window.scrollY > 30);
    document.body.classList.toggle('promo-hidden', hidden);
    if (promo) promo.setAttribute('aria-hidden', hidden ? 'true' : 'false');
  });
})();
</script>
