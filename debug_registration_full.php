<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Comprehensive Registration Debug</h2>";

// Test 1: Check if all required files exist
echo "<h3>1. File Existence Check</h3>";
$files = [
    'settings/db_cred.php' => __DIR__ . '/settings/db_cred.php',
    'settings/db_class.php' => __DIR__ . '/settings/db_class.php',
    'classes/user_class.php' => __DIR__ . '/classes/user_class.php',
    'controllers/user_controller.php' => __DIR__ . '/controllers/user_controller.php'
];

foreach ($files as $name => $path) {
    echo $name . ": " . (file_exists($path) ? "✅ EXISTS" : "❌ MISSING") . "<br>";
}

// Test 2: Database connection
echo "<h3>2. Database Connection Test</h3>";
try {
    require_once __DIR__ . '/settings/db_class.php';
    $db = new db_connection();
    if ($db->db_connect()) {
        echo "✅ Database connected successfully<br>";
        echo "Database: " . DATABASE . "<br>";
        echo "Server: " . SERVER . "<br>";
        echo "Username: " . USERNAME . "<br>";
    } else {
        echo "❌ Database connection failed<br>";
        echo "MySQL error: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check customer table structure
echo "<h3>3. Customer Table Structure</h3>";
try {
    $result = mysqli_query($db->db, "DESCRIBE customer");
    if ($result) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['Extra'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// Test 4: Test User class instantiation
echo "<h3>4. User Class Test</h3>";
try {
    require_once __DIR__ . '/classes/user_class.php';
    $user = new User();
    echo "✅ User class instantiated<br>";
} catch (Exception $e) {
    echo "❌ User class error: " . $e->getMessage() . "<br>";
}

// Test 5: Test controller
echo "<h3>5. Controller Test</h3>";
try {
    require_once __DIR__ . '/controllers/user_controller.php';
    echo "✅ Controller loaded<br>";
    echo "register_user_ctr function exists: " . (function_exists('register_user_ctr') ? "✅" : "❌") . "<br>";
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "<br>";
}

// Test 6: Actual registration test
echo "<h3>6. Registration Test</h3>";
$test_email = 'debug_test_' . time() . '@example.com';
$test_data = [
    'name' => 'Debug Test User',
    'email' => $test_email,
    'password' => 'testpass123',
    'phone_number' => '0123456789',
    'country' => 'Ghana',
    'city' => 'Accra',
    'role' => 1
];

echo "<h4>Test data:</h4>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

try {
    echo "<h4>Calling register_user_ctr...</h4>";
    $result = register_user_ctr(
        $test_data['name'],
        $test_data['email'],
        $test_data['password'],
        $test_data['phone_number'],
        $test_data['country'],
        $test_data['city'],
        $test_data['role']
    );

    echo "<h4>Registration result:</h4>";
    echo "<pre>" . print_r($result, true) . "</pre>";

    if (is_array($result) && isset($result['status'])) {
        if ($result['status'] === 'success') {
            echo "<h4 style='color: green'>✅ REGISTRATION SUCCESSFUL!</h4>";

            // Verify user was created
            $check_sql = "SELECT * FROM customer WHERE customer_email = '" . mysqli_real_escape_string($db->db, $test_email) . "'";
            $check_result = mysqli_query($db->db, $check_sql);

            if ($check_result && mysqli_num_rows($check_result) > 0) {
                $created_user = mysqli_fetch_assoc($check_result);
                echo "<h4>✅ User verified in database:</h4>";
                echo "<pre>" . print_r($created_user, true) . "</pre>";
            } else {
                echo "<h4>❌ User not found in database after successful registration</h4>";
            }
        } else {
            echo "<h4 style='color: red'>❌ REGISTRATION FAILED</h4>";
            echo "Error: " . ($result['message'] ?? 'Unknown error') . "<br>";
        }
    } else {
        echo "<h4 style='color: red'>❌ UNEXPECTED RESULT FORMAT</h4>";
        echo "Result type: " . gettype($result) . "<br>";
        echo "Result: " . print_r($result, true) . "<br>";
    }

} catch (Exception $e) {
    echo "<h4 style='color: red'>❌ REGISTRATION EXCEPTION</h4>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 7: Direct SQL test
echo "<h3>7. Direct SQL Insert Test</h3>";
$direct_test_email = 'direct_test_' . time() . '@example.com';
$hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);

$direct_sql = "INSERT INTO customer (
    customer_name,
    customer_email,
    customer_pass,
    customer_contact,
    user_role,
    customer_country,
    customer_city,
    sms_notifications,
    sms_marketing,
    phone_verified
) VALUES (
    'Direct Test User',
    '$direct_test_email',
    '$hashed_password',
    '0123456789',
    1,
    'Ghana',
    'Accra',
    1,
    1,
    0
)";

echo "<h4>Direct SQL:</h4>";
echo "<pre>" . htmlspecialchars($direct_sql) . "</pre>";

if (mysqli_query($db->db, $direct_sql)) {
    $insert_id = mysqli_insert_id($db->db);
    echo "<h4 style='color: green'>✅ DIRECT SQL INSERT SUCCESSFUL</h4>";
    echo "Insert ID: " . $insert_id . "<br>";
} else {
    echo "<h4 style='color: red'>❌ DIRECT SQL INSERT FAILED</h4>";
    echo "MySQL Error: " . mysqli_error($db->db) . "<br>";
    echo "MySQL Error Number: " . mysqli_errno($db->db) . "<br>";
}
?>