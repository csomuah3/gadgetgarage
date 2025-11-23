<?php
// Direct verification of promo code functionality
echo "<h1>🔍 Promo Code Verification Test</h1>";
echo "<p>Testing the promo code endpoint directly...</p>";

// Test the endpoint
$test_url = "http://169.239.251.102/Ecommerce_Final/test_standalone_promo.php?code=BLACKFRIDAY20&total=100";

echo "<h3>Testing URL:</h3>";
echo "<code>" . htmlspecialchars($test_url) . "</code>";

echo "<h3>Making Request...</h3>";

try {
    $response = file_get_contents($test_url);

    echo "<h3>Raw Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";

    $data = json_decode($response, true);

    echo "<h3>Parsed Data:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";

    if ($data && $data['success']) {
        echo "<h2 style='color: green;'>✅ SUCCESS! Promo code validation is working!</h2>";
        echo "<p><strong>Savings:</strong> GH₵" . $data['savings'] . "</p>";
    } else {
        echo "<h2 style='color: red;'>❌ FAILED!</h2>";
        echo "<p>Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>💥 ERROR!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Manual Test Links:</h3>";
echo "<p><a href='test_standalone_promo.php?code=BLACKFRIDAY20&total=100'>Test BLACKFRIDAY20 with $100</a></p>";
echo "<p><a href='test_standalone_promo.php?code=SAVE10&total=200'>Test SAVE10 with $200</a></p>";
echo "<p><a href='test_standalone_promo.php?code=INVALID&total=100'>Test Invalid Code</a></p>";
?>