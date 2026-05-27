<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Cart.php';

// Ensure proper error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if waiter is logged in
if (!isset($_SESSION['waiter_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    // Get JSON input and decode with error checking
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) {
        throw new Exception('No input data received', 400);
    }

    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg(), 400);
    }

    error_log("Create Order Input: " . $rawInput);

    // Validate input
    if (!$input || !isset($input['items']) || !isset($input['total'])) {
        throw new Exception('Missing required fields: items, total', 400);
    }

    // Validate cart is not empty
    if (empty($input['items'])) {
        throw new Exception('Cart is empty', 400);
    }

    $db = Database::getInstance();
    $cart = new Cart($_SESSION['waiter_id']);

    // Verify cart has items
    $cartItems = $cart->getItems();
    if (empty($cartItems)) {
        throw new Exception('Cart is empty in database', 400);
    }

    // Start transaction
    if (!$db->beginTransaction()) {
        throw new Exception('Failed to start transaction', 500);
    }

    try {
        // Create order
        $waiter_id   = $_SESSION['waiter_id']   ?? null;
        $waiter_name = $_SESSION['waiter_name'] ?? null;
        $success = $db->execute(
            "INSERT INTO orders (table_number, total_amount, status, waiter_id, waiter_name, created_at) VALUES (NULL, ?, 'pending', ?, ?, NOW())",
            [$input['total'], $waiter_id, $waiter_name]
        );

        if (!$success) {
            throw new Exception('Failed to create order');
        }

        $orderId = $db->lastInsertId();
        if (!$orderId) {
            throw new Exception('Failed to get order ID');
        }

        $orderNumber = date('Ymd') . str_pad($orderId, 4, '0', STR_PAD_LEFT);

        // Update order number
        if (!$db->execute(
            "UPDATE orders SET order_number = ? WHERE id = ?",
            [$orderNumber, $orderId]
        )) {
            throw new Exception('Failed to update order number');
        }

        // Insert order items
        foreach ($input['items'] as $item) {
            if (!$db->execute(
                "INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)",
                [$orderId, $item['id'], $item['quantity'], $item['price']]
            )) {
                throw new Exception('Failed to insert order item');
            }

            // Check if item is a drink and deduct from stock
            $drinkCheck = $db->fetch(
                "SELECT ds.id, ds.quantity FROM menu_items mi 
                 JOIN drink_stock ds ON mi.id = ds.menu_item_id
                 WHERE mi.id = ? AND mi.is_drink = 1",
                [$item['id']]
            );

            if ($drinkCheck) {
                // Ensure enough stock is available
                if ($drinkCheck['quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for drink item {$item['id']}");
                }

                // Deduct from stock
                if (!$db->execute(
                    "UPDATE drink_stock 
                     SET quantity = quantity - ?, 
                         updated_at = NOW() 
                     WHERE menu_item_id = ?",
                    [$item['quantity'], $item['id']]
                )) {
                    throw new Exception('Failed to update drink stock');
                }

                // Check if stock is low after deduction
                $newStock = $db->fetch(
                    "SELECT ds.quantity, ds.low_stock_threshold, mi.name 
                     FROM drink_stock ds 
                     JOIN menu_items mi ON mi.id = ds.menu_item_id 
                     WHERE ds.menu_item_id = ?",
                    [$item['id']]
                );

                if ($newStock['quantity'] <= $newStock['low_stock_threshold']) {
                    // Create low stock notification
                    $db->execute(
                        "INSERT INTO notifications (type, message, is_read, created_at) 
                         VALUES ('low_stock', ?, 0, NOW())",
                        ["'{$newStock['name']}' stock is low ({$newStock['quantity']} remaining)"]
                    );
                }
            }
        }

        // Clear the cart
        if (!$cart->clear()) {
            throw new Exception('Failed to clear cart');
        }

        // Commit transaction
        if (!$db->commit()) {
            throw new Exception('Failed to commit transaction');
        }

        // Detect which ticket types have items in this order
        $ticketRows = $db->fetchAll("
            SELECT DISTINCT
                CASE
                    WHEN mi.category_id IN (24,25,26) THEN 'coffee'
                    WHEN mi.category_id IN (27,28,29,30) THEN 'juice'
                    WHEN (c.id IN (12,20) OR c.parent_id IN (12,20)) THEN 'kitchen'
                    ELSE 'bar'
                END as ticket_type
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN categories c ON mi.category_id = c.id
            WHERE oi.order_id = ?
        ", [$orderId]);
        $ticketTypes = array_column($ticketRows, 'ticket_type');

        echo json_encode([
            'success'     => true,
            'message'     => 'Order created successfully',
            'orderNumber' => $orderNumber,
            'orderId'     => $orderId,
            'tickets'     => [
                'coffee'  => in_array('coffee',  $ticketTypes),
                'juice'   => in_array('juice',   $ticketTypes),
                'bar'     => in_array('bar',     $ticketTypes),
                'kitchen' => in_array('kitchen', $ticketTypes),
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on any error
        $db->rollback();
        throw $e; // Re-throw to be caught by outer catch block
    }

} catch (Exception $e) {
    error_log("Create Order Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Set appropriate status code
    $statusCode = $e->getCode();
    if (!is_int($statusCode) || $statusCode < 400 || $statusCode > 599) {
        $statusCode = 500;
    }
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getTraceAsString() : null
        ]
    ]);
}
