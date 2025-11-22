<?php
session_start();
header('Content-Type: application/json');

try {
    require_once('../settings/core.php');
    require_once('../settings/db_class.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!check_login()) {
        throw new Exception('User not logged in');
    }

    $customer_id = get_user_id();

    // Connect to database
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();

    // Update user to mark newsletter popup as shown
    $stmt = $conn->prepare("UPDATE customer SET newsletter_popup_shown = 1 WHERE customer_id = ?");
    $stmt->bind_param('i', $customer_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Popup status updated'
        ]);
    } else {
        throw new Exception('Failed to update popup status');
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