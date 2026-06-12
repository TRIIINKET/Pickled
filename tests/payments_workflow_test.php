<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/services/CartService.php';
require_once __DIR__ . '/../app/services/CheckoutService.php';
require_once __DIR__ . '/../app/services/PaymentService.php';

function payments_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function payments_booking(PDO $pdo, int $bookingId): array
{
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    payments_assert((bool) $booking, 'Booking row was not found.');
    return $booking;
}

function payments_image_file(string $name): array
{
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=',
        true
    );
    payments_assert($png !== false, 'Could not create receipt image bytes.');
    file_put_contents($path, $png);

    return [
        'name' => $name,
        'type' => 'image/png',
        'tmp_name' => $path,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($path),
    ];
}

function payments_cleanup_files(array $paths): void
{
    foreach ($paths as $path) {
        $absolute = __DIR__ . '/../' . ltrim($path, '/\\');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}

$pdo = Database::connection();
$createdProofs = [];
$tempFiles = [];
$pdo->beginTransaction();

try {
    $variant = $pdo->query(
        'SELECT slug, price FROM booking_variants WHERE active = 1 ORDER BY id LIMIT 1'
    )->fetch(PDO::FETCH_ASSOC);
    payments_assert((bool) $variant, 'No active booking variant found for test setup.');

    $adminId = (int) $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetchColumn();
    if ($adminId <= 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['Payments Admin Test', 'payments-admin-test-' . bin2hex(random_bytes(4)) . '@example.com', password_hash('secret123', PASSWORD_DEFAULT)]);
        $adminId = (int) $pdo->lastInsertId();
    }

    $email = 'payments-player-test-' . bin2hex(random_bytes(4)) . '@example.com';
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'player')");
    $stmt->execute(['Payments Player Test', $email, password_hash('secret123', PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    $cart = new CartService();
    $checkout = new CheckoutService();
    $payments = new PaymentService();
    $bookingRepo = new BookingRepository();

    $startedAt = time();
    $expiresAt = $startedAt + 900;
    $added = $cart->addVariantForUser($userId, (string) $variant['slug'], 1, '2099-12-31', '6:00 AM - 7:00 AM', $startedAt, $expiresAt, (float) $variant['price']);
    payments_assert(($added['ok'] ?? false) === true, 'Could not add a session to the cart.');

    $booking = $checkout->createBooking($userId, [], 'Payments Player Test', 'manual_online', 'Payments workflow validation');
    $bookingId = (int) $booking['id'];
    $storedBooking = payments_booking($pdo, $bookingId);
    payments_assert($storedBooking['status'] === 'pending', 'Checkout did not create a pending booking.');
    payments_assert($storedBooking['payment_status'] === 'pending', 'Checkout did not create a pending payment status.');

    $firstFile = payments_image_file('payment-proof-a.png');
    $tempFiles[] = $firstFile['tmp_name'];
    $firstPayment = $payments->uploadReceipt($userId, $bookingId, $firstFile, 'PAY-REF-001');
    $createdProofs[] = $firstPayment['proof_image'];
    payments_assert($firstPayment['status'] === 'pending', 'Receipt upload did not create a pending payment.');

    try {
        $duplicateFile = payments_image_file('payment-proof-duplicate.png');
        $tempFiles[] = $duplicateFile['tmp_name'];
        $payments->uploadReceipt($userId, $bookingId, $duplicateFile, 'PAY-REF-DUP');
        payments_assert(false, 'Duplicate upload while pending should not be allowed.');
    } catch (RuntimeException $e) {
        payments_assert(str_contains($e->getMessage(), 'already waiting'), 'Unexpected duplicate upload validation message.');
    }

    payments_assert($payments->reject((int) $firstPayment['id'], $adminId, 'Receipt is unclear.'), 'Admin payment rejection failed.');
    $storedBooking = payments_booking($pdo, $bookingId);
    payments_assert($storedBooking['status'] === 'pending', 'Rejected payment should keep booking pending.');
    payments_assert($storedBooking['payment_status'] === 'rejected', 'Rejected payment should mark booking payment_status rejected.');

    $secondFile = payments_image_file('payment-proof-b.png');
    $tempFiles[] = $secondFile['tmp_name'];
    $secondPayment = $payments->uploadReceipt($userId, $bookingId, $secondFile, 'PAY-REF-002');
    $createdProofs[] = $secondPayment['proof_image'];
    payments_assert($secondPayment['status'] === 'pending', 'Receipt re-upload after rejection failed.');

    payments_assert($payments->approve((int) $secondPayment['id'], $adminId, 'Payment verified.'), 'Admin payment approval failed.');
    $storedBooking = payments_booking($pdo, $bookingId);
    payments_assert($storedBooking['status'] === 'confirmed', 'Approved payment did not confirm booking.');
    payments_assert($storedBooking['payment_status'] === 'paid', 'Approved payment did not mark booking paid.');

    $reviewedPayment = $payments->latestForBooking($bookingId);
    payments_assert(($reviewedPayment['status'] ?? '') === 'approved', 'Latest payment was not approved.');
    payments_assert((int) ($reviewedPayment['reviewed_by'] ?? 0) === $adminId, 'Approved payment reviewer was not stored.');
    payments_assert(!empty($reviewedPayment['reviewed_at']), 'Approved payment reviewed_at was not stored.');

    try {
        $thirdFile = payments_image_file('payment-proof-after-approval.png');
        $tempFiles[] = $thirdFile['tmp_name'];
        $payments->uploadReceipt($userId, $bookingId, $thirdFile, 'PAY-REF-003');
        payments_assert(false, 'Upload after approval should not be allowed.');
    } catch (RuntimeException $e) {
        payments_assert(str_contains($e->getMessage(), 'approved payment'), 'Unexpected approved upload validation message.');
    }

    payments_assert($bookingRepo->bookedCountMismatches() === [], 'Payment workflow caused booked_count mismatch.');

    echo "Payments workflow test passed.\n";
} finally {
    payments_cleanup_files($createdProofs);
    foreach ($tempFiles as $tempFile) {
        if (is_file($tempFile)) {
            @unlink($tempFile);
        }
    }
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
