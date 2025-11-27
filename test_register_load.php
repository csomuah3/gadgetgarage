<?php
// Test script to check if register.php loads without errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Register.php Load</h2>";

echo "<h3>1. Testing Core Files</h3>";

// Test core.php
try {
    require_once 'settings/core.php';
    echo "✅ core.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ core.php failed: " . $e->getMessage() . "<br>";
}

// Test database class
try {
    require_once 'settings/db_class.php';
    echo "✅ db_class.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ db_class.php failed: " . $e->getMessage() . "<br>";
}

echo "<h3>2. Testing Controller Files</h3>";

// Test category controller
try {
    require_once 'controllers/category_controller.php';
    echo "✅ category_controller.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ category_controller.php failed: " . $e->getMessage() . "<br>";
}

// Test brand controller
try {
    require_once 'controllers/brand_controller.php';
    echo "✅ brand_controller.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ brand_controller.php failed: " . $e->getMessage() . "<br>";
}

// Test cart controller
try {
    require_once 'controllers/cart_controller.php';
    echo "✅ cart_controller.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ cart_controller.php failed: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Testing Database Connection</h3>";

try {
    $db = new db_connection();
    if ($db->db_connect()) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. Testing Functions</h3>";

// Test check_login function
if (function_exists('check_login')) {
    echo "✅ check_login() function exists<br>";
} else {
    echo "❌ check_login() function missing<br>";
}

// Test category function
if (function_exists('get_all_categories_ctr')) {
    echo "✅ get_all_categories_ctr() function exists<br>";
    try {
        $categories = get_all_categories_ctr();
        echo "✅ Categories loaded: " . count($categories) . " items<br>";
    } catch (Exception $e) {
        echo "❌ Categories load failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ get_all_categories_ctr() function missing<br>";
}

echo "<h3>5. Direct Register Page Test</h3>";

echo "<p>Testing if register.php can be loaded...</p>";

// Capture output and errors
ob_start();
$error_occurred = false;

try {
    // Include the register page
    include 'login/register.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $error_occurred = true;
    $error_message = $e->getMessage();
} catch (Error $e) {
    $error_occurred = true;
    $error_message = $e->getMessage();
}

ob_end_clean();

if ($error_occurred) {
    echo "❌ Register.php failed to load: " . $error_message . "<br>";
} else {
    echo "✅ Register.php loaded successfully<br>";
    echo "<p><a href='login/register.php' target='_blank'>Click here to test register.php directly</a></p>";
}

echo "<h3>6. Manual Navigation Test</h3>";
echo "<p><a href='login/login.php'>Go to login page</a></p>";
echo "<p><a href='login/register.php'>Go to register page (direct link)</a></p>";
?>