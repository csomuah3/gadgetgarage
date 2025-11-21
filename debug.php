<?php
// Debug script to identify the fatal error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: Checking file includes step by step</h2>";

echo "<p>1. Starting debug...</p>";

try {
    echo "<p>2. Including settings/core.php...</p>";
    require_once(__DIR__ . '/settings/core.php');
    echo "<p>✅ core.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error in core.php: " . $e->getMessage() . "</p>";
    exit;
}

try {
    echo "<p>3. Including controllers/cart_controller.php...</p>";
    require_once(__DIR__ . '/controllers/cart_controller.php');
    echo "<p>✅ cart_controller.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error in cart_controller.php: " . $e->getMessage() . "</p>";
    exit;
}

try {
    echo "<p>4. Including helpers/image_helper.php...</p>";
    require_once(__DIR__ . '/helpers/image_helper.php');
    echo "<p>✅ image_helper.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error in image_helper.php: " . $e->getMessage() . "</p>";
    exit;
}

try {
    echo "<p>5. Testing check_login() function...</p>";
    $is_logged_in = check_login();
    echo "<p>✅ check_login() works: " . ($is_logged_in ? 'logged in' : 'not logged in') . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error in check_login(): " . $e->getMessage() . "</p>";
    exit;
}

if ($is_logged_in) {
    try {
        echo "<p>6. Testing check_admin() function...</p>";
        $is_admin = check_admin();
        echo "<p>✅ check_admin() works: " . ($is_admin ? 'is admin' : 'not admin') . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error in check_admin(): " . $e->getMessage() . "</p>";
        exit;
    }
} else {
    echo "<p>6. Skipping check_admin() - user not logged in</p>";
}

try {
    echo "<p>7. Testing language configuration...</p>";
    require_once(__DIR__ . '/includes/language_config.php');
    echo "<p>✅ Language config loaded successfully</p>";
    echo "<p>Current language: " . $current_language . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error in language config: " . $e->getMessage() . "</p>";
    exit;
}

echo "<p>✅ All basic includes working! The error might be elsewhere in index.php</p>";
echo "<p><a href='index.php'>Try index.php again</a></p>";
?>