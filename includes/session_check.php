<?php
require_once 'includes/TableManager.php';

// Function to check table session validity
function checkTableSession() {
    if (!isset($_SESSION['table_number'])) {
        header("Location: table_login.php");
        exit;
    }
    
    // Check if session has expired
    $lastActivity = $_SESSION['table_last_activity'] ?? 0;
    $tableManager = TableManager::getInstance();
    
    if (time() - $lastActivity > 1800) { // 30 minutes
        // Session expired
        $tableManager->releaseTable($_SESSION['table_number']);
        unset($_SESSION['table_number'], $_SESSION['table_last_activity']);
        header("Location: table_login.php");
        exit;
    }
    
    // Update last activity
    $_SESSION['table_last_activity'] = time();
    $tableManager->updateTableActivity($_SESSION['table_number']);
}
