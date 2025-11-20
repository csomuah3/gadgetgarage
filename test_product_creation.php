<?php
// Test script to verify product creation functionality
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/controllers/product_controller.php';
require_once __DIR__ . '/controllers/category_controller.php';
require_once __DIR__ . '/controllers/brand_controller.php';

echo "<h2>Testing Product Creation System</h2>\n";

// Test 1: Check if categories exist
echo "<h3>Test 1: Categories</h3>\n";
try {
    $categories = get_all_categories_ctr();
    if ($categories) {
        echo "<p style='color: green;'>✓ Categories loaded: " . count($categories) . " categories found</p>\n";
        foreach (array_slice($categories, 0, 3) as $cat) {
            echo "<p>- Category: " . htmlspecialchars($cat['cat_name']) . " (ID: " . $cat['cat_id'] . ")</p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ No categories found</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading categories: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 2: Check if brands exist
echo "<h3>Test 2: Brands</h3>\n";
try {
    $brands = get_all_brands_ctr();
    if ($brands) {
        echo "<p style='color: green;'>✓ Brands loaded: " . count($brands) . " brands found</p>\n";
        foreach (array_slice($brands, 0, 3) as $brand) {
            echo "<p>- Brand: " . htmlspecialchars($brand['brand_name']) . " (ID: " . $brand['brand_id'] . ")</p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ No brands found</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading brands: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 3: Test product creation (without file upload)
echo "<h3>Test 3: Product Creation</h3>\n";
try {
    if (!empty($categories) && !empty($brands)) {
        $test_title = "Test Product " . time();
        $test_price = 99.99;
        $test_desc = "This is a test product created to verify the system functionality.";
        $test_image = ""; // No image for this test
        $test_keywords = "test, verification, system";
        $test_color = "Black";
        $test_category = $categories[0]['cat_id'];
        $test_brand = $brands[0]['brand_id'];
        $test_stock = 10;

        $result = add_product_ctr(
            $test_title,
            $test_price,
            $test_desc,
            $test_image,
            $test_keywords,
            $test_color,
            $test_category,
            $test_brand,
            $test_stock
        );

        if ($result['status'] === 'success') {
            echo "<p style='color: green;'>✓ Product created successfully!</p>\n";
            echo "<p>Product ID: " . $result['product_id'] . "</p>\n";
            echo "<p>Message: " . htmlspecialchars($result['message']) . "</p>\n";

            // Clean up - delete the test product
            $delete_result = delete_product_ctr($result['product_id']);
            if ($delete_result['status'] === 'success') {
                echo "<p style='color: blue;'>✓ Test product cleaned up successfully</p>\n";
            }
        } else {
            echo "<p style='color: red;'>✗ Product creation failed: " . htmlspecialchars($result['message']) . "</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Cannot test product creation - missing categories or brands</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error during product creation test: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 4: Check database structure
echo "<h3>Test 4: Database Structure</h3>\n";
try {
    require_once __DIR__ . '/classes/product_class.php';
    $product = new Product();

    // Check if required columns exist
    $check_stock = "SHOW COLUMNS FROM products LIKE 'stock_quantity'";
    $stock_exists = $product->db_fetch_one($check_stock);

    $check_color = "SHOW COLUMNS FROM products LIKE 'product_color'";
    $color_exists = $product->db_fetch_one($check_color);

    echo "<p style='color: " . ($stock_exists ? "green" : "orange") . ";'>" .
         ($stock_exists ? "✓" : "⚠") . " stock_quantity column: " .
         ($stock_exists ? "exists" : "missing") . "</p>\n";

    echo "<p style='color: " . ($color_exists ? "green" : "orange") . ";'>" .
         ($color_exists ? "✓" : "⚠") . " product_color column: " .
         ($color_exists ? "exists" : "missing") . "</p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking database structure: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h3>Test Summary</h3>\n";
echo "<p>If all tests show green checkmarks (✓), the product creation system should work properly.</p>\n";
echo "<p>Orange warnings (⚠) indicate missing optional features that won't break functionality.</p>\n";
echo "<p>Red X marks (✗) indicate critical issues that need to be fixed.</p>\n";
?>