<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/session_check.php';

// Check waiter session validity
checkTableSession();

$db = Database::getInstance();

// Get all active categories with proper ordering
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// Get all active menu items
$menuItems = $db->fetchAll("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY category_id, name");

// Group menu items by category
$menuByCategory = [];
foreach ($menuItems as $item) {
    if (!isset($menuByCategory[$item['category_id']])) {
        $menuByCategory[$item['category_id']] = [];
    }
    $menuByCategory[$item['category_id']][] = $item;
}

// Group categories by parent
$parentCategories = [];
$childCategories = [];
foreach ($categories as $category) {
    if (empty($category['parent_id'])) {
        $parentCategories[] = $category;
    } else {
        if (!isset($childCategories[$category['parent_id']])) {
            $childCategories[$category['parent_id']] = [];
        }
        $childCategories[$category['parent_id']][] = $category;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        const CURRENCY = <?php echo json_encode(CURRENCY); ?>;
        const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
        const WAITER_NAME = <?php echo json_encode($_SESSION['waiter_name'] ?? ''); ?>;
        window.API_BASE = '../';
    </script>
</head>
<body>
    <!-- Navigation - Only visible on desktop -->
    <nav class="navbar navbar-dark bg-dark sticky-top d-none d-lg-block">
        <div class="container">
            <a class="navbar-brand" href="pos.php">
                <img src="../assets/images/logo.jpg" alt="<?= SITE_NAME ?>" height="40">
            </a>
            <ul class="navbar-nav ms-auto d-flex flex-row">
                <li class="nav-item mx-2">
                    <span class="nav-link">Waiter: <?= htmlspecialchars($_SESSION['waiter_name'] ?? '') ?></span>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link cart-link" href="#" id="cartToggle2">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count badge bg-primary">0</span>
                    </a>
                </li>
                <li class="nav-item mx-2">
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Waiter Display -->
    <div class="d-lg-none bg-dark text-white text-center py-2" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000;">
        <span>Waiter: <?= htmlspecialchars($_SESSION['waiter_name'] ?? '') ?></span>
    </div>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="cart-sidebar">
        <div class="cart-header">
            <h4>Your Cart</h4>
            <button type="button" class="btn-close" id="closeCart"></button>
        </div>
        <div class="cart-items" id="cartItems">
            <!-- Cart items will be dynamically inserted here -->
        </div>
        <div class="cart-footer">
            <div class="d-flex justify-content-between mb-2">
                <span>Total:</span>
                <span id="cartTotal">$0.00</span>
            </div>
            <button class="btn btn-primary w-100" id="checkout">Checkout</button>
        </div>
    </div>

    <!-- Menu Section -->
    <div class="container py-5" style="padding-bottom: 80px !important; padding-top: 60px !important;">
        <h1 class="text-center mb-5">Our Menu</h1>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-md-6 mx-auto">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="menuSearch" placeholder="Search menu items...">
                </div>
            </div>
        </div>

        <!-- Category Tabs -->
        <ul class="nav nav-pills mb-4 justify-content-center" id="menuTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#all">All Items</button>
            </li>
            <?php foreach ($parentCategories as $parent): ?>
                <li class="nav-item dropdown" role="presentation">
                    <button class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($parent['name']) ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <button class="dropdown-item" data-bs-toggle="pill" data-bs-target="#category-<?= $parent['id'] ?>">
                                All <?= htmlspecialchars($parent['name']) ?>
                            </button>
                        </li>
                        <?php if (isset($childCategories[$parent['id']])): ?>
                            <?php foreach ($childCategories[$parent['id']] as $child): ?>
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="pill" data-bs-target="#category-<?= $child['id'] ?>">
                                        <?= htmlspecialchars($child['name']) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Menu Items Grid -->
        <div class="tab-content" id="menuContent">
            <!-- All Items Tab -->
            <div class="tab-pane fade show active" id="all">
                <div class="row g-2">
                    <?php foreach ($menuItems as $item): ?>
                        <div class="col-6 col-md-4 col-lg-3" style="margin-bottom: 10px;">
                            <div class="card h-100" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px;">
                                <div style="height: 140px; overflow: hidden;">
                                    <img src="<?= htmlspecialchars($item['image_path'] ? '../' . $item['image_path'] : '../assets/images/placeholder.jpg') ?>"
                                         class="card-img-top"
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         style="height: 100%; width: 100%; object-fit: cover;">
                                </div>
                                <div class="card-body p-2" style="font-size: 0.9rem;">
                                    <h5 class="card-title mb-1" style="font-size: 1rem; font-weight: 600;"><?= htmlspecialchars($item['name']) ?></h5>
                                    <p class="card-text mb-1" style="font-size: 0.8rem; color: #666; height: 32px; overflow: hidden;"><?= htmlspecialchars($item['description']) ?></p>
                                    <div class="price mb-2" style="color: #2c3e50; font-weight: 600;"><?= CURRENCY ?> <?= number_format($item['price'], 2) ?></div>
                                    <div class="d-flex gap-1 justify-content-between align-items-center">
                                        <div class="input-group input-group-sm flex-nowrap" style="width: 85px;">
                                            <button type="button" class="btn btn-outline-secondary btn-decrease px-1" data-item-id="<?= $item['id'] ?>" style="padding: 2px 6px;">-</button>
                                            <input type="text" class="form-control text-center item-quantity p-0" value="1" style="width: 30px; border-left: none; border-right: none;" readonly>
                                            <button type="button" class="btn btn-outline-secondary btn-increase px-1" data-item-id="<?= $item['id'] ?>" style="padding: 2px 6px;">+</button>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm add-to-cart" data-item-id="<?= $item['id'] ?>" style="padding: 4px 8px;">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Per-Category Tabs -->
            <?php foreach ($categories as $category): ?>
                <div class="tab-pane fade" id="category-<?= $category['id'] ?>">
                    <div class="row g-2">
                        <?php if (isset($menuByCategory[$category['id']])): ?>
                            <?php foreach ($menuByCategory[$category['id']] as $item): ?>
                                <div class="col-6 col-md-4 col-lg-3" style="margin-bottom: 10px;">
                                    <div class="card h-100" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px;">
                                        <div style="height: 140px; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($item['image_path'] ? '../' . $item['image_path'] : '../assets/images/placeholder.jpg') ?>"
                                                 class="card-img-top"
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 style="height: 100%; width: 100%; object-fit: cover;">
                                        </div>
                                        <div class="card-body p-2" style="font-size: 0.9rem;">
                                            <h5 class="card-title mb-1" style="font-size: 1rem; font-weight: 600;"><?= htmlspecialchars($item['name']) ?></h5>
                                            <p class="card-text mb-1" style="font-size: 0.8rem; color: #666; height: 32px; overflow: hidden;"><?= htmlspecialchars($item['description']) ?></p>
                                            <div class="price mb-2" style="color: #2c3e50; font-weight: 600;"><?= CURRENCY ?> <?= number_format($item['price'], 2) ?></div>
                                            <div class="d-flex gap-1 justify-content-between align-items-center">
                                                <div class="input-group input-group-sm flex-nowrap" style="width: 85px;">
                                                    <button type="button" class="btn btn-outline-secondary btn-decrease px-1" data-item-id="<?= $item['id'] ?>" style="padding: 2px 6px;">-</button>
                                                    <input type="text" class="form-control text-center item-quantity p-0" value="1" style="width: 30px; border-left: none; border-right: none;" readonly>
                                                    <button type="button" class="btn btn-outline-secondary btn-increase px-1" data-item-id="<?= $item['id'] ?>" style="padding: 2px 6px;">+</button>
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm add-to-cart" data-item-id="<?= $item['id'] ?>" style="padding: 4px 8px;">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-center">No items available in this category.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-lg-none" style="position: fixed; bottom: 0; left: 0; right: 0; background: #343a40; display: flex; justify-content: space-around; align-items: center; padding: 0.5rem; z-index: 1000; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);">
        <a href="pos.php" class="mobile-nav-item active" style="color: #fff; text-decoration: none; display: flex; flex-direction: column; align-items: center; padding: 0.5rem 1rem; position: relative;">
            <i class="fas fa-utensils" style="font-size: 1.2rem; margin-bottom: 0.2rem;"></i>
            <span style="font-size: 0.8rem;">Menu</span>
        </a>
        <a href="#" class="mobile-nav-item cart-link" id="cartToggle" style="color: #fff; text-decoration: none; display: flex; flex-direction: column; align-items: center; padding: 0.5rem 1rem; position: relative;">
            <i class="fas fa-shopping-cart" style="font-size: 1.2rem; margin-bottom: 0.2rem;"></i>
            <span style="font-size: 0.8rem;">Cart</span>
            <span class="cart-count badge bg-primary" style="position: absolute; top: 0; right: 25%; transform: translateX(50%);">0</span>
        </a>
        <a href="../logout.php" class="mobile-nav-item" style="color: #fff; text-decoration: none; display: flex; flex-direction: column; align-items: center; padding: 0.5rem 1rem; position: relative;">
            <i class="fas fa-sign-out-alt" style="font-size: 1.2rem; margin-bottom: 0.2rem;"></i>
            <span style="font-size: 0.8rem;">Logout</span>
        </a>
    </nav>

    <!-- Multiple Orders Modal -->
    <div class="modal fade" id="orderCompleteModal" tabindex="-1" aria-labelledby="orderCompleteModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderCompleteModalLabel">
                        <i class="fas fa-check-circle text-success me-2"></i>Order Placed!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-1">Order <strong id="modalOrderNumber"></strong> submitted successfully.</p>
                    <p class="text-muted">What would you like to do next?</p>
                </div>
                <div class="modal-footer d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-success" id="sameWaiterBtn">
                        <i class="fas fa-redo me-1"></i>New Order &mdash; Same Waiter
                    </button>
                    <a href="../logout.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user me-1"></i>New Order &mdash; Different Waiter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Expose modal trigger for main.js to call after successful order
        window.showOrderCompleteModal = function(orderNumber) {
            document.getElementById('modalOrderNumber').textContent = '#' + orderNumber;
            new bootstrap.Modal(document.getElementById('orderCompleteModal')).show();
        };

        // Same waiter: clear cart via AJAX, close modal, stay on page
        document.getElementById('sameWaiterBtn').addEventListener('click', function() {
            $.ajax({
                url: (window.API_BASE||'') + 'api/cart/manage.php?action=clear',
                method: 'POST',
                complete: function() {
                    bootstrap.Modal.getInstance(document.getElementById('orderCompleteModal')).hide();
                    if (typeof loadCartItems === 'function') loadCartItems();
                }
            });
        });

        // Initialize dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        dropdownElementList.map(function(el) { return new bootstrap.Dropdown(el); });

        // Menu search
        jQuery(function($) {
            const $searchInput = $('#menuSearch');
            const $menuItems = $('.card');

            $searchInput.on('input', function() {
                const searchTerm = $(this).val().toLowerCase().trim();
                if (searchTerm === '') { $menuItems.parent().show(); return; }
                $menuItems.each(function() {
                    const $item = $(this);
                    const match = $item.find('.card-title').text().toLowerCase().includes(searchTerm)
                               || $item.find('.card-text').text().toLowerCase().includes(searchTerm);
                    $item.parent().toggle(match);
                });
                $('#menuTabs .nav-link[data-bs-target="#all"]').tab('show');
            });

            $('#menuTabs .nav-link, #menuTabs .dropdown-item').on('click', function() {
                $searchInput.val('');
                $menuItems.parent().show();
            });
        });
    </script>
</body>
</html>