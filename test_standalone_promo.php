<?php
// Standalone promo code test - no external dependencies
header('Content-Type: application/json');

$promo_code = $_GET['code'] ?? 'BLACKFRIDAY20';
$cart_total = floatval($_GET['total'] ?? 100);

// Simple hardcoded validation
$valid_promos = [
    'BLACKFRIDAY20' => ['type' => 'percentage', 'value' => 20, 'description' => 'Black Friday 20% Off'],
    'SAVE10' => ['type' => 'percentage', 'value' => 10, 'description' => 'Save 10% Off'],
    'WELCOME15' => ['type' => 'percentage', 'value' => 15, 'description' => 'Welcome 15% Off']
];

$promo_code = strtoupper(trim($promo_code));

if (!isset($valid_promos[$promo_code])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid promo code: ' . $promo_code
    ]);
    exit;
}

$promo = $valid_promos[$promo_code];
$discount_amount = ($cart_total * $promo['value']) / 100;
$new_total = max(0, $cart_total - $discount_amount);

echo json_encode([
    'success' => true,
    'message' => 'Promo code applied successfully!',
    'promo_code' => $promo_code,
    'description' => $promo['description'],
    'discount_type' => $promo['type'],
    'discount_value' => $promo['value'],
    'discount_amount' => round($discount_amount, 2),
    'original_total' => round($cart_total, 2),
    'new_total' => round($new_total, 2),
    'savings' => round($discount_amount, 2)
]);
?>