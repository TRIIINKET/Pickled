<?php
session_start();
$appConfig = require __DIR__ . '/../../backend/config/app.php';

if (empty($_SESSION['user']) || !isset($_COOKIE[$appConfig['login_cookie']['name']])) {
  unset($_SESSION['user'], $_SESSION['membership'], $_SESSION['cart'], $_SESSION['cart_started_at'], $_SESSION['cart_expires_at'], $_SESSION['last_booking'], $_SESSION['waitlist']);
  header('Location: ../login.php?notice=booking&redirect=pages/courts.php%23court-detail');
  exit;
}

header('Location: courts.php#court-detail');
exit;
