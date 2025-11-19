<?php
session_start();
require_once '../controllers/cart_controller.php';

header('Content-Type: application/json');

try {
    // Get user session
    $is_logged_in = isset($_SESSION['user_id']);

    // Get customer ID and IP address
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Get cart count
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'count' => (int)$cart_count
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'count' => 0,
        'error' => $e->getMessage()
    ]);
}
?>