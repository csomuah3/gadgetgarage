<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/category_controller.php');

header('Content-Type: application/json');

if (!check_login() || !check_admin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$category_id = intval($_POST['category_id'] ?? 0);
$category_name = trim($_POST['category_name'] ?? '');
$user_id = $_SESSION['user_id'];

if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
    exit();
}

if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
}

try {
    // FIXED: Pass both category_id AND user_id to controller
    $existing_category = get_category_ctr($category_id, $user_id);

    if (!$existing_category) {
        echo json_encode(['success' => false, 'message' => 'Category not found or access denied']);
        exit();
    }

    // FIXED: Pass all three parameters to update function
    $result = update_category_ctr($category_id, $category_name, $user_id);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Category name already exists or failed to update']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
