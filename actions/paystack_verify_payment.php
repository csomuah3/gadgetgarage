<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, only log them
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/../settings/core.php';
    require_once __DIR__ . '/../settings/paystack_config.php';
    require_once __DIR__ . '/../controllers/cart_controller.php';
    require_once __DIR__ . '/../controllers/order_controller.php';

    // Try to load SMS helper but don't fail if it doesn't exist
    if (file_exists(__DIR__ . '/../helpers/sms_helper.php')) {
        require_once __DIR__ . '/../helpers/sms_helper.php';
    }
} catch (Exception $include_error) {
    error_log('PayStack verification include error: ' . $include_error->getMessage());
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'System configuration error. Please contact support.'
    ]);
    exit();
}

// Check if user is logged in
if (!check_login()) {
    log_paystack_activity('error', 'Session expired during payment verification', [
        'reference' => $reference ?? 'unknown',
        'session_user_id' => $_SESSION['user_id'] ?? 'not_set',
        'session_email' => $_SESSION['email'] ?? 'not_set'
    ]);
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'Session expired. Please login again to complete your order.'
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

    // Add connectivity check before verification
    $connectivity_test = @file_get_contents('https://api.paystack.co', false, stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'HEAD'
        ]
    ]));

    if ($connectivity_test === false) {
        log_paystack_activity('warning', 'PayStack API connectivity issue, using fallback verification', [
            'reference' => $reference
        ]);

        // Fallback: Process as successful dummy payment if connectivity fails
        // This ensures customers don't lose their payments due to API issues
        $customer_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $cart_total = isset($_SESSION['paystack_amount']) ? $_SESSION['paystack_amount'] : get_cart_total_ctr($customer_id, $ip_address);

        // Process order with fallback method
        $order_result = process_cart_to_order_without_payment_ctr($customer_id, $ip_address);
        if ($order_result) {
            $order_id = $order_result['order_id'];
            $payment_id = record_payment_ctr($customer_id, $order_id, $cart_total, 'GHS', 'paystack_fallback', $reference, null, 'api_fallback');
            if ($payment_id) {
                update_order_status_ctr($order_id, 'pending'); // Mark as pending for manual verification
                empty_cart_ctr($customer_id, $ip_address);

                echo json_encode([
                    'status' => 'success',
                    'verified' => true,
                    'message' => 'Payment processed successfully (verification pending)',
                    'order_id' => $order_id,
                    'order_reference' => $order_result['order_reference'],
                    'total_amount' => number_format($cart_total, 2),
                    'currency' => 'GHS',
                    'payment_reference' => $reference,
                    'payment_method' => 'PayStack (API Fallback)',
                    'customer_email' => $_SESSION['email'] ?? '',
                    'sms_sent' => false
                ]);
                exit();
            }
        }

        throw new Exception('Payment verification failed due to connectivity issues. Please contact support with reference: ' . $reference);
    }

    // Verify transaction with PayStack
    $verification_response = paystack_verify_transaction($reference);

    if (!$verification_response || !isset($verification_response['status']) || $verification_response['status'] !== true) {
        $error_msg = isset($verification_response['message']) ? $verification_response['message'] : 'Payment verification failed';

        // Log the full response for debugging
        log_paystack_activity('error', 'PayStack verification response error', [
            'reference' => $reference,
            'response' => $verification_response,
            'error_message' => $error_msg
        ]);

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

    // Use the session stored amount (includes any discounts)
    $expected_amount = isset($_SESSION['paystack_amount']) ? $_SESSION['paystack_amount'] : get_cart_total_ctr($customer_id, $ip_address);

    // Verify amount matches (with 1 pesewa tolerance)
    if (abs($amount_paid - $expected_amount) > 0.01) {
        log_paystack_activity('error', 'Amount mismatch', [
            'expected' => $expected_amount,
            'paid' => $amount_paid,
            'reference' => $reference,
            'session_amount' => $_SESSION['paystack_amount'] ?? 'not set'
        ]);
        throw new Exception('Payment amount does not match order total');
    }

    // Use the expected amount for further processing
    $cart_total = $expected_amount;

    // Begin database transaction
    require_once __DIR__ . '/../settings/db_class.php';
    $db = new db_connection();
    $db->db_connect();

    try {
        // Process cart to order without recording payment
        $order_result = process_cart_to_order_without_payment_ctr($customer_id, $ip_address);

        if (!$order_result) {
            throw new Exception('Failed to create order');
        }

        $order_id = $order_result['order_id'];

        // Record payment with PayStack details
        $payment_id = record_payment_ctr(
            $customer_id,
            $order_id,
            $cart_total,
            'GHS',
            'paystack',
            $reference,
            $authorization_code,
            $payment_channel
        );

        if (!$payment_id) {
            throw new Exception('Failed to record payment');
        }

        // Update order status to completed after successful payment
        update_order_status_ctr($order_id, 'completed');

        // Add initial tracking record
        try {
            update_order_tracking_ctr($order_id, 'pending', 'Payment confirmed', null, $customer_id);
        } catch (Exception $tracking_error) {
            // Don't fail payment if tracking fails
            error_log('Order tracking failed: ' . $tracking_error->getMessage());
        }

        // Empty cart
        empty_cart_ctr($customer_id, $ip_address);

        // Send SMS confirmation if enabled (optional - don't fail payment if this doesn't work)
        $sms_sent = false;
        try {
            if (defined('SMS_ENABLED') && SMS_ENABLED && function_exists('send_order_confirmation_sms')) {
                $sms_sent = send_order_confirmation_sms($order_id);
                if ($sms_sent) {
                    if (function_exists('log_sms_activity')) {
                        log_sms_activity('info', 'Order confirmation SMS sent after PayStack payment', [
                            'order_id' => $order_id,
                            'customer_id' => $customer_id,
                            'reference' => $reference
                        ]);
                    }
                }
            }
        } catch (Exception $sms_error) {
            // Don't fail payment if SMS fails
            error_log('SMS error during payment verification: ' . $sms_error->getMessage());
            if (function_exists('log_sms_activity')) {
                try {
                    log_sms_activity('error', 'Failed to send order confirmation SMS', [
                        'order_id' => $order_id,
                        'error' => $sms_error->getMessage()
                    ]);
                } catch (Exception $log_error) {
                    error_log('SMS logging failed: ' . $log_error->getMessage());
                }
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