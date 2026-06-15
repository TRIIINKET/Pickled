<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../includes/EmailVerification.php';

pickled_start_secure_session();
pickled_init_csrf();

$frontendPath = __DIR__ . '/..';
require_once $frontendPath . '/includes/paths.php';

$pageTitle = 'Verify Email - Pickled';
$activePage = 'login.php';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css?v=20260615a')) . '"/>';
$auth = new AuthService();
$pending = EmailVerification::pending();
$message = '';
$messageType = 'error';

if (!$pending) {
    $message = 'No email verification request was found. Please sign up or resend your OTP.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid request. Please refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'verify');
        $pending = EmailVerification::pending();

        if (!$pending) {
            $message = 'No email verification request was found. Please sign up or resend your OTP.';
        } elseif ($action === 'resend') {
            $user = $auth->findByEmail((string) ($pending['email'] ?? ''));
            if ($user && EmailVerification::issue($user)) {
                $message = 'A new OTP has been sent to your email.';
                $messageType = 'success';
                $pending = EmailVerification::pending();
            } else {
                $message = 'Unable to resend OTP right now. Please try again later.';
            }
        } else {
            $result = EmailVerification::verify((string) ($_POST['otp'] ?? ''));
            if ($result['ok']) {
                $userId = (int) ($pending['user_id'] ?? 0);
                if ($userId > 0 && $auth->markVerified($userId)) {
                    EmailVerification::clear();
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'message' => 'Email verified. You may now log in.',
                    ];
                    header('Location: login.php?verified=1');
                    exit;
                }
                $message = 'Unable to verify this account right now.';
            } else {
                $message = $result['message'];
            }
        }
    }
}

include $frontendPath . '/includes/header.php';
?>

<main class="login-page">
  <section class="login-panel" aria-labelledby="verifyTitle">
    <h1 id="verifyTitle">VERIFY EMAIL</h1>
    <p class="login-subtitle">Enter the 6-digit OTP sent to <?= htmlspecialchars((string) ($pending['email'] ?? 'your email')) ?>.</p>

    <?php if ($message): ?>
      <div class="<?= $messageType === 'success' ? 'login-success' : 'login-error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form class="login-form" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <input type="hidden" name="action" value="verify" />
      <label>
        <span>OTP Code</span>
        <input type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required />
      </label>
      <button type="submit">Verify Email</button>
    </form>

    <form class="login-form" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <input type="hidden" name="action" value="resend" />
      <button type="submit">Resend OTP</button>
    </form>

    <div class="login-links">
      <p><a href="login.php">Back to login</a></p>
    </div>
  </section>
</main>

<?php include $frontendPath . '/includes/footer.php'; ?>
