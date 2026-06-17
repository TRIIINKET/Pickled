<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/booking-system.php';
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/EmailService.php';
require_once __DIR__ . '/../includes/EmailVerification.php';
pickled_start_secure_session();
pickled_init_csrf();

$pageTitle = 'Login - Pickled';
$activePage = 'login.php';
$frontendPath = __DIR__ . '/..';
require_once $frontendPath . '/includes/paths.php';
$extraHead = '<link rel="stylesheet" href="' . htmlspecialchars(pickled_asset_url('css/login.css?v=20260615a')) . '"/>' . "\n" .
    '<script defer src="' . htmlspecialchars(pickled_asset_url('js/login.js?v=20260615a')) . '"></script>';

$auth = new AuthService();

if (!empty($_SESSION['user'])) {
    header('Location: ' . pickled_frontend_url(pickled_login_redirect_for_role($_SESSION['user']['role'] ?? null)));
    exit;
}

$mode = $_GET['mode'] ?? 'login';
$mode = $mode === 'signup' ? 'signup' : 'login';
$allowedRoles = ['admin', 'coach', 'player'];
$selectedRole = $_POST['role'] ?? ($_GET['role'] ?? 'player');
$selectedRole = in_array($selectedRole, $allowedRoles, true) ? $selectedRole : 'player';
$redirect = pickled_safe_redirect($_POST['redirect'] ?? ($_GET['redirect'] ?? 'resident/index.php'));
$bookingNotice = ($_GET['notice'] ?? '') === 'booking' ? 'Please sign up or sign in before booking.' : '';
$loginError = '';
$signupError = '';
$signupSuccess = '';
$unverifiedEmail = '';
$roleLabels = [
    'admin' => 'Admin',
    'coach' => 'Coaches',
    'player' => 'Players',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $rawEmail = (string) ($_POST['email'] ?? '');
    try {
        $email = validateEmail($rawEmail);
    } catch (RuntimeException) {
        $email = null;
    }
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!pickled_validate_csrf_token($csrfToken)) {
        $loginError = 'Invalid request. Please refresh and try again.';
    } else {
        if ($action === 'resend_otp') {
            $mode = 'login';
            $user = $email ? $auth->findByEmail($email) : null;
            if (!$user) {
                $loginError = 'Enter a valid registered email address.';
            } elseif ($auth->isVerified($user)) {
                $signupSuccess = 'This email is already verified. You may log in.';
            } elseif (EmailVerification::issue($user)) {
                header('Location: verify-otp.php');
                exit;
            } else {
                $loginError = 'Unable to send OTP right now. Please try again later.';
                $unverifiedEmail = (string) ($user['email'] ?? $email);
            }
        } elseif ($action === 'signup') {
            $mode = 'signup';
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            try {
                $name = validateName($_POST['name'] ?? '');
                $email = validateEmail($rawEmail);
                $password = validatePassword($password);
                if ($password !== $confirmPassword) {
                    throw new RuntimeException('Passwords do not match.');
                }

                    $user = $auth->register($name, $email, $password);
                    if (EmailVerification::issue($user)) {
                        header('Location: verify-otp.php');
                        exit;
                    }
                    $signupError = 'Account created, but the OTP email could not be sent. Please use resend OTP on the login page.';
            } catch (RuntimeException $e) {
                $signupError = $e->getMessage();
            }
        } else {
            $mode = 'login';
            $user = $email && $password !== '' ? $auth->attempt($email, $password) : null;
            if ($user) {
                if (!$auth->isVerified($user)) {
                    $loginError = 'Please verify your email address before logging in.';
                    $unverifiedEmail = (string) ($user['email'] ?? $email);
                } else {
                session_regenerate_id(true);
                $_SESSION['user'] = pickled_session_user($user);
                $_SESSION['user_id'] = $_SESSION['user']['id'];
                $firstName = trim((string) (preg_split('/\s+/', (string) ($_SESSION['user']['name'] ?? ''))[0] ?? ''));
                $_SESSION['welcome_banner'] = 'Welcome back, ' . ($firstName !== '' ? $firstName : 'Player') . '!';
                pickled_restore_cart_for_user();
                $emailService = new EmailService();
                if (!$emailService->sendLoginNotification($_SESSION['user'])) {
                    $_SESSION['flash'] = [
                        'type' => 'warning',
                        'message' => 'Logged in, but the notification email could not be sent.',
                    ];
                    error_log('Login notification failed for ' . ($_SESSION['user']['email'] ?? 'unknown email'));
                }

                header('Location: ' . pickled_frontend_url(pickled_login_redirect_for_role($_SESSION['user']['role'], $redirect)));
                exit;
                }
            }

            if (!$loginError) {
                $loginError = 'Invalid email or password.';
            }
        }
    }
}
include $frontendPath . '/includes/header.php';
?>

<main class="login-page">
  <section class="login-panel" aria-labelledby="loginTitle">
    <h1 id="loginTitle"><?= $mode === 'signup' ? 'SIGN UP' : 'LOGIN' ?></h1>
    <?php if ($mode !== 'signup'): ?>
      <p class="login-subtitle">Welcome back! Please sign in to continue.</p>
    <?php endif; ?>

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
        <input type="text" name="name" placeholder="Name" autocomplete="name" minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z .'\-]*" title="Please enter a valid name." required/>
      </label>

      <label>
        <span>Email</span>
        <input type="email" name="email" placeholder="Email" autocomplete="email" maxlength="150" required/>
      </label>

      <div class="login-form-field">
        <label for="signupPassword">Password</label>
        <span class="login-field login-field--password">
          <input id="signupPassword" type="password" name="password" placeholder="Password" autocomplete="new-password" minlength="8" maxlength="72" pattern="(?=.*[A-Za-z])(?=.*\d).{8,72}" title="Password must be at least 8 characters and include letters and numbers." required/>
          <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="signupPassword" data-password-toggle>
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
        <label for="signupConfirmPassword">Confirm Password</label>
        <span class="login-field login-field--password">
          <input id="signupConfirmPassword" type="password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" minlength="8" maxlength="72" required/>
          <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="signupConfirmPassword" data-password-toggle>
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

      <button type="submit">Sign up</button>
    </form>
    <?php else: ?>
    <form class="login-form" action="login.php" method="post" data-loader="auth">
      <input type="hidden" name="action" value="login" />
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
      <div class="login-role-tabs" role="radiogroup" aria-label="Account type">
        <?php foreach ($roleLabels as $roleValue => $roleLabel): ?>
          <label class="login-role-tab">
            <input type="radio" name="role" value="<?= htmlspecialchars($roleValue) ?>" <?= $selectedRole === $roleValue ? 'checked' : '' ?> />
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <?php if ($roleValue === 'admin'): ?>
                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"></path>
                <path d="M4 21a8 8 0 0 1 11.1-7.4"></path>
                <path d="m18 14 1.1 2.3 2.5.3-1.8 1.8.4 2.5-2.2-1.2-2.2 1.2.4-2.5-1.8-1.8 2.5-.3Z"></path>
              <?php elseif ($roleValue === 'coach'): ?>
                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"></path>
                <path d="M4 21a8 8 0 0 1 16 0"></path>
                <path d="M8 17h8"></path>
              <?php else: ?>
                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"></path>
                <path d="M4 21a8 8 0 0 1 16 0"></path>
              <?php endif; ?>
            </svg>
            <span><?= htmlspecialchars($roleLabel) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
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
        <span class="login-field">
          <input type="email" name="email" placeholder="Enter your email" autocomplete="email" maxlength="150" required value="<?= htmlspecialchars($unverifiedEmail ?: ($_POST['email'] ?? '')) ?>"/>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16v12H4z"></path>
            <path d="m4 7 8 6 8-6"></path>
          </svg>
        </span>
      </label>

      <div class="login-form-field">
        <label for="loginPassword">Password</label>
        <span class="login-field login-field--password">
          <input id="loginPassword" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" maxlength="72" required/>
          <button class="login-password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="loginPassword" data-password-toggle>
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

      <div class="login-options">
        <label class="login-remember">
          <input type="checkbox" name="remember" checked />
          <span>Remember me</span>
        </label>
        <a href="forgot-password.php">Forgot your password?</a>
      </div>

      <button type="submit">Sign in</button>
    </form>
    <?php if ($unverifiedEmail): ?>
      <form class="login-form" action="login.php" method="post">
        <input type="hidden" name="action" value="resend_otp" />
        <input type="hidden" name="email" value="<?= htmlspecialchars($unverifiedEmail) ?>" />
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
        <button type="submit">Resend OTP</button>
      </form>
    <?php endif; ?>
    <?php endif; ?>

    <div class="login-links">
      <?php if ($mode === 'signup'): ?>
        <p>Already have an account? <a href="login.php">Log in</a></p>
      <?php elseif ($selectedRole !== 'admin'): ?>
        <p>Don't have an account? <a href="login.php?mode=signup">Sign up</a></p>
      <?php endif; ?>
    </div>

    <div class="login-help">
      <h2>Need help accessing your subscriptions?</h2>
      <a href="<?= htmlspecialchars(pickled_frontend_url('resident/contact.php')) ?>">Click here</a>
    </div>
  </section>
</main>

<?php include $frontendPath . '/includes/footer.php'; ?>
