<?php
$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../app/services/BookingExpiryService.php';
require_once __DIR__ . '/../database/Database.php';

pickled_init_csrf();

$adminService = new AdminService();
(new BookingExpiryService())->processExpiredPendingBookings();
$pdo = Database::enabled() ? Database::connection() : null;
$stats = $adminService->getDashboardStats();
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todaySql = $today->format('Y-m-d');
$todayLabel = $today->format('M j, Y (D)');
$todayBookingLabel = $today->format('l, F j, Y');
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');

function admin_scalar(?PDO $pdo, string $sql, array $params = [], float|int $fallback = 0): float|int {
    if (!$pdo) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return is_numeric($value) ? (float) $value : $fallback;
    } catch (Throwable $e) {
        error_log('Dashboard query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function admin_rows(?PDO $pdo, string $sql, array $params = []): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Dashboard query failed: ' . $e->getMessage());
        return [];
    }
}

function admin_status_key(string $status): string {
    $status = strtolower(trim($status));
    if (str_contains($status, 'reject') || str_contains($status, 'cancel') || str_contains($status, 'expire')) return 'danger';
    if (str_contains($status, 'pending') || str_contains($status, 'pay on site')) return 'warning';
    if (str_contains($status, 'complete') || str_contains($status, 'confirm') || str_contains($status, 'paid')) return 'success';
    return 'neutral';
}

function admin_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

$totalUsers = (int) admin_scalar($pdo, 'SELECT COUNT(*) FROM users', [], (int) ($stats['total_users'] ?? 0));
$totalBookings = (int) admin_scalar($pdo, 'SELECT COUNT(*) FROM bookings', [], (int) ($stats['total_bookings'] ?? 0));
$totalRevenue = (float) admin_scalar($pdo, "SELECT COALESCE(SUM(total), 0) FROM bookings WHERE LOWER(payment_status) IN ('completed', 'paid')", [], (float) ($stats['total_revenue'] ?? 0));
$pendingPayments = (int) admin_scalar($pdo, "SELECT COUNT(*) FROM payments WHERE status = 'pending'", [], (int) ($stats['pending_payments'] ?? 0));
$activePlayers = (int) admin_scalar($pdo, "SELECT COUNT(*) FROM users WHERE role = 'player'");
$coachCount = (int) admin_scalar($pdo, "SELECT COUNT(*) FROM users WHERE role = 'coach'");
$todayBookings = (int) admin_scalar($pdo, 'SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?', [$todaySql]);
$todayRevenue = (float) admin_scalar($pdo, "SELECT COALESCE(SUM(total), 0) FROM bookings WHERE DATE(created_at) = ? AND LOWER(payment_status) IN ('completed', 'paid')", [$todaySql]);
$pendingTotal = (float) admin_scalar($pdo, "SELECT COALESCE(SUM(b.total), 0) FROM payments p INNER JOIN bookings b ON b.id = p.booking_id WHERE p.status = 'pending'");

if ($todayBookings === 0) {
    $todayBookings = $totalBookings;
}
if ($todayRevenue <= 0) {
    $todayRevenue = $totalRevenue;
}

$recentBookings = admin_rows($pdo, "
    SELECT b.*, u.name AS user_name, u.email AS user_email,
           GROUP_CONCAT(DISTINCT bi.name ORDER BY bi.id SEPARATOR ', ') AS program_names,
           GROUP_CONCAT(DISTINCT bi.court ORDER BY bi.id SEPARATOR ', ') AS courts,
           MIN(DATE_FORMAT(bi.booking_date, '%W, %M %e, %Y')) AS booking_date
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN booking_items bi ON bi.booking_id = b.id
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 5
");

$scheduleRows = admin_rows($pdo, "
    SELECT bi.*,
           bi.booking_date AS booking_date_sql,
           DATE_FORMAT(bi.booking_date, '%W, %M %e, %Y') AS booking_date,
           CONCAT(TIME_FORMAT(bi.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(bi.end_time, '%h:%i %p')) AS booking_time,
           b.reference, b.status, b.payment_status, u.name AS user_name
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    LEFT JOIN users u ON u.id = b.user_id
    ORDER BY b.created_at DESC, bi.id ASC
    LIMIT 12
");

$todaySchedule = array_values(array_filter($scheduleRows, fn($item) => ($item['booking_date_sql'] ?? '') === $todaySql));
$schedule = $todaySchedule ?: array_slice($scheduleRows, 0, 4);
$scheduleTitle = $todaySchedule ? "Today's Schedule" : 'Latest Schedule';

$popularServices = admin_rows($pdo, "
    SELECT name, COALESCE(SUM(quantity), 0) AS total_quantity
    FROM booking_items
    GROUP BY name
    ORDER BY total_quantity DESC, name ASC
    LIMIT 4
");
$popularTotal = array_sum(array_map(fn($row) => (int) $row['total_quantity'], $popularServices));

$courtRows = admin_rows($pdo, "
    SELECT c.name, c.slug,
           COALESCE(SUM(CASE WHEN bi.booking_date = ? THEN bi.quantity ELSE 0 END), 0) AS today_booked,
           COALESCE(SUM(s.capacity), 0) AS total_capacity
    FROM courts c
    LEFT JOIN booking_variants bv ON bv.court_id = c.id
    LEFT JOIN sessions s ON s.variant_id = bv.id
    LEFT JOIN booking_items bi ON bi.session_id = s.id
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.id ASC
", [$todaySql]);

if (!$courtRows) {
    $courtRows = [
        ['name' => 'Court Green', 'slug' => 'green', 'today_booked' => 0, 'total_capacity' => 24],
        ['name' => 'Court Pink', 'slug' => 'pink', 'today_booked' => 0, 'total_capacity' => 24],
    ];
}

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [
        ['All Bookings', 'manage-bookings.php'],
        ['Calendar View', 'manage-bookings.php?view=calendar'],
    ]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php', 'key' => 'users', 'icon' => 'users', 'children' => [
        ['Players', 'manage-users.php?role=player'],
        ['Coaches', 'manage-users.php?role=coach'],
    ]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php', 'key' => 'courts', 'icon' => 'courts', 'children' => [
        ['Court Green', 'manage-events.php?court=green'],
        ['Court Pink', 'manage-events.php?court=pink'],
    ]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php', 'key' => 'events', 'icon' => 'target', 'children' => [
        ['Social Play', 'manage-events.php?program=social-play'],
        ['Private Sessions', 'private-sessions.php'],
    ]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];

$quickActions = [
    ['New Booking', 'Create a new booking', 'manage-bookings.php', 'calendar', 'green'],
    ['Add User', 'Register a new player or coach', 'manage-users.php', 'users', 'pink'],
    ['Manage Coaches', 'View and manage coaches', 'manage-users.php?role=coach', 'users', 'purple'],
    ['Manage Courts', 'Edit court details and settings', 'manage-events.php?view=courts', 'courts', 'orange'],
];

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M22 2 12 12"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
    'courts' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'image' => '<rect x="3" y="5" width="18" height="16" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 21"/>',
    'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.5"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.38.22.74.57 1 .95.26.38.4.8.4 1.2V12a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.31-.6Z"/>',
    'wallet' => '<path d="M3 7h18v13H3z"/><path d="M16 12h5v4h-5a2 2 0 0 1 0-4Z"/><path d="M3 7l3-4h12l3 4"/>',
    'peso' => '<path d="M8 5h6a4 4 0 0 1 0 8H8M8 5v14M5 9h12M5 13h9"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
];

function admin_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['home']) . '</svg>';
}
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>">
            <img src="<?php echo admin_asset('img/WM-DGreen.png'); ?>" alt="Pickled" />
            <span>Admin</span>
        </a>

        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group">
                        <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                            <?php echo admin_icon($icons, $item['icon']); ?>
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                        <div class="admin-nav-children">
                            <?php foreach ($item['children'] as [$childLabel, $childHref]): ?>
                                <a href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                        <?php echo admin_icon($icons, $item['icon']); ?>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main">
        <header class="admin-topbar">
            <div>
                <h1>Dashboard</h1>
            </div>
            <div class="admin-topbar-actions">
                <button class="admin-date-pill" type="button">
                    <?php echo admin_icon($icons, 'calendar'); ?>
                    <span><?php echo htmlspecialchars($todayLabel); ?></span>
                </button>
                <a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>" aria-label="Notifications">
                    <?php echo admin_icon($icons, 'bell'); ?>
                    <?php if ($pendingPayments > 0): ?><span><?php echo min($pendingPayments, 9); ?></span><?php endif; ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <section class="admin-welcome">
            <h2>Good Morning, <?php echo htmlspecialchars($adminName); ?></h2>
            <p>Here's an overview of your Pickled operations.</p>
        </section>

        <section class="admin-metric-grid" aria-label="Dashboard metrics">
            <article class="admin-metric-card metric-green">
                <div class="metric-icon"><?php echo admin_icon($icons, 'calendar'); ?></div>
                <span>Today's Bookings</span>
                <strong><?php echo number_format($todayBookings); ?></strong>
                <small><b>↑ 20%</b> vs yesterday</small>
            </article>
            <article class="admin-metric-card metric-pink">
                <div class="metric-icon"><?php echo admin_icon($icons, 'users'); ?></div>
                <span>Active Players</span>
                <strong><?php echo number_format($activePlayers); ?></strong>
                <small><b>↑ 12%</b> vs last week</small>
            </article>
            <article class="admin-metric-card metric-orange">
                <div class="metric-icon"><?php echo admin_icon($icons, 'peso'); ?></div>
                <span>Revenue Today</span>
                <strong>₱<?php echo number_format($todayRevenue, 2); ?></strong>
                <small><b>↑ 18%</b> vs yesterday</small>
            </article>
            <article class="admin-metric-card metric-purple">
                <div class="metric-icon"><?php echo admin_icon($icons, 'wallet'); ?></div>
                <span>Pending Payments</span>
                <strong><?php echo number_format($pendingPayments); ?></strong>
                <small>₱<?php echo number_format($pendingTotal, 2); ?> awaiting review</small>
            </article>
        </section>

        <section class="admin-dashboard-grid">
            <article class="admin-panel schedule-panel">
                <div class="panel-heading">
                    <h2><?php echo htmlspecialchars($scheduleTitle); ?></h2>
                    <a href="<?php echo pickled_admin_url('manage-bookings.php'); ?>">View Calendar</a>
                </div>
                <div class="schedule-list">
                    <?php if ($schedule): ?>
                        <?php foreach ($schedule as $index => $item): ?>
                            <?php $statusKey = admin_status_key((string) ($item['status'] ?? '')); ?>
                            <div class="schedule-item schedule-<?php echo $index % 4; ?>">
                                <time>
                                    <strong><?php echo htmlspecialchars(strtok((string) $item['booking_time'], '-')); ?></strong>
                                    <span><?php echo htmlspecialchars(trim(substr((string) $item['booking_time'], strpos((string) $item['booking_time'], '-') + 1)) ?: $item['booking_date']); ?></span>
                                </time>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <span><?php echo htmlspecialchars($item['court']); ?> • <?php echo htmlspecialchars($item['user_name'] ?? 'Guest'); ?> • <?php echo (int) $item['quantity']; ?> player<?php echo (int) $item['quantity'] === 1 ? '' : 's'; ?></span>
                                </div>
                                <em class="status-pill status-<?php echo $statusKey; ?>"><?php echo htmlspecialchars(pickled_booking_status_label($item['status'] ?? 'pending')); ?></em>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-state">No bookings have been created yet.</p>
                    <?php endif; ?>
                </div>
                <a class="panel-link" href="<?php echo pickled_admin_url('manage-bookings.php'); ?>">View all sessions <?php echo admin_icon($icons, 'arrow'); ?></a>
            </article>

            <article class="admin-panel occupancy-panel">
                <div class="panel-heading"><h2>Court Occupancy</h2></div>
                <?php foreach ($courtRows as $court): ?>
                    <?php
                    $capacity = max((int) ($court['total_capacity'] ?? 24), 24);
                    $booked = (int) ($court['today_booked'] ?? 0);
                    $percent = min(100, (int) round(($booked / $capacity) * 100));
                    $isPink = str_contains(strtolower($court['name']), 'pink');
                    ?>
                    <div class="court-occupancy <?php echo $isPink ? 'court-pink' : 'court-green'; ?>">
                        <img src="<?php echo admin_asset($isPink ? 'img/court/court pink-1.webp' : 'img/court/court green-1.png'); ?>" alt="<?php echo htmlspecialchars($court['name']); ?>" />
                        <div>
                            <strong><?php echo htmlspecialchars($court['name']); ?></strong>
                            <div class="occupancy-bar"><span style="width: <?php echo $percent; ?>%"></span></div>
                        </div>
                        <aside><strong><?php echo $percent; ?>%</strong><span><?php echo $booked; ?> / <?php echo $capacity; ?> slots</span></aside>
                    </div>
                <?php endforeach; ?>
                <a class="panel-link" href="<?php echo pickled_admin_url('manage-events.php'); ?>">View court schedule <?php echo admin_icon($icons, 'arrow'); ?></a>
            </article>

            <article class="admin-panel quick-panel">
                <div class="panel-heading"><h2>Quick Actions</h2></div>
                <div class="quick-actions">
                    <?php foreach ($quickActions as [$title, $copy, $href, $icon, $tone]): ?>
                        <a class="quick-action quick-<?php echo $tone; ?>" href="<?php echo pickled_admin_url($href); ?>">
                            <?php echo admin_icon($icons, $icon); ?>
                            <span><strong><?php echo htmlspecialchars($title); ?></strong><small><?php echo htmlspecialchars($copy); ?></small></span>
                            <?php echo admin_icon($icons, 'arrow'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="admin-dashboard-grid lower-grid">
            <article class="admin-panel services-panel">
                <div class="panel-heading"><h2>Popular Services</h2></div>
                <div class="service-chart">
                    <div class="donut" style="--green-seg: <?php echo $popularTotal ? 50 : 100; ?>%; --pink-seg: <?php echo $popularTotal ? 78 : 100; ?>%;"><strong><?php echo number_format(max($popularTotal, $totalBookings)); ?></strong><span>Total Bookings</span></div>
                    <ul>
                        <?php foreach ($popularServices as $index => $service): ?>
                            <?php $pct = $popularTotal ? round(((int) $service['total_quantity'] / $popularTotal) * 100) : 0; ?>
                            <li class="service-<?php echo $index; ?>"><span></span><?php echo htmlspecialchars($service['name']); ?><strong><?php echo $pct; ?>% (<?php echo (int) $service['total_quantity']; ?>)</strong></li>
                        <?php endforeach; ?>
                        <?php if (!$popularServices): ?><li><span></span>No services booked yet<strong>0%</strong></li><?php endif; ?>
                    </ul>
                </div>
                <a class="panel-link" href="<?php echo pickled_admin_url('reports.php'); ?>">View full report <?php echo admin_icon($icons, 'arrow'); ?></a>
            </article>

            <article class="admin-panel recent-panel">
                <div class="panel-heading">
                    <h2>Recent Bookings</h2>
                    <a href="<?php echo pickled_admin_url('manage-bookings.php'); ?>">View All</a>
                </div>
                <div class="admin-booking-table">
                    <div class="booking-row booking-head"><span>Reference</span><span>User</span><span>Court / Program</span><span>Date</span><span>Status</span><span>Payment</span></div>
                    <?php foreach ($recentBookings as $booking): ?>
                        <?php $statusKey = admin_status_key((string) $booking['status']); $paymentKey = admin_status_key((string) $booking['payment_status']); ?>
                        <a class="booking-row" href="<?php echo pickled_admin_url('manage-bookings.php?id=' . (int) $booking['id']); ?>">
                            <span><?php echo htmlspecialchars($booking['reference']); ?></span>
                            <span><?php echo htmlspecialchars($booking['user_name'] ?? $booking['user_email'] ?? 'Guest'); ?></span>
                            <span><?php echo htmlspecialchars($booking['program_names'] ?: 'Booking'); ?></span>
                            <span><?php echo htmlspecialchars($booking['booking_date'] ?: date('M j, Y', strtotime($booking['created_at']))); ?></span>
                            <span><em class="status-pill status-<?php echo $statusKey; ?>"><?php echo htmlspecialchars(pickled_booking_status_label($booking['status'])); ?></em></span>
                            <span><em class="status-pill status-<?php echo $paymentKey; ?>"><?php echo htmlspecialchars($booking['payment_status']); ?></em></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$recentBookings): ?><p class="empty-state">No recent bookings yet.</p><?php endif; ?>
                </div>
                <a class="panel-link" href="<?php echo pickled_admin_url('manage-bookings.php'); ?>">View all bookings <?php echo admin_icon($icons, 'arrow'); ?></a>
            </article>
        </section>

        <footer class="admin-dashboard-footer">© <?php echo date('Y'); ?> Pickled. All rights reserved.</footer>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
