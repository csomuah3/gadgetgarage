<?php
require_once __DIR__ . '/../controllers/product_controller.php';
require_once __DIR__ . '/../controllers/category_controller.php';
require_once __DIR__ . '/../controllers/brand_controller.php';
require_once __DIR__ . '/../helpers/image_helper.php';

function enrich_product_image_urls($products) {
    if (empty($products)) {
        return $products;
    }

    // Single product
    if (isset($products['product_id'])) {
        $products['image_url'] = get_product_image_url(
            $products['product_image'] ?? '',
            $products['product_title'] ?? ''
        );
        return $products;
    }

    return array_map(function ($product) {
        $product['image_url'] = get_product_image_url(
            $product['product_image'] ?? '',
            $product['product_title'] ?? ''
        );
        return $product;
    }, $products);
}
header('Content-Type: application/json');

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'view_all_products':
            $products = view_all_products_ctr();
            $products = enrich_product_image_urls($products);
            echo json_encode($products);
            break;

        case 'search_products':
            $query = $_REQUEST['query'] ?? '';
            if (!empty($query)) {
                $products = search_products_ctr($query);
                $products = enrich_product_image_urls($products);
                echo json_encode($products);
            } else {
                echo json_encode(['error' => 'Search query is required']);
            }
            break;

        case 'filter_by_category':
            $cat_id = $_REQUEST['cat_id'] ?? 0;
            if ($cat_id > 0) {
                $products = filter_products_by_category_ctr($cat_id);
                $products = enrich_product_image_urls($products);
                echo json_encode($products);
            } else {
                echo json_encode(['error' => 'Valid category ID is required']);
            }
            break;

        case 'filter_by_brand':
            $brand_id = $_REQUEST['brand_id'] ?? 0;
            if ($brand_id > 0) {
                $products = filter_products_by_brand_ctr($brand_id);
                $products = enrich_product_image_urls($products);
                echo json_encode($products);
            } else {
                echo json_encode(['error' => 'Valid brand ID is required']);
            }
            break;

        case 'view_single_product':
            $product_id = $_REQUEST['product_id'] ?? 0;
            if ($product_id > 0) {
                $product = view_single_product_ctr($product_id);
                $product = enrich_product_image_urls($product);
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
            try {
                // Get and sanitize filter parameters
                $search_query = isset($_REQUEST['query']) ? trim($_REQUEST['query']) : '';
                
                // Handle cat_ids - can be array or single value
                $cat_ids = [];
                if (isset($_REQUEST['cat_ids'])) {
                    if (is_array($_REQUEST['cat_ids'])) {
                        $cat_ids = array_filter($_REQUEST['cat_ids'], function($id) {
                            return !empty($id) && $id !== '';
                        });
                    } else if (!empty($_REQUEST['cat_ids']) && $_REQUEST['cat_ids'] !== '') {
                        $cat_ids = [$_REQUEST['cat_ids']];
                    }
                }
                
                // Handle brand_ids - can be array or single value
                $brand_ids = [];
                if (isset($_REQUEST['brand_ids'])) {
                    if (is_array($_REQUEST['brand_ids'])) {
                        $brand_ids = array_filter($_REQUEST['brand_ids'], function($id) {
                            return !empty($id) && $id !== '';
                        });
                    } else if (!empty($_REQUEST['brand_ids']) && $_REQUEST['brand_ids'] !== '') {
                        $brand_ids = [$_REQUEST['brand_ids']];
                    }
                }
                
                $min_price = isset($_REQUEST['min_price']) && $_REQUEST['min_price'] !== '' ? (float)$_REQUEST['min_price'] : 0;
                $max_price = isset($_REQUEST['max_price']) && $_REQUEST['max_price'] !== '' ? (float)$_REQUEST['max_price'] : PHP_FLOAT_MAX;
                $rating = isset($_REQUEST['rating']) ? trim($_REQUEST['rating']) : '';
                $size = isset($_REQUEST['size']) ? trim($_REQUEST['size']) : '';
                $color = isset($_REQUEST['color']) ? trim($_REQUEST['color']) : '';

                // Debug logging
                error_log("=== FILTER REQUEST ===");
                error_log("Search: " . $search_query);
                error_log("Category IDs: " . json_encode($cat_ids));
                error_log("Brand IDs: " . json_encode($brand_ids));
                error_log("Price Range: $min_price - $max_price");
                error_log("Rating: $rating, Size: $size, Color: $color");

                // Start with all products
                $products = view_all_products_ctr();
                if (!is_array($products)) {
                    $products = [];
                }
                error_log("Initial product count: " . count($products));

                // Apply search filter if provided
                if (!empty($search_query)) {
                    $products = array_filter($products, function ($product) use ($search_query) {
                        $title = isset($product['product_title']) ? $product['product_title'] : '';
                        $desc = isset($product['product_desc']) ? $product['product_desc'] : '';
                        $keywords = isset($product['product_keywords']) ? $product['product_keywords'] : '';
                        
                        return stripos($title, $search_query) !== false ||
                            stripos($desc, $search_query) !== false ||
                            stripos($keywords, $search_query) !== false;
                    });
                    error_log("After search filter: " . count($products) . " products");
                }

                // Apply category filter
                if (!empty($cat_ids) && !empty($cat_ids[0])) {
                    $cat_id = (int)$cat_ids[0];
                    if ($cat_id > 0) {
                        error_log("Filtering by category ID: $cat_id");
                        $products = array_filter($products, function ($product) use ($cat_id) {
                            $product_cat = isset($product['product_cat']) ? (int)$product['product_cat'] : 0;
                            return $product_cat === $cat_id;
                        });
                        error_log("After category filter: " . count($products) . " products");
                    }
                }

                // Apply brand filter
                if (!empty($brand_ids) && !empty($brand_ids[0])) {
                    $brand_id = (int)$brand_ids[0];
                    if ($brand_id > 0) {
                        error_log("Filtering by brand ID: $brand_id");
                        $products = array_filter($products, function ($product) use ($brand_id) {
                            $product_brand = isset($product['product_brand']) ? (int)$product['product_brand'] : 0;
                            return $product_brand === $brand_id;
                        });
                        error_log("After brand filter: " . count($products) . " products");
                    }
                }

                // Apply price range filter
                if ($min_price > 0 || ($max_price < PHP_FLOAT_MAX && $max_price > 0)) {
                    $products = array_filter($products, function ($product) use ($min_price, $max_price) {
                        $price = isset($product['product_price']) ? (float)$product['product_price'] : 0;
                        return $price >= $min_price && $price <= $max_price;
                    });
                    error_log("After price filter: " . count($products) . " products");
                }

                // Apply rating filter (simulated ratings based on product ID)
                if (!empty($rating) && is_numeric($rating)) {
                    $rating_int = (int)$rating;
                    if ($rating_int >= 1 && $rating_int <= 5) {
                        $products = array_filter($products, function ($product) use ($rating_int) {
                            // Simulate rating based on product ID
                            $simulated_rating = ((int)$product['product_id'] % 5) + 1;
                            return $simulated_rating >= $rating_int;
                        });
                        error_log("After rating filter (simulated): " . count($products) . " products");
                    }
                }

                // Apply size filter
                if (!empty($size)) {
                    $products = array_filter($products, function ($product) use ($size) {
                        $desc = isset($product['product_desc']) ? $product['product_desc'] : '';
                        $keywords = isset($product['product_keywords']) ? $product['product_keywords'] : '';
                        $title = isset($product['product_title']) ? $product['product_title'] : '';
                        
                        return stripos($desc, $size) !== false ||
                               stripos($keywords, $size) !== false ||
                               stripos($title, $size) !== false;
                    });
                    error_log("After size filter: " . count($products) . " products");
                }

                // Apply color filter
                if (!empty($color)) {
                    $products = array_filter($products, function ($product) use ($color) {
                        $desc = isset($product['product_desc']) ? $product['product_desc'] : '';
                        $keywords = isset($product['product_keywords']) ? $product['product_keywords'] : '';
                        $title = isset($product['product_title']) ? $product['product_title'] : '';
                        
                        return stripos($desc, $color) !== false ||
                               stripos($keywords, $color) !== false ||
                               stripos($title, $color) !== false;
                    });
                    error_log("After color filter: " . count($products) . " products");
                }

                // Re-index array and enrich with image URLs
                $products = array_values($products);
                $products = enrich_product_image_urls($products);
                
                error_log("Final product count: " . count($products));
                error_log("=== FILTER COMPLETE ===");
                
                // Return JSON response
                header('Content-Type: application/json');
                echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
            } catch (Exception $e) {
                error_log("Filter error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'error' => 'Filter error occurred',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
