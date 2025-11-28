<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../helpers/image_helper.php';

header('Content-Type: application/json');

if (!check_login()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to view order details.']);
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

try {
    // Get order information
    $order = get_order_by_id_ctr($order_id);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Verify this order belongs to the logged-in user
    if ($order['customer_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    // Get order details (products)
    $order_details = get_order_details_ctr($order_id);

    // Prepare order items with proper image URLs
    $items = [];
    foreach ($order_details as $item) {
        $items[] = [
            'product_id' => $item['product_id'],
            'product_title' => $item['product_title'],
            'product_price' => $item['product_price'],
            'product_image' => get_product_image_url($item['product_image']),
            'qty' => $item['qty'],
            'subtotal' => $item['product_price'] * $item['qty']
        ];
    }

    // Prepare complete order data
    $order_data = [
        'order_id' => $order['order_id'],
        'invoice_no' => $order['invoice_no'],
        'order_date' => $order['order_date'],
        'order_status' => $order['order_status'],
        'payment_amount' => $order['payment_amount'],
        'currency' => $order['currency'],
        'payment_date' => $order['payment_date'],
        'items' => $items
    ];

    echo json_encode([
        'success' => true,
        'order' => $order_data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading order details: ' . $e->getMessage()
    ]);
}