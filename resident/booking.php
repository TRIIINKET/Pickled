<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';
require_once __DIR__ . '/../app/services/FeedbackService.php';
require_once __DIR__ . '/../app/services/PaymentService.php';
require_once __DIR__ . '/../app/services/BookingExpiryService.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
pickled_start_secure_session();
pickled_init_csrf();

if (!pickled_is_logged_in()) {
  unset($_SESSION['user'], $_SESSION['membership'], $_SESSION['cart'], $_SESSION['cart_started_at'], $_SESSION['cart_expires_at'], $_SESSION['last_booking'], $_SESSION['waitlist']);
  header('Location: ../auth/login.php?notice=booking&redirect=resident/booking.php');
  exit;
}

$pageTitle = 'Booking Status - Pickled';
$activePage = 'booking.php';
$userId = (int) ($_SESSION['user']['id'] ?? 0);
pickled_process_pending_booking_expiry();
$bookingRepo = new BookingRepository();
$feedbackService = new FeedbackService();
$paymentService = new PaymentService();
$expiryService = new BookingExpiryService();
$notificationService = new NotificationService();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $message = 'Invalid request. Please refresh and try again.';
    $messageType = 'error';
  } elseif (($_POST['action'] ?? '') === 'cancel_booking') {
    try {
      $cancelledBooking = $bookingRepo->cancelForUser((int) ($_POST['booking_id'] ?? 0), $userId);
      $notificationService->notifyBookingCancelled($cancelledBooking);
      $message = !empty($cancelledBooking['cancellation_requires_refund_review'])
        ? 'Your cancellation request has been submitted. Since a receipt was already uploaded, refund review may be required.'
        : 'Booking cancelled. The reserved slot has been released.';
    } catch (RuntimeException $e) {
      $message = $e->getMessage();
      $messageType = 'warning';
    } catch (Throwable $e) {
      error_log('User booking cancellation failed. user_id=' . $userId . '; booking_id=' . (int) ($_POST['booking_id'] ?? 0) . '; error=' . $e->getMessage());
      $message = 'Unable to cancel booking right now.';
      $messageType = 'warning';
    }
  }
}

$bookings = $userId > 0 ? $bookingRepo->findByUserId($userId) : [];
$hasBookings = !empty($bookings);
$extraHead = '<link rel="stylesheet" href="../assets/css/cart.css?v=20260615a"/>';

function booking_history_feedback_is_eligible(array $booking, array $items): bool {
  $status = strtolower(trim((string) ($booking['status'] ?? '')));
  if ($status !== 'completed') {
    return false;
  }
  if (!$items) {
    return false;
  }

  $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
  foreach ($items as $item) {
    $date = trim((string) ($item['booking_date_raw'] ?? ''));
    $end = trim((string) ($item['end_time'] ?? ''));
    if ($date === '' || $end === '') {
      return false;
    }
    try {
      if (new DateTimeImmutable($date . ' ' . $end, new DateTimeZone('Asia/Manila')) > $now) {
        return false;
      }
    } catch (Throwable) {
      return false;
    }
  }

  return true;
}

function booking_history_proof_path(?array $payment): string {
  if (!$payment) {
    return '';
  }
  return trim((string) ($payment['proof_of_payment'] ?? $payment['proof_image'] ?? ''));
}

function booking_history_payment_state(array $booking, ?array $latestPayment): array {
  $status = strtolower(trim((string) ($latestPayment['status'] ?? $booking['payment_status'] ?? 'pending')));
  $proofPath = booking_history_proof_path($latestPayment);
  $hasProof = $proofPath !== '';
  if ($status === 'expired') {
    return ['key' => 'expired', 'label' => 'Expired', 'subtext' => 'This booking expired because payment was not submitted within the required time.', 'has_proof' => $hasProof];
  }
  if ($status === 'refund_pending') {
    return ['key' => 'refund_pending', 'label' => 'Refund Review', 'subtext' => 'Cancellation/refund request is waiting for admin review', 'has_proof' => $hasProof];
  }
  if ($status === 'refund_rejected') {
    return ['key' => 'rejected', 'label' => 'Cancellation Rejected', 'subtext' => 'Your cancellation/refund request was rejected by admin', 'has_proof' => $hasProof];
  }
  if (in_array($status, ['approved', 'verified', 'paid', 'completed'], true)) {
    return ['key' => 'paid', 'label' => 'Paid', 'subtext' => 'Payment verified by admin', 'has_proof' => $hasProof];
  }
  if (in_array($status, ['rejected', 'refunded'], true)) {
    return ['key' => 'rejected', 'label' => 'Payment Rejected', 'subtext' => 'Upload a new receipt for review', 'has_proof' => $hasProof];
  }
  if ($hasProof) {
    return ['key' => 'uploaded', 'label' => 'Receipt Uploaded', 'subtext' => 'Waiting for admin verification', 'has_proof' => true];
  }
  return ['key' => 'awaiting', 'label' => 'Awaiting Receipt', 'subtext' => 'Upload your GCash receipt to continue review', 'has_proof' => false];
}

function booking_history_status_key(array $booking, array $items): string {
  $rawStatus = strtolower((string) ($booking['status'] ?? ''));
  $paymentStatus = strtolower((string) ($booking['payment_status'] ?? ''));
  if ($paymentStatus === 'expired') {
    return 'expired';
  }
  if (str_contains($rawStatus, 'cancel') || str_contains($rawStatus, 'reject') || str_contains($rawStatus, 'expire') || str_contains($rawStatus, 'refund')) {
    return str_contains($rawStatus, 'expire') ? 'expired' : 'cancelled';
  }
  if (str_contains($rawStatus, 'complete') || booking_history_feedback_is_eligible($booking, $items)) {
    return 'completed';
  }
  if (str_contains($rawStatus, 'ongoing')) {
    return 'ongoing';
  }
  if (str_contains($rawStatus, 'confirm') || str_contains($rawStatus, 'approve') || str_contains($rawStatus, 'paid')) {
    return 'confirmed';
  }
  return 'pending';
}

include __DIR__ . '/../includes/header.php';
?>

<main class="cart-page">
  <div class="cart-shell">
    <div class="cart-top">
      <h1>Booking Status</h1>
      <div class="cart-top-links">
        <a href="cart.php">View cart</a>
        <a href="courts.php#court-detail">Continue shopping</a>
      </div>
    </div>

    <?php if ($hasBookings): ?>
      <?php if ($message): ?>
        <div class="cart-message cart-message--<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <div class="booking-filters">
        <button type="button" data-booking-filter="all" class="is-selected">All</button>
        <button type="button" data-booking-filter="pending">Pending</button>
        <button type="button" data-booking-filter="confirmed">Confirmed</button>
        <button type="button" data-booking-filter="ongoing">Ongoing</button>
        <button type="button" data-booking-filter="completed">Completed</button>
        <button type="button" data-booking-filter="cancelled">Cancelled</button>
      </div>
      <section class="confirmation booking-history">
        <h2>Booking History</h2>
        <?php foreach ($bookings as $booking): ?>
          <?php $items = $bookingRepo->getBookingItems((int) $booking['id']); ?>
          <?php
            $paymentStatus = strtolower((string) $booking['payment_status']);
            $normStatus = booking_history_status_key($booking, $items);
            $bookingIdForFeedback = (int) $booking['id'];
            $latestPayment = $paymentService->latestForBooking($bookingIdForFeedback);
            $paymentState = booking_history_payment_state($booking, $latestPayment);
            $paymentDeadline = $expiryService->deadlineForBooking($booking);
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
            $deadlinePassed = $paymentDeadline && $paymentDeadline <= $now;
            $showPaymentDeadline = $paymentState['key'] === 'awaiting' && $paymentDeadline && !$deadlinePassed;
            $canUploadReceipt = in_array($paymentState['key'], ['awaiting', 'rejected'], true) && !$deadlinePassed && $paymentState['key'] !== 'expired';
            $cancelEligibility = $bookingRepo->cancellationEligibility($booking);
            $existingFeedback = $feedbackService->feedbackForBooking($bookingIdForFeedback, $userId);
            $canLeaveFeedback = $feedbackService->canLeaveFeedback($bookingIdForFeedback, $userId);
          ?>
          <article class="booking-card" data-booking-status="<?= htmlspecialchars($normStatus) ?>" data-payment-status="<?= htmlspecialchars($paymentState['key']) ?>">
            <div class="booking-card__header">
              <div>
                <strong>Reference:</strong> <?= htmlspecialchars($booking['reference']) ?>
              </div>
              <div class="booking-card__status-group">
                <div class="booking-card__status booking-card__status--<?= htmlspecialchars($normStatus) ?>">Booking: <?= htmlspecialchars(ucfirst($normStatus)) ?></div>
                <div class="booking-card__status payment-state payment-state--<?= htmlspecialchars($paymentState['key']) ?>"><?= htmlspecialchars($paymentState['label']) ?></div>
              </div>
            </div>

            <div class="booking-card__meta">
              <span>Payment method: <?= htmlspecialchars($booking['payment_method']) ?></span>
              <span><?= htmlspecialchars($paymentState['subtext']) ?></span>
              <?php if ($showPaymentDeadline): ?>
                <span class="booking-deadline" data-payment-deadline="<?= (int) $paymentDeadline->getTimestamp() ?>">Please upload your receipt within 30 minutes. Time left: <strong data-deadline-countdown>--:--</strong></span>
              <?php endif; ?>
              <span>Total: ₱<?= number_format((float) $booking['total'], 2) ?></span>
              <span>Booked on: <?= htmlspecialchars($booking['created_at'] ?? '') ?></span>
            </div>

            <?php if (!empty($items)): ?>
              <div class="booking-items">
                <?php foreach ($items as $item): ?>
                  <div class="booking-item">
                    <img src="<?= htmlspecialchars($item['image'] ?? '../assets/img/Hero.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
                    <div>
                      <strong><?= htmlspecialchars($item['court']) ?> · <?= htmlspecialchars($item['name']) ?></strong>
                      <p><?= htmlspecialchars($item['category']) ?> · <?= htmlspecialchars($item['duration_label']) ?></p>
                      <p><?= htmlspecialchars($item['booking_date']) ?> <?= htmlspecialchars($item['booking_time']) ?></p>
                      <p>Qty: <?= htmlspecialchars((string) $item['quantity']) ?> · ₱<?= number_format((float) $item['unit_price'], 2) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="booking-card__actions">
              <a class="booking-action booking-action--secondary" href="booking-details.php?id=<?= $bookingIdForFeedback ?>">View details</a>
              <?php if ($canUploadReceipt): ?>
                <a class="booking-action booking-action--upload" href="booking-details.php?id=<?= $bookingIdForFeedback ?>">Upload <?= $paymentState['key'] === 'rejected' ? 'New ' : '' ?>Receipt</a>
              <?php elseif ($paymentState['key'] === 'uploaded'): ?>
                <a class="booking-action booking-action--secondary" href="booking-details.php?id=<?= $bookingIdForFeedback ?>">View Receipt</a>
              <?php endif; ?>
              <?php if (!empty($cancelEligibility['allowed'])): ?>
                <form class="booking-inline-form" method="post" data-cancel-booking-form>
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
                  <input type="hidden" name="action" value="cancel_booking" />
                  <input type="hidden" name="booking_id" value="<?= $bookingIdForFeedback ?>" />
                  <button class="booking-action booking-action--danger" type="submit"><?= $paymentState['has_proof'] || in_array(strtolower((string) $booking['payment_status']), ['approved', 'verified', 'paid', 'completed'], true) ? 'Request Cancellation' : 'Cancel Booking' ?></button>
                </form>
              <?php elseif (($cancelEligibility['reason'] ?? '') === 'This booking can no longer be cancelled because it is within 24 hours of the scheduled time.'): ?>
                <span class="booking-action booking-action--disabled"><?= htmlspecialchars($cancelEligibility['reason']) ?></span>
              <?php endif; ?>
              <?php if ($existingFeedback): ?>
                <span class="booking-action booking-action--disabled">Feedback Submitted</span>
              <?php elseif ($canLeaveFeedback): ?>
                <a class="booking-action booking-action--feedback" href="booking-details.php?id=<?= $bookingIdForFeedback ?>#booking-feedback">Leave Feedback</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php else: ?>
      <div class="empty-cart">
        <p>No recent bookings yet. You may need to checkout or add a reservation to your cart first.</p>
        <a class="btn btn-green btn-md" href="courts.php#court-detail">Browse courts</a>
      </div>
    <?php endif; ?>
  </div>
</main>

<script>
(function(){
  var buttons = document.querySelectorAll('[data-booking-filter]');
  var cards = document.querySelectorAll('.booking-card');
  if (!buttons.length || !cards.length) return;

  function applyFilter(filter) {
    cards.forEach(function(card) {
      var status = (card.dataset.bookingStatus || '').toLowerCase();
      var payment = (card.dataset.paymentStatus || '').toLowerCase();
      var visible = true;

      if (filter === 'completed') {
        visible = status === 'completed';
      } else if (filter === 'pending') {
        visible = status === 'pending';
      } else if (filter === 'confirmed') {
        visible = status === 'confirmed';
      } else if (filter === 'ongoing') {
        visible = status === 'ongoing';
      } else if (filter === 'cancelled') {
        visible = status === 'cancelled' || status === 'expired';
      }

      card.style.display = visible ? '' : 'none';
    });
  }

  buttons.forEach(function(button) {
    button.addEventListener('click', function() {
      buttons.forEach(function(btn) { btn.classList.remove('is-selected'); });
      button.classList.add('is-selected');
      applyFilter(button.dataset.bookingFilter);
    });
  });

  document.querySelectorAll("[data-cancel-booking-form]").forEach(function(form) {
    form.addEventListener("submit", function(event) {
      if (!window.confirm("Are you sure you want to cancel this booking?")) {
        event.preventDefault();
      }
    });
  });

  function tickPaymentDeadlines() {
    var now = Math.floor(Date.now() / 1000);
    document.querySelectorAll("[data-payment-deadline]").forEach(function(node) {
      var deadline = parseInt(node.getAttribute("data-payment-deadline") || "0", 10);
      var countdown = node.querySelector("[data-deadline-countdown]");
      if (!deadline || !countdown) return;
      var remaining = Math.max(0, deadline - now);
      var minutes = Math.floor(remaining / 60);
      var seconds = remaining % 60;
      countdown.textContent = String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
      if (remaining <= 0) {
        node.textContent = "This booking expired because payment was not submitted within the required time.";
      }
    });
  }
  tickPaymentDeadlines();
  window.setInterval(tickPaymentDeadlines, 1000);
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
