<?php
/**
 * SMS System Test Script
 * Use this to test your SMS functionality
 */

require_once __DIR__ . '/helpers/sms_helper.php';
require_once __DIR__ . '/classes/sms_class.php';

// Prevent direct access if not testing
if (!isset($_GET['test']) || $_GET['test'] !== '1') {
    die('SMS Testing Script - Add ?test=1 to run tests');
}

echo "<h2>SMS System Test Results</h2>";
echo "<pre>";

try {
    echo "=== SMS Configuration Test ===\n";
    echo "SMS Enabled: " . (SMS_ENABLED ? 'YES' : 'NO') . "\n";
    echo "Sender ID: " . SMS_SENDER_ID . "\n";
    echo "API URL: " . SMS_API_URL . "\n";
    echo "Rate Limit: " . SMS_RATE_LIMIT . " SMS/hour\n";
    echo "Cart Abandonment: " . (CART_ABANDONMENT_ENABLED ? 'ENABLED' : 'DISABLED') . "\n";
    echo "\n";

    echo "=== Phone Number Format Test ===\n";
    $test_phones = [
        '0551387578',
        '+233551387578',
        '233551387578',
        '055-138-7578',
        '0201234567'
    ];

    foreach ($test_phones as $phone) {
        $formatted = format_phone_number($phone);
        $status = $formatted ? "✓ Valid: $formatted" : "✗ Invalid";
        echo "$phone -> $status\n";
    }
    echo "\n";

    echo "=== SMS Template Test ===\n";
    $template = get_sms_template('order_confirmation', 'en');
    echo "Order Confirmation Template: " . ($template ? "✓ Found" : "✗ Not found") . "\n";

    if ($template) {
        $processed = process_sms_template($template, [
            'name' => 'John Doe',
            'order_id' => '12345',
            'amount' => '150.00',
            'delivery_date' => 'Dec 25, 2024',
            'tracking_url' => 'https://gadgetgarage.com/track/12345'
        ]);
        echo "Processed: $processed\n";
    }
    echo "\n";

    echo "=== Business Hours Test ===\n";
    echo "Current time: " . date('H:i') . "\n";
    echo "Business hours: " . (is_business_hours() ? "✓ OPEN" : "✗ CLOSED") . "\n";
    echo "\n";

    echo "=== Database Connection Test ===\n";
    try {
        require_once __DIR__ . '/settings/db_class.php';
        $db = new db_connection();
        echo "Database: ✓ Connected\n";

        // Test SMS logs table
        $test_query = "SHOW TABLES LIKE 'sms_logs'";
        $result = $db->db_fetch_one($test_query);
        echo "SMS Logs Table: " . ($result ? "✓ Exists" : "✗ Missing") . "\n";

        // Test cart abandonment table
        $test_query = "SHOW TABLES LIKE 'cart_abandonment'";
        $result = $db->db_fetch_one($test_query);
        echo "Cart Abandonment Table: " . ($result ? "✓ Exists" : "✗ Missing") . "\n";

    } catch (Exception $e) {
        echo "Database: ✗ Error - " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "=== SMS Service Test ===\n";
    try {
        $sms = new SMSService();
        echo "SMS Service: ✓ Initialized\n";

        // Test account balance (this will make an actual API call)
        if (isset($_GET['api_test']) && $_GET['api_test'] === '1') {
            echo "Testing API connection...\n";
            $balance = $sms->getAccountBalance();
            echo "Account Balance: $balance\n";
        } else {
            echo "API Test: Skipped (add &api_test=1 to test actual API)\n";
        }

    } catch (Exception $e) {
        echo "SMS Service: ✗ Error - " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "=== File System Test ===\n";
    $files_to_check = [
        'settings/sms_config.php',
        'classes/sms_class.php',
        'helpers/sms_helper.php',
        'actions/send_sms_action.php',
        'actions/order_sms_action.php',
        'actions/cart_abandonment_action.php',
        'admin/sms_management.php'
    ];

    foreach ($files_to_check as $file) {
        $path = __DIR__ . '/' . $file;
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        echo "$file: " . ($readable ? "✓ OK" : "✗ Missing/Unreadable") . "\n";
    }
    echo "\n";

    echo "=== Test Summary ===\n";
    echo "✓ All basic tests completed\n";
    echo "Next steps:\n";
    echo "1. Test sending actual SMS via admin panel\n";
    echo "2. Setup cron job for cart abandonment\n";
    echo "3. Test order confirmation SMS on real orders\n";
    echo "\n";

    echo "Admin Panel: <a href='admin/sms_management.php'>SMS Management</a>\n";
    echo "API Test: <a href='?test=1&api_test=1'>Test API Connection</a>\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>