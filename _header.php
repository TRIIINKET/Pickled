<?php
// _header.php - shared HTML head
// Usage: include '_header.php'; at top of each page
// Set $pageTitle and $activePage before including.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) || !isset($_COOKIE['login_cookie'])) {
    unset($_SESSION['user'], $_SESSION['cart'], $_SESSION['last_booking']);
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
  <link rel="stylesheet" href="assets/css/style.css?v=20260430d"/>
  <link rel="stylesheet" href="assets/css/navbar.css?v=20260430d"/>
  <link rel="stylesheet" href="assets/css/footer.css?v=20260430d"/>
  <link rel="stylesheet" href="assets/css/privacy.css?v=20260430d"/>
  <link rel="stylesheet" href="assets/css/terms.css?v=20260430d"/>
  <link rel="stylesheet" href="assets/css/cancellation.css?v=20260430d"/>
  <?= $extraHead ?? '' ?>
</head>
<body>

<?php include '_navbar.php'; ?>
