<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../settings/core.php';
require_admin(); // Only admins can update refund status

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
    $input = $_POST;
}

// Extract and validate input
$refund_id = isset($input['refund_id']) ? intval($input['refund_id']) : 0;
$new_status = isset($input['status']) ? trim($input['status']) : '';
$admin_notes = isset($input['admin_notes']) ? trim($input['admin_notes']) : '';

// Validate required fields
if (!$refund_id || !$new_status) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Refund ID and status are required'
    ]);
    exit();
}

// Validate status values
$valid_statuses = ['pending', 'approved', 'rejected', 'processed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid status value'
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

    // Check if refund exists
    $check_sql = "SELECT refund_id, status, order_id FROM refund_requests WHERE refund_id = ?";
    $existing_refund = $db->db_fetch_one($check_sql, [$refund_id]);

    if (!$existing_refund) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Refund request not found'
        ]);
        exit();
    }

    // Update refund status
    $update_sql = "UPDATE refund_requests SET
                   status = ?,
                   admin_notes = ?,
                   updated_at = NOW()
                   WHERE refund_id = ?";

    $params = [$new_status, $admin_notes, $refund_id];
    $result = $db->db_query($update_sql, $params);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Refund status updated successfully',
            'refund_id' => $refund_id,
            'new_status' => $new_status,
            'order_id' => $existing_refund['order_id']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update refund status'
        ]);
    }

} catch (Exception $e) {
    error_log("Refund status update error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update refund status: ' . $e->getMessage()
    ]);
}
?>