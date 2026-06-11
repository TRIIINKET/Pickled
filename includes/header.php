<?php
// Shared HTML head. Set $pageTitle and $activePage before including.
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/security.php';

pickled_start_secure_session();

$pageTitle  = $pageTitle  ?? 'Pickled - Indoor Pickleball · Manila';
$activePage = $activePage ?? '';
$showInitialLoader = !empty($showInitialLoader);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="Pickled is an indoor pickleball venue in Manila. Book courts, join open play, take coaching sessions, and compete in tournaments. All levels welcome."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@300;400;500;600;700;800;900&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet"/>
<<<<<<< HEAD
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/style.css?v=20260611b')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/navbar.css?v=20260610d')) ?>"/>
=======
  <link rel="preload" as="image" href="<?= htmlspecialchars(pickled_asset_url('img/WM-LPink.png')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/style.css?v=20260610a')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/global-loader.css?v=20260611a')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/navbar.css?v=20260611a')) ?>"/>
>>>>>>> f169e4afbc6fafd9f05ee3b517904c3a5c8733fd
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/footer.css?v=20260610a')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/privacy.css?v=20260610a')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/terms.css?v=20260610a')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/cancellation.css?v=20260610a')) ?>"/>
  <script>document.documentElement.classList.add('global-loader-enabled'<?= $showInitialLoader ? ", 'global-loader-active', 'global-loader-booting'" : '' ?>);</script>
  <script src="<?= htmlspecialchars(pickled_asset_url('js/global-loader.js?v=20260611a')) ?>"></script>
  <?= $extraHead ?? '' ?>
</head>
<body>

<?php include __DIR__ . '/global-loader.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<?php if (!empty($_SESSION['flash'])): ?>
  <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div style="position:relative; z-index: 899; padding: 12px clamp(16px, 4vw, 56px); background: <?= htmlspecialchars(($flash['type'] ?? 'info') === 'warning' ? '#FFF4D6' : (($flash['type'] ?? 'info') === 'error' ? '#FBE4E8' : '#EAF4E2')) ?>; color: #264414; border-bottom: 1px solid rgba(38,68,20,.12); font-family: 'DM Sans', sans-serif; font-size: 14px; line-height: 1.45;">
    <div style="max-width: var(--max-w); margin: 0 auto;">
      <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
    </div>
  </div>
<?php endif; ?>
