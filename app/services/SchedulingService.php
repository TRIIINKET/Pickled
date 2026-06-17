<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/SchedulingRepository.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/AdminLogService.php';
require_once __DIR__ . '/../../includes/validation.php';

final class SchedulingService
{
    private const SESSION_STATUSES = ['open', 'full', 'cancelled', 'completed'];
    private const AVAILABILITY_STATUSES = ['available', 'unavailable', 'leave'];
    private const TIME_OFF_STATUSES = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
    private const TIME_OFF_REASONS = [
        'Vacation',
        'Personal Leave',
        'Medical Appointment',
        'Family Commitment',
        'Tournament Participation',
        'Other',
    ];

    public function __construct(
        private readonly SchedulingRepository $schedules = new SchedulingRepository(),
        private readonly NotificationService $notifications = new NotificationService(),
        private readonly AdminLogService $adminLogs = new AdminLogService()
    ) {}

    public function coaches(bool $activeOnly = true): array
    {
        return $this->schedules->coaches($activeOnly);
    }

    public function createSession(array $input, ?int $adminId = null): int
    {
        $data = $this->sessionData($input);
        $this->assertSessionCanBeSaved($data, null);
        $sessionId = $this->schedules->createSession($data);
        $session = $this->schedules->sessionById($sessionId);
        if ($session) {
            $this->adminLogs->recordSessionCreated($this->adminId($input, $adminId), $session);
            $this->notifications->notifySessionUpdated($session, 'assigned');
        }

        return $sessionId;
    }

    public function updateSession(int $id, array $input, ?int $adminId = null): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Session is required.');
        }
        $data = $this->sessionData($input);
        $this->assertSessionCanBeSaved($data, $id);
        $updated = $this->schedules->updateSession($id, $data);
        if ($updated) {
            $session = $this->schedules->sessionById($id);
            if ($session) {
                if (($session['status'] ?? '') === 'cancelled') {
                    $this->adminLogs->recordSessionCancelled($this->adminId($input, $adminId), $session);
                } else {
                    $this->adminLogs->recordSessionUpdated($this->adminId($input, $adminId), $session);
                }
                $this->notifications->notifySessionUpdated($session, 'updated');
            }
        }

        return $updated;
    }

    public function setSessionStatus(int $id, string $status, ?int $adminId = null): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Session is required.');
        }
        $status = $this->sessionStatus($status);
        $updated = $this->schedules->setSessionStatus($id, $status);
        if ($updated) {
            $session = $this->schedules->sessionById($id);
            if ($session) {
                if ($status === 'cancelled') {
                    $this->adminLogs->recordSessionCancelled((int) ($adminId ?? 0), $session);
                } else {
                    $this->adminLogs->recordSessionUpdated((int) ($adminId ?? 0), $session);
                }
                $this->notifications->notifySessionUpdated($session, 'updated');
            }
        }

        return $updated;
    }

    public function sessionById(int $id, bool $forUpdate = false): ?array
    {
        return $this->schedules->sessionById($id, $forUpdate);
    }

    public function findOrCreateSession(int $variantId, string $date, string $time, int $capacity, ?int $coachUserId = null): array
    {
        $sessionDate = validateDate($date, false);
        [$start, $end] = $this->timeRange($time, $sessionDate);
        return $this->schedules->findOrCreateSession($variantId, $sessionDate, $start, $end, max(1, $capacity), $coachUserId);
    }

    public function sessionsForVariantMonth(int $variantId, int $year, int $month, bool $includeDisabled = false): array
    {
        return $this->schedules->sessionsForVariantMonth($variantId, $year, $month, $includeDisabled);
    }

    public function sessionsBetween(?int $coachUserId, string $startDate, string $endDate, bool $includeDisabled = false): array
    {
        return $this->schedules->sessionsBetween($coachUserId, $startDate, $endDate, $includeDisabled);
    }

    public function allSessions(bool $includeDisabled = true, int $limit = 120): array
    {
        return $this->schedules->allSessions($includeDisabled, $limit);
    }

    public function createAvailability(array $input): int
    {
        $data = $this->availabilityData($input);
        $this->assertAvailabilityCanBeSaved($data, null);
        return $this->schedules->createAvailability($data);
    }

    public function updateAvailability(int $id, array $input): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Availability record is required.');
        }
        $data = $this->availabilityData($input);
        $this->assertAvailabilityCanBeSaved($data, $id);
        return $this->schedules->updateAvailability($id, $data);
    }

    public function setAvailabilityStatus(int $id, string $status): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Availability record is required.');
        }
        return $this->schedules->setAvailabilityStatus($id, $this->availabilityStatus($status));
    }

    public function availabilityForCoach(int $coachUserId, bool $includeUnavailable = true): array
    {
        return $this->schedules->availabilityForCoach($coachUserId, $includeUnavailable);
    }

    public function createTimeOffRequest(array $input): int
    {
        $data = $this->timeOffData($input);
        $data['status'] = 'pending';
        if ($this->schedules->coachHasApprovedTimeOff((int) $data['coach_user_id'], (string) $data['start_date'], (string) $data['end_date'])) {
            throw new RuntimeException('This request overlaps an approved time-off period.');
        }
        return $this->schedules->createTimeOffRequest($data);
    }

    public function updateTimeOffRequest(int $id, array $input): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Time off request is required.');
        }
        $data = $this->timeOffData($input);
        $data['status'] = 'pending';
        if ($this->schedules->coachHasApprovedTimeOff((int) $data['coach_user_id'], (string) $data['start_date'], (string) $data['end_date'], $id)) {
            throw new RuntimeException('This request overlaps an approved time-off period.');
        }
        return $this->schedules->updateTimeOffRequest($id, $data);
    }

    public function cancelTimeOffRequest(int $id, int $coachUserId): bool
    {
        if ($id <= 0 || $coachUserId <= 0) {
            throw new RuntimeException('Time off request is required.');
        }
        return $this->schedules->cancelTimeOffRequest($id, $coachUserId);
    }

    public function timeOffRequestsForCoach(int $coachUserId): array
    {
        return $coachUserId > 0 ? $this->schedules->timeOffRequestsForCoach($coachUserId) : [];
    }

    public function allAvailability(bool $includeUnavailable = true): array
    {
        return $this->schedules->allAvailability($includeUnavailable);
    }

    public function availableCoachesForSlot(string $date, string $time): array
    {
        $sessionDate = $this->date($date);
        [$start, $end] = $this->timeRange($time);
        $dayOfWeek = (int) (new DateTimeImmutable($sessionDate))->format('w');
        return $this->schedules->availableCoachesForSlot($dayOfWeek, $start, $end, $sessionDate);
    }

    public function incrementBookedCount(int $sessionId, int $quantity): bool
    {
        return $this->schedules->incrementBookedCount($sessionId, $quantity);
    }

    public function displayTimeRange(string $start, string $end): string
    {
        return (new DateTimeImmutable('1970-01-01 ' . $start))->format('h:i A')
            . ' - '
            . (new DateTimeImmutable('1970-01-01 ' . $end))->format('h:i A');
    }

    private function sessionData(array $input): array
    {
        $variantId = (int) ($input['variant_id'] ?? 0);
        $sessionDate = validateDate($input['session_date'] ?? '', false);
        [$start, $end] = validateTime($input['start_time'] ?? '', $input['end_time'] ?? '', $sessionDate);
        $capacity = validatePositiveInt($input['capacity'] ?? 0, null, 'Please enter a valid number of players.');
        $booked = (int) ($input['booked_count'] ?? 0);

        if ($variantId <= 0 || $capacity <= 0) {
            throw new RuntimeException('Variant and capacity are required.');
        }
        if ($start >= $end) {
            throw new RuntimeException('Session start time must be before end time.');
        }
        if ($booked < 0 || $booked > $capacity) {
            throw new RuntimeException('Booked count cannot be greater than capacity.');
        }

        return [
            'variant_id' => $variantId,
            'coach_user_id' => empty($input['coach_user_id']) ? null : (int) $input['coach_user_id'],
            'session_date' => $sessionDate,
            'start_time' => $start,
            'end_time' => $end,
            'capacity' => $capacity,
            'booked_count' => $booked,
            'status' => $this->sessionStatus((string) ($input['status'] ?? 'open')),
        ];
    }

    private function availabilityData(array $input): array
    {
        $coachUserId = (int) ($input['coach_user_id'] ?? 0);
        $dayOfWeek = (int) ($input['day_of_week'] ?? -1);
        [$start, $end] = validateTime($input['start_time'] ?? '', $input['end_time'] ?? '');

        if ($coachUserId <= 0 || !$this->schedules->coachById($coachUserId)) {
            throw new RuntimeException('A valid coach is required.');
        }
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new RuntimeException('Day of week must be between 0 and 6.');
        }
        if ($start >= $end) {
            throw new RuntimeException('Availability start time must be before end time.');
        }

        return [
            'coach_user_id' => $coachUserId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $this->availabilityStatus((string) ($input['status'] ?? 'available')),
        ];
    }

    private function assertSessionCanBeSaved(array $data, ?int $ignoreSessionId): void
    {
        $coachUserId = $data['coach_user_id'] ?? null;
        if (!$coachUserId || $data['status'] === 'cancelled') {
            return;
        }
        if (!$this->schedules->coachById((int) $coachUserId)) {
            throw new RuntimeException('Assigned coach does not exist.');
        }

        $dayOfWeek = (int) (new DateTimeImmutable((string) $data['session_date']))->format('w');
        if (!$this->schedules->coachAvailableForDatedSlot((int) $coachUserId, $dayOfWeek, (string) $data['start_time'], (string) $data['end_time'], (string) $data['session_date'])) {
            throw new RuntimeException('Assigned coach is not available for that schedule.');
        }
        if ($this->schedules->coachSessionOverlap((int) $coachUserId, (string) $data['session_date'], (string) $data['start_time'], (string) $data['end_time'], $ignoreSessionId)) {
            throw new RuntimeException('Assigned coach already has a session during that time.');
        }
    }

    private function assertAvailabilityCanBeSaved(array $data, ?int $ignoreId): void
    {
        if ($this->schedules->availabilityOverlap(
            (int) $data['coach_user_id'],
            (int) $data['day_of_week'],
            (string) $data['start_time'],
            (string) $data['end_time'],
            $ignoreId
        )) {
            throw new RuntimeException('Overlapping coach availability is not allowed.');
        }
    }

    private function date(string $value): string
    {
        return validateDate($value, true);
    }

    private function timeRange(string $value, ?string $date = null): array
    {
        $parts = preg_split('/\s*-\s*/', trim($value));
        if (!$parts || count($parts) !== 2) {
            throw new RuntimeException('Time range is invalid.');
        }
        return validateTime($parts[0], $parts[1], $date);
    }

    private function time(string $value): string
    {
        return validateTime($value);
    }

    private function sessionStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === 'disabled') {
            $status = 'cancelled';
        }
        return in_array($status, self::SESSION_STATUSES, true) ? $status : 'open';
    }

    private function availabilityStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === 'disabled') {
            $status = 'unavailable';
        }
        return in_array($status, self::AVAILABILITY_STATUSES, true) ? $status : 'available';
    }

    private function timeOffData(array $input): array
    {
        $coachUserId = (int) ($input['coach_user_id'] ?? 0);
        $startDate = validateDate($input['start_date'] ?? '', false);
        $endDate = validateDate($input['end_date'] ?? '', false);
        $reason = trim((string) ($input['reason'] ?? ''));
        $notes = validateText($input['notes'] ?? '', false, 1000);
        $status = strtolower(trim((string) ($input['status'] ?? 'pending')));

        if ($coachUserId <= 0 || !$this->schedules->coachById($coachUserId)) {
            throw new RuntimeException('A valid coach is required.');
        }
        if ($endDate < $startDate) {
            throw new RuntimeException('End date cannot be before start date.');
        }
        if (!in_array($reason, self::TIME_OFF_REASONS, true)) {
            throw new RuntimeException('Choose a valid time off reason.');
        }

        return [
            'coach_user_id' => $coachUserId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'notes' => $notes !== '' ? substr($notes, 0, 1000) : null,
            'status' => in_array($status, self::TIME_OFF_STATUSES, true) ? $status : 'pending',
        ];
    }

    private function adminId(array $input, ?int $adminId): int
    {
        return max(0, (int) ($adminId ?? $input['admin_id'] ?? 0));
    }
}
