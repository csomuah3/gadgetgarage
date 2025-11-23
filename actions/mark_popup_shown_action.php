<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../settings/core.php');

try {
    // Check if user is logged in
    $is_logged_in = check_login();

    if (!$is_logged_in) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }

    $customer_id = $_SESSION['user_id'];

    require_once(__DIR__ . '/../settings/db_class.php');
    $db = new db_connection();

    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();

    // First, check if the newsletter_popup_shown column exists, if not create it
    $check_column_sql = "SHOW COLUMNS FROM customer LIKE 'newsletter_popup_shown'";
    $result = $conn->query($check_column_sql);

    if ($result->num_rows == 0) {
        // Column doesn't exist, create it
        $add_column_sql = "ALTER TABLE customer ADD COLUMN newsletter_popup_shown TINYINT(1) DEFAULT 0";
        $conn->query($add_column_sql);
    }

    // Update the user's newsletter popup status
    $stmt = $conn->prepare("UPDATE customer SET newsletter_popup_shown = 1 WHERE customer_id = ?");
    $stmt->bind_param('i', $customer_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Newsletter popup marked as shown'
        ]);
    } else {
        throw new Exception('Failed to update popup status');
    }

} catch (Exception $e) {
    error_log('Mark popup shown error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark popup as shown: ' . $e->getMessage()
    ]);
}
?>