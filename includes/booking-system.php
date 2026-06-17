<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/services/CartService.php';
require_once __DIR__ . '/../app/services/BookingExpiryService.php';
require_once __DIR__ . '/../app/repositories/CatalogRepository.php';

const PICKLED_CART_LIMIT = 3;
const PICKLED_CART_HOLD_SECONDS = 300;
const PICKLED_WAITLIST_CLAIM_SECONDS = 900;

function pickled_app_config(): array {
    return require __DIR__ . '/config.php';
}

function pickled_cart_limit(): int {
    $config = pickled_app_config();
    return (int) ($config['cart']['item_limit'] ?? PICKLED_CART_LIMIT);
}

function pickled_cart_hold_seconds(): int {
    $config = pickled_app_config();
    return (int) ($config['cart']['hold_seconds'] ?? PICKLED_CART_HOLD_SECONDS);
}

function pickled_is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function pickled_clear_booking_state(): void {
    unset(
        $_SESSION['user'],
        $_SESSION['cart'],
        $_SESSION['cart_started_at'],
        $_SESSION['cart_expires_at'],
        $_SESSION['checkout_notes'],
        $_SESSION['last_booking']
    );
}

function pickled_require_login(string $redirect): void {
    if (pickled_is_logged_in()) {
        return;
    }

    pickled_clear_booking_state();
    header('Location: ../auth/login.php?notice=booking&redirect=' . rawurlencode($redirect));
    exit;
}

function pickled_restore_cart_for_user(): void {
    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    $saved = (new CartService())->restoreForUser($userId);

    $_SESSION['cart'] = $saved['items'];
    $_SESSION['cart_started_at'] = $saved['started_at'];
    $_SESSION['cart_expires_at'] = $saved['expires_at'];
    if ((int) ($saved['removed_expired'] ?? 0) > 0) {
        $_SESSION['cart_removed_expired'] = true;
    }
}

function pickled_persist_cart_for_user(): void {
    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    (new CartService())->persistTimerForUser($userId, $_SESSION['cart_started_at'] ?? null, $_SESSION['cart_expires_at'] ?? null);
}

function pickled_cart_count(): int {
    $cart = $_SESSION['cart'] ?? [];
    return array_sum(array_map(static fn($item) => (int) ($item['quantity'] ?? 0), $cart));
}

function pickled_start_cart_timer(): void {
    if (empty($_SESSION['cart_started_at']) || empty($_SESSION['cart_expires_at'])) {
        $_SESSION['cart_started_at'] = time();
        $_SESSION['cart_expires_at'] = time() + pickled_cart_hold_seconds();
    }
    pickled_persist_cart_for_user();
}

function pickled_clear_cart_timer(): void {
    unset($_SESSION['cart_started_at'], $_SESSION['cart_expires_at']);
}

function pickled_expire_cart_if_needed(): bool {
    if (!empty($_SESSION['cart_expires_at']) && time() >= (int) $_SESSION['cart_expires_at']) {
        $_SESSION['cart'] = [];
        (new CartService())->clearForUser((int) ($_SESSION['user']['id'] ?? 0));
        pickled_clear_cart_timer();
        return true;
    }

    return false;
}

function pickled_process_pending_booking_expiry(): int {
    try {
        return (new BookingExpiryService())->processExpiredPendingBookings();
    } catch (Throwable $e) {
        error_log('Pending booking expiry failed: ' . $e->getMessage());
        return 0;
    }
}

function pickled_is_member(): bool {
    $email = strtolower((string) ($_SESSION['user']['email'] ?? ''));
    return $email === 'player@example.com' || !empty($_SESSION['membership']['active']);
}

function pickled_member_discount(float $price): float {
    return pickled_is_member() ? round($price * 0.9, 2) : $price;
}

function pickled_booking_catalog(): array {
    return [];
}

function pickled_catalog_item(string $variantId): ?array {
    $variant = (new CatalogRepository())->findVariantBySlug($variantId);
    if (!$variant) {
        return null;
    }

    return [
        'variant_id' => $variant['slug'],
        'name' => $variant['name'],
        'court' => $variant['court'],
        'category' => $variant['category'],
        'price' => (float) $variant['price'],
        'duration' => $variant['duration_label'],
        'participants' => (int) $variant['participants_limit'],
        'capacity' => (int) $variant['capacity'],
        'booked' => 0,
        'availability' => 'Available',
        'image' => $variant['image'] ?: '../assets/img/Hero.jpg',
        'remaining' => (int) $variant['capacity'],
        'is_full' => false,
        'member_price' => pickled_member_discount((float) $variant['price']),
    ];
}

function pickled_cart_item_id(array $item, string $date, string $time): string {
    return substr(sha1($item['variant_id'] . '|' . $date . '|' . $time), 0, 14);
}

function pickled_add_to_cart(string $variantId, int $quantity, string $date, string $time, ?int $coachUserId = null, ?int $sessionId = null): array {
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    error_log('Cart POST received. user_id=' . (int) ($_SESSION['user']['id'] ?? 0) . '; variant_id=' . $variantId . '; session_id=' . (string) ($sessionId ?? '') . '; booking_date=' . $date . '; time=' . $time . '; quantity=' . $quantity . '; coach_user_id=' . (string) ($coachUserId ?? ''));
    if ($variantId === '' || $date === '' || $time === '') {
        return ['ok' => false, 'code' => 'invalid'];
    }
    if (pickled_cart_count() + $quantity > pickled_cart_limit()) {
        return ['ok' => false, 'code' => 'limit'];
    }

    $item = pickled_catalog_item($variantId);
    if (!$item) {
        return ['ok' => false, 'code' => 'invalid'];
    }

    pickled_start_cart_timer();
    try {
        $result = (new CartService())->addVariantForUser(
            (int) ($_SESSION['user']['id'] ?? 0),
            $variantId,
            $quantity,
            $date,
            $time,
            $_SESSION['cart_started_at'] ?? null,
            $_SESSION['cart_expires_at'] ?? null,
            (float) $item['member_price'],
            $coachUserId,
            $sessionId
        );
    } catch (RuntimeException) {
        $result = ['ok' => false, 'code' => 'invalid'];
    }

    unset($_SESSION['cart']);
    pickled_restore_cart_for_user();
    return $result;
}

function pickled_update_cart_quantity(int $cartItemId, int $quantity): array {
    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if ($userId <= 0 || $cartItemId <= 0) {
        return ['ok' => false, 'code' => 'invalid'];
    }

    pickled_restore_cart_for_user();
    $cart = $_SESSION['cart'] ?? [];
    if (!isset($cart[(string) $cartItemId])) {
        return ['ok' => false, 'code' => 'invalid'];
    }

    $quantity = max(1, $quantity);
    $currentQuantity = (int) ($cart[(string) $cartItemId]['quantity'] ?? 0);
    if (pickled_cart_count() - $currentQuantity + $quantity > pickled_cart_limit()) {
        return ['ok' => false, 'code' => 'limit'];
    }

    $result = (new CartService())->updateQuantityForUser($userId, $cartItemId, $quantity);
    unset($_SESSION['cart']);
    pickled_restore_cart_for_user();
    return $result;
}

function pickled_join_waitlist(string $variantId): array {
    $item = pickled_catalog_item($variantId);
    if (!$item) {
        return ['ok' => false, 'code' => 'invalid'];
    }

    $_SESSION['waitlist'] = $_SESSION['waitlist'] ?? [];
    if (isset($_SESSION['waitlist'][$variantId])) {
        return ['ok' => false, 'code' => 'waitlisted'];
    }

    $_SESSION['waitlist'][$variantId] = [
        'variant_id' => $variantId,
        'name' => $item['name'],
        'court' => $item['court'],
        'position' => count($_SESSION['waitlist']) + 1,
        'claim_expires_at' => time() + PICKLED_WAITLIST_CLAIM_SECONDS,
        'status' => 'Waiting for a slot',
    ];

    return ['ok' => true, 'code' => 'waitlist'];
}

function pickled_cart_total(): float {
    $cart = $_SESSION['cart'] ?? [];
    return array_reduce($cart, static function ($sum, $item) {
        return $sum + ((float) $item['price'] * (int) $item['quantity']);
    }, 0.0);
}

function pickled_cancellation_policy(int $bookingTimestamp): array {
    $hoursUntilBooking = ($bookingTimestamp - time()) / 3600;
    if ($hoursUntilBooking > 24) {
        return ['eligible' => true, 'label' => 'Cancellable until 24 hours before schedule'];
    }

    return ['eligible' => false, 'label' => 'Within 24 hours - cancellation unavailable'];
}
