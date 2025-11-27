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
    // Get and validate form data
    $promo_code = trim($_POST['promo_code'] ?? '');
    $promo_description = trim($_POST['promo_description'] ?? '');
    $discount_type = trim($_POST['discount_type'] ?? '');
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
    $max_discount_amount = floatval($_POST['max_discount_amount'] ?? 0);
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $usage_limit = intval($_POST['usage_limit'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($promo_code)) {
        echo json_encode(['status' => 'error', 'message' => 'Promo code is required']);
        exit;
    }

    if (empty($discount_type)) {
        echo json_encode(['status' => 'error', 'message' => 'Discount type is required']);
        exit;
    }

    if ($discount_value <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Discount value must be greater than 0']);
        exit;
    }

    // Format dates properly for MySQL
    if (!empty($start_date)) {
        $start_date = date('Y-m-d H:i:s', strtotime($start_date));
    } else {
        $start_date = date('Y-m-d H:i:s'); // Default to now
    }

    if (!empty($end_date)) {
        $end_date = date('Y-m-d H:i:s', strtotime($end_date));
    } else {
        $end_date = null;
    }

    error_log("Adding discount with data: " . json_encode([
        'promo_code' => $promo_code,
        'discount_type' => $discount_type,
        'discount_value' => $discount_value,
        'min_order_amount' => $min_order_amount,
        'max_discount_amount' => $max_discount_amount,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'usage_limit' => $usage_limit,
        'is_active' => $is_active
    ]));

    $result = add_discount_ctr(
        $promo_code,
        $promo_description,
        $discount_type,
        $discount_value,
        $min_order_amount,
        $max_discount_amount,
        $start_date,
        $end_date,
        $usage_limit,
        $is_active
    );

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Add discount error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>