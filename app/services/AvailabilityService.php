<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CatalogRepository.php';

final class AvailabilityService
{
    public function __construct(private readonly CatalogRepository $catalog = new CatalogRepository()) {}

    public function month(string $variantSlug, int $year, int $month): array
    {
        $variant = $this->catalog->findVariantBySlug($variantSlug);
        if (!$variant) {
            return ['variant' => null, 'dates' => []];
        }

        $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $lastDay = (int) $first->format('t');
        $dates = [];
        for ($day = 1; $day <= $lastDay; $day++) {
            $date = $first->setDate($year, $month, $day);
            if (!$this->isAllowedDate($variantSlug, $date)) {
                continue;
            }
            $label = $date->format('l, F j, Y');
            $slots = [];
            foreach ($this->slotsFor($variantSlug) as $time) {
                $session = $this->catalog->findOrCreateSession((int) $variant['id'], $label, $time, (int) $variant['capacity']);
                $remaining = max(0, (int) $session['capacity'] - (int) $session['booked_count']);
                $slots[$time] = [
                    'remaining' => $remaining,
                    'full' => $remaining <= 0,
                ];
            }
            $dates[$label] = [
                'available' => array_filter($slots, static fn(array $slot): bool => !$slot['full']) !== [],
                'slots' => $slots,
            ];
        }

        return [
            'variant' => [
                'slug' => $variant['slug'],
                'capacity' => (int) $variant['capacity'],
            ],
            'dates' => $dates,
        ];
    }

    private function slotsFor(string $slug): array
    {
        return match ($slug) {
            'green-open-match-play' => ['08:00 AM - 10:00 AM', '10:00 AM - 12:00 PM', '12:00 PM - 02:00 PM', '02:00 PM - 04:00 PM', '04:00 PM - 06:00 PM', '06:00 PM - 08:00 PM', '07:00 PM - 09:00 PM', '08:00 PM - 10:00 PM'],
            'green-weekly-tournament' => ['09:00 AM - 12:00 PM', '01:00 PM - 04:00 PM', '06:00 PM - 09:00 PM'],
            default => ['07:00 AM - 08:00 AM', '08:00 AM - 09:00 AM', '09:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '11:00 AM - 12:00 PM', '01:00 PM - 02:00 PM', '02:00 PM - 03:00 PM', '03:00 PM - 04:00 PM', '04:00 PM - 05:00 PM', '05:00 PM - 06:00 PM', '06:00 PM - 07:00 PM', '07:00 PM - 08:00 PM', '08:00 PM - 09:00 PM', '09:00 PM - 10:00 PM'],
        };
    }

    private function isAllowedDate(string $slug, DateTimeImmutable $date): bool
    {
        $day = (int) $date->format('w');
        return match ($slug) {
            'green-open-match-play' => in_array($day, [2, 4, 6], true),
            'green-weekly-tournament' => in_array($day, [0, 5], true),
            default => true,
        };
    }
}
