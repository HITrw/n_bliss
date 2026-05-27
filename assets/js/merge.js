// Handle merge modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const mergeModal = document.getElementById('mergeOrdersModal');
    if (!mergeModal) return;

    function showDebugInfo(info) {
        const debugDiv = document.getElementById('debug-info');
        if (debugDiv) {
            debugDiv.style.display = 'block';
            debugDiv.innerHTML = `<strong>Debug Info:</strong><pre>${JSON.stringify(info, null, 2)}</pre>`;
        }
    }

    // Handle modal show event
    mergeModal.addEventListener('show.bs.modal', function (event) {
        console.log('Merge modal showing...');
        const button = event.relatedTarget;
        const orderId = button.getAttribute('data-order-id');
        const tableNumber = button.getAttribute('data-table-number');
        
        console.log(`Loading merge data for Order: ${orderId}, Table: ${tableNumber}`);
        
        // Clear previous content and show loading
        const ordersList = document.getElementById('mergeable-orders-list');
        ordersList.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Update modal title
        const modalTitle = mergeModal.querySelector('.modal-title');
        modalTitle.textContent = `Merge Orders - Table ${tableNumber}`;
        
        // Store the main order ID
        document.getElementById('confirmMerge').setAttribute('data-main-order-id', orderId);
          // Show debug info for request
        showDebugInfo({
            type: 'request',
            orderId,
            tableNumber,
            url: `../api/orders/get-mergeable.php?orderId=${orderId}&tableNumber=${tableNumber}`
        });
        
        // Fetch mergeable orders
        fetch(`../api/orders/get-mergeable.php?orderId=${orderId}&tableNumber=${tableNumber}`)
            .then(response => response.json())
            .then(data => {
                console.log('Received data:', data);
                showDebugInfo(data);
                
                if (data.success) {
                    if (data.orders && data.orders.length > 0) {
                        ordersList.innerHTML = data.orders.map(order => `
                            <div class="form-check mb-2">
                                <input class="form-check-input mergeable-order" type="checkbox" 
                                       value="${order.id}" id="order-${order.id}">
                                <label class="form-check-label" for="order-${order.id}">
                                    <strong>Order #${order.order_number}</strong><br>
                                    <small class="text-muted">${order.items_summary ? order.items_summary.replace(/\n/g, '<br>') : 'No items'}</small>
                                </label>
                            </div>
                        `).join('');
                    } else {
                        ordersList.innerHTML = `
                            <div class="alert alert-info">
                                No orders available for merging<br>
                                <small class="text-muted">Make sure there are other pending orders for table ${tableNumber}</small>
                            </div>`;
                    }
                } else {
                    ordersList.innerHTML = `
                        <div class="alert alert-danger">
                            ${data.message || 'Failed to fetch mergeable orders'}<br>
                            <small class="text-muted">Please try again or contact support if the problem persists</small>
                        </div>`;
                }
            })
            .catch(error => {
                console.error('Error fetching mergeable orders:', error);
                ordersList.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to fetch mergeable orders<br>
                        <small class="text-muted">Error: ${error.message}</small>
                    </div>`;
            });
    });

    // Handle merge confirmation
    document.getElementById('confirmMerge')?.addEventListener('click', function() {
        const mainOrderId = this.getAttribute('data-main-order-id');
        const selectedOrders = Array.from(document.querySelectorAll('.mergeable-order:checked'))
            .map(checkbox => checkbox.value);

        if (selectedOrders.length === 0) {
            alert('Please select orders to merge');
            return;
        }

        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'modal-overlay';
        loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Processing merge...</span></div>';
        document.body.appendChild(loadingOverlay);

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
            document.body.removeChild(loadingOverlay);
            if (data.success) {
                alert('Orders merged successfully');
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to merge orders');
            }
        })
        .catch(error => {
            document.body.removeChild(loadingOverlay);
            console.error('Error merging orders:', error);
            alert('Error: ' + error.message);
        });
    });
});
