<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class AdminLogRepository
{
    public function __construct(private readonly ?PDO $connection = null) {}

    public function create(int $adminId, string $action, string $entityType, ?int $entityId = null, ?string $description = null): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, description)
             VALUES (:admin_id, :action, :entity_type, :entity_id, :description)'
        );
        $stmt->execute([
            'admin_id' => $adminId,
            'action' => $this->normalizeToken($action, 80),
            'entity_type' => $this->normalizeToken($entityType, 80),
            'entity_id' => $entityId && $entityId > 0 ? $entityId : null,
            'description' => $this->normalizeDescription($description),
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function findAll(array $filters = [], int $limit = 100, string $sort = 'desc'): array
    {
        [$whereSql, $params] = $this->filters($filters);
        $sortSql = strtolower($sort) === 'asc' ? 'ASC' : 'DESC';

        $stmt = $this->db()->prepare(
            "SELECT al.*, u.name AS admin_name, u.email AS admin_email, u.role AS admin_role
             FROM admin_logs al
             JOIN users u ON u.id = al.admin_id
             $whereSql
             ORDER BY al.created_at $sortSql, al.id $sortSql
             LIMIT :limit_count"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit_count', max(1, min($limit, 500)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByAdminId(int $adminId, int $limit = 50, string $sort = 'desc'): array
    {
        return $this->findAll(['admin_id' => $adminId], $limit, $sort);
    }

    public function actionOptions(): array
    {
        $stmt = $this->db()->query('SELECT DISTINCT action FROM admin_logs ORDER BY action ASC');
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function entityTypeOptions(): array
    {
        $stmt = $this->db()->query('SELECT DISTINCT entity_type FROM admin_logs ORDER BY entity_type ASC');
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function firstAdminId(): ?int
    {
        $stmt = $this->db()->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    private function filters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['admin_id'])) {
            $where[] = 'al.admin_id = :admin_id';
            $params[':admin_id'] = (int) $filters['admin_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'al.action = :action';
            $params[':action'] = $this->normalizeToken((string) $filters['action'], 80);
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'al.entity_type = :entity_type';
            $params[':entity_type'] = $this->normalizeToken((string) $filters['entity_type'], 80);
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'al.created_at >= :date_from';
            $params[':date_from'] = (string) $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'al.created_at <= :date_to';
            $params[':date_to'] = (string) $filters['date_to'] . ' 23:59:59';
        }

        $search = trim((string) ($filters['q'] ?? $filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(al.action LIKE :q_action OR al.entity_type LIKE :q_entity OR al.description LIKE :q_description OR u.name LIKE :q_name OR u.email LIKE :q_email)';
            $params[':q_action'] = '%' . $search . '%';
            $params[':q_entity'] = '%' . $search . '%';
            $params[':q_description'] = '%' . $search . '%';
            $params[':q_name'] = '%' . $search . '%';
            $params[':q_email'] = '%' . $search . '%';
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function normalizeToken(string $value, int $maxLength): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\\-]+/', '_', $value) ?? '';
        return substr(trim($value, '_-'), 0, $maxLength) ?: 'unknown';
    }

    private function normalizeDescription(?string $description): ?string
    {
        $description = trim((string) $description);
        return $description === '' ? null : $description;
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }
}
