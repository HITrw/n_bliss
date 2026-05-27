<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/TableManager.php';

// Clean up any abandoned sessions
TableManager::getInstance()->cleanupAbandonedSessions();

// If already logged in with a table number, redirect to menu
if(isset($_SESSION['table_number'])) {
    header("Location: menu.php");
    exit;
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $table_number = isset($_POST['table_number']) ? trim($_POST['table_number']) : '';
    $tableManager = TableManager::getInstance();
    
    // Check if table is available
    if($tableManager->isTableAvailable($table_number)) {
        // Update table status to occupied
        if($tableManager->occupyTable($table_number)) {
            // Set session
            $_SESSION['table_number'] = $table_number;
            $_SESSION['table_last_activity'] = time();
            
            // Redirect to menu
            header("Location: menu.php");
            exit;
        } else {
            $error = "Error assigning table. Please try again.";
        }
    } else {
        $error = "Invalid table number or table is already occupied";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8639623010963658"
     crossorigin="anonymous"></script>
    <title><?= SITE_NAME ?> - Table Login</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e2a78 0%, #ff2e4c 100%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
        }
        .restaurant-logo {
            width: 150px;
            height: auto;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-lg-none" style="position: fixed; bottom: 0; left: 0; right: 0; background: #343a40; display: flex; justify-content: center; padding: 0.5rem; z-index: 1000; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);">
        <a href="<?= BASE_URL ?>" class="mobile-nav-item" style="color: #fff; text-decoration: none; display: flex; flex-direction: column; align-items: center; padding: 0.5rem 1rem;">
            <i class="fas fa-home" style="font-size: 1.2rem; margin-bottom: 0.2rem;"></i>
            <span style="font-size: 0.8rem;">Home</span>
        </a>
    </nav>

    <div class="login-container" style="padding-bottom: 60px;">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="assets/images/logo.jpg" alt="<?= SITE_NAME ?>" class="restaurant-logo">
                <h2 class="mb-3">Welcome to <?= SITE_NAME ?></h2>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="table_number" class="form-label">Please Enter Your Table Number</label>
                    <input type="text" 
                           class="form-control form-control-lg text-center" 
                           id="table_number" 
                           name="table_number" 
                           required 
                           placeholder="Enter Table Number"
                           autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Start Ordering</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
