<?php
declare(strict_types=1);

return [
    'app_name' => 'PICKLED',
    'timezone' => 'Asia/Manila',
    'cart' => [
        'hold_seconds' => 300,
        'item_limit' => 3,
    ],
    'payment_methods' => [
        'gcash' => ['label' => 'GCash', 'fee_rate' => 0.00],
        'maya' => ['label' => 'Maya', 'fee_rate' => 0.00],
        'card' => ['label' => 'Credit / Debit Card', 'fee_rate' => 0.03],
        'cash' => ['label' => 'Cash On Site', 'fee_rate' => 0.00],
        'bank' => ['label' => 'Bank Transfer', 'fee_rate' => 0.00],
    ],
];
