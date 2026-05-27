<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/Database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}


header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Get today's date
    $today = date('Y-m-d');
    
    // Get today's statistics
    $todayStats = $db->fetch("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
            SUM(total_amount) as total_sales
        FROM orders 
        WHERE DATE(created_at) = ?
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
    
    echo json_encode([
        'success' => true,
        'total_orders' => $todayStats['total_orders'],
        'pending_orders' => $todayStats['pending_orders'],
        'completed_orders' => $todayStats['completed_orders'],
        'total_sales' => number_format($todayStats['total_sales'], 2),
        'recent_orders' => $recentOrders,
        'top_items' => $topItems
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch dashboard statistics: ' . $e->getMessage()
    ]);
}
