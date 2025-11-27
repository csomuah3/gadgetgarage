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
    // Get complete order information
    $order_info = get_order_by_id_ctr($order_id);

    if (!$order_info) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    // Get order items (product details)
    $order_items = get_order_details_ctr($order_id);

    // Get customer information
    require_once __DIR__ . '/../controllers/user_controller.php';
    $customer_info = get_customer_by_id_ctr($order_info['customer_id']);

    // Combine all information
    $complete_order = array_merge($order_info, [
        'items' => $order_items,
        'customer_name' => $customer_info['customer_name'] ?? 'Unknown',
        'customer_email' => $customer_info['customer_email'] ?? 'N/A',
        'customer_contact' => $customer_info['customer_contact'] ?? 'N/A',
        'customer_city' => $customer_info['customer_city'] ?? 'N/A',
        'customer_country' => $customer_info['customer_country'] ?? 'N/A',
        'total_amount' => $order_info['payment_amount'] ?? 0
    ]);

    echo json_encode([
        'status' => 'success',
        'order' => $complete_order
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch order details: ' . $e->getMessage()
    ]);
}
?>