<?php

/**
 * Direct SMS Test Script
 * This will test sending an SMS directly to see if the Brevo API integration works
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/settings/sms_config.php';
require_once __DIR__ . '/helpers/sms_helper.php';
require_once __DIR__ . '/classes/sms_class.php';

echo "<h2>🧪 Direct SMS Test</h2>";

// Test phone number - CHANGE THIS TO YOUR PHONE NUMBER  
// Format: +233XXXXXXXXX (must include country code +233 for Ghana)
$test_phone = '+233551387578'; // ⚠️ REPLACE THIS with your actual test phone number
$test_message = "Test SMS from Gadget Garage - " . date('Y-m-d H:i:s');

echo "<h3>1. Configuration Check</h3>";
echo "SMS Enabled: " . (SMS_ENABLED ? "✅ YES" : "❌ NO") . "<br>";
echo "API Key: " . (defined('SMS_API_KEY') && !empty(SMS_API_KEY) && SMS_API_KEY !== 'YOUR_API_KEY_HERE' ? "✅ SET (" . substr(SMS_API_KEY, 0, 20) . "...)" : "❌ NOT SET") . "<br>";
echo "API URL: " . SMS_API_URL . "<br>";
echo "Sender ID: " . SMS_SENDER_ID . "<br><br>";

echo "<h3>2. Testing Phone Number Formatting</h3>";
$formatted = format_phone_number($test_phone);
echo "Original: $test_phone<br>";
echo "Formatted: " . ($formatted ?: "❌ INVALID") . "<br><br>";

if (!$formatted) {
    echo "<p style='color:red;'>❌ Phone number format is invalid. Please check the phone number.</p>";
    exit;
}

echo "<h3>3. Testing SMS Sending</h3>";
echo "<p>Attempting to send SMS to: <strong>$formatted</strong></p>";
echo "<p>Message: $test_message</p>";

try {
    $sms = new SMSService();

    // Test direct SMS sending
    echo "<h4>Using SMSService->sendSMS() directly...</h4>";

    // We need to use reflection or make the method public for testing
    // For now, let's test using the welcome SMS function which uses sendSMS internally
    echo "<p>Testing via send_welcome_registration_sms function...</p>";

    $result = send_welcome_registration_sms(999, 'Test User', $formatted);

    echo "<h4>Result:</h4>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if ($result) {
        echo "<p style='color:green;'>✅ SMS sent successfully! Check your phone and Brevo dashboard.</p>";
    } else {
        echo "<p style='color:red;'>❌ SMS failed to send. Check error logs below.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>4. Manual API Test</h3>";
echo "<p>Testing direct API call to Brevo...</p>";

// Manual API test
$api_key = SMS_API_KEY;
$api_url = SMS_API_URL;

$api_data = [
    'recipient' => $formatted,
    'content' => $test_message,
    'sender' => SMS_SENDER_ID,
    'type' => 'transactional'
];

echo "<h4>Request Details:</h4>";
echo "URL: $api_url<br>";
echo "Method: POST<br>";
echo "Headers: Content-Type: application/json, api-key: " . substr($api_key, 0, 20) . "...<br>";
echo "<h4>Request Body:</h4>";
echo "<pre>" . json_encode($api_data, JSON_PRETTY_PRINT) . "</pre>";

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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h4>Response:</h4>";
echo "HTTP Code: <strong>$http_code</strong><br>";
if ($curl_error) {
    echo "cURL Error: <span style='color:red;'>$curl_error</span><br>";
}
echo "<h4>Response Body:</h4>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$response_data = json_decode($response, true);
if ($response_data) {
    echo "<h4>Parsed Response:</h4>";
    echo "<pre>" . print_r($response_data, true) . "</pre>";
}

if ($http_code === 200 || $http_code === 201) {
    echo "<p style='color:green;'>✅ API call successful! Check your phone and Brevo dashboard for the SMS.</p>";
} else {
    echo "<p style='color:red;'>❌ API call failed with HTTP code $http_code</p>";
    if (isset($response_data['message'])) {
        echo "<p>Error message: " . htmlspecialchars($response_data['message']) . "</p>";
    }
}

echo "<hr>";
echo "<h3>📋 Next Steps:</h3>";
echo "<ol>";
echo "<li>Check your phone for the test SMS</li>";
echo "<li>Check Brevo dashboard → Transactional → SMS → Logs</li>";
echo "<li>Check PHP error logs for any errors</li>";
echo "<li>If SMS was sent but not received, check Brevo logs for delivery status</li>";
echo "</ol>";

?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        max-width: 900px;
    }

    h2 {
        color: #2c5aa0;
        border-bottom: 2px solid #2c5aa0;
        padding-bottom: 10px;
    }

    h3 {
        color: #666;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
        margin-top: 20px;
    }

    pre {
        background: #f5f5f5;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
</style>