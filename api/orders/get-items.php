<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

$orderId = $_GET['order_id'] ?? 0;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$db = Database::getInstance();

try {
    // Get order items with prices and discount information
    $items = $db->fetchAll("
        SELECT 
            oi.id, 
            mi.name, 
            oi.quantity, 
            mi.price as menu_price,
            mi.is_drink,
            c.name as category_name,
            oi.original_price,
            oi.final_price,
            oi.discount_type,
            oi.discount_value,
            oi.discount_amount,
            CASE 
                WHEN oi.original_price IS NOT NULL THEN oi.original_price
                ELSE mi.price
            END as current_original_price,
            CASE 
                WHEN oi.final_price IS NOT NULL THEN oi.final_price
                ELSE mi.price
            END as current_final_price
        FROM order_items oi
        JOIN menu_items mi ON mi.id = oi.menu_item_id
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE oi.order_id = ?
        ORDER BY mi.name
    ", [$orderId]);

    // Get order details
    $order = $db->fetch("
        SELECT 
            id,
            subtotal_amount,
            total_amount,
            discount_type as order_discount_type,
            discount_value as order_discount_value,
            discount_amount as order_discount_amount
        FROM orders 
        WHERE id = ?
    ", [$orderId]);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
        'order' => $order
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching order data: ' . $e->getMessage()]);
}
?>