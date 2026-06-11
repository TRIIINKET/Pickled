<?php
$programSlug = $_GET['program'] ?? '';
$isSocialPlay = $programSlug === 'social-play';
$courtSlug = ($_GET['court'] ?? 'green') === 'pink' ? 'pink' : 'green';
$pageTitle = $isSocialPlay ? 'Social Play' : ($courtSlug === 'pink' ? 'Court Pink' : 'Court Green');
$activePage = $isSocialPlay ? 'events' : 'courts';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../database/Database.php';

pickled_init_csrf();

$pdo = Database::connection();
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');

function court_rows(PDO $pdo, string $sql, array $params = []): array {
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

$court = court_rows($pdo, 'SELECT * FROM courts WHERE slug = ? LIMIT 1', [$courtSlug])[0] ?? ['id' => $courtSlug === 'pink' ? 2 : 1, 'name' => $pageTitle, 'slug' => $courtSlug];
$services = court_rows($pdo, '
    SELECT bv.*
    FROM booking_variants bv
    JOIN courts c ON c.id = bv.court_id
    WHERE c.slug = ?
    ORDER BY bv.id ASC
', [$courtSlug]);
$socialServices = court_rows($pdo, "
    SELECT bv.*, c.name AS court_name
    FROM booking_variants bv
    JOIN courts c ON c.id = bv.court_id
    WHERE bv.category = 'Social Play'
       OR bv.name LIKE '%Match%'
       OR bv.name LIKE '%Tournament%'
    ORDER BY bv.id ASC
");
$socialSessions = court_rows($pdo, "
    SELECT s.*, bv.name, bv.price, bv.duration_label, bv.capacity AS variant_capacity, c.name AS court_name
    FROM sessions s
    JOIN booking_variants bv ON bv.id = s.variant_id
    JOIN courts c ON c.id = bv.court_id
    WHERE bv.category = 'Social Play'
       OR bv.name LIKE '%Match%'
       OR bv.name LIKE '%Tournament%'
    ORDER BY s.id DESC
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
    'arrow' => '<path d="m9 18 6-6-6-6"/>',
];

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', ''], ['Coaches', 'manage-users.php?role=coach', '']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green', 'green'], ['Court Pink', 'manage-events.php?court=pink', 'pink']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', 'social-play'], ['Private Sessions', 'private-sessions.php', 'private']]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo court_asset('img/LM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo court_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref, $childKey]): ?><a class="<?php echo $childKey && (($activePage === 'courts' && $courtSlug === $childKey) || ($activePage === 'events' && $programSlug === $childKey)) ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo court_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'sidebar'); ?>
    </aside>

    <main class="admin-dashboard-main court-manager-main">
        <header class="admin-topbar">
            <div><h1><?php echo htmlspecialchars($pageTitle); ?> <span class="court-title-badge">Active</span></h1><p class="court-breadcrumb"><?php echo $isSocialPlay ? 'Programs' : 'Courts'; ?> <?php echo court_icon($icons, 'arrow'); ?> <?php echo htmlspecialchars($pageTitle); ?></p><?php if ($isSocialPlay): ?><p class="program-subtitle">Community-driven pickleball sessions</p><?php endif; ?></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo court_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo court_icon($icons, 'bell'); ?></a></div>
        </header>

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
                                <p><small>Status</small><b class="status-pill status-success">Active</b></p>
                                <div class="service-actions"><button type="button"><?php echo court_icon($icons, 'edit'); ?> Edit</button><button type="button">Archive</button></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="social-panel">
                    <header><div><h2>Upcoming Sessions</h2><p>Manage and schedule upcoming social play sessions.</p></div><button type="button"><?php echo court_icon($icons, 'plus'); ?> Add New Session</button></header>
                    <div class="social-session-table">
                        <div class="social-session-row head"><span>Date</span><span>Time</span><span>Session Type</span><span>Court</span><span>Players</span><span>Status</span><span>Actions</span></div>
                        <?php foreach ($socialSessions ?: [['session_date' => 'Jun 10, 2026', 'session_time' => '06:00 PM - 08:00 PM', 'name' => 'Open Match-Play', 'court_name' => 'Court Green', 'booked_count' => 14, 'variant_capacity' => 16], ['session_date' => 'Jun 12, 2026', 'session_time' => '07:00 PM - 10:00 PM', 'name' => 'Weekly Tournament', 'court_name' => 'Court Green', 'booked_count' => 18, 'variant_capacity' => 24]] as $session): ?>
                            <div class="social-session-row"><span><?php echo court_icon($icons, 'calendar'); ?> <strong><?php echo htmlspecialchars($session['session_date']); ?></strong></span><span><?php echo court_icon($icons, 'bell'); ?> <?php echo htmlspecialchars($session['session_time']); ?></span><span><em class="social-chip"><?php echo htmlspecialchars($session['name']); ?></em></span><span><em class="court-chip"><?php echo htmlspecialchars($session['court_name']); ?></em></span><span><?php echo number_format((int) $session['booked_count']); ?> / <?php echo number_format((int) ($session['variant_capacity'] ?? 16)); ?></span><span><b class="status-pill status-success">Open</b></span><span class="social-row-actions"><button><?php echo court_icon($icons, 'edit'); ?></button><button><?php echo court_icon($icons, 'plus'); ?></button><button><?php echo court_icon($icons, 'more'); ?></button></span></div>
                        <?php endforeach; ?>
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
                        <p><small>Status</small><strong class="dot-status">Active</strong></p>
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
                                <p><small>Status</small><em class="status-pill status-success"><?php echo !empty($service['active']) ? 'Active' : 'Inactive'; ?></em></p>
                                <div class="service-actions"><button type="button"><?php echo court_icon($icons, 'edit'); ?> Edit</button><button type="button">Archive</button></div>
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
