<?php
/**
 * Debug script to check product images
 * Access this file directly in your browser to see product image information
 */
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/controllers/product_controller.php');
require_once(__DIR__ . '/helpers/image_helper.php');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Images Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #008060; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
        img { max-width: 100px; max-height: 100px; }
    </style>
</head>
<body>
    <h1>Product Images Debug Report</h1>
    <p>This page shows all products and their image information.</p>
    
    <?php
    $products = get_all_products_ctr();
    
    if (empty($products)) {
        echo "<p class='error'>No products found in database.</p>";
    } else {
        echo "<p>Found " . count($products) . " products.</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Image Field Value</th><th>Generated URL</th><th>Image Preview</th><th>Status</th></tr>";
        
        foreach ($products as $product) {
            $product_id = $product['product_id'] ?? 'N/A';
            $product_title = $product['product_title'] ?? 'N/A';
            $product_image_field = $product['product_image'] ?? 'EMPTY';
            $image_url = get_product_image_url($product_image_field, $product_title);
            
            // Check if it's a data URI (placeholder) or actual URL
            $is_placeholder = strpos($image_url, 'data:image') === 0;
            $status = $is_placeholder ? '<span class="error">Using Placeholder</span>' : '<span class="success">Has Image URL</span>';
            
            echo "<tr>";
            echo "<td>{$product_id}</td>";
            echo "<td>" . htmlspecialchars($product_title) . "</td>";
            echo "<td>" . htmlspecialchars($product_image_field) . "</td>";
            echo "<td style='word-break: break-all; max-width: 400px;'>" . htmlspecialchars($image_url) . "</td>";
            echo "<td><img src='" . htmlspecialchars($image_url) . "' alt='Preview' onerror='this.src=\"data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+RXJyb3I8L3RleHQ+PC9zdmc+\";'></td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    ?>
    
    <hr>
    <h2>Server Upload Path Check</h2>
    <p>Server Base URL: <code>http://169.239.251.102:442/~chelsea.somuah/uploads/</code></p>
    <p><strong>Note:</strong> If images are showing as placeholders, it means the <code>product_image</code> field in the database is empty or the images don't exist on the server.</p>
</body>
</html>

