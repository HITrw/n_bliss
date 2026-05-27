<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

$db = Database::getInstance();

$orderId = $_POST['order_id'] ?? 0;
$quantities = $_POST['quantities'] ?? [];
$itemDiscounts = $_POST['item_discounts'] ?? []; // New: item-level discounts

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Validate item discount inputs
foreach ($itemDiscounts as $itemId => $discount) {
    if (!isset($discount['type']) || !isset($discount['value'])) {
        continue;
    }
    
    $discountType = $discount['type'];
    $discountValue = floatval($discount['value']);
    
    if ($discountType === 'percentage' && ($discountValue < 0 || $discountValue > 100)) {
        echo json_encode(['success' => false, 'message' => 'Percentage discount must be between 0 and 100']);
        exit;
    }
    
    if ($discountType === 'fixed' && $discountValue < 0) {
        echo json_encode(['success' => false, 'message' => 'Fixed discount cannot be negative']);
        exit;
    }
}

try {
    $db->beginTransaction();

    // Update quantities and item discounts
    if (!empty($quantities)) {
        foreach ($quantities as $itemId => $newQty) {
            $newQty = (int)$newQty;
            
            // Fetch the old quantity and item details
            $itemData = $db->fetch("SELECT oi.quantity AS old_qty, oi.menu_item_id, mi.is_drink, mi.price 
                                    FROM order_items oi 
                                    JOIN menu_items mi ON oi.menu_item_id = mi.id 
                                    WHERE oi.id = ? AND oi.order_id = ?", [$itemId, $orderId]);
            
            if (!$itemData) continue;
            
            $oldQty = (int)$itemData['old_qty'];
            $menuItemId = $itemData['menu_item_id'];
            $isDrink = (int)$itemData['is_drink'];
            $originalPrice = floatval($itemData['price']);
            
            // Handle item-level discount
            $itemDiscount = $itemDiscounts[$itemId] ?? null;
            $discountType = null;
            $discountValue = 0;
            $discountAmount = 0;
            $finalPrice = $originalPrice;
            
            if ($itemDiscount && $itemDiscount['type'] !== 'none' && $itemDiscount['value'] > 0) {
                $discountType = $itemDiscount['type'];
                $discountValue = floatval($itemDiscount['value']);
                
                if ($discountType === 'percentage') {
                    $discountAmount = ($originalPrice * $discountValue) / 100;
                } elseif ($discountType === 'fixed') {
                    $discountAmount = min($discountValue, $originalPrice); // Can't discount more than item price
                }
                
                $finalPrice = $originalPrice - $discountAmount;
            }
            
            // Update order_items with new quantity and discount info
            $db->execute("UPDATE order_items SET 
                            quantity = ?, 
                            original_price = ?, 
                            final_price = ?, 
                            discount_type = ?, 
                            discount_value = ?, 
                            discount_amount = ? 
                          WHERE id = ? AND order_id = ?", [
                $newQty, $originalPrice, $finalPrice, $discountType, $discountValue, $discountAmount, $itemId, $orderId
            ]);
            
            // If it's a drink, adjust stock
            if ($isDrink) {
                $diff = $newQty - $oldQty;
                $db->execute("UPDATE drink_stock SET quantity = quantity - ? WHERE menu_item_id = ?", [
                    $diff, $menuItemId
                ]);
            }
        }
    }

    // Calculate new totals based on item-level pricing
    $orderItems = $db->fetchAll("
        SELECT 
            oi.quantity, 
            CASE 
                WHEN oi.final_price IS NOT NULL THEN oi.final_price
                ELSE mi.price
            END as effective_price,
            CASE 
                WHEN oi.original_price IS NOT NULL THEN oi.original_price
                ELSE mi.price
            END as original_price,
            COALESCE(oi.discount_amount, 0) as item_discount_amount
        FROM order_items oi
        JOIN menu_items mi ON mi.id = oi.menu_item_id
        WHERE oi.order_id = ? AND oi.quantity > 0
    ", [$orderId]);

    $subtotal = 0;
    $total = 0;
    $totalDiscountAmount = 0;
    
    foreach ($orderItems as $item) {
        $itemSubtotal = $item['quantity'] * $item['original_price'];
        $itemTotal = $item['quantity'] * $item['effective_price'];
        $itemDiscountTotal = $item['quantity'] * $item['item_discount_amount'];
        
        $subtotal += $itemSubtotal;
        $total += $itemTotal;
        $totalDiscountAmount += $itemDiscountTotal;
    }

    // Update order with new totals
    $db->execute("UPDATE orders SET 
                    subtotal_amount = ?, 
                    total_amount = ?, 
                    discount_amount = ?,
                    discount_type = 'item_level',
                    discount_value = NULL
                  WHERE id = ?", [
        $subtotal, $total, $totalDiscountAmount, $orderId
    ]);

    // Log the discount application if applicable
    if ($totalDiscountAmount > 0) {
        $logMessage = "Item-level discounts applied. Total discount amount: " . number_format($totalDiscountAmount, 2);
        
        // You can add order log entry here if you have an order_logs table
        // $db->execute("INSERT INTO order_logs (order_id, action, description, created_at) VALUES (?, 'item_discount_applied', ?, NOW())", 
        //              [$orderId, $logMessage]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()]);
}
?>