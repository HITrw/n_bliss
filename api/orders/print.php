<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../config/config.php';
require_once '../../includes/Database.php';

// First check authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID and type are required']);
    exit;
}

if (!in_array($_GET['type'], ['kitchen', 'receipt'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid print type']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get order details with related data - including discount fields
    $order = $db->fetch("
        SELECT o.*, t.table_number, e.name as employee_name
        FROM orders o
        LEFT JOIN tables t ON o.table_number = t.table_number
        LEFT JOIN employees e ON o.employee_id = e.id
        WHERE o.id = ?
    ", [$_GET['id']]);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items
    $itemsQuery = "
        SELECT oi.*, mi.name, mi.price, c.name as category,
               mi.is_drink, mi.description
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE oi.order_id = ?
    ";
    
    // For kitchen tickets, only include food items (not drinks)
    if ($_GET['type'] === 'kitchen') {
        $itemsQuery .= " AND mi.is_drink = 0";
    }
    
    $itemsQuery .= " ORDER BY c.name, mi.name";
    
    $items = $db->fetchAll($itemsQuery, [$_GET['id']]);
    
    // Group items by category
    $groupedItems = [];
    foreach ($items as $item) {
        $category = $item['category'] ?? 'Other';
        if (!isset($groupedItems[$category])) {
            $groupedItems[$category] = [];
        }
        $groupedItems[$category][] = [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'total' => $item['quantity'] * $item['price'], // Original stored price
            'description' => $item['description']
        ];
    }
    
    // Prepare print data with discount fields
    $printData = [
        'orderNumber' => $order['order_number'],
        'tableNumber' => $order['table_number'],
        'employee' => $order['employee_name'],
        'datetime' => date('Y-m-d H:i:s', strtotime($order['created_at'])),
        'items' => $groupedItems,
        'total' => $order['total_amount'],
        // Add discount fields for receipt
        'subtotal_amount' => $order['subtotal_amount'] ?? $order['total_amount'],
        'discount_amount' => $order['discount_amount'] ?? 0
    ];
    
    // Get receipt settings
    $receiptType = $db->fetch("
        SELECT setting_value 
        FROM settings 
        WHERE setting_key = 'receipt_type'
    ");
    $isThermoPrinter = ($receiptType && $receiptType['setting_value'] === 'thermal');
    
    // Load and render the appropriate template
    $template = $_GET['type'] === 'kitchen' ? 'kitchen-order.php' : 'receipt.php';
    
    ob_start();
    include "../../views/print/{$template}";
    $html = ob_get_clean();
    
    // If this is a kitchen order, mark it as printed
    if ($_GET['type'] === 'kitchen') {
        $db->execute(
            "UPDATE orders SET needs_printing = 0 WHERE id = ?",
            [$_GET['id']]
        );
    }
    
    // Send the response
    header('Content-Type: text/html');
    echo $html;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating print document: ' . $e->getMessage()
    ]);
}
?>