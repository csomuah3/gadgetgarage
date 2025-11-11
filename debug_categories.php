<?php
require_once __DIR__ . '/settings/db_class.php';
require_once __DIR__ . '/settings/core.php';

// Initialize session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $db = new db_connection();
    $connection = $db->db_connect();

    if (!$connection) {
        throw new Exception("Database connection failed");
    }

    $debug_info = [];

    // Check if categories table exists and has data
    $sql = "SHOW TABLES LIKE 'categories'";
    $result = $db->db_fetch_one($sql);
    $debug_info['categories_table_exists'] = !empty($result);

    if ($debug_info['categories_table_exists']) {
        // Get categories count
        $sql = "SELECT COUNT(*) as count FROM categories";
        $result = $db->db_fetch_one($sql);
        $debug_info['categories_count'] = $result['count'] ?? 0;

        // Get all categories
        $sql = "SELECT * FROM categories ORDER BY cat_name";
        $categories = $db->db_fetch_all($sql);
        $debug_info['categories'] = $categories ?: [];
    }

    // Check if brands table exists and its structure
    $sql = "SHOW TABLES LIKE 'brands'";
    $result = $db->db_fetch_one($sql);
    $debug_info['brands_table_exists'] = !empty($result);

    if ($debug_info['brands_table_exists']) {
        $sql = "SHOW COLUMNS FROM brands";
        $columns = $db->db_fetch_all($sql);
        $debug_info['brands_columns'] = array_column($columns, 'Field');

        $sql = "SELECT COUNT(*) as count FROM brands";
        $result = $db->db_fetch_one($sql);
        $debug_info['brands_count'] = $result['count'] ?? 0;
    }

    // Check if brand_categories table exists
    $sql = "SHOW TABLES LIKE 'brand_categories'";
    $result = $db->db_fetch_one($sql);
    $debug_info['brand_categories_table_exists'] = !empty($result);

    if ($debug_info['brand_categories_table_exists']) {
        $sql = "SELECT COUNT(*) as count FROM brand_categories";
        $result = $db->db_fetch_one($sql);
        $debug_info['brand_categories_count'] = $result['count'] ?? 0;
    }

    // Check session info
    $debug_info['session_user_id'] = $_SESSION['user_id'] ?? 'not_set';
    $debug_info['session_logged_in'] = isset($_SESSION['user_id']);

    // Test the actual category fetch
    require_once __DIR__ . '/controllers/category_controller.php';

    if (isset($_SESSION['user_id'])) {
        $user_categories = get_user_categories_ctr($_SESSION['user_id']);
        $debug_info['user_categories'] = $user_categories;
        $debug_info['user_categories_count'] = count($user_categories);
    } else {
        $debug_info['user_categories'] = 'user_not_logged_in';
    }

    echo json_encode([
        'success' => true,
        'debug_info' => $debug_info
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => $debug_info ?? []
    ], JSON_PRETTY_PRINT);
}
?>