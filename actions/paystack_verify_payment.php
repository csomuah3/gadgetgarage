<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';
require_once __DIR__ . '/../controllers/cart_controller.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../helpers/sms_helper.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Session expired. Please login again.'
    ]);
    exit();
}

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
    log_paystack_activity('info', 'Verifying PayStack transaction', ['reference' => $reference]);

    // Verify transaction with PayStack
    $verification_response = paystack_verify_transaction($reference);

    if (!$verification_response || !isset($verification_response['status']) || $verification_response['status'] !== true) {
        $error_msg = isset($verification_response['message']) ? $verification_response['message'] : 'Payment verification failed';
        throw new Exception($error_msg);
    }

    // Extract transaction data
    $transaction_data = $verification_response['data'];
    $payment_status = $transaction_data['status'];
    $amount_paid = pesewas_to_amount($transaction_data['amount']); // Convert from pesewas
    $customer_email = $transaction_data['customer']['email'];
    $authorization = $transaction_data['authorization'] ?? [];
    $authorization_code = $authorization['authorization_code'] ?? '';
    $payment_channel = $authorization['channel'] ?? 'card';

    log_paystack_activity('info', 'PayStack verification successful', [
        'reference' => $reference,
        'status' => $payment_status,
        'amount' => $amount_paid,
        'channel' => $payment_channel
    ]);

    // Validate payment status
    if ($payment_status !== 'success') {
        throw new Exception('Payment was not successful. Status: ' . ucfirst($payment_status));
    }

    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Get cart items and validate amount
    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    if (!$cart_items || count($cart_items) == 0) {
        throw new Exception('Cart is empty');
    }

    $cart_total = get_cart_total_ctr($customer_id, $ip_address);

    // Verify amount matches (with 1 pesewa tolerance)
    if (abs($amount_paid - $cart_total) > 0.01) {
        log_paystack_activity('error', 'Amount mismatch', [
            'expected' => $cart_total,
            'paid' => $amount_paid,
            'reference' => $reference
        ]);
        throw new Exception('Payment amount does not match order total');
    }

    // Begin database transaction
    require_once __DIR__ . '/../settings/db_class.php';
    $db = new db_connection();
    $db->db_connect();

    try {
        // Process cart to order
        $order_result = process_cart_to_order_ctr($customer_id, $ip_address);

        if (!$order_result) {
            throw new Exception('Failed to create order');
        }

        $order_id = $order_result['order_id'];

        // Record payment with PayStack details
        $payment_id = record_payment_ctr(
            $cart_total,
            $customer_id,
            $order_id,
            'GHS',
            date('Y-m-d'),
            'paystack',
            $reference,
            $authorization_code,
            $payment_channel
        );

        if (!$payment_id) {
            throw new Exception('Failed to record payment');
        }

        // Empty cart
        empty_cart_ctr($customer_id, $ip_address);

        // Send SMS confirmation if enabled
        $sms_sent = false;
        if (SMS_ENABLED) {
            try {
                $sms_sent = send_order_confirmation_sms($order_id);
                if ($sms_sent) {
                    log_sms_activity('info', 'Order confirmation SMS sent after PayStack payment', [
                        'order_id' => $order_id,
                        'customer_id' => $customer_id,
                        'reference' => $reference
                    ]);
                }
            } catch (Exception $sms_error) {
                log_sms_activity('error', 'Failed to send order confirmation SMS', [
                    'order_id' => $order_id,
                    'error' => $sms_error->getMessage()
                ]);
            }
        }

        // Clear session payment data
        unset($_SESSION['paystack_reference']);
        unset($_SESSION['paystack_amount']);
        unset($_SESSION['paystack_email']);
        unset($_SESSION['paystack_timestamp']);

        log_paystack_activity('info', 'Order completed successfully', [
            'order_id' => $order_id,
            'reference' => $reference,
            'amount' => $cart_total,
            'sms_sent' => $sms_sent
        ]);

        // Return success response
        echo json_encode([
            'status' => 'success',
            'verified' => true,
            'message' => 'Payment successful! Order confirmed.',
            'order_id' => $order_id,
            'order_reference' => $order_result['order_reference'],
            'total_amount' => number_format($cart_total, 2),
            'currency' => 'GHS',
            'payment_reference' => $reference,
            'payment_method' => ucfirst($payment_channel),
            'customer_email' => $customer_email,
            'sms_sent' => $sms_sent
        ]);

    } catch (Exception $e) {
        // Log the database error
        log_paystack_activity('error', 'Database error during order processing', [
            'reference' => $reference,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }

} catch (Exception $e) {
    log_paystack_activity('error', 'PayStack verification failed', [
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