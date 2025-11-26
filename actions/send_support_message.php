<?php
// Handle AJAX support message submissions from chatbot
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../controllers/support_controller.php';

header('Content-Type: application/json');
ob_start(); // Start output buffering

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get input data - could be from chatbot or contact form
    $customer_name = trim($_POST['name'] ?? $_POST['full_name'] ?? $_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['email'] ?? $_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['phone'] ?? $_POST['customer_phone'] ?? $_POST['guest_phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Chat Support');
    $message = trim($_POST['message'] ?? '');

    // If logged in, get user info from session
    $customer_id = null;
    if (check_login()) {
        $customer_id = $_SESSION['user_id'] ?? null;
        // Get customer name from database if not provided
        if (empty($customer_name)) {
            require_once __DIR__ . '/../settings/db_class.php';
            $db = new db_connection();
            if ($db->db_connect()) {
                $customer_query = "SELECT customer_name FROM customer WHERE customer_id = " . intval($customer_id);
                $customer_data = $db->db_fetch_one($customer_query);
                if ($customer_data) {
                    $customer_name = $customer_data['customer_name'];
                }
            }
        }
        // Get customer email from database if not provided
        if (empty($customer_email)) {
            if (!isset($db)) {
                $db = new db_connection();
                $db->db_connect();
            }
            $email_query = "SELECT customer_email FROM customer WHERE customer_id = " . intval($customer_id);
            $email_data = $db->db_fetch_one($email_query);
            if ($email_data) {
                $customer_email = $email_data['customer_email'];
            }
        }
        // Get customer phone from database if not provided
        if (empty($customer_phone)) {
            if (!isset($db)) {
                $db = new db_connection();
                $db->db_connect();
            }
            $phone_query = "SELECT customer_contact FROM customer WHERE customer_id = " . intval($customer_id);
            $phone_data = $db->db_fetch_one($phone_query);
            if ($phone_data) {
                $customer_phone = $phone_data['customer_contact'];
            }
        }
    }

    // Validate required fields
    if (empty($message)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    if (empty($customer_name)) {
        $customer_name = 'Anonymous User';
    }

    if (empty($customer_email)) {
        $customer_email = 'noemail@guest.com';
    }

    // Phone is optional, but if provided, clean it
    if (!empty($customer_phone)) {
        $customer_phone = preg_replace('/[^0-9+]/', '', $customer_phone);
    } else {
        $customer_phone = '';
    }

    // Database connection for rate limiting check
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    // Rate limiting - check if user has sent too many messages recently
    $rate_limit_query = "SELECT COUNT(*) as message_count
                         FROM support_messages
                         WHERE (customer_email = '" . mysqli_real_escape_string($db->db_conn(), $customer_email) . "' 
                                OR customer_id = " . ($customer_id ? intval($customer_id) : 'NULL') . ")
                         AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)";
    
    $rate_limit_result = $db->db_fetch_one($rate_limit_query);
    $recent_messages = $rate_limit_result['message_count'] ?? 0;

    if ($recent_messages >= 3) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Please wait a moment before sending another message']);
        exit;
    }

    // Determine priority based on subject
    $priority = 'normal';
    if (in_array($subject, ['device_quality', 'tech_revival'])) {
        $priority = 'high';
    } elseif ($subject === 'repair') {
        $priority = 'normal';
    }

    // Insert directly using db_connection class to ensure all fields are saved
    // This bypasses the phone validation in the controller function
    $customer_id_sql = $customer_id ? intval($customer_id) : 'NULL';
    $customer_name_escaped = mysqli_real_escape_string($db->db_conn(), $customer_name);
    $customer_email_escaped = mysqli_real_escape_string($db->db_conn(), $customer_email);
    $customer_phone_escaped = mysqli_real_escape_string($db->db_conn(), $customer_phone ?: '');
    $subject_escaped = mysqli_real_escape_string($db->db_conn(), $subject);
    $message_escaped = mysqli_real_escape_string($db->db_conn(), $message);
    $priority_escaped = mysqli_real_escape_string($db->db_conn(), $priority);

    // Check if customer_email column exists in the table
    $check_email_column = "SHOW COLUMNS FROM support_messages LIKE 'customer_email'";
    $email_column_exists = $db->db_fetch_one($check_email_column);

    if ($email_column_exists) {
        // Table has customer_email column
        $insert_sql = "INSERT INTO support_messages 
                       (customer_id, customer_name, customer_email, customer_phone, subject, message, priority, status)
                       VALUES ($customer_id_sql, '$customer_name_escaped', '$customer_email_escaped', 
                               '$customer_phone_escaped', '$subject_escaped', '$message_escaped', 
                               '$priority_escaped', 'new')";
    } else {
        // Table doesn't have customer_email column
        $insert_sql = "INSERT INTO support_messages 
                       (customer_id, customer_name, customer_phone, subject, message, priority, status)
                       VALUES ($customer_id_sql, '$customer_name_escaped', 
                               '$customer_phone_escaped', '$subject_escaped', '$message_escaped', 
                               '$priority_escaped', 'new')";
    }

    if (!$db->db_write_query($insert_sql)) {
        throw new Exception('Failed to save message to database: ' . mysqli_error($db->db_conn()));
    }

    $message_id = mysqli_insert_id($db->db_conn());
    
    if (!$message_id) {
        // Try to get the ID using MAX query as fallback
        $last_id_result = $db->db_fetch_one("SELECT MAX(message_id) as id FROM support_messages 
                                             WHERE customer_name = '$customer_name_escaped' 
                                             AND subject = '$subject_escaped' 
                                             ORDER BY created_at DESC LIMIT 1");
        if ($last_id_result && $last_id_result['id']) {
            $message_id = $last_id_result['id'];
        } else {
            throw new Exception('Failed to retrieve message ID after insertion');
        }
    }

    if ($message_id) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully! Our support team will respond shortly.',
            'message_id' => $message_id
        ]);
    } else {
        throw new Exception('Failed to save message to database');
    }

} catch (Exception $e) {
    ob_end_clean();
    error_log("Support message error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while sending your message. Please try again.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}

exit();
?>