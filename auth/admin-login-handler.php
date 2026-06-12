<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../app/services/AuthService.php';

pickled_start_secure_session();

$auth = new AuthService();
$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!pickled_validate_csrf_token($csrfToken)) {
        $errorMsg = 'Invalid form submission. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $errorMsg = 'Email and password are required.';
    } else {
        $user = $auth->attempt($email, $password);
        
        if ($user) {
            // Check if user is admin
            if ($user['role'] !== 'admin') {
                $errorMsg = 'Unauthorized. This account is not an admin.';
            } else {
                // Login successful
                session_regenerate_id(true);
                $_SESSION['user'] = pickled_session_user($user);
                $_SESSION['user_id'] = $_SESSION['user']['id'];
                header('Location: ' . pickled_frontend_url(pickled_default_redirect_for_role('admin')));
                exit;
            }
        } else {
            $errorMsg = 'Invalid email or password.';
        }
    }
}

// Ensure CSRF token is set for form
if (empty($_SESSION['csrf_token'])) {
    pickled_init_csrf();
}
?>
