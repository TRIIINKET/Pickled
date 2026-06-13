<?php

class AdminRepository {
    private $connection;

    public function __construct($connection) {
        $this->connection = $connection;
    }

    public function logAction($adminId, $action, $entityType, $entityId, $details = null) {
        try {
            $description = is_array($details) ? json_encode($details) : $details;
            $stmt = $this->connection->prepare("
                INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, description)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$adminId, $action, $entityType, $entityId, $description]);
        } catch (Exception $e) {
            error_log("AdminRepository::logAction - " . $e->getMessage());
            return false;
        }
    }

    public function getLogs($limit = 100) {
        try {
            $stmt = $this->connection->prepare("
                SELECT al.*, u.name, u.email FROM admin_logs al
                LEFT JOIN users u ON al.admin_id = u.id
                ORDER BY al.created_at DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminRepository::getLogs - " . $e->getMessage());
            return [];
        }
    }

    public function getLogsByAdmin($adminId, $limit = 50) {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM admin_logs WHERE admin_id = ? ORDER BY created_at DESC LIMIT ?
            ");
            $stmt->execute([$adminId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminRepository::getLogsByAdmin - " . $e->getMessage());
            return [];
        }
    }

    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Total users
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM users");
            $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Total bookings
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM bookings");
            $stats['total_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Revenue
            $stmt = $this->connection->query("SELECT SUM(total) as total FROM bookings WHERE LOWER(payment_status) IN ('completed', 'paid')");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_revenue'] = $result['total'] ?? 0;
            
            // Pending payments
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
            $stats['pending_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Total events
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM events");
            $stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Total courts
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM courts");
            $stats['total_courts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("AdminRepository::getDashboardStats - " . $e->getMessage());
            return [];
        }
    }

    public function getBookingStats() {
        try {
            $stmt = $this->connection->query("
                SELECT status, COUNT(*) as count FROM bookings GROUP BY status
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminRepository::getBookingStats - " . $e->getMessage());
            return [];
        }
    }

    public function getRevenueStats($period = 'day') {
        try {
            $dateFormat = match($period) {
                'week' => '%Y-W%v',
                'month' => '%Y-%m',
                default => '%Y-%m-%d'
            };
            
            $stmt = $this->connection->prepare("
                SELECT DATE_FORMAT(created_at, ?) as period, SUM(total) as revenue, COUNT(*) as bookings
                FROM bookings
                WHERE LOWER(payment_status) IN ('completed', 'paid')
                GROUP BY DATE_FORMAT(created_at, ?)
                ORDER BY period DESC
                LIMIT 30
            ");
            $stmt->execute([$dateFormat, $dateFormat]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AdminRepository::getRevenueStats - " . $e->getMessage());
            return [];
        }
    }
}
