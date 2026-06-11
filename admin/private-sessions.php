<?php
$pageTitle = 'Private Sessions';
$activePage = 'events';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';

pickled_init_csrf();

$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');

function private_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function private_public_url(string $path): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/private-sessions.php');
    $position = strpos($script, '/admin/');
    $base = $position === false ? rtrim(dirname($script), '/') . '/' : substr($script, 0, $position + 1);
    return htmlspecialchars($base . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
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
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06A2 2 0 1 1 20.1 7l-.06.06A1.7 1.7 0 0 0 19.4 9c.38.22.74.57 1 .95.26.38.4.8.4 1.2V12a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.31-.6Z"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'edit' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/>',
    'more' => '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'upload' => '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'pin' => '<path d="M20 10c0 5-8 11-8 11s-8-6-8-11a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
    'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.08 5.18 2 2 0 0 1 5.06 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.63 2.61a2 2 0 0 1-.45 2.11L9 10.69a16 16 0 0 0 4.31 4.31l1.25-1.24a2 2 0 0 1 2.11-.45c.84.3 1.71.51 2.61.63A2 2 0 0 1 22 16.92Z"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'reply' => '<path d="m9 17-5-5 5-5"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>',
    'check' => '<path d="m20 6-11 11-5-5"/>',
    'external' => '<path d="M15 3h6v6"/><path d="m10 14 11-11"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>',
    'peso' => '<path d="M8 5h6a4 4 0 0 1 0 8H8M8 5v14M5 9h12M5 13h9"/>',
];

function private_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['target']) . '</svg>';
}

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', ''], ['Coaches', 'manage-users.php?role=coach', '']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green', ''], ['Court Pink', 'manage-events.php?court=pink', '']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', 'social-play'], ['Private Sessions', 'private-sessions.php', 'private']]],
['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];

$heroDescription = 'A 5,000 sq ft private space best for ultimate corporate or brand event experience where your team can dine, drink, and play in a dynamic and engaging environment. Our dedicated event spaces, pickleball courts, and food and drink options create the perfect setting for fostering connections, collaboration, and unforgettable moments.';
$gallery = ['img/court/private-1.png', 'img/court/private-2.png', 'img/court/private-3.png', 'img/court/friends private.png'];
$inquiries = [
    ['John Santos', 'ABC Corporation', 'May 23, 2026', 'Team building event for 30 pax with court time, food, and facilitation.', 'New', 'danger'],
    ['Maria Cruz', 'Family Birthday', 'May 21, 2026', 'Birthday celebration for my son with games, snacks, and a private area.', 'Contacted', 'warning'],
    ['Patricia Lim', 'Brand Launch', 'May 18, 2026', 'Exclusive venue rental for a product reveal and creator event.', 'Confirmed', 'success'],
];
$packages = [
    ['Corporate Team Building', 'Team building, meetings, dining, and pickleball activities.', '20 - 50 Guests', '₱15,000', 'users', 'green'],
    ['Birthday Celebration', 'Celebrate birthdays with fun games, food, and memories.', '10 - 30 Guests', '₱8,000', 'tag', 'pink'],
    ['Exclusive Venue Rental', 'Entire venue for your exclusive event and private use.', 'Up to 100 Guests', '₱20,000', 'courts', 'orange'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo private_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo private_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref, $childKey]): ?><a class="<?php echo $childKey === 'private' ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo private_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main private-sessions-main">
        <header class="admin-topbar">
            <div><h1>Private Sessions <span class="court-title-badge">Active</span></h1><p class="program-subtitle">Custom events, team-building, and private bookings</p></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo private_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo private_icon($icons, 'bell'); ?><span>3</span>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <section class="private-page-actions">
            <a class="bookings-button ghost" href="<?php echo pickled_admin_url('content.php'); ?>"><?php echo private_icon($icons, 'image'); ?> Edit Website Content</a>
            <button class="bookings-button primary" type="button"><?php echo private_icon($icons, 'plus'); ?> Add Package</button>
        </section>

        <section class="private-admin-layout">
            <div class="private-editor-column">
                <section class="private-kpi-grid" aria-label="Private sessions metrics">
                    <article class="user-stat green"><div><?php echo private_icon($icons, 'users'); ?></div><span>Inquiries</span><strong>18</strong><small>↑ 20% vs last month</small></article>
                    <article class="user-stat pink"><div><?php echo private_icon($icons, 'peso'); ?></div><span>Revenue This Month</span><strong>₱72,000</strong><small>↑ 15% vs last month</small></article>
                    <article class="user-stat orange"><div><?php echo private_icon($icons, 'calendar'); ?></div><span>Events This Month</span><strong>12</strong><small>↑ 9% vs last month</small></article>
                    <article class="user-stat purple"><div><?php echo private_icon($icons, 'clock'); ?></div><span>Pending Inquiries</span><strong>4</strong><small>View all pending</small></article>
                </section>

                <article class="private-admin-card">
                    <header><div><h2>Private Packages</h2><p>Manage promoted offerings for private events and group bookings.</p></div><button type="button"><?php echo private_icon($icons, 'plus'); ?> Add Package</button></header>
                    <div class="package-list operational-package-list">
                        <?php foreach ($packages as [$title, $copy, $capacity, $price, $icon, $tone]): ?>
                            <article class="package-item package-<?php echo $tone; ?>"><span><?php echo private_icon($icons, $icon); ?></span><div><strong><?php echo htmlspecialchars($title); ?></strong><small><?php echo htmlspecialchars($copy); ?></small></div><p><small>Starting at</small><b><?php echo htmlspecialchars($price); ?></b><em><?php echo htmlspecialchars($capacity); ?></em></p><button type="button">Edit</button><button class="icon-button danger" type="button" aria-label="Archive package"><?php echo private_icon($icons, 'trash'); ?></button></article>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="private-admin-card">
                    <header><div><h2>Upcoming Events</h2><p>Track confirmed private events and venue rentals.</p></div><button type="button"><?php echo private_icon($icons, 'calendar'); ?> Add Event</button></header>
                    <div class="private-inquiry-table">
                        <div class="private-inquiry-row head"><span>Event</span><span>Client</span><span>Date</span><span>Package</span><span>Status</span><span>Actions</span></div>
                        <div class="private-inquiry-row"><span>Team Building</span><span>ABC Corporation</span><span>Jun 14, 2026</span><span>Corporate Team Building</span><span><em class="status-pill status-success">Confirmed</em></span><span class="private-row-actions"><button><?php echo private_icon($icons, 'eye'); ?></button><button><?php echo private_icon($icons, 'edit'); ?></button></span></div>
                        <div class="private-inquiry-row"><span>Birthday Party</span><span>Cruz Family</span><span>Jun 20, 2026</span><span>Birthday Celebration</span><span><em class="status-pill status-warning">Pending</em></span><span class="private-row-actions"><button><?php echo private_icon($icons, 'eye'); ?></button><button><?php echo private_icon($icons, 'edit'); ?></button></span></div>
                    </div>
                </article>

                <article class="private-admin-card">
                    <header><div><h2>Revenue Snapshot</h2><p>Compare private-event value without opening Reports.</p></div><button type="button">View Report</button></header>
                    <div class="private-contact-grid">
                        <label>Confirmed Revenue<input type="text" value="₱72,000" readonly></label>
                        <label>Average Event Value<input type="text" value="₱12,000" readonly></label>
                        <label>Pending Pipeline<input type="text" value="₱36,000" readonly></label>
                        <label>Conversion Rate<input type="text" value="67%" readonly></label>
                    </div>
                </article>

                <article class="private-admin-card private-table-card">
                    <header><div><h2>Recent Inquiries</h2><p>View and manage the latest private event inquiries.</p></div><button type="button">View All Inquiries</button></header>
                    <div class="private-inquiry-table">
                        <div class="private-inquiry-row head"><span>Name</span><span>Company / Event</span><span>Date</span><span>Message</span><span>Status</span><span>Actions</span></div>
                        <?php foreach ($inquiries as [$name, $event, $date, $message, $status, $tone]): ?>
                            <div class="private-inquiry-row"><span><?php echo htmlspecialchars($name); ?></span><span><?php echo htmlspecialchars($event); ?></span><span><?php echo htmlspecialchars($date); ?></span><span><?php echo htmlspecialchars($message); ?></span><span><em class="status-pill status-<?php echo $tone; ?>"><?php echo htmlspecialchars($status); ?></em></span><span class="private-row-actions"><button aria-label="View inquiry"><?php echo private_icon($icons, 'eye'); ?></button><button aria-label="Reply"><?php echo private_icon($icons, 'reply'); ?></button><button aria-label="Mark complete"><?php echo private_icon($icons, 'check'); ?></button></span></div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <aside class="private-preview-column">
                <details class="private-preview-card">
                    <summary>Website Preview</summary>
                    <div class="private-site-preview">
                        <section class="private-site-hero"><p>Planning an event?</p><h3>PICKLE <span>&amp;</span> LAUNCH</h3><p><?php echo htmlspecialchars($heroDescription); ?></p></section>
                        <section class="private-site-contact"><span><?php echo private_icon($icons, 'mail'); ?> info@pickled.ph</span><span><?php echo private_icon($icons, 'pin'); ?> Makati, Metro Manila</span><span><?php echo private_icon($icons, 'clock'); ?> Monday - Sunday, 10AM - 10PM</span><span><?php echo private_icon($icons, 'phone'); ?> 0900 000 0000</span></section>
                        <section class="private-site-gallery"><img class="large" src="<?php echo private_asset('img/court/private-1.png'); ?>" alt="Private event preview"><img src="<?php echo private_asset('img/court/private-2.png'); ?>" alt="Private event preview"><img src="<?php echo private_asset('img/court/private-3.png'); ?>" alt="Private event preview"></section>
                    </div>
                    <footer><a href="<?php echo private_public_url('resident/private.php'); ?>">Open in new tab <?php echo private_icon($icons, 'external'); ?></a></footer>
                </details>

                <article class="private-preview-card package-card">
                    <header><div><h2>Featured Packages <span>(Optional)</span></h2><p>Manage the event packages or offerings you want to highlight.</p></div><button type="button"><?php echo private_icon($icons, 'plus'); ?> Add Package</button></header>
                    <div class="package-list">
                        <?php foreach ($packages as [$title, $copy, $capacity, $price, $icon, $tone]): ?>
                            <article class="package-item package-<?php echo $tone; ?>"><span><?php echo private_icon($icons, $icon); ?></span><div><strong><?php echo htmlspecialchars($title); ?></strong><small><?php echo htmlspecialchars($copy); ?></small></div><p><small>Starting at</small><b><?php echo htmlspecialchars($price); ?></b><em><?php echo htmlspecialchars($capacity); ?></em></p><button type="button">Edit</button><button type="button" aria-label="More options"><?php echo private_icon($icons, 'more'); ?></button></article>
                        <?php endforeach; ?>
                    </div>
                    <footer><?php echo private_icon($icons, 'clock'); ?> Packages are shown on the website. You can reorder them.</footer>
                </article>
            </aside>
        </section>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
