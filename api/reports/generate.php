<?php
// Ensure no output before headers
ob_start();

// Configure error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header for JSON response
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Access denied. Please log in as admin.'
    ], 403);
}

if (!isset($_POST['reportType'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Report type is required'
    ], 400);
}

try {
    $db = Database::getInstance();
    
    $reportType = $_POST['reportType'] ?? '';
    $dateRange = $_POST['dateRange'] ?? '';
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $startTime = $_POST['startTime'] ?? '00:00';
    $endTime = $_POST['endTime'] ?? '23:59';
    $category = $_POST['category'] ?? 'all';
    $stockStatus = $_POST['stockStatus'] ?? 'all';

    if ($reportType === 'sales') {
        // Calculate date range
        switch ($dateRange) {
            case 'today':
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
                $startTime = '00:00';
                $endTime = '23:59';
                break;
            case 'yesterday':
                $startDate = date('Y-m-d', strtotime('-1 day'));
                $endDate = date('Y-m-d', strtotime('-1 day'));
                $startTime = '00:00';
                $endTime = '23:59';
                break;
            case 'thisWeek':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = date('Y-m-d', strtotime('sunday this week'));
                $startTime = '00:00';
                $endTime = '23:59';
                break;
            case 'thisMonth':
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                $startTime = '00:00';
                $endTime = '23:59';
                break;
            case 'custom':
                // Dates and times are already set from POST
                break;
        }

        // Create datetime strings
        $startDateTime = $startDate . ' ' . $startTime;
        $endDateTime = $endDate . ' ' . $endTime;

        // Get sales data - FIXED: Use total_amount from orders table instead of calculating from order_items
        $query = "
            SELECT 
                DATE(o.created_at) as date,
                TIME(o.created_at) as time,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_sales,
                SUM(oi.quantity) as items_sold
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            WHERE o.created_at BETWEEN ? AND ?
                AND o.status != 'cancelled'
            GROUP BY DATE(o.created_at), HOUR(o.created_at)
            ORDER BY date DESC, time DESC
        ";
        
        $salesData = $db->fetchAll($query, [$startDateTime, $endDateTime]);
        
        // Calculate totals - FIXED: Use total_amount from orders table
        $totalQuery = "
            SELECT 
                SUM(o.total_amount) as total_sales,
                COUNT(DISTINCT o.id) as total_orders
            FROM orders o
            WHERE o.created_at BETWEEN ? AND ?
                AND o.status != 'cancelled'
        ";
        $totals = $db->fetch($totalQuery, [$startDateTime, $endDateTime]);
        
        $totalSales = $totals['total_sales'] ?? 0;
        $totalOrders = $totals['total_orders'] ?? 0;
        
        // Get total items sold
        $itemsQuery = "
            SELECT SUM(oi.quantity) as total_items
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.created_at BETWEEN ? AND ?
                AND o.status != 'cancelled'
        ";
        $itemsResult = $db->fetch($itemsQuery, [$startDateTime, $endDateTime]);
        $totalItems = $itemsResult['total_items'] ?? 0;
        
        // Get top selling items by quantity
        $topItemsQuery = "
            SELECT 
                mi.name,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.price * oi.quantity) as total_revenue
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN menu_items mi ON mi.id = oi.menu_item_id
            WHERE o.created_at BETWEEN ? AND ?
                AND o.status != 'cancelled'
            GROUP BY mi.id, mi.name
            ORDER BY quantity_sold DESC
            LIMIT 5
        ";
        $topItems = $db->fetchAll($topItemsQuery, [$startDateTime, $endDateTime]);

        // Get all items sold in the selected range
        $allItemsQuery = "
            SELECT 
                mi.name,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.price * oi.quantity) as total_revenue
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN menu_items mi ON mi.id = oi.menu_item_id
            WHERE o.created_at BETWEEN ? AND ?
                AND o.status != 'cancelled'
            GROUP BY mi.id, mi.name
            ORDER BY quantity_sold DESC
        ";
        $allItems = $db->fetchAll($allItemsQuery, [$startDateTime, $endDateTime]);

        sendJsonResponse([
            'success' => true,
            'data' => [
                'summary' => [
                    'totalSales' => $totalSales,
                    'totalOrders' => $totalOrders,
                    'totalItems' => $totalItems,
                    'startDate' => $startDate,
                    'endDate' => $endDate
                ],
                'dailySales' => $salesData,
                'topItems' => $topItems,
                'allItems' => $allItems
            ]
        ]);
        
    } else if ($reportType === 'inventory') {
        $params = [];
        $whereClause = [];
        
        if ($category !== 'all') {
            $whereClause[] = "c.id = ?";
            $params[] = $category;
        }
        
        switch ($stockStatus) {
            case 'inStock':
                $whereClause[] = "COALESCE(ds.quantity, 0) > 5";
                break;
            case 'lowStock':
                $whereClause[] = "COALESCE(ds.quantity, 0) <= 5 AND COALESCE(ds.quantity, 0) > 0";
                break;
            case 'outOfStock':
                $whereClause[] = "COALESCE(ds.quantity, 0) = 0";
                break;
        }
        
        $whereStr = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
        $query = "
            SELECT 
                mi.id,
                mi.name,
                c.name as category,
                COALESCE(ds.quantity, 0) as stock_quantity,
                CASE 
                    WHEN COALESCE(ds.quantity, 0) = 0 THEN 'Out of Stock'
                    WHEN COALESCE(ds.quantity, 0) <= 5 THEN 'Low Stock'
                    ELSE 'In Stock'
                END as stock_status
            FROM menu_items mi
            LEFT JOIN categories c ON c.id = mi.category_id
            LEFT JOIN drink_stock ds ON ds.menu_item_id = mi.id
            WHERE mi.is_drink = 1" . 
            (!empty($whereStr) ? " AND " . substr($whereStr, 6) : "") . "
            ORDER BY c.name, mi.name
        ";
        
        $inventoryData = $db->fetchAll($query, $params);
        
        // Get summary counts
        $summary = [
            'totalItems' => count($inventoryData),
            'inStock' => count(array_filter($inventoryData, fn($item) => $item['stock_status'] === 'In Stock')),
            'lowStock' => count(array_filter($inventoryData, fn($item) => $item['stock_status'] === 'Low Stock')),
            'outOfStock' => count(array_filter($inventoryData, fn($item) => $item['stock_status'] === 'Out of Stock'))
        ];
        
        sendJsonResponse([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'items' => $inventoryData
            ]
        ]);
    }
} catch (Exception $e) {
    error_log("Report generation error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendJsonResponse([
        'success' => false,
        'message' => 'Error generating report: ' . $e->getMessage()
    ], 500);
} catch (Error $e) {
    error_log("Fatal error in report generation: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendJsonResponse([
        'success' => false,
        'message' => 'Internal server error while generating report'
    ], 500);
}