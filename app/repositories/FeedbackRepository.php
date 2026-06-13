<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class FeedbackRepository
{
    public function __construct(private readonly ?PDO $connection = null) {}

    public function bookingForUser(int $bookingId, int $userId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT b.*, u.name AS user_name, u.email AS user_email
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             WHERE b.id = :booking_id
               AND b.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'user_id' => $userId,
        ]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        return $booking ?: null;
    }

    public function findByBookingId(int $bookingId): ?array
    {
        $stmt = $this->db()->prepare($this->feedbackSelect() . ' WHERE f.booking_id = :booking_id LIMIT 1');
        $stmt->execute(['booking_id' => $bookingId]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        return $feedback ?: null;
    }

    public function findByBookingIdForUser(int $bookingId, int $userId): ?array
    {
        $stmt = $this->db()->prepare(
            $this->feedbackSelect() . '
             WHERE f.booking_id = :booking_id
               AND f.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'user_id' => $userId,
        ]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        return $feedback ?: null;
    }

    public function targetsForBooking(int $bookingId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT bi.id AS booking_item_id,
                    bi.booking_id,
                    bi.name,
                    bi.court,
                    bi.category,
                    bi.booking_date,
                    bi.start_time,
                    bi.end_time,
                    s.coach_user_id,
                    coach.name AS coach_name,
                    coach.email AS coach_email
             FROM booking_items bi
             LEFT JOIN sessions s ON s.id = bi.session_id
             LEFT JOIN users coach ON coach.id = s.coach_user_id
             WHERE bi.booking_id = :booking_id
             ORDER BY bi.id ASC'
        );
        $stmt->execute(['booking_id' => $bookingId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function targetForBookingItem(int $bookingId, int $bookingItemId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT bi.id AS booking_item_id,
                    bi.booking_id,
                    bi.name,
                    bi.court,
                    bi.category,
                    bi.booking_date,
                    bi.start_time,
                    bi.end_time,
                    s.coach_user_id,
                    coach.name AS coach_name,
                    coach.email AS coach_email
             FROM booking_items bi
             LEFT JOIN sessions s ON s.id = bi.session_id
             LEFT JOIN users coach ON coach.id = s.coach_user_id
             WHERE bi.id = :booking_item_id
               AND bi.booking_id = :booking_id
             LIMIT 1'
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'booking_item_id' => $bookingItemId,
        ]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        return $target ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO feedback (booking_id, booking_item_id, user_id, coach_user_id, rating, comment)
             VALUES (:booking_id, :booking_item_id, :user_id, :coach_user_id, :rating, :comment)'
        );
        $stmt->execute([
            'booking_id' => (int) $data['booking_id'],
            'booking_item_id' => $data['booking_item_id'] === null ? null : (int) $data['booking_item_id'],
            'user_id' => (int) $data['user_id'],
            'coach_user_id' => $data['coach_user_id'] === null ? null : (int) $data['coach_user_id'],
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'],
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function updateForUser(int $feedbackId, int $userId, array $data): bool
    {
        $stmt = $this->db()->prepare(
            'UPDATE feedback
             SET booking_item_id = :booking_item_id,
                 coach_user_id = :coach_user_id,
                 rating = :rating,
                 comment = :comment
             WHERE id = :id
               AND user_id = :user_id'
        );

        return $stmt->execute([
            'id' => $feedbackId,
            'user_id' => $userId,
            'booking_item_id' => $data['booking_item_id'] === null ? null : (int) $data['booking_item_id'],
            'coach_user_id' => $data['coach_user_id'] === null ? null : (int) $data['coach_user_id'],
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'],
        ]);
    }

    public function all(?int $rating = null, string $search = '', int $limit = 100): array
    {
        $where = [];
        $params = [];

        if ($rating !== null) {
            $where[] = 'f.rating = :rating';
            $params['rating'] = $rating;
        }

        $search = trim($search);
        if ($search !== '') {
            $where[] = '(f.comment LIKE :search OR b.reference LIKE :search OR player.name LIKE :search OR coach.name LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql = $this->feedbackSelect();
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY f.created_at DESC, f.id DESC LIMIT :limit_count';

        $stmt = $this->db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit_count', max(1, min($limit, 250)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function forCoach(int $coachUserId, int $limit = 20): array
    {
        $stmt = $this->db()->prepare(
            $this->feedbackSelect() . '
             WHERE f.coach_user_id = :coach_user_id
             ORDER BY f.created_at DESC, f.id DESC
             LIMIT :limit_count'
        );
        $stmt->bindValue(':coach_user_id', $coachUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_count', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function statsForCoach(int $coachUserId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT COALESCE(AVG(rating), 0) AS average_rating,
                    COUNT(*) AS total_reviews
             FROM feedback
             WHERE coach_user_id = :coach_user_id'
        );
        $stmt->execute(['coach_user_id' => $coachUserId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'average_rating' => (float) ($stats['average_rating'] ?? 0),
            'total_reviews' => (int) ($stats['total_reviews'] ?? 0),
        ];
    }

    public function coachSummary(): array
    {
        $stmt = $this->db()->query(
            "SELECT u.id AS coach_user_id,
                    u.name AS coach_name,
                    u.email AS coach_email,
                    COALESCE(AVG(f.rating), 0) AS average_rating,
                    COUNT(f.id) AS total_reviews
             FROM users u
             LEFT JOIN feedback f ON f.coach_user_id = u.id
             WHERE u.role = 'coach'
             GROUP BY u.id, u.name, u.email
             ORDER BY average_rating DESC, total_reviews DESC, u.name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function platformStats(): array
    {
        $stmt = $this->db()->query(
            'SELECT COALESCE(AVG(rating), 0) AS average_rating,
                    COUNT(*) AS total_reviews
             FROM feedback'
        );
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'average_rating' => (float) ($stats['average_rating'] ?? 0),
            'total_reviews' => (int) ($stats['total_reviews'] ?? 0),
        ];
    }

    private function feedbackSelect(): string
    {
        return "SELECT f.*,
                       b.reference,
                       b.status AS booking_status,
                       player.name AS user_name,
                       player.email AS user_email,
                       coach.name AS coach_name,
                       coach.email AS coach_email,
                       bi.name AS booking_item_name,
                       bi.court,
                       bi.category,
                       bi.booking_date,
                       CONCAT(TIME_FORMAT(bi.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(bi.end_time, '%h:%i %p')) AS booking_time
                FROM feedback f
                JOIN bookings b ON b.id = f.booking_id
                JOIN users player ON player.id = f.user_id
                LEFT JOIN users coach ON coach.id = f.coach_user_id
                LEFT JOIN booking_items bi ON bi.id = f.booking_item_id";
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }
}
