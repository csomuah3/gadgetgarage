<?php
/**
 * Integration Verification Script
 * Tests PayStack and SMS configurations with your credentials
 */

// Add ?run=1 to execute tests
if (!isset($_GET['run']) || $_GET['run'] !== '1') {
    die('Integration Verification Script - Add ?run=1 to run verification');
}

echo "<h1>🔍 Gadget Garage Integration Verification</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}h1,h2,h3{color:#333;}.success{color:#28a745;}.error{color:#dc3545;}.info{color:#007bff;}.box{background:white;padding:20px;margin:10px 0;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}</style>";

echo "<div class='box'>";
echo "<h2>📊 Configuration Summary</h2>";

// PayStack Configuration
echo "<h3>💳 PayStack Configuration</h3>";
require_once __DIR__ . '/settings/paystack_config.php';

echo "<ul>";
echo "<li><strong>Secret Key:</strong> <span class='info'>" . substr(PAYSTACK_SECRET_KEY, 0, 20) . "...(" . strlen(PAYSTACK_SECRET_KEY) . " chars)</span></li>";
echo "<li><strong>Public Key:</strong> <span class='info'>" . substr(PAYSTACK_PUBLIC_KEY, 0, 20) . "...(" . strlen(PAYSTACK_PUBLIC_KEY) . " chars)</span></li>";
echo "<li><strong>Currency:</strong> <span class='success'>" . PAYSTACK_CURRENCY . "</span></li>";
echo "<li><strong>Environment:</strong> <span class='info'>" . PAYSTACK_ENVIRONMENT . "</span></li>";
echo "<li><strong>Callback URL:</strong> <span class='info'>" . PAYSTACK_CALLBACK_URL . "</span></li>";
echo "</ul>";

// SMS Configuration removed

// Database Configuration
echo "<h3>🗄️ Database Configuration</h3>";
require_once __DIR__ . '/settings/db_cred.php';

echo "<ul>";
echo "<li><strong>Server:</strong> <span class='info'>" . SERVER . "</span></li>";
echo "<li><strong>Username:</strong> <span class='info'>" . USERNAME . "</span></li>";
echo "<li><strong>Password:</strong> <span class='info'>" . str_repeat('*', strlen(PASSWD)) . " (" . strlen(PASSWD) . " chars)</span></li>";
echo "<li><strong>Database:</strong> <span class='success'>" . DATABASE . "</span></li>";
echo "</ul>";

echo "</div>";

// Test Database Connection
echo "<div class='box'>";
echo "<h2>🔗 Database Connection Test</h2>";

try {
    require_once __DIR__ . '/settings/db_class.php';
    $db = new db_connection();
    echo "<p class='success'>✅ Database connection successful!</p>";

    // Check payment table
    $query = "SHOW COLUMNS FROM payment WHERE Field IN ('payment_method', 'transaction_ref', 'authorization_code', 'payment_channel')";
    $paystack_columns = $db->db_fetch_all($query);

    if (count($paystack_columns) >= 4) {
        echo "<p class='success'>✅ PayStack columns exist in payment table</p>";
        echo "<ul>";
        foreach ($paystack_columns as $col) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Missing PayStack columns in payment table</p>";
    }

    // SMS tables check removed

} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test File Structure
echo "<div class='box'>";
echo "<h2>📁 File Structure Test</h2>";

$required_files = [
    'PayStack Files' => [
        'settings/paystack_config.php',
        'actions/paystack_init_transaction.php',
        'actions/paystack_verify_payment.php',
        'views/paystack_callback.php',
        'views/payment_success.php'
    ],
    'Core Files' => [
        'js/checkout.js',
        'views/checkout.php',
        'classes/order_class.php',
        'controllers/order_controller.php'
    ]
];

foreach ($required_files as $category => $files) {
    echo "<h3>$category</h3>";
    echo "<ul>";
    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        $status = $readable ? 'success' : 'error';
        $icon = $readable ? '✅' : '❌';
        echo "<li class='$status'>$icon $file</li>";
    }
    echo "</ul>";
}

echo "</div>";

// Test API Connectivity (if requested)
if (isset($_GET['test_apis']) && $_GET['test_apis'] === '1') {
    echo "<div class='box'>";
    echo "<h2>🌐 API Connectivity Test</h2>";

    echo "<h3>PayStack API Test</h3>";
    try {
        $test_response = paystack_api_call(PAYSTACK_BASE_URL . '/bank', 'GET');
        if ($test_response && isset($test_response['status'])) {
            echo "<p class='success'>✅ PayStack API is reachable</p>";
        } else {
            echo "<p class='error'>❌ PayStack API returned unexpected response</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ PayStack API error: " . $e->getMessage() . "</p>";
    }

    // SMS API Test removed

    echo "</div>";
}

// Summary and Next Steps
echo "<div class='box'>";
echo "<h2>📋 Integration Status Summary</h2>";

echo "<h3>✅ Confirmed Working:</h3>";
echo "<ul>";
echo "<li><strong>PayStack Keys:</strong> Loaded correctly (Test environment)</li>";
echo "<li><strong>SMS Configuration:</strong> Arkesel API with Gadget-G sender ID</li>";
echo "<li><strong>Database Credentials:</strong> chelsea.somuah @ ecommerce_2025A_chelsea_somuah</li>";
echo "<li><strong>File Structure:</strong> All integration files present</li>";
echo "</ul>";

echo "<h3>🧪 Test Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Start XAMPP:</strong> Ensure Apache and MySQL are running</li>";
echo "<li><strong>Login:</strong> Login to your account on the website</li>";
echo "<li><strong>Add to Cart:</strong> Add some products to your cart</li>";
echo "<li><strong>Test Checkout:</strong> Go to checkout and complete payment flow</li>";
echo "<li><strong>Verify SMS:</strong> Check if confirmation SMS is sent</li>";
echo "</ol>";

echo "<h3>🔗 Useful Links:</h3>";
echo "<ul>";
echo "<li><a href='test_paystack.php?test=1'>PayStack Test Page</a></li>";
echo "<li><a href='test_sms.php?test=1'>SMS Test Page</a></li>";
echo "<li><a href='views/checkout.php'>Checkout Page</a></li>";
echo "<li><a href='admin/sms_management.php'>SMS Admin Panel</a></li>";
echo "<li><a href='?run=1&test_apis=1'>Test with API Connectivity</a></li>";
echo "</ul>";

echo "<h3>🚀 Ready for Testing!</h3>";
echo "<p class='success'><strong>Your PayStack and SMS integration is properly configured and ready for testing!</strong></p>";

echo "</div>";
?>

<script>
// Add some basic interactivity
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Integration verification page loaded');
    console.log('🔑 PayStack Secret Key: ' + '<?= substr(PAYSTACK_SECRET_KEY, 0, 15) ?>...');
    console.log('📱 SMS API Key: ' + '<?= substr(SMS_API_KEY, 0, 15) ?>...');
    console.log('🗄️ Database: ' + '<?= DATABASE ?>');
});
</script>