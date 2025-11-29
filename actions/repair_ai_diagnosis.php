<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../helpers/ai_helper.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    $device_type = isset($_POST['device_type']) ? trim($_POST['device_type']) : '';
    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    $model = isset($_POST['model']) ? trim($_POST['model']) : '';
    $issue_description = isset($_POST['issue_description']) ? trim($_POST['issue_description']) : '';
    $base_cost = isset($_POST['base_cost']) ? floatval($_POST['base_cost']) : null;

    if (empty($issue_description)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please describe the problem with your device before analyzing.'
        ]);
        exit;
    }

    $ai = new AIHelper();
    $raw_json = $ai->analyzeRepairIssue($device_type, $brand, $model, $issue_description, $base_cost);

    $data = json_decode($raw_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        error_log('AI Repair JSON decode error: ' . json_last_error_msg());
        error_log('AI raw response: ' . $raw_json);
        echo json_encode([
            'status' => 'error',
            'message' => 'AI returned an invalid response. Please try again.'
        ]);
        exit;
    }

    $result = [
        'likely_issue' => $data['likely_issue'] ?? '',
        'recommended_repair_type' => $data['recommended_repair_type'] ?? '',
        'estimated_cost_range' => $data['estimated_cost_range'] ?? '',
        'estimated_time' => $data['estimated_time'] ?? '',
        'urgency' => $data['urgency'] ?? '',
        'notes' => $data['notes'] ?? '',
    ];

    echo json_encode([
        'status' => 'success',
        'analysis' => $result,
    ]);
} catch (Exception $e) {
    error_log('AI Repair Diagnosis Error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to analyze issue right now. Please try again later.'
    ]);
}


