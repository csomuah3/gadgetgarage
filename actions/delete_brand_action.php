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

    // Validate input
    if ($brand_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid brand ID']);
        exit;
    }

    try {
        $result = delete_brand_ctr($brand_id);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete brand: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>