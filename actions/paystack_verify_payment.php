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

        // Check for store credits applied (from session)
        $store_credits_applied = isset($_SESSION['store_credits_applied']) ? floatval($_SESSION['store_credits_applied']) : 0;
        
        // Process cart to order
        $order_result = process_cart_to_order_without_payment_ctr($customer_id, $ip_address);

        if ($order_result) {
            $order_id = $order_result['order_id'];
            $cart_total = $order_result['total_amount'];
            
            // Apply store credits if any were used
            $final_total = $cart_total;
            if ($store_credits_applied > 0) {
                require_once __DIR__ . '/../helpers/store_credit_helper.php';
                $storeCreditHelper = new StoreCreditHelper();
                $credit_result = $storeCreditHelper->applyCreditsToOrder($customer_id, $cart_total, $order_id);
                
                if ($credit_result && $credit_result['applied_amount'] > 0) {
                    $final_total = $credit_result['remaining_total'];
                    error_log("Applied GH₵{$credit_result['applied_amount']} in store credits to order #$order_id. Remaining: GH₵$final_total");
                }
            }

            // Record payment (using the amount after store credits if applied)
            $payment_id = record_payment_ctr(
                $customer_id,
                $order_id,
                $final_total,
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

                // SMS functionality removed

                // Clear session data
                unset($_SESSION['paystack_reference']);
                unset($_SESSION['paystack_amount']);
                unset($_SESSION['paystack_email']);
                unset($_SESSION['paystack_timestamp']);
                unset($_SESSION['store_credits_applied']);

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