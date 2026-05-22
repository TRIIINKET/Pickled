<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking_system.php';
require_once __DIR__ . '/../services/AuthService.php';
pickled_start_secure_session();
pickled_init_csrf();

$pageTitle = 'Login - Pickled';
$activePage = 'login.php';
$frontendPath = __DIR__ . '/../../frontend';
require_once $frontendPath . '/includes/paths.php';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css')) . '"/>';

$auth = new AuthService();

if (!empty($_SESSION['user'])) {
    header('Location: ' . pickled_frontend_url('index.php'));
    exit;
}

$mode = $_GET['mode'] ?? 'login';
$mode = $mode === 'signup' ? 'signup' : 'login';
$redirect = pickled_safe_redirect($_POST['redirect'] ?? ($_GET['redirect'] ?? 'index.php'));
$bookingNotice = ($_GET['notice'] ?? '') === 'booking' ? 'Please sign up or sign in before booking.' : '';
$loginError = '';
$signupError = '';
$signupSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!pickled_validate_csrf_token($csrfToken)) {
        $loginError = 'Invalid request. Please refresh and try again.';
    } else {
        if ($action === 'signup') {
            $mode = 'signup';
            $name = trim($_POST['name'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if ($name === '') {
                $signupError = 'Name is required.';
            } elseif (!$email) {
                $signupError = 'Enter a valid email.';
            } elseif (strlen($password) < 6) {
                $signupError = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirmPassword) {
                $signupError = 'Passwords do not match.';
            } else {
                try {
                    $auth->register($name, $email, $password);
                    $mode = 'login';
                    $signupSuccess = 'Account created. Please log in.';
                } catch (RuntimeException $e) {
                    $signupError = $e->getMessage();
                }
            }
        } else {
            $mode = 'login';
            $user = $email && $password !== '' ? $auth->attempt($email, $password) : null;
            if ($user) {
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'email' => $email,
                    'name'  => $user['name'],
                    'role' => $user['role'],
                ];
                session_regenerate_id(true);
                pickled_restore_cart_for_user();

                header('Location: ' . pickled_frontend_url($redirect));
                exit;
            }

            $loginError = 'Invalid email or password.';
        }
    }
}
include $frontendPath . '/includes/_header.php';
?>

<main class="login-page">
  <section class="login-panel" aria-labelledby="loginTitle">
    <h1 id="loginTitle"><?= $mode === 'signup' ? 'SIGN UP' : 'LOGIN' ?></h1>

    <?php if ($mode === 'signup'): ?>
    <form class="login-form" action="login.php?mode=signup" method="post">
      <input type="hidden" name="action" value="signup" />
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <?php if ($signupError): ?>
        <div class="login-error"><?= htmlspecialchars($signupError) ?></div>
      <?php endif; ?>

      <label>
        <span>Name</span>
        <input type="text" name="name" placeholder="Name" autocomplete="name" required/>
      </label>

      <label>
        <span>Email</span>
        <input type="email" name="email" placeholder="Email" autocomplete="email" required/>
      </label>

      <label>
        <span>Password</span>
        <input type="password" name="password" placeholder="Password" autocomplete="new-password" required/>
      </label>

      <label>
        <span>Confirm Password</span>
        <input type="password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" required/>
      </label>

      <button type="submit">Sign up</button>
    </form>
    <?php else: ?>
    <form class="login-form" action="login.php" method="post">
      <input type="hidden" name="action" value="login" />
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <?php if ($signupSuccess): ?>
        <div class="login-success"><?= htmlspecialchars($signupSuccess) ?></div>
      <?php endif; ?>
      <?php if ($bookingNotice): ?>
        <div class="login-error"><?= htmlspecialchars($bookingNotice) ?></div>
      <?php endif; ?>
      <?php if ($loginError): ?>
        <div class="login-error"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>

      <label>
        <span>Email</span>
        <input type="email" name="email" placeholder="Email" autocomplete="email" required/>
      </label>

      <label>
        <span>Password</span>
        <input type="password" name="password" placeholder="Password" autocomplete="current-password" required/>
      </label>

      <button type="submit">Sign in</button>
    </form>
    <?php endif; ?>

    <div class="login-links">
      <a href="forgot-password.php">Forgot your password?</a>
      <?php if ($mode === 'signup'): ?>
        <p>Already have an account? <a href="login.php">Log in</a></p>
      <?php else: ?>
        <p>Don't have an account? <a href="login.php?mode=signup">Sign up</a></p>
      <?php endif; ?>
    </div>

    <div class="login-help">
      <h2>Need help accessing your subscriptions?</h2>
      <a href="pages/contact.php">Click here</a>
    </div>
  </section>
</main>

<?php include $frontendPath . '/includes/_footer.php'; ?>
