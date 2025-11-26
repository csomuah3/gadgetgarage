<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include required files to test them
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');

    // Get the same data as the real endpoint
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    echo json_encode([
        'success' => true,
        'message' => 'Connection working! Product ID: ' . $product_id . ', Quantity: ' . $quantity,
        'session_check' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? 'not_set',
        'product_id' => $product_id,
        'quantity' => $quantity,
        'customer_id' => $customer_id,
        'ip_address' => $ip_address,
        'post_data' => $_POST,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'cart_functions_loaded' => function_exists('update_cart_item_ctr')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
?>