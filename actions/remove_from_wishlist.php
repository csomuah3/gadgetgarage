<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');

try {
    // Check if user is logged in
    $is_logged_in = check_login();

    if (!$is_logged_in) {
        throw new Exception('Please log in to manage wishlist');
    }

    $customer_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    // Remove from wishlist
    $result = remove_from_wishlist_ctr($product_id, $customer_id);

    if ($result) {
        // Get updated wishlist count
        $count = get_wishlist_count_ctr($customer_id);

        echo json_encode([
            'success' => true,
            'message' => 'Item removed from wishlist',
            'count' => $count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove item from wishlist'
        ]);
    }

} catch (Exception $e) {
    error_log('Remove from wishlist error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>