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
    // Check if category exists
    $existing_category = get_category_ctr($category_id);

    if (!$existing_category['success']) {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        exit();
    }

    // Delete category
    $result = delete_category_ctr($category_id);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    } else {
        $error_message = $result['message'] ?? 'Failed to delete category';
        echo json_encode(['success' => false, 'message' => $error_message]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
