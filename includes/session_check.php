<?php
// Function to check waiter session validity
function checkTableSession() {
    if (!isset($_SESSION['waiter_id'])) {
        header("Location: table_login.php");
        exit;
    }
}
