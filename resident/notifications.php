<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (!pickled_is_logged_in()) {
  header('Location: ../auth/login.php?redirect=resident/notifications.php');
  exit;
}

pickled_process_pending_booking_expiry();

$notificationService = new NotificationService();
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $message = 'Invalid request. Please refresh and try again.';
    $messageType = 'error';
  } elseif (($_POST['action'] ?? '') === 'mark_all') {
    $notificationService->markAllAsRead($userId);
    $message = 'All notifications marked as read.';
  } elseif (($_POST['action'] ?? '') === 'mark_read') {
    $notificationService->markAsRead((int) ($_POST['notification_id'] ?? 0), $userId);
    $message = 'Notification marked as read.';
  }
}

$notifications = $notificationService->notificationsForUser($userId, 80);
$unreadCount = $notificationService->unreadCount($userId);
$pageTitle = 'Notifications - Pickled';
$activePage = 'notifications.php';
$extraHead = '<link rel="stylesheet" href="../assets/css/cart.css?v=20260430d"/>';

function notification_label(string $type): string {
  return ucwords(str_replace('_', ' ', $type));
}

function notification_safe_href(?string $link): string {
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

include __DIR__ . '/../includes/header.php';
?>

<main class="cart-page">
  <div class="cart-shell">
    <div class="cart-top">
      <h1>Notifications</h1>
      <div class="cart-top-links">
        <a href="booking.php">Booking history</a>
        <a href="cart.php">View cart</a>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="cart-message cart-message--<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <section class="confirmation booking-history">
      <div class="booking-card__header">
        <div>
          <strong><?= number_format($unreadCount) ?></strong> unread notification<?= $unreadCount === 1 ? '' : 's' ?>
        </div>
        <?php if ($unreadCount > 0): ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
            <input type="hidden" name="action" value="mark_all" />
            <button class="btn btn-green btn-sm" type="submit">Mark all as read</button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (!$notifications): ?>
        <div class="empty-cart">
          <p>No notifications yet.</p>
          <a class="btn btn-green btn-md" href="courts.php#court-detail">Browse courts</a>
        </div>
      <?php endif; ?>

      <?php foreach ($notifications as $notification): ?>
        <?php $notificationLink = notification_safe_href($notification['link'] ?? null); ?>
        <article class="booking-card" data-notification-type="<?= htmlspecialchars($notification['type']) ?>">
          <div class="booking-card__header">
            <div>
              <strong><?= htmlspecialchars($notification['title']) ?></strong>
              <small><?= htmlspecialchars(notification_label((string) $notification['type'])) ?></small>
            </div>
            <div class="booking-card__status booking-card__status--<?= empty($notification['is_read']) ? 'pending' : 'confirmed' ?>">
              <?= empty($notification['is_read']) ? 'Unread' : 'Read' ?>
            </div>
          </div>
          <p><?= htmlspecialchars($notification['message']) ?></p>
          <div class="booking-card__meta">
            <span><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) $notification['created_at']))) ?></span>
            <?php if ($notificationLink !== ''): ?>
              <a href="<?= htmlspecialchars($notificationLink) ?>">View details</a>
            <?php endif; ?>
            <?php if (empty($notification['is_read'])): ?>
              <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
                <input type="hidden" name="action" value="mark_read" />
                <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>" />
                <button class="btn btn-green btn-sm" type="submit">Mark as read</button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
