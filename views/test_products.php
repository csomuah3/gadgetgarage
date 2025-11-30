<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/product_controller.php');
require_once(__DIR__ . '/../controllers/category_controller.php');

echo "<h1>Product Debug Test</h1>";

// Test getting all products
$all_products = get_all_products_ctr();
echo "<h2>All Products Count: " . count($all_products) . "</h2>";

if (!empty($all_products)) {
    echo "<h3>First 5 Products:</h3>";
    echo "<pre>";
    print_r(array_slice($all_products, 0, 5));
    echo "</pre>";
}

// Test getting categories
$categories = get_all_categories_ctr();
echo "<h2>All Categories Count: " . count($categories) . "</h2>";

if (!empty($categories)) {
    echo "<h3>All Categories:</h3>";
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
}

// Test getting products by category
foreach ($categories as $cat) {
    $cat_id = $cat['cat_id'];
    $cat_name = $cat['cat_name'];
    $products = get_products_by_category_ctr($cat_id);
    echo "<h3>Category: $cat_name (ID: $cat_id) - Products: " . count($products) . "</h3>";
    if (!empty($products)) {
        echo "<pre>";
        print_r(array_slice($products, 0, 2));
        echo "</pre>";
    }
}
?>

