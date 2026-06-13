<?php
declare(strict_types=1);

$pageTitle = 'Private Sessions';
$activePage = 'events';
$bodyClass = 'admin-dashboard-body';

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../app/services/PrivatePackageService.php';
require_once __DIR__ . '/../app/services/PrivateInquiryService.php';

pickled_init_csrf();

$packageService = new PrivatePackageService();
$inquiryService = new PrivateInquiryService();
$adminId = (int) ($_SESSION['user']['id'] ?? 0);
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$successMsg = '';
$errorMsg = '';
$packageStatusFilter = trim((string) ($_GET['package_status'] ?? ''));
$inquiryStatusFilter = trim((string) ($_GET['inquiry_status'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Invalid form submission. Please try again.';
    } else {
        try {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create_package') {
                $packageService->create($_POST, $adminId);
                $successMsg = 'Private package created.';
            } elseif ($action === 'update_package') {
                $packageService->update((int) ($_POST['package_id'] ?? 0), $_POST, $adminId);
                $successMsg = 'Private package updated.';
            } elseif ($action === 'set_package_status') {
                $packageService->setStatus((int) ($_POST['package_id'] ?? 0), (string) ($_POST['status'] ?? 'inactive'), $adminId);
                $successMsg = 'Private package status updated.';
            } elseif ($action === 'respond_inquiry') {
                $inquiryService->respond((int) ($_POST['inquiry_id'] ?? 0), (string) ($_POST['admin_response'] ?? ''), (string) ($_POST['status'] ?? 'responded'), $adminId);
                $successMsg = 'Inquiry response saved.';
            } elseif ($action === 'set_inquiry_status') {
                $inquiryService->setStatus((int) ($_POST['inquiry_id'] ?? 0), (string) ($_POST['status'] ?? 'in_review'), $adminId);
                $successMsg = 'Inquiry status updated.';
            }
        } catch (Throwable $e) {
            error_log('Private sessions admin action failed: ' . $e->getMessage());
            $errorMsg = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to save private session changes.';
        }
    }
}

try {
    $coachProfiles = $packageService->coachProfiles();
    $packages = $packageService->allPackages($packageStatusFilter !== '' ? $packageStatusFilter : null, $search, 100);
    $inquiries = $inquiryService->allInquiries($inquiryStatusFilter !== '' ? $inquiryStatusFilter : null, $search, 100);
} catch (Throwable $e) {
    error_log('Private sessions admin load failed: ' . $e->getMessage());
    $coachProfiles = [];
    $packages = [];
    $inquiries = [];
    $errorMsg = $errorMsg ?: 'Private sessions data is unavailable. Apply the private packages and inquiries schema.';
}

$activePackages = count(array_filter($packages, static fn(array $package): bool => ($package['status'] ?? '') === 'active'));
$newInquiries = count(array_filter($inquiries, static fn(array $inquiry): bool => ($inquiry['status'] ?? '') === 'new'));

function private_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function private_h(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function private_label(string $value): string {
    return ucwords(str_replace('_', ' ', strtolower($value)));
}

function private_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['target']) . '</svg>';
}

function private_status_options(string $selected, array $statuses): string {
    $html = '';
    foreach ($statuses as $status) {
        $html .= '<option value="' . private_h($status) . '"' . ($selected === $status ? ' selected' : '') . '>' . private_h(private_label($status)) . '</option>';
    }
    return $html;
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
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'edit' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'check' => '<path d="m20 6-11 11-5-5"/>',
    'peso' => '<path d="M8 5h6a4 4 0 0 1 0 8H8M8 5v14M5 9h12M5 13h9"/>',
];

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php', ''], ['Calendar View', 'manage-bookings.php?view=calendar', '']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player', ''], ['Coaches', 'manage-users.php?role=coach', '']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php?court=green', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green', ''], ['Court Pink', 'manage-events.php?court=pink', '']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php?program=social-play', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play', 'social-play'], ['Private Sessions', 'private-sessions.php', 'private']]],
    ['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
    ['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo private_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo private_icon($icons, $item['icon']); ?><span><?php echo private_h($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref, $childKey]): ?><a class="<?php echo $childKey === 'private' ? 'active-child' : ''; ?>" href="<?php echo pickled_admin_url($childHref); ?>"><?php echo private_h($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo private_icon($icons, $item['icon']); ?><span><?php echo private_h($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main private-sessions-main">
        <header class="admin-topbar">
            <div><h1>Private Sessions <span class="court-title-badge">MySQL</span></h1><p class="program-subtitle">Private package management and player inquiries</p></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo private_icon($icons, 'calendar'); ?><span><?php echo private_h($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo private_icon($icons, 'bell'); ?></a><?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?></div>
        </header>

        <?php if ($successMsg !== ''): ?><p class="status-pill status-success"><?php echo private_h($successMsg); ?></p><?php endif; ?>
        <?php if ($errorMsg !== ''): ?><p class="status-pill status-danger"><?php echo private_h($errorMsg); ?></p><?php endif; ?>

        <section class="private-kpi-grid" aria-label="Private sessions metrics">
            <article class="user-stat green"><div><?php echo private_icon($icons, 'target'); ?></div><span>Packages</span><strong><?php echo number_format(count($packages)); ?></strong><small><?php echo number_format($activePackages); ?> active</small></article>
            <article class="user-stat pink"><div><?php echo private_icon($icons, 'mail'); ?></div><span>Inquiries</span><strong><?php echo number_format(count($inquiries)); ?></strong><small><?php echo number_format($newInquiries); ?> new</small></article>
            <article class="user-stat orange"><div><?php echo private_icon($icons, 'users'); ?></div><span>Coaches</span><strong><?php echo number_format(count($coachProfiles)); ?></strong><small>Assignable profiles</small></article>
            <article class="user-stat purple"><div><?php echo private_icon($icons, 'peso'); ?></div><span>Active Value</span><strong>PHP <?php echo number_format(array_sum(array_map(static fn(array $package): float => ($package['status'] ?? '') === 'active' ? (float) $package['price'] : 0.0, $packages)), 0); ?></strong><small>Package total</small></article>
        </section>

        <section class="private-admin-layout">
            <div class="private-editor-column">
                <article class="private-admin-card">
                    <header><div><h2><?php echo private_icon($icons, 'plus'); ?> Create Package</h2><p>Add a coach-assigned private coaching package.</p></div></header>
                    <form class="private-contact-grid" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo private_h(pickled_csrf_token()); ?>">
                        <input type="hidden" name="action" value="create_package">
                        <label>Title<input name="title" required></label>
                        <label>Price<input name="price" type="number" min="0" step="0.01" required></label>
                        <label>Duration<input name="duration" required placeholder="90 minutes"></label>
                        <label>Coach<select name="coach_profile_id" required><option value="">Select coach</option><?php foreach ($coachProfiles as $coach): ?><option value="<?php echo (int) $coach['id']; ?>"><?php echo private_h($coach['name'] . ' - ' . ($coach['specialization'] ?? 'Coach')); ?></option><?php endforeach; ?></select></label>
                        <label>Status<select name="status"><?php echo private_status_options('active', ['active', 'inactive', 'archived']); ?></select></label>
                        <label>Description<textarea name="description" rows="4" required></textarea></label>
                        <button class="bookings-button primary" type="submit">Create Package</button>
                    </form>
                </article>

                <article class="private-admin-card">
                    <header><div><h2>Private Packages</h2><p>Create, edit, activate, and deactivate private package offerings.</p></div></header>
                    <form class="booking-filter-bar" method="get">
                        <select name="package_status"><option value="">All package statuses</option><?php echo private_status_options($packageStatusFilter, ['active', 'inactive', 'archived']); ?></select>
                        <input type="search" name="q" value="<?php echo private_h($search); ?>" placeholder="Search packages or coaches">
                        <button type="submit">Filter</button>
                    </form>
                    <div class="package-list operational-package-list">
                        <?php foreach ($packages as $package): ?>
                            <article class="package-item package-green">
                                <span><?php echo private_icon($icons, 'target'); ?></span>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo private_h(pickled_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="update_package">
                                    <input type="hidden" name="package_id" value="<?php echo (int) $package['id']; ?>">
                                    <label>Title<input name="title" value="<?php echo private_h($package['title']); ?>" required></label>
                                    <label>Description<textarea name="description" rows="3" required><?php echo private_h($package['description']); ?></textarea></label>
                                    <label>Price<input name="price" type="number" min="0" step="0.01" value="<?php echo private_h($package['price']); ?>" required></label>
                                    <label>Duration<input name="duration" value="<?php echo private_h($package['duration']); ?>" required></label>
                                    <label>Coach<select name="coach_profile_id" required><?php foreach ($coachProfiles as $coach): ?><option value="<?php echo (int) $coach['id']; ?>" <?php echo (int) $coach['id'] === (int) $package['coach_profile_id'] ? 'selected' : ''; ?>><?php echo private_h($coach['name']); ?></option><?php endforeach; ?></select></label>
                                    <label>Status<select name="status"><?php echo private_status_options((string) $package['status'], ['active', 'inactive', 'archived']); ?></select></label>
                                    <button type="submit"><?php echo private_icon($icons, 'edit'); ?> Save</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo private_h(pickled_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="set_package_status">
                                    <input type="hidden" name="package_id" value="<?php echo (int) $package['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo ($package['status'] ?? '') === 'active' ? 'inactive' : 'active'; ?>">
                                    <button type="submit"><?php echo ($package['status'] ?? '') === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                        <?php if (!$packages): ?><p>No private packages found.</p><?php endif; ?>
                    </div>
                </article>

                <article class="private-admin-card private-table-card">
                    <header><div><h2>Private Inquiries</h2><p>Respond to players and update inquiry status.</p></div></header>
                    <form class="booking-filter-bar" method="get">
                        <select name="inquiry_status"><option value="">All inquiry statuses</option><?php echo private_status_options($inquiryStatusFilter, ['new', 'in_review', 'responded', 'closed', 'cancelled']); ?></select>
                        <input type="search" name="q" value="<?php echo private_h($search); ?>" placeholder="Search inquiries, packages, or players">
                        <button type="submit">Filter</button>
                    </form>
                    <div class="private-inquiry-table">
                        <div class="private-inquiry-row head"><span>Player</span><span>Package</span><span>Message</span><span>Status</span><span>Response</span><span>Actions</span></div>
                        <?php foreach ($inquiries as $inquiry): ?>
                            <div class="private-inquiry-row">
                                <span><?php echo private_h($inquiry['user_name']); ?><br><small><?php echo private_h($inquiry['user_email']); ?></small></span>
                                <span><?php echo private_h($inquiry['package_title']); ?><br><small><?php echo private_h($inquiry['coach_name'] ?? 'Coach'); ?></small></span>
                                <span><?php echo private_h($inquiry['message']); ?></span>
                                <span><em class="status-pill status-<?php echo ($inquiry['status'] ?? '') === 'new' ? 'danger' : 'success'; ?>"><?php echo private_h(private_label((string) $inquiry['status'])); ?></em></span>
                                <span><?php echo private_h($inquiry['admin_response'] ?? ''); ?></span>
                                <span class="private-row-actions">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo private_h(pickled_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="respond_inquiry">
                                        <input type="hidden" name="inquiry_id" value="<?php echo (int) $inquiry['id']; ?>">
                                        <select name="status"><?php echo private_status_options((string) $inquiry['status'], ['new', 'in_review', 'responded', 'closed', 'cancelled']); ?></select>
                                        <textarea name="admin_response" rows="3" required placeholder="Admin response"><?php echo private_h($inquiry['admin_response'] ?? ''); ?></textarea>
                                        <button type="submit"><?php echo private_icon($icons, 'check'); ?> Respond</button>
                                    </form>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$inquiries): ?><p>No private inquiries found.</p><?php endif; ?>
                    </div>
                </article>
            </div>

            <aside class="private-preview-column">
                <article class="private-preview-card package-card">
                    <header><div><h2>Website Preview</h2><p>Active packages shown to players.</p></div></header>
                    <div class="package-list">
                        <?php foreach (array_filter($packages, static fn(array $package): bool => ($package['status'] ?? '') === 'active') as $package): ?>
                            <article class="package-item package-pink"><span><?php echo private_icon($icons, 'target'); ?></span><div><strong><?php echo private_h($package['title']); ?></strong><small><?php echo private_h($package['duration']); ?> with <?php echo private_h($package['coach_name']); ?></small></div><p><small>Price</small><b>PHP <?php echo number_format((float) $package['price'], 2); ?></b></p></article>
                        <?php endforeach; ?>
                    </div>
                    <footer>Only active packages appear on the resident private page.</footer>
                </article>
            </aside>
        </section>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
