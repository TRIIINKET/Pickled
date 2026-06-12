<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CatalogRepository.php';
require_once __DIR__ . '/SchedulingService.php';

final class AvailabilityService
{
    public function __construct(
        private readonly CatalogRepository $catalog = new CatalogRepository(),
        private readonly SchedulingService $schedules = new SchedulingService()
    ) {}

    public function month(string $variantSlug, int $year, int $month): array
    {
        $variant = $this->catalog->findVariantBySlug($variantSlug);
        if (!$variant) {
            return ['variant' => null, 'dates' => []];
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
                'capacity' => (int) $variant['capacity'],
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
}
