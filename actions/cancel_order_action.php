<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to cancel orders'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    $input = $_POST;
}

$order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
$order_reference = isset($input['order_reference']) ? trim($input['order_reference']) : '';

// Validate input
if (!$order_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Order ID is required'
    ]);
    exit();
}

try {
    $customer_id = $_SESSION['user_id'];

    // First, verify the order belongs to this customer
    $orders = get_user_orders_ctr($customer_id);
    $order_found = false;

    foreach ($orders as $order) {
        if ($order['order_id'] == $order_id) {
            $order_found = true;
            $order_date = $order['order_date'];
            break;
        }
    }

    if (!$order_found) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found or access denied'
        ]);
        exit();
    }

    // Check if order can be cancelled (only allow cancellation within 1 hour of placing order)
    $order_timestamp = strtotime($order_date);
    $current_timestamp = time();
    $hours_difference = ($current_timestamp - $order_timestamp) / 3600;

    if ($hours_difference > 24) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Order cannot be cancelled. Orders can only be cancelled within 24 hours of placement.'
        ]);
        exit();
    }

    // Cancel the order (delete from database)
    $result = delete_order_ctr($order_id);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Order cancelled successfully',
            'order_reference' => $order_reference
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to cancel order. Please contact support.'
        ]);
    }

} catch (Exception $e) {
    error_log("Order cancellation error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to cancel order: ' . $e->getMessage()
    ]);
}
?>