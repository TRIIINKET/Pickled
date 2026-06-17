<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CartRepository.php';
require_once __DIR__ . '/../repositories/CatalogRepository.php';
require_once __DIR__ . '/SchedulingService.php';
require_once __DIR__ . '/../../includes/schedule-time.php';
require_once __DIR__ . '/../../includes/validation.php';

final class CartService
{
    public function __construct(
        private readonly CartRepository $carts = new CartRepository(),
        private readonly CatalogRepository $catalog = new CatalogRepository()
    ) {}

    public function restoreForUser(int $userId): array
    {
        if ($userId <= 0) {
            return ['items' => [], 'started_at' => null, 'expires_at' => null, 'removed_expired' => 0];
        }

        $this->carts->deleteExpired();
        $cart = $this->carts->findForUser($userId);
        if (!$cart) {
            return ['items' => [], 'started_at' => null, 'expires_at' => null, 'removed_expired' => 0];
        }

        $rows = $this->carts->itemsForCart((int) $cart['id']);
        $removedCount = $this->removeExpiredRows((int) $cart['id'], $rows);
        if ($removedCount > 0) {
            $rows = $this->carts->itemsForCart((int) $cart['id']);
        }

        return [
            'items' => $this->hydrateItems($rows),
            'started_at' => $this->timestampFromDateTime($cart['started_at'] ?? null),
            'expires_at' => $this->timestampFromDateTime($cart['expires_at'] ?? null),
            'removed_expired' => $removedCount,
        ];
    }

    public function addVariantForUser(int $userId, string $variantSlug, int $quantity, string $date, string $time, ?int $startedAt, ?int $expiresAt, ?float $unitPrice = null, ?int $requestedCoachUserId = null, ?int $sessionId = null): array
    {
        error_log('Cart add requested. user_id=' . $userId . '; variant_id=' . $variantSlug . '; session_id=' . (string) ($sessionId ?? '') . '; booking_date=' . $date . '; time=' . $time . '; quantity=' . $quantity . '; coach_user_id=' . (string) ($requestedCoachUserId ?? ''));
        if ($userId <= 0) {
            return ['ok' => false, 'code' => 'login'];
        }

        $this->carts->deleteExpired();
        $variant = $this->catalog->findVariantBySlug($variantSlug);
        if (!$variant) {
            return ['ok' => false, 'code' => 'invalid'];
        }

        $quantity = max(1, min($quantity, (int) $variant['participants_limit']));
        try {
            [$sessionDate, $startTime, $endTime, $slotCount] = $this->normalizeSlot($date, $time);
        } catch (RuntimeException $e) {
            error_log('Cart add rejected during schedule normalization. reason=' . $e->getMessage() . '; variant_id=' . $variantSlug . '; date=' . $date . '; time=' . $time);
            return ['ok' => false, 'code' => 'invalid'];
        }
        if (!pickled_schedule_starts_in_future($sessionDate, $startTime)) {
            error_log('Cart add rejected because schedule is expired. variant_id=' . $variantSlug . '; booking_date=' . $sessionDate . '; start_time=' . $startTime);
            return ['ok' => false, 'code' => 'expired_schedule'];
        }

        $cartId = $this->carts->saveTimerForUser($userId, $startedAt, $expiresAt);
        error_log('Cart add using cart_id=' . $cartId . '; user_id=' . $userId);

        if ($this->usesStandardCourtFlow($variant)) {
            if ($this->carts->duplicateStandardItemInCart($cartId, (int) $variant['id'], $sessionDate, $startTime, $endTime)) {
                return ['ok' => false, 'code' => 'duplicate'];
            }
            if ($this->carts->courtHasOverlap((int) $variant['court_id'], $sessionDate, $startTime, $endTime, (string) $variant['slug'], $userId)) {
                return ['ok' => false, 'code' => 'conflict'];
            }

            $booked = $this->carts->bookedQuantityForStandardSlot((string) $variant['slug'], $sessionDate, $startTime, $endTime);
            $held = $this->carts->activeHeldQuantityForStandardSlot((int) $variant['id'], $sessionDate, $startTime, $endTime, null, $userId);
            if ($booked + $held + $quantity > (int) $variant['capacity']) {
                return ['ok' => false, 'code' => 'capacity'];
            }

            $coachUserId = null;
            if ($this->requiresCoach($variant)) {
                $coachUserId = $this->availableCoachId($sessionDate, $time, $userId, $requestedCoachUserId);
                if ($coachUserId === null) {
                    return ['ok' => false, 'code' => 'coach_unavailable'];
                }
            }

            $storedUnitPrice = ($unitPrice ?? (float) $variant['price']) * max(1, $slotCount);
            $this->carts->addStandardItem($cartId, (int) $variant['id'], $coachUserId, $sessionDate, $startTime, $endTime, $quantity, $storedUnitPrice);
            error_log('Cart standard booking added. cart_id=' . $cartId . '; variant_id=' . (int) $variant['id'] . '; booking_date=' . $sessionDate . '; start_time=' . $startTime . '; end_time=' . $endTime . '; unit_price=' . $storedUnitPrice);
            return ['ok' => true, 'code' => 'added'];
        }

        if ($sessionId === null || $sessionId <= 0) {
            error_log('Cart social/session booking rejected because session_id is missing. variant_id=' . $variantSlug . '; date=' . $date . '; time=' . $time);
            return ['ok' => false, 'code' => 'invalid'];
        }
        $session = $this->catalog->sessionById($sessionId);
        if (!$session || (int) ($session['variant_id'] ?? 0) !== (int) $variant['id']) {
            error_log('Cart social/session booking rejected because session_id is invalid. variant_id=' . $variantSlug . '; session_id=' . $sessionId);
            return ['ok' => false, 'code' => 'invalid'];
        }
        if (!pickled_schedule_starts_in_future((string) $session['session_date'], (string) $session['start_time'])) {
            error_log('Cart social/session booking rejected because session is expired. variant_id=' . $variantSlug . '; session_id=' . $sessionId);
            return ['ok' => false, 'code' => 'expired_schedule'];
        }
        foreach ($this->carts->itemsForCart($cartId) as $item) {
            if ((int) ($item['session_id'] ?? 0) === (int) $session['id']) {
                return ['ok' => false, 'code' => 'duplicate'];
            }
        }
        if (!$this->sessionCanHold($session, $quantity)) {
            return ['ok' => false, 'code' => 'capacity'];
        }

        $this->carts->addItem($cartId, (int) $session['id'], $quantity, $unitPrice ?? (float) $variant['price']);
        error_log('Cart session booking added. cart_id=' . $cartId . '; session_id=' . (int) $session['id'] . '; variant_id=' . (int) $variant['id']);

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
        if (empty($item['session_id'])) {
            $held = $this->carts->activeHeldQuantityForStandardSlot(
                (int) $item['variant_id'],
                (string) $item['booking_date'],
                (string) $item['start_time'],
                (string) $item['end_time'],
                $cartItemId
            );
            if ($held + $quantity > (int) $item['capacity']) {
                return ['ok' => false, 'code' => 'capacity'];
            }
        } elseif (!$this->sessionCanHold($item, $quantity, $cartItemId)) {
            return ['ok' => false, 'code' => 'capacity'];
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

    private function usesStandardCourtFlow(array $variant): bool
    {
        $courtSlug = strtolower((string) ($variant['court_slug'] ?? ''));
        $category = strtolower((string) ($variant['category'] ?? ''));
        $name = strtolower((string) ($variant['name'] ?? ''));

        if (!in_array($courtSlug, ['green', 'pink'], true)) {
            return false;
        }

        return !str_contains($category . ' ' . $name, 'social play')
            && !str_contains($name, 'tournament')
            && !str_contains($name, 'match-play');
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

    private function availableCoachId(string $date, string $time, int $userId, ?int $preferredCoachUserId = null): ?int
    {
        [$sessionDate, $startTime, $endTime] = $this->normalizeSlot($date, $time);
        $availableCoachIds = [];
        foreach ((new SchedulingService())->availableCoachesForSlot($sessionDate, $time) as $coach) {
            $coachId = (int) ($coach['id'] ?? 0);
            if ($coachId <= 0 || $this->carts->coachHasOverlap($coachId, $sessionDate, $startTime, $endTime, $userId)) {
                continue;
            }
            $availableCoachIds[] = $coachId;
            if ($preferredCoachUserId !== null && $preferredCoachUserId > 0 && $coachId === $preferredCoachUserId) {
                return $coachId;
            }
        }

        if ($preferredCoachUserId !== null && $preferredCoachUserId > 0) {
            return null;
        }

        return $availableCoachIds[0] ?? null;
    }

    private function normalizeSlot(string $date, string $time): array
    {
        $sessionDate = validateDate($date, false);

        $ranges = array_values(array_filter(array_map('trim', explode(',', $time)), static fn(string $range): bool => $range !== ''));
        if (!$ranges) {
            throw new RuntimeException('Time range is invalid.');
        }

        $normalizedRanges = [];
        foreach ($ranges as $range) {
            $parts = preg_split('/\s*-\s*/', trim($range));
            if (!$parts || count($parts) !== 2) {
                throw new RuntimeException('Time range is invalid.');
            }
            [$start, $end] = validateTime($parts[0], $parts[1], $sessionDate);
            $normalizedRanges[] = [$start, $end];
        }

        usort($normalizedRanges, static fn(array $a, array $b): int => strcmp($a[0], $b[0]));
        for ($i = 1, $count = count($normalizedRanges); $i < $count; $i++) {
            if ($normalizedRanges[$i][0] !== $normalizedRanges[$i - 1][1]) {
                throw new RuntimeException('Selected time ranges must be consecutive.');
            }
        }

        return [$sessionDate, $normalizedRanges[0][0], $normalizedRanges[count($normalizedRanges) - 1][1], count($normalizedRanges)];
    }

    private function normalizeTime(string $value): string
    {
        $value = trim($value);
        foreach (['H:i:s', 'H:i', 'g:i A', 'h:i A', 'g A', 'h A'] as $format) {
            $time = DateTimeImmutable::createFromFormat($format, $value);
            if ($time instanceof DateTimeImmutable) {
                return $time->format('H:i:s');
            }
        }
        throw new RuntimeException('Time is invalid.');
    }

    private function hydrateItems(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[(string) $row['cart_item_id']] = [
                'id' => (string) $row['cart_item_id'],
                'session_id' => $row['session_id'] === null ? null : (int) $row['session_id'],
                'coach_user_id' => empty($row['coach_user_id']) ? null : (int) $row['coach_user_id'],
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

    private function removeExpiredRows(int $cartId, array $rows): int
    {
        $removed = 0;
        foreach ($rows as $row) {
            $date = (string) ($row['session_date_raw'] ?? '');
            $start = (string) ($row['start_time'] ?? '');
            if ($date === '' || $start === '') {
                continue;
            }

            try {
                if (pickled_schedule_starts_in_future($date, $start)) {
                    continue;
                }
            } catch (Throwable $e) {
                error_log('Cart item removed because schedule datetime could not be validated. cart_id=' . $cartId . '; cart_item_id=' . (int) ($row['cart_item_id'] ?? 0) . '; error=' . $e->getMessage());
            }

            $this->carts->removeItem((int) $row['cart_item_id'], $cartId);
            $removed++;
        }

        return $removed;
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
