<?php
session_start();
require_once 'settings/core.php';
require_once 'controllers/product_controller.php';

// Check login
if (!check_login()) {
    echo "<p style='color: red;'>❌ Please log in as admin to test product updates</p>";
    echo "<a href='login/login.php'>Login here</a>";
    exit();
}

echo "<h2>Product Update Test</h2>";

// Get a test product
try {
    $products = get_all_products_ctr();
    if (empty($products)) {
        echo "<p style='color: red;'>❌ No products found in database</p>";
        exit();
    }

    $test_product = $products[0];
    $product_id = $test_product['product_id'];

    echo "<h3>Testing Product Update for Product ID: $product_id</h3>";
    echo "<p><strong>Current Product:</strong> " . htmlspecialchars($test_product['product_title']) . "</p>";

    // Test update with minimal changes
    $new_title = $test_product['product_title'] . " (Updated " . date('H:i:s') . ")";

    echo "<h4>Test Parameters:</h4>";
    $test_params = [
        'product_id' => $product_id,
        'product_title' => $new_title,
        'product_price' => $test_product['product_price'],
        'product_desc' => $test_product['product_desc'] ?: 'Test description',
        'product_image' => $test_product['product_image'],
        'product_keywords' => $test_product['product_keywords'] ?: 'test',
        'category_id' => $test_product['product_cat'],
        'brand_id' => $test_product['product_brand'],
        'product_color' => '',
        'stock_quantity' => $test_product['stock_quantity'] ?: 10
    ];

    echo "<ul>";
    foreach ($test_params as $key => $value) {
        echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
    }
    echo "</ul>";

    // Test the update
    echo "<h4>Testing Update...</h4>";
    $result = update_product_ctr(
        $test_params['product_id'],
        $test_params['product_title'],
        $test_params['product_price'],
        $test_params['product_desc'],
        $test_params['product_image'],
        $test_params['product_keywords'],
        $test_params['category_id'],
        $test_params['brand_id'],
        $test_params['product_color'],
        $test_params['stock_quantity']
    );

    echo "<h4>Result:</h4>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if ($result['status'] === 'success') {
        echo "<p style='color: green;'>✅ Product update successful!</p>";

        // Verify the change
        $updated_product = get_product_by_id_ctr($product_id);
        if ($updated_product && $updated_product['product_title'] === $new_title) {
            echo "<p style='color: green;'>✅ Verification: Title was updated correctly</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Warning: Title may not have been updated in database</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Product update failed: " . htmlspecialchars($result['message']) . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

echo "<h3>Manual Form Test</h3>";
echo "<p>Use this form to manually test the update action:</p>";

if (!empty($products)) {
    $test_product = $products[0];
?>
<form method="POST" action="actions/update_product_action.php" enctype="multipart/form-data">
    <table border="1" style="border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td><strong>Product ID:</strong></td>
            <td><input type="number" name="product_id" value="<?= $test_product['product_id'] ?>" readonly style="background: #f0f0f0;"></td>
        </tr>
        <tr>
            <td><strong>Title:</strong></td>
            <td><input type="text" name="product_title" value="<?= htmlspecialchars($test_product['product_title'] . ' (Manual Test)') ?>" style="width: 300px;"></td>
        </tr>
        <tr>
            <td><strong>Price:</strong></td>
            <td><input type="number" step="0.01" name="product_price" value="<?= $test_product['product_price'] ?>"></td>
        </tr>
        <tr>
            <td><strong>Description:</strong></td>
            <td><textarea name="product_desc" style="width: 300px; height: 100px;"><?= htmlspecialchars($test_product['product_desc'] ?: 'Test description') ?></textarea></td>
        </tr>
        <tr>
            <td><strong>Keywords:</strong></td>
            <td><input type="text" name="product_keywords" value="<?= htmlspecialchars($test_product['product_keywords'] ?: 'test, keywords') ?>" style="width: 300px;"></td>
        </tr>
        <tr>
            <td><strong>Category ID:</strong></td>
            <td><input type="number" name="category_id" value="<?= $test_product['product_cat'] ?>"></td>
        </tr>
        <tr>
            <td><strong>Brand ID:</strong></td>
            <td><input type="number" name="brand_id" value="<?= $test_product['product_brand'] ?>"></td>
        </tr>
        <tr>
            <td><strong>Color:</strong></td>
            <td><input type="text" name="product_color" value=""></td>
        </tr>
        <tr>
            <td><strong>Stock Quantity:</strong></td>
            <td><input type="number" name="stock_quantity" value="<?= $test_product['stock_quantity'] ?: 10 ?>"></td>
        </tr>
        <tr>
            <td><strong>Image:</strong></td>
            <td><input type="file" name="product_image" accept="image/*"> (Optional - leave empty to keep current)</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center;">
                <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;">Test Update</button>
            </td>
        </tr>
    </table>
</form>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('actions/update_product_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert('Result: ' + JSON.stringify(data, null, 2));
        console.log('Update result:', data);
    })
    .catch(error => {
        alert('Error: ' + error.message);
        console.error('Error:', error);
    });
});
</script>

<?php
}

echo "<h3>Error Log Check</h3>";
echo "<p>Check your PHP error log for detailed debugging information:</p>";
echo "<p>Common locations: /Applications/XAMPP/logs/error_log or /var/log/apache2/error.log</p>";
echo "<p>Look for entries containing: 'Product update', 'Update product error', or 'db_prepare_execute'</p>";

echo "<p><a href='admin/product.php'>← Back to Admin Products</a></p>";
?>