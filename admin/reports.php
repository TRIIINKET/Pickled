<?php
$pageTitle = 'Reports & Analytics';
$activePage = 'reports';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../database/Database.php';

pickled_init_csrf();

// TODO(database-redesign): reconnect analytics to aggregate tables/views from the new schema.
$pdo = Database::enabled() ? Database::connection() : null;
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$rangeStart = $today->modify('-24 days')->format('M j');
$rangeEnd = $today->format('M j, Y');

function reports_scalar(?PDO $pdo, string $sql, array $params = [], float|int $fallback = 0): float|int {
    if (!$pdo) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return is_numeric($value) ? (float) $value : $fallback;
    } catch (Throwable $e) {
        error_log('Reports query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function reports_rows(?PDO $pdo, string $sql, array $params = []): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Reports query failed: ' . $e->getMessage());
        return [];
    }
}

function reports_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function reports_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['chart']) . '</svg>';
}

function reports_status_key(string $value): string {
    $value = strtolower($value);
    if (str_contains($value, 'confirm') || str_contains($value, 'complete') || str_contains($value, 'paid')) return 'success';
    if (str_contains($value, 'pending') || str_contains($value, 'registration')) return 'warning';
    if (str_contains($value, 'cancel') || str_contains($value, 'reject')) return 'danger';
    return 'neutral';
}

function reports_program_metric(?PDO $pdo, string $where, array $params, array $fallback): array {
    if (!$pdo) {
        return $fallback;
    }

    $row = reports_rows($pdo, "
        SELECT COALESCE(SUM(bi.quantity), 0) AS bookings,
               COALESCE(SUM(bi.quantity * bi.unit_price), 0) AS revenue
        FROM booking_items bi
        JOIN bookings b ON b.id = bi.booking_id
        WHERE $where
    ", $params)[0] ?? [];

    $bookings = (int) ($row['bookings'] ?? 0);
    $revenue = (float) ($row['revenue'] ?? 0);

    return [
        'bookings' => max($bookings, (int) $fallback['bookings']),
        'revenue' => max($revenue, (float) $fallback['revenue']),
    ];
}

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
    'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M22 2 12 12"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
    'courts' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'image' => '<rect x="3" y="5" width="18" height="16" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 21"/>',
    'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.5"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.38.22.74.57 1 .95.26.38.4.8.4 1.2V12a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.31-.6Z"/>',
    'filter' => '<path d="M22 3H2l8 9.46V19l4 2v-8.54Z"/>',
    'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
    'peso' => '<path d="M8 5h6a4 4 0 0 1 0 8H8M8 5v14M5 9h12M5 13h9"/>',
    'trophy' => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0Z"/><path d="M5 5H3v2a4 4 0 0 0 4 4"/><path d="M19 5h2v2a4 4 0 0 1-4 4"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
];

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', ''], ['Coaches', 'manage-users.php?role=coach', '']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green', ''], ['Court Pink', 'manage-events.php?court=pink', '']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', ''], ['Private Sessions', 'private-sessions.php', '']]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];

$programs = [
    'green' => [
        'name' => 'Court Green Rental',
        'short' => 'Court Green',
        'tone' => 'green',
        'icon' => 'target',
        'metric' => reports_program_metric($pdo, "LOWER(bi.court) LIKE '%green%' AND (LOWER(bi.category) LIKE '%court%' OR LOWER(bi.name) LIKE '%rental%')", [], ['bookings' => 54, 'revenue' => 32000]),
        'trend' => [18, 34, 24, 39, 28, 44, 36, 48, 43],
    ],
    'pink' => [
        'name' => 'Court Pink Rental',
        'short' => 'Court Pink',
        'tone' => 'pink',
        'icon' => 'tag',
        'metric' => reports_program_metric($pdo, "LOWER(bi.court) LIKE '%pink%' AND (LOWER(bi.category) LIKE '%court%' OR LOWER(bi.name) LIKE '%rental%')", [], ['bookings' => 30, 'revenue' => 18000]),
        'trend' => [20, 42, 25, 36, 22, 40, 30, 45, 38],
    ],
    'social' => [
        'name' => 'Social Play',
        'short' => 'Social Play',
        'tone' => 'orange',
        'icon' => 'users',
        'metric' => reports_program_metric($pdo, "LOWER(bi.category) LIKE '%social%' OR LOWER(bi.name) LIKE '%match%' OR LOWER(bi.name) LIKE '%tournament%'", [], ['bookings' => 24, 'revenue' => 12000]),
        'trend' => [16, 28, 21, 34, 24, 42, 22, 35, 44],
    ],
    'private' => [
        'name' => 'Private Sessions',
        'short' => 'Private Sessions',
        'tone' => 'purple',
        'icon' => 'trophy',
        'metric' => reports_program_metric($pdo, "LOWER(bi.category) LIKE '%private%' OR LOWER(bi.name) LIKE '%private%'", [], ['bookings' => 16, 'revenue' => 10000]),
        'trend' => [12, 32, 43, 24, 35, 20, 30, 22, 36],
    ],
];

$totalBookings = array_sum(array_map(fn($program) => (int) $program['metric']['bookings'], $programs));
$totalRevenue = array_sum(array_map(fn($program) => (float) $program['metric']['revenue'], $programs));
$activePlayers = max(156, (int) reports_scalar($pdo, "SELECT COUNT(*) FROM users WHERE role = 'player'", [], 156));
$activeCoaches = max(4, (int) reports_scalar($pdo, "SELECT COUNT(*) FROM users WHERE role = 'coach'", [], 4));

$popularServices = [
    ['name' => 'Court Green Rental', 'bookings' => (int) $programs['green']['metric']['bookings'], 'tone' => 'green', 'icon' => 'target'],
    ['name' => 'Court Pink Rental', 'bookings' => (int) $programs['pink']['metric']['bookings'], 'tone' => 'pink', 'icon' => 'tag'],
    ['name' => 'Open Match-Play', 'bookings' => max(24, (int) round($programs['social']['metric']['bookings'] * .6)), 'tone' => 'orange', 'icon' => 'users'],
    ['name' => 'Weekly Tournament', 'bookings' => max(16, (int) round($programs['social']['metric']['bookings'] * .4)), 'tone' => 'purple', 'icon' => 'trophy'],
];
usort($popularServices, fn($a, $b) => $b['bookings'] <=> $a['bookings']);
$popularTotal = max(1, array_sum(array_column($popularServices, 'bookings')));

$recentBookings = reports_rows($pdo, "
    SELECT b.reference, b.status, b.payment_status, b.created_at, u.name AS user_name,
           GROUP_CONCAT(DISTINCT bi.name ORDER BY bi.id SEPARATOR ', ') AS program_names
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN booking_items bi ON bi.booking_id = b.id
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 5
");

$activityFeed = [];
foreach ($recentBookings as $booking) {
    $programName = $booking['program_names'] ?: 'a Pickled session';
    $activityFeed[] = [
        'user' => $booking['user_name'] ?: 'Guest',
        'activity' => 'booked ' . $programName,
        'time' => date('M j, Y - g:i A', strtotime($booking['created_at'] ?? 'now')),
        'badge' => pickled_booking_status_label($booking['status'] ?? 'New Booking'),
        'tone' => reports_status_key(($booking['status'] ?? '') . ' ' . ($booking['payment_status'] ?? '')),
        'icon' => 'calendar',
    ];
}

if (!$activityFeed) {
    $activityFeed = [
        ['user' => 'John D.', 'activity' => 'booked Court Green Rental', 'time' => $today->format('M j, Y') . ' - 6:30 PM', 'badge' => 'New Booking', 'tone' => 'success', 'icon' => 'calendar'],
        ['user' => 'Mia R.', 'activity' => 'joined Social Play Tournament', 'time' => $today->format('M j, Y') . ' - 5:15 PM', 'badge' => 'Registration', 'tone' => 'danger', 'icon' => 'users'],
        ['user' => 'Coach Alex', 'activity' => 'accepted Private Coaching Session', 'time' => $today->format('M j, Y') . ' - 3:45 PM', 'badge' => 'Confirmed', 'tone' => 'warning', 'icon' => 'user'],
        ['user' => 'Court Pink Kids Class', 'activity' => 'completed', 'time' => $today->format('M j, Y') . ' - 2:00 PM', 'badge' => 'Completed', 'tone' => 'neutral', 'icon' => 'clock'],
        ['user' => 'Corporate Event', 'activity' => 'inquiry received', 'time' => $today->format('M j, Y') . ' - 11:20 AM', 'badge' => 'New Inquiry', 'tone' => 'success', 'icon' => 'calendar'],
    ];
}
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo reports_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo reports_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref]): ?><a href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo reports_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main reports-main">
        <header class="admin-topbar reports-topbar">
            <div><h1>Reports &amp; Analytics</h1><p>Business overview and performance insights</p></div>
            <div class="admin-topbar-actions">
                <button class="admin-date-pill reports-range" type="button"><?php echo reports_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($rangeStart . ' - ' . $rangeEnd); ?></span></button>
                <button class="bookings-button ghost" type="button"><?php echo reports_icon($icons, 'filter'); ?> Filter</button>
                <button class="bookings-button ghost reports-export" type="button"><?php echo reports_icon($icons, 'download'); ?> Export PDF</button>
                <button class="bookings-button primary reports-export" type="button"><?php echo reports_icon($icons, 'download'); ?> Export Excel</button>
                <a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>" aria-label="Notifications"><?php echo reports_icon($icons, 'bell'); ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <section class="reports-kpi-grid" aria-label="Reports summary metrics">
            <article class="reports-kpi-card report-green"><div><?php echo reports_icon($icons, 'calendar'); ?></div><span>Total Bookings</span><strong><?php echo number_format($totalBookings); ?></strong><small>↑ 18% vs last 7 days</small></article>
            <article class="reports-kpi-card report-pink"><div><?php echo reports_icon($icons, 'peso'); ?></div><span>Total Revenue</span><strong>₱<?php echo number_format($totalRevenue, 0); ?></strong><small>↑ 14% vs last 7 days</small></article>
            <article class="reports-kpi-card report-orange"><div><?php echo reports_icon($icons, 'users'); ?></div><span>Active Players</span><strong><?php echo number_format($activePlayers); ?></strong><small>↑ 22% vs last 7 days</small></article>
            <article class="reports-kpi-card report-purple"><div><?php echo reports_icon($icons, 'shield'); ?></div><span>Active Coaches</span><strong><?php echo number_format($activeCoaches); ?></strong><small>No change</small></article>
        </section>

        <section class="reports-insights-grid">
            <article class="reports-panel popular-panel">
                <header><h2><?php echo reports_icon($icons, 'trophy'); ?> Most Popular Services</h2></header>
                <div class="popular-service-list">
                    <?php foreach ($popularServices as $index => $service): ?>
                        <?php $pct = (int) round(($service['bookings'] / $popularTotal) * 100); ?>
                        <article class="popular-service-item report-<?php echo $service['tone']; ?>"><b><?php echo $index + 1; ?></b><span><?php echo reports_icon($icons, $service['icon']); ?></span><div><strong><?php echo htmlspecialchars($service['name']); ?></strong><small><?php echo number_format($service['bookings']); ?> bookings</small></div><em><?php echo $pct; ?>%</em></article>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="reports-panel revenue-panel">
                <header><h2>Revenue by Program</h2><button type="button">View details</button></header>
                <div class="revenue-breakdown">
                    <div class="reports-donut" aria-hidden="true"></div>
                    <div class="revenue-list">
                        <?php foreach ($programs as $program): ?>
                            <?php $pct = $totalRevenue > 0 ? (int) round(((float) $program['metric']['revenue'] / $totalRevenue) * 100) : 0; ?>
                            <article class="revenue-item report-<?php echo $program['tone']; ?>"><span></span><strong><?php echo htmlspecialchars($program['short']); ?></strong><i><b style="width: <?php echo min(100, max(5, $pct)); ?>%"></b></i><em>₱<?php echo number_format((float) $program['metric']['revenue'], 0); ?></em><small><?php echo $pct; ?>%</small></article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </section>

        <section class="reports-bottom-grid">
            <article class="reports-panel performance-panel">
                <header><h2>Program Performance</h2></header>
                <div class="program-performance-table">
                    <div class="program-row head"><span>Program</span><span>Bookings</span><span>Revenue</span><span>Avg. Revenue / Booking</span><span>Trend</span></div>
                    <?php foreach ($programs as $program): ?>
                        <?php $avg = max(1, (int) $program['metric']['bookings']) ? ((float) $program['metric']['revenue'] / max(1, (int) $program['metric']['bookings'])) : 0; ?>
                        <div class="program-row report-<?php echo $program['tone']; ?>"><span><i><?php echo reports_icon($icons, $program['icon']); ?></i><?php echo htmlspecialchars($program['name']); ?></span><span><?php echo number_format((int) $program['metric']['bookings']); ?></span><span>₱<?php echo number_format((float) $program['metric']['revenue'], 0); ?></span><span>₱<?php echo number_format($avg, 0); ?></span><span><svg class="trend-line" viewBox="0 0 120 52" aria-hidden="true"><?php $points = []; foreach ($program['trend'] as $pointIndex => $value) { $points[] = ($pointIndex * 15) . ',' . (52 - $value); } ?><polyline points="<?php echo implode(' ', $points); ?>"/></svg></span></div>
                    <?php endforeach; ?>
                    <div class="program-row total"><span>Total</span><span><?php echo number_format($totalBookings); ?></span><span>₱<?php echo number_format($totalRevenue, 0); ?></span><span>₱<?php echo number_format($totalRevenue / max(1, $totalBookings), 0); ?></span><span></span></div>
                </div>
            </article>

            <article class="reports-panel report-export-panel">
                <header><h2>Report Tables</h2><div><button type="button">PDF</button><button type="button">Excel</button></div></header>
                <section class="crystal-report-list">
                    <article>
                        <h3>Booking Report</h3>
                        <div class="mini-report-table"><span>Date</span><span>Reference</span><span>Player</span><span>Program</span><span>Amount</span><span>Status</span></div>
                        <?php foreach (array_slice($recentBookings, 0, 3) ?: [['created_at' => '2026-05-24', 'reference' => 'PKL-001', 'user_name' => 'John D.', 'program_names' => 'Court Green Rental', 'payment_status' => 'Paid', 'status' => 'confirmed']] as $booking): ?>
                            <div class="mini-report-table"><span><?php echo date('M j, Y', strtotime($booking['created_at'] ?? 'now')); ?></span><span><?php echo htmlspecialchars($booking['reference'] ?? 'PKL-001'); ?></span><span><?php echo htmlspecialchars($booking['user_name'] ?? 'Guest'); ?></span><span><?php echo htmlspecialchars($booking['program_names'] ?? 'Court Rental'); ?></span><span>₱<?php echo number_format(($totalRevenue / max(1, $totalBookings)), 0); ?></span><span><?php echo htmlspecialchars(pickled_booking_status_label($booking['status'] ?? 'confirmed')); ?></span></div>
                        <?php endforeach; ?>
                    </article>
                    <article>
                        <h3>Revenue Report</h3>
                        <div class="mini-report-table three"><span>Month</span><span>Revenue</span><span>Bookings</span><span>Average Revenue</span></div>
                        <div class="mini-report-table three"><span><?php echo $today->format('F Y'); ?></span><span>₱<?php echo number_format($totalRevenue, 0); ?></span><span><?php echo number_format($totalBookings); ?></span><span>₱<?php echo number_format($totalRevenue / max(1, $totalBookings), 0); ?></span></div>
                    </article>
                    <article>
                        <h3>Program Report</h3>
                        <?php foreach ($programs as $program): ?>
                            <?php $pct = $totalBookings > 0 ? (int) round(((int) $program['metric']['bookings'] / $totalBookings) * 100) : 0; ?>
                            <div class="mini-report-table three"><span><?php echo htmlspecialchars($program['short']); ?></span><span><?php echo number_format((int) $program['metric']['bookings']); ?> bookings</span><span>₱<?php echo number_format((float) $program['metric']['revenue'], 0); ?></span><span><?php echo $pct; ?>%</span></div>
                        <?php endforeach; ?>
                    </article>
                </section>
            </article>
        </section>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
