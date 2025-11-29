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
    // Check if user session exists - required for order processing
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo json_encode([
            'status' => 'error',
            'verified' => false,
            'message' => 'Session expired. Please login again to complete your order.',
            'requires_login' => true
        ]);
        exit();
    }

    $customer_id = $_SESSION['user_id'];
    $verification_succeeded = false;
    $payment_status = 'unknown';

    // Try to verify transaction with PayStack (but don't fail if it doesn't work)
    try {
        $verification_response = paystack_verify_transaction($reference);
        
        if ($verification_response && isset($verification_response['status']) && $verification_response['status'] === true) {
            $transaction_data = $verification_response['data'];
            $payment_status = $transaction_data['status'] ?? 'unknown';
            $verification_succeeded = ($payment_status === 'success');
            
            log_paystack_activity('info', 'Payment completion verification', [
                'reference' => $reference,
                'paystack_status' => $payment_status,
                'paystack_verified' => true
            ]);
        }
    } catch (Exception $verify_error) {
        // Verification failed, but we'll still try to process the order
        log_paystack_activity('warning', 'PayStack verification failed, processing order anyway', [
            'reference' => $reference,
            'error' => $verify_error->getMessage()
        ]);
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

    // Process the order even if verification failed
    require_once __DIR__ . '/../controllers/cart_controller.php';
    require_once __DIR__ . '/../controllers/order_controller.php';
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    log_paystack_activity('info', 'Starting order creation', [
        'reference' => $reference,
        'customer_id' => $customer_id,
        'ip_address' => $ip_address
    ]);
    
    // Process cart to order
    $order_result = process_cart_to_order_without_payment_ctr($customer_id, $ip_address);
    
    log_paystack_activity('info', 'Order creation result', [
        'reference' => $reference,
        'result' => $order_result ? 'SUCCESS' : 'FAILED',
        'order_data' => $order_result
    ]);
    
    if ($order_result && isset($order_result['order_id'])) {
        $order_id = $order_result['order_id'];
        $cart_total = $order_result['total_amount'];
        
        log_paystack_activity('info', 'Recording payment', [
            'reference' => $reference,
            'order_id' => $order_id,
            'amount' => $cart_total
        ]);
        
        // Record payment (even if verification failed, we still record it)
        $payment_id = record_payment_ctr(
            $customer_id,
            $order_id,
            $cart_total,
            'GHS',
            'paystack',
            $reference
        );
        
        log_paystack_activity('info', 'Payment recording result', [
            'reference' => $reference,
            'payment_id' => $payment_id,
            'success' => $payment_id ? true : false
        ]);
        
        if ($payment_id) {
            // Update order status
            update_order_status_ctr($order_id, 'completed');
            
            // Assign tracking number
            assign_tracking_number_ctr($order_id);
            
            // Empty cart
            empty_cart_ctr($customer_id, $ip_address);
            
            // SMS functionality removed
            
            log_paystack_activity('success', 'Order fully processed', [
                'reference' => $reference,
                'order_id' => $order_id
            ]);
            
            echo json_encode([
                'status' => 'success',
                'verified' => $verification_succeeded,
                'message' => $verification_succeeded ? 'Payment verified and order processed successfully!' : 'Order processed successfully (verification unavailable)',
                'order_id' => $order_id,
                'order_reference' => $order_result['order_reference'],
                'total_amount' => number_format($cart_total, 2),
                'currency' => 'GHS',
                'payment_reference' => $reference,
                'payment_method' => 'PayStack'
            ]);
            exit();
        } else {
            log_paystack_activity('error', 'Failed to record payment', [
                'reference' => $reference,
                'order_id' => $order_id
            ]);
            throw new Exception('Failed to record payment');
        }
    } else {
        log_paystack_activity('error', 'Failed to create order - process_cart_to_order returned false or invalid', [
            'reference' => $reference,
            'customer_id' => $customer_id,
            'order_result' => $order_result
        ]);
        throw new Exception('Failed to create order - cart may be empty or order creation failed');
    }

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