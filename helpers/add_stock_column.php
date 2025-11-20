<?php
// Add stock quantity column to products table
require_once __DIR__ . '/../settings/db_class.php';

$db = new db_connection();

try {
    // Add stock_quantity column to products table
    $sql = "ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0 AFTER product_keywords";
    $result = $db->db_query($sql);

    if ($result) {
        echo "✅ Successfully added stock_quantity column to products table\n";
    } else {
        echo "❌ Failed to add stock_quantity column\n";
    }

    // Update existing products to have some stock (optional)
    $update_sql = "UPDATE products SET stock_quantity = 10 WHERE stock_quantity = 0";
    $update_result = $db->db_query($update_sql);

    if ($update_result) {
        echo "✅ Updated existing products with default stock quantity of 10\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>