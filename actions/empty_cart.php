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

    // Empty the cart
    $empty_result = empty_cart_ctr($customer_id, $ip_address);

    if ($empty_result) {
        echo json_encode([
            'success' => true,
            'message' => 'Your cart has been emptied successfully',
            'cart_total' => '0.00',
            'cart_count' => 0
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to empty cart. Please try again.'
        ]);
    }

} catch (Exception $e) {
    error_log('Empty cart error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while emptying the cart'
    ]);
}
?>