<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../helpers/sms_helper.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Session expired. Please login again to complete your order.'
    ]);
    exit();
}

// Get reference from POST data
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
    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Get cart items
    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    if (!$cart_items || count($cart_items) == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cart is empty'
        ]);
        exit();
    }

    // Get cart total (use discounted amount from session if available)
    $cart_total = isset($_SESSION['paystack_amount']) ? $_SESSION['paystack_amount'] : get_cart_total_ctr($customer_id, $ip_address);

    if ($cart_total <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid cart total'
        ]);
        exit();
    }

    // Begin database transaction
    require_once __DIR__ . '/../settings/db_class.php';
    $db = new db_connection();
    $db->db_connect();

    try {
        // Verify cart still has items before processing
        if (empty($cart_items) || count($cart_items) == 0) {
            throw new Exception('Cart is empty. Cannot create order.');
        }

        // Process cart to order without payment verification (dummy payment)
        $order_result = process_cart_to_order_without_payment_ctr($customer_id, $ip_address);

        if (!$order_result) {
            // More detailed error message
            $error_details = [
                'customer_id' => $customer_id,
                'ip_address' => $ip_address,
                'cart_items_count' => count($cart_items),
                'cart_total' => $cart_total
            ];
            error_log('Order creation failed. Details: ' . json_encode($error_details));
            throw new Exception('Failed to create order. Please ensure your cart has valid items and try again.');
        }

        $order_id = $order_result['order_id'];

        // Record payment with dummy reference
        $payment_id = record_payment_ctr(
            $customer_id,
            $order_id,
            $cart_total,
            'GHS',
            'paystack',
            $reference,
            null, // authorization_code
            'dummy' // payment_channel
        );

        if (!$payment_id) {
            throw new Exception('Failed to record payment');
        }

        // Update order status to completed
        update_order_status_ctr($order_id, 'completed');

        // Empty cart
        empty_cart_ctr($customer_id, $ip_address);

        // Send SMS confirmation if enabled
        $sms_sent = false;
        if (defined('SMS_ENABLED') && SMS_ENABLED) {
            try {
                $sms_sent = send_order_confirmation_sms($order_id);
            } catch (Exception $sms_error) {
                // SMS error is not critical, continue
                error_log('SMS error: ' . $sms_error->getMessage());
            }
        }

        // Clear session payment data
        unset($_SESSION['paystack_reference']);
        unset($_SESSION['paystack_amount']);
        unset($_SESSION['paystack_email']);
        unset($_SESSION['paystack_timestamp']);

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment successful! Order confirmed.',
            'order_id' => $order_id,
            'order_reference' => $order_result['order_reference'] ?? null,
            'total_amount' => number_format($cart_total, 2),
            'currency' => 'GHS',
            'payment_reference' => $reference,
            'payment_method' => 'Dummy Payment',
            'sms_sent' => $sms_sent
        ]);
    } catch (Exception $e) {
        error_log('Database error during dummy payment processing: ' . $e->getMessage());
        throw $e;
    }
} catch (Exception $e) {
    error_log('Dummy payment processing failed: ' . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
