<?php
/**
 * SMS Action Handler
 * Handles SMS sending requests
 */

session_start();
require_once __DIR__ . '/../helpers/sms_helper.php';
require_once __DIR__ . '/../classes/sms_class.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

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
    $sms = new SMSService();

    switch ($action) {
        case 'send_single':
            $phone = $input['phone'] ?? '';
            $message = $input['message'] ?? '';
            $type = $input['type'] ?? 'custom';
            $priority = $input['priority'] ?? SMS_PRIORITY_MEDIUM;

            if (empty($phone) || empty($message)) {
                throw new Exception('Phone number and message are required');
            }

            $formatted_phone = format_phone_number($phone);
            if (!$formatted_phone) {
                throw new Exception('Invalid phone number format');
            }

            $result = $sms->sendCustomSMS($formatted_phone, $message, $priority);

            echo json_encode([
                'success' => true,
                'message' => 'SMS sent successfully',
                'sms_id' => $result
            ]);
            break;

        case 'send_order_confirmation':
            $order_id = $input['order_id'] ?? 0;

            if (empty($order_id)) {
                throw new Exception('Order ID is required');
            }

            $result = send_order_confirmation_sms($order_id);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Order confirmation SMS sent' : 'Failed to send SMS'
            ]);
            break;

        case 'send_payment_confirmation':
            $order_id = $input['order_id'] ?? 0;

            if (empty($order_id)) {
                throw new Exception('Order ID is required');
            }

            $result = send_payment_confirmation_sms($order_id);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Payment confirmation SMS sent' : 'Failed to send SMS'
            ]);
            break;

        case 'send_cart_abandonment':
            $user_id = $input['user_id'] ?? 0;

            if (empty($user_id)) {
                throw new Exception('User ID is required');
            }

            $result = send_cart_abandonment_sms($user_id);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Cart abandonment SMS sent' : 'Failed to send SMS'
            ]);
            break;

        case 'test_sms':
            $phone = $input['phone'] ?? '';

            if (empty($phone)) {
                throw new Exception('Phone number is required for test');
            }

            $formatted_phone = format_phone_number($phone);
            if (!$formatted_phone) {
                throw new Exception('Invalid phone number format');
            }

            $test_message = "Test SMS from Gadget Garage! Your SMS system is working correctly. Time: " . date('Y-m-d H:i:s');
            $result = $sms->sendCustomSMS($formatted_phone, $test_message, SMS_PRIORITY_HIGH);

            echo json_encode([
                'success' => true,
                'message' => 'Test SMS sent successfully',
                'sms_id' => $result
            ]);
            break;

        case 'get_sms_stats':
            $days = $input['days'] ?? 30;
            $stats = get_sms_statistics($days);

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        case 'get_sms_balance':
            $balance = $sms->getAccountBalance();

            echo json_encode([
                'success' => true,
                'balance' => $balance
            ]);
            break;

        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    log_sms_activity('error', 'SMS action error', [
        'action' => $action ?? 'unknown',
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? null
    ]);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}