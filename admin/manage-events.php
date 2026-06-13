<?php
$programSlug = $_GET['program'] ?? '';
$isSocialPlay = $programSlug === 'social-play';
$courtSlug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_GET['court'] ?? 'green'))) ?: 'green';
$pageTitle = $isSocialPlay ? 'Social Play' : ($courtSlug === 'pink' ? 'Court Pink' : 'Court Green');
$activePage = $isSocialPlay ? 'events' : 'courts';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/services/CatalogService.php';
require_once __DIR__ . '/../app/services/SchedulingService.php';

pickled_init_csrf();

$catalogService = new CatalogService();
$schedulingService = new SchedulingService();
$adminId = (int) ($_SESSION['user']['id'] ?? 0);
$successMsg = '';
$errorMsg = '';
$pdo = null;

try {
    $pdo = Database::connection();
} catch (Throwable $e) {
    error_log('Court catalog database connection failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Invalid form submission. Please try again.';
    } else {
        try {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create_court') {
                $catalogService->createCourt($_POST, $adminId);
                $courtSlug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['slug'] ?? $_POST['name'] ?? $courtSlug))) ?: $courtSlug;
                $successMsg = 'Court added successfully.';
            } elseif ($action === 'update_court') {
                $catalogService->updateCourt((int) ($_POST['court_id'] ?? 0), $_POST, $adminId);
                $courtSlug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['slug'] ?? $courtSlug))) ?: $courtSlug;
                $successMsg = 'Court updated successfully.';
            } elseif ($action === 'set_court_status') {
                $catalogService->setCourtStatus((int) ($_POST['court_id'] ?? 0), (string) ($_POST['status'] ?? 'inactive'), $adminId);
                $successMsg = 'Court status updated successfully.';
            } elseif ($action === 'create_variant') {
                $catalogService->createVariant($_POST, $adminId);
                $successMsg = 'Booking variant added successfully.';
            } elseif ($action === 'update_variant') {
                $catalogService->updateVariant((int) ($_POST['variant_id'] ?? 0), $_POST, $adminId);
                $successMsg = 'Booking variant updated successfully.';
            } elseif ($action === 'set_variant_active') {
                $catalogService->setVariantActive((int) ($_POST['variant_id'] ?? 0), (string) ($_POST['active'] ?? '0') === '1', $adminId);
                $successMsg = 'Booking variant status updated successfully.';
            } elseif ($action === 'create_session') {
                $schedulingService->createSession($_POST, $adminId);
                $successMsg = 'Session created successfully.';
            } elseif ($action === 'update_session') {
                $schedulingService->updateSession((int) ($_POST['session_id'] ?? 0), $_POST, $adminId);
                $successMsg = 'Session updated successfully.';
            } elseif ($action === 'set_session_status') {
                $schedulingService->setSessionStatus((int) ($_POST['session_id'] ?? 0), (string) ($_POST['status'] ?? 'cancelled'), $adminId);
                $successMsg = 'Session status updated successfully.';
            }
        } catch (Throwable $e) {
            error_log('Court catalog action failed: ' . $e->getMessage());
            $errorMsg = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to save catalog changes.';
        }
    }
}

$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');

function court_rows(?PDO $pdo, string $sql, array $params = []): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Court page query failed: ' . $e->getMessage());
        return [];
    }
}

function court_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function court_public_url(string $path): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/manage-events.php');
    $position = strpos($script, '/admin/');
    $base = $position === false ? rtrim(dirname($script), '/') . '/' : substr($script, 0, $position + 1);
    return htmlspecialchars($base . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
}

function court_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['courts']) . '</svg>';
}

function service_icon_name(string $name): string {
    $lower = strtolower($name);
    if (str_contains($lower, 'lesson') || str_contains($lower, 'class')) return 'users';
    if (str_contains($lower, 'training') || str_contains($lower, 'tournament')) return 'target';
    if (str_contains($lower, 'private') || str_contains($lower, 'coaching')) return 'user';
    return 'courts';
}

function court_h(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function court_csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . court_h(pickled_csrf_token()) . '">';
}

$allCourts = [];
$allVariants = [];
$court = ['id' => 0, 'name' => $pageTitle, 'slug' => $courtSlug, 'status' => 'inactive'];
$services = [];
$socialServices = [];
$allSessions = [];
$coaches = [];

try {
    $allCourts = $catalogService->courts(true);
    $selectedCourt = $catalogService->courtBySlug($courtSlug, true);
    if (!$selectedCourt && $allCourts) {
        $courtSlug = (string) $allCourts[0]['slug'];
        $selectedCourt = $catalogService->courtBySlug($courtSlug, true);
    }
    if ($selectedCourt) {
        $court = $selectedCourt;
    }
    $services = $catalogService->variantsForCourtSlug($courtSlug, true);
    $socialServices = $catalogService->socialVariants(true);
    $allVariants = $catalogService->variants(true);
    $allSessions = $schedulingService->allSessions(true);
    $coaches = $schedulingService->coaches(false);
} catch (Throwable $e) {
    error_log('Court catalog load failed: ' . $e->getMessage());
    $errorMsg = $errorMsg ?: 'Catalog data is unavailable. Please apply the Court & Service Catalog schema.';
}

$pageTitle = $isSocialPlay ? 'Social Play' : (string) ($court['name'] ?? $pageTitle);
$socialSessions = court_rows($pdo, "
    SELECT s.*,
           DATE_FORMAT(s.session_date, '%W, %M %e, %Y') AS session_date,
           CONCAT(TIME_FORMAT(s.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(s.end_time, '%h:%i %p')) AS session_time,
           bv.name,
           bv.price,
           bv.duration_label,
           bv.capacity AS variant_capacity,
           c.name AS court_name
    FROM sessions s
    JOIN booking_variants bv ON bv.id = s.variant_id
    JOIN courts c ON c.id = bv.court_id
    WHERE bv.category = 'Social Play'
       OR bv.name LIKE '%Match%'
       OR bv.name LIKE '%Tournament%'
    ORDER BY s.session_date DESC, s.start_time DESC
    LIMIT 4
");
$socialParticipants = array_sum(array_map(fn($row) => (int) ($row['booked_count'] ?? 0), $socialSessions));
$socialRevenue = array_sum(array_map(fn($row) => (float) ($row['price'] ?? 0) * (int) ($row['booked_count'] ?? 0), $socialSessions));

$heroImage = $courtSlug === 'pink' ? 'img/court/court pink-1.webp' : 'img/court/court green-1.png';
$gallery = $courtSlug === 'pink'
    ? ['img/court/court pink-1.webp', 'img/court/court pink-2.png', 'img/court/court pink-3.png', 'img/court/academy.png']
    : ['img/court/court green-1.png', 'img/court/court green-2.png', 'img/court/court green-3.png', 'img/court/social play-1.png'];
$socialGallery = ['img/court/social play-1.png', 'img/court/social play-2.png', 'img/court/social play-3.png', 'img/court/court green-1.png', 'img/court/court pink-1.webp'];

$basePrice = $services ? min(array_map(fn($service) => (float) $service['price'], $services)) : ($courtSlug === 'pink' ? 400 : 600);
$capacity = $services ? max(array_map(fn($service) => (int) $service['capacity'], $services)) : 24;
$subtitle = $courtSlug === 'pink' ? 'Youth-friendly indoor court' : 'Main standard indoor court';
$accent = $courtSlug === 'pink' ? 'pink' : 'green';

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
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'edit' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>',
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
];

$courtNavChildren = array_map(
    static fn(array $item): array => [(string) $item['name'], 'manage-events.php?court=' . rawurlencode((string) $item['slug']), (string) $item['slug']],
    $allCourts
);

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', ''], ['Coaches', 'manage-users.php?role=coach', '']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => $courtNavChildren],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', 'social-play'], ['Private Sessions', 'private-sessions.php', 'private']]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo court_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo court_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref, $childKey]): ?><a class="<?php echo $childKey && (($activePage === 'courts' && $courtSlug === $childKey) || ($activePage === 'events' && $programSlug === $childKey)) ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo court_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main court-manager-main">
        <header class="admin-topbar">
            <div><h1><?php echo htmlspecialchars($pageTitle); ?> <span class="court-title-badge"><?php echo $isSocialPlay ? 'Active' : court_h(ucfirst((string) ($court['status'] ?? 'inactive'))); ?></span></h1><p class="court-breadcrumb"><?php echo $isSocialPlay ? 'Programs' : 'Courts'; ?> <?php echo court_icon($icons, 'arrow'); ?> <?php echo htmlspecialchars($pageTitle); ?></p><?php if ($isSocialPlay): ?><p class="program-subtitle">Community-driven pickleball sessions</p><?php endif; ?></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo court_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo court_icon($icons, 'bell'); ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo court_h($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo court_h($errorMsg); ?></div><?php endif; ?>

        <section class="catalog-admin-panel" aria-label="Court and service catalog management">
            <details id="catalog-add-court">
                <summary><?php echo court_icon($icons, 'plus'); ?> Add Court</summary>
                <form class="catalog-admin-form" method="post">
                    <?php echo court_csrf_input(); ?>
                    <label><span>Name</span><input type="text" name="name" placeholder="Court Blue" required></label>
                    <label><span>Slug</span><input type="text" name="slug" placeholder="blue" required></label>
                    <label><span>Status</span><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="maintenance">Maintenance</option></select></label>
                    <button class="bookings-button primary" type="submit" name="action" value="create_court">Add Court</button>
                </form>
            </details>

            <details id="catalog-add-variant">
                <summary><?php echo court_icon($icons, 'plus'); ?> Add Booking Variant</summary>
                <form class="catalog-admin-form catalog-admin-form-wide" method="post">
                    <?php echo court_csrf_input(); ?>
                    <label><span>Court</span><select name="court_id" required><?php foreach ($allCourts as $selectCourt): ?><option value="<?php echo (int) $selectCourt['id']; ?>"><?php echo court_h($selectCourt['name']); ?></option><?php endforeach; ?></select></label>
                    <label><span>Name</span><input type="text" name="name" placeholder="Court Rentals" required></label>
                    <label><span>Slug</span><input type="text" name="slug" placeholder="blue-court-rentals" required></label>
                    <label><span>Category</span><input type="text" name="category" placeholder="Court Rental" required></label>
                    <label><span>Duration</span><input type="text" name="duration_label" placeholder="1 hour" required></label>
                    <label><span>Price</span><input type="number" name="price" step="0.01" min="0" value="0.00" required></label>
                    <label><span>Limit</span><input type="number" name="participants_limit" min="1" value="1" required></label>
                    <label><span>Capacity</span><input type="number" name="capacity" min="1" value="8" required></label>
                    <label><span>Image</span><input type="text" name="image" placeholder="assets/img/court/example.png"></label>
                    <label class="catalog-check"><input type="checkbox" name="active" value="1" checked> Active</label>
                    <button class="bookings-button primary" type="submit" name="action" value="create_variant">Add Variant</button>
                </form>
            </details>

            <details id="catalog-add-session">
                <summary><?php echo court_icon($icons, 'calendar'); ?> Add Session</summary>
                <form class="catalog-admin-form catalog-admin-form-wide" method="post">
                    <?php echo court_csrf_input(); ?>
                    <label><span>Variant</span><select name="variant_id" required><?php foreach ($allVariants as $variant): ?><option value="<?php echo (int) $variant['id']; ?>"><?php echo court_h($variant['court'] . ' - ' . $variant['name']); ?></option><?php endforeach; ?></select></label>
                    <label><span>Coach</span><select name="coach_user_id"><option value="">Unassigned</option><?php foreach ($coaches as $coach): ?><option value="<?php echo (int) $coach['id']; ?>"><?php echo court_h($coach['name']); ?></option><?php endforeach; ?></select></label>
                    <label><span>Date</span><input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required></label>
                    <label><span>Start</span><input type="time" name="start_time" value="09:00" required></label>
                    <label><span>End</span><input type="time" name="end_time" value="10:00" required></label>
                    <label><span>Capacity</span><input type="number" name="capacity" min="1" value="8" required></label>
                    <label><span>Booked</span><input type="number" name="booked_count" min="0" value="0" required></label>
                    <label><span>Status</span><select name="status"><option value="open">Open</option><option value="full">Full</option><option value="cancelled">Cancelled</option><option value="completed">Completed</option></select></label>
                    <button class="bookings-button primary" type="submit" name="action" value="create_session">Add Session</button>
                </form>
            </details>

            <details>
                <summary><?php echo court_icon($icons, 'edit'); ?> Edit Courts</summary>
                <div class="catalog-admin-list">
                    <?php foreach ($allCourts as $catalogCourt): $status = (string) ($catalogCourt['status'] ?? 'active'); ?>
                        <article class="catalog-admin-row">
                            <form class="catalog-admin-form catalog-inline-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="court_id" value="<?php echo (int) $catalogCourt['id']; ?>">
                                <label><span>Name</span><input type="text" name="name" value="<?php echo court_h($catalogCourt['name']); ?>" required></label>
                                <label><span>Slug</span><input type="text" name="slug" value="<?php echo court_h($catalogCourt['slug']); ?>" required></label>
                                <label><span>Status</span><select name="status"><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option><option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option></select></label>
                                <button type="submit" name="action" value="update_court">Save</button>
                            </form>
                            <form class="catalog-status-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="court_id" value="<?php echo (int) $catalogCourt['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $status === 'active' ? 'inactive' : 'active'; ?>">
                                <button class="<?php echo $status === 'active' ? 'danger' : ''; ?>" type="submit" name="action" value="set_court_status"><?php echo $status === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$allCourts): ?><p class="catalog-empty-state">No courts yet. Add the first court above.</p><?php endif; ?>
                </div>
            </details>

            <details>
                <summary><?php echo court_icon($icons, 'edit'); ?> Edit Booking Variants</summary>
                <div class="catalog-admin-list">
                    <?php foreach ($allVariants as $variant): $variantActive = !empty($variant['active']); ?>
                        <article class="catalog-admin-row">
                            <form class="catalog-admin-form catalog-inline-form catalog-variant-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="variant_id" value="<?php echo (int) $variant['id']; ?>">
                                <label><span>Court</span><select name="court_id" required><?php foreach ($allCourts as $selectCourt): ?><option value="<?php echo (int) $selectCourt['id']; ?>" <?php echo (int) $variant['court_id'] === (int) $selectCourt['id'] ? 'selected' : ''; ?>><?php echo court_h($selectCourt['name']); ?></option><?php endforeach; ?></select></label>
                                <label><span>Name</span><input type="text" name="name" value="<?php echo court_h($variant['name']); ?>" required></label>
                                <label><span>Slug</span><input type="text" name="slug" value="<?php echo court_h($variant['slug']); ?>" required></label>
                                <label><span>Category</span><input type="text" name="category" value="<?php echo court_h($variant['category']); ?>" required></label>
                                <label><span>Duration</span><input type="text" name="duration_label" value="<?php echo court_h($variant['duration_label']); ?>" required></label>
                                <label><span>Price</span><input type="number" name="price" step="0.01" min="0" value="<?php echo court_h($variant['price']); ?>" required></label>
                                <label><span>Limit</span><input type="number" name="participants_limit" min="1" value="<?php echo (int) $variant['participants_limit']; ?>" required></label>
                                <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) $variant['capacity']; ?>" required></label>
                                <label><span>Image</span><input type="text" name="image" value="<?php echo court_h($variant['image'] ?? ''); ?>"></label>
                                <label class="catalog-check"><input type="checkbox" name="active" value="1" <?php echo $variantActive ? 'checked' : ''; ?>> Active</label>
                                <button type="submit" name="action" value="update_variant">Save</button>
                            </form>
                            <form class="catalog-status-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="variant_id" value="<?php echo (int) $variant['id']; ?>">
                                <input type="hidden" name="active" value="<?php echo $variantActive ? '0' : '1'; ?>">
                                <button class="<?php echo $variantActive ? 'danger' : ''; ?>" type="submit" name="action" value="set_variant_active"><?php echo $variantActive ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$allVariants): ?><p class="catalog-empty-state">No booking variants yet. Add the first service above.</p><?php endif; ?>
                </div>
            </details>

            <details>
                <summary><?php echo court_icon($icons, 'edit'); ?> Edit Sessions</summary>
                <div class="catalog-admin-list">
                    <?php foreach ($allSessions as $session): $sessionStatus = (string) ($session['status'] ?? 'open'); ?>
                        <article class="catalog-admin-row">
                            <form class="catalog-admin-form catalog-inline-form catalog-variant-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="session_id" value="<?php echo (int) $session['id']; ?>">
                                <label><span>Variant</span><select name="variant_id" required><?php foreach ($allVariants as $variant): ?><option value="<?php echo (int) $variant['id']; ?>" <?php echo (int) $session['variant_id'] === (int) $variant['id'] ? 'selected' : ''; ?>><?php echo court_h($variant['court'] . ' - ' . $variant['name']); ?></option><?php endforeach; ?></select></label>
                                <label><span>Coach</span><select name="coach_user_id"><option value="">Unassigned</option><?php foreach ($coaches as $coach): ?><option value="<?php echo (int) $coach['id']; ?>" <?php echo (int) ($session['coach_user_id'] ?? 0) === (int) $coach['id'] ? 'selected' : ''; ?>><?php echo court_h($coach['name']); ?></option><?php endforeach; ?></select></label>
                                <label><span>Date</span><input type="date" name="session_date" value="<?php echo court_h($session['session_date']); ?>" required></label>
                                <label><span>Start</span><input type="time" name="start_time" value="<?php echo court_h(substr((string) $session['start_time'], 0, 5)); ?>" required></label>
                                <label><span>End</span><input type="time" name="end_time" value="<?php echo court_h(substr((string) $session['end_time'], 0, 5)); ?>" required></label>
                                <label><span>Capacity</span><input type="number" name="capacity" min="1" value="<?php echo (int) $session['capacity']; ?>" required></label>
                                <label><span>Booked</span><input type="number" name="booked_count" min="0" value="<?php echo (int) $session['booked_count']; ?>" required></label>
                                <label><span>Status</span><select name="status"><option value="open" <?php echo $sessionStatus === 'open' ? 'selected' : ''; ?>>Open</option><option value="full" <?php echo $sessionStatus === 'full' ? 'selected' : ''; ?>>Full</option><option value="cancelled" <?php echo $sessionStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option><option value="completed" <?php echo $sessionStatus === 'completed' ? 'selected' : ''; ?>>Completed</option></select></label>
                                <button type="submit" name="action" value="update_session">Save</button>
                            </form>
                            <form class="catalog-status-form" method="post">
                                <?php echo court_csrf_input(); ?>
                                <input type="hidden" name="session_id" value="<?php echo (int) $session['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $sessionStatus === 'cancelled' ? 'open' : 'cancelled'; ?>">
                                <button class="<?php echo $sessionStatus === 'cancelled' ? '' : 'danger'; ?>" type="submit" name="action" value="set_session_status"><?php echo $sessionStatus === 'cancelled' ? 'Reopen' : 'Disable'; ?></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$allSessions): ?><p class="catalog-empty-state">No sessions yet. Add the first schedule above.</p><?php endif; ?>
                </div>
            </details>
        </section>

        <?php if ($isSocialPlay): ?>
        <section class="social-play-layout">
            <div class="social-main-column">
                <div class="social-actions-row"><a class="bookings-button ghost" href="<?php echo court_public_url('courts.php#social-play'); ?>"><?php echo court_icon($icons, 'eye'); ?> Preview on Website</a><button class="bookings-button primary" type="button">Save Changes</button></div>
                <section class="social-stat-grid">
                    <article class="user-stat green"><div><?php echo court_icon($icons, 'users'); ?></div><span>Participants This Month</span><strong><?php echo number_format(max($socialParticipants, 128)); ?></strong><small>↑ 18% vs last month</small></article>
                    <article class="user-stat orange"><div><?php echo court_icon($icons, 'calendar'); ?></div><span>Sessions This Month</span><strong><?php echo number_format(max(count($socialSessions), 12)); ?></strong><small>↑ 9% vs last month</small></article>
                    <article class="user-stat pink"><div><?php echo court_icon($icons, 'tag'); ?></div><span>Revenue This Month</span><strong>₱<?php echo number_format(max($socialRevenue, 18400), 0); ?></strong><small>↑ 22% vs last month</small></article>
                    <article class="user-stat orange"><div><?php echo court_icon($icons, 'bell'); ?></div><span>Upcoming Sessions</span><strong><?php echo number_format(max(count($socialSessions), 4)); ?></strong><small>Next: Jun 10, 6:00 PM</small></article>
                </section>

                <article class="social-panel">
                    <header><div><h2>Booking Types</h2><p>Manage available social play products and booking options.</p></div><button type="button"><?php echo court_icon($icons, 'plus'); ?> Add Booking Type</button></header>
                    <div class="social-type-list">
                        <?php foreach ($socialServices as $index => $service): ?>
                            <article class="social-type-card <?php echo $index % 2 ? 'purple' : 'pink'; ?>">
                                <span><?php echo court_icon($icons, $index % 2 ? 'target' : 'courts'); ?></span>
                                <div><h3><?php echo htmlspecialchars(strtoupper($service['name'])); ?> <em>₱<?php echo number_format((float) $service['price'], 0); ?></em></h3><p><?php echo $index % 2 ? "Compete in this week's Court Green bracket." : 'Meet new partners, rotate games, and level up with peers.'; ?></p></div>
                                <p><small>Capacity</small><strong><?php echo number_format((int) $service['capacity']); ?> Players</strong></p>
                                <p><small>Duration</small><strong><?php echo htmlspecialchars($service['duration_label']); ?></strong></p>
                                <p><small>Status</small><b class="status-pill status-<?php echo !empty($service['active']) ? 'success' : 'warning'; ?>"><?php echo !empty($service['active']) ? 'Active' : 'Inactive'; ?></b></p>
                                <div class="service-actions"><button type="button"><?php echo court_icon($icons, 'edit'); ?> Edit</button><button class="icon-button danger" type="button" aria-label="Archive service"><?php echo court_icon($icons, 'trash'); ?></button></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="social-panel">
                    <header><div><h2>Upcoming Sessions</h2><p>Manage and schedule upcoming social play sessions.</p></div><button type="button"><?php echo court_icon($icons, 'plus'); ?> Add New Session</button></header>
                    <div class="social-session-table">
                        <div class="social-session-row head"><span>Date</span><span>Time</span><span>Session Type</span><span>Court</span><span>Players</span><span>Status</span><span>Actions</span></div>
                        <?php foreach ($socialSessions as $session): ?>
                            <div class="social-session-row"><span><?php echo court_icon($icons, 'calendar'); ?> <strong><?php echo htmlspecialchars($session['session_date']); ?></strong></span><span><?php echo court_icon($icons, 'bell'); ?> <?php echo htmlspecialchars($session['session_time']); ?></span><span><em class="social-chip"><?php echo htmlspecialchars($session['name']); ?></em></span><span><em class="court-chip"><?php echo htmlspecialchars($session['court_name']); ?></em></span><span><?php echo number_format((int) $session['booked_count']); ?> / <?php echo number_format((int) ($session['variant_capacity'] ?? 16)); ?></span><span><b class="status-pill status-success">Open</b></span><span class="social-row-actions"><button><?php echo court_icon($icons, 'edit'); ?></button><button><?php echo court_icon($icons, 'plus'); ?></button><button><?php echo court_icon($icons, 'more'); ?></button></span></div>
                        <?php endforeach; ?>
                        <?php if (!$socialSessions): ?><div class="social-session-row"><span>No sessions scheduled yet.</span><span></span><span></span><span></span><span></span><span></span><span></span></div><?php endif; ?>
                    </div>
                    <a class="social-view-all" href="#">View All Sessions <?php echo court_icon($icons, 'arrow'); ?></a>
                </article>
            </div>

            <aside class="social-content-column">
                <article class="court-photo-card"><header><h2>Hero Image</h2><button type="button"><?php echo court_icon($icons, 'plus'); ?> Change Photo</button></header><div class="hero-photo social-hero"><img src="<?php echo court_asset('img/court/social play-2.png'); ?>" alt="Social Play hero"></div></article>
                <article class="court-photo-card"><header><h2>Gallery</h2><button type="button">Manage Photos</button></header><div class="gallery-grid social-gallery"><?php foreach ($socialGallery as $photo): ?><img src="<?php echo court_asset($photo); ?>" alt="Social Play photo"><?php endforeach; ?></div></article>
                <details class="website-preview-card social-preview-card"><summary>Website Preview</summary><div class="social-preview"><h3>SOCIAL PLAY</h3><p>Community-driven pickleball sessions</p><?php foreach ($socialServices as $index => $service): ?><article><span><?php echo court_icon($icons, $index % 2 ? 'target' : 'courts'); ?></span><div><strong><?php echo htmlspecialchars(strtoupper($service['name'])); ?></strong><small><?php echo $index % 2 ? "Compete in this week's Court Green bracket." : 'Meet new partners, rotate games, and level up with peers.'; ?></small></div><b>₱<?php echo number_format((float) $service['price'], 0); ?><small>/ session</small></b></article><?php endforeach; ?><button type="button">Book Now</button></div><footer><span>This is how Social Play looks on the public website.</span><a href="<?php echo court_public_url('courts.php#social-play'); ?>">View Full Page</a></footer></details>
            </aside>
        </section>
        <?php else: ?>
        <section class="court-actions-row">
            <nav class="court-tabs" aria-label="Court sections"><a class="active" href="#">Catalogs</a><a href="#">Details</a><a href="#">Photos</a><a href="#">Schedule</a></nav>
            <div><a class="bookings-button ghost" href="<?php echo court_public_url('courts.php#' . $courtSlug); ?>"><?php echo court_icon($icons, 'eye'); ?> Preview on Website</a><button class="bookings-button primary" type="button">Save Changes</button></div>
        </section>

        <section class="court-editor-layout court-accent-<?php echo $accent; ?>">
            <div class="court-editor-column">
                <article class="court-info-card">
                    <header><h2>Court Details</h2><button type="button"><?php echo court_icon($icons, 'edit'); ?> Edit Details</button></header>
                    <div class="court-info-grid">
                        <div class="court-info-title"><span><?php echo court_icon($icons, 'courts'); ?></span><div><strong><?php echo htmlspecialchars($pageTitle); ?></strong><small><?php echo htmlspecialchars($subtitle); ?></small></div></div>
                        <p><small>Base Price</small><strong>₱<?php echo number_format($basePrice, 2); ?> / session</strong></p>
                        <p><small>Capacity</small><strong><?php echo number_format($capacity); ?> Players</strong></p>
                        <p><small>Status</small><strong class="dot-status"><?php echo court_h(ucfirst((string) ($court['status'] ?? 'inactive'))); ?></strong></p>
                    </div>
                </article>

                <article class="catalog-manager-card">
                    <header><div><h2>Services</h2><p>Manage the services and offers for <?php echo htmlspecialchars($pageTitle); ?>.</p></div><button type="button"><?php echo court_icon($icons, 'plus'); ?> Add New Service</button></header>
                    <div class="service-list">
                        <?php foreach ($services as $service): ?>
                            <article class="service-card">
                                <span class="service-icon"><?php echo court_icon($icons, service_icon_name($service['name'])); ?></span>
                                <div><strong><?php echo htmlspecialchars($service['name']); ?></strong><small><?php echo htmlspecialchars($service['category']); ?> for <?php echo htmlspecialchars($pageTitle); ?></small></div>
                                <p><small>Price</small><strong>₱<?php echo number_format((float) $service['price'], 2); ?></strong></p>
                                <p><small>Duration</small><strong><?php echo htmlspecialchars($service['duration_label']); ?></strong></p>
                                <p><small>Status</small><em class="status-pill status-<?php echo !empty($service['active']) ? 'success' : 'warning'; ?>"><?php echo !empty($service['active']) ? 'Active' : 'Inactive'; ?></em></p>
                                <div class="service-actions"><button type="button"><?php echo court_icon($icons, 'edit'); ?> Edit</button><button class="icon-button danger" type="button" aria-label="Archive service"><?php echo court_icon($icons, 'trash'); ?></button></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <footer><?php echo court_icon($icons, 'target'); ?> Drag to reorder services</footer>
                </article>
            </div>

            <aside class="court-preview-column">
                <article class="court-photo-card">
                    <header><h2>Photos</h2><button type="button"><?php echo court_icon($icons, 'gear'); ?> Manage Photos</button></header>
                    <div class="hero-photo"><img src="<?php echo court_asset($heroImage); ?>" alt="<?php echo htmlspecialchars($pageTitle); ?>"><span>Hero Image</span></div>
                    <div class="gallery-grid"><?php foreach ($gallery as $index => $photo): ?><img src="<?php echo court_asset($photo); ?>" alt="Court photo <?php echo $index + 1; ?>"><?php endforeach; ?></div>
                </article>

                <details class="website-preview-card">
                    <summary>Website Preview</summary>
                    <div class="website-preview">
                        <div class="preview-copy"><h3><?php echo htmlspecialchars($pageTitle); ?></h3><strong>₱<?php echo number_format($basePrice, 2); ?> / session</strong><p><?php echo htmlspecialchars($subtitle); ?></p><div class="preview-stats"><span><?php echo court_icon($icons, 'users'); ?><b><?php echo number_format($capacity); ?></b><small>Capacity</small></span><span><?php echo court_icon($icons, 'courts'); ?><b>Indoor</b><small>Court</small></span><span><?php echo court_icon($icons, 'calendar'); ?><b>8AM - 10PM</b><small>Operating</small></span></div></div>
                        <img src="<?php echo court_asset($heroImage); ?>" alt="<?php echo htmlspecialchars($pageTitle); ?> preview">
                        <section><h4>Services</h4><div class="preview-services"><?php foreach (array_slice($services, 0, 4) as $service): ?><article><span><?php echo court_icon($icons, service_icon_name($service['name'])); ?></span><strong><?php echo htmlspecialchars($service['name']); ?></strong><b>₱<?php echo number_format((float) $service['price'], 0); ?></b><small><?php echo htmlspecialchars($service['category']); ?></small></article><?php endforeach; ?></div></section>
                        <button type="button">Book now</button>
                    </div>
                    <footer><span>This is how your court page looks on the public website.</span><a href="<?php echo court_public_url('courts.php#' . $courtSlug); ?>">View Full Page</a></footer>
                </details>
            </aside>
        </section>
        <?php endif; ?>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
