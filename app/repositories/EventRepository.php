<?php

class EventRepository {
    private $connection;

    public function __construct($connection) {
        $this->connection = $connection;
    }

    public function create($title, $description, $eventDate, $eventTime, $location, $maxParticipants, $createdBy) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO events (title, description, event_date, event_time, location, max_participants, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $eventDate, $eventTime, $location, $maxParticipants, $createdBy]);
            return $this->connection->lastInsertId();
        } catch (Exception $e) {
            error_log("EventRepository::create - " . $e->getMessage());
            return false;
        }
    }

    public function findById($id) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("EventRepository::findById - " . $e->getMessage());
            return null;
        }
    }

    public function findAll($limit = 50, $offset = 0) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM events ORDER BY event_date DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("EventRepository::findAll - " . $e->getMessage());
            return [];
        }
    }

    public function findByStatus($status) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM events WHERE status = ? ORDER BY event_date DESC");
            $stmt->execute([$status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("EventRepository::findByStatus - " . $e->getMessage());
            return [];
        }
    }

    public function update($id, $title, $description, $eventDate, $eventTime, $location, $maxParticipants, $status) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE events 
                SET title = ?, description = ?, event_date = ?, event_time = ?, 
                    location = ?, max_participants = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$title, $description, $eventDate, $eventTime, $location, $maxParticipants, $status, $id]);
        } catch (Exception $e) {
            error_log("EventRepository::update - " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM events WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("EventRepository::delete - " . $e->getMessage());
            return false;
        }
    }

    public function updateParticipantCount($id, $count) {
        try {
            $stmt = $this->connection->prepare("UPDATE events SET current_participants = ? WHERE id = ?");
            return $stmt->execute([$count, $id]);
        } catch (Exception $e) {
            error_log("EventRepository::updateParticipantCount - " . $e->getMessage());
            return false;
        }
    }

    public function getTotalCount() {
        try {
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM events");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("EventRepository::getTotalCount - " . $e->getMessage());
            return 0;
        }
    }
}
