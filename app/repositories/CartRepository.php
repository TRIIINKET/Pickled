<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class CartRepository
{
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
                    s.session_date AS session_date_raw,
                    DATE_FORMAT(s.session_date, '%W, %M %e, %Y') AS session_date,
                    s.start_time,
                    s.end_time,
                    CONCAT(TIME_FORMAT(s.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(s.end_time, '%h:%i %p')) AS session_time,
                    s.capacity, s.booked_count, s.status AS session_status,
                    v.slug AS variant_id, v.slug AS variant_slug, v.name, v.category, v.duration_label, v.image, v.price AS base_price, c.name AS court
             FROM cart_items ci
             JOIN sessions s ON s.id = ci.session_id
             JOIN booking_variants v ON v.id = s.variant_id
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
                    s.capacity, s.booked_count, s.status AS session_status,
                    v.participants_limit
             FROM cart_items ci
             JOIN carts ca ON ca.id = ci.cart_id
             JOIN sessions s ON s.id = ci.session_id
             JOIN booking_variants v ON v.id = s.variant_id
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

    public function addItem(int $cartId, int $sessionId, int $quantity, float $unitPrice): bool
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
}
