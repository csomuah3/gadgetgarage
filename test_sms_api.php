<?php
/**
 * Arkesel SMS API Test
 * Test if the SMS API key is working and can send messages
 */

require_once 'settings/sms_config.php';

echo "<h2>Arkesel SMS API Test</h2>";
echo "<hr>";

// Test 1: Check configuration
echo "<h3>1. Configuration Check</h3>";
echo "API Key: " . (defined('SMS_API_KEY') ? SMS_API_KEY : "❌ Not defined") . "<br>";
echo "API URL: " . (defined('SMS_API_URL') ? SMS_API_URL : "❌ Not defined") . "<br>";
echo "Sender ID: " . (defined('SMS_SENDER_ID') ? SMS_SENDER_ID : "❌ Not defined") . "<br>";
echo "SMS Enabled: " . (SMS_ENABLED ? "✅ Yes" : "❌ No") . "<br>";

// Test 2: Check API connectivity
echo "<h3>2. API Connectivity Test</h3>";

if (!function_exists('curl_init')) {
    echo "❌ cURL is not available - SMS will not work<br>";
    exit;
}

echo "✅ cURL is available<br>";

// Test 3: Test API with a simple request (balance check)
echo "<h3>3. Arkesel API Authentication Test</h3>";

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
                echo "✅ API responded with JSON<br>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

                if (isset($data['code']) && $data['code'] === 'ok') {
                    echo "✅ API authentication successful<br>";
                    if (isset($data['data']['balance'])) {
                        echo "✅ Account balance: " . $data['data']['balance'] . " credits<br>";
                    }
                } else {
                    echo "❌ API authentication failed<br>";
                    echo "Response: " . $response . "<br>";
                }
            } else {
                echo "⚠️ API responded but not in JSON format<br>";
                echo "Response: " . htmlspecialchars($response) . "<br>";
            }
        } elseif ($http_code == 401) {
            echo "❌ Authentication failed - Invalid API key<br>";
            echo "Response: " . htmlspecialchars($response) . "<br>";
        } elseif ($http_code == 403) {
            echo "❌ Access forbidden - Check API permissions<br>";
            echo "Response: " . htmlspecialchars($response) . "<br>";
        } else {
            echo "❌ API Error (HTTP $http_code)<br>";
            echo "Response: " . htmlspecialchars($response) . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

// Test 4: Test phone number formatting
echo "<h3>4. Phone Number Formatting Test</h3>";

require_once 'helpers/sms_helper.php';

$test_numbers = [
    '0241234567',
    '+233241234567',
    '233241234567',
    '0501234567',
    '+233501234567'
];

foreach ($test_numbers as $number) {
    $formatted = format_phone_number($number);
    echo "Original: $number → Formatted: " . ($formatted ?: "❌ Invalid") . "<br>";
}

// Test 5: Test SMS sending (optional - only if you want to send a real SMS)
echo "<h3>5. Test SMS Send (Simulation)</h3>";
echo "<p><strong>Note:</strong> This is just a simulation. No actual SMS will be sent.</p>";

$test_phone = "+233241234567";
$test_message = "Test message from Gadget Garage - " . date('Y-m-d H:i:s');

echo "Would send to: $test_phone<br>";
echo "Message: $test_message<br>";

// Uncomment below to send actual SMS (be careful with credits!)
/*
try {
    $curl = curl_init();

    $sms_data = [
        'sender' => SMS_SENDER_ID,
        'message' => $test_message,
        'recipients' => [$test_phone]
    ];

    curl_setopt_array($curl, array(
        CURLOPT_URL => SMS_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($sms_data),
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
        echo "SMS Send HTTP Code: " . $http_code . "<br>";
        echo "SMS Response: " . htmlspecialchars($response) . "<br>";

        $data = json_decode($response, true);
        if ($data && isset($data['code']) && $data['code'] === 'ok') {
            echo "✅ SMS sent successfully!<br>";
        } else {
            echo "❌ SMS send failed<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ SMS Exception: " . $e->getMessage() . "<br>";
}
*/

echo "<hr>";
echo "<h3>Test Summary</h3>";
echo "<p>If the API authentication test shows '✅ API authentication successful' and shows your balance, then the SMS integration should work.</p>";
echo "<p>If you see errors, the SMS functionality will not work until the API key is fixed.</p>";
echo "<p><a href='views/checkout.php'>Go to Checkout</a> | <a href='test_paystack_connection.php'>Test PayStack</a></p>";

// Debug path info
echo "<hr>";
echo "<h3>Debug Info</h3>";
echo "Current file path: " . __FILE__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
?>