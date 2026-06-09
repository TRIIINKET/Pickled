<?php
require_once __DIR__ . '/../includes/security.php';

pickled_start_secure_session();
session_destroy();
header('Location: admin-login.php');
exit;
?>
