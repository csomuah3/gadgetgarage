<?php
session_start();
header('Content-Type: application/json');

// Simple test version that always returns success
try {
    // Get reference from input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $reference = isset($input['reference']) ? trim($input['reference']) : 'test-reference';

    error_log("PayStack Simple Verification: Reference = " . $reference);

    // Always return success for testing
    echo json_encode([
        'status' => 'success',
        'verified' => true,
        'message' => 'Payment successful! Order confirmed (simple mode).',
        'order_id' => rand(1000, 9999), // Generate a random order ID for testing
        'order_reference' => 'ORD' . date('YmdHis'),
        'total_amount' => '100.00',
        'currency' => 'GHS',
        'payment_reference' => $reference,
        'payment_method' => 'PayStack',
        'customer_email' => $_SESSION['email'] ?? 'test@example.com',
        'sms_sent' => false
    ]);

} catch (Exception $e) {
    error_log("PayStack Simple Verification Error: " . $e->getMessage());

    // Still return success in test mode
    echo json_encode([
        'status' => 'success',
        'verified' => true,
        'message' => 'Payment processed (emergency fallback).',
        'order_id' => rand(1000, 9999),
        'order_reference' => 'ORD' . date('YmdHis'),
        'total_amount' => '0.00',
        'currency' => 'GHS',
        'payment_reference' => 'fallback-ref',
        'payment_method' => 'PayStack',
        'customer_email' => 'test@example.com',
        'sms_sent' => false
    ]);
}
?>