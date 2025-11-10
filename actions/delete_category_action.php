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
$user_id = $_SESSION['user_id'];

if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
    exit();
}

try {
    // FIXED: Pass both category_id AND user_id to verify ownership
    $existing_category = get_category_ctr($category_id, $user_id);

    if (!$existing_category) {
        echo json_encode(['success' => false, 'message' => 'Category not found or access denied']);
        exit();
    }

    // FIXED: Pass both category_id AND user_id to delete function
    $result = delete_category_ctr($category_id, $user_id);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
