<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Simple debug version of the promo validator
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    }

    // Get all possible input methods
    $raw_input = file_get_contents('php://input');
    $post_data = $_POST;
    $request_data = $_REQUEST;

    $debug_info = [
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not_set',
        'raw_input_length' => strlen($raw_input),
        'raw_input' => $raw_input,
        'post_data' => $post_data,
        'request_data' => $request_data,
        'headers' => getallheaders(),
    ];

    // Try JSON decode
    $json_data = null;
    if (!empty($raw_input)) {
        $json_data = json_decode($raw_input, true);
        $debug_info['json_decode_result'] = $json_data;
        $debug_info['json_error'] = json_last_error_msg();
    }

    // Determine final input
    $input = null;
    if ($json_data) {
        $input = $json_data;
        $debug_info['input_source'] = 'json';
    } elseif (!empty($post_data)) {
        $input = $post_data;
        $debug_info['input_source'] = 'post';
    } elseif (!empty($request_data)) {
        $input = $request_data;
        $debug_info['input_source'] = 'request';
    }

    $debug_info['final_input'] = $input;

    // Check required parameters
    if (!$input || !isset($input['promo_code']) || !isset($input['cart_total'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters in debug version',
            'debug' => $debug_info
        ]);
        exit;
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Debug validation successful!',
        'promo_code' => $input['promo_code'],
        'cart_total' => $input['cart_total'],
        'debug' => $debug_info
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Debug error: ' . $e->getMessage(),
        'debug' => isset($debug_info) ? $debug_info : []
    ]);
}
?>