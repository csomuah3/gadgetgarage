<?php
require_once __DIR__ . '/../controllers/product_controller.php';
require_once __DIR__ . '/../controllers/category_controller.php';
require_once __DIR__ . '/../controllers/brand_controller.php';
header('Content-Type: application/json');

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'view_all_products':
            $products = view_all_products_ctr();
            echo json_encode($products);
            break;

        case 'search_products':
            $query = $_REQUEST['query'] ?? '';
            if (!empty($query)) {
                $products = search_products_ctr($query);
                echo json_encode($products);
            } else {
                echo json_encode(['error' => 'Search query is required']);
            }
            break;

        case 'filter_by_category':
            $cat_id = $_REQUEST['cat_id'] ?? 0;
            if ($cat_id > 0) {
                $products = filter_products_by_category_ctr($cat_id);
                echo json_encode($products);
            } else {
                echo json_encode(['error' => 'Valid category ID is required']);
            }
            break;

        case 'filter_by_brand':
            $brand_id = $_REQUEST['brand_id'] ?? 0;
            if ($brand_id > 0) {
                $products = filter_products_by_brand_ctr($brand_id);
                echo json_encode($products);
            } else {
                echo json_encode(['error' => 'Valid brand ID is required']);
            }
            break;

        case 'view_single_product':
            $product_id = $_REQUEST['product_id'] ?? 0;
            if ($product_id > 0) {
                $product = view_single_product_ctr($product_id);
                echo json_encode($product);
            } else {
                echo json_encode(['error' => 'Valid product ID is required']);
            }
            break;

        case 'get_categories':
            $categories = get_all_categories_ctr();
            echo json_encode($categories);
            break;

        case 'get_brands':
            $brands = get_all_brands_ctr();
            echo json_encode($brands);
            break;

        case 'combined_filter':
            $search_query = $_REQUEST['query'] ?? '';
            $cat_ids = $_REQUEST['cat_ids'] ?? [];
            $brand_ids = $_REQUEST['brand_ids'] ?? [];
            $min_price = isset($_REQUEST['min_price']) ? (float)$_REQUEST['min_price'] : 0;
            $max_price = isset($_REQUEST['max_price']) ? (float)$_REQUEST['max_price'] : PHP_FLOAT_MAX;
            $rating = $_REQUEST['rating'] ?? '';
            $size = $_REQUEST['size'] ?? '';
            $color = $_REQUEST['color'] ?? '';

            // Start with all products
            $products = view_all_products_ctr();

            // Apply search filter if provided
            if (!empty($search_query)) {
                $products = array_filter($products, function ($product) use ($search_query) {
                    return stripos($product['product_title'], $search_query) !== false ||
                        stripos($product['product_desc'], $search_query) !== false ||
                        stripos($product['product_keywords'], $search_query) !== false;
                });
            }

            // Apply category filter (single category like brand)
            if (!empty($cat_ids) && is_array($cat_ids) && !empty($cat_ids[0])) {
                $cat_id = $cat_ids[0];
                $products = array_filter($products, function ($product) use ($cat_id) {
                    return $product['product_cat'] == $cat_id;
                });
            }

            // Apply brand filter (single brand for new design)
            if (!empty($brand_ids) && is_array($brand_ids) && !empty($brand_ids[0])) {
                $brand_id = $brand_ids[0];
                $products = array_filter($products, function ($product) use ($brand_id) {
                    return $product['product_brand'] == $brand_id;
                });
            }

            // Apply price range filter
            if ($min_price > 0 || $max_price < PHP_FLOAT_MAX) {
                $products = array_filter($products, function ($product) use ($min_price, $max_price) {
                    $price = (float)$product['product_price'];
                    return $price >= $min_price && $price <= $max_price;
                });
            }

            // Apply rating filter (simulate ratings since we don't have a rating field in products)
            if (!empty($rating)) {
                // For demonstration purposes, we'll add a simulated rating based on product ID
                $products = array_filter($products, function ($product) use ($rating) {
                    // Simulate rating based on product ID (just for demo)
                    $simulated_rating = ((int)$product['product_id'] % 5) + 1;
                    return $simulated_rating >= (int)$rating;
                });
            }

            // Apply size filter (check if product description or keywords contain size)
            if (!empty($size)) {
                $products = array_filter($products, function ($product) use ($size) {
                    return stripos($product['product_desc'], $size) !== false ||
                           stripos($product['product_keywords'], $size) !== false ||
                           stripos($product['product_title'], $size) !== false;
                });
            }

            // Apply color filter (check if product description or keywords contain color)
            if (!empty($color)) {
                $products = array_filter($products, function ($product) use ($color) {
                    return stripos($product['product_desc'], $color) !== false ||
                           stripos($product['product_keywords'], $color) !== false ||
                           stripos($product['product_title'], $color) !== false;
                });
            }

            echo json_encode(array_values($products));
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
