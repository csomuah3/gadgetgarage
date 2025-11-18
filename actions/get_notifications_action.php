<?php
session_start();
require_once __DIR__ . '/../controllers/support_controller.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

try {
    $customer_id = $_SESSION['user_id'];
    $notifications = get_customer_notifications_ctr($customer_id);

    echo json_encode([
        'status' => 'success',
        'notifications' => $notifications
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
}
?>