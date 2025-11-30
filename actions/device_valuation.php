<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../helpers/ai_helper.php';

// Note: Device valuation is available to all users for device drop requests

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

    // Log the AI response for debugging
    error_log("AI Valuation Response: " . $ai_response);

    // Parse AI response
    $valuation_data = json_decode($ai_response, true);

    if (!$valuation_data || json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        throw new Exception("Invalid JSON response from AI service");
    }

    if (!isset($valuation_data['cash_value'])) {
        error_log("Missing cash_value in AI response: " . json_encode($valuation_data));
        throw new Exception("Invalid AI response format - missing cash_value");
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

    // Provide fallback valuation based on device type and condition
    try {
        $fallback_valuation = getFallbackValuation($device_type, $brand, $condition);
        error_log("Using fallback valuation for $device_type $brand");
        echo json_encode($fallback_valuation);
    } catch (Exception $fallback_error) {
        error_log("Fallback valuation failed: " . $fallback_error->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to get device valuation at this time. Please try again later.',
            'debug' => $e->getMessage() // Remove in production
        ]);
    }
}

/**
 * Provide fallback valuation when AI service fails
 */
function getFallbackValuation($device_type, $brand, $condition) {
    // Base values by device type
    $base_values = [
        'smartphone' => 4500,
        'tablet' => 3500,
        'laptop' => 8000,
        'desktop' => 6000,
        'camera' => 5000
    ];

    // Condition multipliers
    $condition_multipliers = [
        'excellent' => 0.85,
        'good' => 0.70,
        'fair' => 0.50
    ];

    // Brand multipliers
    $brand_multipliers = [
        'apple' => 1.3,
        'samsung' => 1.2,
        'google' => 1.1,
        'sony' => 1.1,
        'dell' => 1.0,
        'hp' => 0.9,
        'lenovo' => 0.9
    ];

    $base_value = $base_values[strtolower($device_type)] ?? 3000;
    $condition_mult = $condition_multipliers[strtolower($condition)] ?? 0.5;
    $brand_mult = $brand_multipliers[strtolower($brand)] ?? 1.0;

    $cash_value = max(3000, $base_value * $condition_mult * $brand_mult);
    $credit_value = $cash_value * 1.10;

    return [
        'status' => 'success',
        'valuation' => [
            'cash_value' => $cash_value,
            'credit_value' => $credit_value,
            'original_retail_estimate' => $cash_value * 2.5,
            'condition_grade' => ucfirst($condition),
            'value_reasoning' => "Estimated value based on device type, brand, and condition. AI service temporarily unavailable.",
            'market_comparison' => "Competitive with current market rates for similar devices.",
            'recommendations' => "Consider store credit option for 10% bonus value.",
            'bonus_amount' => $credit_value - $cash_value
        ],
        'device_info' => [
            'device_type' => $device_type,
            'brand' => $brand,
            'model' => '',
            'condition' => $condition,
            'description' => ''
        ]
    ];
}
?>