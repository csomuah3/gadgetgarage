<?php
session_start();
header('Content-Type: application/json');

try {
    require_once('../settings/core.php');
    require_once('../helpers/notification_helper.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    if (!check_login()) {
        throw new Exception('User not logged in');
    }

    $customer_id = get_user_id();
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

    // Get notifications
    $notifications = get_user_notifications($customer_id, $limit);

    // Format notifications for frontend
    $formatted_notifications = [];
    foreach ($notifications as $notification) {
        $formatted_notifications[] = [
            'id' => $notification['notification_id'],
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'icon' => $notification['icon'],
            'is_read' => (bool)$notification['is_read'],
            'priority' => $notification['priority'],
            'action_url' => $notification['action_url'],
            'time_ago' => time_ago($notification['created_at']),
            'created_at' => $notification['created_at']
        ];
    }

    // Get unread count
    $unread_count = get_unread_notification_count($customer_id);

    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications,
        'unread_count' => $unread_count,
        'total_count' => count($formatted_notifications)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>