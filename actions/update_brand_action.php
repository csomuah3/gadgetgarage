<?php
session_start();
require_once __DIR__ . '/../controllers/brand_controller.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $brand_name = trim($_POST['brand_name'] ?? '');
    $category_ids = $_POST['category_ids'] ?? [];
    $user_id = $_SESSION['user_id'];

    // Validate input
    if ($brand_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid brand ID']);
        exit;
    }

    if (empty($brand_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Brand name is required']);
        exit;
    }

    if (empty($category_ids) || !is_array($category_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select at least one category']);
        exit;
    }

    // Convert to integers and filter out invalid values
    $category_ids = array_map('intval', $category_ids);
    $category_ids = array_filter($category_ids, function($id) { return $id > 0; });

    if (empty($category_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select valid categories']);
        exit;
    }

    try {
        $result = update_brand_ctr($brand_id, $brand_name, $category_ids, $user_id);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update brand: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>