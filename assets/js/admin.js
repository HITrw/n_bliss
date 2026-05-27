document.addEventListener('DOMContentLoaded', function() {
    // Initialize Charts for Dashboard
    initializeDashboardCharts();    // Setup Order Management
    setupOrderManagement();

    // Setup Menu Management
    setupMenuManagement();

    // Setup Stock Management
    setupStockManagement();

    // Setup Notifications
    setupNotifications();
});

// Dashboard Charts
function initializeDashboardCharts() {
    // Sales Chart
    const salesChartCtx = document.getElementById('salesChart');
    if (salesChartCtx) {
        new Chart(salesChartCtx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Sales',
                    borderColor: '#6f42c1',
                    tension: 0.3,
                    fill: true,
                    backgroundColor: 'rgba(111, 66, 193, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Load sales data
        updateSalesChart();
        // Refresh every 5 minutes
        setInterval(updateSalesChart, 300000);
    }

    // Orders Chart
    const ordersChartCtx = document.getElementById('ordersChart');
    if (ordersChartCtx) {
        new Chart(ordersChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Cancelled'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Load orders data
        updateOrdersChart();
        // Refresh every minute
        setInterval(updateOrdersChart, 60000);
    }

    // Dashboard Chart Initialization
    const ordersChart = document.getElementById('ordersChart');
    if (!ordersChart) return;

    new Chart(ordersChart, {
        type: 'line',
        data: {
            labels: Array.from({length: 24}, (_, i) => i + ':00'),
            datasets: [{
                label: 'Orders',
                data: window.hourlyOrders || [],
                borderColor: getComputedStyle(document.documentElement)
                    .getPropertyValue('--bs-primary'),
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

// Update Sales Chart
function updateSalesChart() {
    fetch('../api/dashboard/stats.php?type=sales')
        .then(response => response.json())
        .then(data => {
            const chart = Chart.getChart('salesChart');
            if (chart) {
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = data.values;
                chart.update();
            }
        })
        .catch(error => console.error('Error updating sales chart:', error));
}

// Update Orders Chart
function updateOrdersChart() {
    fetch('../api/dashboard/stats.php?type=orders')
        .then(response => response.json())
        .then(data => {
            const chart = Chart.getChart('ordersChart');
            if (chart) {
                chart.data.datasets[0].data = [
                    data.completed,
                    data.pending,
                    data.cancelled
                ];
                chart.update();

                // Update stats cards
                updateStatsCards(data);
            }
        })
        .catch(error => console.error('Error updating orders chart:', error));
}

// Optimize real-time updates for charts
function updateDashboardCharts() {
    fetch('../api/dashboard/stats.php?type=charts')
        .then(response => response.json())
        .then((data) => {
            const ordersChart = Chart.getChart('ordersChart');
            if (ordersChart) {
                ordersChart.data.datasets[0].data = data.hourlyOrders;
                ordersChart.update();
            }

            const salesChart = Chart.getChart('salesChart');
            if (salesChart) {
                salesChart.data.labels = data.sales.labels;
                salesChart.data.datasets[0].data = data.sales.values;
                salesChart.update();
            }
        })
        .catch((error) => {
            console.error('Error updating dashboard charts:', error);
        });
}

setInterval(updateDashboardCharts, 60000);

// Update Stats Cards
function updateStatsCards(data) {
    const elements = {
        totalOrders: document.getElementById('totalOrders'),
        totalSales: document.getElementById('totalSales'),
        avgOrderValue: document.getElementById('avgOrderValue'),
        pendingOrders: document.getElementById('pendingOrders')
    };

    if (elements.totalOrders) {
        elements.totalOrders.textContent = data.total;
    }
    if (elements.totalSales) {
        elements.totalSales.textContent = formatCurrency(data.sales);
    }
    if (elements.avgOrderValue) {
        elements.avgOrderValue.textContent = formatCurrency(data.average);
    }
    if (elements.pendingOrders) {
        elements.pendingOrders.textContent = data.pending;
    }
}

// DataTables initialization moved to header.php

// Order Management
function setupOrderManagement() {
    console.log('Setting up order management...');  // Debug log
    
    // Handle merge modal show event
    const mergeModal = document.getElementById('mergeOrdersModal');
    if (mergeModal) {
        mergeModal.addEventListener('show.bs.modal', function (event) {
            console.log('Merge modal showing...');  // Debug log
            const button = event.relatedTarget;
            const orderId = button.dataset.orderId;
            const tableNumber = button.dataset.tableNumber;
            
            console.log('Order ID:', orderId, 'Table:', tableNumber);  // Debug log
            
            // Clear previous content
            const ordersList = document.getElementById('mergeable-orders-list');
            ordersList.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
              // Fetch mergeable orders
            console.log(`Fetching mergeable orders for Table ${tableNumber}, Order ${orderId}`);
            fetch(`../api/orders/get-mergeable.php?orderId=${orderId}&tableNumber=${tableNumber}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);  // Debug log
                    if (data.success) {
                        if (data.orders.length > 0) {
                        // Update modal title to include table number
                        const modalTitle = document.querySelector('#mergeOrdersModal .modal-title');
                        modalTitle.textContent = `Merge Orders - Table ${tableNumber}`;
                        
                        // Populate modal with mergeable orders
                        ordersList.innerHTML = data.orders.map(order => `
                            <div class="form-check mb-2">
                                <input class="form-check-input mergeable-order" type="checkbox" 
                                       value="${order.id}" id="order-${order.id}">
                                <label class="form-check-label" for="order-${order.id}">
                                    <strong>Order #${order.order_number}</strong><br>
                                    <small class="text-muted">${order.items_summary ? order.items_summary.replace(/\n/g, '<br>') : ''}</small>
                                </label>
                            </div>
                        `).join('');
                        
                        // Store main order ID for merging
                        document.getElementById('confirmMerge').dataset.mainOrderId = orderId;
                    } else {
                        ordersList.innerHTML = '<div class="alert alert-info">No orders available for merging</div>';
                    }
                }
                .catch(error => {
                    console.error('Error fetching mergeable orders:', error);
                    ordersList.innerHTML = '<div class="alert alert-danger">Failed to fetch mergeable orders</div>';
                });
        });
    }

    // Handle order status updates
    document.querySelectorAll('.update-order-status').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const status = this.dataset.status;
            
            updateOrderStatus(orderId, status)
                .then(() => {
                    showSuccess('Order status updated successfully');
                    setTimeout(() => location.reload(), 1000);
                })
                .catch(error => showError('Failed to update order status'));
        });
    });

    // Handle order printing
    document.querySelectorAll('.print-order').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const type = this.dataset.type;
            window.open(`../api/orders/print.php?id=${orderId}&type=${type}`, '_blank');
        });
    });    // Handle merge orders button click
    document.querySelectorAll('.merge-orders').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const tableNumber = this.dataset.tableNumber;
            
            // Clear previous content
            const ordersList = document.getElementById('mergeable-orders-list');
            ordersList.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Show modal immediately with loading state
            const modal = new bootstrap.Modal(document.getElementById('mergeOrdersModal'));
            modal.show();
            
            // Fetch mergeable orders
            fetch(`../api/orders/get-mergeable.php?orderId=${orderId}&tableNumber=${tableNumber}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.orders.length > 0) {
                        // Update modal title to include table number
                        const modalTitle = document.querySelector('#mergeOrdersModal .modal-title');
                        modalTitle.textContent = `Merge Orders - Table ${tableNumber}`;
                        
                        // Populate modal with mergeable orders
                        ordersList.innerHTML = data.orders.map(order => `
                            <div class="form-check mb-2">
                                <input class="form-check-input mergeable-order" type="checkbox" 
                                       value="${order.id}" id="order-${order.id}">
                                <label class="form-check-label" for="order-${order.id}">
                                    <strong>Order #${order.order_number}</strong><br>
                                    <small class="text-muted">${order.items_summary.replace(/\n/g, '<br>')}</small>
                                </label>
                            </div>
                        `).join('');
                        
                        // Store main order ID for merging
                        document.getElementById('confirmMerge').dataset.mainOrderId = orderId;
                    } else {
                        ordersList.innerHTML = '<div class="alert alert-info">No orders available for merging</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching mergeable orders:', error);
                    ordersList.innerHTML = '<div class="alert alert-danger">Failed to fetch mergeable orders</div>';
                });
        });
    });

    // Handle confirm merge button click
    document.getElementById('confirmMerge')?.addEventListener('click', function() {
        const mainOrderId = this.dataset.mainOrderId;
        const selectedOrders = Array.from(document.querySelectorAll('.mergeable-order:checked'))
            .map(checkbox => checkbox.value);

        if (selectedOrders.length === 0) {
            showError('Please select orders to merge');
            return;
        }

        showLoading();
        fetch('../api/orders/merge.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                mainOrderId: mainOrderId,
                orderIds: selectedOrders
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('mergeOrdersModal')).hide();
                showSuccess('Orders merged successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                showError(data.message || 'Failed to merge orders');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error merging orders:', error);
            showError('Failed to merge orders');
        });
    });
}

// Setup Notifications
function setupNotifications() {
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    if (notificationsDropdown) {
        notificationsDropdown.addEventListener('show.bs.dropdown', loadNotifications);
    }

    // Auto refresh notifications every minute
    setInterval(updateNotificationsBadge, 60000);
}

// Format Currency
function formatCurrency(amount) {
    const formatted = new Intl.NumberFormat('rw-RW', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
    return `RWF ${formatted}`;
}

// Show Success Message
function showSuccess(message) {
    const toast = createToast('Success', message, 'success');
    showToast(toast);
}

// Show Error Message
function showError(message) {
    const toast = createToast('Error', message, 'danger');
    showToast(toast);
}

// Create Toast
function createToast(title, message, type) {
    const toast = document.createElement('div');
    toast.className = `toast bg-${type} text-white`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="toast-header bg-${type} text-white">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    return toast;
}

// Show Toast
function showToast(toast) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Show Loading Spinner
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.body.appendChild(spinner);
}

// Hide Loading Spinner
function hideLoading() {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.remove();
    }
}

// Inventory Management
function checkInventory() {
    showLoading();
    fetch('../api/inventory/check.php')
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showSuccess('Inventory check completed successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                showError(data.message || 'Failed to check inventory');
            }
        })
        .catch(error => {
            hideLoading();
            showError('Error checking inventory');
            console.error('Error:', error);
        });
}

function restock(itemId) {
    const quantity = prompt('Enter quantity to add:');
    if (!quantity || isNaN(quantity) || quantity <= 0) {
        showError('Please enter a valid quantity');
        return;
    }

    showLoading();
    fetch('../api/inventory/update-stock.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            itemId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showSuccess('Stock updated successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            showError(data.message || 'Failed to update stock');
        }
    })
    .catch(error => {
        hideLoading();
        showError('Error updating stock');
        console.error('Error:', error);
    });
}

// Export Functions
function exportToPDF() {
    showLoading();
    fetch('../api/dashboard/export.php?format=pdf')
        .then(response => response.blob())
        .then(blob => {
            hideLoading();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dashboard-report-${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        })
        .catch(error => {
            hideLoading();
            showError('Error exporting to PDF');
            console.error('Error:', error);
        });
}

function exportToExcel() {
    showLoading();
    fetch('../api/dashboard/export.php?format=excel')
        .then(response => response.blob())
        .then(blob => {
            hideLoading();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dashboard-report-${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        })
        .catch(error => {
            hideLoading();
            showError('Error exporting to Excel');
            console.error('Error:', error);
        });
}