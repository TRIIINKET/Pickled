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
$extraHead = '<link rel="stylesheet" href="../assets/css/cart.css?v=20260617a"/>';

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
    <div class="cart-top booking-detail-nav">
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
      <?php $canUpload = !$latestPayment || (($latestPayment['status'] ?? '') === 'rejected'); ?>
      <?php $showReceiptUpload = $canUpload && ($booking['payment_status'] ?? '') !== 'paid' && ($booking['status'] ?? '') !== 'cancelled'; ?>
      <?php $showFeedbackSection = $statusKey === 'completed' && ($feedback || $feedbackEligible); ?>

      <section class="booking-detail-page">
        <header class="booking-detail-header">
          <div>
            <h1>Booking Details</h1>
            <p>Reference: <strong><?= htmlspecialchars($booking['reference']) ?></strong></p>
          </div>
          <div class="booking-card__status booking-card__status--<?= htmlspecialchars($statusKey) ?>"><?= htmlspecialchars(ucfirst($statusKey)) ?></div>
        </header>

        <section class="booking-detail-section">
          <div class="booking-detail-section__heading">
            <h2>Booking Summary</h2>
            <span><?= htmlspecialchars($booking['created_at'] ?? '') ?></span>
          </div>

          <div class="booking-detail-summary-grid">
            <?php foreach ($items as $item): ?>
              <article class="booking-detail-item">
                <img src="<?= htmlspecialchars($item['image'] ?? '../assets/img/Hero.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
                <div>
                  <h3><?= htmlspecialchars($item['court']) ?> - <?= htmlspecialchars($item['name']) ?></h3>
                  <dl>
                    <div><dt>Date</dt><dd><?= htmlspecialchars($item['booking_date']) ?></dd></div>
                    <div><dt>Time</dt><dd><?= htmlspecialchars($item['booking_time']) ?></dd></div>
                    <div><dt>Players</dt><dd><?= htmlspecialchars((string) $item['quantity']) ?></dd></div>
                    <div><dt>Amount</dt><dd>&#8369;<?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 2) ?></dd></div>
                  </dl>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <div class="booking-detail-totals">
            <span>Payment: <?= htmlspecialchars($booking['payment_method']) ?> · <?= htmlspecialchars(ucfirst((string) ($latestPayment['status'] ?? $booking['payment_status'] ?? 'pending'))) ?></span>
            <strong>Total: &#8369;<?= number_format((float) $booking['total'], 2) ?></strong>
          </div>

          <?php if (!empty($latestPayment['remarks'])): ?>
            <p class="booking-detail-note">Admin remarks: <?= htmlspecialchars($latestPayment['remarks']) ?></p>
          <?php elseif (!empty($booking['notes'])): ?>
            <p class="booking-detail-note"><?= htmlspecialchars($booking['notes']) ?></p>
          <?php endif; ?>
        </section>

        <section class="booking-detail-payment-grid">
          <article class="booking-detail-section booking-detail-payment-card">
            <div class="booking-detail-section__heading">
              <h2>Payment Instructions</h2>
            </div>
            <div class="booking-detail-payment-list">
              <div>
                <span>GCash Number</span>
                <strong id="bookingGcashNumber">0917 123 4567</strong>
                <button type="button" data-copy-target="bookingGcashNumber">Copy</button>
              </div>
              <div><span>Account Name</span><strong>PICKLED SPORTS CENTER</strong></div>
              <div><span>Reference</span><strong><?= htmlspecialchars((string) $booking['reference']) ?></strong></div>
            </div>
            <ol class="booking-detail-steps">
              <li>Send payment through GCash.</li>
              <li>Use your booking reference as the payment note.</li>
              <li>Upload your receipt or screenshot.</li>
              <li>Wait for admin verification.</li>
            </ol>
          </article>

          <article class="booking-detail-section booking-detail-upload-card">
            <div class="booking-detail-section__heading">
              <h2>Upload Receipt</h2>
            </div>
            <?php if ($showReceiptUpload): ?>
              <form class="booking-detail-upload-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
                <input type="hidden" name="action" value="upload_payment" />
                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>" />
                <label>
                  Reference Number
                  <input type="text" name="reference_number" required />
                </label>
                <label>
                  Receipt Image or PDF
                  <input type="file" name="proof_image" accept="image/png,image/jpeg,image/webp,application/pdf,.pdf" required />
                </label>
                <button class="checkout-btn" type="submit">Submit Receipt</button>
              </form>
            <?php elseif (($latestPayment['status'] ?? '') === 'pending'): ?>
              <div class="cart-message cart-message--warning booking-detail-inline-message">Your uploaded receipt is waiting for admin review.</div>
            <?php else: ?>
              <div class="cart-message booking-detail-inline-message">No receipt upload is needed for this booking.</div>
            <?php endif; ?>
          </article>
        </section>

        <?php if ($payments): ?>
          <section class="booking-detail-section">
            <div class="booking-detail-section__heading">
              <h2>Receipt History</h2>
            </div>
            <div class="booking-detail-receipts">
              <?php foreach ($payments as $payment): ?>
                <?php $proofPath = (string) $payment['proof_image']; ?>
                <article>
                  <?php if (payment_proof_is_image($proofPath)): ?>
                    <img src="<?= htmlspecialchars(payment_proof_url($proofPath)) ?>" alt="Payment receipt" />
                  <?php endif; ?>
                  <div>
                    <strong><?= htmlspecialchars(ucfirst((string) $payment['status'])) ?> receipt</strong>
                    <p><a href="<?= htmlspecialchars(payment_proof_url($proofPath)) ?>" target="_blank" rel="noopener">View proof of payment</a></p>
                    <p>Reference No: <?= htmlspecialchars($payment['reference_number']) ?></p>
                    <p>&#8369;<?= number_format((float) $payment['amount'], 2) ?> · <?= htmlspecialchars($payment['payment_method']) ?></p>
                    <p>Uploaded: <?= htmlspecialchars($payment['created_at']) ?></p>
                    <?php if (!empty($payment['remarks'])): ?><p>Remarks: <?= htmlspecialchars($payment['remarks']) ?></p><?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

          <?php if ($showFeedbackSection): ?>
            <section class="booking-detail-section" id="booking-feedback">
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
          <?php endif; ?>
      </section>
    <?php endif; ?>
  </div>
</main>
<script>
(function(){
  document.querySelectorAll("[data-copy-target]").forEach(function(button){
    button.addEventListener("click", function(){
      var target = document.getElementById(button.getAttribute("data-copy-target") || "");
      var value = target ? target.textContent.trim() : "";
      if (!value || !navigator.clipboard) return;
      navigator.clipboard.writeText(value).then(function(){
        button.textContent = "Copied";
        window.setTimeout(function(){ button.textContent = "Copy"; }, 1600);
      }).catch(function(){
        button.textContent = "Copy failed";
        window.setTimeout(function(){ button.textContent = "Copy"; }, 1600);
      });
    });
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
