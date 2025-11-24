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
        // For update, we don't change the image, so we need to get the current image
        $product_image = ''; // We'll keep the existing image for now

        $result = update_product_ctr($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update product: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>