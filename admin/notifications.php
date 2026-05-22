<?php
$pageTitle = 'Notifications';
$activePage = 'notifications';
require_once __DIR__ . '/includes/_header.php';
require_once __DIR__ . '/../backend/services/AdminService.php';
require_once __DIR__ . '/../backend/repositories/UserRepository.php';

$adminService = new AdminService();
$userRepo = new UserRepository();
$successMsg = '';
$errorMsg = '';

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'send_notification') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId === 0) {
                // Broadcast
                $count = $adminService->sendBroadcastNotification(
                    $_POST['title'] ?? '',
                    $_POST['message'] ?? '',
                    $_POST['type'] ?? 'info',
                    $_SESSION['user']['id']
                );
                if ($count > 0) {
                    $successMsg = "Broadcast sent to $count users";
                } else {
                    $errorMsg = 'Failed to send broadcast';
                }
            } else {
                if ($adminService->sendNotification(
                    $userId,
                    $_POST['title'] ?? '',
                    $_POST['message'] ?? '',
                    $_POST['type'] ?? 'info',
                    $_POST['link'] ?? null,
                    $_SESSION['user']['id']
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

$users = $userRepo->findAll();
$notifications = $adminService->getAdminLogs(50);
?>

<?php require_once __DIR__ . '/includes/_navbar.php'; ?>

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
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
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
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="link">Link (Optional)</label>
                        <input type="text" id="link" name="link" placeholder="/admin/manage-bookings.php">
                    </div>
                    
                    <button type="submit" class="btn btn-success">Send Notification</button>
                </form>
            </section>
            
            <!-- Notification History -->
            <section>
                <h2>Activity Log</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($notifications, 0, 20) as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['entity_type'] ?? '-'); ?> #<?php echo $log['entity_id'] ?? '-'; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>
