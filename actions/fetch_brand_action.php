<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/brand_controller.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

try {
    $user_id = get_user_id();
    $brands = get_brands_ctr($user_id);

    echo json_encode(['status' => 'success', 'data' => $brands]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch brands: ' . $e->getMessage()]);
}
?>