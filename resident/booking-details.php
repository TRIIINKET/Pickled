<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';
require_once __DIR__ . '/../app/services/PaymentService.php';
require_once __DIR__ . '/../app/services/FeedbackService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (!pickled_is_logged_in()) {
  header('Location: ../auth/login.php?notice=booking&redirect=resident/booking-details.php');
  exit;
}

$pageTitle = 'Booking Details - Pickled';
$activePage = 'booking.php';
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$bookingId = (int) ($_GET['id'] ?? 0);
pickled_process_pending_booking_expiry();
$bookingRepo = new BookingRepository();
$paymentService = new PaymentService();
$feedbackService = new FeedbackService();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $message = 'Invalid request. Please refresh and try again.';
    $messageType = 'error';
  } elseif (($_POST['action'] ?? '') === 'upload_payment') {
    try {
      $paymentService->uploadReceipt(
        $userId,
        (int) ($_POST['booking_id'] ?? 0),
        $_FILES['proof_image'] ?? [],
        (string) ($_POST['reference_number'] ?? '')
      );
      $message = 'Payment receipt uploaded. Please wait for admin review.';
    } catch (RuntimeException $e) {
      $message = $e->getMessage();
      $messageType = 'warning';
    }
  } elseif (($_POST['action'] ?? '') === 'submit_feedback') {
    try {
      $feedbackService->submit(
        $userId,
        (int) ($_POST['booking_id'] ?? 0),
        empty($_POST['booking_item_id']) ? null : (int) $_POST['booking_item_id'],
        (int) ($_POST['rating'] ?? 0),
        (string) ($_POST['comment'] ?? '')
      );
      $message = 'Thanks for your feedback.';
    } catch (RuntimeException $e) {
      $message = $e->getMessage();
      $messageType = 'warning';
    }
  } elseif (($_POST['action'] ?? '') === 'update_feedback') {
    try {
      $feedbackService->update(
        $userId,
        (int) ($_POST['booking_id'] ?? 0),
        empty($_POST['booking_item_id']) ? null : (int) $_POST['booking_item_id'],
        (int) ($_POST['rating'] ?? 0),
        (string) ($_POST['comment'] ?? '')
      );
      $message = 'Feedback updated.';
    } catch (RuntimeException $e) {
      $message = $e->getMessage();
      $messageType = 'warning';
    }
  }
}

$booking = $bookingId > 0 ? $bookingRepo->findByIdForUser($bookingId, $userId) : null;
$items = $booking ? $bookingRepo->getBookingItems((int) $booking['id']) : [];
$payments = $booking ? $paymentService->paymentsForBooking((int) $booking['id']) : [];
$feedback = $booking ? $feedbackService->feedbackForBooking((int) $booking['id'], $userId) : null;
$feedbackTargets = $booking ? $feedbackService->targetsForBooking((int) $booking['id'], $userId) : [];
$latestPayment = $payments[0] ?? null;
$extraHead = '<link rel="stylesheet" href="../assets/css/cart.css?v=20260615a"/>';

function booking_detail_status_key(string $status): string {
  $status = strtolower($status);
  if (str_contains($status, 'cancel')) return 'cancelled';
  if (str_contains($status, 'complete')) return 'completed';
  if (str_contains($status, 'confirm')) return 'confirmed';
  return 'pending';
}

function payment_proof_url(string $path): string {
  return '../' . ltrim($path, '/');
}

function payment_proof_is_image(string $path): bool {
  return (bool) preg_match('/\.(jpe?g|png|webp)$/i', $path);
}

function feedback_target_label(array $target): string {
  $label = (string) ($target['court'] ?? 'Session') . ' - ' . (string) ($target['name'] ?? 'Booking');
  if (!empty($target['coach_name'])) {
    $label .= ' with ' . (string) $target['coach_name'];
  }
  return $label;
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

    <?php if ($message): ?>
      <div class="cart-message cart-message--<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$booking): ?>
      <div class="empty-cart">
        <p>Booking not found.</p>
        <a class="btn btn-green btn-md" href="booking.php">Back to bookings</a>
      </div>
    <?php else: ?>
      <?php $statusKey = booking_detail_status_key((string) $booking['status']); ?>
      <?php $feedbackEligible = $feedbackService->canLeaveFeedback((int) $booking['id'], $userId); ?>
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

          <section class="booking-items">
            <div class="booking-item">
              <div>
                <strong>Payment Status</strong>
                <p><?= htmlspecialchars(ucfirst((string) ($latestPayment['status'] ?? $booking['payment_status'] ?? 'pending'))) ?></p>
                <?php if (!empty($latestPayment['reference_number'])): ?>
                  <p>Reference No: <?= htmlspecialchars($latestPayment['reference_number']) ?></p>
                <?php endif; ?>
                <?php if (!empty($latestPayment['remarks'])): ?>
                  <p>Admin remarks: <?= htmlspecialchars($latestPayment['remarks']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </section>

          <?php $canUpload = !$latestPayment || (($latestPayment['status'] ?? '') === 'rejected'); ?>
          <?php if ($canUpload && ($booking['payment_status'] ?? '') !== 'paid' && ($booking['status'] ?? '') !== 'cancelled'): ?>
            <form class="checkout-card" method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
              <input type="hidden" name="action" value="upload_payment" />
              <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>" />
              <h2>Upload Payment Receipt</h2>
              <label>
                Reference Number
                <input type="text" name="reference_number" required />
              </label>
              <label>
                Receipt Image or PDF
                <input type="file" name="proof_image" accept="image/png,image/jpeg,image/webp,application/pdf,.pdf" required />
              </label>
              <button class="checkout-btn" type="submit">Submit receipt</button>
            </form>
          <?php elseif (($latestPayment['status'] ?? '') === 'pending'): ?>
            <div class="cart-message cart-message--warning">Your uploaded receipt is waiting for admin review.</div>
          <?php endif; ?>

          <?php if ($payments): ?>
            <div class="booking-items">
              <?php foreach ($payments as $payment): ?>
                <div class="booking-item">
                  <?php $proofPath = (string) $payment['proof_image']; ?>
                  <?php if (payment_proof_is_image($proofPath)): ?>
                    <img src="<?= htmlspecialchars(payment_proof_url($proofPath)) ?>" alt="Payment receipt" />
                  <?php endif; ?>
                  <div>
                    <strong><?= htmlspecialchars(ucfirst((string) $payment['status'])) ?> receipt</strong>
                    <p><a href="<?= htmlspecialchars(payment_proof_url($proofPath)) ?>" target="_blank" rel="noopener">View proof of payment</a></p>
                    <p>Reference No: <?= htmlspecialchars($payment['reference_number']) ?></p>
                    <p>Amount: &#8369;<?= number_format((float) $payment['amount'], 2) ?> - <?= htmlspecialchars($payment['payment_method']) ?></p>
                    <p>Uploaded: <?= htmlspecialchars($payment['created_at']) ?></p>
                    <?php if (!empty($payment['remarks'])): ?><p>Remarks: <?= htmlspecialchars($payment['remarks']) ?></p><?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

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

          <?php if ($feedback || $feedbackEligible): ?>
            <section class="checkout-card" id="booking-feedback">
              <h2><?= $feedback ? 'Your Feedback' : 'Share Feedback' ?></h2>
              <?php if ($feedback): ?>
                <p><strong>Current rating:</strong> <?= (int) $feedback['rating'] ?> / 5</p>
                <p><?= htmlspecialchars((string) $feedback['comment']) ?></p>
                <?php if (!empty($feedback['coach_name'])): ?>
                  <p>Coach: <?= htmlspecialchars((string) $feedback['coach_name']) ?></p>
                <?php endif; ?>
              <?php else: ?>
                <p>Tell us about your booking experience or anything you want the team to know.</p>
              <?php endif; ?>

              <?php if ($feedbackEligible): ?>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
                  <input type="hidden" name="action" value="<?= $feedback ? 'update_feedback' : 'submit_feedback' ?>" />
                  <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>" />

                  <?php if ($feedbackTargets): ?>
                    <label>
                      Session or coach
                      <select name="booking_item_id">
                        <option value="">Overall booking</option>
                        <?php foreach ($feedbackTargets as $target): ?>
                          <option value="<?= (int) $target['booking_item_id'] ?>" <?= $feedback && (int) ($feedback['booking_item_id'] ?? 0) === (int) $target['booking_item_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(feedback_target_label($target)) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  <?php endif; ?>

                  <label>
                    Rating
                    <select name="rating" required>
                      <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                        <option value="<?= $rating ?>" <?= $feedback && (int) $feedback['rating'] === $rating ? 'selected' : '' ?>><?= $rating ?> / 5</option>
                      <?php endfor; ?>
                    </select>
                  </label>

                  <label>
                    Comment
                    <textarea name="comment" required><?= htmlspecialchars((string) ($feedback['comment'] ?? '')) ?></textarea>
                  </label>

                  <button class="checkout-btn" type="submit"><?= $feedback ? 'Update feedback' : 'Submit feedback' ?></button>
                </form>
              <?php endif; ?>
            </section>
          <?php else: ?>
            <div class="cart-message cart-message--warning">Feedback is not available for this booking.</div>
          <?php endif; ?>
        </article>
      </section>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
