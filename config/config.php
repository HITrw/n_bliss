<?php
session_start();

// Base URL configuration
define('BASE_URL', 'https://nsblisslounge.com');
define('ADMIN_URL', BASE_URL . '/admin');

// File upload paths
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/bliss/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('Africa/Kigali');

// Log session info for debugging
error_log("Session status: " . print_r($_SESSION, true));

// Site settings
define('SITE_NAME', "N'S Bliss LOUNGE");
define('CURRENCY', 'RWF');

// Security
define('HASH_SALT', 'bliss_restaurant_' . date('Y'));

// Cart session key
define('CART_SESSION_KEY', 'bliss_cart');

// Image settings
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Order status
define('ORDER_STATUS', [
    'PENDING' => 'Pending',
    'APPROVED' => 'Approved',
    'IN_PROGRESS' => 'In Progress',
    'READY' => 'Ready',
    'SERVED' => 'Served',
    'COMPLETED' => 'Completed',
    'CANCELLED' => 'Cancelled'
]);

// Function to sanitize output
function h($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>
