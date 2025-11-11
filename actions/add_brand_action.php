<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/brand_controller.php';

header('Content-Type: application/json');

// Check if user is logged in using core function
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand_name = trim($_POST['brand_name'] ?? '');
    $user_id = get_user_id();

    // Handle both single category (legacy) and multiple categories
    $category_ids = [];

    if (isset($_POST['category_ids']) && is_array($_POST['category_ids'])) {
        // New format: array of category IDs
        $category_ids = array_filter(array_map('intval', $_POST['category_ids']), function($id) { return $id > 0; });
    } elseif (isset($_POST['category_id']) && $_POST['category_id'] > 0) {
        // Legacy format: single category ID
        $category_ids = [(int)$_POST['category_id']];
    }

    // Validate input
    if (empty($brand_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Brand name is required']);
        exit;
    }

    if (empty($category_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select at least one category']);
        exit;
    }

    try {
        $result = add_brand_ctr($brand_name, $category_ids, $user_id);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add brand: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>