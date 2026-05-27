<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/TableManager.php';

if(isset($_SESSION['table_number'])) {
    $tableManager = TableManager::getInstance();
    
    // Release the table
    $tableManager->releaseTable($_SESSION['table_number']);
    
    // Clear all session data
    unset($_SESSION['table_number'], $_SESSION['table_last_activity']);
    session_destroy();
}

// Redirect to login page
header("Location: table_login.php");
exit;
