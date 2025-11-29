<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/product_controller.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');
require_once(__DIR__ . '/../helpers/ai_helper.php');
require_once(__DIR__ . '/../helpers/image_helper.php');

header('Content-Type: application/json');

$is_logged_in = check_login();
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

// Build user context
$user_context = [];

if ($is_logged_in && $customer_id) {
    // Get cart items
    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    if (!empty($cart_items)) {
        $user_context['cart_items'] = $cart_items;
    }
    
    // Get wishlist items
    try {
        require_once(__DIR__ . '/../controllers/wishlist_controller.php');
        $wishlist_items = get_wishlist_items_ctr($customer_id);
        if (!empty($wishlist_items)) {
            $user_context['wishlist_items'] = $wishlist_items;
        }
    } catch (Exception $e) {
        error_log("Failed to load wishlist: " . $e->getMessage());
    }
}

// Get current product if viewing a product page
$current_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($current_product_id > 0) {
    try {
        $current_product = view_single_product_ctr($current_product_id);
        if ($current_product) {
            $user_context['current_product'] = $current_product;
        }
    } catch (Exception $e) {
        error_log("Failed to load current product: " . $e->getMessage());
    }
}

// Get all products
try {
    $all_products = get_all_products_ctr();
    
    // Get AI recommendations
    $ai_helper = new AIHelper();
    $recommended_products = $ai_helper->getProductRecommendations($all_products, $user_context);
    
    // Enrich with image URLs
    foreach ($recommended_products as &$product) {
        $product['image_url'] = get_product_image_url(
            $product['product_image'] ?? '',
            $product['product_title'] ?? ''
        );
    }
    
    echo json_encode([
        'status' => 'success',
        'products' => $recommended_products,
        'count' => count($recommended_products)
    ]);
} catch (Exception $e) {
    error_log("AI Recommendations Error: " . $e->getMessage());
    
    // Fallback: return random products
    try {
        $all_products = get_all_products_ctr();
        shuffle($all_products);
        $fallback_products = array_slice($all_products, 0, 6);
        
        foreach ($fallback_products as &$product) {
            $product['image_url'] = get_product_image_url(
                $product['product_image'] ?? '',
                $product['product_title'] ?? ''
            );
        }
        
        echo json_encode([
            'status' => 'success',
            'products' => $fallback_products,
            'count' => count($fallback_products),
            'fallback' => true
        ]);
    } catch (Exception $e2) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to load recommendations',
            'products' => []
        ]);
    }
}
?>

