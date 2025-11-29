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
    $request_sql = "SELECT * FROM device_drop_requests WHERE id = ?";
    $request = $db->db_prepare_fetch_one($request_sql, 'i', [$request_id]);

    if (!$request) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Request not found'
        ]);
        exit();
    }

    // Get request images
    $images_sql = "SELECT image_url, original_filename FROM device_drop_images WHERE request_id = ?";
    $images = $db->db_prepare_fetch_all($images_sql, 'i', [$request_id]) ?: [];

    echo json_encode([
        'status' => 'success',
        'request' => $request,
        'images' => $images
    ]);

} catch (Exception $e) {
    error_log("Device Drop Details Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch request details: ' . $e->getMessage()
    ]);
}
?>