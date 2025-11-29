<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';
require_once __DIR__ . '/../controllers/cart_controller.php';
require_once __DIR__ . '/../controllers/order_controller.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'Please login to verify payment'
    ]);
    exit;
}

// Get verification reference
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$reference = isset($input['reference']) ? trim($input['reference']) : null;

if (!$reference) {
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'No payment reference provided'
    ]);
    exit;
}

try {
    // Verify transaction with PayStack
    $verification_response = paystack_verify_transaction($reference);

    if ($verification_response && isset($verification_response['status']) && $verification_response['status'] === true) {
        // Payment verified successfully
        $customer_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // Process cart to order
        $order_result = process_cart_to_order_without_payment_ctr($customer_id, $ip_address);

        if ($order_result) {
            $order_id = $order_result['order_id'];
            $cart_total = $order_result['total_amount'];

            // Record payment
            $payment_id = record_payment_ctr(
                $customer_id,
                $order_id,
                $cart_total,
                'GHS',
                'paystack',
                $reference
            );

            if ($payment_id) {
                // Update order status
                update_order_status_ctr($order_id, 'completed');

                // Assign tracking number
                assign_tracking_number_ctr($order_id);

                // Empty cart
                empty_cart_ctr($customer_id, $ip_address);

                // Send customer order confirmation SMS
                if (defined('SMS_ENABLED') && SMS_ENABLED) {
                    try {
                        require_once __DIR__ . '/../helpers/sms_helper.php';
                        $customer_sms_result = send_order_confirmation_sms($order_id);
                        if ($customer_sms_result) {
                            error_log("Customer order confirmation SMS sent successfully for order ID: $order_id");
                        } else {
                            error_log("Failed to send customer order confirmation SMS for order ID: $order_id");
                        }
                    } catch (Exception $customer_sms_error) {
                        // Log but don't fail the order
                        error_log('Customer SMS notification error: ' . $customer_sms_error->getMessage());
                        error_log('Customer SMS error trace: ' . $customer_sms_error->getTraceAsString());
                    }
                }

                // Send admin SMS notification for new order
                if (defined('ADMIN_SMS_ENABLED') && ADMIN_SMS_ENABLED && defined('ADMIN_NEW_ORDER_SMS_ENABLED') && ADMIN_NEW_ORDER_SMS_ENABLED) {
                    try {
                        require_once __DIR__ . '/../helpers/sms_helper.php';
                        send_admin_new_order_sms($order_id);
                    } catch (Exception $admin_sms_error) {
                        // Log but don't fail the order
                        error_log('Admin SMS notification error: ' . $admin_sms_error->getMessage());
                    }
                }

                // Clear session data
                unset($_SESSION['paystack_reference']);
                unset($_SESSION['paystack_amount']);
                unset($_SESSION['paystack_email']);
                unset($_SESSION['paystack_timestamp']);

                echo json_encode([
                    'status' => 'success',
                    'verified' => true,
                    'message' => 'Payment verified successfully',
                    'order_id' => $order_id,
                    'order_reference' => $order_result['order_reference'],
                    'total_amount' => number_format($cart_total, 2),
                    'currency' => 'GHS',
                    'payment_reference' => $reference,
                    'payment_method' => 'PayStack',
                    'customer_email' => $_SESSION['email'] ?? ''
                ]);
            } else {
                throw new Exception('Failed to record payment');
            }
        } else {
            throw new Exception('Failed to create order');
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'verified' => false,
            'message' => 'Payment verification failed'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'Verification error: ' . $e->getMessage()
    ]);
}
?>