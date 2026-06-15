<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../app/services/AuthService.php';

pickled_start_secure_session();
pickled_init_csrf();

$pageTitle = 'Verify Reset Code - Pickled';
$activePage = 'login.php';
$frontendPath = __DIR__ . '/..';
require_once $frontendPath . '/includes/paths.php';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css?v=20260615a')) . '"/>' . "\n" .
    '<script defer src="' . htmlspecialchars(pickled_asset_url('js/login.js?v=20260615a')) . '"></script>';

$pending = $_SESSION['password_reset_otp'] ?? null;
$email = is_array($pending) ? (string) ($pending['email'] ?? '') : '';
$error = '';
$auth = new AuthService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif (!$pending || !is_array($pending)) {
        $error = 'Please request a new reset code.';
    } elseif ((int) ($pending['attempts'] ?? 0) >= 3) {
        $error = 'Maximum attempts reached. Please request a new reset code.';
    } else {
        $otp = trim((string) ($_POST['otp'] ?? ''));
        $userId = (int) ($pending['user_id'] ?? 0);
        $reset = $userId > 0 ? $auth->verifyPasswordResetOtp($userId, $email, $otp) : null;

        if (!$reset) {
            $_SESSION['password_reset_otp']['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            $remaining = max(0, 3 - (int) $_SESSION['password_reset_otp']['attempts']);
            $error = 'Invalid or expired reset code. Attempts remaining: ' . $remaining . '.';
        } else {
            $_SESSION['password_reset_verified'] = [
                'user_id' => $userId,
                'email' => $email,
                'reset_id' => (int) ($reset['reset_id'] ?? $reset['id'] ?? 0),
                'verified_at' => time(),
            ];
            unset($_SESSION['password_reset_otp']);
            header('Location: reset-password.php');
            exit;
        }
    }
}

include $frontendPath . '/includes/header.php';
?>
<main class="login-page">
  <section class="login-panel">
    <h1>VERIFY CODE</h1>
    <p class="login-subtitle">Enter the 6-digit reset code sent to your email.</p>
    <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form class="login-form" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <div class="login-form-field">
        <label for="resetOtp">Reset Code</label>
        <span class="login-field">
          <input id="resetOtp" type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required />
        </span>
      </div>
      <button type="submit">Verify code</button>
    </form>
    <div class="login-links"><a href="forgot-password.php">Request a new code</a></div>
  </section>
</main>
<?php include $frontendPath . '/includes/footer.php'; ?>
