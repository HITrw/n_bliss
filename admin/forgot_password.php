<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $db = Database::getInstance();
    
    // Check if email exists
    $admin = $db->fetch("SELECT id, email FROM employees WHERE email = ?", [$email]);
    
    if ($admin) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token in database
        $db->execute(
            "UPDATE employees SET 
                reset_token = ?,
                reset_token_expiry = ?,
                updated_at = NOW()
            WHERE id = ?",
            [$token, $expiry, $admin['id']]
        );
        
        // Create reset link
        $resetLink = BASE_URL . '/admin/reset_password.php?token=' . $token;
        
        // Email configuration
        $to = $admin['email'];
        $subject = SITE_NAME . " - Password Reset Request";
        $message = "Hello,\n\n";
        $message .= "You have requested to reset your password. Please click the link below to reset your password:\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you did not request this password reset, please ignore this email.\n\n";
        $message .= "Best regards,\n";
        $message .= SITE_NAME . " Team";
        
        $headers = "From: " . SITE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if (mail($to, $subject, $message, $headers)) {
            $success = "Password reset instructions have been sent to your email.";
        } else {
            $error = "Failed to send reset email. Please try again.";
        }
    } else {
        // Don't reveal if email exists or not for security
        $success = "If your email exists in our system, you will receive password reset instructions.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        .forgot-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        .forgot-card {
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
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="text-center mb-4">
                <img src="<?= BASE_URL ?>/assets/images/logo.jpg" alt="<?= SITE_NAME ?>" class="logo">
                <h2 class="mb-3">Forgot Password</h2>
                <p class="text-muted">Enter your email address to reset your password.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           required 
                           placeholder="Enter your email">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                    <a href="index.php" class="btn btn-link">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
