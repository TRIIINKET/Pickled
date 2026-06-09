<?php
require_once __DIR__ . '/../includes/security.php';
pickled_start_secure_session();

$logoutTarget = '../index.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: ' . $logoutTarget);
    exit;
}

// Clear user-related session data including the session-only cart on logout.
unset($_SESSION['user'], $_SESSION['cart'], $_SESSION['cart_started_at'], $_SESSION['cart_expires_at'], $_SESSION['last_booking']);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: ' . $logoutTarget);
exit;
