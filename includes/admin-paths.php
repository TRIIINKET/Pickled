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

function pickled_admin_account_menu(string $adminName, string $logoutCsrf, string $variant = 'sidebar'): string {
    $initial = htmlspecialchars(strtoupper(substr($adminName, 0, 1)), ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8');
    $csrf = htmlspecialchars($logoutCsrf, ENT_QUOTES, 'UTF-8');
    $class = 'admin-sidebar-user admin-account-menu sidebar-account-menu';

    return '
        <details class="' . $class . '">
            <summary>
                <div class="admin-avatar">' . $initial . '</div>
                <div><strong>' . $name . '</strong><span>Super Admin</span></div>
                <b class="admin-account-chevron">⌄</b>
            </summary>
            <div class="admin-account-popover">
                <a href="' . pickled_admin_url('admin-profile.php') . '#profile">Profile</a>
                <a href="' . pickled_admin_url('admin-profile.php') . '#password">Change Password</a>
                <form method="post" action="' . pickled_admin_url('admin-logout.php') . '">
                    <input type="hidden" name="csrf_token" value="' . $csrf . '">
                    <button type="submit">Logout</button>
                </form>
            </div>
        </details>';
}
