<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$appConfig = require __DIR__ . '/../config/app.php';

// Clear user-related session data including the session-only cart on logout.
unset($_SESSION['user'], $_SESSION['cart'], $_SESSION['cart_started_at'], $_SESSION['cart_expires_at'], $_SESSION['last_booking']);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
    setcookie($appConfig['login_cookie']['name'], '', time() - 42000, '/');
}
session_destroy();
$logoutTarget = defined('PICKLED_FRONTEND_ENTRY') ? 'index.php' : '../../frontend/index.php';
header('Location: ' . $logoutTarget);
exit;
