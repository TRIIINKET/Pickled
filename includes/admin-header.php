<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/admin-paths.php';

pickled_start_secure_session();

// Check if user is admin
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: admin-login.php');
    exit;
}

$pageTitle = $pageTitle ?? 'Admin Dashboard';
$activePage = $activePage ?? 'dashboard';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Pickled Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo pickled_admin_asset_url('css/admin-style.css'); ?>">
    <?php
    if (file_exists(__DIR__ . '/../../assets/css/admin-' . str_replace('-', '', $activePage) . '.css')) {
        echo '<link rel="stylesheet" href="' . pickled_admin_asset_url('css/admin-' . str_replace('-', '', $activePage) . '.css') . '">';
    }
    ?>
</head>
<body<?php echo $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
