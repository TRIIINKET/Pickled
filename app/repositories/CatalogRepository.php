<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../services/SchedulingService.php';

final class CatalogRepository
{
    public function findCourtBySlug(string $slug, bool $includeInactive = false): ?array
    {
        $sql = 'SELECT * FROM courts WHERE slug = :slug';
        if (!$includeInactive) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $court = $stmt->fetch();
        return $court ?: null;
    }

    public function findCourtById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM courts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $court = $stmt->fetch();
        return $court ?: null;
    }

    public function courts(bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM courts';
        if (!$includeInactive) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= ' ORDER BY id ASC';

        return Database::connection()->query($sql)->fetchAll() ?: [];
    }

    public function createCourt(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO courts (name, slug, status)
             VALUES (:name, :slug, :status)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'status' => $data['status'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateCourt(int $id, array $data): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE courts
             SET name = :name, slug = :slug, status = :status
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'status' => $data['status'],
        ]);
    }

    public function setCourtStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE courts SET status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function findVariantBySlug(string $slug, bool $includeInactive = false): ?array
    {
        $sql = 'SELECT v.*, c.name AS court, c.slug AS court_slug, c.status AS court_status
                FROM booking_variants v
                JOIN courts c ON c.id = v.court_id
                WHERE v.slug = :slug';
        if (!$includeInactive) {
            $sql .= " AND v.active = 1 AND c.status = 'active'";
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $variant = $stmt->fetch();
        return $variant ?: null;
    }

    public function findVariantById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT v.*, c.name AS court, c.slug AS court_slug
             FROM booking_variants v
             JOIN courts c ON c.id = v.court_id
             WHERE v.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $variant = $stmt->fetch();
        return $variant ?: null;
    }

    public function variantsForCourtSlug(string $courtSlug, bool $includeInactive = false): array
    {
        $sql = 'SELECT v.*, c.name AS court, c.slug AS court_slug
                FROM booking_variants v
                JOIN courts c ON c.id = v.court_id
                WHERE c.slug = :court_slug';
        if (!$includeInactive) {
            $sql .= " AND v.active = 1 AND c.status = 'active'";
        }
        $sql .= ' ORDER BY v.id ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['court_slug' => $courtSlug]);
        return $stmt->fetchAll() ?: [];
    }

    public function socialVariants(bool $includeInactive = false): array
    {
        $sql = "SELECT v.*, c.name AS court, c.name AS court_name, c.slug AS court_slug
                FROM booking_variants v
                JOIN courts c ON c.id = v.court_id
                WHERE (v.category = 'Social Play' OR v.name LIKE '%Match%' OR v.name LIKE '%Tournament%')";
        if (!$includeInactive) {
            $sql .= " AND v.active = 1 AND c.status = 'active'";
        }
        $sql .= ' ORDER BY v.id ASC';

        return Database::connection()->query($sql)->fetchAll() ?: [];
    }

    public function variants(bool $includeInactive = false): array
    {
        $sql = 'SELECT v.*, c.name AS court, c.slug AS court_slug
                FROM booking_variants v
                JOIN courts c ON c.id = v.court_id';
        if (!$includeInactive) {
            $sql .= " WHERE v.active = 1 AND c.status = 'active'";
        }
        $sql .= ' ORDER BY c.id ASC, v.id ASC';

        return Database::connection()->query($sql)->fetchAll() ?: [];
    }

    public function createVariant(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO booking_variants
                (court_id, slug, name, category, duration_label, price, participants_limit, capacity, image, active)
             VALUES
                (:court_id, :slug, :name, :category, :duration_label, :price, :participants_limit, :capacity, :image, :active)'
        );
        $stmt->execute($this->variantParams($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function updateVariant(int $id, array $data): bool
    {
        $params = $this->variantParams($data) + ['id' => $id];
        $stmt = Database::connection()->prepare(
            'UPDATE booking_variants
             SET court_id = :court_id,
                 slug = :slug,
                 name = :name,
                 category = :category,
                 duration_label = :duration_label,
                 price = :price,
                 participants_limit = :participants_limit,
                 capacity = :capacity,
                 image = :image,
                 active = :active
             WHERE id = :id'
        );
        return $stmt->execute($params);
    }

    public function setVariantActive(int $id, bool $active): bool
    {
        $stmt = Database::connection()->prepare('UPDATE booking_variants SET active = :active WHERE id = :id');
        return $stmt->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public function findOrCreateSession(int $variantId, string $date, string $time, int $capacity): array
    {
        return (new SchedulingService())->findOrCreateSession($variantId, $date, $time, $capacity);
    }

    public function sessionsForVariantMonth(int $variantId, int $year, int $month): array
    {
        return (new SchedulingService())->sessionsForVariantMonth($variantId, $year, $month);
    }

    public function sessionById(int $sessionId, bool $forUpdate = false): ?array
    {
        return (new SchedulingService())->sessionById($sessionId, $forUpdate);
    }

    public function incrementBookedCount(int $sessionId, int $quantity): bool
    {
        return (new SchedulingService())->incrementBookedCount($sessionId, $quantity);
    }

    private function variantParams(array $data): array
    {
        return [
            'court_id' => (int) $data['court_id'],
            'slug' => $data['slug'],
            'name' => $data['name'],
            'category' => $data['category'],
            'duration_label' => $data['duration_label'],
            'price' => (float) $data['price'],
            'participants_limit' => (int) $data['participants_limit'],
            'capacity' => (int) $data['capacity'],
            'image' => $data['image'] ?? null,
            'active' => !empty($data['active']) ? 1 : 0,
        ];
    }
}
