<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Simple approach - handle both JSON and form data
$promo_code = '';
$cart_total = 0;

// Try to get data from JSON
$json = file_get_contents('php://input');
if (!empty($json)) {
    $data = json_decode($json, true);
    if ($data && isset($data['promo_code'])) {
        $promo_code = $data['promo_code'];
        $cart_total = isset($data['cart_total']) ? (float)$data['cart_total'] : 100; // Default to 100 if missing
    }
}

// If no JSON, try POST
if (empty($promo_code) && isset($_POST['promo_code'])) {
    $promo_code = $_POST['promo_code'];
    $cart_total = isset($_POST['cart_total']) ? (float)$_POST['cart_total'] : 100;
}

// If still no data, return error with debug info
if (empty($promo_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'No promo code provided',
        'debug' => [
            'json_input' => $json,
            'post_data' => $_POST,
            'method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
        ]
    ]);
    exit;
}

// Set minimum cart total if it's 0
if ($cart_total <= 0) {
    $cart_total = 100; // Use default for testing
}

$promo_code = strtoupper(trim($promo_code));

// Simple validation without database for now
if ($promo_code === 'BLACKFRIDAY20') {
    $discount_percent = 20;
    $discount_amount = ($cart_total * $discount_percent) / 100;
    $new_total = $cart_total - $discount_amount;

    echo json_encode([
        'success' => true,
        'message' => 'Promo code applied successfully!',
        'promo_code' => $promo_code,
        'description' => 'Black Friday 20% Off',
        'discount_type' => 'percentage',
        'discount_value' => $discount_percent,
        'discount_amount' => round($discount_amount, 2),
        'original_total' => round($cart_total, 2),
        'new_total' => round($new_total, 2),
        'savings' => round($discount_amount, 2)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid promo code: ' . $promo_code
    ]);
}
?>