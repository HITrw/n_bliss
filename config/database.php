<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'nsblqjkh');
define('DB_PASS', '^j^528dv6MJSk9#');
define('DB_NAME', 'nsblqjkh_bliss_restaurant');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    $pdo->exec("SET time_zone = '+02:00'");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
