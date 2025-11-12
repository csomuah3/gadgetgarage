<?php
session_start();
require_once __DIR__ . '/../controllers/cart_controller.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$condition = isset($_POST['condition']) ? trim($_POST['condition']) : 'excellent';
$final_price = isset($_POST['final_price']) ? floatval($_POST['final_price']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

if ($quantity <= 0) {
    $quantity = 1;
}

// Validate condition
$valid_conditions = ['excellent', 'good', 'fair'];
if (!in_array($condition, $valid_conditions)) {
    $condition = 'excellent';
}

$customer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    // Try to add with condition first, fallback to basic if it fails
    $result = add_to_cart_with_condition_ctr($product_id, $quantity, $customer_id, $ip_address, $condition, $final_price);

    if ($result) {
        $cart_count = get_cart_count_ctr($customer_id, $ip_address);
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => $cart_count
        ]);
    } else {
        // Try basic cart addition as fallback
        $basic_result = add_to_cart_ctr($product_id, $quantity, $customer_id, $ip_address);
        if ($basic_result) {
            $cart_count = get_cart_count_ctr($customer_id, $ip_address);
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully (basic)',
                'cart_count' => $cart_count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product to cart - both methods failed']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
}
?>