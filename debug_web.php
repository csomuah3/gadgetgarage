<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Debug - Hosted Server</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Registration Debug - Hosted Server</h1>

    <?php
    // Test 1: Basic file checks
    echo "<div class='test-section'>";
    echo "<h2>1. File System Check</h2>";

    $files = [
        'settings/db_cred.php',
        'settings/db_class.php',
        'classes/user_class.php',
        'controllers/user_controller.php',
        'actions/register_user_action.php'
    ];

    foreach ($files as $file) {
        $exists = file_exists($file);
        echo "<p class='" . ($exists ? 'success' : 'error') . "'>";
        echo $file . ": " . ($exists ? "✅ EXISTS" : "❌ MISSING");
        echo "</p>";
    }
    echo "</div>";

    // Test 2: Database connection
    echo "<div class='test-section'>";
    echo "<h2>2. Database Connection</h2>";

    try {
        require_once 'settings/db_class.php';
        $db = new db_connection();

        if ($db->db_connect()) {
            echo "<p class='success'>✅ Database connected successfully</p>";

            // Test customer table
            $result = $db->db->query("SELECT COUNT(*) as count FROM customer");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<p class='success'>✅ Customer table accessible (contains {$row['count']} records)</p>";
            }
        } else {
            echo "<p class='error'>❌ Database connection failed</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";

    // Test 3: Quick registration test
    echo "<div class='test-section'>";
    echo "<h2>3. Registration Test</h2>";

    try {
        require_once 'controllers/user_controller.php';

        $test_email = 'webdebug_' . time() . '@test.com';

        echo "<p>Testing registration with email: <strong>$test_email</strong></p>";

        $result = register_user_ctr(
            'Web Debug User',
            $test_email,
            'testpass123',
            '0123456789',
            'Ghana',
            'Accra',
            1
        );

        echo "<h3>Registration Result:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";

        if (is_array($result) && $result['status'] === 'success') {
            echo "<p class='success'>✅ Registration successful!</p>";
        } else {
            echo "<p class='error'>❌ Registration failed</p>";
            if (isset($result['message'])) {
                echo "<p class='error'>Error message: " . htmlspecialchars($result['message']) . "</p>";
            }
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ Registration exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";

    // Test 4: Form simulation
    echo "<div class='test-section'>";
    echo "<h2>4. Form Simulation Test</h2>";
    echo "<p>This simulates what happens when the registration form is submitted:</p>";

    // Simulate POST data
    $_POST = [
        'name' => 'Form Test User',
        'email' => 'formtest_' . time() . '@test.com',
        'password' => 'testpass123',
        'phone_number' => '0123456789',
        'country' => 'Ghana',
        'city' => 'Accra',
        'role' => '1'
    ];

    echo "<h3>Simulated POST data:</h3>";
    echo "<pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre>";

    // Start output buffering like the action file does
    ob_start();

    try {
        // Include the action file logic without the exit statements
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone_number = trim($_POST['phone_number'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $role = (int)($_POST['role'] ?? 1);

        echo "<p>Data extracted successfully</p>";

        // Validation
        if (empty($name) || empty($email) || empty($password) || empty($phone_number) || empty($country) || empty($city)) {
            throw new Exception("Please fill in all fields");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long");
        }
        if (!preg_match('/^[0-9+\-\s()]+$/', $phone_number)) {
            throw new Exception("Invalid phone number format");
        }
        if (!in_array($role, [1, 2], true)) {
            throw new Exception("Invalid role selected");
        }

        echo "<p class='success'>✅ All validation passed</p>";

        // Try registration
        $res = register_user_ctr($name, $email, $password, $phone_number, $country, $city, $role);

        echo "<h3>Form Registration Result:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($res, true)) . "</pre>";

        if (is_array($res) && $res['status'] === 'success') {
            echo "<p class='success'>✅ Form simulation successful!</p>";
        } else {
            echo "<p class='error'>❌ Form simulation failed</p>";
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ Form simulation error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    ob_end_clean();
    echo "</div>";
    ?>

    <div class='test-section'>
        <h2>5. Next Steps</h2>
        <p>If the tests above show success but the actual form still fails, the issue might be:</p>
        <ul>
            <li>JavaScript/AJAX communication problems</li>
            <li>Browser console errors</li>
            <li>Response format issues</li>
            <li>Session handling problems</li>
        </ul>
        <p><strong>To check:</strong> Open browser developer tools (F12) when submitting the form and look at the Network tab for the actual server response.</p>
    </div>

</body>
</html>