<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/repositories/BookingRepository.php';
require_once __DIR__ . '/../app/services/PaymentService.php';
require_once __DIR__ . '/../app/services/FeedbackService.php';
require_once __DIR__ . '/../app/services/BookingExpiryService.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

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
$expiryService = new BookingExpiryService();
$notificationService = new NotificationService();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $message = 'Invalid request. Please refresh and try again.';
    $messageType = 'error';
  } elseif (($_POST['action'] ?? '') === 'upload_payment') {
    try {
      $receiptFile = $_FILES['receipt'] ?? $_FILES['proof_image'] ?? [];
      $paymentService->uploadReceipt(
        $userId,
        $bookingId,
        $receiptFile
      );
      $message = 'Payment receipt uploaded. Please wait for admin review.';
    } catch (RuntimeException $e) {
      $message = $e->getMessage();
      $messageType = 'warning';
    } catch (Throwable $e) {
      error_log('Unexpected payment receipt upload failure on booking details. booking_id=' . (int) ($_POST['booking_id'] ?? 0) . '; user_id=' . $userId . '; error=' . $e->getMessage());
      $message = 'Receipt upload failed. Please try again.';
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
  } elseif (($_POST['action'] ?? '') === 'cancel_booking') {
    try {
      $cancelledBooking = $bookingRepo->cancelForUser($bookingId, $userId);
      $notificationService->notifyBookingCancelled($cancelledBooking);
      $message = !empty($cancelledBooking['cancellation_requires_refund_review'])
        ? 'Your cancellation request has been submitted. Since a receipt was already uploaded, refund review may be required.'
        : 'Booking cancelled. The reserved slot has been released.';
    } catch (RuntimeException $e) {
      $message = $e->getMessage();
      $messageType = 'warning';
    } catch (Throwable $e) {
      error_log('User booking cancellation failed on details. user_id=' . $userId . '; booking_id=' . $bookingId . '; error=' . $e->getMessage());
      $message = 'Unable to cancel booking right now.';
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
  if (str_contains($status, 'expire')) return 'expired';
  if (str_contains($status, 'cancel')) return 'cancelled';
  if (str_contains($status, 'complete')) return 'completed';
  if (str_contains($status, 'confirm')) return 'confirmed';
  return 'pending';
}

function payment_proof_url(string $path): string {
  return '../' . ltrim($path, '/');
}

function payment_proof_absolute_path(string $path): string {
  $relative = ltrim(str_replace('\\', '/', trim($path)), '/');
  if ($relative === '' || str_contains($relative, '..')) {
    return '';
  }

  return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function payment_has_receipt(array $payment): bool {
  return payment_proof_path($payment) !== '';
}

function payment_proof_is_image(string $path): bool {
  return (bool) preg_match('/\.(jpe?g|png|webp)$/i', $path);
}

function payment_proof_path(array $payment): string {
  return trim((string) ($payment['proof_of_payment'] ?? $payment['proof_image'] ?? ''));
}

function booking_detail_payment_state(array $booking, ?array $payment): array {
  $status = strtolower(trim((string) ($payment['status'] ?? $booking['payment_status'] ?? 'pending')));
  $hasProof = $payment ? payment_proof_path($payment) !== '' : false;
  if ($status === 'expired') {
    return ['key' => 'expired', 'label' => 'Expired', 'subtext' => 'This booking expired because payment was not submitted within the required time.'];
  }
  if ($status === 'refund_pending') {
    return ['key' => 'refund_pending', 'label' => 'Refund Review', 'subtext' => 'Cancellation/refund request is waiting for admin review'];
  }
  if ($status === 'refund_rejected') {
    return ['key' => 'rejected', 'label' => 'Cancellation Rejected', 'subtext' => 'Your cancellation/refund request was rejected by admin'];
  }
  if (in_array($status, ['approved', 'verified', 'paid', 'completed'], true)) {
    return ['key' => 'paid', 'label' => 'Paid', 'subtext' => 'Payment verified by admin'];
  }
  if (in_array($status, ['rejected', 'refunded'], true)) {
    return ['key' => 'rejected', 'label' => 'Payment Rejected', 'subtext' => 'Upload a new receipt for review'];
  }
  if ($hasProof) {
    return ['key' => 'uploaded', 'label' => 'Receipt Uploaded', 'subtext' => 'Waiting for admin verification'];
  }
  return ['key' => 'awaiting', 'label' => 'Awaiting Receipt', 'subtext' => 'Upload your GCash receipt to continue review'];
}

function feedback_target_label(array $target): string {
  $label = (string) ($target['court'] ?? 'Session') . ' - ' . (string) ($target['name'] ?? 'Booking');
  if (!empty($target['coach_name'])) {
    $label .= ' with ' . (string) $target['coach_name'];
  }
  return $label;
}

function feedback_is_coach_service(array $target): bool {
  if (empty($target['coach_user_id'])) {
    return false;
  }
  $label = strtolower((string) ($target['name'] ?? '') . ' ' . (string) ($target['category'] ?? ''));
  if (str_contains($label, 'court rental') || str_contains($label, 'social play') || str_contains($label, 'match-play') || str_contains($label, 'tournament') || str_contains($label, 'private package')) {
    return false;
  }
  return str_contains($label, 'training') || str_contains($label, 'lesson') || str_contains($label, 'private coaching');
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
      <?php $statusKey = booking_detail_status_key((string) (($booking['payment_status'] ?? '') === 'expired' ? 'expired' : $booking['status'])); ?>
      <?php $feedbackEligible = $feedbackService->canLeaveFeedback((int) $booking['id'], $userId); ?>
      <?php $latestPaymentStatus = strtolower((string) ($latestPayment['status'] ?? '')); ?>
      <?php $uploadedPayments = array_values(array_filter($payments, 'payment_has_receipt')); ?>
      <?php $uploadedPaymentIds = array_map(static fn(array $payment): int => (int) $payment['id'], $uploadedPayments); ?>
      <?php $latestHasProof = $latestPayment && in_array((int) $latestPayment['id'], $uploadedPaymentIds, true); ?>
      <?php $paymentDeadline = $expiryService->deadlineForBooking($booking); ?>
      <?php $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')); ?>
      <?php $deadlinePassed = $paymentDeadline && $paymentDeadline <= $now; ?>
      <?php $canUpload = $latestPayment && (($latestPaymentStatus === 'rejected') || ($latestPaymentStatus === 'pending' && !$latestHasProof)); ?>
      <?php $showReceiptUpload = $canUpload && !$deadlinePassed && ($booking['payment_status'] ?? '') !== 'paid' && ($booking['payment_status'] ?? '') !== 'expired' && ($booking['status'] ?? '') !== 'cancelled'; ?>
      <?php $detailPaymentState = booking_detail_payment_state($booking, $latestPayment); ?>
      <?php $showPaymentDeadline = $detailPaymentState['key'] === 'awaiting' && $paymentDeadline && !$deadlinePassed; ?>
      <?php $cancelEligibility = $bookingRepo->cancellationEligibility($booking); ?>
      <?php $coachFeedbackTargets = array_values(array_filter($feedbackTargets, 'feedback_is_coach_service')); ?>
      <?php $hasCoachFeedback = !empty($coachFeedbackTargets); ?>
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
            <span>Payment: <?= htmlspecialchars($booking['payment_method']) ?> · <?= htmlspecialchars($detailPaymentState['label']) ?></span>
            <span><?= htmlspecialchars($detailPaymentState['subtext']) ?></span>
            <strong>Total: &#8369;<?= number_format((float) $booking['total'], 2) ?></strong>
          </div>
          <?php if ($showPaymentDeadline): ?>
            <p class="booking-deadline" data-payment-deadline="<?= (int) $paymentDeadline->getTimestamp() ?>">Please upload your receipt within 30 minutes. Time left: <strong data-deadline-countdown>--:--</strong></p>
          <?php endif; ?>

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
            <?php if (($booking['payment_status'] ?? '') === 'expired' || $detailPaymentState['key'] === 'expired'): ?>
              <div class="cart-message cart-message--error booking-detail-inline-message">This booking expired because payment was not submitted within the required time.</div>
            <?php elseif ($showReceiptUpload): ?>
              <div class="cart-message booking-detail-inline-message">Receipt not uploaded yet.</div>
              <form class="booking-detail-upload-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
                <input type="hidden" name="action" value="upload_payment" />
                <label>
                  Receipt Image or PDF
                  <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required />
                </label>
                <button class="checkout-btn" type="submit">Submit Receipt</button>
              </form>
            <?php elseif ($latestPaymentStatus === 'pending' && $latestHasProof): ?>
              <div class="cart-message cart-message--warning booking-detail-inline-message">Receipt uploaded. Waiting for admin verification.</div>
            <?php else: ?>
              <div class="cart-message booking-detail-inline-message">No receipt upload is needed for this booking.</div>
            <?php endif; ?>
          </article>
        </section>

        <?php if (!empty($cancelEligibility['allowed'])): ?>
          <section class="booking-detail-section">
            <div class="booking-detail-section__heading">
              <h2>Cancel Booking</h2>
            </div>
            <form method="post" data-cancel-booking-form>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
              <input type="hidden" name="action" value="cancel_booking" />
              <button class="booking-action booking-action--danger" type="submit"><?= $latestHasProof || in_array(strtolower((string) $booking['payment_status']), ['approved', 'verified', 'paid', 'completed'], true) ? 'Request Cancellation' : 'Cancel Booking' ?></button>
            </form>
          </section>
        <?php elseif (($cancelEligibility['reason'] ?? '') === 'This booking can no longer be cancelled because it is within 24 hours of the scheduled time.'): ?>
          <div class="cart-message cart-message--warning"><?= htmlspecialchars($cancelEligibility['reason']) ?></div>
        <?php endif; ?>

        <?php if ($uploadedPayments): ?>
          <section class="booking-detail-section">
            <div class="booking-detail-section__heading">
              <h2>Receipt History</h2>
            </div>
            <div class="booking-detail-receipts">
              <?php foreach ($uploadedPayments as $payment): ?>
                <?php $proofPath = payment_proof_path($payment); ?>
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
                <p><?= $hasCoachFeedback ? 'Rate your coach and service experience.' : 'Rate the court, facilities, and overall experience.' ?></p>
              <?php endif; ?>

              <?php if ($feedbackEligible && !$feedback): ?>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
                  <input type="hidden" name="action" value="submit_feedback" />
                  <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>" />

                  <?php if ($hasCoachFeedback): ?>
                    <label>
                      Coach service
                      <select name="booking_item_id">
                        <?php foreach ($coachFeedbackTargets as $target): ?>
                          <option value="<?= (int) $target['booking_item_id'] ?>" <?= $feedback && (int) ($feedback['booking_item_id'] ?? 0) === (int) $target['booking_item_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(feedback_target_label($target)) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  <?php endif; ?>

                  <label>
                    <?= $hasCoachFeedback ? 'Coach rating / Service rating' : 'Court/facility rating / Overall experience rating' ?>
                    <select name="rating" required>
                      <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                        <option value="<?= $rating ?>" <?= $feedback && (int) $feedback['rating'] === $rating ? 'selected' : '' ?>><?= $rating ?> / 5</option>
                      <?php endfor; ?>
                    </select>
                  </label>

                  <label>
                    Comment
                    <textarea name="comment" maxlength="1000" required><?= htmlspecialchars((string) ($feedback['comment'] ?? '')) ?></textarea>
                  </label>

                  <button class="checkout-btn" type="submit">Submit feedback</button>
                </form>
              <?php elseif ($feedback): ?>
                <span class="booking-action booking-action--disabled">Feedback Submitted</span>
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
