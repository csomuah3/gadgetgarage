<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once('../settings/core.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['promo_code']) || !isset($input['cart_total'])) {
        throw new Exception('Missing required parameters');
    }

    $promo_code = trim(strtoupper($input['promo_code']));
    $cart_total = floatval($input['cart_total']);

    if (empty($promo_code)) {
        throw new Exception('Promo code is required');
    }

    if ($cart_total <= 0) {
        throw new Exception('Invalid cart total');
    }

    // Validate promo code against database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Check if promo code exists and is valid
    $stmt = $conn->prepare("
        SELECT promo_id, promo_code, promo_description, discount_type, discount_value,
               min_order_amount, max_discount_amount, end_date, usage_limit, used_count, is_active
        FROM promo_codes
        WHERE promo_code = ? AND is_active = 1
    ");

    $stmt->bind_param('s', $promo_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid promo code');
    }

    $promo = $result->fetch_assoc();

    // Check if promo code has expired
    if ($promo['end_date'] && strtotime($promo['end_date']) < time()) {
        throw new Exception('This promo code has expired');
    }

    // Check if usage limit has been reached
    if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
        throw new Exception('This promo code has reached its usage limit');
    }

    // Check minimum order amount
    if ($cart_total < $promo['min_order_amount']) {
        throw new Exception('Minimum order amount of GHS ' . number_format($promo['min_order_amount'], 2) . ' required for this promo code');
    }

    // Calculate discount
    $discount_amount = 0;

    if ($promo['discount_type'] === 'percentage') {
        $discount_amount = ($cart_total * $promo['discount_value']) / 100;

        // Apply maximum discount limit if specified
        if ($promo['max_discount_amount'] && $discount_amount > $promo['max_discount_amount']) {
            $discount_amount = $promo['max_discount_amount'];
        }
    } else if ($promo['discount_type'] === 'fixed') {
        $discount_amount = min($promo['discount_value'], $cart_total);
    }

    // Calculate new total
    $new_total = max(0, $cart_total - $discount_amount);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Promo code applied successfully!',
        'promo_code' => $promo['promo_code'],
        'description' => $promo['promo_description'],
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value'],
        'discount_amount' => round($discount_amount, 2),
        'original_total' => round($cart_total, 2),
        'new_total' => round($new_total, 2),
        'savings' => round($discount_amount, 2)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>