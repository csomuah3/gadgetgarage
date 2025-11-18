<?php
/**
 * Support Message Controller
 * Handles customer support messages and admin responses
 */

// Include database connection
require_once(__DIR__ . '/../settings/db_class.php');

/**
 * Create a new support message
 */
function create_support_message_ctr($customer_id, $name, $phone, $subject, $message) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        error_log("Support controller: Database connection failed");
        return false;
    }

    error_log("Support controller: Database connected successfully");

    // Sanitize inputs
    $customer_id = $customer_id ? intval($customer_id) : null;
    $name = mysqli_real_escape_string($db->db_conn(), trim($name));
    $phone = mysqli_real_escape_string($db->db_conn(), trim($phone));
    $subject = mysqli_real_escape_string($db->db_conn(), trim($subject));
    $message = mysqli_real_escape_string($db->db_conn(), trim($message));

    // Validate phone number (basic validation)
    if (empty($phone) || strlen($phone) < 10) {
        return false;
    }

    // Determine priority based on subject
    $priority = 'normal';
    if (in_array($subject, ['device_quality', 'tech_revival'])) {
        $priority = 'high';
    } elseif ($subject === 'repair') {
        $priority = 'normal';
    }

    // Create the message
    $sql = "INSERT INTO support_messages
            (customer_id, customer_name, customer_phone, subject, message, priority, status)
            VALUES (?, ?, ?, ?, ?, ?, 'new')";

    $stmt = mysqli_prepare($db->db_conn(), $sql);

    if (!$stmt) {
        error_log("Support controller: Failed to prepare statement: " . mysqli_error($db->db_conn()));
        return false;
    }

    error_log("Support controller: Statement prepared successfully");

    mysqli_stmt_bind_param($stmt, "isssss", $customer_id, $name, $phone, $subject, $message, $priority);

    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        error_log("Support controller: Failed to execute statement: " . mysqli_error($db->db_conn()));
        mysqli_stmt_close($stmt);
        return false;
    }

    $message_id = mysqli_insert_id($db->db_conn());
    error_log("Support controller: Message inserted with ID: $message_id");

    mysqli_stmt_close($stmt);

    // If mysqli_insert_id returns 0, try to get the ID from the last inserted row
    if ($message_id == 0) {
        $last_id_result = $db->db_fetch_one("SELECT MAX(message_id) as id FROM support_messages WHERE customer_id = $customer_id AND customer_name = '$name' ORDER BY created_at DESC LIMIT 1");
        if ($last_id_result) {
            $message_id = $last_id_result['id'];
            error_log("Support controller: Retrieved ID using MAX query: $message_id");
        }
    }

    // If message was created successfully, prepare data for email notifications
    if ($result && $message_id) {
        // Get the complete message details for notifications
        $message_details = [
            'message_id' => $message_id,
            'customer_id' => $customer_id,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'priority' => $priority,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Send email notifications (temporarily disabled to debug)
        // if (file_exists(__DIR__ . '/../helpers/email_helper.php')) {
        //     require_once(__DIR__ . '/../helpers/email_helper.php');

        //     // Send notification to admin
        //     send_support_notification_email($message_details);

        //     // Send confirmation to customer
        //     send_customer_confirmation_email($message_details);
        // }
    }

    return $result && $message_id > 0 ? $message_id : false;
}

/**
 * Get all support messages for admin view
 */
function get_all_support_messages_ctr($status = null, $limit = null) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $sql = "SELECT message_id, customer_id, customer_name, customer_phone, subject,
                   LEFT(message, 100) as message_preview, message, status, priority,
                   assigned_to, admin_response, response_date, created_at, updated_at
            FROM support_messages";

    if ($status) {
        $sql .= " WHERE status = '" . mysqli_real_escape_string($db->db_conn(), $status) . "'";
    }

    $sql .= " ORDER BY
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    WHEN 'low' THEN 4
                END ASC,
                created_at DESC";

    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }

    $result = $db->db_fetch_all($sql);

    return $result;
}

/**
 * Get a single support message by ID
 */
function get_support_message_by_id_ctr($message_id) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $message_id = intval($message_id);
    $sql = "SELECT * FROM support_messages WHERE message_id = $message_id";

    $result = $db->db_fetch_one($sql);

    return $result;
}

/**
 * Update support message status
 */
function update_support_message_status_ctr($message_id, $status, $assigned_to = null) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $message_id = intval($message_id);
    $status = mysqli_real_escape_string($db->db_conn(), $status);

    $sql = "UPDATE support_messages SET status = '$status'";

    if ($assigned_to) {
        $assigned_to = intval($assigned_to);
        $sql .= ", assigned_to = $assigned_to";
    }

    $sql .= " WHERE message_id = $message_id";

    $result = $db->db_query($sql);

    return $result;
}

/**
 * Add admin response to support message
 */
function add_admin_response_ctr($message_id, $response, $admin_id = null) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $message_id = intval($message_id);
    $response = mysqli_real_escape_string($db->db_conn(), trim($response));
    $admin_id = $admin_id ? intval($admin_id) : null;

    // Get customer_id for notification
    $customer_query = "SELECT customer_id, customer_name FROM support_messages WHERE message_id = $message_id";
    $customer_data = $db->db_fetch_one($customer_query);

    if (!$customer_data) {
        return false;
    }

    $customer_id = $customer_data['customer_id'];

    // Insert into support_responses table
    $response_sql = "INSERT INTO support_responses (message_id, response_text, admin_id, response_date)
                     VALUES ($message_id, '$response', " . ($admin_id ? $admin_id : 'NULL') . ", NOW())";

    $response_result = $db->db_query($response_sql);

    if (!$response_result) {
        return false;
    }

    // Update support_messages table
    $update_sql = "UPDATE support_messages
                   SET last_response_date = NOW(),
                       response_count = response_count + 1,
                       status = 'in_progress'";

    if ($admin_id) {
        $update_sql .= ", assigned_to = $admin_id";
    }

    $update_sql .= " WHERE message_id = $message_id";

    $update_result = $db->db_query($update_sql);

    // Create notification for customer
    $notification_message = "You have received a response to your support message.";
    $notification_sql = "INSERT INTO notifications (customer_id, message, type, related_id, created_at)
                         VALUES ($customer_id, '$notification_message', 'support_response', $message_id, NOW())";

    $notification_result = $db->db_query($notification_sql);

    return $response_result && $update_result && $notification_result;
}

/**
 * Get notifications for a customer
 */
function get_customer_notifications_ctr($customer_id, $unread_only = false) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return [];
    }

    $customer_id = intval($customer_id);
    $where_clause = $unread_only ? "AND is_read = FALSE" : "";

    $sql = "SELECT * FROM notifications
            WHERE customer_id = $customer_id $where_clause
            ORDER BY created_at DESC";

    return $db->db_fetch_all($sql) ?: [];
}

/**
 * Mark notification as read
 */
function mark_notification_read_ctr($notification_id, $customer_id) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $notification_id = intval($notification_id);
    $customer_id = intval($customer_id);

    $sql = "UPDATE notifications
            SET is_read = TRUE
            WHERE notification_id = $notification_id AND customer_id = $customer_id";

    return $db->db_query($sql);
}

/**
 * Get unread notification count
 */
function get_unread_notification_count_ctr($customer_id) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return 0;
    }

    $customer_id = intval($customer_id);

    $sql = "SELECT COUNT(*) as count FROM notifications
            WHERE customer_id = $customer_id AND is_read = FALSE";

    $result = $db->db_fetch_one($sql);
    return $result ? intval($result['count']) : 0;
}

/**
 * Get support responses for a message
 */
function get_support_responses_ctr($message_id) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return [];
    }

    $message_id = intval($message_id);

    $sql = "SELECT sr.*, c.customer_name as admin_name
            FROM support_responses sr
            LEFT JOIN customer c ON sr.admin_id = c.customer_id
            WHERE sr.message_id = $message_id
            ORDER BY sr.response_date DESC";

    return $db->db_fetch_all($sql) ?: [];
}

/**
 * Get support message statistics for dashboard
 */
function get_support_statistics_ctr() {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $stats = [];

    // Total messages
    $result = $db->db_fetch_one("SELECT COUNT(*) as total FROM support_messages");
    $stats['total'] = $result['total'];

    // Messages by status
    $result = $db->db_fetch_all("SELECT status, COUNT(*) as count FROM support_messages GROUP BY status");
    foreach ($result as $row) {
        $stats[$row['status']] = $row['count'];
    }

    // Messages by priority
    $result = $db->db_fetch_all("SELECT priority, COUNT(*) as count FROM support_messages WHERE status != 'resolved' AND status != 'closed' GROUP BY priority");
    foreach ($result as $row) {
        $stats['priority_' . $row['priority']] = $row['count'];
    }

    // Recent messages (last 24 hours)
    $result = $db->db_fetch_one("SELECT COUNT(*) as count FROM support_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['recent_24h'] = $result['count'];


    return $stats;
}

/**
 * Get customer's support messages
 */
function get_customer_support_messages_ctr($customer_id, $phone = null) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $sql = "SELECT message_id, subject, LEFT(message, 100) as message_preview,
                   status, priority, admin_response, created_at, response_date
            FROM support_messages WHERE ";

    if ($customer_id) {
        $customer_id = intval($customer_id);
        $sql .= "customer_id = $customer_id";
    } else {
        $phone = mysqli_real_escape_string($db->db_conn(), $phone);
        $sql .= "customer_phone = '$phone'";
    }

    $sql .= " ORDER BY created_at DESC";

    $result = $db->db_fetch_all($sql);

    return $result;
}
?>