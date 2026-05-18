<?php
require_once __DIR__ . '/../../backend/includes/security.php';
require_once __DIR__ . '/../../backend/includes/booking_system.php';
pickled_start_secure_session();

if (!pickled_is_logged_in()) {
  unset($_SESSION['user'], $_SESSION['membership'], $_SESSION['cart'], $_SESSION['cart_started_at'], $_SESSION['cart_expires_at'], $_SESSION['last_booking'], $_SESSION['waitlist']);
  header('Location: ../login.php?notice=booking&redirect=pages/courts.php%23court-detail');
  exit;
}

header('Location: courts.php#court-detail');
exit;
