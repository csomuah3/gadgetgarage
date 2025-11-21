<?php
/**
 * Order SMS Action Handler
 * Handles order-related SMS notifications
 */

session_start();
require_once __DIR__ . '/../helpers/sms_helper.php';
require_once __DIR__ . '/../classes/sms_class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? '';
    $order_id = $input['order_id'] ?? 0;

    if (empty($order_id)) {
        throw new Exception('Order ID is required');
    }

    switch ($action) {
        case 'order_confirmed':
            $result = send_order_confirmation_sms($order_id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Order confirmation SMS sent' : 'Failed to send SMS'
            ]);
            break;

        case 'payment_received':
            $result = send_payment_confirmation_sms($order_id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Payment confirmation SMS sent' : 'Failed to send SMS'
            ]);
            break;

        case 'order_shipped':
            require_once __DIR__ . '/../controllers/order_controller.php';
            $order = get_order_details_ctr($order_id);

            if (!$order) {
                throw new Exception('Order not found');
            }

            $phone = format_phone_number($order['phone']);
            if (!$phone) {
                throw new Exception('Invalid phone number');
            }

            $delivery_date = $input['delivery_date'] ?? date('M j, Y', strtotime('+2 days'));

            $template_data = [
                'name' => $order['customer_name'],
                'order_id' => $order_id,
                'delivery_date' => $delivery_date
            ];

            $sms = new SMSService();
            $result = $sms->sendSMS($phone, SMS_TYPE_ORDER_SHIPPED, $template_data, SMS_PRIORITY_HIGH);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Order shipped SMS sent' : 'Failed to send SMS'
            ]);
            break;

        case 'order_delivered':
            require_once __DIR__ . '/../controllers/order_controller.php';
            $order = get_order_details_ctr($order_id);

            if (!$order) {
                throw new Exception('Order not found');
            }

            $phone = format_phone_number($order['phone']);
            if (!$phone) {
                throw new Exception('Invalid phone number');
            }

            $template_data = [
                'name' => $order['customer_name'],
                'order_id' => $order_id
            ];

            $sms = new SMSService();
            $result = $sms->sendSMS($phone, SMS_TYPE_ORDER_DELIVERED, $template_data, SMS_PRIORITY_MEDIUM);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Order delivered SMS sent' : 'Failed to send SMS'
            ]);
            break;

        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    log_sms_activity('error', 'Order SMS action error', [
        'action' => $action ?? 'unknown',
        'order_id' => $order_id ?? null,
        'error' => $e->getMessage()
    ]);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}