<?php
$pageTitle = 'Content';
$activePage = 'content';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';

pickled_init_csrf();

$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');

function content_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function content_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['image']) . '</svg>';
}

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
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'pin' => '<path d="M20 10c0 5-8 11-8 11s-8-6-8-11a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
    'edit' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/>',
];

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php'], ['Calendar View', 'manage-bookings.php?view=calendar']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player'], ['Coaches', 'manage-users.php?role=coach']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green'], ['Court Pink', 'manage-events.php?court=pink']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play'], ['Private Packages', 'private-sessions.php']]],
    ['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
    ['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
    ['type' => 'single', 'label' => 'Admin Profile', 'href' => 'admin-profile.php', 'key' => 'admin-profile', 'icon' => 'users'],
];

$pages = [
    ['Homepage', 'Banner, featured programs, intro copy', 'Published'],
    ['Courts Page', 'Court copy, photos, public details', 'Published'],
    ['Private Page Content', 'Hero, descriptions, packages, gallery', 'Draft changes'],
    ['Contact Information', 'Email, phone, address, operating hours', 'Published'],
];

$gallery = ['img/court/court green-1.png', 'img/court/court pink-1.webp', 'img/court/social play-2.png', 'img/court/private-1.png'];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo content_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo content_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref]): ?><a href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo content_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main settings-main content-main">
        <header class="admin-topbar settings-topbar">
            <div><h1>Content</h1><p>Website pages, galleries, banners, and public contact details</p></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo content_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo content_icon($icons, 'bell'); ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <section class="content-layout">
            <article class="settings-card content-pages-card">
                <header><span><?php echo content_icon($icons, 'image'); ?></span><div><h2>Website Pages</h2><p>Content areas that affect the public website.</p></div></header>
                <div class="content-page-list">
                    <?php foreach ($pages as [$title, $description, $status]): ?>
                        <article><div><strong><?php echo htmlspecialchars($title); ?></strong><span><?php echo htmlspecialchars($description); ?></span></div><em><?php echo htmlspecialchars($status); ?></em><button type="button"><?php echo content_icon($icons, 'edit'); ?> Edit</button></article>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="settings-card">
                <header><span><?php echo content_icon($icons, 'tag'); ?></span><div><h2>Homepage Banner</h2><p>Primary campaign shown on the public homepage.</p></div></header>
                <div class="settings-form-grid">
                    <label>Headline<input type="text" value="Play, connect, and compete at Pickled"></label>
                    <label>Call to Action<input type="text" value="Book a Court"></label>
                    <label class="wide">Banner Copy<input type="text" value="Modern indoor pickleball courts for players, coaches, and private events."></label>
                </div>
            </article>

            <article class="settings-card">
                <header><span><?php echo content_icon($icons, 'image'); ?></span><div><h2>Gallery</h2><p>Featured photos used across public pages.</p></div></header>
                <div class="content-gallery-grid">
                    <?php foreach ($gallery as $index => $photo): ?><figure><img src="<?php echo content_asset($photo); ?>" alt="Gallery photo <?php echo $index + 1; ?>"><figcaption>Featured</figcaption></figure><?php endforeach; ?>
                </div>
            </article>

            <article class="settings-card">
                <header><span><?php echo content_icon($icons, 'pin'); ?></span><div><h2>Contact Information</h2><p>Public details used for inquiries, invoices, and directions.</p></div></header>
                <div class="settings-form-grid">
                    <label>Email<input type="email" value="info@pickled.ph"></label>
                    <label>Phone<input type="tel" value="0900 000 0000"></label>
                    <label class="wide">Address<input type="text" value="Makati, Metro Manila"></label>
                    <label>City<input type="text" value="Makati"></label>
                    <label>Province<input type="text" value="Metro Manila"></label>
                    <label>Operating Hours<input type="text" value="8:00 AM – 10:00 PM"></label>
                </div>
            </article>
        </section>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
