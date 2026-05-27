<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class TableManager {
    private static $instance = null;
    private $db;
    private $sessionTimeout = 86400; // 10 minutes in seconds
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
      public function isTableAvailable($tableNumber) {
        $table = $this->db->fetch(
            "SELECT status FROM tables WHERE table_number = ?",
            [$tableNumber]
        );
        return $table && $table['status'] === 'available';
    }    public function occupyTable($tableNumber) {
        // Update table status and set last activity
        return $this->db->query(
            "UPDATE tables SET 
                status = 'available',
                updated_at = NOW()
            WHERE table_number = ? AND status = 'available'",
            [$tableNumber]
        );
    }    public function releaseTable($tableNumber) {
        // Clear cart items first
        $this->db->query(
            "DELETE FROM cart WHERE table_number = ?",
            [$tableNumber]
        );
        
        // Update table status
        return $this->db->query(
            "UPDATE tables SET 
                status = 'available',
                updated_at = NOW()
            WHERE table_number = ?",
            [$tableNumber]
        );
    }
    
    public function cleanupAbandonedSessions() {
        // Find tables that haven't had activity in the timeout period
        $abandoned = $this->db->fetchAll(
            "SELECT table_number 
             FROM tables 
             WHERE status = 'occupied' 
             AND updated_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$this->sessionTimeout]
        );
        
        foreach ($abandoned as $table) {
            $this->releaseTable($table['table_number']);
        }
        
        return count($abandoned);
    }    public function updateTableActivity($tableNumber) {
        return $this->db->query(
            "UPDATE tables SET updated_at = NOW() 
             WHERE table_number = ? AND status = 'occupied'",
            [$tableNumber]
        );
    }
}
