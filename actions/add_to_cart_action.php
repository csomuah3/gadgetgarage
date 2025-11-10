<?php
session_start();
require_once __DIR__ . '/../controllers/cart_controller.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

if ($quantity <= 0) {
    $quantity = 1;
}

$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    $result = add_to_cart_ctr($product_id, $customer_id, $ip_address, $quantity);

    if ($result) {
        $cart_count = get_cart_count_ctr($customer_id, $ip_address);
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>