<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/../repositories/PaymentRepository.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';
require_once __DIR__ . '/BookingExpiryService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/AdminLogService.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/../../includes/upload-helper.php';
require_once __DIR__ . '/../../database/Database.php';

final class PaymentService
{
    private const MAX_UPLOAD_BYTES = 10485760;

    public function __construct(
        private readonly PaymentRepository $payments = new PaymentRepository(),
        private readonly BookingRepository $bookings = new BookingRepository(),
        private readonly NotificationService $notifications = new NotificationService(),
        private readonly AdminLogService $adminLogs = new AdminLogService()
    ) {}

    public function uploadReceipt(int $userId, int $bookingId, array $file, string $referenceNumber): array
    {
        (new BookingExpiryService())->processExpiredPendingBookings();

        $booking = $this->bookings->findByIdForUser($bookingId, $userId);
        if (!$booking) {
            throw new RuntimeException('Booking not found.');
        }
        if (($booking['status'] ?? '') === 'cancelled') {
            throw new RuntimeException('Cancelled bookings cannot accept payment uploads.');
        }

        $latest = $this->payments->latestForBooking($bookingId);
        $latestHasProof = $latest ? $this->paymentHasReceipt($latest) : false;
        if (($latest['status'] ?? null) === 'pending' && $latestHasProof) {
            throw new RuntimeException('A payment receipt is already waiting for admin review.');
        }
        if (($latest['status'] ?? null) === 'approved') {
            throw new RuntimeException('This booking already has an approved payment.');
        }

        if ($this->missingReceiptFile($file)) {
            error_log('Payment receipt upload rejected because no file was selected. booking_id=' . $bookingId . '; user_id=' . $userId);
            throw new RuntimeException('Please choose a receipt file before submitting.');
        }

        $referenceNumber = trim($referenceNumber);
        if ($referenceNumber === '') {
            throw new RuntimeException('Payment reference number is required.');
        }

        $proofPath = $this->storeProofImage($bookingId, $file);
        try {
            if (($latest['status'] ?? null) === 'pending' && !$latestHasProof) {
                $paymentId = (int) $latest['id'];
                $this->payments->updateUpload($paymentId, [
                    'proof_image' => $proofPath,
                    'amount' => (float) $booking['total'],
                    'payment_method' => CheckoutController::GCASH_LABEL,
                    'reference_number' => $referenceNumber,
                    'status' => 'pending',
                    'remarks' => null,
                ]);
                error_log('Payment receipt updated pending placeholder. booking_id=' . $bookingId . '; payment_id=' . $paymentId . '; proof_path=' . $proofPath);
            } else {
                $paymentId = $this->payments->create([
                    'booking_id' => $bookingId,
                    'proof_image' => $proofPath,
                    'amount' => (float) $booking['total'],
                    'payment_method' => CheckoutController::GCASH_LABEL,
                    'reference_number' => $referenceNumber,
                    'status' => 'pending',
                    'remarks' => null,
                ]);
                error_log('Payment receipt inserted. booking_id=' . $bookingId . '; payment_id=' . $paymentId . '; proof_path=' . $proofPath);
            }
        } catch (Throwable $e) {
            error_log('Payment receipt database save failed. booking_id=' . $bookingId . '; proof_path=' . $proofPath . '; error=' . $e->getMessage());
            throw new RuntimeException('Receipt was uploaded, but payment review could not be saved. Please contact support.');
        }

        $this->bookings->updateStatus($bookingId, 'pending');
        $this->bookings->updatePaymentStatus($bookingId, 'pending');

        $payment = $this->payments->findById($paymentId) ?? [];
        $this->adminLogs->recordPaymentUploaded($booking, $payment);
        $this->notifications->notifyPaymentUploaded($booking, $payment);

        return $payment;
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
            $booking = $this->bookings->findById($bookingId);
            if ($status === 'approved' && (!$booking || ($booking['status'] ?? '') === 'cancelled')) {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return false;
            }

            $updated = $this->payments->updateReview($paymentId, $status, $adminId, $remarks ?: null);
            if (!$updated) {
                if ($startedTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return false;
            }

            $updatedBooking = $this->bookings->findById($bookingId) ?? $booking;
            $updatedPayment = $this->payments->findById($paymentId) ?? $payment;
            $updatedBooking['payment_status'] = $status;
            if ($status === 'approved') {
                $this->adminLogs->recordPaymentApproved($updatedBooking, $updatedPayment, $adminId);
                $this->adminLogs->recordBookingConfirmed($updatedBooking, $adminId);
                $this->notifications->notifyPaymentApproved($updatedBooking);
            } else {
                $this->adminLogs->recordPaymentRejected($updatedBooking, $updatedPayment, $adminId, $remarks);
                $this->notifications->notifyPaymentRejected($updatedBooking, $remarks);
            }

            if ($startedTransaction) {
                $pdo->commit();
            }
            $this->sendPaymentReviewEmail($updatedBooking, $status, $remarks);
            return true;
        } catch (Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function sendPaymentReviewEmail(array $booking, string $status, string $remarks): void
    {
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) ($booking['user_id'] ?? 0)]);
            $user = $stmt->fetch() ?: null;
            if (!$user) {
                return;
            }

            $booking['items'] = $this->bookings->getBookingItems((int) ($booking['id'] ?? 0));
            $email = new EmailService();
            if ($status === 'approved') {
                $email->sendPaymentApproved($user, $booking);
            } else {
                $email->sendPaymentRejected($user, $booking, $remarks);
            }
        } catch (Throwable $e) {
            error_log('Payment review email failed: ' . $e->getMessage());
        }
    }

    private function storeProofImage(int $bookingId, array $file): string
    {
        try {
            return pickled_upload_file(
                $file,
                'assets/uploads/payments',
                [
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png'],
                    'webp' => ['image/webp'],
                    'pdf' => ['application/pdf', 'application/x-pdf'],
                ],
                self::MAX_UPLOAD_BYTES,
                'payment_' . $bookingId
            );
        } catch (RuntimeException $e) {
            error_log('Payment receipt upload failed: ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'folder')) {
                throw new RuntimeException('Receipt upload is temporarily unavailable. Please try again later.');
            }
            if (str_contains($e->getMessage(), 'too large')) {
                throw new RuntimeException('Receipt file must be 10MB or smaller.');
            }
            if (str_contains($e->getMessage(), 'type')) {
                throw new RuntimeException('Receipt must be a JPG, JPEG, PNG, WEBP, or PDF file.');
            }
            if (str_contains($e->getMessage(), 'choose a valid file')) {
                throw new RuntimeException('Please choose a receipt file before submitting.');
            }
            throw new RuntimeException($e->getMessage());
        }
    }

    private function missingReceiptFile(array $file): bool
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return false;
        }

        return trim((string) ($file['tmp_name'] ?? '')) === '' || (int) ($file['size'] ?? 0) <= 0;
    }

    private function paymentHasReceipt(array $payment): bool
    {
        $path = trim((string) ($payment['proof_image'] ?? ''));
        if ($path === '') {
            return false;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return false;
        }

        return is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    }
}
