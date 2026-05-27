<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Cart.php';

// Ensure errors are reported
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug logging
error_log("Cart API: Request received - " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if waiter is logged in
if (!isset($_SESSION['waiter_id'])) {
    error_log("Cart API: No waiter_id in session");
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in'
    ]);
    exit;
}

try {
    $cart = new Cart($_SESSION['waiter_id']);
    
    // Get request data - support both JSON and form data
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    $data = [];
    
    if (strpos($contentType, 'application/json') === 0) {
        $input = file_get_contents('php://input');
        error_log("Cart API: Raw input: " . $input);
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }
    } else {
        $data = $_POST;
    }

    $action = $_GET['action'] ?? '';
    if (empty($action)) {
        throw new Exception('No action specified');
    }

    error_log("Cart API: Processing action: " . $action);
    error_log("Cart API: Data: " . json_encode($data));

    // Initialize response
    $response = [
        'status' => 'success',
        'data' => []
    ];

    switch ($action) {
        case 'add':
            error_log("Cart API: Processing add action");
            if (!isset($data['menu_item_id'], $data['quantity'])) {
                throw new Exception('Missing required fields: menu_item_id and quantity');
            }

            if (!is_numeric($data['menu_item_id']) || !is_numeric($data['quantity']) || $data['quantity'] <= 0) {
                throw new Exception('Invalid menu_item_id or quantity');
            }

            $cart->addItem($data['menu_item_id'], $data['quantity']);
            $response['data'] = [
                'message' => 'Item added to cart',
                'cart_count' => $cart->getCount(),
                'total' => $cart->getTotal()
            ];
            break;

        case 'update':
            error_log("Cart API: Processing update action");
            if (!isset($data['cart_id'], $data['quantity'])) {
                throw new Exception('Missing required fields: cart_id and quantity');
            }

            if (!is_numeric($data['cart_id']) || !is_numeric($data['quantity'])) {
                throw new Exception('Invalid cart_id or quantity');
            }

            $cart->updateQuantity($data['cart_id'], $data['quantity']);
            $response['data'] = [
                'message' => 'Cart updated',
                'cart_count' => $cart->getCount(),
                'total' => $cart->getTotal()
            ];
            break;

        case 'remove':
            error_log("Cart API: Processing remove action");
            if (!isset($data['cart_id'])) {
                throw new Exception('Missing required field: cart_id');
            }

            if (!is_numeric($data['cart_id'])) {
                throw new Exception('Invalid cart_id');
            }

            $cart->removeItem($data['cart_id']);
            $response['data'] = [
                'message' => 'Item removed',
                'cart_count' => $cart->getCount(),
                'total' => $cart->getTotal()
            ];
            break;

        case 'clear':
            error_log("Cart API: Processing clear action");
            $cart->clear();
            $response['data'] = [
                'message' => 'Cart cleared',
                'cart_count' => 0,
                'total' => 0
            ];
            break;

        case 'get':
            error_log("Cart API: Processing get action");
            $items = $cart->getItems();
            $response['data'] = [
                'items' => $items,
                'total' => $cart->getTotal(),
                'count' => $cart->getCount()
            ];
            break;

        default:
            throw new Exception('Invalid action: ' . $action);
    }

    error_log("Cart API: Sending response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Cart API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
