<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to user
ini_set('log_errors', 1);

try {
    // Include required files
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');

    // Check if user is logged in
    if (!check_login()) {
        echo json_encode([
            'success' => false,
            'message' => 'Please log in to modify your cart'
        ]);
        exit;
    }

    // Get customer info
    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit;
    }

    // Get POST data
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    // Validate inputs
    if ($product_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product ID'
        ]);
        exit;
    }

    // Remove item from cart
    $remove_result = remove_from_cart_ctr($customer_id, $product_id, $ip_address);

    if ($remove_result) {
        // Get updated cart totals
        $cart_total = get_cart_total_ctr($customer_id, $ip_address) ?: 0;
        $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart successfully',
            'cart_total' => number_format($cart_total, 2),
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove item from cart. Please try again.'
        ]);
    }

} catch (Exception $e) {
    error_log('Cart item removal error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while removing the item'
    ]);
}
?>