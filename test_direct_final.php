<?php
// Direct test of the final promo endpoint
header('Content-Type: text/html');

echo "<h1>Direct Promo Test</h1>";

// Test data
$test_data = json_encode([
    'promo_code' => 'BLACKFRIDAY20',
    'cart_total' => 100
]);

// Make a direct request to our endpoint
$url = 'http://169.239.251.102/Ecommerce_Final/actions/validate_promo_final.php';
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $test_data
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

echo "<h3>Request Data:</h3>";
echo "<pre>" . htmlspecialchars($test_data) . "</pre>";

echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h3>Response Parsed:</h3>";
$parsed = json_decode($response, true);
if ($parsed) {
    echo "<pre>" . print_r($parsed, true) . "</pre>";

    if ($parsed['success']) {
        echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS! Promo code validation is working!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ FAILED: " . $parsed['message'] . "</p>";
    }
} else {
    echo "<p style='color: red;'>Failed to parse JSON response</p>";
}
?>