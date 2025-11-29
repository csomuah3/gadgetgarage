<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once(__DIR__ . '/../settings/core.php');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if user is logged in
    if (!check_login()) {
        throw new Exception('You must be logged in');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;

    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }

    // Try to send SMS if helper exists
    $sms_sent = false;
    if (file_exists(__DIR__ . '/../helpers/sms_helper.php')) {
        require_once(__DIR__ . '/../helpers/sms_helper.php');
        
        if (defined('SMS_ENABLED') && SMS_ENABLED && function_exists('send_order_confirmation_sms')) {
            try {
                $sms_sent = send_order_confirmation_sms($order_id);
            } catch (Exception $sms_error) {
                error_log('SMS sending error: ' . $sms_error->getMessage());
            }
        }
    }

    echo json_encode([
        'success' => true,
        'sms_sent' => $sms_sent,
        'message' => $sms_sent ? 'SMS sent successfully' : 'SMS not sent (may have been sent earlier or SMS disabled)'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

