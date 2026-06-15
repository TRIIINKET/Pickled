<?php
declare(strict_types=1);

$pageTitle  = 'Private Events - Pickled';
$activePage = 'private.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../assets/css/private.css?v=20260615a"/>';

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/PrivatePackageService.php';
require_once __DIR__ . '/../app/services/PrivateInquiryService.php';

pickled_start_secure_session();
pickled_init_csrf();

$packageService = new PrivatePackageService();
$inquiryService = new PrivateInquiryService();
$user = $_SESSION['user'] ?? null;
$userId = (int) ($user['id'] ?? 0);
$isPlayer = ($user['role'] ?? '') === 'player';
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$isPlayer) {
    header('Location: ' . pickled_frontend_url('auth/login.php?redirect=resident/private.php'));
    exit;
  }

  if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
    $errorMsg = 'Invalid form submission. Please try again.';
  } else {
    try {
      $inquiryService->submit($userId, (int) ($_POST['private_package_id'] ?? 0), (string) ($_POST['message'] ?? ''));
      $successMsg = 'Your private package inquiry was sent.';
    } catch (Throwable $e) {
      error_log('Private inquiry submission failed: ' . $e->getMessage());
      $errorMsg = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to send your inquiry right now.';
    }
  }
}

try {
  $packages = $packageService->availablePackages();
  $myInquiries = $isPlayer ? $inquiryService->inquiriesForUser($userId, 20) : [];
} catch (Throwable $e) {
  error_log('Private package page failed: ' . $e->getMessage());
  $packages = [];
  $myInquiries = [];
  $errorMsg = $errorMsg ?: 'Private packages are unavailable. Please try again later.';
}

function private_h(mixed $value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function private_status_label(string $status): string {
  return ucwords(str_replace('_', ' ', strtolower($status)));
}

include __DIR__ . '/../includes/header.php';
?>

<main class="private-page">
  <section class="private-hero">
    <div class="private-hero__inner">
      <p>Planning an event?</p>
      <h1>Pickle <span>&amp;</span> <em>Launch</em></h1>
      <p>A 5,000 sq ft private space best for ultimate corporate or brand event experience where your team can dine, drink, and play in a dynamic and engaging environment. Our dedicated event spaces, pickleball courts, and food and drink options create the perfect setting for fostering connections, collaboration, and unforgettable moments.</p>
    </div>
  </section>

  <section class="private-gallery">
    <div class="private-gallery__grid">
      <article class="private-gallery__large">
        <img src="../assets/img/court/private-1.png" alt="Private pickleball event group" />
      </article>
      <article><img src="../assets/img/court/private-2.png" alt="Group event at Pickle and Club" /></article>
      <article><img src="../assets/img/court/private-3.png" alt="Players at private pickleball event" /></article>
    </div>
  </section>

  <section class="private-service" id="packages">
    <div class="private-service__inner">
      <h2>Private coaching packages</h2>
      <p>Choose an available private package and send the team your preferred goals, timing, and group details.</p>

      <?php if ($successMsg !== ''): ?>
        <p><?= private_h($successMsg) ?></p>
      <?php endif; ?>
      <?php if ($errorMsg !== ''): ?>
        <p><?= private_h($errorMsg) ?></p>
      <?php endif; ?>

      <?php if (!$packages): ?>
        <p>No private packages are available yet.</p>
      <?php endif; ?>

      <?php foreach ($packages as $package): ?>
        <article class="private-package-card">
          <h3><?= private_h($package['title']) ?></h3>
          <p><?= private_h($package['description']) ?></p>
          <p>
            <strong>PHP <?= number_format((float) $package['price'], 2) ?></strong>
            <span><?= private_h($package['duration']) ?></span>
            <span>Coach: <?= private_h($package['coach_name'] ?? 'Assigned coach') ?></span>
          </p>

          <?php if ($isPlayer): ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= private_h(pickled_csrf_token()) ?>">
              <input type="hidden" name="private_package_id" value="<?= (int) $package['id'] ?>">
              <label>
                Inquiry message
                <textarea name="message" rows="4" required placeholder="Tell us your preferred date, group size, and goals."></textarea>
              </label>
              <button class="private-service__button" type="submit">Send inquiry</button>
            </form>
          <?php else: ?>
            <a href="../auth/login.php?redirect=resident/private.php" class="private-service__button">Log in to inquire</a>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($isPlayer): ?>
    <section class="private-service" id="my-inquiries">
      <div class="private-service__inner">
        <h2>Your private inquiries</h2>
        <?php if (!$myInquiries): ?>
          <p>No private inquiries yet.</p>
        <?php endif; ?>

        <?php foreach ($myInquiries as $inquiry): ?>
          <article class="private-package-card">
            <h3><?= private_h($inquiry['package_title']) ?></h3>
            <p><strong>Status:</strong> <?= private_h(private_status_label((string) $inquiry['status'])) ?></p>
            <p><?= private_h($inquiry['message']) ?></p>
            <?php if (!empty($inquiry['admin_response'])): ?>
              <p><strong>Admin response:</strong> <?= private_h($inquiry['admin_response']) ?></p>
            <?php endif; ?>
            <small>Submitted <?= private_h(date('M j, Y g:i A', strtotime((string) $inquiry['created_at']))) ?></small>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
