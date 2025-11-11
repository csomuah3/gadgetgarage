<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    $product_id = $input['product_id'] ?? null;
    $condition = $input['condition'] ?? 'excellent';
    $price = $input['price'] ?? 0;
    $quantity = $input['quantity'] ?? 1;

    // Get customer info
    $customer_id = null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (isset($_SESSION['user_id'])) {
        $customer_id = $_SESSION['user_id'];
    }

    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }

    try {
        // For our demo products, we'll create a modified product ID that includes condition
        $cart_product_id = $product_id . '_' . $condition;

        // Add to cart
        $result = add_to_cart_ctr($cart_product_id, $quantity, $customer_id, $ip_address);

        if ($result) {
            // Get updated cart count
            $cart_count = get_cart_count_ctr($customer_id, $ip_address);

            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully!',
                'cart_count' => $cart_count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>