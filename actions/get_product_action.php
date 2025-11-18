<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/product_controller.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if (!check_admin()) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admin privileges required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($product_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
        exit;
    }

    try {
        $product = get_product_by_id_ctr($product_id);

        if ($product) {
            // Clean and format the product data
            $product_data = [
                'product_id' => $product['product_id'],
                'product_title' => htmlspecialchars($product['product_title']),
                'product_price' => floatval($product['product_price']),
                'product_desc' => htmlspecialchars($product['product_desc'] ?? ''),
                'product_image' => $product['product_image'] ?? '',
                'product_keywords' => htmlspecialchars($product['product_keywords'] ?? ''),
                'product_color' => $product['product_color'] ?? '',
                'product_cat' => intval($product['product_cat']),
                'product_brand' => intval($product['product_brand']),
                'stock_quantity' => intval($product['stock_quantity'] ?? 0)
            ];

            echo json_encode(['status' => 'success', 'product' => $product_data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>