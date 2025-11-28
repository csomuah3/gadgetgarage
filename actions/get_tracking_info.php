<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to track your order'
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

$order_reference = '';
if (isset($input['order_reference'])) {
    $order_reference = trim($input['order_reference']);
} elseif (isset($_POST['order_reference'])) {
    $order_reference = trim($_POST['order_reference']);
}

// Log the received data for debugging
error_log("Order tracking request - Order reference: " . $order_reference);

// Validate order reference
if (!$order_reference) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Order reference is required'
    ]);
    exit();
}

try {
    $customer_id = $_SESSION['user_id'];

    // Log the tracking attempt
    error_log("Attempting to track order: $order_reference for customer: $customer_id");

    // Get order details using tracking function
    $tracking_result = get_order_tracking_details($order_reference);

    if (!$tracking_result) {
        error_log("Tracking result is null for order: $order_reference");
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found. Please check your order reference number.'
        ]);
        exit();
    }

    if (!isset($tracking_result['order'])) {
        error_log("No order data in tracking result for: $order_reference");
        echo json_encode([
            'status' => 'error',
            'message' => 'Order data not found. Please contact support.'
        ]);
        exit();
    }

    $order = $tracking_result['order'];

    // Ensure the order belongs to the current customer
    if ($order['customer_id'] != $customer_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found or access denied'
        ]);
        exit();
    }

    // Get order items for additional details if needed
    $order_items = get_order_details_ctr($order['order_id']);

    // Calculate order status based on date
    $order_date = new DateTime($order['order_date']);
    $current_date = new DateTime();
    $days_since_order = $order_date->diff($current_date)->days;

    $calculated_status = 'processing';
    if ($days_since_order >= 4) {
        $calculated_status = 'delivered';
    } elseif ($days_since_order >= 2) {
        $calculated_status = 'out_for_delivery';
    } elseif ($days_since_order >= 1) {
        $calculated_status = 'shipped';
    }

    // Generate tracking number if not exists
    if (empty($order['tracking_number'])) {
        $invoice_ref = $order['invoice_no'] ?? $order_reference;
        $tracking_number = 'TRK' . strtoupper(substr($invoice_ref, -8)) . rand(100, 999);

        // Update order with tracking number
        try {
            $pdo = new PDO("mysql:host=" . SERVER . ";dbname=" . DATABASE, USERNAME, PASSWD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("UPDATE orders SET tracking_number = ? WHERE order_id = ?");
            $stmt->execute([$tracking_number, $order['order_id']]);

            $order['tracking_number'] = $tracking_number;
        } catch (PDOException $e) {
            error_log("Failed to update tracking number: " . $e->getMessage());
        }
    }

    // Return order tracking information
    echo json_encode([
        'status' => 'success',
        'order' => [
            'order_id' => $order['order_id'],
            'order_reference' => $order['invoice_no'],
            'order_date' => $order['order_date'],
            'total_amount' => $order['total_amount'],
            'order_status' => $calculated_status,
            'tracking_number' => $order['tracking_number'],
            'customer_name' => $order['customer_name'] ?? $_SESSION['fname'] . ' ' . $_SESSION['lname'],
            'delivery_address' => $order['delivery_address'] ?? 'N/A',
            'items_count' => count($order_items),
            'days_since_order' => $days_since_order
        ],
        'message' => 'Order tracking information retrieved successfully'
    ]);

} catch (Exception $e) {
    error_log("Tracking error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve tracking information: ' . $e->getMessage()
    ]);
}
?>