<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to complete payment'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$customer_email = isset($input['email']) ? trim($input['email']) : '';
$custom_total = isset($input['total_amount']) ? floatval($input['total_amount']) : null;

// Validate email
if (!$customer_email || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email address'
    ]);
    exit();
}

try {
    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Get cart total (use custom total if provided for promo discounts)
    $cart_total = $custom_total ?: get_cart_total_ctr($customer_id, $ip_address);

    if ($cart_total <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Your cart is empty or invalid'
        ]);
        exit();
    }

    // Convert amount to pesewas
    $amount_pesewas = amount_to_pesewas($cart_total);

    // Generate unique reference
    $reference = generate_transaction_reference($customer_id);

    log_paystack_activity('info', 'Initializing PayStack transaction', [
        'customer_id' => $customer_id,
        'amount_ghs' => $cart_total,
        'custom_amount' => $custom_total,
        'amount_pesewas' => $amount_pesewas,
        'email' => $customer_email,
        'reference' => $reference
    ]);

    // Prepare metadata
    $metadata = [
        'customer_id' => $customer_id,
        'cart_total' => $cart_total,
        'ip_address' => $ip_address,
        'site' => 'Gadget Garage'
    ];

    // Initialize PayStack transaction
    $paystack_response = paystack_initialize_transaction(
        $customer_email,
        $amount_pesewas,
        $reference,
        $metadata
    );

    if (isset($paystack_response['status']) && $paystack_response['status'] === true) {
        // Store transaction details in session for verification later
        $_SESSION['paystack_reference'] = $reference;
        $_SESSION['paystack_amount'] = $cart_total;
        $_SESSION['paystack_original_amount'] = $custom_total ? get_cart_total_ctr($customer_id, $ip_address) : $cart_total;
        $_SESSION['paystack_email'] = $customer_email;
        $_SESSION['paystack_timestamp'] = time();

        log_paystack_activity('info', 'PayStack transaction initialized successfully', [
            'reference' => $reference,
            'authorization_url' => $paystack_response['data']['authorization_url']
        ]);

        echo json_encode([
            'status' => 'success',
            'authorization_url' => $paystack_response['data']['authorization_url'],
            'reference' => $reference,
            'access_code' => $paystack_response['data']['access_code'],
            'message' => 'Redirecting to PayStack...'
        ]);
    } else {
        $error_message = isset($paystack_response['message']) ? $paystack_response['message'] : 'PayStack API error';
        throw new Exception($error_message);
    }

} catch (Exception $e) {
    log_paystack_activity('error', 'Failed to initialize PayStack transaction', [
        'customer_id' => $customer_id ?? null,
        'email' => $customer_email ?? null,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
        'trace' => $e->getTraceAsString()
    ]);

    // More detailed error for debugging
    $debug_info = [
        'status' => 'error',
        'message' => 'Failed to initialize payment: ' . $e->getMessage(),
        'debug' => [
            'customer_id' => $customer_id ?? 'not set',
            'email' => $customer_email ?? 'not set',
            'cart_total' => $cart_total ?? 'not set',
            'amount_pesewas' => isset($amount_pesewas) ? $amount_pesewas : 'not set',
            'reference' => isset($reference) ? $reference : 'not set',
            'session_user_id' => $_SESSION['user_id'] ?? 'not set',
            'session_email' => $_SESSION['email'] ?? 'not set'
        ]
    ];

    echo json_encode($debug_info);
}
?>