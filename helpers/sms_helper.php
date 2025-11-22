<?php
/**
 * SMS Helper Functions
 * Utility functions for SMS operations
 */

require_once __DIR__ . '/../settings/sms_config.php';
require_once __DIR__ . '/../classes/sms_class.php';

/**
 * Format phone number to international format
 * @param string $phone
 * @param string $country
 * @return string|false
 */
function format_phone_number($phone, $country = 'ghana') {
    global $phone_patterns;

    $phone = preg_replace('/[^0-9+]/', '', $phone);

    if ($country === 'ghana') {
        // Convert Ghana numbers to international format
        if (preg_match('/^0[2-9][0-9]{8}$/', $phone)) {
            return '+233' . substr($phone, 1);
        } elseif (preg_match('/^\+233[2-9][0-9]{8}$/', $phone)) {
            return $phone;
        } elseif (preg_match('/^233[2-9][0-9]{8}$/', $phone)) {
            return '+' . $phone;
        }
    }

    // Validate international format
    if (isset($phone_patterns[$country]) && preg_match($phone_patterns[$country], $phone)) {
        return $phone;
    }

    return false;
}

/**
 * Validate phone number
 * @param string $phone
 * @param string $country
 * @return bool
 */
function is_valid_phone($phone, $country = 'ghana') {
    return format_phone_number($phone, $country) !== false;
}

/**
 * Get SMS template by type and language
 * @param string $type
 * @param string $language
 * @return string|false
 */
function get_sms_template($type, $language = 'en') {
    global $sms_templates;

    if (isset($sms_templates[$type][$language])) {
        return $sms_templates[$type][$language];
    }

    // Fallback to English if language not found
    if (isset($sms_templates[$type]['en'])) {
        return $sms_templates[$type]['en'];
    }

    return false;
}

/**
 * Replace placeholders in SMS template
 * @param string $template
 * @param array $data
 * @return string
 */
function process_sms_template($template, $data) {
    foreach ($data as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    return $template;
}

/**
 * Check if SMS sending is allowed during business hours
 * @return bool
 */
function is_business_hours() {
    global $business_hours;

    date_default_timezone_set($business_hours['timezone']);
    $current_time = date('H:i');

    return ($current_time >= $business_hours['start'] && $current_time <= $business_hours['end']);
}

/**
 * Log SMS activity
 * @param string $level
 * @param string $message
 * @param array $context
 */
function log_sms_activity($level, $message, $context = []) {
    if (!SMS_LOG_ENABLED) return;

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context
    ];

    $log_line = sprintf("[%s] %s: %s %s\n",
        $log_entry['timestamp'],
        $log_entry['level'],
        $log_entry['message'],
        !empty($context) ? json_encode($context) : ''
    );

    // Create logs directory if it doesn't exist
    $log_dir = dirname(SMS_LOG_FILE);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    file_put_contents(SMS_LOG_FILE, $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Send order confirmation SMS
 * @param int $order_id
 * @return bool
 */
function send_order_confirmation_sms($order_id) {
    try {
        require_once __DIR__ . '/../controllers/order_controller.php';
        require_once __DIR__ . '/../settings/db_class.php';

        // Get order with customer information
        $db = new db_connection();
        $sql = "SELECT o.*, u.user_name as customer_name, u.user_contact as phone, py.amt as total_amount
                FROM orders o
                JOIN users u ON o.customer_id = u.user_id
                LEFT JOIN payment py ON o.order_id = py.order_id
                WHERE o.order_id = ?";
        $order = $db->db_fetch_one($sql, [$order_id]);

        if (!$order) {
            log_sms_activity('error', 'Order not found for SMS', ['order_id' => $order_id]);
            return false;
        }

        $phone = format_phone_number($order['phone']);
        if (!$phone) {
            log_sms_activity('error', 'Invalid phone number for order SMS', [
                'order_id' => $order_id,
                'phone' => $order['phone']
            ]);
            return false;
        }

        // Calculate delivery date (3-5 business days)
        $delivery_date = date('M j, Y', strtotime('+4 days'));

        $template_data = [
            'name' => $order['customer_name'],
            'order_id' => $order_id,
            'amount' => number_format($order['total_amount'], 2),
            'delivery_date' => $delivery_date,
            'tracking_url' => $GLOBALS['sms_urls']['tracking_base'] . $order_id
        ];

        $sms = new SMSService();
        return $sms->sendSMS($phone, SMS_TYPE_ORDER_CONFIRMATION, $template_data, SMS_PRIORITY_HIGH);

    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to send order confirmation SMS', [
            'order_id' => $order_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Send cart abandonment SMS
 * @param int $user_id
 * @return bool
 */
function send_cart_abandonment_sms($user_id) {
    try {
        require_once __DIR__ . '/../controllers/cart_controller.php';
        require_once __DIR__ . '/../controllers/user_controller.php';

        $user = get_user_details_ctr($user_id);
        $cart_items = get_cart_items_ctr($user_id);

        if (!$user || !$cart_items) {
            return false;
        }

        $phone = format_phone_number($user['phone']);
        if (!$phone) {
            return false;
        }

        $cart_total = 0;
        foreach ($cart_items as $item) {
            $cart_total += $item['price'] * $item['qty'];
        }

        $template_data = [
            'name' => $user['name'],
            'items_count' => count($cart_items),
            'cart_total' => number_format($cart_total, 2),
            'checkout_url' => $GLOBALS['sms_urls']['checkout_url']
        ];

        $sms = new SMSService();
        return $sms->sendSMS($phone, SMS_TYPE_CART_ABANDONMENT, $template_data, SMS_PRIORITY_MEDIUM);

    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to send cart abandonment SMS', [
            'user_id' => $user_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Send payment confirmation SMS
 * @param int $order_id
 * @return bool
 */
function send_payment_confirmation_sms($order_id) {
    try {
        require_once __DIR__ . '/../controllers/order_controller.php';
        $order = get_order_details_ctr($order_id);

        if (!$order) return false;

        $phone = format_phone_number($order['phone']);
        if (!$phone) return false;

        $template_data = [
            'name' => $order['customer_name'],
            'order_id' => $order_id,
            'amount' => number_format($order['total_amount'], 2)
        ];

        $sms = new SMSService();
        return $sms->sendSMS($phone, SMS_TYPE_PAYMENT_RECEIVED, $template_data, SMS_PRIORITY_HIGH);

    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to send payment confirmation SMS', [
            'order_id' => $order_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Check and send cart abandonment reminders
 */
function process_cart_abandonment_reminders() {
    if (!CART_ABANDONMENT_ENABLED) return;

    try {
        require_once __DIR__ . '/../controllers/cart_controller.php';
        $abandoned_carts = get_abandoned_carts_ctr(CART_ABANDONMENT_DELAY);

        foreach ($abandoned_carts as $cart) {
            $reminders_sent = get_cart_abandonment_reminders_count($cart['user_id']);

            if ($reminders_sent < CART_ABANDONMENT_REMINDERS) {
                $last_reminder = get_last_cart_reminder_time($cart['user_id']);

                // Check if enough time has passed since last reminder
                if (!$last_reminder || (time() - strtotime($last_reminder)) >= CART_ABANDONMENT_INTERVAL) {
                    if ($reminders_sent == 0) {
                        send_cart_abandonment_sms($cart['user_id']);
                    } else {
                        send_cart_reminder_sms($cart['user_id']);
                    }

                    record_cart_reminder_sent($cart['user_id']);
                }
            }
        }
    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to process cart abandonment reminders', [
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Send cart reminder SMS (follow-up)
 * @param int $user_id
 * @return bool
 */
function send_cart_reminder_sms($user_id) {
    try {
        require_once __DIR__ . '/../controllers/user_controller.php';

        $user = get_user_details_ctr($user_id);
        if (!$user) return false;

        $phone = format_phone_number($user['phone']);
        if (!$phone) return false;

        $template_data = [
            'name' => $user['name'],
            'checkout_url' => $GLOBALS['sms_urls']['checkout_url']
        ];

        $sms = new SMSService();
        return $sms->sendSMS($phone, SMS_TYPE_CART_REMINDER, $template_data, SMS_PRIORITY_LOW);

    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to send cart reminder SMS', [
            'user_id' => $user_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Get cart abandonment reminders count for user
 * @param int $user_id
 * @return int
 */
function get_cart_abandonment_reminders_count($user_id) {
    try {
        require_once __DIR__ . '/../settings/db_class.php';
        $db = new db_connection();

        $query = "SELECT COUNT(*) as count FROM cart_abandonment
                  WHERE user_id = ? AND DATE(created_at) = CURDATE()";
        $result = $db->db_fetch_one($query, [$user_id]);

        return $result ? $result['count'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get last cart reminder time for user
 * @param int $user_id
 * @return string|null
 */
function get_last_cart_reminder_time($user_id) {
    try {
        require_once __DIR__ . '/../settings/db_class.php';
        $db = new db_connection();

        $query = "SELECT created_at FROM cart_abandonment
                  WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $result = $db->db_fetch_one($query, [$user_id]);

        return $result ? $result['created_at'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Record that a cart reminder was sent
 * @param int $user_id
 * @return bool
 */
function record_cart_reminder_sent($user_id) {
    try {
        require_once __DIR__ . '/../settings/db_class.php';
        $db = new db_connection();

        $query = "INSERT INTO cart_abandonment (user_id, created_at) VALUES (?, NOW())";
        return $db->db_query($query, [$user_id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get SMS statistics
 * @param int $days
 * @return array
 */
function get_sms_statistics($days = 30) {
    try {
        require_once __DIR__ . '/../settings/db_class.php';
        $db = new db_connection();

        $query = "SELECT
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    sms_type,
                    DATE(created_at) as send_date
                  FROM sms_logs
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY sms_type, DATE(created_at)
                  ORDER BY send_date DESC";

        return $db->db_fetch_all($query, [$days]) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Clean old SMS logs
 * @param int $days_to_keep
 * @return bool
 */
function cleanup_old_sms_logs($days_to_keep = 90) {
    try {
        require_once __DIR__ . '/../settings/db_class.php';
        $db = new db_connection();

        $query = "DELETE FROM sms_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $db->db_query($query, [$days_to_keep]);
    } catch (Exception $e) {
        return false;
    }
}