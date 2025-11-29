<?php
session_start();
header('Content-Type: application/json');

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

if (!$admin_notes) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Rejection reason is required'
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

    // Check if request exists and is pending
    $check_sql = "SELECT id FROM device_drop_requests WHERE id = ? AND status = 'pending'";
    $existing = $db->db_prepare_fetch_one($check_sql, 'i', [$request_id]);

    if (!$existing) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Request not found or already processed'
        ]);
        exit();
    }

    // Update request status to rejected
    $update_sql = "UPDATE device_drop_requests SET
                   status = 'rejected',
                   admin_notes = ?,
                   updated_at = NOW()
                   WHERE id = ?";

    if ($db->db_prepare_execute($update_sql, 'si', [$admin_notes, $request_id])) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Request has been rejected successfully.',
            'request_id' => $request_id
        ]);
    } else {
        throw new Exception('Failed to update request status');
    }

} catch (Exception $e) {
    error_log("Device Drop Rejection Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to reject request: ' . $e->getMessage()
    ]);
}
?>