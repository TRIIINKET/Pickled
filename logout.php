<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear user-related session data including cart on logout.
unset($_SESSION['user'], $_SESSION['cart'], $_SESSION['last_booking']);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
    setcookie('login_cookie', '', time() - 42000, '/');
}
session_destroy();
header('Location: index.php');
exit;
