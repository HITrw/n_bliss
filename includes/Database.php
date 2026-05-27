<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->pdo = $pdo;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        
        $this->query($sql, $values);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $values = array_values($data);
        
        $set = implode('=?,', $fields) . '=?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge($values, $whereParams);
        return $this->query($sql, $params);
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }

    public function beginTransaction() {
        error_log("Database: Beginning transaction");
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        error_log("Database: Committing transaction");
        return $this->pdo->commit();
    }

    public function rollback() {
        error_log("Database: Rolling back transaction");
        return $this->pdo->rollBack();
    }

    public function execute($sql, $params = []) {
        error_log("Database: Executing query: $sql");
        error_log("Database: Parameters: " . json_encode($params));
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        error_log("Database: Query result: " . ($result ? "success" : "failure"));
        return $result;
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
?>
