<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../includes/Database.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['mainOrderId']) || !isset($input['orderIds']) || empty($input['orderIds'])) {
        throw new Exception('Invalid input data');
    }

    $db = Database::getInstance();
    
    // Start transaction
    $db->beginTransaction();

    try {
        // Get main order details
        $mainOrder = $db->fetch("
            SELECT table_number, status 
            FROM orders 
            WHERE id = ? AND status = 'pending'", 
            [$input['mainOrderId']]
        );

        if (!$mainOrder) {
            throw new Exception('Main order not found or not pending');
        }

        // Verify all orders are from same table and pending
        $ordersList = implode(',', array_map('intval', $input['orderIds']));
        $ordersToMerge = $db->fetchAll("
            SELECT id, table_number, total_amount 
            FROM orders 
            WHERE id IN ({$ordersList}) 
                AND status = 'pending' 
                AND table_number = ?",
            [$mainOrder['table_number']]
        );

        if (count($ordersToMerge) !== count($input['orderIds'])) {
            throw new Exception('Some orders are not valid for merging');
        }

        // Move all order items to main order
        foreach ($ordersToMerge as $order) {
            // Update order items to point to main order
            $db->execute(
                "UPDATE order_items SET order_id = ? WHERE order_id = ?",
                [$input['mainOrderId'], $order['id']]
            );

            // Delete the merged order
            $db->execute("DELETE FROM orders WHERE id = ?", [$order['id']]);
        }

        // Update total amount in main order
        $newTotal = $db->fetch(
            "SELECT SUM(quantity * price) as total 
             FROM order_items 
             WHERE order_id = ?",
            [$input['mainOrderId']]
        );

        $db->execute(
            "UPDATE orders SET total_amount = ? WHERE id = ?",
            [$newTotal['total'], $input['mainOrderId']]
        );

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Orders merged successfully'
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
