<?php
/**
 * PayStack Connection Test
 * Simple test to verify PayStack API connectivity
 */

require_once 'settings/paystack_config.php';

echo "<h2>PayStack Connection Test</h2>";
echo "<hr>";

// Test 1: Check if constants are defined
echo "<h3>1. Configuration Check</h3>";
echo "Secret Key: " . (defined('PAYSTACK_SECRET_KEY') ? "✅ Defined" : "❌ Not defined") . "<br>";
echo "Public Key: " . (defined('PAYSTACK_PUBLIC_KEY') ? "✅ Defined" : "❌ Not defined") . "<br>";
echo "Base URL: " . (defined('PAYSTACK_BASE_URL') ? PAYSTACK_BASE_URL : "❌ Not defined") . "<br>";
echo "Callback URL: " . (defined('PAYSTACK_CALLBACK_URL') ? PAYSTACK_CALLBACK_URL : "❌ Not defined") . "<br>";
echo "Currency: " . (defined('PAYSTACK_CURRENCY') ? PAYSTACK_CURRENCY : "❌ Not defined") . "<br>";

// Test 2: Check cURL availability
echo "<h3>2. cURL Check</h3>";
if (function_exists('curl_init')) {
    echo "✅ cURL is available<br>";
} else {
    echo "❌ cURL is not available - PayStack integration will not work<br>";
    exit;
}

// Test 3: Test API connectivity
echo "<h3>3. PayStack API Connectivity Test</h3>";

try {
    // Simple API call to check connectivity
    $url = PAYSTACK_BASE_URL . "/bank";
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Content-Type: application/json",
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "❌ cURL Error: " . $err . "<br>";
    } else if ($http_code == 200) {
        echo "✅ PayStack API is reachable (HTTP $http_code)<br>";
        $data = json_decode($response, true);
        if ($data && isset($data['status']) && $data['status'] === true) {
            echo "✅ PayStack API responded correctly<br>";
            echo "✅ Authentication successful<br>";
        } else {
            echo "⚠️ PayStack API responded but format unexpected<br>";
        }
    } else {
        echo "❌ PayStack API Error (HTTP $http_code)<br>";
        if ($http_code == 401) {
            echo "❌ Authentication failed - check your secret key<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

// Test 4: Test amount conversion functions
echo "<h3>4. Amount Conversion Test</h3>";
$test_amount = 25.50;
$pesewas = amount_to_pesewas($test_amount);
$back_to_amount = pesewas_to_amount($pesewas);

echo "Original amount: GHS $test_amount<br>";
echo "Converted to pesewas: $pesewas<br>";
echo "Converted back: GHS $back_to_amount<br>";

if ($back_to_amount == $test_amount) {
    echo "✅ Amount conversion working correctly<br>";
} else {
    echo "❌ Amount conversion failed<br>";
}

// Test 5: Test reference generation
echo "<h3>5. Reference Generation Test</h3>";
$ref1 = generate_transaction_reference(123);
$ref2 = generate_transaction_reference(123);

echo "Reference 1: $ref1<br>";
echo "Reference 2: $ref2<br>";

if ($ref1 != $ref2) {
    echo "✅ References are unique<br>";
} else {
    echo "❌ References are not unique<br>";
}

// Test 6: Check log directory
echo "<h3>6. Logging Test</h3>";
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    if (mkdir($log_dir, 0755, true)) {
        echo "✅ Created logs directory<br>";
    } else {
        echo "❌ Failed to create logs directory<br>";
    }
} else {
    echo "✅ Logs directory exists<br>";
}

try {
    log_paystack_activity('info', 'PayStack connection test', ['test' => true]);
    echo "✅ Logging function works<br>";
} catch (Exception $e) {
    echo "❌ Logging failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Test Complete</h3>";
echo "<p><strong>Note:</strong> If all tests pass, PayStack integration should work correctly.</p>";
echo "<p><a href='views/checkout.php'>Go to Checkout</a> | <a href='views/cart.php'>Go to Cart</a></p>";
?>