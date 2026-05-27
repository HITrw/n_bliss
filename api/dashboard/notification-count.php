<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = Database::getInstance();

// Get unread notifications count
$count = $db->fetch(
    "SELECT COUNT(*) as count FROM notifications WHERE employee_id = ? AND is_read = 0",
    [$_SESSION['admin_id']]
)['count'];

echo json_encode(['count' => $count]);
