<?php
require_once 'config/config.php';

// Clear waiter session and destroy
session_destroy();

// Redirect to login page
header("Location: table_login.php");
exit;
