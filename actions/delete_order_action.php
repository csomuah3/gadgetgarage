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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

$order_id = intval($_POST['order_id']);

try {
    $result = delete_order_ctr($order_id);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Order deleted successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete order'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error deleting order: ' . $e->getMessage()
    ]);
}
?>