<?php
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/classes/brand_class.php';

header('Content-Type: text/plain');

echo "=== DEBUGGING BRANDS TABLE ===\n\n";

try {
    $brand = new Brand();

    // Check table structure
    echo "1. BRANDS TABLE STRUCTURE:\n";
    $columns = $brand->db_fetch_all("SHOW COLUMNS FROM brands");
    foreach ($columns as $column) {
        echo "Column: {$column['Field']} | Type: {$column['Type']} | Key: {$column['Key']}\n";
    }

    echo "\n2. EXISTING BRANDS:\n";
    $existing_brands = $brand->db_fetch_all("SELECT * FROM brands LIMIT 5");
    foreach ($existing_brands as $brand_row) {
        print_r($brand_row);
    }

    echo "\n3. CATEGORIES TABLE STRUCTURE:\n";
    $cat_columns = $brand->db_fetch_all("SHOW COLUMNS FROM categories");
    foreach ($cat_columns as $column) {
        echo "Column: {$column['Field']} | Type: {$column['Type']} | Key: {$column['Key']}\n";
    }

    echo "\n4. EXISTING CATEGORIES:\n";
    $existing_cats = $brand->db_fetch_all("SELECT * FROM categories LIMIT 5");
    foreach ($existing_cats as $cat_row) {
        print_r($cat_row);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>