<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers/security.php';
pickled_start_secure_session();
require_once __DIR__ . '/../../includes/helpers/booking-system.php';

header('Content-Type: application/json');
echo json_encode([
    'count' => pickled_cart_count(),
    'total' => pickled_cart_total(),
    'expires_at' => $_SESSION['cart_expires_at'] ?? null,
]);
