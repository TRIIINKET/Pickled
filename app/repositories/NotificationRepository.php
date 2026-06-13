<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class NotificationRepository
{
    private const TYPES = [
        'info',
        'success',
        'warning',
        'error',
        'booking_created',
        'booking_confirmed',
        'booking_cancelled',
        'booking_expired',
        'payment_uploaded',
        'payment_approved',
        'payment_rejected',
        'session_updated',
    ];

    private ?bool $hasLinkColumn = null;

    public function __construct(private readonly ?PDO $connection = null) {}

    public function create(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $title = trim($title);
        $message = trim($message);
        if ($title === '' || $message === '') {
            return 0;
        }

        $type = $this->normalizeType($type);
        $params = [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ];

        if ($this->hasLinkColumn()) {
            $stmt = $this->db()->prepare(
                'INSERT INTO notifications (user_id, title, message, type, link)
                 VALUES (:user_id, :title, :message, :type, :link)'
            );
            $params['link'] = $this->normalizeLink($link);
        } else {
            $stmt = $this->db()->prepare(
                'INSERT INTO notifications (user_id, title, message, type)
                 VALUES (:user_id, :title, :message, :type)'
            );
        }

        $stmt->execute($params);

        return (int) $this->db()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM notifications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        return $notification ?: null;
    }

    public function findByUserId(int $userId, int $limit = 50): array
    {
        $stmt = $this->db()->prepare(
            'SELECT *
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit_count'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_count', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findUnreadByUserId(int $userId, int $limit = 50): array
    {
        $stmt = $this->db()->prepare(
            'SELECT *
             FROM notifications
             WHERE user_id = :user_id
               AND is_read = 0
             ORDER BY created_at DESC, id DESC
             LIMIT :limit_count'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_count', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAll(int $limit = 100): array
    {
        $stmt = $this->db()->prepare(
            'SELECT n.*, u.name AS user_name, u.email AS user_email, u.role AS user_role
             FROM notifications n
             JOIN users u ON u.id = n.user_id
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT :limit_count'
        );
        $stmt->bindValue(':limit_count', max(1, min($limit, 200)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markAsRead(int $id, ?int $userId = null): bool
    {
        $sql = 'UPDATE notifications SET is_read = 1 WHERE id = :id';
        $params = ['id' => $id];
        if ($userId !== null) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $stmt = $this->db()->prepare($sql);
        return $stmt->execute($params);
    }

    public function markUserNotificationsAsRead(int $userId): bool
    {
        $stmt = $this->db()->prepare(
            'UPDATE notifications
             SET is_read = 1
             WHERE user_id = :user_id
               AND is_read = 0'
        );
        return $stmt->execute(['user_id' => $userId]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare('DELETE FROM notifications WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*)
             FROM notifications
             WHERE user_id = :user_id
               AND is_read = 0'
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function usersByRole(string $role): array
    {
        $stmt = $this->db()->prepare('SELECT id, name, email, role FROM users WHERE role = :role ORDER BY name ASC');
        $stmt->execute(['role' => $role]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, self::TYPES, true) ? $type : 'info';
    }

    private function normalizeLink(?string $link): ?string
    {
        $link = trim((string) $link);
        return $link === '' ? null : substr($link, 0, 255);
    }

    private function hasLinkColumn(): bool
    {
        if ($this->hasLinkColumn !== null) {
            return $this->hasLinkColumn;
        }

        try {
            $stmt = $this->db()->query("SHOW COLUMNS FROM notifications LIKE 'link'");
            $this->hasLinkColumn = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            $this->hasLinkColumn = false;
        }

        return $this->hasLinkColumn;
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }
}
