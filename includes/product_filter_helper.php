<?php
/**
 * Centralized Product Filter Helper
 * 
 * This file provides reusable functions for filtering products across all pages.
 * Just include this file and call apply_product_filters() with your products array.
 * 
 * Usage:
 *   require_once(__DIR__ . '/../includes/product_filter_helper.php');
 *   $filtered_products = apply_product_filters($all_products);
 *   $filter_values = get_filter_values_from_url();
 */

/**
 * Get filter values from URL parameters
 * 
 * @return array Associative array with filter values
 */
function get_filter_values_from_url() {
    return [
        'category' => $_GET['category'] ?? 'all',
        'brand' => $_GET['brand'] ?? 'all',
        'search' => $_GET['search'] ?? '',
        'min_price' => isset($_GET['min_price']) ? intval($_GET['min_price']) : 0,
        'max_price' => isset($_GET['max_price']) ? intval($_GET['max_price']) : 50000,
        'rating' => isset($_GET['rating']) ? intval($_GET['rating']) : 0
    ];
}

/**
 * Apply filters to products array based on URL parameters
 * 
 * @param array $products Array of products to filter
 * @return array Filtered products array
 */
function apply_product_filters($products) {
    $filters = get_filter_values_from_url();
    $filtered = $products;

    // Category filter by ID
    if ($filters['category'] !== 'all' && $filters['category'] !== '') {
        $category_id = intval($filters['category']);
        $filtered = array_filter($filtered, function ($product) use ($category_id) {
            return isset($product['cat_id']) && (int)$product['cat_id'] == $category_id;
        });
    }

    // Brand filter by ID
    if ($filters['brand'] !== 'all' && $filters['brand'] !== '') {
        $brand_id = intval($filters['brand']);
        $filtered = array_filter($filtered, function ($product) use ($brand_id) {
            return isset($product['brand_id']) && (int)$product['brand_id'] == $brand_id;
        });
    }

    // Search query filter
    if (!empty($filters['search'])) {
        $filtered = array_filter($filtered, function ($product) use ($filters) {
            return stripos($product['product_title'], $filters['search']) !== false ||
                stripos($product['product_desc'] ?? '', $filters['search']) !== false;
        });
    }

    // Price range filter
    if ($filters['min_price'] > 0 || $filters['max_price'] < 50000) {
        $filtered = array_filter($filtered, function ($product) use ($filters) {
            $price = floatval($product['product_price'] ?? 0);
            return $price >= $filters['min_price'] && $price <= $filters['max_price'];
        });
    }

    // Rating filter (simulated rating based on product ID)
    if ($filters['rating'] > 0) {
        $filtered = array_filter($filtered, function ($product) use ($filters) {
            // Simulate rating based on product ID (same logic as product_actions.php)
            $simulated_rating = ((int)($product['product_id'] ?? 0) % 5) + 1;
            return $simulated_rating >= $filters['rating'];
        });
    }

    // Re-index array
    return array_values($filtered);
}

/**
 * Get paginated products
 * 
 * @param array $products Array of products
 * @param int $products_per_page Number of products per page (default: 12)
 * @return array Array with 'products', 'total', 'pages', 'current_page'
 */
function get_paginated_products($products, $products_per_page = 12) {
    $total_products = count($products);
    $total_pages = ceil($total_products / $products_per_page);
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($current_page - 1) * $products_per_page;
    $products_to_display = array_slice($products, $offset, $products_per_page);

    return [
        'products' => $products_to_display,
        'total' => $total_products,
        'pages' => $total_pages,
        'current_page' => $current_page
    ];
}

