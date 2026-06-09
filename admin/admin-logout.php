<?php
require_once __DIR__ . '/../includes/helpers/security.php';

pickled_start_secure_session();
session_destroy();
header('Location: admin-login.php');
exit;
?>
