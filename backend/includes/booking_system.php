<?php
declare(strict_types=1);

const PICKLED_CART_LIMIT = 3;
const PICKLED_CART_HOLD_SECONDS = 300;
const PICKLED_WAITLIST_CLAIM_SECONDS = 900;

function pickled_is_logged_in(): bool {
    $config = require __DIR__ . '/../config/app.php';
    return !empty($_SESSION['user']) && isset($_COOKIE[$config['login_cookie']['name']]);
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
    header('Location: ../login.php?notice=booking&redirect=' . rawurlencode($redirect));
    exit;
}

function pickled_cart_count(): int {
    $cart = $_SESSION['cart'] ?? [];
    return array_sum(array_map(static fn($item) => (int) ($item['quantity'] ?? 0), $cart));
}

function pickled_start_cart_timer(): void {
    if (empty($_SESSION['cart_started_at']) || empty($_SESSION['cart_expires_at'])) {
        $_SESSION['cart_started_at'] = time();
        $_SESSION['cart_expires_at'] = time() + PICKLED_CART_HOLD_SECONDS;
    }
}

function pickled_clear_cart_timer(): void {
    unset($_SESSION['cart_started_at'], $_SESSION['cart_expires_at']);
}

function pickled_expire_cart_if_needed(): bool {
    if (!empty($_SESSION['cart_expires_at']) && time() >= (int) $_SESSION['cart_expires_at']) {
        $_SESSION['cart'] = [];
        pickled_clear_cart_timer();
        return true;
    }

    return false;
}

function pickled_is_member(): bool {
    $email = strtolower((string) ($_SESSION['user']['email'] ?? ''));
    return $email === 'player@example.com' || !empty($_SESSION['membership']['active']);
}

function pickled_member_discount(float $price): float {
    return pickled_is_member() ? round($price * 0.9, 2) : $price;
}

function pickled_booking_catalog(): array {
    return [
        'green-peak' => [
            'name' => 'Peak Hours',
            'court' => 'Court Green',
            'court_key' => 'green',
            'category' => 'Court Reservation',
            'price' => 600,
            'duration' => '1 hour',
            'participants' => 6,
            'capacity' => 12,
            'booked' => 6,
            'availability' => '6 slots remaining',
            'level' => 'Competitive',
            'badge' => 'Professional standard',
            'schedule' => 'Mon-Fri 6pm-10pm, weekends and holidays',
            'description' => 'Premium standard-court play for peak hours, competitive rallies, and match-ready groups.',
            'image' => '../assets/Images/Hero.jpg',
        ],
        'green-off-peak' => [
            'name' => 'Off-Peak',
            'court' => 'Court Green',
            'court_key' => 'green',
            'category' => 'Court Reservation',
            'price' => 400,
            'duration' => '1 hour',
            'participants' => 6,
            'capacity' => 10,
            'booked' => 2,
            'availability' => '8 slots remaining',
            'level' => 'All levels',
            'badge' => 'Best value',
            'schedule' => 'Mon-Fri 7am-5pm',
            'description' => 'Easy daytime court access for drills, practice games, and relaxed team play.',
            'image' => '../assets/Images/Hero.jpg',
        ],
        'green-private-coaching' => [
            'name' => 'Private Coaching',
            'court' => 'Court Green',
            'court_key' => 'green',
            'category' => 'Coaching',
            'price' => 1200,
            'duration' => '1 hour',
            'participants' => 1,
            'capacity' => 4,
            'booked' => 1,
            'availability' => '3 coach slots',
            'level' => 'Personalized',
            'badge' => 'Certified coach',
            'schedule' => 'Coach-matched schedule',
            'description' => 'One-on-one training for tactical decision-making, shot selection, and point construction.',
            'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__205.jpg?v=1744700285&width=900',
        ],
        'green-social-play' => [
            'name' => 'Social Play',
            'court' => 'Court Green',
            'court_key' => 'green',
            'category' => 'Open Match-Play',
            'price' => 350,
            'duration' => '2 hours',
            'participants' => 1,
            'capacity' => 16,
            'booked' => 10,
            'availability' => '6 slots remaining',
            'level' => 'Beginner to intermediate',
            'badge' => 'Rotating partners',
            'schedule' => 'Tue, Thu, Sat evenings',
            'description' => 'Structured open play with rotating partners, balanced matchups, and a welcoming community rhythm.',
            'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__215.jpg?v=1744701445&width=900',
        ],
        'green-intermediate-clinic' => [
            'name' => 'Intermediate Clinic',
            'court' => 'Court Green',
            'court_key' => 'green',
            'category' => 'Training',
            'price' => 500,
            'duration' => '90 minutes',
            'participants' => 1,
            'capacity' => 8,
            'booked' => 6,
            'availability' => '2 slots remaining',
            'level' => 'Intermediate',
            'badge' => 'Tactical drills',
            'schedule' => 'Wednesdays 7pm',
            'description' => 'Level up transitions, third-shot strategy, kitchen control, and pressure-point decision making.',
            'image' => 'https://pickleand.club/cdn/shop/files/class2-touched.jpg?v=1744706809&width=1600',
        ],
        'green-pro-series' => [
            'name' => 'Pro Series Bootcamp',
            'court' => 'Court Green',
            'court_key' => 'green',
            'category' => 'Tournament Prep',
            'price' => 2500,
            'duration' => '3 days',
            'participants' => 1,
            'capacity' => 8,
            'booked' => 8,
            'availability' => 'Full - waitlist open',
            'level' => 'Advanced',
            'badge' => 'Waitlist',
            'schedule' => 'Monthly intensive',
            'description' => 'A high-intensity bootcamp for attacking patterns, modern offense, resets, and tournament structure.',
            'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__008.jpg?v=1744701445&width=1200',
        ],
        'pink-foundational' => [
            'name' => 'Foundational Training',
            'court' => 'Court Pink',
            'court_key' => 'pink',
            'category' => 'Kids Program',
            'price' => 1200,
            'duration' => '4 sessions',
            'participants' => 1,
            'capacity' => 10,
            'booked' => 4,
            'availability' => '6 slots remaining',
            'level' => 'Ages 6-10',
            'badge' => 'Beginner friendly',
            'schedule' => 'Weekend mornings',
            'description' => 'Hand-eye coordination, first rallies, movement basics, and game confidence for younger players.',
            'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__024r.jpg?v=1744816152&width=1200',
        ],
        'pink-youth-development' => [
            'name' => 'Youth Development',
            'court' => 'Court Pink',
            'court_key' => 'pink',
            'category' => 'Youth Program',
            'price' => 1200,
            'duration' => '4 sessions',
            'participants' => 1,
            'capacity' => 10,
            'booked' => 5,
            'availability' => '5 slots remaining',
            'level' => 'Ages 11-17',
            'badge' => 'Confidence builder',
            'schedule' => 'After-school blocks',
            'description' => 'A friendly progression for serves, returns, footwork, and simple doubles patterns.',
            'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__208_cc44460d-89be-4e85-a4cb-835987f57ee6.jpg?v=1744816152&width=1200',
        ],
        'pink-adult-bootcamp' => [
            'name' => 'Adult Beginner Bootcamp',
            'court' => 'Court Pink',
            'court_key' => 'pink',
            'category' => 'Beginner Lessons',
            'price' => 1800,
            'duration' => '4 sessions',
            'participants' => 1,
            'capacity' => 12,
            'booked' => 7,
            'availability' => '5 slots remaining',
            'level' => 'First-timers',
            'badge' => 'No-pressure learning',
            'schedule' => 'Weeknights',
            'description' => 'A complete beginner path covering scoring, serving, rallies, positioning, and confidence.',
            'image' => 'https://pickleand.club/cdn/shop/files/class2-touched.jpg?v=1744706809&width=1600',
        ],
        'pink-trial' => [
            'name' => 'Introductory Trial Class',
            'court' => 'Court Pink',
            'court_key' => 'pink',
            'category' => 'Trial Class',
            'price' => 250,
            'duration' => '1 hour',
            'participants' => 1,
            'capacity' => 8,
            'booked' => 3,
            'availability' => '5 slots remaining',
            'level' => 'New players',
            'badge' => 'Starter pick',
            'schedule' => 'Daily selected slots',
            'description' => 'A low-commitment first session to learn the rules, hit your first shots, and try the court.',
            'image' => '../assets/Images/Hero.jpg',
        ],
        'pink-parent-child' => [
            'name' => 'Parent & Child Trial',
            'court' => 'Court Pink',
            'court_key' => 'pink',
            'category' => 'Family Play',
            'price' => 500,
            'duration' => '1 hour',
            'participants' => 2,
            'capacity' => 8,
            'booked' => 8,
            'availability' => 'Full - waitlist open',
            'level' => 'Ages 6+',
            'badge' => 'Waitlist',
            'schedule' => 'Sunday family blocks',
            'description' => 'A playful shared session for one adult and one child, with guided games and basic rallies.',
            'image' => 'https://pickleand.club/cdn/shop/files/250411_-_Pickle__055.jpg?v=1744709434&width=900',
        ],
    ];
}

function pickled_catalog_item(string $variantId): ?array {
    $catalog = pickled_booking_catalog();
    if (!isset($catalog[$variantId])) {
        return null;
    }

    $item = $catalog[$variantId];
    $item['variant_id'] = $variantId;
    $item['remaining'] = max(0, (int) $item['capacity'] - (int) $item['booked']);
    $item['is_full'] = $item['remaining'] <= 0;
    $item['member_price'] = pickled_member_discount((float) $item['price']);

    return $item;
}

function pickled_cart_item_id(array $item, string $date, string $time): string {
    return substr(sha1($item['variant_id'] . '|' . $date . '|' . $time), 0, 14);
}

function pickled_add_to_cart(string $variantId, int $quantity, string $date, string $time): array {
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    $item = pickled_catalog_item($variantId);
    if (!$item) {
        return ['ok' => false, 'code' => 'invalid'];
    }

    if ($item['is_full']) {
        return ['ok' => false, 'code' => 'full'];
    }

    $quantity = max(1, min($quantity, (int) $item['participants']));
    if (pickled_cart_count() + $quantity > PICKLED_CART_LIMIT) {
        return ['ok' => false, 'code' => 'limit'];
    }

    $cartId = pickled_cart_item_id($item, $date, $time);
    if (isset($_SESSION['cart'][$cartId])) {
        return ['ok' => false, 'code' => 'duplicate'];
    }

    pickled_start_cart_timer();
    $_SESSION['cart'][$cartId] = [
        'id' => $cartId,
        'variant_id' => $variantId,
        'name' => $item['name'],
        'court' => $item['court'],
        'category' => $item['category'],
        'price' => $item['member_price'],
        'base_price' => $item['price'],
        'member_discount' => pickled_is_member(),
        'quantity' => $quantity,
        'duration' => $item['duration'],
        'date' => $date,
        'time' => $time,
        'participants' => $quantity,
        'availability' => $item['availability'],
        'description' => $item['court'] . ' · ' . $item['duration'] . ' · ' . $date . ' · ' . $time,
        'image' => $item['image'],
        'status' => 'Reserved in cart',
        'created_at' => date('Y-m-d H:i:s'),
    ];

    return ['ok' => true, 'code' => 'added', 'cart_id' => $cartId];
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
    if ($hoursUntilBooking >= 48) {
        return ['eligible' => true, 'label' => 'Full credit eligible'];
    }

    return ['eligible' => false, 'label' => 'Late cancellation - no refund'];
}
