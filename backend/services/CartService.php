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
        $cart = $this->carts->findForUser($userId);
        if (!$cart) {
            return ['items' => [], 'started_at' => null, 'expires_at' => null];
        }

        return [
            'items' => $this->hydrateItems($this->carts->itemsForCart((int) $cart['id'])),
            'started_at' => $cart['started_at'] ? strtotime($cart['started_at']) : null,
            'expires_at' => $cart['expires_at'] ? strtotime($cart['expires_at']) : null,
        ];
    }

    public function addVariantForUser(int $userId, string $variantSlug, int $quantity, string $date, string $time, ?int $startedAt, ?int $expiresAt, ?float $unitPrice = null): array
    {
        $variant = $this->catalog->findVariantBySlug($variantSlug);
        if (!$variant) {
            return ['ok' => false, 'code' => 'invalid'];
        }

        $quantity = max(1, min($quantity, (int) $variant['participants_limit']));
        $session = $this->catalog->findOrCreateSession((int) $variant['id'], $date, $time, (int) $variant['capacity']);
        if ((int) $session['booked_count'] + $quantity > (int) $session['capacity']) {
            return ['ok' => false, 'code' => 'full'];
        }
        $cartId = $this->carts->saveTimerForUser($userId, $startedAt, $expiresAt);
        foreach ($this->carts->itemsForCart($cartId) as $item) {
            if ((int) $item['session_id'] === (int) $session['id']) {
                return ['ok' => false, 'code' => 'duplicate'];
            }
        }
        $this->carts->addItem($cartId, (int) $session['id'], $quantity, $unitPrice ?? (float) $variant['price']);
        return ['ok' => true, 'code' => 'added'];
    }

    public function removeForUser(int $userId, int $cartItemId): void
    {
        $cart = $this->carts->findForUser($userId);
        if (!$cart) return;
        foreach ($this->carts->itemsForCart((int) $cart['id']) as $item) {
            if ((int) $item['cart_item_id'] === $cartItemId) {
                $this->carts->removeItem($cartItemId);
                break;
            }
        }
    }

    public function persistTimerForUser(int $userId, ?int $startedAt, ?int $expiresAt): void
    {
        $this->carts->saveTimerForUser($userId, $startedAt, $expiresAt);
    }

    public function clearForUser(int $userId): void
    {
        $this->carts->clearForUser($userId);
    }

    private function hydrateItems(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[(string) $row['cart_item_id']] = [
                'id' => (string) $row['cart_item_id'],
                'session_id' => (int) $row['session_id'],
                'variant_id' => $row['variant_id'],
                'name' => $row['name'],
                'court' => $row['court'],
                'category' => $row['category'],
                'price' => (float) $row['unit_price'],
                'base_price' => (float) $row['base_price'],
                'member_discount' => (float) $row['unit_price'] < (float) $row['base_price'],
                'quantity' => (int) $row['quantity'],
                'duration' => $row['duration_label'],
                'date' => $row['session_date'],
                'time' => $row['session_time'],
                'participants' => (int) $row['quantity'],
                'availability' => 'Temporarily reserved',
                'description' => $row['court'] . ' · ' . $row['duration_label'],
                'image' => $row['image'] ?: '../assets/Images/Hero.jpg',
                'status' => 'Reserved in cart',
            ];
        }
        return $items;
    }
}
