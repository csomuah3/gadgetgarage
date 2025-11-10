<?php
require_once __DIR__ . '/../classes/brand_class.php';

// Add brand
function add_brand_ctr($brand_name, $category_id, $user_id) {
    $brand = new Brand();

    // Check if brand name already exists
    $existing = $brand->check_brand_exists($brand_name);
    if ($existing) {
        return ['status' => 'error', 'message' => 'Brand name already exists'];
    }

    $result = $brand->add_brand($brand_name, $category_id, $user_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Brand added successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to add brand'];
    }
}

// Get all brands for a user
function get_brands_ctr($user_id) {
    $brand = new Brand();
    return $brand->get_brands_by_user($user_id);
}

// Get all brands (for admin)
function get_all_brands_ctr() {
    $brand = new Brand();
    return $brand->get_all_brands();
}

// Get brand by ID
function get_brand_by_id_ctr($brand_id) {
    $brand = new Brand();
    return $brand->get_brand_by_id($brand_id);
}

// Update brand
function update_brand_ctr($brand_id, $brand_name, $category_id, $user_id) {
    $brand = new Brand();

    // Check if brand name already exists (excluding current brand)
    $existing = $brand->check_brand_exists($brand_name, $category_id, $user_id, $brand_id);
    if ($existing) {
        return ['status' => 'error', 'message' => 'Brand name already exists'];
    }

    $result = $brand->update_brand($brand_id, $brand_name, $category_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Brand updated successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to update brand'];
    }
}

// Delete brand
function delete_brand_ctr($brand_id) {
    $brand = new Brand();
    $result = $brand->delete_brand($brand_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Brand deleted successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to delete brand'];
    }
}
?>