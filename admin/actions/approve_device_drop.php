<?php
// Start output buffering to catch any errors/warnings
ob_start();

// Suppress error display (we'll catch them in logs)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// Clear any output that might have been generated
ob_clean();

require_once __DIR__ . '/../../settings/core.php';
require_admin(); // Only admins can access this

require_once __DIR__ . '/../../settings/db_class.php';

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
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request data'
    ]);
    exit();
}

$request_id = isset($input['request_id']) ? intval($input['request_id']) : 0;
$admin_notes = isset($input['admin_notes']) ? trim($input['admin_notes']) : '';

if (!$request_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Request ID is required'
    ]);
    exit();
}

try {
    $db = new db_connection();
    if (!$db->db_connect()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Get request details
    $request_sql = "SELECT * FROM device_drop_requests WHERE id = ? AND status = 'pending'";
    $request = $db->db_prepare_fetch_one($request_sql, 'i', [$request_id]);

    if (!$request) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Request not found or already processed'
        ]);
        exit();
    }

    // Update request status
    $update_sql = "UPDATE device_drop_requests SET
                   status = 'approved',
                   admin_notes = ?,
                   updated_at = NOW()
                   WHERE id = ?";

    if (!$db->db_prepare_execute($update_sql, 'si', [$admin_notes, $request_id])) {
        throw new Exception('Failed to update request status');
    }

    $message = '';

    // Handle payment processing based on payment method
    if ($request['payment_method'] === 'store_credit' && $request['final_amount'] > 0) {
        // Create store credit for the user

        // First, find the user by email
        $user_check_sql = "SELECT customer_id FROM customer WHERE customer_email = ? LIMIT 1";
        $user = $db->db_prepare_fetch_one($user_check_sql, 's', [$request['email']]);

        if ($user) {
            $user_id = $user['customer_id'];

            // Generate credit reference ID
            $credit_reference = 'DDC' . str_pad($request_id, 6, '0', STR_PAD_LEFT);

            // Insert store credit using correct database schema
            $credit_sql = "INSERT INTO store_credits (
                customer_id, credit_amount, remaining_amount, source, device_drop_id,
                admin_notes, status, expires_at, created_at, admin_verified, verified_at
            ) VALUES (
                ?, ?, ?, 'device_drop', ?,
                ?, 'active', DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW(), 1, NOW()
            )";

            $credit_description = "Store credit from device drop request #$request_id";

            if ($db->db_prepare_execute($credit_sql, 'iddiss', [
                $user_id,
                $request['final_amount'],  // credit_amount
                $request['final_amount'],  // remaining_amount (starts equal to credit_amount)
                $credit_reference,          // source
                $request_id,               // device_drop_id
                $credit_description        // admin_notes
            ])) {
                $message = "Request approved! Store credit of GH₵" . number_format($request['final_amount'], 2) . " has been added to the customer's account.";
                error_log("Store credit created for user $user_id: GH₵{$request['final_amount']} (Request #$request_id)");
            } else {
                error_log("Failed to create store credit for request #$request_id");
                $message = "Request approved, but store credit creation failed. Please create manually.";
            }
        } else {
            error_log("No customer found for email {$request['email']} - request #$request_id");
            $message = "Request approved, but customer not found. Please create store credit manually.";
        }

    } else if ($request['payment_method'] === 'cash' && $request['final_amount'] > 0) {
        // For cash payments, just show dummy success message
        $message = "Request approved! Cash payment of GH₵" . number_format($request['final_amount'], 2) . " has been processed.";

    } else {
        $message = "Request approved successfully.";
    }

    // Clean any output and send JSON response
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'request_id' => $request_id,
        'payment_method' => $request['payment_method'],
        'amount' => $request['final_amount']
    ]);
    ob_end_flush();
    exit();

} catch (Exception $e) {
    error_log("Device Drop Approval Error: " . $e->getMessage());
    
    // Clean any output and send error JSON
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to approve request: ' . $e->getMessage()
    ]);
    ob_end_flush();
    exit();
}

// Fallback - should never reach here
ob_clean();
echo json_encode([
    'status' => 'error',
    'message' => 'Unexpected error occurred'
]);
ob_end_flush();
exit();
?>