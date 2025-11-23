<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once('../settings/core.php');
    require_once('../settings/db_class.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get JSON input
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);

    // Debug logging
    error_log('Promo validation debug: Raw input = ' . $raw_input);
    error_log('Promo validation debug: Decoded input = ' . json_encode($input));
    error_log('Promo validation debug: JSON decode error = ' . json_last_error_msg());

    if (!$input || !isset($input['promo_code']) || !isset($input['cart_total'])) {
        $debug_info = [
            'input_exists' => !empty($input),
            'input_is_array' => is_array($input),
            'promo_code_exists' => isset($input['promo_code']),
            'cart_total_exists' => isset($input['cart_total']),
            'input_keys' => is_array($input) ? array_keys($input) : 'not_array',
            'input_values' => is_array($input) ? array_values($input) : 'not_array',
            'raw_input_length' => strlen($raw_input),
            'json_last_error' => json_last_error_msg(),
            'raw_input_preview' => substr($raw_input, 0, 200),
            'php_input_method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not_set'
        ];
        error_log('Promo validation debug: Missing parameters = ' . json_encode($debug_info));

        // Return JSON error instead of throwing exception for better debugging
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters. Check that both promo_code and cart_total are being sent.',
            'debug' => $debug_info
        ]);
        exit;
    }

    // Additional validation for cart_total value
    if (isset($input['cart_total']) && ($input['cart_total'] === null || $input['cart_total'] === '')) {
        error_log('Promo validation debug: cart_total is null or empty');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cart total is empty. Please add items to your cart first.',
            'debug' => [
                'cart_total_value' => $input['cart_total'],
                'cart_total_type' => gettype($input['cart_total'])
            ]
        ]);
        exit;
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
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();

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
    if (isset($db)) {
        $db->db_close();
    }
}
?>