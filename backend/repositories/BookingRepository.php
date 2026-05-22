<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/CatalogRepository.php';

final class BookingRepository
{
    public function __construct(private readonly CatalogRepository $catalog = new CatalogRepository()) {}

    public function create(int $userId, array $booking): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO bookings (user_id, reference, status, subtotal, payment_fee, total, payment_method, payment_status, notes, cancellation_label)
                 VALUES (:user_id, :reference, :status, :subtotal, :payment_fee, :total, :payment_method, :payment_status, :notes, :cancellation_label)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'reference' => $booking['reference'],
                'status' => $booking['status'],
                'subtotal' => $booking['subtotal'],
                'payment_fee' => $booking['payment_fee'],
                'total' => $booking['total'],
                'payment_method' => $booking['payment_method'],
                'payment_status' => $booking['payment_status'],
                'notes' => $booking['notes'],
                'cancellation_label' => $booking['cancellation_policy']['label'],
            ]);
            $bookingId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO booking_items (booking_id, session_id, variant_id, name, court, category, duration_label, booking_date, booking_time, quantity, unit_price, image)
                 VALUES (:booking_id, :session_id, :variant_id, :name, :court, :category, :duration_label, :booking_date, :booking_time, :quantity, :unit_price, :image)'
            );
            foreach ($booking['items'] as $item) {
                if (!$this->catalog->incrementBookedCount((int) $item['session_id'], (int) $item['quantity'])) {
                    throw new RuntimeException('One of the selected sessions is already full.');
                }
                $itemStmt->execute([
                    'booking_id' => $bookingId,
                    'session_id' => $item['session_id'],
                    'variant_id' => $item['variant_id'] ?? 'custom',
                    'name' => $item['name'],
                    'court' => $item['court'],
                    'category' => $item['category'],
                    'duration_label' => $item['duration'],
                    'booking_date' => $item['date'],
                    'booking_time' => $item['time'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'image' => $item['image'] ?? null,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $booking + ['id' => $bookingId];
    }

    // Admin methods
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $booking = $stmt->fetch();
        return $booking ?: null;
    }

    public function findAll($limit = 50, $offset = 0): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->execute(['limit' => $limit, 'offset' => $offset]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByStatus(string $status): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE status = :status ORDER BY created_at DESC');
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByPaymentStatus(string $status): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE payment_status = :status ORDER BY created_at DESC');
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByUserId(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE bookings SET status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function updatePaymentStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE bookings SET payment_status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function getBookingItems(int $bookingId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM booking_items WHERE booking_id = :booking_id');
        $stmt->execute(['booking_id' => $bookingId]);
        return $stmt->fetchAll() ?: [];
    }

    public function getTotalCount(): int
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) as count FROM bookings');
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0);
    }
}