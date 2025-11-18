<?php
require_once __DIR__ . '/../classes/brand_class.php';

// Add brand with single category
function add_brand_ctr($brand_name, $category_id, $user_id) {
    $brand = new Brand();

    // Check if brand name already exists in this specific category
    $existing = $brand->check_brand_exists($brand_name, $category_id, $user_id);
    if ($existing) {
        return ['status' => 'error', 'message' => 'Brand name already exists in this category'];
    }

    $result = $brand->add_brand($brand_name, $category_id, $user_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Brand added successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to add brand'];
    }
}

// Legacy function for backward compatibility
function add_brand_single_ctr($brand_name, $category_id, $user_id) {
    return add_brand_ctr($brand_name, $category_id, $user_id);
}

// Get all brands for a user
function get_brands_ctr($user_id) {
    $brand = new Brand();
    return $brand->get_brands_by_user($user_id);
}

// Get all brands (for admin)
function get_all_brands_ctr() {
    $brand = new Brand();
    // Use a custom query to get brands with category information
    $sql = "SELECT b.brand_id, b.brand_name, b.category_id, c.cat_name
            FROM brands b
            LEFT JOIN categories c ON b.category_id = c.cat_id
            ORDER BY b.brand_name";
    return $brand->db_fetch_all($sql);
}

// Get brand by ID
function get_brand_by_id_ctr($brand_id) {
    $brand = new Brand();
    return $brand->get_brand_by_id($brand_id);
}

// Update brand with single category
function update_brand_ctr($brand_id, $brand_name, $category_id, $user_id) {
    $brand = new Brand();

    // Check if brand name already exists in this specific category (excluding current brand)
    $existing = $brand->check_brand_exists($brand_name, $category_id, $user_id, $brand_id);
    if ($existing) {
        return ['status' => 'error', 'message' => 'Brand name already exists in this category'];
    }

    $result = $brand->update_brand($brand_id, $brand_name, $category_id);
    if ($result) {
        return ['status' => 'success', 'message' => 'Brand updated successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to update brand'];
    }
}

// Legacy function for backward compatibility
function update_brand_single_ctr($brand_id, $brand_name, $category_id, $user_id) {
    return update_brand_ctr($brand_id, $brand_name, $category_id, $user_id);
}

// Delete brand
function delete_brand_ctr($brand_id) {
    $brand = new Brand();
    $result = $brand->delete_brand($brand_id);

    // Check if result is an array (new format) or boolean (old format)
    if (is_array($result)) {
        return $result; // Return the detailed response from the brand class
    } else if ($result) {
        return ['status' => 'success', 'message' => 'Brand deleted successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to delete brand'];
    }
}

// Get brands by category
function get_brands_by_category_ctr($category_id) {
    $brand = new Brand();
    return $brand->get_brands_by_category($category_id);
}

// Get categories for a specific brand
function get_brand_categories_ctr($brand_id) {
    $brand = new Brand();
    return $brand->get_brand_categories($brand_id);
}

// Add category to existing brand
function add_brand_category_ctr($brand_id, $category_id) {
    $brand = new Brand();
    $result = $brand->add_brand_category($brand_id, $category_id);

    if ($result) {
        return ['status' => 'success', 'message' => 'Category added to brand successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to add category to brand'];
    }
}

// Remove category from brand
function remove_brand_category_ctr($brand_id, $category_id) {
    $brand = new Brand();
    $result = $brand->remove_brand_category($brand_id, $category_id);

    if ($result) {
        return ['status' => 'success', 'message' => 'Category removed from brand successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to remove category from brand'];
    }
}
?>