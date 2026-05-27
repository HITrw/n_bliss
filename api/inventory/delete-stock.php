<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$itemId = intval($data['itemId'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

$db = Database::getInstance();

try {
    // Delete stock first (due to foreign key constraint)
    $db->query("DELETE FROM drink_stock WHERE menu_item_id = ?", [$itemId]);

    // Optionally delete from menu_items too (uncomment if needed)
    // $db->query("DELETE FROM menu_items WHERE id = ?", [$itemId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
