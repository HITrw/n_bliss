<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$error = '';
$success = '';

// Validate token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'];
$db = Database::getInstance();

// Check if token exists and is valid
$admin = $db->fetch(
    "SELECT id, email FROM employees
     WHERE reset_token = ? 
     AND reset_token_expiry > NOW() 
     AND reset_token IS NOT NULL",
    [$token]
);

if (!$admin) {
    $error = "Invalid or expired reset token. Please request a new password reset.";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash password and update
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $success = $db->execute(
            "UPDATE admins 
             SET password = ?,
                 reset_token = NULL,
                 reset_token_expiry = NULL,
                 updated_at = NOW()
             WHERE id = ?",
            [$hashed_password, $admin['id']]
        );
        
        if ($success) {
            $success = "Password has been reset successfully. You can now login with your new password.";
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        .reset-card {
            background: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="reset-container">
        <div class="reset-card">
            <div class="text-center mb-4">
                <img src="<?= BASE_URL ?>/assets/images/logo.jpg" alt="<?= SITE_NAME ?>" class="logo">
                <h2 class="mb-3">Reset Password</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php if (strpos($error, 'Invalid or expired') !== false): ?>
                    <div class="text-center">
                        <a href="forgot_password.php" class="btn btn-primary">Request New Reset Link</a>
                    </div>
                <?php endif; ?>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <div class="text-center">
                    <a href="index.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required 
                               minlength="8"
                               placeholder="Enter new password">
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required
                               placeholder="Confirm new password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
