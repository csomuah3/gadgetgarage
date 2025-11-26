<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint working',
        'session_check' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? 'not_set',
        'post_data' => $_POST,
        'request_method' => $_SERVER['REQUEST_METHOD']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>