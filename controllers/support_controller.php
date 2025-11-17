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
        return false;
    }

    // Sanitize inputs
    $customer_id = $customer_id ? intval($customer_id) : null;
    $name = mysqli_real_escape_string($db->db_conn(), trim($name));
    $phone = mysqli_real_escape_string($db->db_conn(), trim($phone));
    $subject = mysqli_real_escape_string($db->db_conn(), trim($subject));
    $message = mysqli_real_escape_string($db->db_conn(), trim($message));

    // Validate phone (basic validation)
    if (empty($phone) || strlen($phone) < 8) {
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
        return false;
    }

    mysqli_stmt_bind_param($stmt, "isssss", $customer_id, $name, $phone, $subject, $message, $priority);

    $result = mysqli_stmt_execute($stmt);
    $message_id = mysqli_insert_id($db->db_conn());

    mysqli_stmt_close($stmt);

    // If message was created successfully, prepare data for email notifications
    if ($result && $message_id) {
        // Get the complete message details for email
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

    $db->db_disconnect();

    return $result ? $message_id : false;
}

/**
 * Get all support messages for admin view
 */
function get_all_support_messages_ctr($status = null, $limit = null) {
    $db = new db_connection();

    if (!$db->db_connect()) {
        return false;
    }

    $sql = "SELECT message_id, customer_id, customer_name, customer_email, customer_phone, subject,
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
    $db->db_disconnect();

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
    $db->db_disconnect();

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
    $db->db_disconnect();

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

    $sql = "UPDATE support_messages
            SET admin_response = '$response',
                response_date = NOW(),
                status = 'resolved'";

    if ($admin_id) {
        $sql .= ", assigned_to = $admin_id";
    }

    $sql .= " WHERE message_id = $message_id";

    $result = $db->db_query($sql);
    $db->db_disconnect();

    return $result;
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

    $db->db_disconnect();

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
    $db->db_disconnect();

    return $result;
}
?>