<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';

pickled_start_secure_session();

if (!pickled_is_logged_in()) {
  header('Location: ../auth/login.php?notice=booking&redirect=resident/booking-details.php');
  exit;
}

$pageTitle = 'Booking Details - Pickled';
$activePage = 'booking.php';
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$bookingId = (int) ($_GET['id'] ?? 0);
$bookingRepo = new BookingRepository();
$booking = $bookingId > 0 ? $bookingRepo->findByIdForUser($bookingId, $userId) : null;
$items = $booking ? $bookingRepo->getBookingItems((int) $booking['id']) : [];
$extraHead = '<link rel="stylesheet" href="../assets/css/cart.css?v=20260430d"/>';

function booking_detail_status_key(string $status): string {
  $status = strtolower($status);
  if (str_contains($status, 'cancel')) return 'cancelled';
  if (str_contains($status, 'complete')) return 'completed';
  if (str_contains($status, 'confirm')) return 'confirmed';
  return 'pending';
}

include __DIR__ . '/../includes/header.php';
?>

<main class="cart-page">
  <div class="cart-shell">
    <div class="cart-top">
      <h1>Booking Details</h1>
      <div class="cart-top-links">
        <a href="booking.php">Booking history</a>
        <a href="courts.php#court-detail">Continue shopping</a>
      </div>
    </div>

    <?php if (!$booking): ?>
      <div class="empty-cart">
        <p>Booking not found.</p>
        <a class="btn btn-green btn-md" href="booking.php">Back to bookings</a>
      </div>
    <?php else: ?>
      <?php $statusKey = booking_detail_status_key((string) $booking['status']); ?>
      <section class="confirmation booking-history">
        <article class="booking-card" data-booking-status="<?= htmlspecialchars($statusKey) ?>">
          <div class="booking-card__header">
            <div><strong>Reference:</strong> <?= htmlspecialchars($booking['reference']) ?></div>
            <div class="booking-card__status booking-card__status--<?= htmlspecialchars($statusKey) ?>"><?= htmlspecialchars(ucfirst($statusKey)) ?></div>
          </div>

          <div class="booking-card__meta">
            <span>Payment: <?= htmlspecialchars($booking['payment_method']) ?> - <?= htmlspecialchars($booking['payment_status']) ?></span>
            <span>Subtotal: &#8369;<?= number_format((float) $booking['subtotal'], 2) ?></span>
            <span>Total: &#8369;<?= number_format((float) $booking['total'], 2) ?></span>
            <span>Booked on: <?= htmlspecialchars($booking['created_at'] ?? '') ?></span>
          </div>

          <?php if (!empty($booking['notes'])): ?>
            <p><?= htmlspecialchars($booking['notes']) ?></p>
          <?php endif; ?>

          <div class="booking-items">
            <?php foreach ($items as $item): ?>
              <div class="booking-item">
                <img src="<?= htmlspecialchars($item['image'] ?? '../assets/img/Hero.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
                <div>
                  <strong><?= htmlspecialchars($item['court']) ?> - <?= htmlspecialchars($item['name']) ?></strong>
                  <p><?= htmlspecialchars($item['category']) ?> - <?= htmlspecialchars($item['duration_label']) ?></p>
                  <p><?= htmlspecialchars($item['booking_date']) ?> <?= htmlspecialchars($item['booking_time']) ?></p>
                  <p>Qty: <?= htmlspecialchars((string) $item['quantity']) ?> - &#8369;<?= number_format((float) $item['unit_price'], 2) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </article>
      </section>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
