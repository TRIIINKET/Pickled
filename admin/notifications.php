<?php
$pageTitle = 'Notifications';
$activePage = 'notifications';
$bodyClass = 'admin-dashboard-body';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

pickled_init_csrf();

$adminService = new AdminService();
$userRepo = new UserRepository();
$successMsg = '';
$errorMsg = '';
$adminName = $_SESSION['user']['name'] ?? 'Admin';
$adminId = (int) ($_SESSION['user']['id'] ?? 0);
$logoutCsrf = htmlspecialchars(pickled_csrf_token(), ENT_QUOTES, 'UTF-8');
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayLabel = $today->format('M j, Y (D)');
$notificationTypes = [
    'info' => 'Info',
    'success' => 'Success',
    'warning' => 'Warning',
    'error' => 'Error',
];
$search = trim((string) ($_GET['q'] ?? ''));
$typeFilter = trim((string) ($_GET['type'] ?? 'all'));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$recipientFilter = trim((string) ($_GET['recipient'] ?? 'all'));
$viewId = (int) ($_GET['view_notification'] ?? 0);

function notifications_icon(array $icons, string $name): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['home']) . '</svg>';
}

function notifications_asset(string $path): string {
    return htmlspecialchars(pickled_admin_asset_url($path), ENT_QUOTES, 'UTF-8');
}

function notifications_h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function notifications_type_label(string $type): string {
    return ucwords(str_replace('_', ' ', $type ?: 'info'));
}

function notifications_excerpt(string $message): string {
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($message, 0, 120, '...');
    }

    return strlen($message) > 120 ? substr($message, 0, 117) . '...' : $message;
}

function notifications_internal_link(?string $link): string {
    $link = trim((string) $link);
    if ($link === '') {
        return '';
    }
    if (strlen($link) > 255 || str_contains($link, "\0") || str_contains($link, '..') || str_starts_with($link, '//')) {
        return '';
    }
    $parts = parse_url($link);
    if ($parts === false || !empty($parts['scheme']) || !empty($parts['host'])) {
        return '';
    }
    if (!preg_match('#^(resident|admin|coach|auth)/[A-Za-z0-9._/?=&%#-]*$#', $link)) {
        return '';
    }

    return $link;
}

function notifications_safe_href(?string $link): string {
    $internal = notifications_internal_link($link);
    return $internal !== '' && function_exists('pickled_frontend_url') ? pickled_frontend_url($internal) : $internal;
}

function notifications_validate_payload(array $input, array $notificationTypes): array {
    $recipient = trim((string) ($input['recipient'] ?? ''));
    $title = trim((string) ($input['title'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));
    $type = trim((string) ($input['type'] ?? ''));
    $link = trim((string) ($input['link'] ?? ''));

    if ($recipient === '') {
        throw new RuntimeException('Recipient is required.');
    }
    if ($title === '' || strlen($title) > 100) {
        throw new RuntimeException('Title is required and must be 100 characters or fewer.');
    }
    if ($message === '' || strlen($message) > 1000) {
        throw new RuntimeException('Message is required and must be 1000 characters or fewer.');
    }
    if (!array_key_exists($type, $notificationTypes)) {
        throw new RuntimeException('Please choose a valid notification type.');
    }
    if ($link !== '' && notifications_internal_link($link) === '') {
        throw new RuntimeException('Link must be a valid internal path.');
    }

    return [
        'recipient' => $recipient,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'link' => $link !== '' ? notifications_internal_link($link) : null,
    ];
}

function notifications_filter_rows(array $rows, string $search, string $typeFilter, string $statusFilter, string $recipientFilter): array {
    $search = strtolower($search);
    return array_values(array_filter($rows, static function (array $row) use ($search, $typeFilter, $statusFilter, $recipientFilter): bool {
        if ($typeFilter !== 'all' && (string) ($row['type'] ?? '') !== $typeFilter) {
            return false;
        }
        if ($statusFilter === 'read' && empty($row['is_read'])) {
            return false;
        }
        if ($statusFilter === 'unread' && !empty($row['is_read'])) {
            return false;
        }
        if ($recipientFilter !== 'all' && (string) ($row['user_id'] ?? '') !== $recipientFilter) {
            return false;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', [
                (string) ($row['title'] ?? ''),
                (string) ($row['message'] ?? ''),
                (string) ($row['type'] ?? ''),
                (string) ($row['user_name'] ?? ''),
                (string) ($row['user_email'] ?? ''),
            ]));
            if (!str_contains($haystack, $search)) {
                return false;
            }
        }
        return true;
    }));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'send_notification') {
                $payload = notifications_validate_payload($_POST, $notificationTypes);
                if ($payload['recipient'] === 'broadcast') {
                    $count = $adminService->sendBroadcastNotification($payload['title'], $payload['message'], $adminId, $payload['type'], $payload['link']);
                    $successMsg = $count > 0 ? 'Broadcast sent to ' . number_format($count) . ' users.' : '';
                    $errorMsg = $successMsg ? '' : 'Failed to send broadcast.';
                } else {
                    $recipientId = (int) $payload['recipient'];
                    if ($recipientId <= 0) {
                        throw new RuntimeException('Recipient is required.');
                    }
                    $sent = $adminService->sendNotification($recipientId, $payload['title'], $payload['message'], $adminId, $payload['type'], $payload['link']);
                    $successMsg = $sent ? 'Notification sent successfully.' : '';
                    $errorMsg = $successMsg ? '' : 'Failed to send notification.';
                }
            } elseif ($action === 'mark_read') {
                $id = (int) ($_POST['notification_id'] ?? 0);
                $successMsg = $id > 0 && $adminService->markNotificationAsRead($id) ? 'Notification marked as read.' : '';
                $errorMsg = $successMsg ? '' : 'Failed to mark notification as read.';
            } elseif ($action === 'delete_notification') {
                $id = (int) ($_POST['notification_id'] ?? 0);
                $successMsg = $id > 0 && $adminService->deleteNotification($id) ? 'Notification deleted.' : '';
                $errorMsg = $successMsg ? '' : 'Failed to delete notification.';
            }
        } catch (RuntimeException $e) {
            $errorMsg = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Admin notification action failed: ' . $e->getMessage());
            $errorMsg = 'Unable to process notification action.';
        }
    }
}

$users = $userRepo->findAll() ?? [];
$allNotifications = $adminService->getAllNotifications(200);
$notifications = notifications_filter_rows($allNotifications, $search, $typeFilter, $statusFilter, $recipientFilter);
$selectedNotification = null;
foreach ($allNotifications as $row) {
    if ((int) ($row['id'] ?? 0) === $viewId) {
        $selectedNotification = $row;
        break;
    }
}
$unreadCount = count(array_filter($allNotifications, static fn(array $row): bool => empty($row['is_read'])));

$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M22 2 12 12"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
    'courts' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'image' => '<rect x="3" y="5" width="18" height="16" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 21"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
    'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 16h10l1-16"/><path d="M10 11v6M14 11v6"/>',
    'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'check' => '<path d="m20 6-11 11-5-5"/>',
];

$dashboardNav = [
    ['type' => 'single', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['type' => 'group', 'label' => 'Bookings', 'href' => 'manage-bookings.php', 'key' => 'bookings', 'icon' => 'calendar', 'children' => [['All Bookings', 'manage-bookings.php'], ['Calendar View', 'manage-bookings.php?view=calendar']]],
    ['type' => 'group', 'label' => 'Users', 'href' => 'manage-users.php?role=player', 'key' => 'users', 'icon' => 'users', 'children' => [['Players', 'manage-users.php?role=player'], ['Coaches', 'manage-users.php?role=coach']]],
    ['type' => 'group', 'label' => 'Courts', 'href' => 'manage-events.php', 'key' => 'courts', 'icon' => 'courts', 'children' => [['Court Green', 'manage-events.php?court=green'], ['Court Pink', 'manage-events.php?court=pink']]],
    ['type' => 'group', 'label' => 'Programs & Events', 'href' => 'manage-events.php', 'key' => 'events', 'icon' => 'target', 'children' => [['Social Play', 'manage-events.php?program=social-play'], ['Private Packages', 'private-sessions.php']]],
    ['type' => 'single', 'label' => 'Notifications', 'href' => 'notifications.php', 'key' => 'notifications', 'icon' => 'bell'],
    ['type' => 'single', 'label' => 'Content', 'href' => 'content.php', 'key' => 'content', 'icon' => 'image'],
    ['type' => 'single', 'label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports', 'icon' => 'chart'],
];
?>

<div class="admin-app-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>">
            <img src="<?php echo notifications_asset('img/WM-DGreen.png'); ?>" alt="Pickled" />
            <span>Admin</span>
        </a>
        <nav class="admin-side-nav" aria-label="Admin navigation">
            <?php foreach ($dashboardNav as $item): ?>
                <?php if ($item['type'] === 'group'): ?>
                    <section class="admin-nav-group">
                        <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                            <?php echo notifications_icon($icons, $item['icon']); ?><span><?php echo notifications_h($item['label']); ?></span>
                        </a>
                        <div class="admin-nav-children">
                            <?php foreach ($item['children'] as [$childLabel, $childHref]): ?>
                                <a href="<?php echo pickled_admin_url($childHref); ?>"><?php echo notifications_h($childLabel); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <a class="admin-nav-parent <?php echo $activePage === $item['key'] ? 'active' : ''; ?>" href="<?php echo pickled_admin_url($item['href']); ?>">
                        <?php echo notifications_icon($icons, $item['icon']); ?><span><?php echo notifications_h($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-dashboard-main notifications-main">
        <header class="admin-topbar">
            <div><h1>Notifications</h1></div>
            <div class="admin-topbar-actions">
                <button class="admin-date-pill" type="button"><?php echo notifications_icon($icons, 'calendar'); ?><span><?php echo notifications_h($todayLabel); ?></span></button>
                <a class="admin-notification" href="<?php echo pickled_admin_url('notifications.php'); ?>" aria-label="Notifications"><?php echo notifications_icon($icons, 'bell'); ?><?php if ($unreadCount > 0): ?><span><?php echo min($unreadCount, 9); ?></span><?php endif; ?></a>
                <?php echo pickled_admin_account_menu($adminName, $logoutCsrf, 'topbar'); ?>
            </div>
        </header>

        <section class="notifications-hero admin-page-actions">
            <div>
                <h2>Admin Notifications</h2>
                <p>Send user notifications and review delivery history from live MySQL records.</p>
            </div>
        </section>

        <?php if ($successMsg): ?><div class="alert alert-success"><?php echo notifications_h($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo notifications_h($errorMsg); ?></div><?php endif; ?>

        <section class="notifications-layout">
            <article class="notifications-card notifications-send-card">
                <div class="notifications-card-head">
                    <span><?php echo notifications_icon($icons, 'send'); ?></span>
                    <div><h2>Send Notification</h2><p>Notify one recipient or broadcast to all users.</p></div>
                </div>
                <form method="post" class="notifications-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo notifications_h(pickled_csrf_token()); ?>">
                    <input type="hidden" name="action" value="send_notification">
                    <label><span>Recipient</span><select name="recipient" required>
                        <option value="">Choose recipient</option>
                        <option value="broadcast">Broadcast (All Users)</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>"><?php echo notifications_h((string) $user['name'] . ' (' . (string) $user['email'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select></label>
                    <label><span>Title</span><input type="text" name="title" maxlength="100" required></label>
                    <label><span>Message</span><textarea name="message" rows="5" maxlength="1000" required></textarea></label>
                    <div class="notifications-form-grid">
                        <label><span>Type</span><select name="type" required>
                            <?php foreach ($notificationTypes as $value => $label): ?>
                                <option value="<?php echo notifications_h($value); ?>"><?php echo notifications_h($label); ?></option>
                            <?php endforeach; ?>
                        </select></label>
                        <label><span>Internal Link</span><input type="text" name="link" maxlength="255" pattern="^(resident|admin|coach|auth)/[A-Za-z0-9._/?=&%#-]+$" placeholder="resident/booking.php"></label>
                    </div>
                    <button class="bookings-button primary" type="submit"><?php echo notifications_icon($icons, 'send'); ?> Send Notification</button>
                </form>
            </article>

            <article class="notifications-card notifications-history-card">
                <div class="notifications-card-head">
                    <span><?php echo notifications_icon($icons, 'bell'); ?></span>
                    <div><h2>Notification History</h2><p><?php echo number_format(count($notifications)); ?> shown from <?php echo number_format(count($allNotifications)); ?> records.</p></div>
                </div>

                <form class="notifications-filter-bar" method="get">
                    <label class="notifications-search"><?php echo notifications_icon($icons, 'search'); ?><input type="search" name="q" value="<?php echo notifications_h($search); ?>" placeholder="Search notification"></label>
                    <select name="type"><option value="all">All Types</option><?php foreach ($notificationTypes as $value => $label): ?><option value="<?php echo notifications_h($value); ?>" <?php echo $typeFilter === $value ? 'selected' : ''; ?>><?php echo notifications_h($label); ?></option><?php endforeach; ?></select>
                    <select name="status"><option value="all">All Statuses</option><option value="unread" <?php echo $statusFilter === 'unread' ? 'selected' : ''; ?>>Unread</option><option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option></select>
                    <select name="recipient"><option value="all">All Recipients</option><?php foreach ($users as $user): ?><option value="<?php echo (int) $user['id']; ?>" <?php echo $recipientFilter === (string) $user['id'] ? 'selected' : ''; ?>><?php echo notifications_h((string) $user['name']); ?></option><?php endforeach; ?></select>
                    <button type="submit">Apply</button>
                </form>

                <div class="notifications-table">
                    <div class="notifications-row notifications-row-head"><span>Recipient</span><span>Title</span><span>Message</span><span>Type</span><span>Status</span><span>Date</span><span>Actions</span></div>
                    <?php if (!$notifications): ?>
                        <p class="empty-state">No notifications found.</p>
                    <?php endif; ?>
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                            $safeLink = notifications_safe_href($notification['link'] ?? null);
                            $isRead = !empty($notification['is_read']);
                            $rowParams = array_filter(['q' => $search, 'type' => $typeFilter, 'status' => $statusFilter, 'recipient' => $recipientFilter, 'view_notification' => (int) $notification['id']], static fn($value): bool => $value !== '' && $value !== 'all');
                        ?>
                        <div class="notifications-row">
                            <span><strong><?php echo notifications_h((string) ($notification['user_name'] ?? 'Unknown user')); ?></strong><small><?php echo notifications_h((string) ($notification['user_email'] ?? '')); ?></small></span>
                            <span><strong><?php echo notifications_h((string) $notification['title']); ?></strong></span>
                            <span><?php echo notifications_h(notifications_excerpt((string) $notification['message'])); ?></span>
                            <span><em class="notification-type notification-type--<?php echo notifications_h((string) $notification['type']); ?>"><?php echo notifications_h(notifications_type_label((string) $notification['type'])); ?></em></span>
                            <span><em class="notification-status <?php echo $isRead ? 'is-read' : 'is-unread'; ?>"><?php echo $isRead ? 'Read' : 'Unread'; ?></em></span>
                            <span><?php echo notifications_h(date('M j, Y g:i A', strtotime((string) $notification['created_at']))); ?></span>
                            <span class="notification-actions">
                                <a class="notification-action" href="<?php echo notifications_h(pickled_admin_url('notifications.php?' . http_build_query($rowParams))); ?>"><?php echo notifications_icon($icons, 'eye'); ?> View</a>
                                <?php if (!$isRead): ?>
                                    <form method="post"><input type="hidden" name="csrf_token" value="<?php echo notifications_h(pickled_csrf_token()); ?>"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="notification_id" value="<?php echo (int) $notification['id']; ?>"><button class="notification-action" type="submit"><?php echo notifications_icon($icons, 'check'); ?> Mark as Read</button></form>
                                <?php endif; ?>
                                <form method="post" onsubmit="return confirm('Delete this notification?');"><input type="hidden" name="csrf_token" value="<?php echo notifications_h(pickled_csrf_token()); ?>"><input type="hidden" name="action" value="delete_notification"><input type="hidden" name="notification_id" value="<?php echo (int) $notification['id']; ?>"><button class="notification-action danger" type="submit"><?php echo notifications_icon($icons, 'trash'); ?> Delete</button></form>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
    </main>
</div>

<?php if ($selectedNotification): ?>
    <?php $selectedLink = notifications_safe_href($selectedNotification['link'] ?? null); ?>
    <div class="booking-drawer-backdrop"><a href="<?php echo notifications_h(pickled_admin_url('notifications.php')); ?>" aria-label="Close"></a></div>
    <aside class="booking-drawer notification-detail-drawer" role="dialog" aria-modal="true" aria-label="Notification detail">
        <header><div><span>Notification</span><h2><?php echo notifications_h((string) $selectedNotification['title']); ?></h2></div><a href="<?php echo notifications_h(pickled_admin_url('notifications.php')); ?>">×</a></header>
        <section><h3>Recipient</h3><p><strong>Name</strong><?php echo notifications_h((string) ($selectedNotification['user_name'] ?? 'Unknown user')); ?></p><p><strong>Email</strong><?php echo notifications_h((string) ($selectedNotification['user_email'] ?? '')); ?></p></section>
        <section><h3>Message</h3><p><?php echo nl2br(notifications_h((string) $selectedNotification['message'])); ?></p></section>
        <section><h3>Status</h3><p><strong>Type</strong><?php echo notifications_h(notifications_type_label((string) $selectedNotification['type'])); ?></p><p><strong>Status</strong><?php echo empty($selectedNotification['is_read']) ? 'Unread' : 'Read'; ?></p><p><strong>Date</strong><?php echo notifications_h(date('M j, Y g:i A', strtotime((string) $selectedNotification['created_at']))); ?></p><?php if ($selectedLink !== ''): ?><p><strong>Link</strong><a href="<?php echo notifications_h($selectedLink); ?>">View target</a></p><?php endif; ?></section>
    </aside>
<?php endif; ?>

<script src="<?php echo pickled_admin_asset_url('js/admin.js'); ?>"></script>
</body>
</html>
