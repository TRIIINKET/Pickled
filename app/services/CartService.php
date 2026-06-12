<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CartRepository.php';
require_once __DIR__ . '/../repositories/CatalogRepository.php';

final class CartService
{
    public function __construct(
        private readonly CartRepository $carts = new CartRepository(),
        private readonly CatalogRepository $catalog = new CatalogRepository()
    ) {}

    public function restoreForUser(int $userId): array
    {
        if ($userId <= 0) {
            return ['items' => [], 'started_at' => null, 'expires_at' => null];
        }

        $this->carts->deleteExpired();
        $cart = $this->carts->findForUser($userId);
        if (!$cart) {
            return ['items' => [], 'started_at' => null, 'expires_at' => null];
        }

        return [
            'items' => $this->hydrateItems($this->carts->itemsForCart((int) $cart['id'])),
            'started_at' => $this->timestampFromDateTime($cart['started_at'] ?? null),
            'expires_at' => $this->timestampFromDateTime($cart['expires_at'] ?? null),
        ];
    }

    public function addVariantForUser(int $userId, string $variantSlug, int $quantity, string $date, string $time, ?int $startedAt, ?int $expiresAt, ?float $unitPrice = null): array
    {
        if ($userId <= 0) {
            return ['ok' => false, 'code' => 'login'];
        }

        $this->carts->deleteExpired();
        $variant = $this->catalog->findVariantBySlug($variantSlug);
        if (!$variant) {
            return ['ok' => false, 'code' => 'invalid'];
        }

        $quantity = max(1, min($quantity, (int) $variant['participants_limit']));
        $session = $this->catalog->findOrCreateSession((int) $variant['id'], $date, $time, (int) $variant['capacity']);
        $cartId = $this->carts->saveTimerForUser($userId, $startedAt, $expiresAt);
        foreach ($this->carts->itemsForCart($cartId) as $item) {
            if ((int) $item['session_id'] === (int) $session['id']) {
                return ['ok' => false, 'code' => 'duplicate'];
            }
        }
        if (!$this->sessionCanHold($session, $quantity)) {
            return ['ok' => false, 'code' => 'full'];
        }

        try {
            $this->carts->addItem($cartId, (int) $session['id'], $quantity, $unitPrice ?? (float) $variant['price']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'code' => 'duplicate'];
            }
            throw $e;
        }

        return ['ok' => true, 'code' => 'added'];
    }

    public function updateQuantityForUser(int $userId, int $cartItemId, int $quantity): array
    {
        if ($userId <= 0 || $cartItemId <= 0) {
            return ['ok' => false, 'code' => 'invalid'];
        }

        $this->carts->deleteExpired();
        $item = $this->carts->itemForUser($userId, $cartItemId);
        if (!$item) {
            return ['ok' => false, 'code' => 'invalid'];
        }

        $quantity = max(1, min($quantity, (int) $item['participants_limit']));
        if (!$this->sessionCanHold($item, $quantity, $cartItemId)) {
            return ['ok' => false, 'code' => 'full'];
        }

        $this->carts->updateItemQuantity($cartItemId, (int) $item['cart_id'], $quantity);
        return ['ok' => true, 'code' => 'updated'];
    }

    public function removeForUser(int $userId, int $cartItemId): void
    {
        $cart = $this->carts->findForUser($userId);
        if (!$cart) return;
        foreach ($this->carts->itemsForCart((int) $cart['id']) as $item) {
            if ((int) $item['cart_item_id'] === $cartItemId) {
                $this->carts->removeItem($cartItemId, (int) $cart['id']);
                break;
            }
        }
    }

    public function persistTimerForUser(int $userId, ?int $startedAt, ?int $expiresAt): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->carts->saveTimerForUser($userId, $startedAt, $expiresAt);
    }

    public function clearForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->carts->clearForUser($userId);
    }

    private function sessionCanHold(array $session, int $quantity, ?int $excludeCartItemId = null): bool
    {
        if (!in_array((string) ($session['session_status'] ?? $session['status'] ?? 'open'), ['open', 'full'], true)) {
            return false;
        }

        $sessionId = (int) ($session['session_id'] ?? $session['id'] ?? 0);
        if ($sessionId <= 0) {
            return false;
        }

        $held = $this->carts->activeHeldQuantityForSession($sessionId, $excludeCartItemId);
        return (int) ($session['booked_count'] ?? 0) + $held + $quantity <= (int) ($session['capacity'] ?? 0);
    }

    private function hydrateItems(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[(string) $row['cart_item_id']] = [
                'id' => (string) $row['cart_item_id'],
                'session_id' => (int) $row['session_id'],
                'variant_id' => $row['variant_id'],
                'variant_slug' => $row['variant_slug'],
                'name' => $row['name'],
                'court' => $row['court'],
                'category' => $row['category'],
                'price' => (float) $row['unit_price'],
                'base_price' => (float) $row['base_price'],
                'member_discount' => (float) $row['unit_price'] < (float) $row['base_price'],
                'quantity' => (int) $row['quantity'],
                'duration' => $row['duration_label'],
                'booking_date' => $row['session_date_raw'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'date' => $row['session_date'],
                'time' => $row['session_time'],
                'participants' => (int) $row['quantity'],
                'availability' => 'Temporarily reserved',
                'description' => $row['court'] . ' · ' . $row['duration_label'],
                'image' => $row['image'] ?: '../assets/img/Hero.jpg',
                'status' => 'Reserved in cart',
            ];
        }
        return $items;
    }

    private function timestampFromDateTime(?string $value): ?int
    {
        if (!$value) {
            return null;
        }

        $config = require __DIR__ . '/../../includes/config.php';
        $timezone = new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Manila'));
        return (new DateTimeImmutable($value, $timezone))->getTimestamp();
    }
}
