<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');

try {
    // Check if user is logged in
    $is_logged_in = check_login();

    if (!$is_logged_in) {
        throw new Exception('Please log in to add items to wishlist');
    }

    $customer_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    // Add to wishlist
    $result = add_to_wishlist_ctr($product_id, $customer_id, $ip_address);

    if ($result) {
        // Get updated wishlist count
        $count = get_wishlist_count_ctr($customer_id);

        echo json_encode([
            'success' => true,
            'message' => 'Item added to wishlist',
            'count' => $count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Item is already in wishlist or failed to add'
        ]);
    }

} catch (Exception $e) {
    error_log('Add to wishlist error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>