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

$currentUser = $_SESSION['user'] ?? null;
$isPlayer = is_array($currentUser) && (($currentUser['role'] ?? '') === 'player');

$packageService = new PrivatePackageService();
$packages = [];
$errorMsg = '';

try {
  $packages = $packageService->availablePackages();
} catch (Throwable $e) {
  error_log('Private package page failed: ' . $e->getMessage());
  $errorMsg = 'Private packages are unavailable. Please try again later.';
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
      <p>A 5,000 sq ft private space best for corporate or brand events where your team can dine, drink, and play in a dynamic environment.</p>
    </div>
  </section>

  <section class="private-gallery">
    <div class="private-gallery__grid">
      <article class="private-gallery__large">
        <img src="../assets/img/court/private-1.png" alt="Private pickleball event group">
      </article>
      <article>
        <img src="../assets/img/court/private-2.png" alt="Group event at Pickled">
      </article>
      <article>
        <img src="../assets/img/court/private-3.png" alt="Players at private pickleball event">
      </article>
    </div>
  </section>

  <section class="private-service" id="packages">
    <div class="private-service__inner">
      <div class="private-service__heading">
        <p>Premium Packages</p>
        <h2>Private packages</h2>
        <span>Choose an available package and send your preferred date, time, group size, and event goals.</span>
      </div>

      <?php if ($errorMsg !== ''): ?>
        <p class="private-alert private-alert--error"><?= private_h($errorMsg) ?></p>
      <?php endif; ?>

      <?php if (empty($packages)): ?>
        <p class="private-empty">No private packages are available yet.</p>
      <?php else: ?>
        <div class="private-package-grid">
          <?php foreach ($packages as $package): ?>
            <?php
              $packageId = (int) ($package['id'] ?? $package['package_id'] ?? 0);
              $packageTitle = $package['title'] ?? $package['name'] ?? 'Private Package';
              $packageCategory = $package['category'] ?? 'Private Package';
              $packageDescription = $package['description'] ?? 'A private event package tailored for your group.';
              $packagePrice = (float) ($package['price'] ?? 0);
              $packageDuration = $package['duration'] ?? $package['duration_label'] ?? 'Custom duration';
              $coachName = $package['coach_name'] ?? 'Assigned coach';
            ?>

            <article class="private-package-card">
              <div class="private-package-card__head">
                <span><?= private_h($packageCategory) ?></span>
                <strong>PHP <?= number_format($packagePrice, 2) ?></strong>
              </div>

              <h3><?= private_h($packageTitle) ?></h3>
              <p><?= private_h($packageDescription) ?></p>

              <p class="private-package-card__meta">
                <span><?= private_h($packageDuration) ?></span>
                <span>Coach: <?= private_h($coachName) ?></span>
              </p>

              <?php if ($isPlayer): ?>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= private_h(pickled_csrf_token()) ?>">
                  <input type="hidden" name="private_package_id" value="<?= $packageId ?>">

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
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>