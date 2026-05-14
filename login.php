<?php
session_start();
$pageTitle = 'Login - Pickled';
$activePage = 'login.php';
$extraHead = '<link rel="stylesheet" href="assets/css/login.css"/>';

class User {
    private string $email;
    private string $password;
    public string $name;

    public function __construct(string $email = '', string $password = '', string $name = '') {
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
    }

    public function __set(string $property, $value) {
        if ($property === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email must be valid.');
            }
            $this->email = $value;
            return;
        }

        if ($property === 'password') {
            if (strlen((string)$value) < 6) {
                throw new Exception('Password must be at least 6 characters.');
            }
            $this->password = $value;
            return;
        }

        if ($property === 'name') {
            $this->name = trim((string)$value);
            return;
        }

        throw new Exception("Cannot set property '$property'.");
    }

    public function __get(string $property) {
        if (in_array($property, ['email', 'password', 'name'], true)) {
            return $this->$property;
        }

        throw new Exception("Cannot get property '$property'.");
    }
}

class Member extends User {
    public string $role = 'guest';

    public function __construct(string $email = '', string $password = '', string $name = '', string $role = 'guest') {
        parent::__construct($email, $password, $name);
        $this->role = $role;
    }

    public function getRoleLabel(): string {
        return ucfirst($this->role);
    }
}

$demoMember = new Member('player@example.com', 'pickle123', 'Player', 'player');
$demoMember->email = 'player@example.com'; // uses __set()
$demoMember->password = 'pickle123';      // uses __set()
$demoMember->role = 'player';             // public property

$defaultUsers = [
    'player@example.com' => ['password' => password_hash('pickle123', PASSWORD_DEFAULT), 'name' => 'Player'],
    'coach@example.com' => ['password' => password_hash('coach123', PASSWORD_DEFAULT), 'name' => 'Coach'],
];

$_SESSION['registered_users'] = $_SESSION['registered_users'] ?? $defaultUsers;

if (!empty($_SESSION['user']) && !isset($_COOKIE['login_cookie'])) {
    unset($_SESSION['user'], $_SESSION['cart'], $_SESSION['last_booking']);
}

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$mode = $_GET['mode'] ?? 'login';
$mode = $mode === 'signup' ? 'signup' : 'login';
$redirect = $_POST['redirect'] ?? ($_GET['redirect'] ?? 'index.php');
if (!preg_match('/^[A-Za-z0-9_-]+\.php(?:#[A-Za-z0-9_-]+)?$/', $redirect)) {
    $redirect = 'index.php';
}
$bookingNotice = ($_GET['notice'] ?? '') === 'booking' ? 'Please sign up or sign in before booking.' : '';
$loginError = '';
$signupError = '';
$signupSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if ($action === 'signup') {
        $mode = 'signup';
        $name = trim($_POST['name'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if ($name === '') {
            $signupError = 'Name is required.';
        } elseif (!$email) {
            $signupError = 'Enter a valid email.';
        } elseif (isset($_SESSION['registered_users'][$email])) {
            $signupError = 'Email is already registered. Please log in.';
        } elseif (strlen($password) < 6) {
            $signupError = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $signupError = 'Passwords do not match.';
        } else {
            $_SESSION['registered_users'][$email] = [
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'name' => $name,
            ];
            $mode = 'login';
            $signupSuccess = 'Account created. Please log in.';
        }
    } else {
        $mode = 'login';
        $users = $_SESSION['registered_users'];

        if ($email && $password !== '' && isset($users[$email]) && password_verify($password, $users[$email]['password'])) {
            $_SESSION['user'] = [
                'email' => $email,
                'name'  => $users[$email]['name'],
            ];

            setcookie('login_cookie', '1', time() + 30, '/'); // expires in 1 minute for the whole site

            header('Location: ' . $redirect);
            exit;
        }

        $loginError = 'Invalid email or password.';
    }
}
include '_header.php';
?>

<main class="login-page">
  <section class="login-panel" aria-labelledby="loginTitle">
    <h1 id="loginTitle"><?= $mode === 'signup' ? 'SIGN UP' : 'LOGIN' ?></h1>

    <?php if ($mode === 'signup'): ?>
    <form class="login-form" action="login.php?mode=signup" method="post">
      <input type="hidden" name="action" value="signup" />
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />
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
      <a href="#">Forgot your password?</a>
      <?php if ($mode === 'signup'): ?>
        <p>Already have an account? <a href="login.php">Log in</a></p>
      <?php else: ?>
        <p>Don't have an account? <a href="login.php?mode=signup">Sign up</a></p>
      <?php endif; ?>
    </div>

    <div class="login-help">
      <h2>Need help accessing your subscriptions?</h2>
      <a href="contact.php">Click here</a>
    </div>
  </section>
</main>

<?php include '_footer.php'; ?>
