<?php
declare(strict_types=1);

return [
    'app_name' => 'PICKLED',
    'timezone' => 'Asia/Manila',
    'cart' => [
        'hold_seconds' => 300,
        'item_limit' => 3,
    ],
    'database' => [
        'enabled' => true,
        'redesign_mode' => true,
    ],
    'mail' => [
        'from_email' => 'no-reply@pickled.local',
        'from_name' => 'PICKLED',
    ],
    'payment_methods' => [
        'manual_online' => ['label' => 'Manual Online Payment', 'fee_rate' => 0.00],
    ],
];
