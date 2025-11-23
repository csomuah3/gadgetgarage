<?php
/**
 * SMS API Test in Views Directory
 */

// Include the SMS configuration from the project
require_once '../settings/sms_config.php';

echo "<h2>SMS API Test</h2>";
echo "<hr>";

// Test 1: Check configuration
echo "<h3>1. Configuration Check</h3>";
echo "API Key: " . SMS_API_KEY . "<br>";
echo "API URL: " . SMS_API_URL . "<br>";
echo "Sender ID: " . SMS_SENDER_ID . "<br>";
echo "SMS Enabled: " . (SMS_ENABLED ? "✅ Yes" : "❌ No") . "<br>";

// Test 2: Test API connectivity
echo "<h3>2. API Authentication Test</h3>";

if (!function_exists('curl_init')) {
    echo "❌ cURL is not available - SMS will not work<br>";
    exit;
}

try {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://sms.arkesel.com/api/v2/sms/balance',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'api-key: ' . SMS_API_KEY,
            'Accept: application/json',
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($curl_error) {
        echo "❌ cURL Error: " . $curl_error . "<br>";
    } else {
        echo "HTTP Response Code: " . $http_code . "<br>";

        if ($http_code == 200) {
            echo "✅ API is reachable<br>";
            $data = json_decode($response, true);
            if ($data) {
                echo "✅ JSON Response received<br>";
                echo "<pre>Response: " . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

                if (isset($data['code']) && $data['code'] === 'ok') {
                    echo "<h3 style='color: green;'>✅ API AUTHENTICATION SUCCESSFUL!</h3>";
                    if (isset($data['balance'])) {
                        echo "<h3 style='color: green;'>✅ Account Balance: " . $data['balance'] . " credits</h3>";
                    }
                    if (isset($data['user'])) {
                        echo "✅ Account User: " . $data['user'] . "<br>";
                    }
                    echo "<h2 style='color: green; background: #e8f5e8; padding: 20px; border-radius: 10px;'>🎉 SMS WILL WORK FOR YOUR CUSTOMERS! 🎉</h2>";
                } else {
                    echo "<h3 style='color: red;'>❌ API Authentication Failed</h3>";
                    echo "Response code: " . ($data['code'] ?? 'unknown') . "<br>";
                    echo "Response message: " . ($data['message'] ?? 'unknown') . "<br>";
                    echo "<h3 style='color: red; background: #ffe8e8; padding: 20px; border-radius: 10px;'>❌ SMS WILL NOT WORK - API KEY ISSUE</h3>";
                }
            } else {
                echo "❌ Invalid JSON response<br>";
                echo "Raw response: " . htmlspecialchars($response) . "<br>";
            }
        } elseif ($http_code == 401) {
            echo "<h3 style='color: red;'>❌ Authentication Failed - Invalid API Key</h3>";
            echo "Response: " . htmlspecialchars($response) . "<br>";
            echo "<h3 style='color: red; background: #ffe8e8; padding: 20px; border-radius: 10px;'>❌ SMS WILL NOT WORK - API KEY EXPIRED/INVALID</h3>";
        } else {
            echo "❌ API Error (HTTP $http_code)<br>";
            echo "Response: " . htmlspecialchars($response) . "<br>";
            echo "<h3 style='color: red; background: #ffe8e8; padding: 20px; border-radius: 10px;'>❌ SMS WILL NOT WORK - API ERROR</h3>";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "<h3 style='color: red; background: #ffe8e8; padding: 20px; border-radius: 10px;'>❌ SMS WILL NOT WORK - CONNECTION ERROR</h3>";
}

// Test 3: Phone number formatting test
echo "<h3>3. Phone Number Formatting Test</h3>";
require_once '../helpers/sms_helper.php';

$test_numbers = ['0241234567', '+233241234567', '0501234567'];
foreach ($test_numbers as $number) {
    $formatted = format_phone_number($number);
    echo "Original: $number → Formatted: " . ($formatted ?: "❌ Invalid") . "<br>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>This test shows if SMS notifications will be sent to customers after they complete checkout.</strong></p>";
echo "<p><a href='checkout.php'>← Back to Checkout</a></p>";
?>