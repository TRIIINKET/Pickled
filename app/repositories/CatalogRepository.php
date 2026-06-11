<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../support/DatabaseRedesign.php';

final class CatalogRepository
{
    public function findVariantBySlug(string $slug): ?array
    {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::variantBySlug($slug);
        }

        $stmt = Database::connection()->prepare(
            'SELECT v.*, c.name AS court, c.slug AS court_slug
             FROM booking_variants v JOIN courts c ON c.id = v.court_id
             WHERE v.slug = :slug AND v.active = 1 LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $variant = $stmt->fetch();
        return $variant ?: null;
    }

    public function findOrCreateSession(int $variantId, string $date, string $time, int $capacity): array
    {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::syntheticSession($variantId, $date, $time, $capacity);
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO sessions (variant_id, session_date, session_time, capacity)
             VALUES (:variant_id, :session_date, :session_time, :capacity)
             ON DUPLICATE KEY UPDATE capacity = VALUES(capacity)'
        );
        $stmt->execute([
            'variant_id' => $variantId,
            'session_date' => $date,
            'session_time' => $time,
            'capacity' => $capacity,
        ]);

        $find = $pdo->prepare(
            'SELECT * FROM sessions WHERE variant_id = :variant_id AND session_date = :session_date AND session_time = :session_time LIMIT 1'
        );
        $find->execute(['variant_id' => $variantId, 'session_date' => $date, 'session_time' => $time]);
        return $find->fetch();
    }

    public function sessionsForVariantMonth(int $variantId, int $year, int $month): array
    {
        if (DatabaseRedesign::active()) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM sessions WHERE variant_id = :variant_id AND session_date LIKE :month_label'
        );
        $monthName = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('F');
        $stmt->execute([
            'variant_id' => $variantId,
            'month_label' => '% ' . $monthName . ' %, ' . $year,
        ]);
        return $stmt->fetchAll();
    }

    public function sessionById(int $sessionId, bool $forUpdate = false): ?array
    {
        if (DatabaseRedesign::active()) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM sessions WHERE id = :id' . ($forUpdate ? ' FOR UPDATE' : ''));
        $stmt->execute(['id' => $sessionId]);
        $session = $stmt->fetch();
        return $session ?: null;
    }

    public function incrementBookedCount(int $sessionId, int $quantity): bool
    {
        if (DatabaseRedesign::active()) {
            return true;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE sessions SET booked_count = booked_count + :increment_quantity
             WHERE id = :id AND booked_count + :capacity_quantity <= capacity'
        );
        $stmt->execute([
            'id' => $sessionId,
            'increment_quantity' => $quantity,
            'capacity_quantity' => $quantity,
        ]);
        return $stmt->rowCount() === 1;
    }
}
