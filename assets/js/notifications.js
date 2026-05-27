// Notification handling
let notificationCount = 0;
let notificationSound = new Audio('../assets/sounds/notification.mp3');
let soundEnabled = localStorage.getItem('notificationSound') !== 'disabled';

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.querySelector('#notificationBadge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Fetch notifications
async function fetchNotifications() {
    try {
        const response = await fetch('../api/notifications.php');
        const data = await response.json();
        
        if (data.success) {
            renderNotifications(data.notifications);
            if (data.notifications.length > notificationCount) {
                if (soundEnabled) notificationSound.play();
            }
            notificationCount = data.notifications.length;
            updateNotificationBadge(data.unread);
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
}

// Render notifications in dropdown
function renderNotifications(notifications) {
    const container = document.querySelector('#notificationList');
    if (!container) return;

    container.innerHTML = notifications.length ? '' : '<li class="dropdown-item text-center text-muted">No new notifications</li>';

    notifications.forEach(notification => {
        const item = document.createElement('li');
        item.innerHTML = `
            <a class="dropdown-item notification-item ${notification.read ? '' : 'unread'}" href="${notification.link || '#'}" 
               data-id="${notification.id}">
                <div class="notification-icon ${notification.type}">
                    <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <p class="mb-1">${notification.message}</p>
                    <small class="text-muted">${formatTimeAgo(notification.created_at)}</small>
                </div>
            </a>
        `;
        container.appendChild(item);
    });
}

// Get appropriate icon for notification type
function getNotificationIcon(type) {
    const icons = {
        'order': 'shopping-cart',
        'alert': 'exclamation-circle',
        'system': 'cog',
        'user': 'user'
    };
    return icons[type] || 'bell';
}

// Format time ago
function formatTimeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + ' years ago';
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + ' months ago';
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + ' days ago';
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + ' hours ago';
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + ' minutes ago';
    
    return 'just now';
}

// Toggle notification sound
document.querySelector('#toggleSound')?.addEventListener('click', function(e) {
    soundEnabled = !soundEnabled;
    localStorage.setItem('notificationSound', soundEnabled ? 'enabled' : 'disabled');
    this.querySelector('i').className = `fas fa-volume-${soundEnabled ? 'up' : 'mute'}`;
});

// Mark notification as read
document.querySelector('#notificationList')?.addEventListener('click', async function(e) {
    const item = e.target.closest('.notification-item');
    if (!item) return;

    try {
        const response = await fetch('../api/mark-notification-read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: item.dataset.id })
        });
        
        if (response.ok) {
            item.classList.remove('unread');
            const data = await response.json();
            updateNotificationBadge(data.unread);
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    fetchNotifications();
    // Poll for new notifications every minute
    setInterval(fetchNotifications, 60000);
});
