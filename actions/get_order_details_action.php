<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!check_login()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!check_admin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

$order_id = intval($_GET['id']);

try {
    $order_details = get_order_details_ctr($order_id);

    if (!$order_details) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'order' => $order_details
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch order details: ' . $e->getMessage()
    ]);
}
?>