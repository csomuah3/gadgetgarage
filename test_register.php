<?php
// Simple test script to debug registration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Registration Process</h2>";

// Test 1: Check if files exist
$required_files = [
    __DIR__ . '/settings/db_cred.php',
    __DIR__ . '/settings/db_class.php',
    __DIR__ . '/classes/user_class.php',
    __DIR__ . '/controllers/user_controller.php',
    __DIR__ . '/actions/register_user_action.php'
];

echo "<h3>1. File Existence Check:</h3>";
foreach ($required_files as $file) {
    $exists = file_exists($file);
    $relative_path = str_replace(__DIR__ . '/', '', $file);
    echo "<li>$relative_path: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "</li>";
}

// Test 2: Database connection
echo "<h3>2. Database Connection Test:</h3>";
try {
    require_once __DIR__ . '/settings/db_class.php';
    $db = new db_connection();
    if ($db->db_connect()) {
        echo "✅ Database connection successful<br>";

        // Test table existence
        $result = mysqli_query($db->db, "SHOW TABLES LIKE 'customer'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ Customer table exists<br>";

            // Check table structure
            $describe = mysqli_query($db->db, "DESCRIBE customer");
            echo "<h4>Customer table structure:</h4><ul>";
            while ($row = mysqli_fetch_assoc($describe)) {
                echo "<li>" . $row['Field'] . " - " . $row['Type'] .
                     ($row['Null'] == 'NO' ? " (Required)" : " (Optional)") . "</li>";
            }
            echo "</ul>";
        } else {
            echo "❌ Customer table does not exist<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: User class instantiation
echo "<h3>3. User Class Test:</h3>";
try {
    require_once __DIR__ . '/classes/user_class.php';
    $user = new User();
    echo "✅ User class instantiated successfully<br>";
} catch (Exception $e) {
    echo "❌ User class error: " . $e->getMessage() . "<br>";
}

// Test 4: Controller function test
echo "<h3>4. Controller Function Test:</h3>";
try {
    require_once __DIR__ . '/controllers/user_controller.php';
    echo "✅ User controller loaded successfully<br>";

    // Test with sample data (but don't actually register)
    echo "Controller functions available: " . (function_exists('register_user_ctr') ? "✅" : "❌") . " register_user_ctr<br>";
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "<br>";
}

echo "<h3>5. Registration Action File Test:</h3>";
echo "Action file exists: " . (file_exists(__DIR__ . '/actions/register_user_action.php') ? "✅" : "❌") . "<br>";
echo "Action file is readable: " . (is_readable(__DIR__ . '/actions/register_user_action.php') ? "✅" : "❌") . "<br>";

?>