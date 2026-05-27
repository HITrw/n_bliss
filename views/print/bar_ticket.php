<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

if (!isset($_SESSION['waiter_id'])) { die('Not authorized'); }

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$order_id) { die('Invalid order'); }

$db = Database::getInstance();

$order = $db->fetch(
    "SELECT order_number, created_at, waiter_name FROM orders WHERE id = ?",
    [$order_id]
);
if (!$order) { die('Order not found'); }

$waiter_name = $order['waiter_name'] ?: ($_SESSION['waiter_name'] ?? 'N/A');

// BAR TICKET: all drinks that are NOT coffee/juice/kitchen
// Excludes: coffee (24,25,26), juice (27,28,29,30), food (parent=12 or id=12), sides (parent=20 or id=20)
$items = $db->fetchAll("
    SELECT oi.quantity, mi.name, c.name as category_name
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN categories c ON mi.category_id = c.id
    WHERE oi.order_id = ?
      AND mi.category_id NOT IN (24, 25, 26, 27, 28, 29, 30)
      AND c.id NOT IN (12, 20)
      AND (c.parent_id NOT IN (12, 20) OR c.parent_id IS NULL)
    ORDER BY c.name, mi.name
", [$order_id]);

if (empty($items)) { exit; }

$grouped = [];
foreach ($items as $item) {
    $grouped[$item['category_name']][] = $item;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BAR - Order #<?= htmlspecialchars($order['order_number']) ?></title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 0; padding: 20px; font-size: 18px; width: 80mm; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
        .order-info { margin-bottom: 20px; }
        .category { font-weight: bold; padding-top: 10px; border-top: 1px dashed #000; }
        .item { display: flex; justify-content: space-between; margin: 5px 0; }
        .item-details { flex-grow: 1; }
        .footer { text-align: center; border-top: 1px dashed #000; padding-top: 10px; font-size: 90%; }
        .print-wrapper { display: block; width: 100%; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            html, body { width: 80mm; margin: 0 !important; padding: 0 !important; }
            .footer { margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body>
<div class="print-wrapper">
    <div class="header">
        <h2>🍸 BAR ORDER</h2>
    </div>
    <div class="order-info">
        <p><strong>Order #:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
        <p><strong>Waiter:</strong> <?= htmlspecialchars($waiter_name) ?></p>
        <p><strong>Time:</strong> <?= date('Y-m-d H:i:s', strtotime($order['created_at'])) ?></p>
    </div>
    <div class="items">
        <?php foreach ($grouped as $category => $catItems): ?>
            <b><div class="category"><?= htmlspecialchars($category) ?></div></b>
            <?php foreach ($catItems as $item): ?>
                <div class="item">
                    <div class="item-details">
                        <strong><?= $item['quantity'] ?>x</strong> <?= htmlspecialchars($item['name']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <div class="footer">
        <p>*** BAR COPY ***</p>
        <p><?= date('Y-m-d H:i:s') ?></p>
    </div>
</div>
<script>
    window.onload = function() {
        window.print();
        setTimeout(function() { window.close(); }, 500);
    };
</script>
</body>
</html>
