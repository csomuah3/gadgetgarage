<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Show errors for debugging
ini_set('log_errors', 1);

try {
    // Include required files
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');

    // Check if user is logged in
    if (!check_login()) {
        echo json_encode([
            'success' => false,
            'message' => 'Please log in to update your cart'
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
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

    // Validate inputs
    if ($product_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product ID'
        ]);
        exit;
    }

    if ($quantity <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Quantity must be at least 1'
        ]);
        exit;
    }

    if ($quantity > 99) {
        echo json_encode([
            'success' => false,
            'message' => 'Maximum quantity is 99'
        ]);
        exit;
    }

    // Log the inputs for debugging
    error_log("Update cart quantity - Product ID: $product_id, Quantity: $quantity, Customer ID: $customer_id, IP: $ip_address");

    // Update cart quantity
    $update_result = update_cart_item_ctr($product_id, $quantity, $customer_id, $ip_address);

    error_log("Update cart quantity result: " . ($update_result ? 'success' : 'failure'));

    if ($update_result) {
        // Get updated cart totals
        $cart_total = get_cart_total_ctr($customer_id, $ip_address) ?: 0;
        $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

        // Calculate item total (quantity * unit price)
        $cart_items = get_user_cart_ctr($customer_id, $ip_address);
        $item_total = 0;

        if ($cart_items) {
            foreach ($cart_items as $item) {
                if ($item['p_id'] == $product_id) {
                    $item_total = $item['product_price'] * $quantity;
                    break;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Quantity updated successfully',
            'cart_total' => number_format($cart_total, 2),
            'cart_count' => $cart_count,
            'item_total' => number_format($item_total, 2),
            'quantity' => $quantity
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update quantity. Please try again.'
        ]);
    }

} catch (Exception $e) {
    error_log('Cart quantity update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating quantity',
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
?>