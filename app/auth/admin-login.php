<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers/security.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

pickled_start_secure_session();

$userRepo = new UserRepository();
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
        // Find user by email
        $user = $userRepo->findByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if user is admin
            if ($user['role'] !== 'admin') {
                $errorMsg = 'Unauthorized. This account is not an admin.';
            } else {
                // Login successful
                session_regenerate_id(true);
                $_SESSION['user'] = $user;
                $_SESSION['user_id'] = $user['id'];
                header('Location: admin-dashboard.php');
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
