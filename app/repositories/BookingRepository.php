<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/CatalogRepository.php';
require_once __DIR__ . '/CartRepository.php';
require_once __DIR__ . '/SchedulingRepository.php';

final class BookingRepository
{
    private const BOOKING_STATUSES = ['pending', 'confirmed', 'completed', 'cancelled', 'rejected', 'expired', 'refunded', 'approved', 'paid'];

    public function __construct(
        private readonly CatalogRepository $catalog = new CatalogRepository(),
        private readonly CartRepository $carts = new CartRepository(),
        private readonly SchedulingRepository $schedules = new SchedulingRepository()
    ) {
        $this->ensureStandardCourtBookingSchema();
    }

    private function ensureStandardCourtBookingSchema(): void
    {
        $pdo = Database::connection();
        try {
            if (!$this->columnExists('booking_items', 'coach_user_id')) {
                $pdo->exec('ALTER TABLE booking_items ADD COLUMN coach_user_id INT UNSIGNED NULL AFTER session_id');
                $pdo->exec('ALTER TABLE booking_items ADD KEY idx_booking_items_coach_slot (coach_user_id, booking_date, start_time, end_time)');
            }
            $pdo->exec('ALTER TABLE booking_items MODIFY session_id INT UNSIGNED NULL');
        } catch (Throwable $e) {
            error_log('Booking standard court schema check failed: ' . $e->getMessage());
        }
    }

    public function create(int $userId, array $booking): array
    {
        $items = $booking['items'] ?? [];
        if (!$items) {
            throw new RuntimeException('Your cart is empty. Add a booking before checkout.');
        }
        $bookingStatus = $this->normalizeBookingStatus((string) ($booking['status'] ?? 'pending'));
        $paymentMethod = trim((string) ($booking['payment_method'] ?? 'GCash'));
        if (strcasecmp($paymentMethod, 'GCash') !== 0) {
            throw new RuntimeException('GCash is the only accepted payment method.');
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
                'status' => $bookingStatus,
                'subtotal' => (float) ($booking['subtotal'] ?? 0),
                'payment_fee' => (float) ($booking['payment_fee'] ?? 0),
                'total' => (float) ($booking['total'] ?? 0),
                'payment_method' => 'GCash',
                'payment_status' => $this->normalizePaymentStatus((string) ($booking['payment_status'] ?? 'pending')),
                'notes' => trim((string) ($booking['notes'] ?? '')) ?: null,
                'cancellation_label' => (string) ($booking['cancellation_policy']['label'] ?? $booking['cancellation_label'] ?? 'Standard cancellation policy'),
            ]);
            $bookingId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO booking_items
                    (booking_id, session_id, coach_user_id, variant_slug, name, court, category, duration_label, booking_date, start_time, end_time, quantity, unit_price, image)
                 VALUES
                    (:booking_id, :session_id, :coach_user_id, :variant_slug, :name, :court, :category, :duration_label, :booking_date, :start_time, :end_time, :quantity, :unit_price, :image)'
            );

            foreach ($items as $item) {
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $sessionId = (int) ($item['session_id'] ?? 0);
                $variantSlug = (string) ($item['variant_slug'] ?? $item['variant_id'] ?? '');
                $variant = $this->catalog->findVariantBySlug($variantSlug);
                [$startTime, $endTime] = $this->timeSnapshot($item);
                $bookingDate = $this->dateSnapshot($item);
                $coachUserId = empty($item['coach_user_id']) ? null : (int) $item['coach_user_id'];

                if ($variant && $this->usesStandardCourtFlow($variant)) {
                    $this->assertStandardCourtBookingAvailable($userId, $variant, $bookingDate, $startTime, $endTime, $quantity, $coachUserId);
                } else {
                    if ($sessionId <= 0) {
                        throw new RuntimeException('One of the selected sessions is no longer available.');
                    }

                    if ($this->statusConsumesCapacity($bookingStatus) && !$this->catalog->incrementBookedCount($sessionId, $quantity)) {
                        throw new RuntimeException('This service has reached its maximum capacity for the selected schedule.');
                    }
                }

                $itemStmt->execute([
                    'booking_id' => $bookingId,
                    'session_id' => $sessionId > 0 ? $sessionId : null,
                    'coach_user_id' => $coachUserId,
                    'variant_slug' => $variantSlug !== '' ? $variantSlug : 'custom',
                    'name' => (string) ($item['name'] ?? 'Booking'),
                    'court' => (string) ($item['court'] ?? 'Any Court'),
                    'category' => (string) ($item['category'] ?? 'Booking'),
                    'duration_label' => (string) ($item['duration_label'] ?? $item['duration'] ?? 'Scheduled session'),
                    'booking_date' => $bookingDate,
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

    public function findExpiredPendingIds(DateTimeInterface $cutoff, int $limit = 100): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id
             FROM bookings
             WHERE status = 'pending'
               AND LOWER(payment_status) <> 'paid'
               AND created_at <= :cutoff
             ORDER BY created_at ASC, id ASC
             LIMIT :limit_count"
        );
        $stmt->bindValue(':cutoff', $cutoff->format('Y-m-d H:i:s'));
        $stmt->bindValue(':limit_count', max(1, min($limit, 500)), PDO::PARAM_INT);
        $stmt->execute();

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function expirePendingBooking(int $id, DateTimeInterface $cutoff, string $reason = 'Expired pending payment'): bool
    {
        $pdo = Database::connection();
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $stmt = $pdo->prepare('SELECT status, payment_status, created_at FROM bookings WHERE id = :id LIMIT 1 FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $booking = $stmt->fetch();
            if (!$booking || $booking['status'] !== 'pending' || strtolower((string) $booking['payment_status']) === 'paid' || (string) $booking['created_at'] > $cutoff->format('Y-m-d H:i:s')) {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return false;
            }

            if (!$this->updateStatus($id, 'cancelled')) {
                if ($startedTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return false;
            }

            $note = 'Booking expired automatically because payment was not completed before the pending window ended.';
            $update = $pdo->prepare(
                "UPDATE bookings
                 SET payment_status = 'expired',
                     cancellation_label = :reason,
                     notes = CASE
                        WHEN notes IS NULL OR notes = '' THEN :note_first
                        WHEN notes LIKE :note_match THEN notes
                        ELSE CONCAT(notes, CHAR(10), :note_append)
                     END
                 WHERE id = :id"
            );
            $update->execute([
                'id' => $id,
                'reason' => $reason,
                'note_first' => $note,
                'note_match' => '%' . $note . '%',
                'note_append' => $note,
            ]);

            $rejectPendingPayments = $pdo->prepare(
                "UPDATE payments
                 SET status = 'rejected',
                     reviewed_at = COALESCE(reviewed_at, NOW()),
                     remarks = CASE
                        WHEN remarks IS NULL OR remarks = '' THEN :remarks_first
                        WHEN remarks LIKE :remarks_match THEN remarks
                        ELSE CONCAT(remarks, CHAR(10), :remarks_append)
                     END
                 WHERE booking_id = :booking_id
                   AND status = 'pending'"
            );
            $rejectPendingPayments->execute([
                'booking_id' => $id,
                'remarks_first' => $reason,
                'remarks_match' => '%' . $reason . '%',
                'remarks_append' => $reason,
            ]);

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

    public function updateStatus(int $id, string $status): bool
    {
        $newStatus = $this->normalizeBookingStatus($status);
        $pdo = Database::connection();
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $stmt = $pdo->prepare('SELECT status FROM bookings WHERE id = :id LIMIT 1 FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetchColumn();
            if ($current === false) {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return false;
            }

            $currentStatus = $this->normalizeBookingStatus((string) $current);
            if ($currentStatus !== $newStatus) {
                $currentConsumesCapacity = $this->statusConsumesCapacity($currentStatus);
                $newConsumesCapacity = $this->statusConsumesCapacity($newStatus);

                if ($currentConsumesCapacity && !$newConsumesCapacity) {
                    $this->releaseBookingCapacity($id);
                } elseif (!$currentConsumesCapacity && $newConsumesCapacity && !$this->reserveBookingCapacity($id)) {
                    if ($startedTransaction && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    return false;
                }
            }

            $update = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id');
            $ok = $update->execute(['id' => $id, 'status' => $newStatus]);

            if ($startedTransaction) {
                $pdo->commit();
            }
            return $ok;
        } catch (Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
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
                       bi.coach_user_id,
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
                JOIN bookings b ON b.id = bi.booking_id
                LEFT JOIN users u ON u.id = b.user_id
                WHERE bi.coach_user_id = :coach_user_id
                  AND b.status NOT IN ('cancelled', 'rejected', 'expired', 'refunded')";
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

    public function bookedCountMismatches(): array
    {
        $stmt = Database::connection()->query(
            "SELECT s.id AS session_id,
                    s.capacity,
                    s.booked_count,
                    COALESCE(SUM(CASE WHEN b.status <> 'cancelled' THEN bi.quantity ELSE 0 END), 0) AS expected_booked_count
             FROM sessions s
             LEFT JOIN booking_items bi ON bi.session_id = s.id
             LEFT JOIN bookings b ON b.id = bi.booking_id
             GROUP BY s.id, s.capacity, s.booked_count
             HAVING s.booked_count <> expected_booked_count
                OR s.booked_count < 0
                OR s.booked_count > s.capacity"
        );
        return $stmt->fetchAll() ?: [];
    }

    private function itemSelect(string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        return "SELECT {$prefix}id,
                       {$prefix}booking_id,
                       {$prefix}session_id,
                       {$prefix}coach_user_id,
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
        if (str_contains($status, 'reject')) {
            return 'rejected';
        }
        if (str_contains($status, 'expire')) {
            return 'expired';
        }
        if (str_contains($status, 'refund')) {
            return 'refunded';
        }
        if (str_contains($status, 'complete')) {
            return 'completed';
        }
        if (str_contains($status, 'approve')) {
            return 'approved';
        }
        if (str_contains($status, 'confirm') || str_contains($status, 'paid')) {
            return 'confirmed';
        }
        return in_array($status, self::BOOKING_STATUSES, true) ? $status : 'pending';
    }

    private function statusConsumesCapacity(string $status): bool
    {
        return in_array($this->normalizeBookingStatus($status), ['pending', 'approved', 'confirmed', 'paid', 'completed'], true);
    }

    private function assertStandardCourtBookingAvailable(int $userId, array $variant, string $bookingDate, string $startTime, string $endTime, int $quantity, ?int $coachUserId): void
    {
        if ($this->courtBookingConflict((int) $variant['court_id'], $bookingDate, $startTime, $endTime)) {
            throw new RuntimeException('That court is already booked for the selected date and time. Please choose another schedule.');
        }

        $bookedQuantity = $this->bookedQuantityForStandardSlot((string) $variant['slug'], $bookingDate, $startTime, $endTime);
        $heldQuantity = $this->carts->activeHeldQuantityForStandardSlot((int) $variant['id'], $bookingDate, $startTime, $endTime, null, $userId);
        if ($bookedQuantity + $heldQuantity + $quantity > (int) $variant['capacity']) {
            throw new RuntimeException('This service has reached its maximum capacity for the selected schedule.');
        }

        if ($this->requiresCoach($variant)) {
            if (!$coachUserId || !$this->coachCanTakeBooking($coachUserId, $bookingDate, $startTime, $endTime, $userId)) {
                throw new RuntimeException('No coach is available for the selected date and time.');
            }
        }
    }

    private function courtBookingConflict(int $courtId, string $bookingDate, string $startTime, string $endTime): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1
             FROM booking_items bi
             JOIN bookings b ON b.id = bi.booking_id
             JOIN booking_variants v ON v.slug = bi.variant_slug
             WHERE v.court_id = :court_id
               AND bi.booking_date = :booking_date
               AND (b.status IN ('pending', 'approved', 'confirmed', 'paid', 'completed')
                    OR b.payment_status IN ('pending', 'approved', 'paid'))
               AND b.status NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
               AND b.payment_status NOT IN ('expired', 'refunded', 'rejected')
               AND :start_time < bi.end_time
               AND :end_time > bi.start_time
             LIMIT 1"
        );
        $stmt->execute([
            'court_id' => $courtId,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private function bookedQuantityForStandardSlot(string $variantSlug, string $bookingDate, string $startTime, string $endTime): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(bi.quantity), 0)
             FROM booking_items bi
             JOIN bookings b ON b.id = bi.booking_id
             WHERE bi.variant_slug = :variant_slug
               AND bi.booking_date = :booking_date
               AND (b.status IN ('pending', 'approved', 'confirmed', 'paid', 'completed')
                    OR b.payment_status IN ('pending', 'approved', 'paid'))
               AND b.status NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
               AND b.payment_status NOT IN ('expired', 'refunded', 'rejected')
               AND :start_time < bi.end_time
               AND :end_time > bi.start_time"
        );
        $stmt->execute([
            'variant_slug' => $variantSlug,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        return (int) $stmt->fetchColumn();
    }

    private function coachCanTakeBooking(int $coachUserId, string $bookingDate, string $startTime, string $endTime, int $userId): bool
    {
        $coach = $this->schedules->coachById($coachUserId);
        if (!$coach || (($coach['status'] ?? 'active') !== 'active')) {
            return false;
        }

        $dayOfWeek = (int) (new DateTimeImmutable($bookingDate))->format('w');
        if (!$this->schedules->coachAvailableForDatedSlot($coachUserId, $dayOfWeek, $startTime, $endTime, $bookingDate)) {
            return false;
        }
        if ($this->schedules->coachSessionOverlap($coachUserId, $bookingDate, $startTime, $endTime)) {
            return false;
        }
        if ($this->coachBookingOverlap($coachUserId, $bookingDate, $startTime, $endTime)) {
            return false;
        }
        return !$this->carts->coachHasOverlap($coachUserId, $bookingDate, $startTime, $endTime, $userId);
    }

    private function coachBookingOverlap(int $coachUserId, string $bookingDate, string $startTime, string $endTime): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1
             FROM booking_items bi
             JOIN bookings b ON b.id = bi.booking_id
             WHERE bi.coach_user_id = :coach_user_id
               AND bi.booking_date = :booking_date
               AND (b.status IN ('pending', 'approved', 'confirmed', 'paid', 'completed')
                    OR b.payment_status IN ('pending', 'approved', 'paid'))
               AND b.status NOT IN ('cancelled', 'rejected', 'expired', 'refunded')
               AND b.payment_status NOT IN ('expired', 'refunded', 'rejected')
               AND :start_time < bi.end_time
               AND :end_time > bi.start_time
             LIMIT 1"
        );
        $stmt->execute([
            'coach_user_id' => $coachUserId,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private function usesStandardCourtFlow(array $variant): bool
    {
        $courtSlug = strtolower((string) ($variant['court_slug'] ?? ''));
        $label = strtolower((string) ($variant['category'] ?? '') . ' ' . (string) ($variant['name'] ?? ''));
        return in_array($courtSlug, ['green', 'pink'], true)
            && !str_contains($label, 'social play')
            && !str_contains($label, 'tournament')
            && !str_contains($label, 'match-play');
    }

    private function requiresCoach(array $variant): bool
    {
        $label = strtolower((string) ($variant['category'] ?? '') . ' ' . (string) ($variant['name'] ?? ''));
        foreach (['lesson', 'coaching', 'training', 'class', 'kids', 'youth', 'parent'] as $keyword) {
            if (str_contains($label, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function bookingSessionQuantities(int $bookingId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT session_id, COALESCE(SUM(quantity), 0) AS quantity
             FROM booking_items
             WHERE booking_id = :booking_id
               AND session_id IS NOT NULL
             GROUP BY session_id'
        );
        $stmt->execute(['booking_id' => $bookingId]);
        return $stmt->fetchAll() ?: [];
    }

    private function releaseBookingCapacity(int $bookingId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE sessions
             SET booked_count = GREATEST(booked_count - :quantity_count, 0),
                 status = CASE
                    WHEN status = 'full' AND GREATEST(booked_count - :quantity_status, 0) < capacity THEN 'open'
                    ELSE status
                 END
             WHERE id = :session_id"
        );

        foreach ($this->bookingSessionQuantities($bookingId) as $item) {
            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            if ($quantity <= 0) {
                continue;
            }
            $stmt->execute([
                'session_id' => (int) $item['session_id'],
                'quantity_count' => $quantity,
                'quantity_status' => $quantity,
            ]);
        }
    }

    private function reserveBookingCapacity(int $bookingId): bool
    {
        foreach ($this->bookingSessionQuantities($bookingId) as $item) {
            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            if ($quantity <= 0) {
                continue;
            }
            if (!$this->catalog->incrementBookedCount((int) $item['session_id'], $quantity)) {
                return false;
            }
        }
        return true;
    }

    private function normalizePaymentStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if (str_contains($status, 'reject')) {
            return 'rejected';
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

    private function columnExists(string $table, string $column): bool
    {
        $stmt = Database::connection()->prepare('
            SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
            LIMIT 1
        ');
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        return (bool) $stmt->fetchColumn();
    }
}
