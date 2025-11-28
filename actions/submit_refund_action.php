<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../settings/db_class.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to submit refund requests'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    $input = $_POST;
}

// Extract and validate input
$order_id = isset($input['orderId']) ? intval($input['orderId']) : 0;
$order_reference = isset($input['orderReference']) ? trim($input['orderReference']) : '';
$first_name = isset($input['firstName']) ? trim($input['firstName']) : '';
$last_name = isset($input['lastName']) ? trim($input['lastName']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$refund_amount = isset($input['refundAmount']) && !empty($input['refundAmount']) ? floatval($input['refundAmount']) : null;
$reason = isset($input['reason']) ? trim($input['reason']) : '';

// Validate required fields
if (!$order_id || !$first_name || !$last_name || !$email || !$phone || !$reason) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All required fields must be filled'
    ]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email address format'
    ]);
    exit();
}

try {
    $customer_id = $_SESSION['user_id'];

    // Verify the order belongs to this customer
    $orders = get_user_orders_ctr($customer_id);
    $order_found = false;

    foreach ($orders as $order) {
        if ($order['order_id'] == $order_id) {
            $order_found = true;
            break;
        }
    }

    if (!$order_found) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found or access denied'
        ]);
        exit();
    }

    // Check if a refund request already exists for this order
    $db = new db_connection();
    $check_sql = "SELECT refund_id FROM refund_requests WHERE order_id = ? AND customer_id = ?";
    $existing_refund = $db->db_fetch_one($check_sql, [$order_reference, $customer_id]);

    if ($existing_refund) {
        echo json_encode([
            'status' => 'error',
            'message' => 'A refund request has already been submitted for this order'
        ]);
        exit();
    }

    // Insert refund request
    $insert_sql = "INSERT INTO refund_requests (
        order_id,
        customer_id,
        first_name,
        last_name,
        email,
        phone,
        refund_amount,
        reason_for_refund,
        request_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $params = [
        $order_reference,
        $customer_id,
        $first_name,
        $last_name,
        $email,
        $phone,
        $refund_amount,
        $reason
    ];

    $result = $db->db_query($insert_sql, $params);

    if ($result) {
        // Get the refund ID
        $refund_id = $db->get_insert_id();

        // Format refund ID with prefix
        $formatted_refund_id = "REF" . str_pad($refund_id, 6, '0', STR_PAD_LEFT);

        echo json_encode([
            'status' => 'success',
            'message' => 'Refund request submitted successfully',
            'refund_id' => $formatted_refund_id,
            'order_reference' => $order_reference
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to submit refund request. Please try again.'
        ]);
    }

} catch (Exception $e) {
    error_log("Refund request error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit refund request: ' . $e->getMessage()
    ]);
}
?>