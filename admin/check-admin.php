<?php
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

$userRepo = new UserRepository();

// Check if admin user exists
$admin = $userRepo->findByEmail('admin@example.com');

echo "<h2>Admin User Check</h2>";

if (!$admin) {
    echo "<p style='color: red;'><strong>❌ Admin user does NOT exist!</strong></p>";
    echo "<p>Creating admin user now...</p>";
    
    // Create admin user with password "password"
    $passwordHash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);
    $newUser = $userRepo->create('Admin', 'admin@example.com', $passwordHash);
    
    // Update role to admin
    $userRepo->updateRole($newUser['id'], 'admin');
    
    echo "<p style='color: green;'><strong>✅ Admin user created successfully!</strong></p>";
    echo "<p><strong>Email:</strong> admin@example.com</p>";
    echo "<p><strong>Password:</strong> password</p>";
    echo "<p><strong>Role:</strong> admin</p>";
} else {
    echo "<p style='color: green;'><strong>✅ Admin user exists!</strong></p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><td><strong>ID:</strong></td><td>" . $admin['id'] . "</td></tr>";
    echo "<tr><td><strong>Name:</strong></td><td>" . $admin['name'] . "</td></tr>";
    echo "<tr><td><strong>Email:</strong></td><td>" . $admin['email'] . "</td></tr>";
    echo "<tr><td><strong>Role:</strong></td><td>" . $admin['role'] . "</td></tr>";
    echo "<tr><td><strong>Password Hash:</strong></td><td>" . substr($admin['password_hash'], 0, 20) . "...</td></tr>";
    echo "</table>";
    
    // Test password verification
    echo "<h3>Password Test</h3>";
    if (password_verify('password', $admin['password_hash'])) {
        echo "<p style='color: green;'><strong>✅ Password 'password' is CORRECT</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Password 'password' is INCORRECT</strong></p>";
        echo "<p>Resetting password to 'password'...</p>";
        
        $passwordHash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);
        $userRepo->updatePassword($admin['id'], $passwordHash);
        
        echo "<p style='color: green;'><strong>✅ Password reset successfully!</strong></p>";
    }
    
    // Check role
    if ($admin['role'] !== 'admin') {
        echo "<h3>Role Issue Detected</h3>";
        echo "<p style='color: red;'><strong>❌ Role is '" . $admin['role'] . "' but should be 'admin'</strong></p>";
        echo "<p>Fixing role...</p>";
        
        $userRepo->updateRole($admin['id'], 'admin');
        
        echo "<p style='color: green;'><strong>✅ Role updated to 'admin'</strong></p>";
    } else {
        echo "<h3>Role Check</h3>";
        echo "<p style='color: green;'><strong>✅ Role is correctly set to 'admin'</strong></p>";
    }
}

echo "<hr>";
echo "<p><a href='admin-login.php'>← Back to Login</a></p>";
?>
