<?php
session_start();
require_once __DIR__ . '/../controllers/support_controller.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['notification_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Notification ID required']);
        exit();
    }

    $customer_id = $_SESSION['user_id'];
    $notification_id = intval($input['notification_id']);

    $result = mark_notification_read_ctr($notification_id, $customer_id);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to mark notification as read']);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>