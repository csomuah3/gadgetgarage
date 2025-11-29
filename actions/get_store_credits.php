<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../helpers/store_credit_helper.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to view store credits'
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

try {
    $customer_id = $_SESSION['user_id'];
    $storeCreditHelper = new StoreCreditHelper();

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'get_available';

    switch ($action) {
        case 'get_available':
            $credits = $storeCreditHelper->getAvailableCredits($customer_id);
            $total_available = $storeCreditHelper->getTotalAvailableCredit($customer_id);

            echo json_encode([
                'status' => 'success',
                'credits' => $credits,
                'total_available' => $total_available,
                'formatted_total' => 'GH₵ ' . number_format($total_available, 2)
            ]);
            break;

        case 'preview_application':
            $order_total = floatval($input['order_total'] ?? 0);

            if ($order_total <= 0) {
                throw new Exception('Invalid order total');
            }

            $preview = $storeCreditHelper->previewCreditApplication($customer_id, $order_total);

            echo json_encode([
                'status' => 'success',
                'preview' => $preview,
                'savings' => 'GH₵ ' . number_format($preview['total_applicable'], 2),
                'final_total' => 'GH₵ ' . number_format($preview['final_total'], 2)
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    error_log("Store credits error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>