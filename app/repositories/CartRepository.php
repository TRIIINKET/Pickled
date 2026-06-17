<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class CartRepository
{
    public function __construct()
    {
        $this->ensureStandardCourtCartSchema();
    }

    private function ensureStandardCourtCartSchema(): void
    {
        $pdo = Database::connection();
        try {
            if (!$this->columnExists('cart_items', 'variant_id')) {
                $pdo->exec('ALTER TABLE cart_items ADD COLUMN variant_id INT UNSIGNED NULL AFTER session_id');
            }
            if (!$this->columnExists('cart_items', 'booking_date')) {
                $pdo->exec('ALTER TABLE cart_items ADD COLUMN booking_date DATE NULL AFTER variant_id');
            }
            if (!$this->columnExists('cart_items', 'start_time')) {
                $pdo->exec('ALTER TABLE cart_items ADD COLUMN start_time TIME NULL AFTER booking_date');
            }
            if (!$this->columnExists('cart_items', 'end_time')) {
                $pdo->exec('ALTER TABLE cart_items ADD COLUMN end_time TIME NULL AFTER start_time');
            }
            if (!$this->columnExists('cart_items', 'coach_user_id')) {
                $pdo->exec('ALTER TABLE cart_items ADD COLUMN coach_user_id INT UNSIGNED NULL AFTER end_time');
            }
            if (!$this->indexExists('cart_items', 'idx_cart_items_variant_slot')) {
                $pdo->exec('ALTER TABLE cart_items ADD KEY idx_cart_items_variant_slot (variant_id, booking_date, start_time, end_time)');
            }
            if (!$this->indexExists('cart_items', 'idx_cart_items_coach_slot')) {
                $pdo->exec('ALTER TABLE cart_items ADD KEY idx_cart_items_coach_slot (coach_user_id, booking_date, start_time, end_time)');
            }
            $pdo->exec('ALTER TABLE cart_items MODIFY session_id INT UNSIGNED NULL');
        } catch (Throwable $e) {
            error_log('Cart standard court schema check failed: ' . $e->getMessage());
        }
    }

    public function findForUser(int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM carts WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $cart = $stmt->fetch();
        return $cart ?: null;
    }

    public function itemsForCart(int $cartId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ci.id AS cart_item_id, ci.quantity, ci.unit_price,
                    s.id AS session_id,
                    COALESCE(s.session_date, ci.booking_date) AS session_date_raw,
                    DATE_FORMAT(COALESCE(s.session_date, ci.booking_date), '%W, %M %e, %Y') AS session_date,
                    COALESCE(s.start_time, ci.start_time) AS start_time,
                    COALESCE(s.end_time, ci.end_time) AS end_time,
                    CONCAT(TIME_FORMAT(COALESCE(s.start_time, ci.start_time), '%h:%i %p'), ' - ', TIME_FORMAT(COALESCE(s.end_time, ci.end_time), '%h:%i %p')) AS session_time,
                    COALESCE(s.capacity, v.capacity) AS capacity,
                    COALESCE(s.booked_count, 0) AS booked_count,
                    COALESCE(s.status, 'open') AS session_status,
                    ci.coach_user_id,
                    v.slug AS variant_id, v.slug AS variant_slug, v.name, v.category, v.duration_label, v.image, v.price AS base_price, c.name AS court
             FROM cart_items ci
             LEFT JOIN sessions s ON s.id = ci.session_id
             JOIN booking_variants v ON v.id = COALESCE(s.variant_id, ci.variant_id)
             JOIN courts c ON c.id = v.court_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.created_at ASC"
        );
        $stmt->execute(['cart_id' => $cartId]);
        return $stmt->fetchAll();
    }

    public function itemForUser(int $userId, int $cartItemId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ci.id AS cart_item_id, ci.cart_id, ci.session_id, ci.quantity, ci.unit_price,
                    ci.variant_id,
                    ci.booking_date,
                    ci.start_time,
                    ci.end_time,
                    ci.coach_user_id,
                    COALESCE(s.capacity, v.capacity) AS capacity,
                    COALESCE(s.booked_count, 0) AS booked_count,
                    COALESCE(s.status, 'open') AS session_status,
                    v.participants_limit,
                    v.slug AS variant_slug,
                    v.court_id
             FROM cart_items ci
             JOIN carts ca ON ca.id = ci.cart_id
             LEFT JOIN sessions s ON s.id = ci.session_id
             JOIN booking_variants v ON v.id = COALESCE(s.variant_id, ci.variant_id)
             WHERE ca.user_id = :user_id
               AND ci.id = :cart_item_id
             LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId, 'cart_item_id' => $cartItemId]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function saveTimerForUser(int $userId, ?int $startedAt, ?int $expiresAt): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO carts (user_id, started_at, expires_at)
             VALUES (:user_id, :started_at, :expires_at)
             ON DUPLICATE KEY UPDATE started_at = VALUES(started_at), expires_at = VALUES(expires_at), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'user_id' => $userId,
            'started_at' => $this->dateTimeFromTimestamp($startedAt),
            'expires_at' => $this->dateTimeFromTimestamp($expiresAt),
        ]);
        return (int) ($this->findForUser($userId)['id'] ?? 0);
    }

    public function addItem(int $cartId, ?int $sessionId, int $quantity, float $unitPrice): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO cart_items (cart_id, session_id, quantity, unit_price)
             VALUES (:cart_id, :session_id, :quantity, :unit_price)'
        );
        $stmt->execute([
            'cart_id' => $cartId,
            'session_id' => $sessionId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function addStandardItem(int $cartId, int $variantId, ?int $coachUserId, string $bookingDate, string $startTime, string $endTime, int $quantity, float $unitPrice): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO cart_items (cart_id, session_id, variant_id, booking_date, start_time, end_time, coach_user_id, quantity, unit_price)
             VALUES (:cart_id, NULL, :variant_id, :booking_date, :start_time, :end_time, :coach_user_id, :quantity, :unit_price)'
        );
        $stmt->execute([
            'cart_id' => $cartId,
            'variant_id' => $variantId,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'coach_user_id' => $coachUserId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function updateItemQuantity(int $cartItemId, int $cartId, int $quantity): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE cart_items
             SET quantity = :quantity
             WHERE id = :id
               AND cart_id = :cart_id'
        );
        $stmt->execute([
            'id' => $cartItemId,
            'cart_id' => $cartId,
            'quantity' => $quantity,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function removeItem(int $cartItemId, int $cartId): void
    {
        Database::connection()
            ->prepare('DELETE FROM cart_items WHERE id = :id AND cart_id = :cart_id')
            ->execute(['id' => $cartItemId, 'cart_id' => $cartId]);
    }

    public function clearForUser(int $userId): void
    {
        $cart = $this->findForUser($userId);
        if ($cart) {
            Database::connection()->prepare('DELETE FROM carts WHERE id = :id')->execute(['id' => $cart['id']]);
        }
    }

    public function activeHeldQuantityForSession(int $sessionId, ?int $excludeCartItemId = null): int
    {
        $sql = "SELECT COALESCE(SUM(ci.quantity), 0)
                FROM cart_items ci
                JOIN carts ca ON ca.id = ci.cart_id
                WHERE ci.session_id = :session_id
                  AND (ca.expires_at IS NULL OR ca.expires_at > NOW())";
        $params = ['session_id' => $sessionId];
        if ($excludeCartItemId !== null) {
            $sql .= ' AND ci.id <> :exclude_cart_item_id';
            $params['exclude_cart_item_id'] = $excludeCartItemId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function activeHeldQuantityForStandardSlot(int $variantId, string $bookingDate, string $startTime, string $endTime, ?int $excludeCartItemId = null, ?int $excludeUserId = null): int
    {
        $sql = "SELECT COALESCE(SUM(ci.quantity), 0)
                FROM cart_items ci
                JOIN carts ca ON ca.id = ci.cart_id
                WHERE ci.variant_id = :variant_id
                  AND ci.session_id IS NULL
                  AND ci.booking_date = :booking_date
                  AND (ca.expires_at IS NULL OR ca.expires_at > NOW())
                  AND :start_time < ci.end_time
                  AND :end_time > ci.start_time";
        $params = [
            'variant_id' => $variantId,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        if ($excludeCartItemId !== null) {
            $sql .= ' AND ci.id <> :exclude_cart_item_id';
            $params['exclude_cart_item_id'] = $excludeCartItemId;
        }
        if ($excludeUserId !== null) {
            $sql .= ' AND ca.user_id <> :exclude_user_id';
            $params['exclude_user_id'] = $excludeUserId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function bookedQuantityForStandardSlot(string $variantSlug, string $bookingDate, string $startTime, string $endTime): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(bi.quantity), 0)
             FROM booking_items bi
             JOIN bookings b ON b.id = bi.booking_id
             WHERE bi.variant_slug = :variant_slug
               AND bi.booking_date = :booking_date
               AND (b.status IN ('pending', 'confirmed', 'completed')
                    OR b.payment_status IN ('pending', 'approved', 'paid'))
	               AND b.status <> 'cancelled'
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

    public function duplicateStandardItemInCart(int $cartId, int $variantId, string $bookingDate, string $startTime, string $endTime): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM cart_items
             WHERE cart_id = :cart_id
               AND session_id IS NULL
               AND variant_id = :variant_id
               AND booking_date = :booking_date
               AND start_time = :start_time
               AND end_time = :end_time
             LIMIT 1'
        );
        $stmt->execute([
            'cart_id' => $cartId,
            'variant_id' => $variantId,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function courtHasOverlap(int $courtId, string $sessionDate, string $startTime, string $endTime, ?string $sameVariantSlug = null, ?int $excludeUserId = null): bool
    {
        $bookingSql = "SELECT 1
                       FROM booking_items bi
                       JOIN bookings b ON b.id = bi.booking_id
                       JOIN booking_variants v ON v.slug = bi.variant_slug
                       WHERE v.court_id = :court_id
                         AND bi.booking_date = :session_date
                         AND (b.status IN ('pending', 'confirmed', 'completed')
                              OR b.payment_status IN ('pending', 'approved', 'paid'))
	                         AND b.status <> 'cancelled'
                         AND b.payment_status NOT IN ('expired', 'refunded', 'rejected')
                         AND :start_time < bi.end_time
                         AND :end_time > bi.start_time";
        $bookingSql .= "
                       LIMIT 1";
        $params = [
            'court_id' => $courtId,
            'session_date' => $sessionDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        $stmt = Database::connection()->prepare($bookingSql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $cartSql = "SELECT 1
                    FROM cart_items ci
                    JOIN carts ca ON ca.id = ci.cart_id
                    JOIN booking_variants v ON v.id = ci.variant_id
                    WHERE v.court_id = :court_id
                      AND ci.session_id IS NULL
                      AND ci.booking_date = :session_date
                      AND (ca.expires_at IS NULL OR ca.expires_at > NOW())
                      AND :start_time < ci.end_time
                      AND :end_time > ci.start_time";
        if ($excludeUserId !== null) {
            $cartSql .= ' AND ca.user_id <> :exclude_user_id';
            $params['exclude_user_id'] = $excludeUserId;
        }
        $cartSql .= ' LIMIT 1';
        $stmt = Database::connection()->prepare($cartSql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function coachHasOverlap(int $coachUserId, string $bookingDate, string $startTime, string $endTime, ?int $excludeUserId = null): bool
    {
        $bookingSql = "SELECT 1
                       FROM booking_items bi
                       JOIN bookings b ON b.id = bi.booking_id
                       WHERE bi.coach_user_id = :coach_user_id
                         AND bi.booking_date = :booking_date
                         AND (b.status IN ('pending', 'confirmed', 'completed')
                              OR b.payment_status IN ('pending', 'approved', 'paid'))
	                         AND b.status <> 'cancelled'
                         AND b.payment_status NOT IN ('expired', 'refunded', 'rejected')
                         AND :start_time < bi.end_time
                         AND :end_time > bi.start_time
                       LIMIT 1";
        $params = [
            'coach_user_id' => $coachUserId,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        $stmt = Database::connection()->prepare($bookingSql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $cartSql = "SELECT 1
                    FROM cart_items ci
                    JOIN carts ca ON ca.id = ci.cart_id
                    WHERE ci.coach_user_id = :coach_user_id
                      AND ci.booking_date = :booking_date
                      AND (ca.expires_at IS NULL OR ca.expires_at > NOW())
                      AND :start_time < ci.end_time
                      AND :end_time > ci.start_time";
        if ($excludeUserId !== null) {
            $cartSql .= ' AND ca.user_id <> :exclude_user_id';
            $params['exclude_user_id'] = $excludeUserId;
        }
        $cartSql .= ' LIMIT 1';
        $stmt = Database::connection()->prepare($cartSql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function deleteExpired(): void
    {
        Database::connection()
            ->prepare('DELETE FROM carts WHERE expires_at IS NOT NULL AND expires_at <= NOW()')
            ->execute();
    }

    private function dateTimeFromTimestamp(?int $timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }

        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->format('Y-m-d H:i:s');
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

    private function indexExists(string $table, string $index): bool
    {
        $stmt = Database::connection()->prepare('
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND INDEX_NAME = :index_name
            LIMIT 1
        ');
        $stmt->execute(['table_name' => $table, 'index_name' => $index]);
        return (bool) $stmt->fetchColumn();
    }
}
