<?php
require_once __DIR__ . '/../backend/auth/admin-login.php';

$errorMsg = $errorMsg ?? '';
$successMsg = $successMsg ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Pickled</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-login.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <h1>Pickled Admin</h1>
            <p class="login-subtitle">Admin Login</p>
            
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMsg); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </form>
            
            <div class="demo-credentials">
                <p><strong>Demo Credentials:</strong></p>
                <p>Email: admin@example.com</p>
                <p>Password: password</p>
            </div>
        </div>
    </div>
</body>
</html>
