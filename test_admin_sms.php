<?php
/**
 * Test script for admin SMS notifications
 * Run this to test if admin SMS is working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Admin SMS Notification System</h2>";

// Include required files
require_once 'settings/sms_config.php';
require_once 'helpers/sms_helper.php';
require_once 'classes/sms_class.php';

echo "<h3>Configuration Check:</h3>";
echo "<ul>";
echo "<li>SMS_ENABLED: " . (defined('SMS_ENABLED') ? (SMS_ENABLED ? 'YES' : 'NO') : 'NOT_DEFINED') . "</li>";
echo "<li>ADMIN_SMS_ENABLED: " . (defined('ADMIN_SMS_ENABLED') ? (ADMIN_SMS_ENABLED ? 'YES' : 'NO') : 'NOT_DEFINED') . "</li>";
echo "<li>ADMIN_NEW_ORDER_SMS_ENABLED: " . (defined('ADMIN_NEW_ORDER_SMS_ENABLED') ? (ADMIN_NEW_ORDER_SMS_ENABLED ? 'YES' : 'NO') : 'NOT_DEFINED') . "</li>";
echo "<li>ADMIN_PHONE_NUMBER: " . (defined('ADMIN_PHONE_NUMBER') ? ADMIN_PHONE_NUMBER : 'NOT_DEFINED') . "</li>";
echo "<li>SMS_API_KEY: " . (defined('SMS_API_KEY') ? (SMS_API_KEY ? 'SET' : 'EMPTY') : 'NOT_DEFINED') . "</li>";
echo "</ul>";

echo "<h3>Function Check:</h3>";
echo "<ul>";
echo "<li>send_admin_new_order_sms function exists: " . (function_exists('send_admin_new_order_sms') ? 'YES' : 'NO') . "</li>";
echo "<li>format_phone_number function exists: " . (function_exists('format_phone_number') ? 'YES' : 'NO') . "</li>";
echo "<li>get_sms_template function exists: " . (function_exists('get_sms_template') ? 'YES' : 'NO') . "</li>";
echo "</ul>";

echo "<h3>Template Test:</h3>";
$template = get_sms_template('admin_new_order', 'en');
if ($template) {
    echo "<p><strong>Template found:</strong> " . htmlspecialchars($template) . "</p>";
} else {
    echo "<p><strong>Template NOT found!</strong></p>";
}

echo "<h3>Phone Number Formatting Test:</h3>";
$formatted_phone = format_phone_number(ADMIN_PHONE_NUMBER);
echo "<p>Original: " . ADMIN_PHONE_NUMBER . "</p>";
echo "<p>Formatted: " . ($formatted_phone ?: 'FAILED') . "</p>";

echo "<h3>SMS Class Test:</h3>";
try {
    $sms = new SMSService();
    echo "<p>SMS Service created successfully</p>";

    // Test admin SMS method
    $test_data = [
        'order_id' => '12345',
        'customer_name' => 'Test Customer',
        'customer_phone' => '+233241234567',
        'amount' => '100.00',
        'items_count' => '2',
        'payment_method' => 'Test Payment',
        'admin_url' => 'http://test.com/admin/orders.php?order=12345'
    ];

    echo "<h4>Testing Admin SMS Method:</h4>";
    $result = $sms->sendAdminOrderNotificationSMS('12345', ADMIN_PHONE_NUMBER, $test_data);
    echo "<pre>";
    print_r($result);
    echo "</pre>";

} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<h3>Manual Function Test:</h3>";
if (function_exists('send_admin_new_order_sms')) {
    echo "<p>Testing send_admin_new_order_sms with fake order ID 999...</p>";
    $result = send_admin_new_order_sms(999);
    echo "<p>Result: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
} else {
    echo "<p>Function does not exist</p>";
}

echo "<h3>Error Log Info:</h3>";
echo "<p>Check your PHP error log for detailed SMS debugging information.</p>";
echo "<p>Typical locations: /var/log/apache2/error.log or /Applications/XAMPP/logs/error_log</p>";

?>