<?php

/**
 * Fetch Category Action
 * A script that invokes the relevant function from the category controller 
 * to fetch all the categories created by a user from the system and returns those to the caller
 */

// Include core functions
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/category_controller.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit();
}

// Check if user is admin
if (!check_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Admin privileges required.'
    ]);
    exit();
}

try {
    // For brand management, get all categories (not just user-specific)
    $categories = get_all_categories_ctr();

    // Return success response with both formats for compatibility
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'data' => $categories,
        'message' => 'Categories fetched successfully',
        'count' => count($categories)
    ]);
} catch (Exception $e) {
    // Return error response to the caller
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Error fetching categories: ' . $e->getMessage()
    ]);
}
