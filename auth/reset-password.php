<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../app/services/AuthService.php';
pickled_start_secure_session();
pickled_init_csrf();

$pageTitle = 'Reset Password - Pickled';
$activePage = 'login.php';
$frontendPath = __DIR__ . '/..';
require_once $frontendPath . '/includes/paths.php';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css?v=20260615a')) . '"/>' . "\n" .
    '<script defer src="' . htmlspecialchars(pickled_asset_url('js/login.js?v=20260615a')) . '"></script>';
$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!(new AuthService())->resetPassword($token, $password)) {
        $error = 'This reset link is invalid or expired.';
    } else {
        $success = 'Password updated. You can now log in.';
    }
}

include $frontendPath . '/includes/header.php';
?>
<main class="login-page">
  <section class="login-panel">
    <h1>NEW PASSWORD</h1>
    <?php if ($success): ?>
      <div class="login-success"><?= htmlspecialchars($success) ?></div>
      <div class="login-links"><a href="login.php">Go to login</a></div>
    <?php else: ?>
      <form class="login-form" method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
        <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="login-form-field">
          <label for="resetPassword">Password</label>
          <span class="login-field login-field--password">
            <input id="resetPassword" type="password" name="password" autocomplete="new-password" required />
            <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="resetPassword" data-password-toggle>
              <svg class="login-password-toggle__icon login-password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <svg class="login-password-toggle__icon login-password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
                <path d="m3 3 18 18"></path>
              </svg>
            </button>
          </span>
        </div>
        <div class="login-form-field">
          <label for="resetConfirmPassword">Confirm Password</label>
          <span class="login-field login-field--password">
            <input id="resetConfirmPassword" type="password" name="confirm_password" autocomplete="new-password" required />
            <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="resetConfirmPassword" data-password-toggle>
              <svg class="login-password-toggle__icon login-password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <svg class="login-password-toggle__icon login-password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
                <path d="m3 3 18 18"></path>
              </svg>
            </button>
          </span>
        </div>
        <button type="submit">Update password</button>
      </form>
    <?php endif; ?>
  </section>
</main>
<?php include $frontendPath . '/includes/footer.php'; ?>
