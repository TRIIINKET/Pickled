<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';

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
            'SELECT ci.id AS cart_item_id, ci.quantity, ci.unit_price, s.id AS session_id, s.session_date, s.session_time,
                    v.slug AS variant_id, v.name, v.category, v.duration_label, v.image, v.price AS base_price, c.name AS court
             FROM cart_items ci
             JOIN sessions s ON s.id = ci.session_id
             JOIN booking_variants v ON v.id = s.variant_id
             JOIN courts c ON c.id = v.court_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.created_at ASC'
        );
        $stmt->execute(['cart_id' => $cartId]);
        return $stmt->fetchAll();
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
            'started_at' => $startedAt ? date('Y-m-d H:i:s', $startedAt) : null,
            'expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : null,
        ]);
        return (int) ($this->findForUser($userId)['id'] ?? 0);
    }

    public function addItem(int $cartId, int $sessionId, int $quantity, float $unitPrice): void
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
    }

    public function removeItem(int $cartItemId): void
    {
        Database::connection()->prepare('DELETE FROM cart_items WHERE id = :id')->execute(['id' => $cartItemId]);
    }

    public function clearForUser(int $userId): void
    {
        $cart = $this->findForUser($userId);
        if ($cart) {
            Database::connection()->prepare('DELETE FROM carts WHERE id = :id')->execute(['id' => $cart['id']]);
        }
    }
}
