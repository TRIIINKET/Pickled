<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class PrivateInquiryRepository
{
    public function __construct(private readonly ?PDO $connection = null) {}

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO private_inquiries (user_id, private_package_id, message, status)
             VALUES (:user_id, :private_package_id, :message, :status)'
        );
        $stmt->execute([
            'user_id' => (int) $data['user_id'],
            'private_package_id' => (int) $data['private_package_id'],
            'message' => $data['message'],
            'status' => $data['status'],
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare($this->selectSql() . ' WHERE pi.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

        return $inquiry ?: null;
    }

    public function forUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db()->prepare(
            $this->selectSql() . '
             WHERE pi.user_id = :user_id
             ORDER BY pi.created_at DESC, pi.id DESC
             LIMIT :limit_count'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_count', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function forCoachUser(int $coachUserId, int $limit = 50): array
    {
        $stmt = $this->db()->prepare(
            $this->selectSql() . '
             WHERE cp.user_id = :coach_user_id
             ORDER BY pi.created_at DESC, pi.id DESC
             LIMIT :limit_count'
        );
        $stmt->bindValue(':coach_user_id', $coachUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_count', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function all(?string $status = null, string $search = '', int $limit = 100): array
    {
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'pi.status = :status';
            $params[':status'] = $status;
        }

        $search = trim($search);
        if ($search !== '') {
            $where[] = '(pi.message LIKE :q_message OR pi.admin_response LIKE :q_response OR pp.title LIKE :q_package OR u.name LIKE :q_user OR u.email LIKE :q_email)';
            $params[':q_message'] = '%' . $search . '%';
            $params[':q_response'] = '%' . $search . '%';
            $params[':q_package'] = '%' . $search . '%';
            $params[':q_user'] = '%' . $search . '%';
            $params[':q_email'] = '%' . $search . '%';
        }

        $sql = $this->selectSql();
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY pi.created_at DESC, pi.id DESC LIMIT :limit_count';

        $stmt = $this->db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit_count', max(1, min($limit, 250)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function respond(int $id, string $status, string $adminResponse): bool
    {
        $stmt = $this->db()->prepare(
            'UPDATE private_inquiries
             SET status = :status,
                 admin_response = :admin_response
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'admin_response' => $adminResponse,
        ]);
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->db()->prepare('UPDATE private_inquiries SET status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function userById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function selectSql(): string
    {
        return "SELECT pi.*,
                       pp.title AS package_title,
                       pp.price AS package_price,
                       pp.duration AS package_duration,
                       pp.status AS package_status,
                       u.name AS user_name,
                       u.email AS user_email,
                       coach.name AS coach_name,
                       coach.email AS coach_email,
                       cp.user_id AS coach_user_id
                FROM private_inquiries pi
                JOIN private_packages pp ON pp.id = pi.private_package_id
                JOIN users u ON u.id = pi.user_id
                JOIN coach_profiles cp ON cp.id = pp.coach_profile_id
                JOIN users coach ON coach.id = cp.user_id";
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }
}
