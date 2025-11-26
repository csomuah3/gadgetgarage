<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/product_controller.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $product_id = (int)($_POST['product_id'] ?? 0);
    $product_title = trim($_POST['product_title'] ?? '');
    $product_price = floatval($_POST['product_price'] ?? 0);
    $product_desc = trim($_POST['product_desc'] ?? '');
    $product_keywords = trim($_POST['product_keywords'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);

    // Validate input
    if ($product_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
        exit;
    }

    if (empty($product_title)) {
        echo json_encode(['status' => 'error', 'message' => 'Product title is required']);
        exit;
    }

    if ($product_price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product price must be greater than 0']);
        exit;
    }

    if (empty($product_desc)) {
        echo json_encode(['status' => 'error', 'message' => 'Product description is required']);
        exit;
    }

    if ($category_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid category is required']);
        exit;
    }

    if ($brand_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid brand is required']);
        exit;
    }

    try {
        // Get existing product to preserve image if no new image is uploaded
        $existing_product = get_product_by_id_ctr($product_id);
        if (!$existing_product) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            exit;
        }
        
        // Keep existing image if no new image uploaded
        $product_image = $existing_product['product_image'] ?? '';
        
        // Get additional product fields from existing product or form
        $product_color = trim($_POST['product_color'] ?? $existing_product['product_color'] ?? '');
        $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : ($existing_product['stock_quantity'] ?? 0);

        // Validate stock quantity
        if ($stock_quantity < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Stock quantity cannot be negative']);
            exit;
        }

        $result = update_product_ctr($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id, $product_color, $stock_quantity);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update product: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>