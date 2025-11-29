<?php
session_start();
require_once __DIR__ . '/../controllers/cart_controller.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../helpers/sms_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$customer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to checkout']);
    exit;
}

try {
    $cart_items = get_user_cart_ctr($customer_id, $ip_address);

    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }

    $cart_total = get_cart_total_ctr($customer_id, $ip_address);

    if ($cart_total <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart total']);
        exit;
    }

    $order_result = process_cart_to_order_ctr($customer_id, $ip_address);

    if (!$order_result) {
        echo json_encode(['success' => false, 'message' => 'Failed to process order']);
        exit;
    }

    empty_cart_ctr($customer_id, $ip_address);

    // Send order confirmation SMS
    $sms_sent = false;
    if (SMS_ENABLED) {
        try {
            $sms_sent = send_order_confirmation_sms($order_result['order_id']);
            if ($sms_sent) {
                log_sms_activity('info', 'Order confirmation SMS sent successfully', [
                    'order_id' => $order_result['order_id'],
                    'customer_id' => $customer_id
                ]);
            }
        } catch (Exception $sms_error) {
            log_sms_activity('error', 'Failed to send order confirmation SMS', [
                'order_id' => $order_result['order_id'],
                'customer_id' => $customer_id,
                'error' => $sms_error->getMessage()
            ]);
        }
    }

    // Send admin SMS notification for new order
    if (defined('ADMIN_SMS_ENABLED') && ADMIN_SMS_ENABLED && defined('ADMIN_NEW_ORDER_SMS_ENABLED') && ADMIN_NEW_ORDER_SMS_ENABLED) {
        try {
            send_admin_new_order_sms($order_result['order_id']);
        } catch (Exception $admin_sms_error) {
            // Log but don't fail the order
            log_sms_activity('error', 'Failed to send admin new order SMS', [
                'order_id' => $order_result['order_id'],
                'error' => $admin_sms_error->getMessage()
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order processed successfully',
        'order_id' => $order_result['order_id'],
        'order_reference' => $order_result['order_reference'],
        'total_amount' => number_format($order_result['total_amount'], 2),
        'sms_sent' => $sms_sent
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>