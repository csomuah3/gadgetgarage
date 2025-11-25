<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/product_controller.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!check_login()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if (!check_admin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin privileges required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $force_delete = isset($_POST['force_delete']) && $_POST['force_delete'] === 'true';

    // Validate input
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
        exit;
    }

    try {
        error_log("Delete product request - Product ID: $product_id, Force delete: " . ($force_delete ? 'yes' : 'no'));
        
        if ($force_delete) {
            $result = force_delete_product_ctr($product_id);
        } else {
            $result = delete_product_ctr($product_id);
        }
        
        error_log("Delete product result: " . json_encode($result));
        
        if (is_array($result) && isset($result['status'])) {
            echo json_encode($result);
        } else {
            // Handle boolean return (legacy)
            if ($result === true) {
                echo json_encode(['status' => 'success', 'message' => 'Product deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete product']);
            }
        }
    } catch (Exception $e) {
        error_log("Delete product exception: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete product: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>