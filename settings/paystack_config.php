<?php
/**
 * PayStack Configuration for Gadget Garage
 * Configured for ecommerce_2025A_chelsea_somuah database
 */

// PayStack API Configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_518f25129d73cf0383fc383569fd28ad1e8bfd4f');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_aba089a6fc33225c7c71f9e1c5207881b9933201');
define('PAYSTACK_BASE_URL', 'https://api.paystack.co');
// Dynamic callback URL based on current request
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '169.239.251.102:442';
$base_path = dirname(dirname($_SERVER['PHP_SELF']));
define('PAYSTACK_CALLBACK_URL', $protocol . '://' . $host . $base_path . '/views/paystack_callback.php');

// Payment Settings
define('PAYSTACK_CURRENCY', 'GHS'); // Ghana Cedis
define('PAYSTACK_ENVIRONMENT', 'test'); // test or live
define('PAYSTACK_TIMEOUT', 30); // API timeout in seconds

// Transaction Settings
define('TRANSACTION_PREFIX', 'GG'); // Gadget Garage prefix
define('MINIMUM_AMOUNT', 100); // Minimum amount in pesewas (1 GHS)

/**
 * Initialize PayStack transaction
 * @param string $email Customer email
 * @param int $amount Amount in pesewas
 * @param string $reference Transaction reference
 * @param array $metadata Additional transaction data
 * @return array API response
 */
function paystack_initialize_transaction($email, $amount, $reference, $metadata = []) {
    $url = PAYSTACK_BASE_URL . "/transaction/initialize";

    $fields = [
        'email' => $email,
        'amount' => $amount,
        'reference' => $reference,
        'currency' => PAYSTACK_CURRENCY,
        'callback_url' => PAYSTACK_CALLBACK_URL,
        'metadata' => $metadata
    ];

    return paystack_api_call($url, 'POST', $fields);
}

/**
 * Verify PayStack transaction
 * @param string $reference Transaction reference
 * @return array API response
 */
function paystack_verify_transaction($reference) {
    $url = PAYSTACK_BASE_URL . "/transaction/verify/" . urlencode($reference);
    return paystack_api_call($url, 'GET');
}

/**
 * Make PayStack API call
 * @param string $url API endpoint
 * @param string $method HTTP method
 * @param array $data Request data
 * @return array Response data
 */
function paystack_api_call($url, $method = 'GET', $data = null) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => PAYSTACK_TIMEOUT,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
            "Content-Type: application/json",
        ],
    ]);

    if ($method === 'POST' && $data) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        throw new Exception("cURL Error: " . $err);
    }

    $decoded_response = json_decode($response, true);

    if ($http_code !== 200) {
        $error_message = isset($decoded_response['message']) ? $decoded_response['message'] : 'PayStack API error';
        throw new Exception("PayStack API Error (HTTP $http_code): " . $error_message);
    }

    return $decoded_response;
}

/**
 * Generate unique transaction reference
 * @param int $customer_id Customer ID
 * @return string Transaction reference
 */
function generate_transaction_reference($customer_id = null) {
    $timestamp = time();
    $random = rand(1000, 9999);
    $customer_part = $customer_id ? $customer_id : 'GUEST';
    return TRANSACTION_PREFIX . '-' . $customer_part . '-' . $timestamp . '-' . $random;
}

/**
 * Convert amount to pesewas
 * @param float $amount Amount in Ghana Cedis
 * @return int Amount in pesewas
 */
function amount_to_pesewas($amount) {
    return intval($amount * 100);
}

/**
 * Convert pesewas to amount
 * @param int $pesewas Amount in pesewas
 * @return float Amount in Ghana Cedis
 */
function pesewas_to_amount($pesewas) {
    return floatval($pesewas / 100);
}

/**
 * Validate PayStack webhook signature
 * @param string $input Request body
 * @param string $signature Webhook signature
 * @return bool Validation result
 */
function validate_paystack_webhook($input, $signature) {
    return hash_equals($signature, hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY));
}

/**
 * Log PayStack transaction
 * @param string $level Log level
 * @param string $message Log message
 * @param array $context Additional context
 */
function log_paystack_activity($level, $message, $context = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context
    ];

    $log_line = sprintf("[%s] %s: %s %s\n",
        $log_entry['timestamp'],
        $log_entry['level'],
        $log_entry['message'],
        !empty($context) ? json_encode($context) : ''
    );

    $log_file = __DIR__ . '/../logs/paystack.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}
?>