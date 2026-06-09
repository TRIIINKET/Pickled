<?php
function pickled_admin_asset_url($path) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/admin-dashboard.php');
    $position = strpos($script, '/admin/');
    $base = $position === false ? rtrim(dirname($script), '/') . '/' : substr($script, 0, $position + 1);
    return $base . 'assets/' . ltrim($path, '/');
}

function pickled_admin_url($page) {
    $base = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($base, '/') . '/' . ltrim($page, '/');
}

function pickled_booking_status_key($status) {
    $status = strtolower((string) $status);

    if (str_contains($status, 'cancel')) {
        return 'cancelled';
    }

    if (str_contains($status, 'complete')) {
        return 'completed';
    }

    if (str_contains($status, 'ongoing')) {
        return 'ongoing';
    }

    if (str_contains($status, 'confirm')) {
        return 'confirmed';
    }

    return 'pending';
}

function pickled_booking_status_label($status) {
    $labels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    return $labels[pickled_booking_status_key($status)];
}
