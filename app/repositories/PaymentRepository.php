<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';

final class PaymentRepository
{
    private ?string $proofColumn = null;

    public function create(array $data): int
    {
        $proofColumn = $this->proofColumn();
        $stmt = Database::connection()->prepare(
            'INSERT INTO payments
                (booking_id, ' . $proofColumn . ', amount, payment_method, reference_number, status, remarks)
             VALUES
                (:booking_id, :proof_image, :amount, :payment_method, :reference_number, :status, :remarks)'
        );
        $stmt->execute([
            'booking_id' => (int) $data['booking_id'],
            'proof_image' => $data['proof_image'],
            'amount' => (float) $data['amount'],
            'payment_method' => $data['payment_method'] ?? CheckoutController::GCASH_LABEL,
            'reference_number' => $data['reference_number'],
            'status' => $data['status'] ?? 'pending',
            'remarks' => $data['remarks'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateUpload(int $id, array $data): bool
    {
        $proofColumn = $this->proofColumn();
        $stmt = Database::connection()->prepare(
            'UPDATE payments
             SET ' . $proofColumn . ' = :proof_image,
                 amount = :amount,
                 payment_method = :payment_method,
                 reference_number = :reference_number,
                 status = :status,
                 remarks = :remarks
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'proof_image' => $data['proof_image'],
            'amount' => (float) $data['amount'],
            'payment_method' => $data['payment_method'] ?? CheckoutController::GCASH_LABEL,
            'reference_number' => $data['reference_number'],
            'status' => $data['status'] ?? 'pending',
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    public function findById(int $id, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM payments WHERE id = :id LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $payment = $stmt->fetch();
        return $payment ? $this->normalizePayment($payment) : null;
    }

    public function latestForBooking(int $bookingId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM payments WHERE booking_id = :booking_id ORDER BY created_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['booking_id' => $bookingId]);
        $payment = $stmt->fetch();
        return $payment ? $this->normalizePayment($payment) : null;
    }

    public function latestPendingForBooking(int $bookingId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT * FROM payments WHERE booking_id = :booking_id AND status = 'pending' ORDER BY created_at DESC, id DESC LIMIT 1";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['booking_id' => $bookingId]);
        $payment = $stmt->fetch();
        return $payment ? $this->normalizePayment($payment) : null;
    }

    public function findByBookingId(int $bookingId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, u.name AS reviewer_name
             FROM payments p
             LEFT JOIN users u ON u.id = p.reviewed_by
             WHERE p.booking_id = :booking_id
             ORDER BY p.created_at DESC, p.id DESC'
        );
        $stmt->execute(['booking_id' => $bookingId]);
        return array_map(fn(array $payment): array => $this->normalizePayment($payment), $stmt->fetchAll() ?: []);
    }

    public function updateReview(int $id, string $status, int $adminId, ?string $remarks): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE payments
             SET status = :status,
                 reviewed_by = :reviewed_by,
                 reviewed_at = NOW(),
                 remarks = :remarks
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'reviewed_by' => $adminId,
            'remarks' => $remarks,
        ]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM payments WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    private function proofColumn(): string
    {
        if ($this->proofColumn !== null) {
            return $this->proofColumn;
        }

        $pdo = Database::connection();
        foreach (['proof_of_payment', 'proof_image'] as $column) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name'
            );
            $stmt->execute([
                'table_name' => 'payments',
                'column_name' => $column,
            ]);
            if ((int) $stmt->fetchColumn() > 0) {
                return $this->proofColumn = $column;
            }
        }

        return $this->proofColumn = 'proof_image';
    }

    private function normalizePayment(array $payment): array
    {
        $proof = (string) ($payment['proof_of_payment'] ?? $payment['proof_image'] ?? '');
        $payment['proof_of_payment'] = $proof;
        $payment['proof_image'] = $proof;
        return $payment;
    }
}
