<?php
// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8639623010963658"
     crossorigin="anonymous"></script>
    <title><?= $pageTitle ?? 'Admin Panel' ?> - <?= SITE_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">    <style>
        .bg-secondary-light { background-color: rgba(255,255,255,0.1); }
        .nav-link { color: rgba(255,255,255,0.8) !important; }
        .nav-link:hover { 
            background-color: rgba(255,255,255,0.1);
            color: #fff !important;
        }
        .nav-link.active {
            background-color: rgba(255,255,255,0.15) !important;
            color: #fff !important;
        }
    </style>
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
</head>
<body>    <div class="d-flex">        <!-- Sidebar -->
        <div class="position-fixed vh-100" style="width: 170px; background-color:rgb(2, 2, 83);">
            <div class="p-3 border-bottom border-light">
                <span class="navbar-brand text-white fs-6">N'S BLISS LOUNGE</span>
            </div>
            <div class="nav flex-column p-2">
                <a href="dashboard.php" class="nav-link text-white <?= $currentPage === 'dashboard' ? 'active bg-secondary-light' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="orders.php" class="nav-link <?= $currentPage === 'orders' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i> Orders
                <?php if (isset($pendingOrders) && $pendingOrders > 0): ?>
                    <span class="badge bg-primary"><?= $pendingOrders ?></span>
                <?php endif; ?>
            </a>
            <a href="menu.php" class="nav-link <?= $currentPage === 'menu' ? 'active' : '' ?>">
                <i class="fas fa-utensils"></i> Menu
            </a>
            <a href="categories.php" class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="tables.php" class="nav-link <?= $currentPage === 'tables' ? 'active' : '' ?>">
                <i class="fas fa-chair"></i> Tables
            </a>
            <?php if ($_SESSION['admin_role'] === 'admin'): ?>
            <a href="employees.php" class="nav-link <?= $currentPage === 'employees' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Employees
            </a>
            <?php endif; ?>
            <a href="reports.php" class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <?php if ($_SESSION['admin_role'] === 'admin'): ?>
            <a href="inventory.php" class="nav-link <?= $currentPage === 'inventory' ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i> Stock
            </a>
            <?php endif; ?>
           <a href="logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="ms-200" style="margin-left: 155px;">
            <div class="container-fluid py-4">
            <!-- Your page content here -->
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>