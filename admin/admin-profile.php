<?php
$pageTitle = 'Admin Profile';
$activePage = 'admin-profile';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-paths.php';
require_once __DIR__ . '/../database/Database.php';

pickled_init_csrf();

$pdo = Database::enabled() ? Database::connection() : null;
$admin = $_SESSION['user'] ?? ['id' => 0, 'name' => 'Admin', 'email' => 'admin@example.com'];
$adminName = $admin['name'] ?? 'Admin';
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$successMsg = '';
$errorMsg = '';

function profile_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function profile_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['user']) . '</svg>';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission.';
    } elseif (($_POST['action'] ?? '') === 'update_admin_account') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Please enter a valid name and email.';
        } elseif ($password !== '' && strlen($password) < 6) {
            $errorMsg = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $errorMsg = 'Password confirmation does not match.';
        } else {
            try {
                if (!$pdo) {
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                } elseif ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ? AND role = ?');
                    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), (int) $admin['id'], 'admin']);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = ?');
                    $stmt->execute([$name, $email, (int) $admin['id'], 'admin']);
                }
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                $adminName = $name;
                $admin['name'] = $name;
                $admin['email'] = $email;
                $successMsg = 'Admin profile updated.';
            } catch (Throwable $e) {
                error_log('Admin profile update failed: ' . $e->getMessage());
                $errorMsg = 'Unable to update account. Please check if the email is already used.';
            }
        }
    }
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
    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
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
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>"><img src="<?php echo profile_asset('img/WM-DGreen.png'); ?>" alt="Pickled"><span>Admin</span></a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group"><a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo profile_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a><div class="admin-nav-children"><?php foreach ($item['children'] as [$childLabel, $childHref]): ?><a href="<?php echo pickled_admin_url($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a><?php endforeach; ?></div></section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>"><?php echo profile_icon($icons, $item['icon']); ?><span><?php echo htmlspecialchars($item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main settings-main">
        <header class="admin-topbar settings-topbar">
            <div><h1>Admin Profile</h1><p>Manage your admin identity and password</p></div>
            <div class="admin-topbar-actions"><button class="admin-date-pill" type="button"><?php echo profile_icon($icons, 'calendar'); ?><span><?php echo htmlspecialchars($todayLabel); ?></span></button><a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>"><?php echo profile_icon($icons, 'bell'); ?>
                </a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

        <section class="settings-layout admin-profile-layout">
            <article class="settings-card admin-account-card" id="profile">
                <header><span><?php echo profile_icon($icons, 'user'); ?></span><div><h2>Profile</h2><p>Name and email used inside Pickled Admin.</p></div></header>
                <form method="post" class="settings-form-grid">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="update_admin_account">
                    <label>Name<input type="text" name="name" value="<?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?>" required></label>
                    <label>Email<input type="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? 'admin@example.com'); ?>" required></label>
                    <label id="password">Password<input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current password"></label>
                    <label>Confirm Password<input type="password" name="confirm_password" autocomplete="new-password" placeholder="Confirm new password"></label>
                    <div class="settings-actions"><button class="bookings-button primary" type="submit">Save Profile</button></div>
                </form>
            </article>

            <article class="settings-card">
                <header><span><?php echo profile_icon($icons, 'shield'); ?></span><div><h2>Access</h2><p>Current role and session actions.</p></div></header>
                <div class="settings-rules">
                    <p><strong>Role</strong><span>Super Admin</span></p>
                    <p><strong>Account Status</strong><span>Active</span></p>
                    <p><strong>Password</strong><span>Change it using the password fields.</span></p>
                </div>
            </article>
        </section>
    </main>
</div>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
