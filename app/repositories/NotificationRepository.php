<?php

class NotificationRepository {
    private $connection;

    public function __construct($connection) {
        $this->connection = $connection;
    }

    public function create($userId, $title, $message, $type = 'info', $link = null) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO notifications (user_id, title, message, type, link)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $message, $type, $link]);
            return $this->connection->lastInsertId();
        } catch (Exception $e) {
            error_log("NotificationRepository::create - " . $e->getMessage());
            return false;
        }
    }

    public function findById($id) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM notifications WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("NotificationRepository::findById - " . $e->getMessage());
            return null;
        }
    }

    public function findByUserId($userId, $limit = 50) {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("NotificationRepository::findByUserId - " . $e->getMessage());
            return [];
        }
    }

    public function findUnreadByUserId($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("NotificationRepository::findUnreadByUserId - " . $e->getMessage());
            return [];
        }
    }

    public function findAll($limit = 100) {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("NotificationRepository::findAll - " . $e->getMessage());
            return [];
        }
    }

    public function markAsRead($id) {
        try {
            $stmt = $this->connection->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("NotificationRepository::markAsRead - " . $e->getMessage());
            return false;
        }
    }

    public function markUserNotificationsAsRead($userId) {
        try {
            $stmt = $this->connection->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("NotificationRepository::markUserNotificationsAsRead - " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM notifications WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("NotificationRepository::delete - " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount($userId) {
        try {
            $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("NotificationRepository::getUnreadCount - " . $e->getMessage());
            return 0;
        }
    }
}
?>
