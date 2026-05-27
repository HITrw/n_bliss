<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/TableManager.php';

// Check if request is from admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

try {
    $tableManager = TableManager::getInstance();
    $cleanedCount = $tableManager->cleanupAbandonedSessions();
    
    echo json_encode([
        'success' => true,
        'message' => "Cleaned up $cleanedCount abandoned table(s)",
        'cleaned_count' => $cleanedCount
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clean up tables: ' . $e->getMessage()
    ]);
}
