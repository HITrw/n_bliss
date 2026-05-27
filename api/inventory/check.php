<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Start transaction
    $db->beginTransaction();

    // Update last check timestamp
    $db->execute("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES ('last_inventory_check', NOW()) 
        ON DUPLICATE KEY UPDATE setting_value = NOW()
    ");

    // Check stock levels
    $lowStockItems = $db->fetchAll("
        SELECT 
            mi.id,
            mi.name,
            ds.quantity,
            ds.low_stock_threshold
        FROM menu_items mi
        JOIN drink_stock ds ON mi.id = ds.menu_item_id
        WHERE ds.quantity <= ds.low_stock_threshold
    ");

    // Create notifications for low stock items
    foreach ($lowStockItems as $item) {
        $db->execute("
            INSERT INTO notifications (
                type,
                title,
                message,
                is_read,
                created_at
            ) VALUES (
                'low_stock',
                'Low Stock Alert',
                ?,
                0,
                NOW()
            )
        ", ["'{$item['name']}' is running low ({$item['quantity']} remaining)"]);
    }

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Inventory check completed successfully',
        'lowStockCount' => count($lowStockItems)
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollback();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Failed to check inventory: ' . $e->getMessage()
    ]);
}
