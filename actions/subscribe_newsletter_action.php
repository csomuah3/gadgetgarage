<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once('../settings/core.php');
    require_once('../settings/db_class.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email'])) {
        throw new Exception('Email is required');
    }

    $email = trim(strtolower($input['email']));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Connect to database
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'You are already subscribed to our newsletter!',
            'already_subscribed' => true
        ]);
        exit;
    }

    // Insert new subscription
    $insert_stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $insert_stmt->bind_param('s', $email);

    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Successfully subscribed! You\'ll receive exclusive Black Friday deals.',
            'already_subscribed' => false
        ]);
    } else {
        throw new Exception('Failed to subscribe to newsletter');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db->db_close();
    }
}
?>