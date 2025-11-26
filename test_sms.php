<?php
/**
 * Test SMS Sending - Direct Arkesel API Test
 * This file tests the SMS API directly to see what's happening
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/settings/sms_config.php';
require_once __DIR__ . '/helpers/sms_helper.php';

// Test phone number - REPLACE WITH YOUR PHONE NUMBER
// Format: 0244123456 (Ghana number starting with 0)
$test_phone = '0244123456'; // ⚠️ CHANGE THIS TO YOUR ACTUAL PHONE NUMBER

echo "<h2>SMS Test Results</h2>";
echo "<pre>";

// Test 1: Phone number formatting
echo "=== Test 1: Phone Number Formatting ===\n";
$formatted = format_phone_number($test_phone);
echo "Original: $test_phone\n";
echo "Formatted: " . ($formatted ? $formatted : 'FAILED') . "\n\n";

if (!$formatted) {
    echo "ERROR: Phone number formatting failed!\n";
    exit;
}

// Test 2: Direct API call
echo "=== Test 2: Direct Arkesel API Call ===\n";
$api_key = SMS_API_KEY;
$api_url = SMS_API_URL;
$sender_id = SMS_SENDER_ID;
$message = "Test SMS from GadgetGarage - " . date('Y-m-d H:i:s');

$api_data = [
    'sender' => $sender_id,
    'message' => $message,
    'recipients' => [$formatted]
];

echo "API URL: $api_url\n";
echo "API Key: " . substr($api_key, 0, 10) . "...\n";
echo "Sender ID: $sender_id\n";
echo "Recipient: $formatted\n";
echo "Message: $message\n";
echo "Request Data: " . json_encode($api_data) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'api-key: ' . $api_key
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== API Response ===\n";
echo "HTTP Code: $http_code\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n";

$response_data = json_decode($response, true);
if ($response_data) {
    echo "Parsed Response: " . json_encode($response_data, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== Test 3: Using SMSService Class ===\n";
try {
    require_once __DIR__ . '/classes/sms_class.php';
    $sms = new SMSService();
    $result = $sms->sendAppointmentConfirmationSMS(
        999, // test appointment ID
        null, // customer_id
        $test_phone,
        'Test Customer',
        date('Y-m-d'),
        date('H:i:s'),
        'Test Specialist',
        'Test Issue'
    );
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
