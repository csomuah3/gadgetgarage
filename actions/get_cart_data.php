<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/product_controller.php');

try {
    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Get cart items
    $cart_items = get_cart_items_ctr($customer_id, $ip_address);
    $cart_total = get_cart_total_ctr($customer_id, $ip_address);
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);

    $formatted_items = [];

    if ($cart_items && is_array($cart_items)) {
        foreach ($cart_items as $item) {
            // Handle different possible field names from cart
            $product_id = $item['p_id'] ?? $item['product_id'] ?? null;
            $quantity = $item['qty'] ?? $item['quantity'] ?? 1;
            
            if (!$product_id) continue;
            
            // Get product details
            $product = view_single_product_ctr($product_id);

            if ($product) {
                // Use final_price if available, otherwise use product_price
                $price = isset($item['final_price']) && $item['final_price'] > 0 
                    ? floatval($item['final_price']) 
                    : floatval($product['product_price']);
                
                $formatted_items[] = [
                    'id' => $item['cart_id'] ?? null,
                    'product_id' => $product_id,
                    'name' => $product['product_title'] ?? 'Unknown Product',
                    'price' => number_format($price, 2),
                    'original_price' => isset($product['original_price']) ? number_format($product['original_price'], 2) : null,
                    'image' => $product['product_image'] ?? '../uploads/default-product.png',
                    'condition' => $item['condition_type'] ?? $product['product_condition'] ?? 'New',
                    'quantity' => intval($quantity)
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'items' => $formatted_items,
        'count' => $cart_count,
        'total' => number_format($cart_total, 2),
        'total_raw' => $cart_total
    ]);

} catch (Exception $e) {
    error_log('Get cart data error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load cart data',
        'items' => [],
        'count' => 0,
        'total' => '0.00',
        'total_raw' => 0
    ]);
}
?>