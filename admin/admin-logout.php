<?php
require_once __DIR__ . '/../includes/security.php';

pickled_start_secure_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: admin-login.php');
    exit;
}

session_destroy();
header('Location: admin-login.php');
exit;
