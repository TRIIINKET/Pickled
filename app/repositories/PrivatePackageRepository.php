<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class PrivatePackageRepository
{
    public function __construct(private readonly ?PDO $connection = null) {}

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO private_packages (title, description, price, duration, coach_profile_id, status)
             VALUES (:title, :description, :price, :duration, :coach_profile_id, :status)'
        );
        $stmt->execute($this->params($data));

        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $params = $this->params($data) + ['id' => $id];
        $stmt = $this->db()->prepare(
            'UPDATE private_packages
             SET title = :title,
                 description = :description,
                 price = :price,
                 duration = :duration,
                 coach_profile_id = :coach_profile_id,
                 status = :status
             WHERE id = :id'
        );

        return $stmt->execute($params);
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->db()->prepare('UPDATE private_packages SET status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare($this->selectSql() . ' WHERE pp.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);

        return $package ?: null;
    }

    public function active(): array
    {
        $stmt = $this->db()->query($this->selectSql() . " WHERE pp.status = 'active' ORDER BY pp.created_at DESC, pp.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function all(?string $status = null, string $search = '', int $limit = 100): array
    {
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'pp.status = :status';
            $params[':status'] = $status;
        }

        $search = trim($search);
        if ($search !== '') {
            $where[] = '(pp.title LIKE :q_title OR pp.description LIKE :q_description OR u.name LIKE :q_coach)';
            $params[':q_title'] = '%' . $search . '%';
            $params[':q_description'] = '%' . $search . '%';
            $params[':q_coach'] = '%' . $search . '%';
        }

        $sql = $this->selectSql();
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY pp.created_at DESC, pp.id DESC LIMIT :limit_count';

        $stmt = $this->db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit_count', max(1, min($limit, 250)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function forCoachUser(int $coachUserId, bool $activeOnly = false): array
    {
        $sql = $this->selectSql() . ' WHERE cp.user_id = :coach_user_id';
        if ($activeOnly) {
            $sql .= " AND pp.status = 'active'";
        }
        $sql .= ' ORDER BY pp.created_at DESC, pp.id DESC';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach_user_id' => $coachUserId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function coachProfiles(): array
    {
        $stmt = $this->db()->query(
            "SELECT cp.id, cp.user_id, u.name, u.email, cp.specialization, cp.status
             FROM coach_profiles cp
             JOIN users u ON u.id = cp.user_id
             WHERE u.role = 'coach'
             ORDER BY u.name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function coachProfileById(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT cp.id, cp.user_id, u.name, u.email, cp.specialization, cp.status
             FROM coach_profiles cp
             JOIN users u ON u.id = cp.user_id
             WHERE cp.id = :id
               AND u.role = 'coach'
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);

        return $coach ?: null;
    }

    private function params(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => (float) $data['price'],
            'duration' => $data['duration'],
            'coach_profile_id' => (int) $data['coach_profile_id'],
            'status' => $data['status'],
        ];
    }

    private function selectSql(): string
    {
        return "SELECT pp.*,
                       cp.user_id AS coach_user_id,
                       cp.specialization AS coach_specialization,
                       u.name AS coach_name,
                       u.email AS coach_email
                FROM private_packages pp
                JOIN coach_profiles cp ON cp.id = pp.coach_profile_id
                JOIN users u ON u.id = cp.user_id";
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }
}
