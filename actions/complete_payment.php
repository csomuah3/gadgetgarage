<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../settings/paystack_config.php';

// Get verification reference from POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$reference = isset($input['reference']) ? trim($input['reference']) : null;

if (!$reference) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No payment reference provided'
    ]);
    exit();
}

try {
    // Verify transaction with PayStack first
    $verification_response = paystack_verify_transaction($reference);

    if (!$verification_response || !isset($verification_response['status']) || $verification_response['status'] !== true) {
        $error_msg = isset($verification_response['message']) ? $verification_response['message'] : 'Payment verification failed';
        throw new Exception($error_msg);
    }

    // Extract transaction data
    $transaction_data = $verification_response['data'];
    $payment_status = $transaction_data['status'];

    log_paystack_activity('info', 'Payment completion verification', [
        'reference' => $reference,
        'paystack_status' => $payment_status,
        'paystack_verified' => true
    ]);

    // Validate payment status
    if ($payment_status !== 'success') {
        throw new Exception('Payment was not successful. Status: ' . ucfirst($payment_status));
    }

    // Check if this payment has already been processed
    $db = new db_connection();
    $conn = $db->db_conn();

    if (!$conn) {
        throw new Exception('Database connection failed while checking payment status');
    }

    $check_query = "SELECT * FROM payments WHERE paystack_reference = ?";
    $stmt = $conn->prepare($check_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare payment lookup statement');
    }

    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Payment already processed - return success
        $payment_record = $result->fetch_assoc();

        echo json_encode([
            'status' => 'success',
            'verified' => true,
            'message' => 'Payment already processed successfully!',
            'order_id' => $payment_record['order_id'],
            'already_processed' => true,
            'payment_reference' => $reference
        ]);
        exit();
    }

    // If payment not processed yet, we need user session to continue
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo json_encode([
            'status' => 'error',
            'verified' => false,
            'message' => 'Session expired. Please login again to complete your order.',
            'requires_login' => true
        ]);
        exit();
    }

    // If we reach here, payment is verified by PayStack but not yet processed in our system
    // Redirect to the full verification handler
    echo json_encode([
        'status' => 'success',
        'verified' => true,
        'message' => 'Payment verified. Processing order...',
        'redirect_to_verify' => true,
        'payment_reference' => $reference
    ]);

} catch (Exception $e) {
    log_paystack_activity('error', 'Payment completion failed', [
        'reference' => $reference ?? 'unknown',
        'error' => $e->getMessage()
    ]);

    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => $e->getMessage()
    ]);
}
?>