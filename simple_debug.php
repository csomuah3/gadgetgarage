<?php
echo "<h1>Simple Debug Test</h1>";

// Test 1: Check if we can include the core files
echo "<h2>Test 1: Including core files</h2>";
try {
    require_once __DIR__ . '/settings/core.php';
    echo "✓ Core files included successfully<br>";

    // Test database constants
    echo "Database constants:<br>";
    echo "SERVER: " . (defined('SERVER') ? SERVER : 'NOT DEFINED') . "<br>";
    echo "DATABASE: " . (defined('DATABASE') ? DATABASE : 'NOT DEFINED') . "<br>";
    echo "USERNAME: " . (defined('USERNAME') ? USERNAME : 'NOT DEFINED') . "<br>";

} catch (Exception $e) {
    echo "✗ Error including core files: " . $e->getMessage() . "<br>";
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    $pdo = new PDO("mysql:host=" . SERVER . ";dbname=" . DATABASE, USERNAME, PASSWD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful<br>";

    // Test if orders table exists and has data
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total orders in database: " . $result['count'] . "<br>";

    if ($result['count'] > 0) {
        $stmt = $pdo->prepare("SELECT order_id, invoice_no, customer_id, order_date FROM orders ORDER BY order_date DESC LIMIT 3");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Recent orders:</h3>";
        foreach ($orders as $order) {
            echo "ID: {$order['order_id']}, Invoice: {$order['invoice_no']}, Customer: {$order['customer_id']}, Date: {$order['order_date']}<br>";
        }
    }

} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check if tracking function exists
echo "<h2>Test 3: Tracking Function</h2>";
try {
    require_once __DIR__ . '/controllers/order_controller.php';
    echo "✓ Order controller loaded<br>";

    if (function_exists('get_order_tracking_details')) {
        echo "✓ get_order_tracking_details function exists<br>";
    } else {
        echo "✗ get_order_tracking_details function NOT found<br>";
    }

} catch (Exception $e) {
    echo "✗ Error loading order controller: " . $e->getMessage() . "<br>";
}

echo "<h2>Test 4: Test URL</h2>";
echo "Access this page with an order number like: <br>";
echo "<a href='?test_order=123'>simple_debug.php?test_order=123</a><br>";

if (isset($_GET['test_order'])) {
    $test_order = $_GET['test_order'];
    echo "<br>Testing with order: " . htmlspecialchars($test_order) . "<br>";

    try {
        $result = get_order_tracking_details($test_order);
        if ($result) {
            echo "✓ Found order data!<br>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        } else {
            echo "✗ No order found for: " . htmlspecialchars($test_order) . "<br>";
        }
    } catch (Exception $e) {
        echo "✗ Error in tracking function: " . $e->getMessage() . "<br>";
    }
}
?>