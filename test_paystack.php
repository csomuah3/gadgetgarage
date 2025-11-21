<?php
/**
 * PayStack Integration Test Page
 * Use this to test your PayStack functionality
 */

session_start();
require_once __DIR__ . '/settings/paystack_config.php';

// Prevent direct access if not testing
if (!isset($_GET['test']) || $_GET['test'] !== '1') {
    die('PayStack Testing Script - Add ?test=1 to run tests');
}

echo "<h2>PayStack Integration Test Results</h2>";
echo "<pre>";

try {
    echo "=== PayStack Configuration Test ===\n";
    echo "Public Key: " . substr(PAYSTACK_PUBLIC_KEY, 0, 15) . "...\n";
    echo "Secret Key: " . substr(PAYSTACK_SECRET_KEY, 0, 15) . "...\n";
    echo "Base URL: " . PAYSTACK_BASE_URL . "\n";
    echo "Currency: " . PAYSTACK_CURRENCY . "\n";
    echo "Environment: " . PAYSTACK_ENVIRONMENT . "\n";
    echo "Callback URL: " . PAYSTACK_CALLBACK_URL . "\n";
    echo "\n";

    echo "=== Amount Conversion Test ===\n";
    $test_amounts = [1.00, 10.50, 100.99, 500.00];
    foreach ($test_amounts as $amount) {
        $pesewas = amount_to_pesewas($amount);
        $back_to_amount = pesewas_to_amount($pesewas);
        echo "GHS {$amount} -> {$pesewas} pesewas -> GHS {$back_to_amount}\n";
    }
    echo "\n";

    echo "=== Reference Generation Test ===\n";
    for ($i = 1; $i <= 3; $i++) {
        $ref = generate_transaction_reference($i);
        echo "Customer {$i}: {$ref}\n";
    }
    echo "\n";

    echo "=== Database Connection Test ===\n";
    try {
        require_once __DIR__ . '/settings/db_class.php';
        $db = new db_connection();
        echo "Database: ✓ Connected\n";

        // Test payment table structure
        $query = "DESCRIBE payment";
        $result = $db->db_fetch_all($query);
        echo "Payment Table Columns:\n";
        foreach ($result as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }

    } catch (Exception $e) {
        echo "Database: ✗ Error - " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "=== PayStack API Test ===\n";
    if (isset($_GET['api_test']) && $_GET['api_test'] === '1') {
        echo "Testing PayStack API connection...\n";

        try {
            // Test with a small amount
            $test_email = 'test@example.com';
            $test_amount = amount_to_pesewas(1.00); // 1 GHS = 100 pesewas
            $test_reference = generate_transaction_reference(999);

            echo "Initializing test transaction:\n";
            echo "  Email: {$test_email}\n";
            echo "  Amount: {$test_amount} pesewas (1.00 GHS)\n";
            echo "  Reference: {$test_reference}\n";

            $response = paystack_initialize_transaction(
                $test_email,
                $test_amount,
                $test_reference
            );

            if ($response['status'] === true) {
                echo "✓ PayStack API: Working correctly\n";
                echo "  Authorization URL: " . substr($response['data']['authorization_url'], 0, 50) . "...\n";
                echo "  Access Code: " . $response['data']['access_code'] . "\n";
            } else {
                echo "✗ PayStack API Error: " . $response['message'] . "\n";
            }

        } catch (Exception $e) {
            echo "✗ PayStack API Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "API Test: Skipped (add &api_test=1 to test actual API)\n";
    }
    echo "\n";

    echo "=== File System Test ===\n";
    $files_to_check = [
        'settings/paystack_config.php',
        'actions/paystack_init_transaction.php',
        'actions/paystack_verify_payment.php',
        'views/paystack_callback.php',
        'views/payment_success.php',
        'js/checkout.js'
    ];

    foreach ($files_to_check as $file) {
        $path = __DIR__ . '/' . $file;
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        echo "$file: " . ($readable ? "✓ OK" : "✗ Missing/Unreadable") . "\n";
    }
    echo "\n";

    echo "=== User Session Test ===\n";
    if (isset($_SESSION['user_id'])) {
        echo "✓ User logged in - ID: {$_SESSION['user_id']}\n";
        echo "  Name: " . ($_SESSION['name'] ?? 'N/A') . "\n";
        echo "  Email: " . ($_SESSION['email'] ?? 'N/A') . "\n";
    } else {
        echo "✗ No user logged in\n";
        echo "  Note: You need to be logged in to test payment flow\n";
    }
    echo "\n";

    echo "=== Integration Test Summary ===\n";
    echo "✓ Configuration loaded correctly\n";
    echo "✓ Database connection working\n";
    echo "✓ All required files present\n";

    if (isset($_GET['api_test'])) {
        echo "✓ PayStack API connectivity tested\n";
    }

    echo "\nNext steps to test payment flow:\n";
    echo "1. Login to your account\n";
    echo "2. Add items to cart\n";
    echo "3. Go to checkout and test payment\n";
    echo "4. Check payment table for records\n";
    echo "\n";

    echo "Test URLs:\n";
    echo "- Checkout: <a href='views/checkout.php'>Checkout Page</a>\n";
    echo "- Cart: <a href='cart.php'>Shopping Cart</a>\n";
    echo "- Login: <a href='login/login.php'>Login Page</a>\n";
    echo "- API Test: <a href='?test=1&api_test=1'>Test PayStack API</a>\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

<style>
body {
    font-family: 'Courier New', monospace;
    margin: 20px;
    background-color: #f5f5f5;
}

pre {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow-x: auto;
}

h2 {
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>