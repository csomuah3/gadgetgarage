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
    // Get user ID from session
    $user_id = $_SESSION['user_id'];

    // Invoke the relevant function from the category controller to fetch all categories created by user
    $categories = get_user_categories_ctr($user_id);

    // Return success response with the categories to the caller
    echo json_encode([
        'success' => true,
        'data' => $categories,
        'message' => 'Categories fetched successfully',
        'count' => count($categories)
    ]);
} catch (Exception $e) {
    // Return error response to the caller
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching categories: ' . $e->getMessage()
    ]);
}
