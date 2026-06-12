<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';
require_once __DIR__ . '/../app/services/BookingExpiryService.php';

function expiry_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expiry_session(PDO $pdo, int $sessionId): array
{
    $stmt = $pdo->prepare('SELECT id, capacity, booked_count, status FROM sessions WHERE id = ? LIMIT 1');
    $stmt->execute([$sessionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    expiry_assert((bool) $row, 'Session row was not found.');
    return $row;
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $variantId = (int) $pdo->query('SELECT id FROM booking_variants WHERE active = 1 ORDER BY id LIMIT 1')->fetchColumn();
    expiry_assert($variantId > 0, 'No active booking variant found for test setup.');

    $email = 'booking-expiry-test-' . bin2hex(random_bytes(4)) . '@example.com';
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'player')");
    $stmt->execute(['Booking Expiry Test', $email, password_hash('secret123', PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    $sessionDate = '2099-' . str_pad((string) random_int(2, 11), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) random_int(1, 27), 2, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare(
        "INSERT INTO sessions (variant_id, coach_user_id, session_date, start_time, end_time, capacity, booked_count, status)
         VALUES (?, NULL, ?, '06:00:00', '07:00:00', 3, 0, 'open')"
    );
    $stmt->execute([$variantId, $sessionDate]);
    $sessionId = (int) $pdo->lastInsertId();

    $repo = new BookingRepository();
    $booking = $repo->create($userId, [
        'reference' => 'EXP-' . strtoupper(bin2hex(random_bytes(4))),
        'status' => 'pending',
        'subtotal' => 1000,
        'payment_fee' => 0,
        'total' => 1000,
        'payment_method' => 'Manual Online Payment',
        'payment_status' => 'pending',
        'notes' => 'Expiry validation',
        'cancellation_label' => 'Standard cancellation policy',
        'items' => [[
            'session_id' => $sessionId,
            'variant_slug' => 'expiry-test-variant',
            'name' => 'Expiry Test Session',
            'court' => 'Test Court',
            'category' => 'Test',
            'duration' => '1 hour',
            'booking_date' => $sessionDate,
            'start_time' => '06:00:00',
            'end_time' => '07:00:00',
            'quantity' => 2,
            'price' => 500,
            'image' => null,
        ]],
    ]);
    $bookingId = (int) $booking['id'];

    $pdo->prepare("UPDATE bookings SET created_at = DATE_SUB(NOW(), INTERVAL 25 HOUR) WHERE id = ?")->execute([$bookingId]);
    $pdo->prepare(
        "INSERT INTO payments (booking_id, proof_image, amount, payment_method, reference_number, status)
         VALUES (?, 'assets/uploads/payments/test-expiry-proof.png', 1000, 'Manual Online Payment', 'EXP-PAY-001', 'pending')"
    )->execute([$bookingId]);

    $session = expiry_session($pdo, $sessionId);
    expiry_assert((int) $session['booked_count'] === 2, 'Pending booking did not reserve session capacity.');

    $service = new BookingExpiryService($repo);
    $expiredCount = $service->processExpiredPendingBookings();
    expiry_assert($expiredCount >= 1, 'Expiry service did not process the old pending booking.');

    $expiredBooking = $repo->findById($bookingId);
    expiry_assert(($expiredBooking['status'] ?? '') === 'cancelled', 'Expired booking was not cancelled.');
    expiry_assert(($expiredBooking['payment_status'] ?? '') === 'expired', 'Expired booking payment status was not marked expired.');
    expiry_assert(str_contains(strtolower((string) ($expiredBooking['cancellation_label'] ?? '')), 'expired'), 'Expired booking cancellation label was not visible.');

    $paymentStatus = (string) $pdo->query("SELECT status FROM payments WHERE booking_id = {$bookingId} ORDER BY id DESC LIMIT 1")->fetchColumn();
    expiry_assert($paymentStatus === 'rejected', 'Pending payment proof was not rejected after booking expiry.');

    $session = expiry_session($pdo, $sessionId);
    expiry_assert((int) $session['booked_count'] === 0, 'Expired booking did not release session capacity.');
    expiry_assert((string) $session['status'] === 'open', 'Expired booking did not reopen released session capacity.');
    expiry_assert($repo->bookedCountMismatches() === [], 'Booked count mismatch found after pending booking expiry.');
    expiry_assert($service->processExpiredPendingBookings() === 0, 'Expiry processing should be idempotent after the first run.');

    echo "Pending booking expiry test passed.\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
