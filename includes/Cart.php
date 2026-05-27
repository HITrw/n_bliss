<?php
class Cart {
    private $db;
    private $table_number; // stores waiter_id used as cart session key

    public function __construct($cart_key = null) {
        $this->db = Database::getInstance();
        $this->table_number = $cart_key ?? $_SESSION['waiter_id'] ?? null;
        
        if (!$this->table_number) {
            error_log("Cart: No cart key (waiter_id) provided");
            throw new Exception("No cart key provided");
        }
        error_log("Cart: Initialized for waiter " . $this->table_number);
    }

    public function addItem($menu_item_id, $quantity = 1) {
        error_log("Cart: Adding item $menu_item_id with quantity $quantity for table {$this->table_number}");
        
        // Input validation
        if (!is_numeric($menu_item_id) || !is_numeric($quantity)) {
            error_log("Cart: Invalid menu item ID or quantity");
            throw new Exception("Invalid menu item ID or quantity");
        }

        if ($quantity <= 0) {
            error_log("Cart: Invalid quantity: $quantity");
            throw new Exception("Quantity must be greater than 0");
        }

        // Start transaction before any DB operations
        $this->db->beginTransaction();

        try {
            // Validate menu item exists and is active - within transaction
            $menuItem = $this->db->fetch(
                "SELECT id, name, price FROM menu_items WHERE id = ? AND is_active = 1 FOR UPDATE",
                [$menu_item_id]
            );

            if (!$menuItem) {
                $this->db->rollback();
                error_log("Cart: Menu item not found or not active: $menu_item_id");
                throw new Exception("Menu item not found or is not available");
            }

            error_log("Cart: Found menu item: " . json_encode($menuItem));

            // Check if item already exists in cart - within transaction
            $existingItem = $this->db->fetch(
                "SELECT id, quantity FROM cart WHERE table_number = ? AND menu_item_id = ? FOR UPDATE",
                [$this->table_number, $menu_item_id]
            );

            error_log("Cart: Existing item check result: " . json_encode($existingItem));

            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                error_log("Cart: Updating quantity to $newQuantity");
                
                $this->db->execute(
                    "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE table_number = ? AND menu_item_id = ?",
                    [$newQuantity, $this->table_number, $menu_item_id]
                );
            } else {
                // Insert new item
                error_log("Cart: Inserting new item");
                
                $this->db->execute(
                    "INSERT INTO cart (table_number, menu_item_id, quantity, created_at, updated_at) 
                     VALUES (?, ?, ?, NOW(), NOW())",
                    [$this->table_number, $menu_item_id, $quantity]
                );
            }

            $this->db->commit();
            error_log("Cart: Successfully added/updated item");
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Cart: Error adding item: " . $e->getMessage());
            throw new Exception("Failed to add item to cart: " . $e->getMessage());
        }
    }

    public function updateQuantity($cart_id, $quantity) {
        // Input validation
        if (!is_numeric($cart_id) || !is_numeric($quantity)) {
            throw new Exception("Invalid cart ID or quantity");
        }

        // Verify cart item belongs to current table
        $cartItem = $this->db->fetch(
            "SELECT id FROM cart WHERE id = ? AND table_number = ?",
            [$cart_id, $this->table_number]
        );

        if (!$cartItem) {
            throw new Exception("Cart item not found");
        }

        if ($quantity <= 0) {
            return $this->removeItem($cart_id);
        }

        try {
            $this->db->execute(
                "UPDATE cart SET quantity = ?, updated_at = NOW() 
                 WHERE id = ? AND table_number = ?",
                [$quantity, $cart_id, $this->table_number]
            );
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to update cart: " . $e->getMessage());
        }
    }

    public function removeItem($cart_id) {
        // Input validation
        if (!is_numeric($cart_id)) {
            throw new Exception("Invalid cart ID");
        }

        // Verify cart item belongs to current table
        $cartItem = $this->db->fetch(
            "SELECT id FROM cart WHERE id = ? AND table_number = ?",
            [$cart_id, $this->table_number]
        );

        if (!$cartItem) {
            throw new Exception("Cart item not found");
        }

        try {
            $this->db->execute(
                "DELETE FROM cart WHERE id = ? AND table_number = ?",
                [$cart_id, $this->table_number]
            );
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to remove item: " . $e->getMessage());
        }
    }

    public function clear() {
        try {
            $this->db->execute(
                "DELETE FROM cart WHERE table_number = ?",
                [$this->table_number]
            );
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to clear cart: " . $e->getMessage());
        }
    }

    public function getItems() {
        error_log("Cart: Getting items for table " . $this->table_number);
        $items = $this->db->fetchAll(
            "SELECT c.*, m.name, m.price, m.image_path 
             FROM cart c 
             INNER JOIN menu_items m ON m.id = c.menu_item_id 
             WHERE c.table_number = ?
             ORDER BY c.created_at DESC",
            [$this->table_number]
        );
        error_log("Cart: Found " . count($items) . " items");
        return $items;
    }

    public function getTotal() {
        $result = $this->db->fetch(
            "SELECT COALESCE(SUM(c.quantity * m.price), 0) as total 
             FROM cart c 
             INNER JOIN menu_items m ON m.id = c.menu_item_id 
             WHERE c.table_number = ?",
            [$this->table_number]
        );
        $total = $result ? floatval($result['total']) : 0;
        error_log("Cart: Total for table {$this->table_number}: $total");
        return $total;
    }

    public function getCount() {
        $result = $this->db->fetch(
            "SELECT COALESCE(SUM(quantity), 0) as count 
             FROM cart 
             WHERE table_number = ?",
            [$this->table_number]
        );
        $count = $result ? intval($result['count']) : 0;
        error_log("Cart: Count for table {$this->table_number}: $count");
        return $count;
    }
}
