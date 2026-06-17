<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/EmailService.php';

pickled_start_secure_session();
pickled_init_csrf();

$pageTitle = 'Forgot Password - Pickled';
$activePage = 'login.php';
$frontendPath = __DIR__ . '/..';
require_once $frontendPath . '/includes/paths.php';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css?v=20260615a')) . '"/>' . "\n" .
    '<script defer src="' . htmlspecialchars(pickled_asset_url('js/login.js?v=20260615a')) . '"></script>';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid request. Please refresh and try again.';
    } else {
        try {
            $email = validateEmail($_POST['email'] ?? '');
        } catch (RuntimeException) {
            $email = '';
        }
        $_SESSION['password_reset_otp'] = [
            'email' => strtolower((string) ($email ?: ($_POST['email'] ?? ''))),
            'user_id' => 0,
            'attempts' => 0,
        ];

        if ($email !== '') {
            try {
                $issued = (new AuthService())->issuePasswordResetOtp($email);
                if ($issued) {
                    $user = $issued['user'];
                    $_SESSION['password_reset_otp'] = [
                        'email' => (string) ($user['email'] ?? $email),
                        'user_id' => (int) ($user['id'] ?? $user['user_id'] ?? 0),
                        'attempts' => 0,
                    ];

                    (new EmailService())->sendPasswordResetOtp(
                        (string) ($user['email'] ?? $email),
                        (string) ($user['name'] ?? 'Member'),
                        (string) $issued['otp']
                    );
                }
            } catch (Throwable $e) {
                error_log('Forgot password OTP issue failed: ' . $e->getMessage());
            }
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'If the email is registered, a reset code has been sent.',
        ];
        header('Location: verify-reset-otp.php');
        exit;
    }
}

include $frontendPath . '/includes/header.php';
?>
<main class="login-page">
  <section class="login-panel">
    <h1>RESET PASSWORD</h1>
    <p class="login-subtitle">Enter your account email and we will send a 6-digit reset code.</p>
    <form class="login-form" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <?php if ($message): ?><div class="login-error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <div class="login-form-field">
        <label for="resetEmail">Email</label>
        <span class="login-field">
          <input id="resetEmail" type="email" name="email" placeholder="Enter your email" autocomplete="email" maxlength="150" required />
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16v12H4z"></path>
            <path d="m4 7 8 6 8-6"></path>
          </svg>
        </span>
      </div>
      <button type="submit">Send reset code</button>
    </form>
    <div class="login-links"><a href="login.php">Back to login</a></div>
  </section>
</main>
<?php include $frontendPath . '/includes/footer.php'; ?>
