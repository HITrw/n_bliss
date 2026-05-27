<?php
$pageTitle = 'Inventory Management';
$currentPage = 'inventory';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once 'header.php';

// Initialize database connection
$db = Database::getInstance();

// Get all drinks with their stock information
$drinks = $db->fetchAll("
    SELECT 
        mi.id,
        mi.name,
        ds.quantity,
        ds.low_stock_threshold
    FROM menu_items mi
    JOIN drink_stock ds ON mi.id = ds.menu_item_id
    ORDER BY mi.name
");
?>

<div class="container">
    <h1 class="mb-4">Inventory Management</h1>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="inventoryTable">                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Current Stock</th>
                            <th>Low Stock Threshold</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drinks as $drink): ?>
                            <tr>
                                <td><?= htmlspecialchars($drink['name']) ?></td>
                                <td><?= htmlspecialchars($drink['quantity']) ?></td>
                                <td><?= htmlspecialchars($drink['low_stock_threshold']) ?></td>
                                <td>
                                    <?php if ($drink['quantity'] <= $drink['low_stock_threshold']): ?>
                                        <span class="badge bg-danger">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm update-stock" 
                                            data-item-id="<?= $drink['id'] ?>"
                                            data-item-name="<?= htmlspecialchars($drink['name']) ?>"
                                            data-current-stock="<?= $drink['quantity'] ?>"
                                            data-threshold="<?= $drink['low_stock_threshold'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-stock ms-2"
            data-item-id="<?= $drink['id'] ?>"
            data-item-name="<?= htmlspecialchars($drink['name']) ?>">
        <i class="fas fa-trash"></i>
    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">                <form id="updateStockForm">
                    <input type="hidden" id="itemId" name="itemId">
                    <div class="mb-3">
                        <label for="itemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="itemName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="currentStock" class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="currentStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="adjustQuantity" class="form-label">Adjust Quantity</label>
                        <input type="number" class="form-control" id="adjustQuantity" name="quantity" required>
                        <small class="form-text text-muted">Enter a positive number to add stock, negative to remove stock.</small>
                    </div>
                    <div class="mb-3">
                        <label for="threshold" class="form-label">Low Stock Threshold</label>
                        <input type="number" class="form-control" id="threshold" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveStock">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#inventoryTable').DataTable({
        pageLength: 1000,
        order: [[0, 'asc']]
    });

    // Handle Update Stock button click - Use event delegation to prevent multiple bindings
    $(document).on('click', '.update-stock', function() {
        const itemId = $(this).data('item-id');
        const itemName = $(this).data('item-name');
        const currentStock = $(this).data('current-stock');
        const threshold = $(this).data('threshold');

        $('#itemId').val(itemId);
        $('#itemName').val(itemName);
        $('#currentStock').val(currentStock);
        $('#threshold').val(threshold);
        $('#adjustQuantity').val('');

        $('#updateStockModal').modal('show');
    });

    // Handle Save Changes button click - Use .one() to ensure it only fires once
    $(document).on('click', '#saveStock', function() {
        // Prevent multiple clicks
        const $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);

        const itemId = $('#itemId').val();
        const quantity = parseInt($('#adjustQuantity').val());

        if (!quantity && quantity !== 0) {
            alert('Please enter a valid quantity adjustment');
            $btn.prop('disabled', false);
            return;
        }

        $.ajax({
            url: '../api/inventory/update-stock.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                itemId: itemId,
                quantity: quantity
            }),
            success: function(response) {
                if (response.success) {
                    // Trigger inventory check after update
                    $.post('../api/inventory/check.php')
                        .always(function() {
                            $('#updateStockModal').modal('hide');
                            location.reload();
                        });
                } else {
                    alert('Error updating stock: ' + response.message);
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Error updating stock. Please try again.');
                $btn.prop('disabled', false);
            }
        });
    });

    // Handle Delete Stock button click
    $(document).on('click', '.delete-stock', function() {
        const itemId = $(this).data('item-id');
        const itemName = $(this).data('item-name');

        if (confirm(`Are you sure you want to delete "${itemName}" from inventory?`)) {
            $.ajax({
                url: '../api/inventory/delete-stock.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ itemId: itemId }),
                success: function(response) {
                    if (response.success) {
                        alert('Item deleted successfully.');
                        location.reload();
                    } else {
                        alert('Error deleting item: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error deleting item. Please try again.');
                }
            });
        }
    });
});
</script>
