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
    // Get all brands
    $brands = get_all_brands_ctr();

    if ($brands && !empty($brands)) {
        echo json_encode($brands);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to get brands: ' . $e->getMessage()]);
}
?>