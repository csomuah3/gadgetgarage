<?php
session_start();
require_once __DIR__ . '/../controllers/cart_controller.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$customer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    $result = empty_cart_ctr($customer_id, $ip_address);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Cart emptied successfully',
            'cart_count' => 0,
            'cart_total' => '0.00'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to empty cart']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>