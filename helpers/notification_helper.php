<?php
/**
 * Notification Helper Functions
 */

require_once(__DIR__ . '/../settings/db_class.php');

/**
 * Create a new notification
 */
function create_notification($customer_id, $type, $title, $message, $related_id = null, $related_table = null, $icon = 'bell', $priority = 'normal', $action_url = null) {
    try {
        $db = new db_connection();
        if (!$db->db_connect()) {
            return false;
        }

        $conn = $db->db_conn();

        $stmt = $conn->prepare("
            INSERT INTO notifications (customer_id, type, title, message, related_id, related_table, icon, priority, action_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param('isssisss',
            $customer_id, $type, $title, $message,
            $related_id, $related_table, $icon, $priority, $action_url
        );

        $result = $stmt->execute();

        if (isset($db)) {
            $db->db_close();
        }

        return $result;

    } catch (Exception $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user notifications
 */
function get_user_notifications($customer_id, $limit = 10, $unread_only = false) {
    try {
        $db = new db_connection();
        if (!$db->db_connect()) {
            return [];
        }

        $conn = $db->db_conn();

        $where_clause = $unread_only ? "WHERE customer_id = ? AND is_read = 0" : "WHERE customer_id = ?";

        $stmt = $conn->prepare("
            SELECT * FROM notifications
            {$where_clause}
            ORDER BY created_at DESC
            LIMIT ?
        ");

        if ($unread_only) {
            $stmt->bind_param('ii', $customer_id, $limit);
        } else {
            $stmt->bind_param('ii', $customer_id, $limit);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);

        if (isset($db)) {
            $db->db_close();
        }

        return $notifications;

    } catch (Exception $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 */
function get_unread_notification_count($customer_id) {
    try {
        $db = new db_connection();
        if (!$db->db_connect()) {
            return 0;
        }

        $conn = $db->db_conn();

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE customer_id = ? AND is_read = 0");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (isset($db)) {
            $db->db_close();
        }

        return $row['count'] ?? 0;

    } catch (Exception $e) {
        error_log("Get unread count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id, $customer_id) {
    try {
        $db = new db_connection();
        if (!$db->db_connect()) {
            return false;
        }

        $conn = $db->db_conn();

        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND customer_id = ?");
        $stmt->bind_param('ii', $notification_id, $customer_id);
        $result = $stmt->execute();

        if (isset($db)) {
            $db->db_close();
        }

        return $result;

    } catch (Exception $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read
 */
function mark_all_notifications_read($customer_id) {
    try {
        $db = new db_connection();
        if (!$db->db_connect()) {
            return false;
        }

        $conn = $db->db_conn();

        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE customer_id = ? AND is_read = 0");
        $stmt->bind_param('i', $customer_id);
        $result = $stmt->execute();

        if (isset($db)) {
            $db->db_close();
        }

        return $result;

    } catch (Exception $e) {
        error_log("Mark all notifications read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to get time ago string
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' mins ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31104000) return floor($time/2592000) . ' months ago';
    return floor($time/31104000) . ' years ago';
}

// Notification Creation Helpers for Different Types

/**
 * Create order notification
 */
function notify_order_update($customer_id, $order_id, $status, $message) {
    $icons = [
        'processing' => 'clock',
        'confirmed' => 'check-circle',
        'shipped' => 'truck',
        'delivered' => 'gift',
        'cancelled' => 'times-circle'
    ];

    $icon = $icons[$status] ?? 'shopping-cart';
    $title = "Order #{$order_id} " . ucfirst($status);

    return create_notification(
        $customer_id,
        'order',
        $title,
        $message,
        $order_id,
        'orders',
        $icon,
        'normal',
        "views/order_details.php?id={$order_id}"
    );
}

/**
 * Create appointment notification
 */
function notify_appointment_update($customer_id, $appointment_id, $type, $message) {
    $icons = [
        'confirmed' => 'calendar-check',
        'reminder' => 'bell',
        'completed' => 'check-circle',
        'cancelled' => 'times-circle'
    ];

    $icon = $icons[$type] ?? 'calendar';
    $titles = [
        'confirmed' => 'Appointment Confirmed',
        'reminder' => 'Appointment Reminder',
        'completed' => 'Appointment Completed',
        'cancelled' => 'Appointment Cancelled'
    ];

    $title = $titles[$type] ?? 'Appointment Update';

    return create_notification(
        $customer_id,
        'appointment',
        $title,
        $message,
        $appointment_id,
        'repair_appointments',
        $icon,
        $type === 'reminder' ? 'high' : 'normal'
    );
}

/**
 * Create refurbishment notification
 */
function notify_refurbishment_update($customer_id, $request_id, $status, $message) {
    $icons = [
        'started' => 'tools',
        'in_progress' => 'cog',
        'quality_check' => 'search',
        'completed' => 'check-circle',
        'failed' => 'exclamation-triangle'
    ];

    $icon = $icons[$status] ?? 'wrench';
    $title = "Refurbishment " . str_replace('_', ' ', ucwords($status, '_'));

    return create_notification(
        $customer_id,
        'refurbishment',
        $title,
        $message,
        $request_id,
        'refurbishment_requests',
        $icon,
        $status === 'failed' ? 'high' : 'normal'
    );
}

/**
 * Create support notification
 */
function notify_support_response($customer_id, $message_id, $admin_name = 'Support Team') {
    return create_notification(
        $customer_id,
        'support',
        'Support Response Received',
        "The {$admin_name} has replied to your support message.",
        $message_id,
        'support_messages',
        'envelope',
        'normal'
    );
}

/**
 * Create payment notification
 */
function notify_payment_update($customer_id, $payment_id, $status, $amount, $order_id = null) {
    $icons = [
        'success' => 'credit-card',
        'failed' => 'exclamation-triangle',
        'pending' => 'clock',
        'refunded' => 'undo'
    ];

    $icon = $icons[$status] ?? 'credit-card';
    $title = "Payment " . ucfirst($status);
    $message = $status === 'success' ?
        "Payment of GH₵{$amount} was successful" :
        "Payment of GH₵{$amount} {$status}";

    return create_notification(
        $customer_id,
        'payment',
        $title,
        $message,
        $payment_id,
        'payment',
        $icon,
        $status === 'failed' ? 'high' : 'normal',
        $order_id ? "views/order_details.php?id={$order_id}" : null
    );
}

/**
 * Create promotion notification
 */
function notify_promotion($customer_id, $title, $message, $promotion_id = null) {
    return create_notification(
        $customer_id,
        'promotion',
        $title,
        $message,
        $promotion_id,
        'promotions',
        'tag',
        'normal'
    );
}
?>