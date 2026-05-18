<?php
// _header.php - shared HTML head
// Usage: set $basePath (optional, defaults to ''), then include 'includes/_header.php' or '../includes/_header.php'
// Set $pageTitle and $activePage before including.

$basePath = $basePath ?? '';
$appConfig = require __DIR__ . '/../../backend/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// The cart lives only in PHP session data. When the short login cookie expires,
// clear login and pending cart state so protected pages cannot reuse old data.
if (empty($_SESSION['user']) || !isset($_COOKIE[$appConfig['login_cookie']['name']])) {
    unset($_SESSION['user'], $_SESSION['cart'], $_SESSION['cart_started_at'], $_SESSION['cart_expires_at'], $_SESSION['last_booking']);
}

$pageTitle  = $pageTitle  ?? 'Pickled - Indoor Pickleball · Manila';
$activePage = $activePage ?? '';
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
  <link rel="stylesheet" href="<?= $basePath ?>css/style.css?v=20260430d"/>
  <link rel="stylesheet" href="<?= $basePath ?>css/navbar.css?v=20260430d"/>
  <link rel="stylesheet" href="<?= $basePath ?>css/footer.css?v=20260430d"/>
  <link rel="stylesheet" href="<?= $basePath ?>css/privacy.css?v=20260430d"/>
  <link rel="stylesheet" href="<?= $basePath ?>css/terms.css?v=20260430d"/>
  <link rel="stylesheet" href="<?= $basePath ?>css/cancellation.css?v=20260430d"/>
  <?= $extraHead ?? '' ?>
</head>
<body>

<?php include '_navbar.php'; ?>
