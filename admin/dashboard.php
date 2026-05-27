<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . " seconds ago";
    } elseif ($diff < 3600) {
        return floor($diff/60) . " minutes ago";
    } elseif ($diff < 86400) {
        return floor($diff/3600) . " hours ago";
    } elseif ($diff < 2592000) {
        return floor($diff/86400) . " days ago";
    } elseif ($diff < 31536000) {
        return floor($diff/2592000) . " months ago";
    } else {
        return floor($diff/31536000) . " years ago";
    }
}

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Get database instance
$db = Database::getInstance();

// Get today's statistics
$today = (new DateTime())->format('Y-m-d');
$todayStats = $db->fetch("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        SUM(total_amount) as total_sales,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_sales
    FROM orders 
    WHERE DATE(created_at) = ?
", [$today]);

$todayStats['total_sales'] = $todayStats['total_sales'] ?? 0;
$todayStats['completed_sales'] = $todayStats['completed_sales'] ?? 0;

// Get employee performance (completed orders)
$employeeStats = $db->fetchAll("
    SELECT 
        e.name as employee_name,
        e.id as employee_id,
        COUNT(o.id) as completed_orders,
        SUM(o.total_amount) as total_completed_sales,
        AVG(o.total_amount) as avg_order_value
    FROM employees e
    LEFT JOIN orders o ON e.id = o.employee_id 
        AND o.status = 'completed' 
        AND DATE(o.created_at) = ?
    GROUP BY e.id, e.name
    HAVING completed_orders > 0
    ORDER BY total_completed_sales DESC
", [$today]);

// Get top selling items
$topItems = $db->fetchAll("
    SELECT 
        mi.name,
        mi.price,
        COUNT(*) as order_count,
        SUM(oi.quantity) as total_quantity
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = ?
    GROUP BY mi.id
    ORDER BY total_quantity DESC
    LIMIT 5
", [$today]);

// Get orders by hour
$hourlyOrders = $db->fetchAll("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as order_count
    FROM orders
    WHERE DATE(created_at) = ?
    GROUP BY HOUR(created_at)
    ORDER BY hour
", [$today]);

// Get recent orders
$recentOrders = $db->fetchAll("
    SELECT 
        o.*,
        t.table_number,
        e.name as employee_name
    FROM orders o
    LEFT JOIN tables t ON o.table_number = t.table_number
    LEFT JOIN employees e ON o.employee_id = e.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Get low stock alerts
$lowStockDrinks = $db->fetchAll("
    SELECT 
        mi.name,
        ds.quantity,
        ds.low_stock_threshold
    FROM drink_stock ds
    JOIN menu_items mi ON ds.menu_item_id = mi.id
    WHERE ds.quantity <= ds.low_stock_threshold
    ORDER BY ds.quantity ASC
");

// Format hourly data for Chart.js
$hours = array_fill(0, 24, 0);
foreach ($hourlyOrders as $order) {
    $hours[$order['hour']] = $order['order_count'];
}

include 'header.php';
?>

<div class="container-fluid dashboard-container py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Dashboard</h1>
        <div class="btn-group">
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Orders</h6>
                            <h2 class="mt-2 mb-0 total-orders"><?= $todayStats['total_orders'] ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Today's Sales</h6>
                            <h2 class="mt-2 mb-0 total-sales"><?= CURRENCY ?><?= number_format($todayStats['total_sales'], 2) ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Pending Orders</h6>
                            <h2 class="mt-2 mb-0 pending-orders"><?= $todayStats['pending_orders'] ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Completed Orders</h6>
                            <h2 class="mt-2 mb-0 completed-orders"><?= $todayStats['completed_orders'] ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New row for Completed Sales -->
    <div class="row g-4 mb-4">
        <!--<div class="col-12 col-md-6">-->
        <!--    <div class="card stat-card bg-gradient-success text-white h-100">-->
        <!--        <div class="card-body">-->
        <!--            <div class="d-flex justify-content-between align-items-center">-->
        <!--                <div>-->
        <!--                    <h6 class="card-title mb-0">Completed Sales</h6>-->
        <!--                    <h2 class="mt-2 mb-0 completed-sales"><?= CURRENCY ?><?= number_format($todayStats['completed_sales'], 2) ?></h2>-->
        <!--                    <small class="opacity-75">From <?= $todayStats['completed_orders'] ?> completed orders</small>-->
        <!--                </div>-->
        <!--                <div class="stat-icon">-->
        <!--                    <i class="fas fa-coins fa-2x opacity-50"></i>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </div>-->
        <!--</div>-->
        
    <!--    <div class="col-12 col-md-6">-->
    <!--        <div class="card stat-card bg-gradient-info text-white h-100">-->
    <!--            <div class="card-body">-->
    <!--                <div class="d-flex justify-content-between align-items-center">-->
    <!--                    <div>-->
    <!--                        <h6 class="card-title mb-0">Average Order Value</h6>-->
    <!--                        <h2 class="mt-2 mb-0">-->
    <!--                            <?= CURRENCY ?><?= $todayStats['total_orders'] > 0 ? number_format($todayStats['total_sales'] / $todayStats['total_orders'], 2) : '0.00' ?>-->
    <!--                        </h2>-->
    <!--                        <small class="opacity-75">Across all orders today</small>-->
    <!--                    </div>-->
    <!--                    <div class="stat-icon">-->
    <!--                        <i class="fas fa-chart-line fa-2x opacity-50"></i>-->
    <!--                    </div>-->
    <!--                </div>-->
    <!--            </div>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</div>-->

    <div class="row g-4">
        <!-- Employee Performance -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-tie me-2"></i>
                        Employee Performance (Completed Orders)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($employeeStats)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Orders</th>
                                        <th>Total Sales</th>
                                        <th>Avg. Order</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employeeStats as $employee): ?>
                                        <tr>
                                            <td>
                                                <strong><?= h($employee['employee_name']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= $employee['completed_orders'] ?></span>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold">
                                                    <?= CURRENCY ?><?= number_format($employee['total_completed_sales'], 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= CURRENCY ?><?= number_format($employee['avg_order_value'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No completed orders today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Selling Items -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Selling Items</h5>
                </div>
                <div class="card-body">
                    <?php if ($topItems): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topItems as $item): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= h($item['name']) ?></h6>
                                        <small class="text-muted"><?= $item['order_count'] ?> orders</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?= $item['total_quantity'] ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted my-4">No orders today</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="card admin-table mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Table</th>
                                    <th>Employee</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recentOrders">
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                        <td><?= htmlspecialchars($order['table_number']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $order['employee_name'] ? h($order['employee_name']) : 'N/A' ?>
                                            </small>
                                        </td>
                                        <td><?= CURRENCY ?><?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= timeAgo($order['created_at']) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" 
                                                        class="btn btn-primary" 
                                                        onclick="viewOrder('<?= $order['id'] ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-success" 
                                                        onclick="updateOrderStatus('<?= $order['id'] ?>', 'next')">
                                                    <i class="fas fa-forward"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Selling Items (Detailed) -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Top Selling Items Today</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($topItems)): ?>
                        <?php foreach ($topItems as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                    <small class="text-muted">
                                        <?= $item['total_quantity'] ?> orders - <?= CURRENCY ?><?= number_format($item['price'], 2) ?>
                                    </small>
                                </div>
                                <div class="h5 mb-0">
                                    <?= CURRENCY ?><?= number_format($item['price'] * $item['total_quantity'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No items sold today</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <!-- Low Stock Alerts -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Low Stock Alerts
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStockDrinks)): ?>
                        <p class="text-muted">All drink items are well stocked.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Drink Name</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockDrinks as $drink): ?>
                                        <tr>
                                            <td><?= h($drink['name']) ?></td>
                                            <td><?= $drink['quantity'] ?></td>
                                            <td>
                                                <?php if ($drink['quantity'] === 0): ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Low Stock</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Other dashboard widgets -->
        <div class="col-md-6">
            <!-- Placeholder for future widgets -->
        </div>
    </div>
</div>

<script>
// Initialize real-time updates when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeEventSource();
// Utility functions
function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'approved': 'info',
        'preparing': 'primary',
        'ready': 'success',
        'served': 'success',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " years ago";
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " months ago";
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " days ago";
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " hours ago";
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutes ago";
    
    return Math.floor(seconds) + " seconds ago";
}

// Order management functions
function viewOrder(orderId) {
    window.location.href = `order-details.php?id=${orderId}`;
}

function updateOrderStatus(orderId, action) {
    fetch(`/api/orders/update-status.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            orderId: orderId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh the orders list
            location.reload();
        } else {
            alert(data.message || 'Failed to update order status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update order status');
    });
}

// Real-time updates
let eventSource;

function initializeEventSource() {
    eventSource = new EventSource('/api/orders/events.php');
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        if (data.type === 'new_order') {
            // Play notification sound
            if (document.getElementById('notificationSound')) {
                document.getElementById('notificationSound').play();
            }
            
            // Show notification
            showNotification('New Order', `Order #${data.order.order_number} received from Table ${data.order.table_number}`);
            
            // Update statistics
            updateDashboardStats();
        }
    };
    
    eventSource.onerror = function() {
        // Try to reconnect after 5 seconds
        eventSource.close();
        setTimeout(initializeEventSource, 5000);
    };
}

function updateDashboardStats() {
    fetch('/api/dashboard/stats.php')
        .then(response => response.json())
        .then(data => {
            // Update statistics cards
            document.querySelector('.total-orders').textContent = data.total_orders;
            document.querySelector('.pending-orders').textContent = data.pending_orders;
            document.querySelector('.completed-orders').textContent = data.completed_orders;
            document.querySelector('.total-sales').textContent = '<?= CURRENCY ?>' + data.total_sales;
            document.querySelector('.completed-sales').textContent = '<?= CURRENCY ?>' + data.completed_sales;
            
            // Update recent orders table
            const ordersTable = document.getElementById('recentOrders');
            if (ordersTable && data.recent_orders) {
                ordersTable.innerHTML = data.recent_orders.map(order => `
                    <tr>
                        <td>${order.order_number}</td>
                        <td>${order.table_number}</td>
                        <td><small class="text-muted">${order.employee_name || 'N/A'}</small></td>
                        <td><?= CURRENCY ?>${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>
                            <span class="badge bg-${getStatusColor(order.status)}">
                                ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                            </span>
                        </td>
                        <td>${timeAgo(order.created_at)}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-primary" onclick="viewOrder('${order.id}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-success" onclick="updateOrderStatus('${order.id}', 'next')">
                                    <i class="fas fa-forward"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

function showNotification(title, message) {
    // Check if the browser supports notifications
    if ("Notification" in window) {
        // Check if permission is granted
        if (Notification.permission === "granted") {
            new Notification(title, {
                body: message,
                icon: '/assets/images/logo.png' // Add your logo path here
            });
        } else if (Notification.permission !== "denied") {
            // Ask for permission
            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    new Notification(title, {
                        body: message,
                        icon: '/assets/images/logo.png'
                    });
                }
            });
        }
    }
}
    document.addEventListener('DOMContentLoaded', function() {
    initializeEventSource();
    // Update stats every minute
    setInterval(updateDashboardStats, 60000);

    // Request notification permission on page load
    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission();
    }
});
</script>

<style>
.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
}

.stat-card {
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.employee-performance-card {
    border-left: 4px solid #007bff;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,.075);
}
</style>