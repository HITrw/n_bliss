// Dashboard specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize real-time updates
    initializeEventSource();
    
    // Set up sound toggle
    setupSoundToggle();
    
    // Update stats every minute
    setInterval(updateDashboardStats, 60000);
    
    // Load notifications
    loadNotifications();
    
    // Refresh notifications every minute
    setInterval(loadNotifications, 60000);
});

// Sound toggle setup
function setupSoundToggle() {
    const soundToggle = document.getElementById('soundToggle');
    if (soundToggle) {
        const isSoundEnabled = localStorage.getItem('notificationSound') !== 'disabled';
        soundToggle.innerHTML = `<i class="fas fa-${isSoundEnabled ? 'volume-up' : 'volume-mute'}"></i>`;
        
        soundToggle.addEventListener('click', function() {
            const isSoundEnabled = localStorage.getItem('notificationSound') !== 'disabled';
            if (isSoundEnabled) {
                localStorage.setItem('notificationSound', 'disabled');
                this.innerHTML = '<i class="fas fa-volume-mute"></i>';
                showNotification('Settings', 'Notification sound disabled');
            } else {
                localStorage.removeItem('notificationSound');
                this.innerHTML = '<i class="fas fa-volume-up"></i>';
                showNotification('Settings', 'Notification sound enabled');
                playNotificationSound();
            }
        });
    }
}

// Play notification sound
function playNotificationSound() {
    if (localStorage.getItem('notificationSound') !== 'disabled') {
        const audio = document.getElementById('notificationSound');
        if (audio) {
            audio.play().catch(error => console.log('Error playing sound:', error));
        }
    }
}

// Event source initialization
function initializeEventSource() {
    const eventSource = new EventSource('../api/orders/events.php');
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        if (data.type === 'new_order') {
            playNotificationSound();
            showNotification('New Order', `Order #${data.order.order_number} received from Table ${data.order.table_number}`);
            updateDashboardStats();
            updateCharts(data);
        }
    };
    
    eventSource.onerror = function() {
        // Try to reconnect after 5 seconds
        eventSource.close();
        setTimeout(initializeEventSource, 5000);
    };
}

// Update dashboard statistics
function updateDashboardStats() {
    fetch('../api/dashboard/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update statistics cards
                updateElement('.total-orders', data.total_orders);
                updateElement('.total-sales', formatCurrency(data.total_sales));
                updateElement('.pending-orders', data.pending_orders);
                updateElement('.completed-orders', data.completed_orders);
                
                // Update orders table
                updateOrdersTable(data.recent_orders);
                
                // Update charts if available
                updateCharts(data);
            }
        })
        .catch(error => console.error('Error updating dashboard:', error));
}

// Helper functions
function updateElement(selector, value) {
    const element = document.querySelector(selector);
    if (element) {
        element.textContent = value;
    }
}

function formatCurrency(amount) {
    const formatted = new Intl.NumberFormat('rw-RW', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
    return `RWF ${formatted}`;
}

function updateOrdersTable(orders) {
    const table = document.getElementById('recentOrders');
    if (!table || !orders) return;
    
    table.innerHTML = orders.map(order => `
        <tr>
            <td>${escapeHtml(order.order_number)}</td>
            <td>${escapeHtml(order.table_number)}</td>
            <td>${formatCurrency(order.total_amount)}</td>
            <td>
                <span class="badge bg-${getStatusColor(order.status)}">
                    ${capitalizeFirst(order.status)}
                </span>
            </td>
            <td>${timeAgo(order.created_at)}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-primary" onclick="viewOrder('${order.id}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-success" onclick="updateOrderStatus('${order.id}', 'next')">
                        <i class="fas fa-forward"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'processing': 'info',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const seconds = Math.floor((new Date() - date) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " years ago";
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " months ago";
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " days ago";
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " hours ago";
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutes ago";
    
    return Math.floor(seconds) + " seconds ago";
}

// Chart updates
function updateCharts(data) {
    // Update orders chart if it exists
    const ordersChart = Chart.getChart('ordersChart');
    if (ordersChart && data.hourly_orders) {
        ordersChart.data.datasets[0].data = data.hourly_orders;
        ordersChart.update();
    }
}

// Notification handling
function loadNotifications() {
    const menu = document.getElementById('notificationsMenu');
    if (!menu) return;

    const list = menu.querySelector('.notifications-list');
    if (!list) return;

    fetch('../api/dashboard/notifications.php')
        .then(response => response.json())
        .then(data => {
            list.innerHTML = data.html;
            updateNotificationCount(data.unread_count);
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function updateNotificationCount(count) {
    const badge = document.querySelector('#notificationsDropdown .badge');
    if (!badge) return;

    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'inline';
    } else {
        badge.style.display = 'none';
    }
}

// Mark notifications as read
document.querySelector('.mark-all-read')?.addEventListener('click', function(e) {
    e.preventDefault();
    fetch('../api/dashboard/mark-read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            updateNotificationCount(0);
        }
    })
    .catch(error => console.error('Error marking notifications as read:', error));
});