<?php
header('Content-Type: application/json');
session_start();
require_once '../../config/config.php';
require_once '../../includes/Database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['orderId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();
    
    // Get current order status
    $order = $db->fetch("SELECT status FROM orders WHERE id = ?", [$input['orderId']]);
    
    if (!$order) {
        throw new Exception('Order not found');
    }

    // Handle employee assignment
    if (isset($input['action']) && $input['action'] === 'assign' && isset($input['employeeId'])) {
        $success = $db->execute(
            "UPDATE orders SET employee_id = ? WHERE id = ?",
            [$input['employeeId'], $input['orderId']]
        );
        
        if (!$success) {
            throw new Exception('Failed to assign server');
        }
        
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Server assigned successfully'
        ]);
        exit;
    }

    // Handle order cancellation
    if (isset($input['action']) && $input['action'] === 'cancel') {
        // Get order items with drinks
        $drinkItems = $db->fetchAll(
            "SELECT oi.menu_item_id, oi.quantity, mi.name
             FROM order_items oi
             JOIN menu_items mi ON mi.id = oi.menu_item_id
             WHERE oi.order_id = ? AND mi.is_drink = 1",
            [$input['orderId']]
        );

        // Restore drink stock for cancelled items
        foreach ($drinkItems as $item) {
            // Restore the stock
            $db->execute(
                "UPDATE drink_stock 
                 SET quantity = quantity + ?,
                     updated_at = NOW()
                 WHERE menu_item_id = ?",
                [$item['quantity'], $item['menu_item_id']]
            );

            // Add notification for stock restoration
            $db->execute(
                "INSERT INTO notifications (type, message, is_read, created_at)
                 VALUES ('stock_update', ?, 0, NOW())",
                ["'{$item['name']}' stock restored (+{$item['quantity']}) due to order cancellation"]
            );
        }

        // Delete the order and its items
        $db->execute("DELETE FROM order_items WHERE order_id = ?", [$input['orderId']]);
        $db->execute("DELETE FROM orders WHERE id = ?", [$input['orderId']]);
        
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Order cancelled and stock restored successfully'
        ]);
        exit;
    }

    // Define valid statuses and flow
    $validStatuses = ['pending', 'completed', 'cancelled'];
    $statusFlow = [
        'pending' => 'completed',
    ];

    $newStatus = '';
    
    // Determine the new status based on action or direct status input
    if (isset($input['action'])) {
        if ($input['action'] === 'next' && isset($statusFlow[$order['status']])) {
            $newStatus = $statusFlow[$order['status']];
        } elseif ($input['action'] === 'approve') {
            $newStatus = 'approved';
        } elseif ($input['action'] === 'cancel') {
            $newStatus = 'cancelled';
        }
    } elseif (isset($input['status']) && in_array($input['status'], $validStatuses)) {
        $newStatus = $input['status'];
    }

    if (empty($newStatus)) {
        throw new Exception('Invalid status transition');
    }

    // Update order status
    $success = $db->execute(
        "UPDATE orders 
         SET status = ?, 
             updated_at = NOW(),
             employee_id = CASE 
                WHEN status = 'pending' AND ? IN ('approved', 'preparing') 
                THEN ? 
                ELSE employee_id 
             END
         WHERE id = ?",
        [$newStatus, $newStatus, $_SESSION['admin_id'], $input['orderId']]
    );
    
    if (!$success) {
        throw new Exception('Failed to update order');
    }

    // Handle kitchen printing for food orders when status becomes 'ready'
    if ($newStatus === 'ready') {
        $items = $db->fetchAll("
            SELECT COUNT(*) as food_count
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ? AND mi.is_drink = 0
        ", [$input['orderId']]);
        
        if ($items[0]['food_count'] > 0) {
            $db->execute(
                "UPDATE orders SET needs_printing = 1 WHERE id = ?",
                [$input['orderId']]
            );
        }
    }

    $db->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully',
        'newStatus' => $newStatus
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
