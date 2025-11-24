<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/category_controller.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

try {
    // Get all categories
    $categories = get_all_categories_ctr();

    if ($categories && !empty($categories)) {
        echo json_encode($categories);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to get categories: ' . $e->getMessage()]);
}
?>