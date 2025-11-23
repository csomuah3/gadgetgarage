<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get input data - try multiple methods
    $input = null;
    $source = '';

    // Method 1: JSON from raw input
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $json_data = json_decode($raw_input, true);
        if ($json_data !== null) {
            $input = $json_data;
            $source = 'json';
        }
    }

    // Method 2: Form data
    if (!$input && !empty($_POST)) {
        $input = $_POST;
        $source = 'post';
    }

    // Method 3: Request data
    if (!$input && !empty($_REQUEST)) {
        $input = $_REQUEST;
        $source = 'request';
    }

    // Validate we have data
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'No data received. Raw input: ' . substr($raw_input, 0, 100)
        ]);
        exit;
    }

    // Extract and validate parameters
    $promo_code = isset($input['promo_code']) ? trim(strtoupper($input['promo_code'])) : '';
    $cart_total = isset($input['cart_total']) ? floatval($input['cart_total']) : 0;

    if (empty($promo_code)) {
        echo json_encode([
            'success' => false,
            'message' => 'Promo code is required'
        ]);
        exit;
    }

    if ($cart_total <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid cart total: ' . $cart_total . '. Please add items to your cart.'
        ]);
        exit;
    }

    // Database connection
    require_once('../settings/core.php');
    require_once('../settings/db_class.php');

    $db = new db_connection();
    if (!$db->db_connect()) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    $conn = $db->db_conn();

    // Check promo code
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
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired promo code: ' . $promo_code
        ]);
        exit;
    }

    $promo = $result->fetch_assoc();

    // Check expiry
    if ($promo['end_date'] && strtotime($promo['end_date']) < time()) {
        echo json_encode([
            'success' => false,
            'message' => 'This promo code has expired'
        ]);
        exit;
    }

    // Check usage limit
    if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
        echo json_encode([
            'success' => false,
            'message' => 'This promo code has reached its usage limit'
        ]);
        exit;
    }

    // Check minimum order
    if ($cart_total < $promo['min_order_amount']) {
        echo json_encode([
            'success' => false,
            'message' => 'Minimum order amount of GHS ' . number_format($promo['min_order_amount'], 2) . ' required'
        ]);
        exit;
    }

    // Calculate discount
    $discount_amount = 0;
    if ($promo['discount_type'] === 'percentage') {
        $discount_amount = ($cart_total * $promo['discount_value']) / 100;
        if ($promo['max_discount_amount'] && $discount_amount > $promo['max_discount_amount']) {
            $discount_amount = $promo['max_discount_amount'];
        }
    } else {
        $discount_amount = min($promo['discount_value'], $cart_total);
    }

    $new_total = max(0, $cart_total - $discount_amount);

    // Success response
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
        'savings' => round($discount_amount, 2),
        'debug_source' => $source
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db->db_close();
    }
}
?>