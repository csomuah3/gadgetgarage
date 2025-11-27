<?php
session_start();
require_once 'settings/core.php';
require_once 'controllers/wishlist_controller.php';
require_once 'controllers/product_controller.php';

$is_logged_in = check_login();

echo "<h2>Wishlist Functionality Test</h2>";

if (!$is_logged_in) {
    echo "<p style='color: red;'>❌ Please log in to test wishlist functionality</p>";
    echo "<a href='login/login.php'>Login here</a>";
    exit();
}

$customer_id = $_SESSION['user_id'];
echo "<p>✅ User logged in: Customer ID = $customer_id</p>";

// Test functions exist
echo "<h3>Function Tests:</h3>";
echo "<ul>";
echo "<li>add_to_wishlist_ctr: " . (function_exists('add_to_wishlist_ctr') ? '✅ EXISTS' : '❌ MISSING') . "</li>";
echo "<li>remove_from_wishlist_ctr: " . (function_exists('remove_from_wishlist_ctr') ? '✅ EXISTS' : '❌ MISSING') . "</li>";
echo "<li>check_wishlist_item_ctr: " . (function_exists('check_wishlist_item_ctr') ? '✅ EXISTS' : '❌ MISSING') . "</li>";
echo "<li>get_wishlist_count_ctr: " . (function_exists('get_wishlist_count_ctr') ? '✅ EXISTS' : '❌ MISSING') . "</li>";
echo "</ul>";

// Get some test product IDs
try {
    $products = get_all_products_ctr();
    $test_product_id = !empty($products) ? $products[0]['product_id'] : 1;
    echo "<p>Using test product ID: $test_product_id</p>";

    // Test wishlist count
    $wishlist_count = get_wishlist_count_ctr($customer_id);
    echo "<p>Current wishlist count: $wishlist_count</p>";

    // Test check item
    $is_in_wishlist = check_wishlist_item_ctr($test_product_id, $customer_id);
    echo "<p>Product $test_product_id in wishlist: " . ($is_in_wishlist ? 'YES' : 'NO') . "</p>";

    // Test add to wishlist
    echo "<h3>Testing Add to Wishlist:</h3>";
    if (!$is_in_wishlist) {
        $result = add_to_wishlist_ctr($test_product_id, $customer_id, $_SERVER['REMOTE_ADDR']);
        echo "<p>Add result: " . ($result ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
    } else {
        echo "<p>Product already in wishlist, testing remove instead...</p>";
        $result = remove_from_wishlist_ctr($test_product_id, $customer_id);
        echo "<p>Remove result: " . ($result ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
    }

    // Check updated count
    $new_count = get_wishlist_count_ctr($customer_id);
    echo "<p>Updated wishlist count: $new_count</p>";

    // Show current wishlist items
    echo "<h3>Current Wishlist Items:</h3>";
    $wishlist_items = get_wishlist_items_ctr($customer_id);
    if (empty($wishlist_items)) {
        echo "<p>No items in wishlist</p>";
    } else {
        echo "<ul>";
        foreach ($wishlist_items as $item) {
            echo "<li>Product ID: " . $item['product_id'] . " - " . htmlspecialchars($item['product_title'] ?? 'Unknown Product') . "</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Manual Test Buttons:</h3>";
echo "<p>Use these buttons to manually test the wishlist functionality:</p>";
?>

<div style="margin: 20px 0;">
    <button onclick="testAddToWishlist()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; margin: 5px;">
        Test Add to Wishlist
    </button>
    <button onclick="testRemoveFromWishlist()" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 5px; margin: 5px;">
        Test Remove from Wishlist
    </button>
    <button onclick="checkWishlistStatus()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; margin: 5px;">
        Check Wishlist Status
    </button>
</div>

<div id="test-result" style="padding: 10px; background: #f3f4f6; border-radius: 5px; margin: 20px 0;"></div>

<script>
const TEST_PRODUCT_ID = <?php echo $test_product_id; ?>;

async function testAddToWishlist() {
    const result = document.getElementById('test-result');
    result.innerHTML = 'Testing add to wishlist...';

    try {
        const response = await fetch('actions/add_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + TEST_PRODUCT_ID
        });

        const data = await response.json();
        result.innerHTML = `<strong>Add to Wishlist Result:</strong><br>
                           Success: ${data.success}<br>
                           Message: ${data.message}<br>
                           Count: ${data.count || 'N/A'}`;
        result.style.background = data.success ? '#d1fae5' : '#fee2e2';
    } catch (error) {
        result.innerHTML = `<strong>Error:</strong> ${error.message}`;
        result.style.background = '#fee2e2';
    }
}

async function testRemoveFromWishlist() {
    const result = document.getElementById('test-result');
    result.innerHTML = 'Testing remove from wishlist...';

    try {
        const response = await fetch('actions/remove_from_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + TEST_PRODUCT_ID
        });

        const data = await response.json();
        result.innerHTML = `<strong>Remove from Wishlist Result:</strong><br>
                           Success: ${data.success}<br>
                           Message: ${data.message}<br>
                           Count: ${data.count || 'N/A'}`;
        result.style.background = data.success ? '#d1fae5' : '#fee2e2';
    } catch (error) {
        result.innerHTML = `<strong>Error:</strong> ${error.message}`;
        result.style.background = '#fee2e2';
    }
}

async function checkWishlistStatus() {
    const result = document.getElementById('test-result');
    result.innerHTML = 'Checking wishlist status...';

    try {
        const response = await fetch('actions/get_wishlist_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + TEST_PRODUCT_ID
        });

        const data = await response.json();
        result.innerHTML = `<strong>Wishlist Status:</strong><br>
                           Product ${TEST_PRODUCT_ID} in wishlist: ${data.in_wishlist ? 'YES' : 'NO'}<br>
                           Total wishlist count: ${data.count || 'N/A'}`;
        result.style.background = '#e0f2fe';
    } catch (error) {
        result.innerHTML = `<strong>Error:</strong> ${error.message}`;
        result.style.background = '#fee2e2';
    }
}
</script>

<p><a href="index.php">← Back to Home</a> | <a href="views/wishlist.php">View Wishlist Page</a></p>