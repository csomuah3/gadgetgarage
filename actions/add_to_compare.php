<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/compare_controller.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to use compare feature'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    if ($product_id <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid product ID'
        ]);
        exit;
    }
    
    try {
        $result = add_to_compare_ctr($product_id, $customer_id, $ip_address);
        echo json_encode($result);
    } catch (Exception $e) {
        error_log("Add to compare error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to add product to compare list'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>

