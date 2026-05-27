<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['itemId']) || !isset($data['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Start transaction
    $db->beginTransaction();
    
    // Update stock quantity (using prepared statement)
    $stmt = $db->execute("
        UPDATE drink_stock 
        SET quantity = quantity + ?, 
            updated_at = NOW() 
        WHERE menu_item_id = ?
    ", [$data['quantity'], $data['itemId']]);
    
    // Get updated item details
    $item = $db->fetch("
        SELECT mi.name, ds.quantity 
        FROM menu_items mi 
        JOIN drink_stock ds ON mi.id = ds.menu_item_id 
        WHERE mi.id = ?
    ", [$data['itemId']]);
    
    // Create restock notification
    $db->execute("
        INSERT INTO notifications (
            type,
            message,
            is_read,
            created_at
        ) VALUES (
            'stock_update',
            ?,
            0,
            NOW()
        )
    ", ["Stock updated: '{$item['name']}' now has {$item['quantity']} units"]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'newQuantity' => $item['quantity']
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update stock: ' . $e->getMessage()
    ]);
}