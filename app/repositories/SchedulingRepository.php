<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/Database.php';

final class SchedulingRepository
{
    public function coaches(bool $activeOnly = true): array
    {
        $sql = "SELECT u.id, u.name, u.email, cp.specialization, cp.bio, cp.experience, cp.status
                FROM users u
                LEFT JOIN coach_profiles cp ON cp.user_id = u.id
                WHERE u.role = 'coach'";
        if ($activeOnly) {
            $sql .= " AND (cp.status IS NULL OR cp.status = 'active')";
        }
        $sql .= ' ORDER BY u.name ASC';

        return Database::connection()->query($sql)->fetchAll() ?: [];
    }

    public function coachById(int $coachUserId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT u.id, u.name, u.email, cp.specialization, cp.bio, cp.experience, cp.status
             FROM users u
             LEFT JOIN coach_profiles cp ON cp.user_id = u.id
             WHERE u.id = :id AND u.role = 'coach'
             LIMIT 1"
        );
        $stmt->execute(['id' => $coachUserId]);
        $coach = $stmt->fetch();
        return $coach ?: null;
    }

    public function createSession(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO sessions
                (variant_id, coach_user_id, session_date, start_time, end_time, capacity, booked_count, status)
             VALUES
                (:variant_id, :coach_user_id, :session_date, :start_time, :end_time, :capacity, :booked_count, :status)'
        );
        $stmt->execute($this->sessionParams($data));
        return (int) Database::connection()->lastInsertId();
    }

    public function updateSession(int $id, array $data): bool
    {
        $params = $this->sessionParams($data) + ['id' => $id];
        $stmt = Database::connection()->prepare(
            'UPDATE sessions
             SET variant_id = :variant_id,
                 coach_user_id = :coach_user_id,
                 session_date = :session_date,
                 start_time = :start_time,
                 end_time = :end_time,
                 capacity = :capacity,
                 booked_count = :booked_count,
                 status = :status
             WHERE id = :id'
        );
        return $stmt->execute($params);
    }

    public function setSessionStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE sessions SET status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function sessionById(int $id, bool $forUpdate = false): ?array
    {
        $sql = $this->sessionSelect() . ' WHERE s.id = :id LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $session = $stmt->fetch();
        return $session ? $this->withDisplayFields($session) : null;
    }

    public function sessionBySlot(int $variantId, string $sessionDate, string $startTime, string $endTime): ?array
    {
        $stmt = Database::connection()->prepare(
            $this->sessionSelect() . '
             WHERE s.variant_id = :variant_id
               AND s.session_date = :session_date
               AND s.start_time = :start_time
               AND s.end_time = :end_time
             LIMIT 1'
        );
        $stmt->execute([
            'variant_id' => $variantId,
            'session_date' => $sessionDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        $session = $stmt->fetch();
        return $session ? $this->withDisplayFields($session) : null;
    }

    public function findOrCreateSession(int $variantId, string $sessionDate, string $startTime, string $endTime, int $capacity): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO sessions (variant_id, session_date, start_time, end_time, capacity, status)
             VALUES (:variant_id, :session_date, :start_time, :end_time, :capacity, :status)
             ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'variant_id' => $variantId,
            'session_date' => $sessionDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'capacity' => $capacity,
            'status' => 'open',
        ]);

        return $this->sessionBySlot($variantId, $sessionDate, $startTime, $endTime) ?? [];
    }

    public function sessionsForVariantMonth(int $variantId, int $year, int $month, bool $includeDisabled = false): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = (new DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');

        $sql = $this->sessionSelect() . '
                WHERE s.variant_id = :variant_id
                  AND s.session_date BETWEEN :start_date AND :end_date';
        if (!$includeDisabled) {
            $sql .= " AND s.status = 'open'";
        }
        $sql .= ' ORDER BY s.session_date ASC, s.start_time ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'variant_id' => $variantId,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        return array_map([$this, 'withDisplayFields'], $stmt->fetchAll() ?: []);
    }

    public function sessionsBetween(?int $coachUserId, string $startDate, string $endDate, bool $includeDisabled = false): array
    {
        $sql = $this->sessionSelect() . '
                WHERE s.session_date BETWEEN :start_date AND :end_date';
        $params = ['start_date' => $startDate, 'end_date' => $endDate];
        if ($coachUserId) {
            $sql .= ' AND s.coach_user_id = :coach_user_id';
            $params['coach_user_id'] = $coachUserId;
        }
        if (!$includeDisabled) {
            $sql .= " AND s.status <> 'cancelled'";
        }
        $sql .= ' ORDER BY s.session_date ASC, s.start_time ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'withDisplayFields'], $stmt->fetchAll() ?: []);
    }

    public function allSessions(bool $includeDisabled = true, int $limit = 120): array
    {
        $sql = $this->sessionSelect();
        if (!$includeDisabled) {
            $sql .= " WHERE s.status <> 'cancelled'";
        }
        $sql .= ' ORDER BY s.session_date DESC, s.start_time DESC LIMIT :limit';

        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'withDisplayFields'], $stmt->fetchAll() ?: []);
    }

    public function coachSessionOverlap(int $coachUserId, string $sessionDate, string $startTime, string $endTime, ?int $ignoreSessionId = null): bool
    {
        $sql = "SELECT 1
                FROM sessions
                WHERE coach_user_id = :coach_user_id
                  AND session_date = :session_date
                  AND status IN ('open', 'full')
                  AND :start_time < end_time
                  AND :end_time > start_time";
        $params = [
            'coach_user_id' => $coachUserId,
            'session_date' => $sessionDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        if ($ignoreSessionId) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreSessionId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function coachAvailableForSlot(int $coachUserId, int $dayOfWeek, string $startTime, string $endTime): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1
             FROM coach_availability
             WHERE coach_user_id = :coach_user_id
               AND day_of_week = :day_of_week
               AND status = 'available'
               AND start_time <= :start_time
               AND end_time >= :end_time
             LIMIT 1"
        );
        $stmt->execute([
            'coach_user_id' => $coachUserId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function availableCoachesForSlot(int $dayOfWeek, string $startTime, string $endTime, ?string $sessionDate = null): array
    {
        $sql = "SELECT u.id, u.name, u.email, cp.specialization, cp.bio, cp.experience
                FROM users u
                JOIN coach_availability ca ON ca.coach_user_id = u.id
                LEFT JOIN coach_profiles cp ON cp.user_id = u.id
                WHERE u.role = 'coach'
                  AND ca.day_of_week = :day_of_week
                  AND ca.status = 'available'
                  AND ca.start_time <= :start_time
                  AND ca.end_time >= :end_time";
        $params = [
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        if ($sessionDate !== null) {
            $sql .= " AND NOT EXISTS (
                        SELECT 1
                        FROM sessions s
                        WHERE s.coach_user_id = u.id
                          AND s.session_date = :session_date
                          AND s.status IN ('open', 'full')
                          AND :conflict_start_time < s.end_time
                          AND :conflict_end_time > s.start_time
                      )";
            $params['session_date'] = $sessionDate;
            $params['conflict_start_time'] = $startTime;
            $params['conflict_end_time'] = $endTime;
        }
        $sql .= ' ORDER BY u.name ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function createAvailability(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO coach_availability (coach_user_id, day_of_week, start_time, end_time, status)
             VALUES (:coach_user_id, :day_of_week, :start_time, :end_time, :status)'
        );
        $stmt->execute($this->availabilityParams($data));
        return (int) Database::connection()->lastInsertId();
    }

    public function updateAvailability(int $id, array $data): bool
    {
        $params = $this->availabilityParams($data) + ['id' => $id];
        $stmt = Database::connection()->prepare(
            'UPDATE coach_availability
             SET coach_user_id = :coach_user_id,
                 day_of_week = :day_of_week,
                 start_time = :start_time,
                 end_time = :end_time,
                 status = :status
             WHERE id = :id'
        );
        return $stmt->execute($params);
    }

    public function setAvailabilityStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE coach_availability SET status = :status WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function availabilityById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ca.*, u.name AS coach_name, u.email AS coach_email
             FROM coach_availability ca
             JOIN users u ON u.id = ca.coach_user_id
             WHERE ca.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->withAvailabilityDisplayFields($row) : null;
    }

    public function availabilityForCoach(int $coachUserId, bool $includeUnavailable = true): array
    {
        $sql = 'SELECT ca.*, u.name AS coach_name, u.email AS coach_email
                FROM coach_availability ca
                JOIN users u ON u.id = ca.coach_user_id
                WHERE ca.coach_user_id = :coach_user_id';
        if (!$includeUnavailable) {
            $sql .= " AND ca.status = 'available'";
        }
        $sql .= ' ORDER BY ca.day_of_week ASC, ca.start_time ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['coach_user_id' => $coachUserId]);
        return array_map([$this, 'withAvailabilityDisplayFields'], $stmt->fetchAll() ?: []);
    }

    public function allAvailability(bool $includeUnavailable = true): array
    {
        $sql = 'SELECT ca.*, u.name AS coach_name, u.email AS coach_email
                FROM coach_availability ca
                JOIN users u ON u.id = ca.coach_user_id';
        if (!$includeUnavailable) {
            $sql .= " WHERE ca.status = 'available'";
        }
        $sql .= ' ORDER BY u.name ASC, ca.day_of_week ASC, ca.start_time ASC';

        return array_map([$this, 'withAvailabilityDisplayFields'], Database::connection()->query($sql)->fetchAll() ?: []);
    }

    public function availabilityOverlap(int $coachUserId, int $dayOfWeek, string $startTime, string $endTime, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1
                FROM coach_availability
                WHERE coach_user_id = :coach_user_id
                  AND day_of_week = :day_of_week
                  AND :start_time < end_time
                  AND :end_time > start_time';
        $params = [
            'coach_user_id' => $coachUserId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        if ($ignoreId) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function incrementBookedCount(int $sessionId, int $quantity): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE sessions
             SET booked_count = booked_count + :quantity,
                 status = CASE WHEN booked_count + :status_quantity >= capacity THEN 'full' ELSE status END
             WHERE id = :id
               AND status IN ('open', 'full')
               AND booked_count + :capacity_quantity <= capacity"
        );
        $stmt->execute([
            'id' => $sessionId,
            'quantity' => $quantity,
            'status_quantity' => $quantity,
            'capacity_quantity' => $quantity,
        ]);
        return $stmt->rowCount() === 1;
    }

    private function sessionSelect(): string
    {
        return "SELECT s.*,
                       v.slug AS variant_slug,
                       v.name,
                       v.category,
                       v.duration_label,
                       v.price,
                       v.participants_limit,
                       v.image,
                       c.name AS court,
                       c.name AS court_name,
                       c.slug AS court_slug,
                       u.name AS coach_name,
                       u.email AS coach_email
                FROM sessions s
                JOIN booking_variants v ON v.id = s.variant_id
                JOIN courts c ON c.id = v.court_id
                LEFT JOIN users u ON u.id = s.coach_user_id";
    }

    private function sessionParams(array $data): array
    {
        return [
            'variant_id' => (int) $data['variant_id'],
            'coach_user_id' => empty($data['coach_user_id']) ? null : (int) $data['coach_user_id'],
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'capacity' => (int) $data['capacity'],
            'booked_count' => (int) ($data['booked_count'] ?? 0),
            'status' => $data['status'],
        ];
    }

    private function availabilityParams(array $data): array
    {
        return [
            'coach_user_id' => (int) $data['coach_user_id'],
            'day_of_week' => (int) $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
        ];
    }

    private function withDisplayFields(array $row): array
    {
        $row['display_date'] = $this->displayDate((string) $row['session_date']);
        $row['session_time'] = $this->displayTimeRange((string) $row['start_time'], (string) $row['end_time']);
        $row['time_range'] = $row['session_time'];
        return $row;
    }

    private function withAvailabilityDisplayFields(array $row): array
    {
        $row['time_range'] = $this->displayTimeRange((string) $row['start_time'], (string) $row['end_time']);
        $row['day_label'] = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][(int) $row['day_of_week']] ?? 'Day';
        return $row;
    }

    private function displayDate(string $date): string
    {
        return (new DateTimeImmutable($date))->format('l, F j, Y');
    }

    private function displayTimeRange(string $start, string $end): string
    {
        $startLabel = (new DateTimeImmutable('1970-01-01 ' . $start))->format('h:i A');
        $endLabel = (new DateTimeImmutable('1970-01-01 ' . $end))->format('h:i A');
        return $startLabel . ' - ' . $endLabel;
    }
}
