<?php
// Shared site navigation.
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/avatar-helper.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
$activePage = $activePage ?? '';
$links = [
  'index.php'       => 'Home',
  'courts.php'      => 'Courts',
  'social-play.php' => 'Social Play',
  'private.php'     => 'Private',
  'contact.php'     => 'Contact',
];
$mobileLinks = [
  pickled_frontend_url('index.php') => 'Home',
  pickled_frontend_url('resident/courts.php') => 'Courts',
  pickled_frontend_url('resident/social-play.php') => 'Social Play',
  pickled_frontend_url('resident/private.php') => 'Private',
  pickled_frontend_url('index.php#pickleball-101') => 'About',
  pickled_frontend_url('resident/contact.php') => 'Contact',
  pickled_frontend_url('auth/login.php') => 'Login',
];
$darkNavPages = ['courts.php', 'social-play.php', 'login.php'];
$useDarkNav = in_array($activePage, $darkNavPages, true);
$logoFile = $useDarkNav ? 'nav-logo-lpink.png' : 'nav-logo-dgreen.png';
$logoImage = pickled_asset_url('img/' . $logoFile);
$navClasses = 'nav' . ($useDarkNav ? ' nav--dark' : '');
$loggedIn = !empty($_SESSION['user']);
$cartCount = !empty($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$notificationUnreadCount = 0;
if ($loggedIn) {
    try {
        $notificationUnreadCount = (new NotificationService())->unreadCount((int) ($_SESSION['user']['id'] ?? 0));
    } catch (Throwable $e) {
        error_log('Notification badge failed: ' . $e->getMessage());
    }
}
$bookNowRedirect = 'resident/courts.php#court-detail';
$bookNowHref = $loggedIn ? pickled_frontend_url('resident/courts.php#court-detail') : pickled_frontend_url('auth/login.php?notice=booking&redirect=' . rawurlencode($bookNowRedirect));
if ($loggedIn && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$logoutCsrf = htmlspecialchars($_SESSION['csrf_token'] ?? '');
$accountName = trim((string) ($_SESSION['user']['name'] ?? 'Member'));
$accountEmail = trim((string) ($_SESSION['user']['email'] ?? 'member@pickled.ph'));
$accountFirstName = preg_split('/\s+/', $accountName)[0] ?? $accountName;
$accountFirstName = trim((string) $accountFirstName);
$accountInitial = strtoupper(substr($accountFirstName !== '' ? $accountFirstName : ($accountName !== '' ? $accountName : $accountEmail), 0, 1));
$accountAvatar = pickled_avatar_default_path();
if ($loggedIn && (int) ($_SESSION['user']['id'] ?? 0) > 0 && Database::enabled()) {
    try {
        $avatarStmt = Database::connection()->prepare('SELECT avatar FROM user_profiles WHERE user_id = :user_id LIMIT 1');
        $avatarStmt->execute(['user_id' => (int) $_SESSION['user']['id']]);
        $accountAvatar = trim((string) ($avatarStmt->fetchColumn() ?: $accountAvatar));
    } catch (Throwable $e) {
        error_log('Navbar avatar load failed: ' . $e->getMessage());
    }
}
$accountAvatarUrl = pickled_avatar_url($accountAvatar);
$hasAccountAvatar = $accountAvatar !== '' && $accountAvatar !== pickled_avatar_default_path();
?>

<div class="promo-bar">Promotion - ₱250 Trial Class</div>

<nav class="<?= htmlspecialchars($navClasses) ?>" id="mainNav">
  <div class="nav-inner">
    <a href="<?= htmlspecialchars(pickled_frontend_url('index.php')) ?>" class="logo">
      <img src="<?= htmlspecialchars($logoImage) ?>" alt="Pickled" class="logo-image" />
    </a>

    <button class="nav-menu-toggle" type="button" aria-label="Open navigation menu" aria-controls="mobileNavDrawer" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="nav-links">
      <?php foreach ($links as $href => $label): ?>
        <?php $navHref = $href === 'index.php' ? pickled_frontend_url($href) : pickled_frontend_url('resident/' . $href); ?>
        <a href="<?= htmlspecialchars($navHref) ?>" class="nav-link <?= $activePage === $href ? 'active' : '' ?>">
          <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="nav-right">
      <div class="nav-sep"></div>
      <a href="<?= htmlspecialchars($bookNowHref) ?>" class="btn btn-green btn-sm">Book Now</a>
      <a href="<?= htmlspecialchars(pickled_frontend_url('resident/cart.php')) ?>" class="nav-cart<?= $activePage === 'cart.php' ? ' active' : '' ?>" aria-label="Cart<?= $cartCount ? ' with ' . $cartCount . ' items' : '' ?>">
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
        <a href="<?= htmlspecialchars(pickled_frontend_url('resident/notifications.php')) ?>" class="nav-cart<?= $activePage === 'notifications.php' ? ' active' : '' ?>" aria-label="Notifications<?= $notificationUnreadCount ? ' with ' . $notificationUnreadCount . ' unread' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          <?php if ($notificationUnreadCount): ?>
            <span class="nav-cart__count"><?= min($notificationUnreadCount, 9) ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
      <?php if ($loggedIn): ?>
        <details class="nav-account" data-account-menu>
          <summary class="nav-account__trigger" aria-label="Open account menu">
            <span class="nav-account__avatar" aria-hidden="true">
              <?php if ($hasAccountAvatar): ?>
                <img src="<?= htmlspecialchars($accountAvatarUrl) ?>" alt="" onerror="this.remove(); this.parentElement.textContent='<?= htmlspecialchars($accountInitial) ?>';" />
              <?php else: ?>
                <?= htmlspecialchars($accountInitial) ?>
              <?php endif; ?>
            </span>
            <span class="nav-account__name"><?= htmlspecialchars($accountFirstName ?: 'Member') ?></span>
            <svg class="nav-account__chevron" viewBox="0 0 24 24" aria-hidden="true">
              <path d="m6 9 6 6 6-6"></path>
            </svg>
          </summary>
          <div class="nav-account__menu">
            <div class="nav-account__header">
              <span class="nav-account__large-avatar" aria-hidden="true">
                <?php if ($hasAccountAvatar): ?>
                  <img src="<?= htmlspecialchars($accountAvatarUrl) ?>" alt="" onerror="this.remove(); this.parentElement.textContent='<?= htmlspecialchars($accountInitial) ?>';" />
                <?php else: ?>
                  <?= htmlspecialchars($accountInitial) ?>
                <?php endif; ?>
              </span>
              <span>
                <strong><?= htmlspecialchars($accountFirstName ?: 'Member') ?></strong>
                <small><?= htmlspecialchars($accountEmail) ?></small>
              </span>
            </div>
            <div class="nav-account__links">
              <a href="<?= htmlspecialchars(pickled_frontend_url('resident/profile.php')) ?>"<?= $activePage === 'profile.php' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>
                My Profile
              </a>
              <a href="<?= htmlspecialchars(pickled_frontend_url('resident/booking.php')) ?>"<?= $activePage === 'booking.php' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg>
                My Bookings
              </a>
              <a href="<?= htmlspecialchars(pickled_frontend_url('resident/notifications.php')) ?>"<?= $activePage === 'notifications.php' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                Notifications<?php if ($notificationUnreadCount): ?> (<?= min($notificationUnreadCount, 99) ?>)<?php endif; ?>
              </a>
              <a href="<?= htmlspecialchars(pickled_frontend_url('resident/profile.php#payments')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M3 10h18M7 15h3"></path></svg>
                My Payments
              </a>
              <a href="<?= htmlspecialchars(pickled_frontend_url('resident/profile.php#settings')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7.1 4l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.9 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.7 1Z"></path></svg>
                Settings
              </a>
            </div>
            <form method="post" action="<?= htmlspecialchars(pickled_frontend_url('auth/logout.php')) ?>" class="nav-account__logout">
              <input type="hidden" name="csrf_token" value="<?= $logoutCsrf ?>" />
              <button type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17 15 12 10 7"></path><path d="M15 12H3"></path><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"></path></svg>
                Logout
              </button>
            </form>
          </div>
        </details>
      <?php else: ?>
        <a href="<?= htmlspecialchars(pickled_frontend_url('auth/login.php')) ?>" class="btn btn-ghost btn-sm">Sign In</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="nav-drawer-overlay" data-nav-close></div>
  <aside class="nav-drawer" id="mobileNavDrawer" aria-hidden="true">
    <div class="nav-drawer__head">
      <img src="<?= htmlspecialchars($logoImage) ?>" alt="Pickled" class="nav-drawer__logo" />
      <button class="nav-drawer__close" type="button" aria-label="Close navigation menu" data-nav-close>×</button>
    </div>
    <nav class="nav-drawer__links" aria-label="Mobile navigation">
      <?php foreach ($mobileLinks as $href => $label): ?>
        <?php if ($label === 'Login' && $loggedIn) continue; ?>
        <a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-drawer__actions">
      <a href="<?= htmlspecialchars($bookNowHref) ?>" class="btn btn-green">Book Now</a>
      <a href="<?= htmlspecialchars(pickled_frontend_url('resident/cart.php')) ?>" class="btn btn-ghost">Cart<?= $cartCount ? ' (' . $cartCount . ')' : '' ?></a>
      <?php if ($loggedIn): ?>
        <a href="<?= htmlspecialchars(pickled_frontend_url('resident/profile.php')) ?>" class="btn btn-ghost">My Profile</a>
      <?php endif; ?>
    </div>
  </aside>
</nav>

<script>
(function(){
  var nav = document.getElementById('mainNav');
  var promo = document.querySelector('.promo-bar');
  var account = document.querySelector('[data-account-menu]');
  var menuToggle = document.querySelector('.nav-menu-toggle');
  var drawer = document.getElementById('mobileNavDrawer');
  var drawerClosers = document.querySelectorAll('[data-nav-close]');

  function setDrawer(open) {
    if (!menuToggle || !drawer) return;
    nav.classList.toggle('nav--drawer-open', open);
    document.body.classList.toggle('nav-drawer-lock', open);
    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    menuToggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
  }

  if (menuToggle && drawer) {
    menuToggle.addEventListener('click', function(){
      setDrawer(!nav.classList.contains('nav--drawer-open'));
    });
    drawerClosers.forEach(function(closer){
      closer.addEventListener('click', function(){ setDrawer(false); });
    });
    drawer.querySelectorAll('a').forEach(function(link){
      link.addEventListener('click', function(){ setDrawer(false); });
    });
  }

  if (account) {
    document.addEventListener('click', function(event){
      if (!account.contains(event.target)) {
        account.removeAttribute('open');
      }
    });

    document.addEventListener('keydown', function(event){
      if (event.key === 'Escape') {
        account.removeAttribute('open');
        setDrawer(false);
      }
    });

    account.querySelectorAll('.nav-account__links a').forEach(function(link){
      link.addEventListener('click', function(){
        account.removeAttribute('open');
      });
    });
  }

  window.addEventListener('scroll', function(){
    var hidden = window.scrollY > 24;
    nav.classList.toggle('scrolled', window.scrollY > 30);
    document.body.classList.toggle('promo-hidden', hidden);
    if (promo) promo.setAttribute('aria-hidden', hidden ? 'true' : 'false');
  });
})();
</script>
