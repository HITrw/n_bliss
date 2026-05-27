<?php
require_once 'config/config.php';

// Clear waiter session and destroy
session_destroy();

// Redirect to cashier login page
header("Location: admin/table_login.php");
exit;
