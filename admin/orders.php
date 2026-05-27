<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$pageTitle = 'Orders';
$currentPage = 'orders';

// Get database instance
$db = Database::getInstance();

// Get filters from query string
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : (new DateTime())->format('Y-m-d');

// Build query conditions
$conditions = ['1=1'];
$params = [];

if ($status) {
    $conditions[] = 'o.status = ?';
    $params[] = $status;
}

if ($date) {
    $conditions[] = 'DATE(o.created_at) = ?';
    $params[] = $date;
}

// Build the WHERE clause from conditions
$whereClause = implode(' AND ', $conditions);

// Get orders with related data
$orders = $db->fetchAll("
    SELECT 
        o.*, 
        t.table_number,
        e.name as user_name,
        (
            SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', mi.name) SEPARATOR '\n')
            FROM order_items oi
            LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = o.id
        ) as items_summary,
        COUNT(o2.id) as mergeable_orders
    FROM orders o
    LEFT JOIN tables t ON o.table_number = t.table_number
    LEFT JOIN employees e ON o.employee_id = e.id
    LEFT JOIN orders o2 ON o2.table_number = o.table_number 
        AND o2.id != o.id 
        AND o2.status = 'pending'
    WHERE {$whereClause}
    GROUP BY o.id
    ORDER BY o.created_at DESC
", $params);

$employees = $db->fetchAll("SELECT id, name FROM employees WHERE is_active = 1 AND role = 'staff' ORDER BY name");

include 'header.php';
?>
<div class="container-fluid py-4">
    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?= h($date) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach (ORDER_STATUS as $key => $label): ?>
                            <option value="<?= strtolower($key) ?>" <?= $status === strtolower($key) ? 'selected' : '' ?>>
                                <?= h($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Table</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Waiter(ess)</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= h($order['order_number']) ?></td>
                                <td><?= h($order['table_number']) ?></td>
                                <td style="white-space: pre-line"><?= h($order['items_summary']) ?></td>
                                <td>
                                    <?php 
                                    $displayTotal = $order['total_amount'];
                                    if ($order['discount_amount'] > 0) {
                                        $originalTotal = $order['total_amount'] + $order['discount_amount'];
                                        echo '<span class="text-decoration-line-through text-muted small">' . number_format($originalTotal, 2) . '</span><br>';
                                        echo '<strong>' . number_format($displayTotal, 2) . '</strong>';
                                        if ($order['discount_type'] === 'percentage') {
                                            echo ' <small class="text-success">(-' . $order['discount_value'] . '%)</small>';
                                        } else {
                                            echo ' <small class="text-success">(-' . number_format($order['discount_amount'], 2) . ')</small>';
                                        }
                                    } else {
                                        echo number_format($displayTotal, 2);
                                    }
                                    ?> <?= CURRENCY ?>
                                </td>
                                <td>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <select class="form-select form-select-sm assign-employee" 
                                                data-order-id="<?= $order['id'] ?>">
                                            <option value="">Assign Server</option>
                                            <?php foreach ($employees as $employee): ?>
                                                <option value="<?= $employee['id'] ?>" 
                                                    <?= $order['employee_id'] == $employee['id'] ? 'selected' : '' ?>>
                                                    <?= h($employee['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <?= h($order['user_name']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                        <?= h(ucfirst($order['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($order['status'] !== 'completed'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning edit-items"
                                                data-order-id="<?= $order['id'] ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editItemsModal"
                                                title="Edit Items">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-sm btn-outline-secondary print-kitchen"
                                                data-order-id="<?= $order['id'] ?>" title="Print Kitchen Order">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary print-receipt"
                                                data-order-id="<?= $order['id'] ?>" title="Print Receipt">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <?php if ($order['status'] !== 'completed'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success update-status"
                                                    data-order-id="<?= $order['id'] ?>" 
                                                    data-action="next"
                                                    title="<?= $order['status'] === 'pending' ? 'Approve Order' : 'Move to Next Status' ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['admin_role'] === 'admin' && $order['status'] !== 'completed'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger update-status"
                                                data-order-id="<?= $order['id'] ?>"
                                                data-action="cancel"
                                                title="Cancel Order">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($order['status'] === 'pending' && $order['mergeable_orders'] > 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info merge-orders"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#mergeOrdersModal"
                                                    data-order-id="<?= $order['id'] ?>"
                                                    data-table-number="<?= $order['table_number'] ?>"
                                                    title="Merge Table Orders">
                                                <i class="fas fa-object-group"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Edit Items Modal with Item-Level Discount -->
<div class="modal fade" id="editItemsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="editItemsForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Order Items & Discounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Items Section -->
                    <div class="mb-4">
                        <h6 class="mb-3">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Original Price</th>
                                        <th>Quantity</th>
                                        <th>Discount Type</th>
                                        <th>Discount Value</th>
                                        <th>Final Price</th>
                                        <th>Item Total</th>
                                    </tr>
                                </thead>
                                <tbody id="editItemsBody">
                                    <!-- Items will be dynamically injected here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Total Preview -->
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Subtotal (Original):</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span id="subtotalAmount">0.00</span> <?= CURRENCY ?>
                                </div>
                            </div>
                            <div class="row" id="discountRow">
                                <div class="col-6">
                                    <strong class="text-success">Total Discount:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="text-success" id="discountAmount">-0.00</span> <?= CURRENCY ?>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Final Total:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <strong id="totalAmount">0.00</strong> <?= CURRENCY ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="order_id" id="editOrderId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let orderItems = [];
    let currentOrderData = {};

    // ... (keep existing code for server assignment, status updates, printing) ...

    // Handle edit items modal
    document.querySelectorAll('.edit-items').forEach(button => {
        button.addEventListener('click', async function () {
            const orderId = this.dataset.orderId;
            document.getElementById('editOrderId').value = orderId;

            const response = await fetch(`../api/orders/get-items.php?order_id=${orderId}`);
            const data = await response.json();

            if (data.success) {
                orderItems = data.items;
                currentOrderData = data.order;
                
                // Populate items table
                const container = document.getElementById('editItemsBody');
                container.innerHTML = '';
                
                orderItems.forEach(item => {
                    const originalPrice = item.current_original_price || item.menu_price;
                    const finalPrice = item.current_final_price || item.menu_price;
                    const discountType = item.discount_type || 'none';
                    const discountValue = item.discount_value || 0;
                    
                    container.innerHTML += `
                        <tr>
                            <td><strong>${item.name}</strong></td>
                            <td>
                                <span class="original-price">${parseFloat(originalPrice).toFixed(2)}</span> <?= CURRENCY ?>
                            </td>
                            <td>
                                <input type="number" name="quantities[${item.id}]" 
                                       class="form-control form-control-sm quantity-input" 
                                       min="0" value="${item.quantity}" 
                                       data-item-id="${item.id}" 
                                       data-original-price="${originalPrice}">
                            </td>
                            <td>
                                <select name="item_discounts[${item.id}][type]" 
                                        class="form-select form-select-sm discount-type-select"
                                        data-item-id="${item.id}">
                                    <option value="none" ${discountType === 'none' ? 'selected' : ''}>No Discount</option>
                                    <option value="percentage" ${discountType === 'percentage' ? 'selected' : ''}>Percentage (%)</option>
                                    <option value="fixed" ${discountType === 'fixed' ? 'selected' : ''}>Fixed Amount</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="item_discounts[${item.id}][value]" 
                                       class="form-control form-control-sm discount-value-input" 
                                       min="0" step="0.01" value="${discountValue}"
                                       data-item-id="${item.id}"
                                       ${discountType === 'none' ? 'disabled' : ''}>
                            </td>
                            <td>
                                <span class="final-price" data-item-id="${item.id}">
                                    ${parseFloat(finalPrice).toFixed(2)}
                                </span> <?= CURRENCY ?>
                            </td>
                            <td>
                                <strong class="item-total" data-item-id="${item.id}">
                                    ${(parseFloat(finalPrice) * parseInt(item.quantity)).toFixed(2)}
                                </strong> <?= CURRENCY ?>
                            </td>
                        </tr>
                    `;
                });

                // Add event listeners
                addItemDiscountEventListeners();
                updateTotalPreview();
            } else {
                document.getElementById('editItemsBody').innerHTML = 
                    `<tr><td colspan="7"><div class="alert alert-danger">${data.message}</div></td></tr>`;
            }
        });
    });

    function addItemDiscountEventListeners() {
        // Quantity change listeners
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', function() {
                updateItemCalculations(this.dataset.itemId);
                updateTotalPreview();
            });
        });

        // Discount type change listeners
        document.querySelectorAll('.discount-type-select').forEach(select => {
            select.addEventListener('change', function() {
                const itemId = this.dataset.itemId;
                const discountValueInput = document.querySelector(`input[data-item-id="${itemId}"].discount-value-input`);
                
                if (this.value === 'none') {
                    discountValueInput.disabled = true;
                    discountValueInput.value = 0;
                } else {
                    discountValueInput.disabled = false;
                    if (this.value === 'percentage') {
                        discountValueInput.max = 100;
                    } else {
                        discountValueInput.removeAttribute('max');
                    }
                }
                
                updateItemCalculations(itemId);
                updateTotalPreview();
            });
        });

        // Discount value change listeners
        document.querySelectorAll('.discount-value-input').forEach(input => {
            input.addEventListener('input', function() {
                updateItemCalculations(this.dataset.itemId);
                updateTotalPreview();
            });
        });
    }

    function updateItemCalculations(itemId) {
        const quantityInput = document.querySelector(`input[data-item-id="${itemId}"].quantity-input`);
        const discountTypeSelect = document.querySelector(`select[data-item-id="${itemId}"]`);
        const discountValueInput = document.querySelector(`input[data-item-id="${itemId}"].discount-value-input`);
        const finalPriceSpan = document.querySelector(`span[data-item-id="${itemId}"].final-price`);
        const itemTotalSpan = document.querySelector(`span[data-item-id="${itemId}"].item-total`);

        const quantity = parseInt(quantityInput.value) || 0;
        const originalPrice = parseFloat(quantityInput.dataset.originalPrice) || 0;
        const discountType = discountTypeSelect.value;
        const discountValue = parseFloat(discountValueInput.value) || 0;

        let discountAmount = 0;
        if (discountType === 'percentage') {
            discountAmount = (originalPrice * discountValue) / 100;
        } else if (discountType === 'fixed') {
            discountAmount = Math.min(discountValue, originalPrice);
        }

        const finalPrice = originalPrice - discountAmount;
        const itemTotal = finalPrice * quantity;

        finalPriceSpan.textContent = finalPrice.toFixed(2);
        itemTotalSpan.textContent = itemTotal.toFixed(2);
    }

    function updateTotalPreview() {
        let subtotal = 0;
        let total = 0;
        let totalDiscount = 0;

        document.querySelectorAll('.quantity-input').forEach(input => {
            const itemId = input.dataset.itemId;
            const quantity = parseInt(input.value) || 0;
            const originalPrice = parseFloat(input.dataset.originalPrice) || 0;
            const finalPriceSpan = document.querySelector(`span[data-item-id="${itemId}"].final-price`);
            const finalPrice = parseFloat(finalPriceSpan.textContent) || 0;

            const itemSubtotal = quantity * originalPrice;
            const itemTotal = quantity * finalPrice;
            const itemDiscount = itemSubtotal - itemTotal;

            subtotal += itemSubtotal;
            total += itemTotal;
            totalDiscount += itemDiscount;
        });

        document.getElementById('subtotalAmount').textContent = subtotal.toFixed(2);
        document.getElementById('discountAmount').textContent = '-' + totalDiscount.toFixed(2);
        document.getElementById('totalAmount').textContent = total.toFixed(2);

        // Show/hide discount row
        const discountRow = document.getElementById('discountRow');
        if (totalDiscount > 0) {
            discountRow.style.display = 'block';
        } else {
            discountRow.style.display = 'none';
        }
    }

    // Handle form submission
    document.getElementById('editItemsForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        const response = await fetch('../api/orders/update-items.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to update order.');
        }
    });
});
</script>

                <!-- Merge Orders Modal -->
                <div class="modal fade" id="mergeOrdersModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Merge Table Orders</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Select orders to merge:</p>
                                <div id="mergeable-orders-list"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmMerge">Merge Orders</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add Debug Information
function getStatusColor($status) {
    return [
        'pending' => 'warning',
        'approved' => 'info',
        'preparing' => 'primary',
        'ready' => 'success',
        'served' => 'secondary',
        'completed' => 'dark',
        'cancelled' => 'danger'
    ][$status] ?? 'secondary';
}

// Add JavaScript
?>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Handle server assignment
    document.querySelectorAll('.assign-employee').forEach(select => {
        select.addEventListener('change', async function() {
            try {
                const orderId = this.dataset.orderId;
                const employeeId = this.value;

                if (!employeeId) {
                    return;
                }

                const response = await fetch('../api/orders/update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        orderId: orderId,
                        employeeId: employeeId,
                        action: 'assign'
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to assign server');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message);
                location.reload();
            }
        });
    });

    // Handle status updates
    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', async function() {
            const orderId = this.dataset.orderId;
            const action = this.dataset.action;

            if (!confirm('Are you sure you want to update this order status?')) {
                return;
            }

            try {
                const response = await fetch('../api/orders/update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        orderId: orderId,
                        action: action
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message);
            }
        });
    });

    // Handle printing
    document.querySelectorAll('.print-kitchen, .print-receipt').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const type = this.classList.contains('print-kitchen') ? 'kitchen' : 'receipt';
            
            window.open(`../api/orders/print.php?id=${orderId}&type=${type}`, '_blank');
        });
    });
});
</script>
<script src="../assets/js/merge.js"></script>
<?php include 'footer.php'; ?>