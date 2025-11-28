<?php
// Test registration with debug output
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Registration Test</h2>";

require_once __DIR__ . '/settings/db_class.php';
require_once __DIR__ . '/classes/user_class.php';
require_once __DIR__ . '/controllers/user_controller.php';

// Test data
$test_data = [
    'name' => 'Test User ' . time(),
    'email' => 'test' . time() . '@example.com',
    'password' => 'testpass123',
    'phone_number' => '0123456789',
    'country' => 'Ghana',
    'city' => 'Accra',
    'role' => 1  // Customer role
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

echo "<h3>Testing Database Connection:</h3>";
$db = new db_connection();
if ($db->db_connect()) {
    echo "✅ Database connected<br>";

    echo "<h3>Testing User Registration:</h3>";
    $result = register_user_ctr(
        $test_data['name'],
        $test_data['email'],
        $test_data['password'],
        $test_data['phone_number'],
        $test_data['country'],
        $test_data['city'],
        $test_data['role']
    );

    echo "<h4>Registration Result:</h4>";
    echo "<pre>" . print_r($result, true) . "</pre>";

    if (is_array($result) && $result['status'] === 'success') {
        echo "<h4>✅ Registration successful!</h4>";

        // Test if we can find the user
        echo "<h3>Verifying user was created:</h3>";
        $user_check = "SELECT * FROM customer WHERE customer_email = '" . mysqli_real_escape_string($db->db, $test_data['email']) . "'";
        $user_result = mysqli_query($db->db, $user_check);

        if ($user_result && mysqli_num_rows($user_result) > 0) {
            $user = mysqli_fetch_assoc($user_result);
            echo "✅ User found in database<br>";
            echo "<pre>" . print_r($user, true) . "</pre>";
        } else {
            echo "❌ User not found in database<br>";
        }
    } else {
        echo "<h4>❌ Registration failed</h4>";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "<br>";
    }

} else {
    echo "❌ Database connection failed<br>";
}
?>