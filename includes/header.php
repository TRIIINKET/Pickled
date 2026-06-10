<?php
// Shared HTML head. Set $pageTitle and $activePage before including.
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/security.php';

pickled_start_secure_session();

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
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/style.css?v=20260610a')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/navbar.css?v=20260430d')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/footer.css?v=20260430d')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/privacy.css?v=20260430d')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/terms.css?v=20260430d')) ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(pickled_asset_url('css/cancellation.css?v=20260430d')) ?>"/>
  <?= $extraHead ?? '' ?>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>
