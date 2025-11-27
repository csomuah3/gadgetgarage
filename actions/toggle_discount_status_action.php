<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/discount_controller.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!check_login()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Your session has expired. Please log in again to continue.',
        'action' => 'redirect',
        'redirect' => '../login/login.php'
    ]);
    exit;
}

if (!check_admin()) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Access denied. Admin privileges required.',
        'action' => 'redirect',
        'redirect' => '../index.php'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $promo_id = intval($_POST['promo_id'] ?? 0);

    if ($promo_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid discount ID']);
        exit;
    }

    error_log("Toggling discount status for ID: " . $promo_id);

    $result = toggle_discount_status_ctr($promo_id);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Toggle discount status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>