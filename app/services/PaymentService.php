<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/../repositories/PaymentRepository.php';
require_once __DIR__ . '/../../database/Database.php';

final class PaymentService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_UPLOAD_BYTES = 5242880;

    public function __construct(
        private readonly PaymentRepository $payments = new PaymentRepository(),
        private readonly BookingRepository $bookings = new BookingRepository()
    ) {}

    public function uploadReceipt(int $userId, int $bookingId, array $file, string $referenceNumber): array
    {
        $booking = $this->bookings->findByIdForUser($bookingId, $userId);
        if (!$booking) {
            throw new RuntimeException('Booking not found.');
        }
        if (($booking['status'] ?? '') === 'cancelled') {
            throw new RuntimeException('Cancelled bookings cannot accept payment uploads.');
        }

        $latest = $this->payments->latestForBooking($bookingId);
        if (($latest['status'] ?? null) === 'pending') {
            throw new RuntimeException('A payment receipt is already waiting for admin review.');
        }
        if (($latest['status'] ?? null) === 'approved') {
            throw new RuntimeException('This booking already has an approved payment.');
        }

        $referenceNumber = trim($referenceNumber);
        if ($referenceNumber === '') {
            throw new RuntimeException('Payment reference number is required.');
        }

        $proofPath = $this->storeProofImage($bookingId, $file);
        $paymentId = $this->payments->create([
            'booking_id' => $bookingId,
            'proof_image' => $proofPath,
            'amount' => (float) $booking['total'],
            'payment_method' => (string) $booking['payment_method'],
            'reference_number' => $referenceNumber,
            'status' => 'pending',
            'remarks' => null,
        ]);

        $this->bookings->updateStatus($bookingId, 'pending');
        $this->bookings->updatePaymentStatus($bookingId, 'pending');

        return $this->payments->findById($paymentId) ?? [];
    }

    public function approveLatestForBooking(int $bookingId, int $adminId, string $remarks = ''): bool
    {
        $payment = $this->payments->latestPendingForBooking($bookingId);
        return $payment ? $this->approve((int) $payment['id'], $adminId, $remarks) : false;
    }

    public function rejectLatestForBooking(int $bookingId, int $adminId, string $remarks = ''): bool
    {
        $payment = $this->payments->latestPendingForBooking($bookingId);
        return $payment ? $this->reject((int) $payment['id'], $adminId, $remarks) : false;
    }

    public function approve(int $paymentId, int $adminId, string $remarks = ''): bool
    {
        return $this->review($paymentId, $adminId, 'approved', $remarks);
    }

    public function reject(int $paymentId, int $adminId, string $remarks = ''): bool
    {
        return $this->review($paymentId, $adminId, 'rejected', $remarks);
    }

    public function paymentsForBooking(int $bookingId): array
    {
        return $this->payments->findByBookingId($bookingId);
    }

    public function latestForBooking(int $bookingId): ?array
    {
        return $this->payments->latestForBooking($bookingId);
    }

    private function review(int $paymentId, int $adminId, string $status, string $remarks): bool
    {
        $pdo = Database::connection();
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $payment = $this->payments->findById($paymentId, true);
            if (!$payment || $payment['status'] !== 'pending') {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return false;
            }

            $remarks = trim($remarks);
            $bookingId = (int) $payment['booking_id'];
            $updated = $this->payments->updateReview($paymentId, $status, $adminId, $remarks ?: null);
            if (!$updated) {
                if ($startedTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return false;
            }

            if ($status === 'approved') {
                if (!$this->bookings->updateStatus($bookingId, 'confirmed')) {
                    if ($startedTransaction && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    return false;
                }
                $this->bookings->updatePaymentStatus($bookingId, 'paid');
            } else {
                $this->bookings->updateStatus($bookingId, 'pending');
                $this->bookings->updatePaymentStatus($bookingId, 'rejected');
            }

            if ($startedTransaction) {
                $pdo->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function storeProofImage(int $bookingId, array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload a valid payment receipt image.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpName === '' || $size <= 0 || $size > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('Receipt image must be 5MB or smaller.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';
        if (!isset(self::ALLOWED_MIME_TYPES[$mime])) {
            throw new RuntimeException('Receipt must be a JPG, PNG, or WEBP image.');
        }

        $uploadDir = __DIR__ . '/../../assets/uploads/payments';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Payment upload folder could not be created.');
        }

        $filename = 'payment-' . $bookingId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . self::ALLOWED_MIME_TYPES[$mime];
        $destination = $uploadDir . '/' . $filename;
        $stored = PHP_SAPI === 'cli'
            ? copy($tmpName, $destination)
            : move_uploaded_file($tmpName, $destination);

        if (!$stored) {
            throw new RuntimeException('Payment receipt upload failed.');
        }

        return 'assets/uploads/payments/' . $filename;
    }
}
