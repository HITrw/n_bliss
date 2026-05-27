<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../includes/Database.php';

try {
    // Log request parameters for debugging
    error_log("Merge API Request - Table: " . ($_GET['tableNumber'] ?? 'none') . ", OrderID: " . ($_GET['orderId'] ?? 'none'));
    
    if (!isset($_GET['tableNumber'])) {
        throw new Exception('Table number is required');
    }
    
    $db = Database::getInstance();
      
    // Get all pending orders for the same table except the current order
    // REMOVED date restriction to allow merging orders from different days
    $orders = $db->fetchAll("
        SELECT 
            o.id,
            o.order_number,
            o.total_amount,
            o.created_at,
            (
                SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', mi.name) SEPARATOR '\n')
                FROM order_items oi
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE oi.order_id = o.id
            ) as items_summary        
        FROM orders o
        WHERE o.table_number = ?
            AND o.status = 'pending'
            AND o.id != ?  -- Exclude the current order
        ORDER BY o.created_at DESC    
    ", [$_GET['tableNumber'], $_GET['orderId'] ?? 0]);    
    
    // Log response for debugging
    error_log("Merge API Response - Found Orders: " . count($orders));
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'debug' => [
            'table' => $_GET['tableNumber'],
            'orderId' => $_GET['orderId'],
            'orderCount' => count($orders)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>