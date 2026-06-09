<?php
declare(strict_types=1);

final class BookingCatalog
{
    public static function all(): array
    {
        return [
            'green-peak' => [
                'name' => 'Peak',
                'court' => 'Court Green',
                'category' => 'Premium hours',
                'price' => 600,
                'duration' => '1 hour',
                'capacity' => 12,
                'booked' => 6,
            ],
            'green-non-peak' => [
                'name' => 'Non-Peak',
                'court' => 'Court Green',
                'category' => 'Discount hours',
                'price' => 400,
                'duration' => '1 hour',
                'capacity' => 12,
                'booked' => 3,
            ],
            'green-private-class' => [
                'name' => 'Private Class',
                'court' => 'Court Green',
                'category' => 'Coaching',
                'price' => 1200,
                'duration' => '1 hour',
                'capacity' => 4,
                'booked' => 1,
            ],
            'green-social-play' => [
                'name' => 'Social Play',
                'court' => 'Court Green',
                'category' => 'Open matches',
                'price' => 350,
                'duration' => '2 hours',
                'capacity' => 16,
                'booked' => 10,
            ],
            'green-intermediate-class' => [
                'name' => 'Intermediate Class',
                'court' => 'Court Green',
                'category' => 'Skill-based training',
                'price' => 500,
                'duration' => '90 minutes',
                'capacity' => 8,
                'booked' => 6,
            ],
            'pink-foundational' => [
                'name' => 'Foundational Training',
                'court' => 'Court Pink',
                'category' => 'Kids lessons',
                'price' => 1200,
                'duration' => '4 sessions',
                'capacity' => 10,
                'booked' => 4,
            ],
            'pink-youth' => [
                'name' => 'Youth Development',
                'court' => 'Court Pink',
                'category' => 'Youth program',
                'price' => 1200,
                'duration' => '4 sessions',
                'capacity' => 10,
                'booked' => 5,
            ],
            'pink-adult-bootcamp' => [
                'name' => 'Adult Beginner Bootcamp',
                'court' => 'Court Pink',
                'category' => 'Beginner program',
                'price' => 1800,
                'duration' => '4 sessions',
                'capacity' => 12,
                'booked' => 7,
            ],
            'pink-trial' => [
                'name' => 'Introductory Trial Class',
                'court' => 'Court Pink',
                'category' => 'Trial class',
                'price' => 250,
                'duration' => '1 hour',
                'capacity' => 8,
                'booked' => 3,
            ],
            'pink-parent-child' => [
                'name' => 'Parent & Child Trial',
                'court' => 'Court Pink',
                'category' => 'Family play',
                'price' => 500,
                'duration' => '1 hour',
                'capacity' => 8,
                'booked' => 8,
            ],
        ];
    }
}
