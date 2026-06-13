<?php
$pageTitle = 'Notifications';
$activePage = 'notifications';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

$adminService = new AdminService();
$userRepo = new UserRepository();
$successMsg = '';
$errorMsg = '';
$notificationTypes = [
    'info' => 'Info',
    'success' => 'Success',
    'warning' => 'Warning',
    'error' => 'Error',
];

function admin_notification_type_label(string $type): string {
    return ucwords(str_replace('_', ' ', $type));
}

function admin_notification_safe_href(?string $link): string {
    $link = trim((string) $link);
    if ($link === '') {
        return '';
    }

    $parts = parse_url($link);
    if ($parts === false) {
        return '';
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($scheme !== '') {
        return in_array($scheme, ['http', 'https'], true) ? $link : '';
    }

    if (function_exists('pickled_frontend_url') && preg_match('#^(resident|admin|coach|auth)/#', $link)) {
        return pickled_frontend_url($link);
    }

    return $link;
}

function admin_notification_excerpt(string $message): string {
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($message, 0, 120, '...');
    }

    return strlen($message) > 120 ? substr($message, 0, 117) . '...' : $message;
}

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'send_notification') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $body = trim((string) ($_POST['message'] ?? ''));
            $type = (string) ($_POST['type'] ?? 'info');
            $link = trim((string) ($_POST['link'] ?? '')) ?: null;

            if ($title === '' || $body === '') {
                $errorMsg = 'Title and message are required';
            } elseif ($userId === 0) {
                // Broadcast
                $count = $adminService->sendBroadcastNotification(
                    $title,
                    $body,
                    (int) $_SESSION['user']['id'],
                    $type,
                    $link
                );
                if ($count > 0) {
                    $successMsg = "Broadcast sent to $count users";
                } else {
                    $errorMsg = 'Failed to send broadcast';
                }
            } else {
                if ($adminService->sendNotification(
                    $userId,
                    $title,
                    $body,
                    (int) $_SESSION['user']['id'],
                    $type,
                    $link
                )) {
                    $successMsg = 'Notification sent successfully';
                } else {
                    $errorMsg = 'Failed to send notification';
                }
            }
        } elseif ($action === 'delete_notification') {
            $id = (int) ($_POST['notification_id'] ?? 0);
            if ($adminService->deleteNotification($id)) {
                $successMsg = 'Notification deleted';
            } else {
                $errorMsg = 'Failed to delete notification';
            }
        }
    }
}

$users = $userRepo->findAll() ?? [];
$notifications = $adminService->getAllNotifications(100);
?>

<?php require_once __DIR__ . '/../includes/admin-navbar.php'; ?>

<main class="admin-main">
    <div class="container">
        <div class="admin-header">
            <h1>Notifications</h1>
            <p class="admin-subtitle">Send and manage notifications to users</p>
        </div>
        
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        
        <div class="grid-2">
            <!-- Send Notification -->
            <section>
                <h2>Send Notification</h2>
                <form method="POST" class="form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                    <input type="hidden" name="action" value="send_notification">
                    
                    <div class="form-group">
                        <label for="user_id">Send To</label>
                        <select id="user_id" name="user_id" required>
                            <option value="0">Broadcast (All Users)</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo (int) $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type">
                            <?php foreach ($notificationTypes as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="link">Link (Optional)</label>
                        <input type="text" id="link" name="link" placeholder="resident/booking.php">
                    </div>
                    
                    <button type="submit" class="btn btn-success">Send Notification</button>
                </form>
            </section>
            
            <!-- Notification History -->
            <section>
                <h2>Notification History</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Type</th>
                                <th>Notification</th>
                                <th>Status</th>
                                <th>Link</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$notifications): ?>
                                <tr>
                                    <td colspan="7">No notifications have been sent yet.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php $safeLink = admin_notification_safe_href($notification['link'] ?? null); ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($notification['user_name'] ?? 'Unknown user'); ?><br>
                                        <small><?php echo htmlspecialchars($notification['user_email'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars(admin_notification_type_label((string) $notification['type'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($notification['title']); ?></strong><br>
                                        <small><?php echo htmlspecialchars(admin_notification_excerpt((string) $notification['message'])); ?></small>
                                    </td>
                                    <td><?php echo empty($notification['is_read']) ? 'Unread' : 'Read'; ?></td>
                                    <td>
                                        <?php if ($safeLink !== ''): ?>
                                            <a href="<?php echo htmlspecialchars($safeLink); ?>">Open</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime((string) $notification['created_at'])); ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Delete this notification?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="delete_notification">
                                            <input type="hidden" name="notification_id" value="<?php echo (int) $notification['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
