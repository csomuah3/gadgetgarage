<?php
require_once __DIR__ . '/../classes/product_class.php';

// Add product
function add_product_ctr($product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity = 10) {
    try {
        $product = new Product();

        // Check if product title already exists
        $existing = $product->check_product_exists($product_title);
        if ($existing) {
            return ['status' => 'error', 'message' => 'Product title already exists'];
        }

        $result = $product->add_product($product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity);

        // Check if result is truthy (could be boolean true or a mysqli_result object)
        if ($result !== false && $result !== null) {
            // Get the inserted product ID
            $product_id = $product->get_last_inserted_id();
            if ($product_id > 0) {
                return ['status' => 'success', 'message' => 'Product added successfully', 'product_id' => $product_id];
            } else {
                // Even if we can't get the ID, the product was likely added
                return ['status' => 'success', 'message' => 'Product added successfully'];
            }
        } else {
            // Get actual MySQL error
            $mysql_error = $product->get_last_error();
            error_log("Add product failed: add_product returned false or null. MySQL Error: " . $mysql_error);
            return [
                'status' => 'error', 
                'message' => 'Database error: Could not add product to database',
                'debug' => [
                    'mysql_error' => $mysql_error,
                    'title' => $product_title,
                    'price' => $product_price,
                    'cat' => $category_id,
                    'brand' => $brand_id
                ]
            ];
        }
    } catch (Exception $e) {
        error_log("Add product controller error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return ['status' => 'error', 'message' => 'Failed to add product: ' . $e->getMessage()];
    }
}

// Get all products
function get_all_products_ctr() {
    $product = new Product();
    return $product->get_all_products();
}

// Get products by category
function get_products_by_category_ctr($category_id) {
    $product = new Product();
    return $product->get_products_by_category($category_id);
}

// Get products by brand
function get_products_by_brand_ctr($brand_id) {
    $product = new Product();
    return $product->get_products_by_brand($brand_id);
}

// Get product by ID
function get_product_by_id_ctr($product_id) {
    $product = new Product();
    return $product->get_product_by_id($product_id);
}

// Update product
function update_product_ctr($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id, $product_color = '', $stock_quantity = null) {
    $product = new Product();

    // Check if product title already exists (excluding current product)
    $existing = $product->check_product_exists($product_title, $product_id);
    if ($existing) {
        return ['status' => 'error', 'message' => 'Product title already exists'];
    }

    // Get existing product to preserve fields if not provided
    if ($stock_quantity === null) {
        $existing_product = $product->get_product_by_id($product_id);
        $stock_quantity = $existing_product ? ($existing_product['stock_quantity'] ?? 0) : 0;
    }

    $result = $product->update_product($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity);
    if ($result) {
        return ['status' => 'success', 'message' => 'Product updated successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to update product'];
    }
}

// Delete product
function delete_product_ctr($product_id) {
    try {
        $product = new Product();
        // Use force_delete to remove all dependencies
        $result = $product->force_delete_product($product_id);

        // Check if result is an array (new detailed response) or boolean (old response)
        if (is_array($result)) {
            return $result; // Return the detailed response from the product class
        } else if ($result === true) {
            return ['status' => 'success', 'message' => 'Product deleted successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete product. Please check server logs for details.'];
        }
    } catch (Exception $e) {
        error_log("Delete product controller error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Failed to delete product: ' . $e->getMessage()];
    }
}

// Force delete product (removes dependencies)
function force_delete_product_ctr($product_id) {
    $product = new Product();
    return $product->force_delete_product($product_id);
}

// Search products
function search_products_ctr($search_term) {
    $product = new Product();
    return $product->search_products($search_term);
}

// Assignment required method aliases
function view_all_products_ctr() {
    $product = new Product();
    return $product->view_all_products();
}

function filter_products_by_category_ctr($cat_id) {
    $product = new Product();
    return $product->filter_products_by_category($cat_id);
}

function filter_products_by_brand_ctr($brand_id) {
    $product = new Product();
    return $product->filter_products_by_brand($brand_id);
}

function view_single_product_ctr($id) {
    $product = new Product();
    return $product->view_single_product($id);
}
?>