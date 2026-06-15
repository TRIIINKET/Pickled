<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';
require_once __DIR__ . '/../app/services/FeedbackService.php';
pickled_start_secure_session();

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
$bookings = $userId > 0 ? $bookingRepo->findByUserId($userId) : [];
$hasBookings = !empty($bookings);
$extraHead = '<link rel="stylesheet" href="../assets/css/cart.css?v=20260615a"/>';

function booking_history_feedback_is_eligible(array $booking, array $items): bool {
  $status = strtolower(trim((string) ($booking['status'] ?? '')));
  if ($status === 'completed') {
    return true;
  }
  if (in_array($status, ['cancelled', 'rejected', 'expired', 'refunded'], true)) {
    return false;
  }

  $paymentStatus = strtolower(trim((string) ($booking['payment_status'] ?? '')));
  if (!in_array($paymentStatus, ['paid', 'verified', 'approved', 'completed'], true) || !$items) {
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

function booking_history_status_key(array $booking, array $items): string {
  $rawStatus = strtolower((string) ($booking['status'] ?? ''));
  if (str_contains($rawStatus, 'cancel') || str_contains($rawStatus, 'reject') || str_contains($rawStatus, 'expire') || str_contains($rawStatus, 'refund')) {
    return 'cancelled';
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
            $existingFeedback = $feedbackService->feedbackForBooking($bookingIdForFeedback, $userId);
            $canLeaveFeedback = $feedbackService->canLeaveFeedback($bookingIdForFeedback, $userId);
          ?>
          <article class="booking-card" data-booking-status="<?= htmlspecialchars($normStatus) ?>" data-payment-status="<?= htmlspecialchars($paymentStatus) ?>">
            <div class="booking-card__header">
              <div>
                <strong>Reference:</strong> <?= htmlspecialchars($booking['reference']) ?>
              </div>
              <div class="booking-card__status booking-card__status--<?= htmlspecialchars($normStatus) ?>"><?= htmlspecialchars(ucfirst($normStatus)) ?></div>
            </div>

            <div class="booking-card__meta">
              <span>Payment: <?= htmlspecialchars($booking['payment_method']) ?> · <?= htmlspecialchars($booking['payment_status']) ?></span>
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
              <?php if ($existingFeedback): ?>
                <a class="booking-action booking-action--feedback" href="booking-details.php?id=<?= $bookingIdForFeedback ?>#booking-feedback">View Feedback</a>
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
        visible = status === 'cancelled';
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
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
