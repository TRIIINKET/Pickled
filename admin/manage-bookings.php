<?php
$pageTitle = 'All Bookings';
$activePage = 'bookings';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../database/Database.php';

pickled_init_csrf();

$pdo = Database::connection();
$adminService = new AdminService();
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todaySql = $today->format('Y-m-d');
$todayLabel = $today->format('M j, Y (D)');
$todayBookingLabel = $today->format('l, F j, Y');
$view = ($_GET['view'] ?? 'table') === 'calendar' ? 'calendar' : 'table';
$query = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$courtFilter = trim((string) ($_GET['court'] ?? 'all'));
$programFilter = trim((string) ($_GET['program'] ?? 'all'));
$dateFilter = trim((string) ($_GET['date'] ?? ''));
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$successMsg = '';
$errorMsg = '';

function booking_rows(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Booking page query failed: ' . $e->getMessage());
        return [];
    }
}

function booking_scalar(PDO $pdo, string $sql, array $params = [], float|int $fallback = 0): float|int {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return is_numeric($value) ? (float) $value : $fallback;
    } catch (Throwable $e) {
        error_log('Booking page query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function booking_status_key(string $status): string {
    $status = strtolower(trim($status));
    if (str_contains($status, 'cancel') || str_contains($status, 'reject')) return 'danger';
    if (str_contains($status, 'pending') || str_contains($status, 'pay on site')) return 'warning';
    if (str_contains($status, 'complete')) return 'neutral';
    if (str_contains($status, 'confirm') || str_contains($status, 'paid')) return 'success';
    return 'neutral';
}

function booking_payment_key(string $status): string {
    $status = strtolower(trim($status));
    if (str_contains($status, 'reject') || str_contains($status, 'refund')) return 'danger';
    if (str_contains($status, 'pending')) return 'warning';
    if (str_contains($status, 'site') || str_contains($status, 'bank')) return 'purple';
    if (str_contains($status, 'complete') || str_contains($status, 'paid')) return 'success';
    return 'neutral';
}

function booking_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['booking_id'] ?? 0);
        if ($action === 'approve_payment' && $id) {
            $successMsg = $adminService->approvePayment($id, (int) $_SESSION['user']['id']) ? 'Payment approved.' : 'Failed to approve payment.';
        } elseif ($action === 'reject_payment' && $id) {
            $reason = trim((string) ($_POST['reason'] ?? 'Payment rejected by admin'));
            $successMsg = $adminService->rejectPayment($id, $reason, (int) $_SESSION['user']['id']) ? 'Payment rejected.' : '';
            $errorMsg = $successMsg ? '' : 'Failed to reject payment.';
        } elseif ($action === 'update_status' && $id) {
            $status = trim((string) ($_POST['status'] ?? ''));
            $successMsg = $adminService->updateBookingStatus($id, $status, (int) $_SESSION['user']['id']) ? 'Booking status updated.' : '';
            $errorMsg = $successMsg ? '' : 'Failed to update booking.';
        }
        $bookingId = $id;
    }
}

$where = [];
$params = [];
if ($query !== '') {
    $where[] = "(b.reference LIKE :q OR u.name LIKE :q OR u.email LIKE :q)";
    $params['q'] = '%' . $query . '%';
}
if ($statusFilter !== 'all') {
    $where[] = "LOWER(b.status) LIKE :status";
    $params['status'] = '%' . strtolower($statusFilter) . '%';
}
if ($courtFilter !== 'all') {
    $where[] = "bi.court = :court";
    $params['court'] = $courtFilter;
}
if ($programFilter !== 'all') {
    $where[] = "bi.name = :program";
    $params['program'] = $programFilter;
}
if ($dateFilter !== '') {
    $where[] = "(bi.booking_date LIKE :date_filter OR DATE(b.created_at) = :date_filter_exact)";
    $params['date_filter'] = '%' . date('F j, Y', strtotime($dateFilter)) . '%';
    $params['date_filter_exact'] = $dateFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$bookings = booking_rows($pdo, "
    SELECT b.*, u.name AS user_name, u.email AS user_email,
           GROUP_CONCAT(DISTINCT bi.name ORDER BY bi.id SEPARATOR ', ') AS program_names,
           GROUP_CONCAT(DISTINCT bi.court ORDER BY bi.id SEPARATOR ', ') AS courts,
           SUM(bi.quantity) AS players,
           MIN(bi.booking_date) AS booking_date,
           MIN(bi.booking_time) AS booking_time
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN booking_items bi ON bi.booking_id = b.id
    $whereSql
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 10
", $params);

$allBookingItems = booking_rows($pdo, "
    SELECT bi.*, b.id AS booking_id, b.reference, b.status, b.payment_status, b.total, u.name AS user_name, u.email AS user_email
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    LEFT JOIN users u ON u.id = b.user_id
    ORDER BY b.created_at DESC, bi.id ASC
    LIMIT 80
");

$currentBooking = $bookingId ? $adminService->getBookingDetail($bookingId) : null;

$totalBookings = (int) booking_scalar($pdo, 'SELECT COUNT(*) FROM bookings');
$weekBookings = (int) booking_scalar($pdo, 'SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$pendingPayments = (int) booking_scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE LOWER(payment_status) LIKE '%pending%' OR LOWER(payment_status) = 'pay on site'");
$todaySessions = (int) booking_scalar($pdo, 'SELECT COUNT(*) FROM booking_items WHERE booking_date = ?', [$todayBookingLabel]);
$monthlyRevenue = (float) booking_scalar($pdo, "SELECT COALESCE(SUM(total), 0) FROM bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND (LOWER(payment_status) IN ('completed', 'paid') OR LOWER(payment_status) = 'pay on site')");
$courts = booking_rows($pdo, 'SELECT name FROM courts ORDER BY id ASC');
$programs = booking_rows($pdo, 'SELECT DISTINCT name FROM booking_items ORDER BY name ASC');

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
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'export' => '<path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'peso' => '<path d="M8 5h6a4 4 0 0 1 0 8H8M8 5v14M5 9h12M5 13h9"/>',
];

function admin_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['home']) . '</svg>';
}

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [
        ['All Bookings', 'manage-bookings.php', 'table'],
        ['Calendar View', 'manage-bookings.php?view=calendar', 'calendar'],
    ]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php', 'key' => 'users', 'icon' => 'users', 'children' => [
        ['Players', 'manage-users.php?role=player', ''],
        ['Coaches', 'manage-users.php?role=coach', ''],
    ]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php', 'key' => 'courts', 'icon' => 'courts', 'children' => [
        ['Court Green', 'manage-events.php?court=green', ''],
        ['Court Pink', 'manage-events.php?court=pink', ''],
    ]],
    ['type' => 'group', 'label' => 'Programs', 'href' => 'manage-events.php', 'key' => 'events', 'icon' => 'target', 'children' => [
        ['Social Play', 'manage-events.php?program=social-play', ''],
        ['Private Sessions', 'manage-events.php?program=private', ''],
    ]],
    ['type' => 'group', 'label' => 'Content', 'href' => 'notifications.php', 'key' => 'content', 'icon' => 'image', 'children' => [
        ['Photos', 'notifications.php?content=photos', ''],
        ['Catalogs', 'notifications.php?content=catalogs', ''],
    ]],
    ['type' => 'single', 'label' => 'Promotions', 'href' => 'notifications.php?type=promotion', 'key' => 'promotions', 'icon' => 'tag'],
    ['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
    ['type' => 'single', 'label' => 'Settings', 'href' => 'manage-users.php?id=' . (int) ($_SESSION['user']['id'] ?? 0), 'key' => 'settings', 'icon' => 'gear'],
];

$weekDays = [];
$start = $today->modify('sunday this week');
for ($i = 0; $i < 7; $i++) {
    $day = $start->modify('+' . $i . ' days');
    $weekDays[] = [
        'label' => strtoupper($day->format('D')),
        'date' => $day->format('M j'),
        'match' => $day->format('l, F j, Y'),
        'today' => $day->format('Y-m-d') === $todaySql,
    ];
}

$calendarLanes = [
    ['Court Green', 'green', 'img/court/court green-1.png'],
    ['Court Pink', 'pink', 'img/court/court pink-1.webp'],
    ['Social Play Area', 'orange', 'img/court/social play-1.png'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>">
            <img src="<?php echo booking_asset('img/LM-DGreen.png'); ?>" alt="Pickled" />
            <span>Admin</span>
        </a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group">
                        <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                            <?php echo admin_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                        <div class="admin-nav-children">
                            <?php foreach ($item['children'] as [$childLabel, $childHref, $childView]): ?>
                                <a class="<?php echo $childView && $view === $childView ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                        <?php echo admin_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-user">
            <div class="admin-avatar"><?php echo htmlspecialchars(strtoupper(substr($adminName, 0, 1))); ?></div>
            <div><strong><?php echo htmlspecialchars($adminName); ?></strong><span>Super Admin</span></div>
        </div>
    </aside>

    <main class="admin-dashboard-main bookings-main">
        <header class="admin-topbar">
            <div><h1><?php echo $view === 'calendar' ? 'Calendar View' : 'All Bookings'; ?></h1></div>
            <div class="admin-topbar-actions">
                <button class="admin-date-pill" type="button"><?php echo admin_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button>
                <a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>" aria-label="Notifications"><?php echo admin_icon($icons, 'bell'); ?><?php if ($pendingPayments > 0): ?><span><?php echo min($pendingPayments, 9); ?></span><?php endif; ?></a>
                <div class="admin-profile">
                    <div class="admin-avatar"><?php echo htmlspecialchars(strtoupper(substr($adminName, 0, 1))); ?></div>
                    <div><strong><?php echo htmlspecialchars($adminName); ?></strong><span>Super Admin</span></div>
                    <form method="post" action="<?php echo pickled_admin_url('admin-logout.php'); ?>"><input type="hidden" name="csrf_token" value="<?php echo $logoutCsrf; ?>"><button type="submit" aria-label="Logout">⌄</button></form>
                </div>
            </div>
        </header>

        <section class="bookings-hero">
            <div>
                <h2><?php echo $view === 'calendar' ? 'Calendar View' : 'All Bookings'; ?></h2>
                <p><?php echo $view === 'calendar' ? 'Visualize bookings across all courts.' : 'Manage court reservations, lessons, social play, and private sessions.'; ?></p>
            </div>
            <div class="bookings-hero-actions">
                <form class="booking-search" method="get">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <?php echo admin_icon($icons, 'search'); ?>
                    <input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search bookings">
                </form>
                <button class="bookings-button ghost" type="button"><?php echo admin_icon($icons, 'export'); ?> Export</button>
                <a class="bookings-button primary" href="<?php echo pickled_admin_url('manage-bookings.php'); ?>">New Booking</a>
            </div>
        </section>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

        <?php if ($view === 'table'): ?>
            <section class="booking-summary-grid">
                <article><span>Total Bookings</span><strong><?php echo number_format($totalBookings); ?></strong><small>+<?php echo number_format($weekBookings); ?> this week</small></article>
                <article><span>Pending Payments</span><strong><?php echo number_format($pendingPayments); ?></strong><small>Need Review</small></article>
                <article><span>Today's Sessions</span><strong><?php echo number_format($todaySessions); ?></strong><small>Across all courts</small></article>
                <article><span>Revenue</span><strong>₱<?php echo number_format($monthlyRevenue, 0); ?></strong><small>This Month</small></article>
            </section>
        <?php endif; ?>

        <form class="booking-filter-bar" method="get">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <label class="filter-search"><?php echo admin_icon($icons, 'search'); ?><input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search by reference, user, or email"></label>
            <select name="court"><option value="all">All Courts</option><?php foreach ($courts as $court): ?><option value="<?php echo htmlspecialchars($court['name']); ?>" <?php echo $courtFilter === $court['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($court['name']); ?></option><?php endforeach; ?></select>
            <select name="program"><option value="all">All Programs</option><?php foreach ($programs as $program): ?><option value="<?php echo htmlspecialchars($program['name']); ?>" <?php echo $programFilter === $program['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($program['name']); ?></option><?php endforeach; ?></select>
            <select name="status"><option value="all">All Statuses</option><?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option><?php endforeach; ?></select>
            <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
            <button type="submit">Apply</button>
            <div class="view-toggle"><a class="<?php echo $view === 'table' ? 'active' : ''; ?>" href="<?php echo pickled_admin_url('manage-bookings.php?view=table'); ?>">Table View</a><a class="<?php echo $view === 'calendar' ? 'active' : ''; ?>" href="<?php echo pickled_admin_url('manage-bookings.php?view=calendar'); ?>">Calendar View</a></div>
        </form>

        <?php if ($view === 'table'): ?>
            <section class="booking-table-card">
                <div class="booking-management-table">
                    <div class="booking-management-row booking-management-head"><span>Reference</span><span>Player</span><span>Program</span><span>Court</span><span>Date</span><span>Time</span><span>Players</span><span>Status</span><span>Payment</span><span>Amount</span><span>Actions</span></div>
                    <?php foreach ($bookings as $booking): ?>
                        <?php $statusKey = booking_status_key((string) $booking['status']); $paymentKey = booking_payment_key((string) $booking['payment_status']); ?>
                        <div class="booking-management-row">
                            <span class="booking-ref"><?php echo htmlspecialchars($booking['reference']); ?></span>
                            <span><strong><?php echo htmlspecialchars($booking['user_name'] ?? 'Guest'); ?></strong><small><?php echo htmlspecialchars($booking['user_email'] ?? ''); ?></small></span>
                            <span><?php echo htmlspecialchars($booking['program_names'] ?: 'Booking'); ?></span>
                            <span><?php echo htmlspecialchars($booking['courts'] ?: 'Any Court'); ?></span>
                            <span><?php echo htmlspecialchars($booking['booking_date'] ?: date('M j, Y', strtotime($booking['created_at']))); ?></span>
                            <span><?php echo htmlspecialchars($booking['booking_time'] ?: '-'); ?></span>
                            <span><?php echo (int) ($booking['players'] ?? 1); ?></span>
                            <span><em class="status-pill status-<?php echo $statusKey; ?>"><?php echo htmlspecialchars(pickled_booking_status_label($booking['status'])); ?></em></span>
                            <span><em class="status-pill payment-<?php echo $paymentKey; ?>"><?php echo htmlspecialchars($booking['payment_status']); ?></em></span>
                            <span>₱<?php echo number_format((float) $booking['total'], 2); ?></span>
                            <span class="row-actions">
                                <a href="<?php echo pickled_admin_url('manage-bookings.php?view=table&id=' . (int) $booking['id']); ?>"><?php echo admin_icon($icons, 'eye'); ?> View</a>
                                <details class="row-more">
                                    <summary aria-label="More actions"><?php echo admin_icon($icons, 'more'); ?></summary>
                                    <div>
                                        <a href="<?php echo pickled_admin_url('manage-bookings.php?view=table&id=' . (int) $booking['id']); ?>">View Details</a>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>"><input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>"><button name="action" value="approve_payment">Confirm Payment</button></form>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>"><input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>"><input type="hidden" name="reason" value="Payment rejected by admin"><button name="action" value="reject_payment">Reject Payment</button></form>
                                        <a href="<?php echo pickled_admin_url('manage-bookings.php?view=table&id=' . (int) $booking['id']); ?>">Edit Booking</a>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>"><input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>"><input type="hidden" name="status" value="Cancelled"><button name="action" value="update_status">Cancel Booking</button></form>
                                    </div>
                                </details>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$bookings): ?><p class="empty-state">No bookings found.</p><?php endif; ?>
                </div>
                <footer class="table-pagination"><span>Showing <?php echo count($bookings); ?> of <?php echo number_format($totalBookings); ?> bookings</span><div><button disabled>‹</button><button class="active">1</button><button>2</button><button>3</button><button>›</button></div></footer>
            </section>
        <?php else: ?>
            <section class="calendar-workspace">
                <aside class="court-lane-cards">
                    <?php foreach ($calendarLanes as [$courtName, $tone, $image]): ?>
                        <article class="court-mini-card <?php echo $tone; ?>"><img src="<?php echo booking_asset($image); ?>" alt="<?php echo htmlspecialchars($courtName); ?>"><strong><?php echo htmlspecialchars($courtName); ?></strong><span>Capacity: 24 slots</span><div><i style="width: <?php echo $tone === 'orange' ? 60 : ($tone === 'pink' ? 45 : 75); ?>%"></i></div></article>
                    <?php endforeach; ?>
                </aside>
                <div class="week-calendar">
                    <div class="calendar-header"><span>Time</span><?php foreach ($weekDays as $day): ?><strong class="<?php echo $day['today'] ? 'today' : ''; ?>"><?php echo $day['label']; ?><small><?php echo $day['date']; ?></small></strong><?php endforeach; ?></div>
                    <?php foreach (range(8, 21) as $hour): ?>
                        <div class="calendar-hour"><time><?php echo date('g:00 A', strtotime($hour . ':00')); ?></time><?php foreach ($weekDays as $day): ?><div class="calendar-cell">
                            <?php foreach ($allBookingItems as $item): ?>
                                <?php if (($item['booking_date'] ?? '') === $day['match'] && str_starts_with((string) $item['booking_time'], date('h:00 A', strtotime($hour . ':00')))): ?>
                                    <?php $tone = str_contains(strtolower($item['court']), 'pink') ? 'pink' : (str_contains(strtolower($item['name']), 'social') ? 'orange' : 'green'); ?>
                                    <a class="calendar-event <?php echo $tone; ?>" href="<?php echo pickled_admin_url('manage-bookings.php?view=calendar&id=' . (int) $item['booking_id']); ?>"><strong><?php echo htmlspecialchars($item['name']); ?></strong><span><?php echo htmlspecialchars($item['booking_time']); ?></span><small><?php echo htmlspecialchars($item['user_name'] ?? 'Guest'); ?></small></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div><?php endforeach; ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php if ($currentBooking): ?>
    <div class="booking-drawer-backdrop"><a href="<?php echo pickled_admin_url('manage-bookings.php?view=' . $view); ?>" aria-label="Close"></a></div>
    <aside class="booking-drawer">
        <header><div><span>Booking Details</span><h2><?php echo htmlspecialchars($currentBooking['reference']); ?></h2></div><a href="<?php echo pickled_admin_url('manage-bookings.php?view=' . $view); ?>">×</a></header>
        <section><h3>Booking Information</h3><p><strong>Date</strong><?php echo htmlspecialchars($currentBooking['items'][0]['booking_date'] ?? date('M j, Y', strtotime($currentBooking['created_at']))); ?></p><p><strong>Time</strong><?php echo htmlspecialchars($currentBooking['items'][0]['booking_time'] ?? '-'); ?></p><p><strong>Program</strong><?php echo htmlspecialchars($currentBooking['items'][0]['name'] ?? 'Booking'); ?></p><p><strong>Court</strong><?php echo htmlspecialchars($currentBooking['items'][0]['court'] ?? 'Any Court'); ?></p><p><strong>Players</strong><?php echo array_sum(array_map(fn($item) => (int) $item['quantity'], $currentBooking['items'] ?? [])); ?></p></section>
        <section><h3>Customer Information</h3><p><strong>Name</strong><?php echo htmlspecialchars($currentBooking['user']['name'] ?? 'Guest'); ?></p><p><strong>Email</strong><?php echo htmlspecialchars($currentBooking['user']['email'] ?? '-'); ?></p><p><strong>Membership</strong>Standard</p></section>
        <section><h3>Payment Information</h3><p><strong>Amount</strong>₱<?php echo number_format((float) $currentBooking['total'], 2); ?></p><p><strong>Method</strong><?php echo htmlspecialchars($currentBooking['payment_method']); ?></p><p><strong>Status</strong><em class="status-pill payment-<?php echo booking_payment_key($currentBooking['payment_status']); ?>"><?php echo htmlspecialchars($currentBooking['payment_status']); ?></em></p></section>
        <form class="drawer-actions" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>"><input type="hidden" name="booking_id" value="<?php echo (int) $currentBooking['id']; ?>"><button name="action" value="approve_payment" class="approve">Approve Payment</button><button name="action" value="reject_payment" class="reject">Reject Payment</button><button name="action" value="update_status" onclick="this.form.status.value='Confirmed'">Confirm Booking</button><input type="hidden" name="status" value=""></form>
    </aside>
<?php endif; ?>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
