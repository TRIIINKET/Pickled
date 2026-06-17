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

$verified = $_SESSION['password_reset_verified'] ?? null;
$error = '';
$auth = new AuthService();
$resetSessionValid = $verified && is_array($verified) && (time() - (int) ($verified['verified_at'] ?? 0)) <= 600;

if (!$resetSessionValid) {
    $error = 'Please verify your reset code first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $password = validatePassword($password);
            $ok = $auth->resetPasswordWithOtp(
                (int) ($verified['user_id'] ?? 0),
                (int) ($verified['reset_id'] ?? 0),
                $password
            );
            if ($ok) {
                unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_otp']);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Password updated. You can now log in.',
                ];
                header('Location: login.php');
                exit;
            }
            $error = 'This reset request is invalid or expired.';
        } catch (Throwable $e) {
            error_log('Password reset OTP update failed: ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update password right now.';
        }
    }
}

include $frontendPath . '/includes/header.php';
?>
<main class="login-page">
  <section class="login-panel">
    <h1>NEW PASSWORD</h1>
    <?php if (!$resetSessionValid): ?>
      <div class="login-error"><?= htmlspecialchars($error) ?></div>
      <div class="login-links"><a href="forgot-password.php">Request a new reset code</a></div>
    <?php else: ?>
      <form class="login-form" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
        <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="login-form-field">
          <label for="resetPassword">Password</label>
          <span class="login-field login-field--password">
            <input id="resetPassword" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" pattern="(?=.*[A-Za-z])(?=.*\d).{8,72}" title="Password must be at least 8 characters and include letters and numbers." required />
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
            <input id="resetConfirmPassword" type="password" name="confirm_password" autocomplete="new-password" minlength="8" maxlength="72" required />
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
