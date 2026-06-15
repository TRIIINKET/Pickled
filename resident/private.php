<?php
declare(strict_types=1);

$pageTitle  = 'Private Events - Pickled';
$activePage = 'private.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../assets/css/private.css?v=20260615a"/>';

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
      <div class="private-service__heading">
        <p>Premium Packages</p>
        <h2>Private packages</h2>
        <span>Choose an available package and send the team your preferred goals, timing, and group details.</span>
      </div>

      <?php if ($errorMsg !== ''): ?>
        <p class="private-alert private-alert--error"><?= private_h($errorMsg) ?></p>
      <?php endif; ?>

      <?php if (!$packages): ?>
        <p>No private packages are available yet.</p>
      <?php endif; ?>

      <div class="private-package-grid">
      <?php foreach ($packages as $package): ?>
        <article class="private-package-card">
          <div class="private-package-card__head">
            <span><?= private_h($package['category'] ?? 'Private Package') ?></span>
            <strong>PHP <?= number_format((float) $package['price'], 2) ?></strong>
          </div>
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
    </div>
  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
