<?php
session_start();
header('Content-Type: application/json');

try {
    require_once('../settings/core.php');
    require_once('../helpers/notification_helper.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!check_login()) {
        throw new Exception('User not logged in');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $customer_id = get_user_id();

    if (isset($input['notification_id'])) {
        // Mark single notification as read
        $notification_id = intval($input['notification_id']);
        $result = mark_notification_read($notification_id, $customer_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } else {
            throw new Exception('Failed to mark notification as read');
        }
    } elseif (isset($input['mark_all']) && $input['mark_all'] === true) {
        // Mark all notifications as read
        $result = mark_all_notifications_read($customer_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } else {
            throw new Exception('Failed to mark all notifications as read');
        }
    } else {
        throw new Exception('Invalid request parameters');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
