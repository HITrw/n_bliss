<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/Database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Get database instance
$db = Database::getInstance();

// Keep track of last checked timestamp
$lastCheck = date('Y-m-d H:i:s');

while (true) {
    // Check for new orders
    $newOrders = $db->fetchAll("
        SELECT 
            o.*,
            t.table_number,
            e.name as employee_name
        FROM orders o
        LEFT JOIN tables t ON o.table_number = t.table_number
        LEFT JOIN employees e ON o.employee_id = e.id
        WHERE o.created_at > ?
        ORDER BY o.created_at DESC
    ", [$lastCheck]);

    if (!empty($newOrders)) {
        foreach ($newOrders as $order) {
            $data = [
                'type' => 'new_order',
                'order' => $order
            ];
            
            echo "data: " . json_encode($data) . "\n\n";
            
            // Update last check timestamp
            $lastCheck = $order['created_at'];
        }
    }
    
    // Flush output
    ob_flush();
    flush();
    
    // Wait for 2 seconds before checking again
    sleep(2);
    
    // Check if client is still connected
    if (connection_status() != CONNECTION_NORMAL) {
        break;
    }
}
