<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');

try {
    // Check if user is logged in
    $is_logged_in = check_login();

    if (!$is_logged_in) {
        echo json_encode([
            'success' => true,
            'is_logged_in' => false,
            'count' => 0,
            'wishlist_items' => []
        ]);
        exit;
    }

    $customer_id = $_SESSION['user_id'];

    // Get wishlist count
    $count = get_wishlist_count_ctr($customer_id);

    // Get all wishlist product IDs for this customer
    $wishlist_items = get_wishlist_items_ctr($customer_id);
    $product_ids = array_column($wishlist_items, 'product_id');

    echo json_encode([
        'success' => true,
        'is_logged_in' => true,
        'count' => $count,
        'wishlist_items' => $product_ids
    ]);

} catch (Exception $e) {
    error_log('Get wishlist status error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'is_logged_in' => false,
        'count' => 0,
        'wishlist_items' => [],
        'message' => 'Failed to load wishlist status'
    ]);
}
?>