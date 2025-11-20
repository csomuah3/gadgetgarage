<?php
require_once __DIR__ . '/controllers/brand_controller.php';

echo "<h2>Testing Brands Loading</h2>\n";

try {
    $brands = get_all_brands_ctr();

    if ($brands) {
        echo "<p style='color: green;'>✓ Brands loaded successfully: " . count($brands) . " brands found</p>\n";
        echo "<table border='1'><tr><th>Brand ID</th><th>Brand Name</th><th>Category ID</th><th>Category Name</th></tr>\n";

        foreach (array_slice($brands, 0, 5) as $brand) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($brand['brand_id']) . "</td>";
            echo "<td>" . htmlspecialchars($brand['brand_name']) . "</td>";
            echo "<td>" . htmlspecialchars($brand['category_id'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($brand['cat_name'] ?? 'N/A') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p style='color: red;'>✗ No brands found or error loading brands</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading brands: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>