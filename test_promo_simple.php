<?php
// Simple promo code test
header('Content-Type: text/plain');

echo "Testing promo code validation...\n\n";

// Simulate the same request as the frontend
$test_data = [
    'promo_code' => 'BLACKFRIDAY20',
    'cart_total' => 9000.00
];

echo "Test data: " . json_encode($test_data) . "\n\n";

// Call the validation endpoint
$url = 'http://localhost/Ecommerce_Final/actions/validate_promo_code.php';
$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($test_data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response_headers = $http_response_header;

echo "Response Headers:\n";
foreach ($response_headers as $header) {
    echo $header . "\n";
}

echo "\nResponse Body:\n";
echo $result;

echo "\n\nParsed Response:\n";
$parsed = json_decode($result, true);
print_r($parsed);
?>