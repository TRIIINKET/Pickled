<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function session_row(PDO $pdo, int $sessionId): array
{
    $stmt = $pdo->prepare('SELECT id, capacity, booked_count, status FROM sessions WHERE id = ? LIMIT 1');
    $stmt->execute([$sessionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true((bool) $row, 'Session row was not found.');
    return $row;
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $variantId = (int) $pdo->query('SELECT id FROM booking_variants WHERE active = 1 ORDER BY id LIMIT 1')->fetchColumn();
    assert_true($variantId > 0, 'No active booking variant found for test setup.');

    $email = 'booking-cancel-test-' . bin2hex(random_bytes(4)) . '@example.com';
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'player')");
    $stmt->execute(['Booking Cancel Test', $email, password_hash('secret123', PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        "INSERT INTO sessions (variant_id, coach_user_id, session_date, start_time, end_time, capacity, booked_count, status)
         VALUES (?, NULL, '2099-12-31', '06:00:00', '07:00:00', 3, 0, 'open')"
    );
    $stmt->execute([$variantId]);
    $sessionId = (int) $pdo->lastInsertId();

    $repo = new BookingRepository();
    $booking = $repo->create($userId, [
        'reference' => 'TST-' . strtoupper(bin2hex(random_bytes(4))),
        'status' => 'confirmed',
        'subtotal' => 1000,
        'payment_fee' => 0,
        'total' => 1000,
        'payment_method' => 'GCash',
        'payment_status' => 'paid',
        'notes' => 'Cancellation consistency validation',
        'cancellation_label' => 'Test policy',
        'items' => [[
            'session_id' => $sessionId,
            'variant_slug' => 'test-variant',
            'name' => 'Cancellation Test Session',
            'court' => 'Test Court',
            'category' => 'Test',
            'duration' => '1 hour',
            'booking_date' => '2099-12-31',
            'start_time' => '06:00:00',
            'end_time' => '07:00:00',
            'quantity' => 2,
            'price' => 500,
            'image' => null,
        ]],
    ]);
    $bookingId = (int) $booking['id'];

    $session = session_row($pdo, $sessionId);
    assert_true((int) $session['booked_count'] === 2, 'Booking creation did not reserve capacity.');
    assert_true($repo->bookedCountMismatches() === [], 'Consistency mismatch after booking creation.');

    assert_true($repo->updateStatus($bookingId, 'cancelled'), 'Cancelling booking failed.');
    $session = session_row($pdo, $sessionId);
    assert_true((int) $session['booked_count'] === 0, 'Cancellation did not release capacity.');
    assert_true((string) $session['status'] === 'open', 'Full session was not reopened after cancellation.');
    assert_true($repo->bookedCountMismatches() === [], 'Consistency mismatch after cancellation.');

    assert_true($repo->updateStatus($bookingId, 'cancelled'), 'Repeated cancellation should remain safe.');
    $session = session_row($pdo, $sessionId);
    assert_true((int) $session['booked_count'] === 0, 'Repeated cancellation decremented capacity twice.');

    assert_true($repo->updateStatus($bookingId, 'confirmed'), 'Reactivating cancelled booking failed.');
    $session = session_row($pdo, $sessionId);
    assert_true((int) $session['booked_count'] === 2, 'Reactivation did not reserve capacity.');
    assert_true($repo->bookedCountMismatches() === [], 'Consistency mismatch after reactivation.');

    $pdo->prepare("UPDATE sessions SET booked_count = 1, status = 'open' WHERE id = ?")->execute([$sessionId]);
    assert_true($repo->updateStatus($bookingId, 'cancelled'), 'Cancellation with low booked_count failed.');
    $session = session_row($pdo, $sessionId);
    assert_true((int) $session['booked_count'] === 0, 'Cancellation allowed booked_count to go negative.');

    $pdo->prepare("UPDATE sessions SET capacity = 1, booked_count = 1, status = 'full' WHERE id = ?")->execute([$sessionId]);
    assert_true(!$repo->updateStatus($bookingId, 'confirmed'), 'Reactivation should fail when capacity is unavailable.');
    $session = session_row($pdo, $sessionId);
    assert_true((int) $session['booked_count'] === 1, 'Failed reactivation changed booked_count.');
    $stored = $repo->findById($bookingId);
    assert_true(($stored['status'] ?? '') === 'cancelled', 'Failed reactivation changed booking status.');

    echo "Booking cancellation consistency test passed.\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
