<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../helpers/ai_helper.php');

header('Content-Type: application/json');

// Check if user is admin
if (!check_admin()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin access required'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_title = isset($_POST['product_title']) ? trim($_POST['product_title']) : '';
    $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';
    $category_name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $existing_description = isset($_POST['existing_description']) ? trim($_POST['existing_description']) : '';
    
    // Validation
    if (empty($product_title)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product title is required'
        ]);
        exit;
    }
    
    if ($price <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Valid price is required'
        ]);
        exit;
    }
    
    try {
        $ai_helper = new AIHelper();
        $description = $ai_helper->generateProductDescription(
            $product_title,
            $brand_name ?: 'Unknown',
            $category_name ?: 'General',
            $price,
            $existing_description
        );
        
        echo json_encode([
            'status' => 'success',
            'description' => $description
        ]);
    } catch (Exception $e) {
        error_log("Generate description error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to generate description: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>

