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
        o.waiter_name as user_name,
        e.name as approver_name,
        (
            SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', mi.name) SEPARATOR '\n')
            FROM order_items oi
            LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = o.id
        ) as items_summary
    FROM orders o
    LEFT JOIN employees e ON o.employee_id = e.id
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
                            <th>Waiter / Approver</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= h($order['order_number']) ?></td>
                                <td>
                                    <?= h($order['user_name'] ?: 'N/A') ?>
                                    <?php if ($order['approver_name']): ?>
                                        <br><small class="text-muted">Approved by: <?= h($order['approver_name']) ?></small>
                                    <?php endif; ?>
                                </td>
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

                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#printModal" data-order-id="<?= $order['id'] ?>" title="Print Tickets">
                                            <i class="fas fa-print"></i>
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

                <!-- Merge Orders Modal (Removed) -->
            </div>
        </div>
    </div>
</div>

<!-- Print Tickets Modal -->
<div class="modal fade" id="printModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Tickets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-grid gap-2">
                <input type="hidden" id="printOrderId" value="">
                <button type="button" class="btn btn-outline-primary modal-print-btn" data-type="receipt"><i class="fas fa-receipt me-2"></i>Client Receipt</button>
                <button type="button" class="btn btn-outline-secondary modal-print-btn" data-type="coffee_ticket"><i class="fas fa-coffee me-2"></i>Coffee Ticket</button>
                <button type="button" class="btn btn-outline-secondary modal-print-btn" data-type="juice_ticket"><i class="fas fa-glass-water me-2"></i>Juice Ticket</button>
                <button type="button" class="btn btn-outline-secondary modal-print-btn" data-type="bar_ticket"><i class="fas fa-beer me-2"></i>Bar Ticket</button>
                <button type="button" class="btn btn-outline-secondary modal-print-btn" data-type="kitchen_ticket"><i class="fas fa-utensils me-2"></i>Kitchen Ticket</button>
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

    // Set order ID and filter tickets in print modal
    const printModal = document.getElementById('printModal');
    if (printModal) {
        printModal.addEventListener('show.bs.modal', async function(event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-order-id');
            document.getElementById('printOrderId').value = orderId;
            
            // Hide all specific ticket buttons initially (except receipt)
            const btnCoffee = document.querySelector('.modal-print-btn[data-type="coffee_ticket"]');
            const btnJuice = document.querySelector('.modal-print-btn[data-type="juice_ticket"]');
            const btnBar = document.querySelector('.modal-print-btn[data-type="bar_ticket"]');
            const btnKitchen = document.querySelector('.modal-print-btn[data-type="kitchen_ticket"]');
            
            btnCoffee.style.display = 'none';
            btnJuice.style.display = 'none';
            btnBar.style.display = 'none';
            btnKitchen.style.display = 'none';
            
            try {
                const response = await fetch(`../api/orders/get-items.php?order_id=${orderId}`);
                const data = await response.json();
                
                if (data.success && data.items) {
                    let hasCoffee = false;
                    let hasJuice = false;
                    let hasBar = false;
                    let hasKitchen = false;
                    
                    data.items.forEach(item => {
                        const catName = (item.category_name || '').toLowerCase();
                        const isDrink = item.is_drink == 1;
                        
                        if (catName.includes('coffee') || catName.includes('tea') || catName.includes('hot')) {
                            hasCoffee = true;
                        } else if (catName.includes('juice') || catName.includes('smoothie')) {
                            hasJuice = true;
                        } else if (isDrink) {
                            hasBar = true;
                        } else {
                            hasKitchen = true;
                        }
                    });
                    
                    if (hasCoffee) btnCoffee.style.display = 'block';
                    if (hasJuice) btnJuice.style.display = 'block';
                    if (hasBar) btnBar.style.display = 'block';
                    if (hasKitchen) btnKitchen.style.display = 'block';
                }
            } catch (error) {
                console.error('Error fetching items for print modal:', error);
            }
        });
    }

    // Handle printing from modal
    document.querySelectorAll('.modal-print-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = document.getElementById('printOrderId').value;
            const type = this.dataset.type; // receipt, coffee_ticket, juice_ticket, bar_ticket, kitchen_ticket
            
            if (type === 'receipt') {
                window.open(`../api/orders/print.php?id=${orderId}&type=receipt`, '_blank');
            } else {
                window.open(`../views/print/${type}.php?order_id=${orderId}`, '_blank');
            }
            
            // Close the modal
            bootstrap.Modal.getInstance(printModal).hide();
        });
    });
});
</script>
<?php include 'footer.php'; ?>