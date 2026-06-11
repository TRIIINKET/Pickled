<?php
declare(strict_types=1);

final class DatabaseRedesign
{
    public static function active(): bool
    {
        $config = require __DIR__ . '/../../includes/config.php';
        return empty($config['database']['enabled']);
    }

    public static function demoPassword(): string
    {
        return 'password';
    }

    public static function users(): array
    {
        $users = [
            [
                'id' => 1,
                'name' => 'Admin Demo',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'password_hash' => password_hash(self::demoPassword(), PASSWORD_DEFAULT),
                'created_at' => '2026-06-01 09:00:00',
            ],
            [
                'id' => 2,
                'name' => 'Coach Alex',
                'email' => 'coach@example.com',
                'role' => 'coach',
                'password_hash' => password_hash(self::demoPassword(), PASSWORD_DEFAULT),
                'created_at' => '2026-06-02 09:00:00',
            ],
            [
                'id' => 3,
                'name' => 'Player Demo',
                'email' => 'player@example.com',
                'role' => 'player',
                'password_hash' => password_hash(self::demoPassword(), PASSWORD_DEFAULT),
                'created_at' => '2026-06-03 09:00:00',
            ],
        ];

        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['redesign_users'])) {
            $users = array_merge($users, array_values($_SESSION['redesign_users']));
        }

        return $users;
    }

    public static function userByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        foreach (self::users() as $user) {
            if (strtolower((string) $user['email']) === $email) {
                return $user;
            }
        }

        return null;
    }

    public static function userById(int $id): ?array
    {
        foreach (self::users() as $user) {
            if ((int) $user['id'] === $id) {
                return $user;
            }
        }

        return null;
    }

    public static function usersByRole(string $role): array
    {
        return array_values(array_filter(self::users(), static fn(array $user): bool => ($user['role'] ?? '') === $role));
    }

    public static function createUser(string $name, string $email, string $passwordHash): array
    {
        $user = [
            'id' => self::nextSessionId('redesign_next_user_id', 100),
            'name' => $name,
            'email' => strtolower($email),
            'role' => 'player',
            'password_hash' => $passwordHash,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['redesign_users'][(int) $user['id']] = $user;
        }

        return $user;
    }

    public static function variants(): array
    {
        $greenImage = '../assets/img/court/court green-1.png';
        $pinkImage = '../assets/img/court/court pink-1.webp';

        return [
            'green-court-rentals' => self::variant(1, 'green-court-rentals', 'Court Rentals', 'Court Rental', 600, '1 hour', 4, 24, 'Court Green', 'green', 1, $greenImage),
            'green-lessons' => self::variant(2, 'green-lessons', 'Lessons', 'Coaching', 500, '1 hour', 4, 16, 'Court Green', 'green', 1, $greenImage),
            'green-private-coaching' => self::variant(3, 'green-private-coaching', 'Private Coaching', 'Private Coaching', 1200, '1 hour', 1, 4, 'Court Green', 'green', 1, $greenImage),
            'green-training' => self::variant(4, 'green-training', 'Training', 'Training', 800, '1 hour', 4, 16, 'Court Green', 'green', 1, $greenImage),
            'green-open-match-play' => self::variant(5, 'green-open-match-play', 'Open Match-Play', 'Social Play', 350, '2 hours', 1, 16, 'Court Green', 'green', 1, $greenImage),
            'green-weekly-tournament' => self::variant(6, 'green-weekly-tournament', 'Weekly Tournament', 'Social Play', 900, 'This week', 1, 24, 'Court Green', 'green', 1, $greenImage),
            'pink-base-rate' => self::variant(7, 'pink-base-rate', 'Court Rental', 'Court Rental', 400, '1 hour', 4, 20, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-kids-pickleball-class-ages-6-10' => self::variant(8, 'pink-kids-pickleball-class-ages-6-10', 'Kids Pickleball Class (Ages 6-10)', 'Academy', 350, '1 hour', 1, 12, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-youth-development-class-ages-11-17' => self::variant(9, 'pink-youth-development-class-ages-11-17', 'Youth Development Class (Ages 11-17)', 'Academy', 350, '1 hour', 1, 12, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-parent-child-session' => self::variant(10, 'pink-parent-child-session', 'Parent & Child Session', 'Academy', 500, '1 hour', 2, 10, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-foundational-ages-6-10' => self::variant(11, 'pink-foundational-ages-6-10', 'Foundational Class (Ages 6-10)', 'Academy', 350, '1 hour', 1, 12, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-youth-development-ages-11-17' => self::variant(12, 'pink-youth-development-ages-11-17', 'Youth Development (Ages 11-17)', 'Academy', 350, '1 hour', 1, 12, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-adult-beginner-bootcamp' => self::variant(13, 'pink-adult-beginner-bootcamp', 'Adult Beginner Bootcamp', 'Academy', 500, '1 hour', 1, 12, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-introductory-trial-class' => self::variant(14, 'pink-introductory-trial-class', 'Introductory Trial Class', 'Academy', 300, '1 hour', 1, 12, 'Court Pink', 'pink', 2, $pinkImage),
            'pink-parent-child-trial' => self::variant(15, 'pink-parent-child-trial', 'Parent & Child Trial', 'Academy', 500, '1 hour', 2, 10, 'Court Pink', 'pink', 2, $pinkImage),
        ];
    }

    public static function variantBySlug(string $slug): ?array
    {
        return self::variants()[$slug] ?? null;
    }

    public static function variantsForCourt(string $courtSlug): array
    {
        return array_values(array_filter(self::variants(), static fn(array $variant): bool => ($variant['court_slug'] ?? '') === $courtSlug));
    }

    public static function socialVariants(): array
    {
        return array_values(array_filter(self::variants(), static fn(array $variant): bool => ($variant['category'] ?? '') === 'Social Play'));
    }

    public static function courts(): array
    {
        return [
            ['id' => 1, 'name' => 'Court Green', 'slug' => 'green', 'today_booked' => 0, 'total_capacity' => 24],
            ['id' => 2, 'name' => 'Court Pink', 'slug' => 'pink', 'today_booked' => 0, 'total_capacity' => 20],
        ];
    }

    public static function syntheticSession(int $variantId, string $date, string $time, int $capacity): array
    {
        return [
            'id' => hexdec(substr(sha1($variantId . '|' . $date . '|' . $time), 0, 8)),
            'variant_id' => $variantId,
            'session_date' => $date,
            'session_time' => $time,
            'capacity' => $capacity,
            'booked_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    public static function createBooking(int $userId, array $booking): array
    {
        $id = self::nextSessionId('redesign_next_booking_id', 1);
        $stored = $booking + [
            'id' => $id,
            'user_id' => $userId,
            'created_at' => $booking['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['redesign_bookings'][$id] = $stored;
        }

        return $stored;
    }

    public static function bookings(?int $userId = null): array
    {
        $bookings = session_status() === PHP_SESSION_ACTIVE ? array_values($_SESSION['redesign_bookings'] ?? []) : [];
        if ($userId !== null) {
            $bookings = array_values(array_filter($bookings, static fn(array $booking): bool => (int) ($booking['user_id'] ?? 0) === $userId));
        }

        usort($bookings, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return $bookings;
    }

    public static function bookingById(int $id): ?array
    {
        foreach (self::bookings() as $booking) {
            if ((int) ($booking['id'] ?? 0) === $id) {
                return $booking;
            }
        }

        return null;
    }

    public static function bookingItems(int $bookingId): array
    {
        $booking = self::bookingById($bookingId);
        if (!$booking) {
            return [];
        }

        return self::bookingItemRows($booking);
    }

    public static function bookingItemRows(array $booking): array
    {
        $rows = [];
        foreach (($booking['items'] ?? []) as $index => $item) {
            $rows[] = [
                'id' => $index + 1,
                'booking_id' => (int) ($booking['id'] ?? 0),
                'session_id' => (int) ($item['session_id'] ?? 0),
                'variant_id' => $item['variant_id'] ?? 'custom',
                'name' => $item['name'] ?? 'Booking',
                'court' => $item['court'] ?? 'Court',
                'category' => $item['category'] ?? 'Booking',
                'duration_label' => $item['duration'] ?? ($item['duration_label'] ?? ''),
                'booking_date' => $item['date'] ?? ($item['booking_date'] ?? ''),
                'booking_time' => $item['time'] ?? ($item['booking_time'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['price'] ?? ($item['unit_price'] ?? 0)),
                'image' => $item['image'] ?? '../assets/img/Hero.jpg',
            ];
        }

        return $rows;
    }

    public static function dashboardStats(): array
    {
        $bookings = self::bookings();
        return [
            'total_users' => count(self::users()),
            'total_bookings' => count($bookings),
            'total_revenue' => array_sum(array_map(static fn(array $booking): float => (float) ($booking['total'] ?? 0), $bookings)),
            'pending_payments' => count(array_filter($bookings, static fn(array $booking): bool => str_contains(strtolower((string) ($booking['payment_status'] ?? '')), 'pending'))),
            'total_events' => 0,
            'total_courts' => count(self::courts()),
        ];
    }

    public static function adminLogs(int $limit = 100): array
    {
        return array_slice([
            [
                'name' => 'Database Redesign Mode',
                'action' => 'database_disabled',
                'entity_type' => 'system',
                'entity_id' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ], 0, $limit);
    }

    private static function variant(int $id, string $slug, string $name, string $category, float $price, string $duration, int $participants, int $capacity, string $court, string $courtSlug, int $courtId, string $image): array
    {
        return [
            'id' => $id,
            'slug' => $slug,
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'duration_label' => $duration,
            'participants_limit' => $participants,
            'capacity' => $capacity,
            'image' => $image,
            'active' => 1,
            'court' => $court,
            'court_slug' => $courtSlug,
            'court_id' => $courtId,
        ];
    }

    private static function nextSessionId(string $key, int $start): int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $start;
        }

        $next = (int) ($_SESSION[$key] ?? $start);
        $_SESSION[$key] = $next + 1;
        return $next;
    }
}
