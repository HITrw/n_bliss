<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = Database::getInstance();

// Get pending orders count
$count = $db->fetch(
    "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'"
)['count'];

echo json_encode(['count' => $count]);
