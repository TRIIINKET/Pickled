<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/SchedulingRepository.php';

final class SchedulingService
{
    private const SESSION_STATUSES = ['open', 'full', 'cancelled', 'completed'];
    private const AVAILABILITY_STATUSES = ['available', 'unavailable', 'leave'];

    public function __construct(private readonly SchedulingRepository $schedules = new SchedulingRepository()) {}

    public function coaches(bool $activeOnly = true): array
    {
        return $this->schedules->coaches($activeOnly);
    }

    public function createSession(array $input): int
    {
        $data = $this->sessionData($input);
        $this->assertSessionCanBeSaved($data, null);
        return $this->schedules->createSession($data);
    }

    public function updateSession(int $id, array $input): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Session is required.');
        }
        $data = $this->sessionData($input);
        $this->assertSessionCanBeSaved($data, $id);
        return $this->schedules->updateSession($id, $data);
    }

    public function setSessionStatus(int $id, string $status): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Session is required.');
        }
        $status = $this->sessionStatus($status);
        return $this->schedules->setSessionStatus($id, $status);
    }

    public function sessionById(int $id, bool $forUpdate = false): ?array
    {
        return $this->schedules->sessionById($id, $forUpdate);
    }

    public function findOrCreateSession(int $variantId, string $date, string $time, int $capacity): array
    {
        $sessionDate = $this->date($date);
        [$start, $end] = $this->timeRange($time);
        return $this->schedules->findOrCreateSession($variantId, $sessionDate, $start, $end, max(1, $capacity));
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
        $sessionDate = $this->date((string) ($input['session_date'] ?? ''));
        $start = $this->time((string) ($input['start_time'] ?? ''));
        $end = $this->time((string) ($input['end_time'] ?? ''));
        $capacity = (int) ($input['capacity'] ?? 0);
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
        $start = $this->time((string) ($input['start_time'] ?? ''));
        $end = $this->time((string) ($input['end_time'] ?? ''));

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
        if (!$this->schedules->coachAvailableForSlot((int) $coachUserId, $dayOfWeek, (string) $data['start_time'], (string) $data['end_time'])) {
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
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException('Date is required.');
        }
        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (Throwable) {
            throw new RuntimeException('Date is invalid.');
        }
    }

    private function timeRange(string $value): array
    {
        $parts = preg_split('/\s*-\s*/', trim($value));
        if (!$parts || count($parts) !== 2) {
            throw new RuntimeException('Time range is invalid.');
        }
        return [$this->time($parts[0]), $this->time($parts[1])];
    }

    private function time(string $value): string
    {
        $value = trim($value);
        foreach (['H:i:s', 'H:i', 'g:i A', 'h:i A', 'g A', 'h A'] as $format) {
            $time = DateTimeImmutable::createFromFormat($format, $value);
            if ($time instanceof DateTimeImmutable) {
                return $time->format('H:i:s');
            }
        }
        throw new RuntimeException('Time is invalid.');
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
}
