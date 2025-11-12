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
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $brand_name = trim($_POST['brand_name'] ?? '');
    $user_id = get_user_id();

    // Simple single category approach
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    // Validate input
    if ($brand_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid brand ID']);
        exit;
    }

    if (empty($brand_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Brand name is required']);
        exit;
    }

    if ($category_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a category']);
        exit;
    }

    try {
        $result = update_brand_ctr($brand_id, $brand_name, $category_id, $user_id);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update brand: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>