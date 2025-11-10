<?php
require_once __DIR__ . '/../classes/product_class.php';

// Add product
function add_product_ctr($product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id) {
    $product = new Product();

    // Check if product title already exists
    $existing = $product->check_product_exists($product_title);
    if ($existing) {
        return ['status' => 'error', 'message' => 'Product title already exists'];
    }

    $result = $product->add_product($product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Product added successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to add product'];
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
function update_product_ctr($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id) {
    $product = new Product();

    // Check if product title already exists (excluding current product)
    $existing = $product->check_product_exists($product_title, $product_id);
    if ($existing) {
        return ['status' => 'error', 'message' => 'Product title already exists'];
    }

    $result = $product->update_product($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Product updated successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to update product'];
    }
}

// Delete product
function delete_product_ctr($product_id) {
    $product = new Product();
    $result = $product->delete_product($product_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Product deleted successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to delete product'];
    }
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