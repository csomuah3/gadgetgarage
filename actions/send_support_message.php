<?php
// Handle AJAX support message submissions from chatbot
header('Content-Type: application/json');
ob_clean(); // Clear any existing output

try {
    // Simple test first - just return success
    echo json_encode([
        'success' => true,
        'message' => 'Test response',
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'post_data' => $_POST
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit();
?>