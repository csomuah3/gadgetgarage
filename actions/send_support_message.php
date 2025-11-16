<?php
// Handle AJAX support message submissions from chatbot
header('Content-Type: application/json');

require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/support_controller.php');

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get form data
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate input
    if (empty($subject) || empty($message)) {
        throw new Exception('Please fill in all required fields');
    }

    // Get user info if logged in
    $is_logged_in = check_login();
    $customer_id = null;
    $name = 'Guest User';
    $email = 'guest@gadgetgarage.com';

    if ($is_logged_in) {
        $customer_id = $_SESSION['user_id'] ?? null;
        $name = $_SESSION['name'] ?? 'Logged User';
        $email = $_SESSION['email'] ?? 'user@gadgetgarage.com';
    }

    // Create support message
    $message_id = create_support_message_ctr($customer_id, $name, $email, $subject, $message);

    if ($message_id) {
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully',
            'message_id' => $message_id
        ]);
    } else {
        throw new Exception('Failed to save message to database');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>