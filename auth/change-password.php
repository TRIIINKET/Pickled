<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/AuthService.php';

pickled_start_secure_session();
pickled_init_csrf();

if (empty($_SESSION['user'])) {
    header('Location: ' . pickled_frontend_url('auth/login.php'));
    exit;
}

$pageTitle = 'Security Settings - Pickled';
$activePage = 'profile.php';
$frontendPath = __DIR__ . '/..';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css?v=20260615b')) . '"/>' . "\n" .
    '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/security-settings.css?v=20260616a')) . '"/>' . "\n" .
    '<script defer src="' . htmlspecialchars(pickled_asset_url('js/login.js?v=20260615a')) . '"></script>';
$error = '';
$success = '';
$role = pickled_normalize_role($_SESSION['user']['role'] ?? null);
$backPath = match ($role) {
    'admin' => 'admin/admin-profile.php',
    'coach' => 'coach/profile.php',
    default => 'resident/profile.php',
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($currentPassword === '') {
        $error = 'Current password is required.';
    } elseif (strlen($password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!(new AuthService())->changePassword((int) ($_SESSION['user']['id'] ?? 0), $currentPassword, $password)) {
        $error = 'Current password is incorrect.';
    } else {
        $success = 'Password updated successfully.';
    }
}

include $frontendPath . '/includes/header.php';
?>
<main class="security-settings-page">
  <section class="security-settings-shell" aria-labelledby="securityTitle">
    <div class="security-settings-header">
      <div>
        <p>Settings</p>
        <h1 id="securityTitle">Security</h1>
      </div>
      <a href="<?= htmlspecialchars(pickled_frontend_url($backPath)) ?>">Back to Profile</a>
    </div>

    <article class="security-settings-card">
      <div class="security-settings-card__header">
        <div>
          <h2>Change Password</h2>
          <p>Update your password after confirming your current password.</p>
        </div>
      </div>

      <?php if ($error): ?><div class="security-settings-alert security-settings-alert--error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="security-settings-alert security-settings-alert--success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

      <form class="security-settings-form" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
        <div class="security-settings-field">
          <label for="currentPassword">Current Password</label>
          <span class="login-field login-field--password">
            <input id="currentPassword" type="password" name="current_password" autocomplete="current-password" required />
            <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="currentPassword" data-password-toggle>
              <svg class="login-password-toggle__icon login-password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              <svg class="login-password-toggle__icon login-password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path d="m3 3 18 18"></path></svg>
            </button>
          </span>
        </div>
        <div class="security-settings-field">
          <label for="newPassword">New Password</label>
          <span class="login-field login-field--password">
            <input id="newPassword" type="password" name="password" autocomplete="new-password" minlength="6" required />
            <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="newPassword" data-password-toggle>
              <svg class="login-password-toggle__icon login-password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              <svg class="login-password-toggle__icon login-password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path d="m3 3 18 18"></path></svg>
            </button>
          </span>
        </div>
        <div class="security-settings-field">
          <label for="confirmPassword">Confirm New Password</label>
          <span class="login-field login-field--password">
            <input id="confirmPassword" type="password" name="confirm_password" autocomplete="new-password" minlength="6" required />
            <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="confirmPassword" data-password-toggle>
              <svg class="login-password-toggle__icon login-password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              <svg class="login-password-toggle__icon login-password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path d="m3 3 18 18"></path></svg>
            </button>
          </span>
        </div>
        <div class="security-settings-actions">
          <button type="submit">Update Password</button>
          <a href="<?= htmlspecialchars(pickled_frontend_url('auth/forgot-password.php')) ?>">Forgot Password?</a>
        </div>
      </form>
    </article>
  </section>
</main>
<?php include $frontendPath . '/includes/footer.php'; ?>
