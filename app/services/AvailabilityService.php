<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CatalogRepository.php';
require_once __DIR__ . '/../repositories/CartRepository.php';
require_once __DIR__ . '/SchedulingService.php';

final class AvailabilityService
{
    public function __construct(
        private readonly CatalogRepository $catalog = new CatalogRepository(),
        private readonly CartRepository $carts = new CartRepository(),
        private readonly SchedulingService $schedules = new SchedulingService()
    ) {}

    public function month(string $variantSlug, int $year, int $month): array
    {
        $variant = $this->catalog->findVariantBySlug($variantSlug);
        if (!$variant) {
            return ['variant' => null, 'dates' => []];
        }

        if ($this->usesGeneratedCourtAvailability($variant)) {
            return $this->generatedCourtAvailability($variant, $year, $month);
        }

        $dates = [];
        foreach ($this->schedules->sessionsForVariantMonth((int) $variant['id'], $year, $month) as $session) {
            $label = $session['display_date'];
            $time = $session['session_time'];
            $remaining = max(0, (int) $session['capacity'] - (int) $session['booked_count']);
            $availableCoaches = $this->schedules->availableCoachesForSlot($label, $time);
            $dates[$label]['slots'][$time] = [
                'session_id' => (int) $session['id'],
                'remaining' => $remaining,
                'full' => $remaining <= 0 || (string) $session['status'] !== 'open',
                'status' => $session['status'],
                'coach_id' => $session['coach_user_id'] ? (int) $session['coach_user_id'] : null,
                'coach_name' => $session['coach_name'] ?? null,
                'available_coaches' => array_map(static fn(array $coach): array => [
                    'id' => (int) $coach['id'],
                    'name' => $coach['name'],
                    'specialization' => $coach['specialization'] ?? null,
                ], $availableCoaches),
            ];
            $dates[$label] = [
                'available' => array_filter($dates[$label]['slots'], static fn(array $slot): bool => !$slot['full']) !== [],
                'slots' => $dates[$label]['slots'],
            ] + ($dates[$label] ?? []);
        }

        return [
            'variant' => [
                'slug' => $variant['slug'],
                'name' => $variant['name'],
                'price' => (float) $variant['price'],
                'duration_label' => $variant['duration_label'],
                'participants_limit' => (int) $variant['participants_limit'],
                'capacity' => (int) $variant['capacity'],
                'court' => $variant['court'],
                'court_slug' => $variant['court_slug'],
                'court_capacity' => (int) ($variant['court_capacity'] ?? 0),
            ],
            'coaches' => array_map(static fn(array $coach): array => [
                'id' => (int) $coach['id'],
                'name' => $coach['name'],
                'email' => $coach['email'],
                'specialization' => $coach['specialization'] ?? null,
            ], $this->schedules->coaches()),
            'dates' => $dates,
        ];
    }

    private function generatedCourtAvailability(array $variant, int $year, int $month): array
    {
        $firstDay = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $lastDay = $firstDay->modify('last day of this month');
        $today = new DateTimeImmutable('today');
        $slots = $this->standardCourtSlots();
        $requiresCoach = $this->requiresCoach($variant);
        $dates = [];

        for ($date = $firstDay; $date <= $lastDay; $date = $date->modify('+1 day')) {
            if ($date < $today) {
                continue;
            }

            $dateSql = $date->format('Y-m-d');
            $label = $date->format('l, F j, Y');
            foreach ($slots as [$start, $end]) {
                $timeLabel = $this->displayTimeRange($start, $end);
                $availableCoaches = $requiresCoach
                    ? $this->schedules->availableCoachesForSlot($label, $timeLabel)
                    : [];
                if ($requiresCoach) {
                    $availableCoaches = array_values(array_filter($availableCoaches, fn(array $coach): bool => !$this->carts->coachHasOverlap((int) $coach['id'], $dateSql, $start, $end)));
                }
                $conflict = $this->carts->courtHasOverlap((int) $variant['court_id'], $dateSql, $start, $end, (string) $variant['slug']);
                $booked = $this->carts->bookedQuantityForStandardSlot((string) $variant['slug'], $dateSql, $start, $end);
                $held = $this->carts->activeHeldQuantityForStandardSlot((int) $variant['id'], $dateSql, $start, $end);
                $remaining = max(0, (int) $variant['capacity'] - $booked - $held);
                $full = $conflict || $remaining <= 0 || ($requiresCoach && !$availableCoaches);
                $dates[$label]['slots'][$timeLabel] = [
                    'session_id' => null,
                    'remaining' => $full ? 0 : $remaining,
                    'full' => $full,
                    'status' => $full ? 'full' : 'open',
                    'coach_id' => $availableCoaches[0]['id'] ?? null,
                    'coach_name' => $availableCoaches[0]['name'] ?? null,
                    'available_coaches' => array_map(static fn(array $coach): array => [
                        'id' => (int) $coach['id'],
                        'name' => $coach['name'],
                        'specialization' => $coach['specialization'] ?? null,
                    ], $availableCoaches),
                ];
            }

            $dates[$label] = [
                'available' => array_filter($dates[$label]['slots'], static fn(array $slot): bool => !$slot['full']) !== [],
                'slots' => $dates[$label]['slots'],
            ] + ($dates[$label] ?? []);
        }

        return [
            'variant' => [
                'slug' => $variant['slug'],
                'name' => $variant['name'],
                'price' => (float) $variant['price'],
                'duration_label' => $variant['duration_label'],
                'participants_limit' => (int) $variant['participants_limit'],
                'capacity' => (int) $variant['capacity'],
                'court' => $variant['court'],
                'court_slug' => $variant['court_slug'],
                'court_capacity' => (int) ($variant['court_capacity'] ?? 0),
            ],
            'coaches' => array_map(static fn(array $coach): array => [
                'id' => (int) $coach['id'],
                'name' => $coach['name'],
                'email' => $coach['email'],
                'specialization' => $coach['specialization'] ?? null,
            ], $this->schedules->coaches()),
            'dates' => $dates,
        ];
    }

    private function standardCourtSlots(): array
    {
        return [
            ['08:00:00', '09:00:00'],
            ['09:00:00', '10:00:00'],
            ['10:00:00', '11:00:00'],
            ['11:00:00', '12:00:00'],
            ['13:00:00', '14:00:00'],
            ['14:00:00', '15:00:00'],
            ['15:00:00', '16:00:00'],
            ['16:00:00', '17:00:00'],
            ['17:00:00', '18:00:00'],
            ['18:00:00', '19:00:00'],
            ['19:00:00', '20:00:00'],
            ['20:00:00', '21:00:00'],
            ['21:00:00', '22:00:00'],
        ];
    }

    private function usesGeneratedCourtAvailability(array $variant): bool
    {
        $courtSlug = strtolower((string) ($variant['court_slug'] ?? ''));
        $label = strtolower((string) ($variant['category'] ?? '') . ' ' . (string) ($variant['name'] ?? ''));
        return in_array($courtSlug, ['green', 'pink'], true)
            && !str_contains($label, 'social play')
            && !str_contains($label, 'tournament')
            && !str_contains($label, 'match-play');
    }

    private function requiresCoach(array $variant): bool
    {
        $label = strtolower((string) ($variant['category'] ?? '') . ' ' . (string) ($variant['name'] ?? ''));
        foreach (['lesson', 'coaching', 'training', 'class', 'kids', 'youth', 'parent'] as $keyword) {
            if (str_contains($label, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function displayTimeRange(string $start, string $end): string
    {
        return (new DateTimeImmutable('1970-01-01 ' . $start))->format('h:i A')
            . ' - '
            . (new DateTimeImmutable('1970-01-01 ' . $end))->format('h:i A');
    }
}
