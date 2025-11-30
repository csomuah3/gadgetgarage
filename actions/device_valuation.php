<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../helpers/ai_helper.php';

// Check if user is logged in
if (!check_login()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to get device valuation'
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
$device_type = isset($input['device_type']) ? trim($input['device_type']) : '';
$brand = isset($input['brand']) ? trim($input['brand']) : '';
$model = isset($input['model']) ? trim($input['model']) : '';
$condition = isset($input['condition']) ? trim($input['condition']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';

// Validate required fields
if (empty($device_type) || empty($brand) || empty($condition)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Device type, brand, and condition are required'
    ]);
    exit();
}

try {
    // Initialize AI Helper
    $aiHelper = new AIHelper();

    // Get AI valuation
    $ai_response = $aiHelper->assessDeviceValue($device_type, $brand, $model, $condition, $description);

    // Parse AI response
    $valuation_data = json_decode($ai_response, true);

    if (!$valuation_data || !isset($valuation_data['cash_value'])) {
        throw new Exception("Invalid AI response format");
    }

    // Ensure numeric values
    $cash_value = floatval($valuation_data['cash_value']);
    $credit_value = floatval($valuation_data['credit_value']);
    $original_retail = floatval($valuation_data['original_retail_estimate']);

    // Enforce minimum quote of 3000 GH₵
    $MINIMUM_QUOTE = 3000;
    if ($cash_value < $MINIMUM_QUOTE) {
        $cash_value = $MINIMUM_QUOTE;
    }

    // Calculate 10% bonus for store credit if not already calculated
    if ($credit_value <= $cash_value) {
        $credit_value = $cash_value * 1.10; // Add 10% bonus
    }
    
    // Ensure credit value also meets minimum
    if ($credit_value < $MINIMUM_QUOTE) {
        $credit_value = $cash_value * 1.10; // Recalculate based on adjusted cash value
    }

    // Format response
    $response = [
        'status' => 'success',
        'valuation' => [
            'cash_value' => $cash_value,
            'credit_value' => $credit_value,
            'original_retail_estimate' => $original_retail,
            'condition_grade' => $valuation_data['condition_grade'] ?? 'Good',
            'value_reasoning' => $valuation_data['value_reasoning'] ?? 'Valuation based on current market conditions',
            'market_comparison' => $valuation_data['market_comparison'] ?? 'Competitive with current market rates',
            'recommendations' => $valuation_data['recommendations'] ?? 'Consider store credit for 10% bonus value',
            'bonus_amount' => $credit_value - $cash_value
        ],
        'device_info' => [
            'device_type' => $device_type,
            'brand' => $brand,
            'model' => $model,
            'condition' => $condition,
            'description' => $description
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Device valuation error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to get device valuation at this time. Please try again later.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
?>