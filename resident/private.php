<?php
declare(strict_types=1);

$pageTitle  = 'Private Events - Pickled';
$activePage = 'private.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../assets/css/private.css?v=20260615d"/>';

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/PrivatePackageService.php';

pickled_start_secure_session();
pickled_init_csrf();

$packageService = new PrivatePackageService();
$errorMsg = '';

try {
  $packages = $packageService->availablePackages();
} catch (Throwable $e) {
  error_log('Private package page failed: ' . $e->getMessage());
  $packages = [];
  $errorMsg = $errorMsg ?: 'Private packages are unavailable. Please try again later.';
}

function private_h(mixed $value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

      <?php if ($errorMsg !== ''): ?>
        <p class="private-alert private-alert--error"><?= private_h($errorMsg) ?></p>
      <?php endif; ?>

      <?php if (!$packages): ?>
        <p>No private packages are available yet.</p>
      <?php endif; ?>

      <?php foreach ($packages as $package): ?>
        <article class="private-package-card">
          <h3><?= private_h($package['title']) ?></h3>
          <p><?= private_h($package['description']) ?></p>
          <p class="private-package-card__meta">
            <strong>PHP <?= number_format((float) $package['price'], 2) ?></strong>
            <span><?= private_h($package['duration']) ?></span>
            <span>Coach: <?= private_h($package['coach_name'] ?? 'Assigned coach') ?></span>
          </p>

          <a href="<?= private_h(pickled_frontend_url('resident/contact.php')) ?>" class="private-service__button">Send inquiry</a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
