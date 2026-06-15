<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../app/services/AuthService.php';
pickled_start_secure_session();
pickled_init_csrf();

$pageTitle = 'Forgot Password - Pickled';
$activePage = 'login.php';
$frontendPath = __DIR__ . '/..';
require_once $frontendPath . '/includes/paths.php';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css?v=20260615a')) . '"/>' . "\n" .
    '<script defer src="' . htmlspecialchars(pickled_asset_url('js/login.js?v=20260615a')) . '"></script>';
$message = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid request. Please refresh and try again.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if ($email) {
            $token = (new AuthService())->issuePasswordReset($email);
            if ($token) {
                $resetLink = pickled_frontend_url('auth/reset-password.php?token=' . rawurlencode($token));
            }
        }
        $message = 'If that email exists, a reset link has been created.';
    }
}

include $frontendPath . '/includes/header.php';
?>
<main class="login-page">
  <section class="login-panel">
    <h1>RESET PASSWORD</h1>
    <form class="login-form" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <?php if ($message): ?><div class="login-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($resetLink): ?><div class="login-success">Demo reset link: <a href="<?= htmlspecialchars($resetLink) ?>">Open reset page</a></div><?php endif; ?>
      <label><span>Email</span><input type="email" name="email" autocomplete="email" required /></label>
      <button type="submit">Create reset link</button>
    </form>
    <div class="login-links"><a href="login.php">Back to login</a></div>
  </section>
</main>
<?php include $frontendPath . '/includes/footer.php'; ?>
