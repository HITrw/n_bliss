<?php
require_once '../../config/config.php';
require_once '../../includes/Database.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = Database::getInstance();

// Get notifications for the current user
$notifications = $db->fetchAll("
    SELECT * FROM notifications 
    WHERE employee_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10", 
    [$_SESSION['admin_id']]
);

$html = '';
if (empty($notifications)) {
    $html = '<div class="p-3 text-center text-muted">No notifications</div>';
} else {
    foreach ($notifications as $notification) {
        $timeAgo = time_elapsed_string($notification['created_at']);
        $icon = get_notification_icon($notification['type']);
        $html .= '
        <div class="notification-item' . ($notification['is_read'] ? '' : ' unread') . '">
            <div class="d-flex align-items-center">
                <div class="notification-icon me-3">
                    <i class="' . $icon . '"></i>
                </div>
                <div class="notification-content flex-grow-1">
                    <p class="notification-message mb-1">' . htmlspecialchars($notification['message']) . '</p>
                    <small class="notification-time text-muted">' . $timeAgo . '</small>
                </div>
                ' . (!$notification['is_read'] ? '<span class="badge bg-primary">New</span>' : '') . '
            </div>
        </div>';
    }
}

// Helper function to get time elapsed string
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return "Just now";
            }
            return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
        }
        return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    }
    if ($diff->d <= 7) {
        return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    }
    return date('M j, Y', strtotime($datetime));
}

// Helper function to get notification icon
function get_notification_icon($type) {
    switch ($type) {
        case 'order':
            return 'fas fa-shopping-cart text-primary';
        case 'stock':
            return 'fas fa-box text-warning';
        case 'user':
            return 'fas fa-user text-success';
        case 'alert':
            return 'fas fa-exclamation-circle text-danger';
        default:
            return 'fas fa-bell text-info';
    }
}

echo json_encode([
    'html' => $html,
    'count' => count(array_filter($notifications, fn($n) => !$n['is_read']))
]);
