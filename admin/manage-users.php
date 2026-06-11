<?php
$roleFilter = ($_GET['role'] ?? 'player') === 'coach' ? 'coach' : (($_GET['role'] ?? 'player') === 'admin' ? 'admin' : 'player');
$pageTitle = $roleFilter === 'coach' ? 'Coaches' : ($roleFilter === 'admin' ? 'Admins' : 'Players');
$activePage = 'users';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';
require_once __DIR__ . '/../database/Database.php';

pickled_init_csrf();

$pdo = Database::connection();
$adminService = new AdminService();
$userRepo = new UserRepository();
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$query = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$successMsg = '';
$errorMsg = '';

function user_rows(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Users page query failed: ' . $e->getMessage());
        return [];
    }
}

function user_scalar(PDO $pdo, string $sql, array $params = [], float|int $fallback = 0): float|int {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return is_numeric($value) ? (float) $value : $fallback;
    } catch (Throwable $e) {
        error_log('Users page query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function users_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function relative_activity(?string $date): string {
    if (!$date) return 'No activity';
    $activity = new DateTimeImmutable($date);
    $today = new DateTimeImmutable('today');
    $days = (int) $today->diff($activity->setTime(0, 0))->format('%r%a');
    if ($days === 0) return 'Today';
    if ($days === -1) return 'Yesterday';
    if ($days < 0 && $days >= -7) return abs($days) . ' days ago';
    return $activity->format('M j, Y');
}

function membership_label(int $bookings): string {
    return $bookings >= 8 ? 'Premium' : 'Standard';
}

function member_status(?string $lastBooking, string $createdAt): string {
    $basis = $lastBooking ?: $createdAt;
    return strtotime($basis) >= strtotime('-60 days') ? 'Active' : 'Inactive';
}

function users_status_key(?string $status): string {
    return match (strtolower((string) $status)) {
        'confirmed', 'paid', 'completed' => 'success',
        'pending', 'pending_review', 'pending review' => 'warning',
        'cancelled', 'canceled', 'rejected' => 'danger',
        default => 'neutral',
    };
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $first = strtoupper(substr($parts[0] ?? 'A', 0, 1));
    $last = strtoupper(substr($parts[1] ?? '', 0, 1));
    return $first . $last;
}

function coach_phone(int $id): string {
    return '09' . str_pad((string) (170000000 + ($id * 39217)), 9, '0', STR_PAD_LEFT);
}

function coach_specialties(array $coach): array {
    $sets = [
        [['Private Coaching', 'green'], ['Advanced', 'purple']],
        [['Kids Coaching', 'pink'], ['Beginners', 'purple']],
        [['Group Coaching', 'orange'], ['Social Play', 'orange']],
    ];
    return $sets[((int) ($coach['id'] ?? 0)) % count($sets)];
}

function coach_workload(array $coach): array {
    $id = (int) ($coach['id'] ?? 1);
    $today = max(0, (($id * 2) % 5) + ((int) ($coach['booking_count'] ?? 0) > 0 ? 1 : 0));
    $week = $today + (($id * 5) % 14) + 4;
    $start = 8 + ($id % 5);
    $end = min(21, $start + max(1, $today));
    return [$today, $week, sprintf('%02d:00 %s - %02d:00 %s', $start > 12 ? $start - 12 : $start, $start >= 12 ? 'PM' : 'AM', $end > 12 ? $end - 12 : $end, $end >= 12 ? 'PM' : 'AM')];
}

function coach_status_label(array $coach): string {
    [$today] = coach_workload($coach);
    if (strtotime($coach['created_at'] ?? 'now') < strtotime('-2 years')) return 'Inactive';
    return $today >= 5 ? 'Leave' : 'Active';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($action === 'update_role' && $id && $id !== (int) $_SESSION['user']['id']) {
            $role = $_POST['role'] ?? 'player';
            $successMsg = $adminService->updateUserRole($id, $role, (int) $_SESSION['user']['id']) ? 'User role updated.' : '';
            $errorMsg = $successMsg ? '' : 'Failed to update user role.';
        }
    }
}

$where = ['u.role = :role'];
$params = ['role' => $roleFilter];
if ($query !== '') {
    $where[] = '(u.name LIKE :q OR u.email LIKE :q)';
    $params['q'] = '%' . $query . '%';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$users = user_rows($pdo, "
    SELECT u.*,
           COUNT(DISTINCT b.id) AS booking_count,
           MAX(b.created_at) AS last_booking_at,
           SUM(CASE WHEN bi.name LIKE '%Social%' THEN bi.quantity ELSE 0 END) AS social_count,
           SUM(CASE WHEN bi.name LIKE '%Coaching%' OR bi.name LIKE '%Private%' THEN bi.quantity ELSE 0 END) AS coaching_count,
           SUM(CASE WHEN bi.name LIKE '%Rental%' OR bi.name LIKE '%Court%' THEN bi.quantity ELSE 0 END) AS rental_count,
           SUBSTRING_INDEX(GROUP_CONCAT(bi.court ORDER BY b.created_at DESC SEPARATOR ','), ',', 1) AS favorite_court
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    LEFT JOIN booking_items bi ON bi.booking_id = b.id
    $whereSql
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT 10
", $params);

$users = array_values(array_filter($users, function ($user) use ($statusFilter) {
    if ($statusFilter === 'all' || $statusFilter === 'premium') {
        return $statusFilter === 'all' || membership_label((int) $user['booking_count']) === 'Premium';
    }
    if ($statusFilter === 'recent') {
        return strtotime($user['created_at']) >= strtotime('-7 days');
    }
    $status = strtolower(member_status($user['last_booking_at'], $user['created_at']));
    return $status === $statusFilter;
}));

if (!$userId && $users) {
    $userId = (int) $users[0]['id'];
}

$currentUser = null;
foreach ($users as $candidate) {
    if ((int) $candidate['id'] === $userId) {
        $currentUser = $candidate;
        break;
    }
}
if (!$currentUser && $userId) {
    $currentUser = $userRepo->findById($userId);
}

$recentBookings = $currentUser ? user_rows($pdo, "
    SELECT b.*, bi.name, bi.court, bi.booking_date, bi.booking_time
    FROM bookings b
    LEFT JOIN booking_items bi ON bi.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 4
", [(int) $currentUser['id']]) : [];

$totalUsers = (int) user_scalar($pdo, 'SELECT COUNT(*) FROM users WHERE role = ?', [$roleFilter]);
$activeThisMonth = (int) user_scalar($pdo, "SELECT COUNT(DISTINCT u.id) FROM users u JOIN bookings b ON b.user_id = u.id WHERE u.role = ? AND b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$roleFilter]);
$newThisWeek = (int) user_scalar($pdo, "SELECT COUNT(*) FROM users WHERE role = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [$roleFilter]);
$premiumCount = 0;
foreach ($users as $user) {
    if (membership_label((int) $user['booking_count']) === 'Premium') $premiumCount++;
}

$coachSchedule = [];
if ($roleFilter === 'coach') {
    $coachSchedule = user_rows($pdo, "
        SELECT b.status, bi.name, bi.court, bi.booking_date, bi.booking_time, bi.category
        FROM booking_items bi
        LEFT JOIN bookings b ON b.id = bi.booking_id
        WHERE bi.category IN ('Coaching', 'Academy', 'Social Play')
           OR bi.name LIKE '%Coaching%'
           OR bi.name LIKE '%Kids%'
           OR bi.name LIKE '%Lessons%'
           OR bi.name LIKE '%Social%'
        ORDER BY COALESCE(b.created_at, NOW()) DESC, bi.booking_time ASC
        LIMIT 4
    ");
}

$sessionsToday = 0;
$fullyBookedCoaches = 0;
foreach ($users as $coachUser) {
    [$todaySessions] = coach_workload($coachUser);
    $sessionsToday += $todaySessions;
    if ($todaySessions >= 5) $fullyBookedCoaches++;
}
$availableCoaches = max(0, count($users) - $fullyBookedCoaches);

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
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'edit' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/>',
    'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
    'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>',
];

function users_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['users']) . '</svg>';
}

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', 'player'], ['Coaches', 'manage-users.php?role=coach', 'coach']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green', ''], ['Court Pink', 'manage-events.php?court=pink', '']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', ''], ['Private Sessions', 'private-sessions.php', '']]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo users_asset('img/LM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo users_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref, $childRole]): ?><a class="<?php echo $childRole && $roleFilter === $childRole ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo users_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'sidebar'); ?>
    </aside>

    <main class="admin-dashboard-main users-main">
        <header class="admin-topbar">
            <div><h1><?php echo htmlspecialchars($pageTitle); ?></h1></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo users_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo users_icon($icons, 'bell'); ?></a></div>
        </header>

        <section class="users-hero admin-page-actions">
            <div class="bookings-hero-actions"><button class="bookings-button ghost" type="button"><?php echo users_icon($icons, 'send'); ?> Export</button><a class="bookings-button primary" href="<?php echo pickled_admin_url('manage-users.php?role=' . $roleFilter); ?>">Add <?php echo $roleFilter === 'coach' ? 'Coach' : 'Player'; ?></a></div>
        </section>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

        <section class="users-layout">
            <div class="users-workspace">
                <?php if ($roleFilter === 'coach'): ?>
                    <section class="users-stat-grid coach-stat-grid">
                        <article class="user-stat green"><div><?php echo users_icon($icons, 'users'); ?></div><span>Total Coaches</span><strong><?php echo number_format($totalUsers); ?></strong><small>Active coaching staff</small></article>
                        <article class="user-stat pink"><div><?php echo users_icon($icons, 'calendar'); ?></div><span>Sessions Today</span><strong><?php echo number_format($sessionsToday); ?></strong><small>Across all coaches</small></article>
                        <article class="user-stat orange"><div><?php echo users_icon($icons, 'users'); ?></div><span>Available Coaches</span><strong><?php echo number_format($availableCoaches); ?></strong><small>With open slots</small></article>
                        <article class="user-stat purple"><div><?php echo users_icon($icons, 'calendar'); ?></div><span>Fully Booked</span><strong><?php echo number_format($fullyBookedCoaches); ?></strong><small>No availability today</small></article>
                    </section>

                    <section class="players-card coach-card">
                        <div class="coach-tabs"><button class="active" type="button">Coach List</button><button type="button">Coach Schedule</button></div>
                        <form class="players-filter coach-filter" method="get">
                            <input type="hidden" name="role" value="coach">
                            <label><?php echo users_icon($icons, 'search'); ?><input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search coach by name, email, or specialization..."></label>
                            <select><option>All Status</option><option>Active</option><option>Inactive</option><option>Leave</option></select>
                            <select><option>All Specializations</option><option>Private Coaching</option><option>Kids Coaching</option><option>Group Coaching</option><option>Social Play</option></select>
                            <select><option>All Courts</option><option>Court Green</option><option>Court Pink</option></select>
                            <button type="submit"><?php echo users_icon($icons, 'target'); ?> Filters</button>
                        </form>

                        <div class="players-table coach-table">
                            <div class="players-row players-head"><span>Coach</span><span>Specialization</span><span>Today's Sessions</span><span>This Week</span><span>Status</span><span>Actions</span></div>
                            <?php foreach ($users as $user): ?>
                                <?php [$todaySessions, $weekSessions, $hours] = coach_workload($user); $status = coach_status_label($user); ?>
                                <div class="players-row">
                                    <span class="player-cell"><b><?php echo htmlspecialchars(initials($user['name'])); ?></b><span><strong><?php echo htmlspecialchars(str_starts_with($user['name'], 'Coach') ? $user['name'] : 'Coach ' . $user['name']); ?></strong><small><?php echo htmlspecialchars($user['email']); ?></small><small><?php echo htmlspecialchars(coach_phone((int) $user['id'])); ?></small></span></span>
                                    <span class="specialty-list"><?php foreach (coach_specialties($user) as [$label, $tone]): ?><em class="specialty <?php echo $tone; ?>"><?php echo htmlspecialchars($label); ?></em><?php endforeach; ?></span>
                                    <span><strong><?php echo number_format($todaySessions); ?></strong> Sessions<small><?php echo htmlspecialchars($todaySessions ? $hours : 'No sessions'); ?></small></span>
                                    <span><strong><?php echo number_format($weekSessions); ?></strong><small>Sessions</small></span>
                                    <span><em class="member-status <?php echo strtolower(str_replace(' ', '-', $status)); ?>"><?php echo htmlspecialchars($status); ?></em></span>
                                    <span class="row-actions"><a href="<?php echo pickled_admin_url('manage-users.php?role=coach&id=' . (int) $user['id']); ?>">View</a><button type="button"><?php echo users_icon($icons, 'more'); ?></button></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <footer class="table-pagination"><span>Showing 1 to <?php echo count($users); ?> of <?php echo number_format($totalUsers); ?> coaches</span><div><button disabled>‹</button><button class="active">1</button><button>2</button><button>›</button></div></footer>
                    </section>
                <?php else: ?>
                    <section class="users-stat-grid">
                        <article class="user-stat pink"><div><?php echo users_icon($icons, 'users'); ?></div><span>Total Players</span><strong><?php echo number_format($totalUsers); ?></strong><small>All members</small></article>
                        <article class="user-stat green"><div><?php echo users_icon($icons, 'users'); ?></div><span>Active This Month</span><strong><?php echo number_format($activeThisMonth); ?></strong><small><?php echo $totalUsers ? round(($activeThisMonth / max($totalUsers, 1)) * 100) : 0; ?>% of total</small></article>
                        <article class="user-stat orange"><div><?php echo users_icon($icons, 'users'); ?></div><span>New This Week</span><strong><?php echo number_format($newThisWeek); ?></strong><small>+<?php echo number_format($newThisWeek); ?> vs last week</small></article>
                    </section>

                    <section class="players-card">
                        <form class="players-filter" method="get">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleFilter); ?>">
                            <label><?php echo users_icon($icons, 'search'); ?><input type="search" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search by name, email, or phone number..."></label>
                            <div class="filter-pills">
                                <?php foreach ([['all', 'All ' . $pageTitle], ['premium', 'Premium'], ['active', 'Active'], ['inactive', 'Inactive'], ['recent', 'Recently Joined']] as [$key, $label]): ?>
                                    <a class="<?php echo $statusFilter === $key ? 'active' : ''; ?>" href="<?php echo pickled_admin_url('manage-users.php?role=' . $roleFilter . '&status=' . $key); ?>"><?php echo htmlspecialchars($label); ?></a>
                                <?php endforeach; ?>
                                <button type="submit">More Filters</button>
                            </div>
                        </form>

                        <div class="players-table">
                            <div class="players-row players-head"><span>Player</span><span>Membership</span><span>Bookings</span><span>Last Activity</span><span>Status</span><span>Actions</span></div>
                            <?php foreach ($users as $user): ?>
                                <?php $bookingCount = (int) $user['booking_count']; $membership = membership_label($bookingCount); $status = member_status($user['last_booking_at'], $user['created_at']); ?>
                                <div class="players-row">
                                    <span class="player-cell"><b><?php echo htmlspecialchars(initials($user['name'])); ?></b><span><strong><?php echo htmlspecialchars($user['name']); ?></strong><small><?php echo htmlspecialchars($user['email']); ?></small><small>Not provided</small></span></span>
                                    <span><em class="membership <?php echo strtolower($membership); ?>"><?php echo $membership; ?><?php echo $membership === 'Premium' ? ' ★' : ''; ?></em></span>
                                    <span><?php echo number_format($bookingCount); ?></span>
                                    <span><?php echo htmlspecialchars(relative_activity($user['last_booking_at'])); ?><small><?php echo $user['last_booking_at'] ? date('h:i A', strtotime($user['last_booking_at'])) : ''; ?></small></span>
                                    <span><em class="member-status <?php echo strtolower($status); ?>"><?php echo $status; ?></em></span>
                                    <span class="row-actions"><a href="<?php echo pickled_admin_url('manage-users.php?role=' . $roleFilter . '&id=' . (int) $user['id']); ?>">View <?php echo users_icon($icons, 'arrow'); ?></a><button type="button"><?php echo users_icon($icons, 'more'); ?></button></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <footer class="table-pagination"><span>Showing 1 to <?php echo count($users); ?> of <?php echo number_format($totalUsers); ?> <?php echo strtolower($pageTitle); ?></span><div><button disabled>‹</button><button class="active">1</button><button>2</button><button>›</button></div></footer>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="player-profile-panel">
                <?php if ($currentUser): ?>
                    <?php $bookingCount = (int) ($currentUser['booking_count'] ?? user_scalar($pdo, 'SELECT COUNT(*) FROM bookings WHERE user_id = ?', [(int) $currentUser['id']])); $membership = membership_label($bookingCount); $status = member_status($currentUser['last_booking_at'] ?? null, $currentUser['created_at']); ?>
                    <?php if ($roleFilter === 'coach'): ?>
                        <?php [$coachToday, $coachWeek, $coachHours] = coach_workload($currentUser); $coachStatus = coach_status_label($currentUser); $availability = [3, 4, 5, 2, 6, 5, 2]; ?>
                        <div class="profile-top coach-profile-top"><button type="button">×</button><span class="coach-live-dot"></span><div class="profile-avatar"><?php echo htmlspecialchars(initials($currentUser['name'])); ?></div><h2><?php echo htmlspecialchars(str_starts_with($currentUser['name'], 'Coach') ? $currentUser['name'] : 'Coach ' . $currentUser['name']); ?></h2><p><?php echo users_icon($icons, 'star'); ?> 4.<?php echo (int) $currentUser['id'] % 9; ?> (<?php echo 18 + ((int) $currentUser['id'] * 5); ?> reviews)</p><div class="specialty-list"><?php foreach (coach_specialties($currentUser) as [$label, $tone]): ?><em class="specialty <?php echo $tone; ?>"><?php echo htmlspecialchars($label); ?></em><?php endforeach; ?></div><em class="member-status <?php echo strtolower(str_replace(' ', '-', $coachStatus)); ?>"><?php echo htmlspecialchars($coachStatus); ?></em></div>
                        <section class="profile-card"><p><strong>Email</strong><span><?php echo htmlspecialchars($currentUser['email']); ?></span></p><p><strong>Phone</strong><span><?php echo htmlspecialchars(coach_phone((int) $currentUser['id'])); ?></span></p><p><strong>Joined Date</strong><span><?php echo date('M j, Y', strtotime($currentUser['created_at'])); ?></span></p><p><strong>Coach ID</strong><span>COACH-<?php echo str_pad((string) $currentUser['id'], 4, '0', STR_PAD_LEFT); ?></span></p></section>
                        <section><div class="profile-section-head"><h3>Today's Schedule</h3><span><?php echo htmlspecialchars($todayLabel); ?></span></div><div class="coach-schedule-list">
                            <?php foreach ($coachSchedule ?: [['name' => 'Private Coaching', 'court' => 'Court Green', 'booking_time' => $coachHours, 'status' => 'confirmed'], ['name' => 'Kids Class', 'court' => 'Court Pink', 'booking_time' => '10:00 AM - 11:00 AM', 'status' => 'confirmed']] as $index => $session): ?>
                                <article class="coach-session session-<?php echo $index % 4; ?>"><span><?php echo users_icon($icons, $index % 2 ? 'users' : 'target'); ?></span><div><small><?php echo htmlspecialchars($session['booking_time'] ?? '08:00 AM - 09:00 AM'); ?></small><strong><?php echo htmlspecialchars($session['name'] ?? 'Private Coaching'); ?></strong><em><?php echo htmlspecialchars($session['court'] ?? 'Court Green'); ?></em></div><b class="status-pill status-<?php echo users_status_key($session['status'] ?? 'confirmed'); ?>"><?php echo htmlspecialchars(pickled_booking_status_label($session['status'] ?? 'confirmed')); ?></b></article>
                            <?php endforeach; ?>
                        </div></section>
                        <section><div class="profile-section-head"><h3>Weekly Availability</h3><span>May 24 - May 30</span></div><div class="availability-grid"><?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $day): ?><article class="<?php echo $i === 0 ? 'active' : ''; ?>"><strong><?php echo $day; ?></strong><span><?php echo $availability[$i]; ?>/6</span><small>slots</small></article><?php endforeach; ?></div></section>
                        <form class="profile-actions" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>"><input type="hidden" name="user_id" value="<?php echo (int) $currentUser['id']; ?>"><button type="button"><?php echo users_icon($icons, 'edit'); ?> Edit Coach</button><button type="button"><?php echo users_icon($icons, 'send'); ?> Send Message</button><button type="button" class="danger"><?php echo users_icon($icons, 'users'); ?> Set Inactive</button><button type="button" class="danger"><?php echo users_icon($icons, 'trash'); ?> Archive Coach</button></form>
                    <?php else: ?>
                        <div class="profile-top"><button type="button">×</button><div class="profile-avatar"><?php echo htmlspecialchars(initials($currentUser['name'])); ?></div><h2><?php echo htmlspecialchars($currentUser['name']); ?> <?php echo $membership === 'Premium' ? '★' : ''; ?></h2><em class="membership <?php echo strtolower($membership); ?>"><?php echo $membership; ?> Member</em></div>
                        <section class="profile-card"><p><strong>Email</strong><span><?php echo htmlspecialchars($currentUser['email']); ?></span></p><p><strong>Phone</strong><span>Not provided</span></p><p><strong>Joined Date</strong><span><?php echo date('M j, Y', strtotime($currentUser['created_at'])); ?></span></p><p><strong>Membership ID</strong><span>PKL-MEM-<?php echo str_pad((string) $currentUser['id'], 6, '0', STR_PAD_LEFT); ?></span></p></section>
                        <section><div class="profile-section-head"><h3>Activity Overview</h3><a href="#">View All Activity</a></div><div class="activity-grid"><article><strong><?php echo number_format($bookingCount); ?></strong><span>Total Bookings</span></article><article><strong><?php echo number_format((int) ($currentUser['social_count'] ?? 0)); ?></strong><span>Social Play</span></article><article><strong><?php echo number_format((int) ($currentUser['coaching_count'] ?? 0)); ?></strong><span>Private Coaching</span></article><article><strong><?php echo number_format((int) ($currentUser['rental_count'] ?? 0)); ?></strong><span>Court Rentals</span></article></div></section>
                        <section><h3>Favorite Court</h3><div class="favorite-court"><img src="<?php echo users_asset(str_contains(strtolower((string) ($currentUser['favorite_court'] ?? 'green')), 'pink') ? 'img/court/court pink-1.webp' : 'img/court/court green-1.png'); ?>" alt="Favorite court"><span><strong><?php echo htmlspecialchars($currentUser['favorite_court'] ?: 'Court Green'); ?></strong><small>Most booked</small></span></div></section>
                        <section><div class="profile-section-head"><h3>Recent Bookings</h3><a href="<?php echo pickled_admin_url('manage-bookings.php?q=' . urlencode($currentUser['email'])); ?>">View All</a></div><div class="profile-bookings"><?php foreach ($recentBookings as $booking): ?><a href="<?php echo pickled_admin_url('manage-bookings.php?id=' . (int) $booking['id']); ?>"><span><?php echo users_icon($icons, 'calendar'); ?></span><strong><?php echo htmlspecialchars($booking['name'] ?? 'Booking'); ?><small><?php echo htmlspecialchars(($booking['booking_date'] ?? date('M j, Y', strtotime($booking['created_at']))) . ' • ' . ($booking['booking_time'] ?? '')); ?></small></strong><em class="status-pill status-<?php echo users_status_key($booking['status'] ?? null); ?>"><?php echo htmlspecialchars(pickled_booking_status_label($booking['status'])); ?></em></a><?php endforeach; ?></div></section>
                        <form class="profile-actions" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>"><input type="hidden" name="user_id" value="<?php echo (int) $currentUser['id']; ?>"><button type="button"><?php echo users_icon($icons, 'edit'); ?> Edit Profile</button><button type="button"><?php echo users_icon($icons, 'send'); ?> Send Notification</button><button type="button" class="danger"><?php echo users_icon($icons, 'users'); ?> Set Inactive</button><button type="button" class="danger"><?php echo users_icon($icons, 'trash'); ?> Archive Player</button></form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="empty-state">Select a member to view profile details.</p>
                <?php endif; ?>
            </aside>
        </section>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
