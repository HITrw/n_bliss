<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

// If already logged in as waiter, redirect to menu
if (isset($_SESSION['waiter_id'])) {
    header("Location: menu.php");
    exit;
}

$db = Database::getInstance();

// Fetch active staff/waiters from employees table
$waiters = $db->fetchAll(
    "SELECT id, name FROM employees WHERE role = 'staff' AND is_active = 1 ORDER BY name"
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $waiter_id = isset($_POST['waiter_id']) ? intval($_POST['waiter_id']) : 0;

    if ($waiter_id <= 0) {
        $error = "Please select a waiter to continue.";
    } else {
        // Verify waiter exists and is active
        $waiter = $db->fetch(
            "SELECT id, name FROM employees WHERE id = ? AND role = 'staff' AND is_active = 1",
            [$waiter_id]
        );

        if (!$waiter) {
            $error = "Invalid waiter selection. Please try again.";
        } else {
            $_SESSION['waiter_id']   = $waiter['id'];
            $_SESSION['waiter_name'] = $waiter['name'];

            header("Location: menu.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Start Ordering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="assets/images/logo.jpg" alt="<?= SITE_NAME ?>" class="restaurant-logo">
                <h2 class="mb-3">Welcome to <?= SITE_NAME ?></h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="waiter_id" class="form-label">Select Waiter</label>
                    <select class="form-control form-control-lg"
                            id="waiter_id"
                            name="waiter_id"
                            required>
                        <option value="">-- Select Waiter --</option>
                        <?php foreach ($waiters as $waiter): ?>
                            <option value="<?= $waiter['id'] ?>"
                                <?= (isset($_POST['waiter_id']) && $_POST['waiter_id'] == $waiter['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($waiter['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Start Ordering</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
