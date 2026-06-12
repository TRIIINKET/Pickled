<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/CatalogRepository.php';

final class BookingRepository
{
    private const BOOKING_STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    public function __construct(private readonly CatalogRepository $catalog = new CatalogRepository()) {}

    public function create(int $userId, array $booking): array
    {
        $items = $booking['items'] ?? [];
        if (!$items) {
            throw new RuntimeException('Your cart is empty. Add a booking before checkout.');
        }

        $pdo = Database::connection();
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO bookings
                    (user_id, reference, status, subtotal, payment_fee, total, payment_method, payment_status, notes, cancellation_label)
                 VALUES
                    (:user_id, :reference, :status, :subtotal, :payment_fee, :total, :payment_method, :payment_status, :notes, :cancellation_label)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'reference' => $booking['reference'],
                'status' => $this->normalizeBookingStatus((string) ($booking['status'] ?? 'pending')),
                'subtotal' => (float) ($booking['subtotal'] ?? 0),
                'payment_fee' => (float) ($booking['payment_fee'] ?? 0),
                'total' => (float) ($booking['total'] ?? 0),
                'payment_method' => (string) ($booking['payment_method'] ?? 'Unknown'),
                'payment_status' => $this->normalizePaymentStatus((string) ($booking['payment_status'] ?? 'pending')),
                'notes' => trim((string) ($booking['notes'] ?? '')) ?: null,
                'cancellation_label' => (string) ($booking['cancellation_policy']['label'] ?? $booking['cancellation_label'] ?? 'Standard cancellation policy'),
            ]);
            $bookingId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO booking_items
                    (booking_id, session_id, variant_slug, name, court, category, duration_label, booking_date, start_time, end_time, quantity, unit_price, image)
                 VALUES
                    (:booking_id, :session_id, :variant_slug, :name, :court, :category, :duration_label, :booking_date, :start_time, :end_time, :quantity, :unit_price, :image)'
            );

            foreach ($items as $item) {
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $sessionId = (int) ($item['session_id'] ?? 0);
                if ($sessionId <= 0) {
                    throw new RuntimeException('One of the selected sessions is no longer available.');
                }

                if (!$this->catalog->incrementBookedCount($sessionId, $quantity)) {
                    throw new RuntimeException('One of the selected sessions is already full.');
                }

                [$startTime, $endTime] = $this->timeSnapshot($item);
                $itemStmt->execute([
                    'booking_id' => $bookingId,
                    'session_id' => $sessionId,
                    'variant_slug' => (string) ($item['variant_slug'] ?? $item['variant_id'] ?? 'custom'),
                    'name' => (string) ($item['name'] ?? 'Booking'),
                    'court' => (string) ($item['court'] ?? 'Any Court'),
                    'category' => (string) ($item['category'] ?? 'Booking'),
                    'duration_label' => (string) ($item['duration_label'] ?? $item['duration'] ?? 'Scheduled session'),
                    'booking_date' => $this->dateSnapshot($item),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'quantity' => $quantity,
                    'unit_price' => (float) ($item['unit_price'] ?? $item['price'] ?? 0),
                    'image' => $item['image'] ?? null,
                ]);
            }

            $stored = $this->findById($bookingId) ?? [];
            $stored['items'] = $this->getBookingItems($bookingId);
            $result = $stored + $booking + ['id' => $bookingId];

            if ($startedTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function referenceExists(string $reference): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM bookings WHERE reference = :reference LIMIT 1');
        $stmt->execute(['reference' => $reference]);
        return (bool) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $booking = $stmt->fetch();
        return $booking ?: null;
    }

    public function findByIdForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $booking = $stmt->fetch();
        return $booking ?: null;
    }

    public function findAll($limit = 50, $offset = 0): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', max(1, (int) $limit), PDO::PARAM_INT);
        $stmt->bindValue('offset', max(0, (int) $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function findByStatus(string $status): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE status = :status ORDER BY created_at DESC');
        $stmt->execute(['status' => $this->normalizeBookingStatus($status)]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByPaymentStatus(string $status): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM bookings WHERE payment_status = :status ORDER BY created_at DESC');
        $stmt->execute(['status' => $this->normalizePaymentStatus($status)]);
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
        return $stmt->execute(['id' => $id, 'status' => $this->normalizeBookingStatus($status)]);
    }

    public function updatePaymentStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE bookings SET payment_status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $this->normalizePaymentStatus($status)]);
    }

    public function getBookingItems(int $bookingId): array
    {
        $stmt = Database::connection()->prepare($this->itemSelect() . ' WHERE booking_id = :booking_id ORDER BY id ASC');
        $stmt->execute(['booking_id' => $bookingId]);
        return $stmt->fetchAll() ?: [];
    }

    public function getItemsForCoach(int $coachUserId, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT bi.id,
                       bi.booking_id,
                       bi.session_id,
                       bi.variant_slug,
                       bi.variant_slug AS variant_id,
                       bi.name,
                       bi.court,
                       bi.category,
                       bi.duration_label,
                       bi.booking_date AS booking_date_raw,
                       DATE_FORMAT(bi.booking_date, '%W, %M %e, %Y') AS booking_date,
                       bi.start_time,
                       bi.end_time,
                       CONCAT(TIME_FORMAT(bi.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(bi.end_time, '%h:%i %p')) AS booking_time,
                       bi.quantity,
                       bi.unit_price,
                       bi.image,
                       bi.created_at,
                       b.reference,
                       b.status AS booking_status,
                       b.payment_status,
                       b.user_id,
                       u.name AS user_name,
                       u.email AS user_email
                FROM booking_items bi
                JOIN sessions s ON s.id = bi.session_id
                JOIN bookings b ON b.id = bi.booking_id
                LEFT JOIN users u ON u.id = b.user_id
                WHERE s.coach_user_id = :coach_user_id";
        $params = ['coach_user_id' => $coachUserId];

        if ($startDate) {
            $sql .= ' AND bi.booking_date >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $sql .= ' AND bi.booking_date <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql .= ' ORDER BY bi.booking_date ASC, bi.start_time ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function getTotalCount(): int
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) AS count FROM bookings');
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0);
    }

    private function itemSelect(string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        return "SELECT {$prefix}id,
                       {$prefix}booking_id,
                       {$prefix}session_id,
                       {$prefix}variant_slug,
                       {$prefix}variant_slug AS variant_id,
                       {$prefix}name,
                       {$prefix}court,
                       {$prefix}category,
                       {$prefix}duration_label,
                       {$prefix}booking_date AS booking_date_raw,
                       DATE_FORMAT({$prefix}booking_date, '%W, %M %e, %Y') AS booking_date,
                       {$prefix}start_time,
                       {$prefix}end_time,
                       CONCAT(TIME_FORMAT({$prefix}start_time, '%h:%i %p'), ' - ', TIME_FORMAT({$prefix}end_time, '%h:%i %p')) AS booking_time,
                       {$prefix}quantity,
                       {$prefix}unit_price,
                       {$prefix}image,
                       {$prefix}created_at
                FROM booking_items {$alias}";
    }

    private function normalizeBookingStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if (str_contains($status, 'cancel')) {
            return 'cancelled';
        }
        if (str_contains($status, 'complete')) {
            return 'completed';
        }
        if (str_contains($status, 'confirm') || str_contains($status, 'paid')) {
            return 'confirmed';
        }
        return in_array($status, self::BOOKING_STATUSES, true) ? $status : 'pending';
    }

    private function normalizePaymentStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if (str_contains($status, 'reject')) {
            return 'rejected';
        }
        if (str_contains($status, 'site')) {
            return 'pay on site';
        }
        if (str_contains($status, 'complete') || str_contains($status, 'paid')) {
            return 'paid';
        }
        return $status !== '' ? $status : 'pending';
    }

    private function dateSnapshot(array $item): string
    {
        $date = (string) ($item['booking_date'] ?? $item['date'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new RuntimeException('One of the selected booking dates is invalid.');
        }

        return date('Y-m-d', $timestamp);
    }

    private function timeSnapshot(array $item): array
    {
        $start = (string) ($item['start_time'] ?? '');
        $end = (string) ($item['end_time'] ?? '');
        if ($this->isSqlTime($start) && $this->isSqlTime($end)) {
            return [$start, $end];
        }

        $range = (string) ($item['booking_time'] ?? $item['time'] ?? '');
        $parts = preg_split('/\s+-\s+/', $range);
        if (!$parts || count($parts) !== 2) {
            throw new RuntimeException('One of the selected booking times is invalid.');
        }

        return [$this->toSqlTime($parts[0]), $this->toSqlTime($parts[1])];
    }

    private function isSqlTime(string $time): bool
    {
        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time);
    }

    private function toSqlTime(string $time): string
    {
        $timestamp = strtotime('1970-01-01 ' . trim($time));
        if ($timestamp === false) {
            throw new RuntimeException('One of the selected booking times is invalid.');
        }

        return date('H:i:s', $timestamp);
    }
}
