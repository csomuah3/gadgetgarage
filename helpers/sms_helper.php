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
        $order_id = (int)$order_id;
        $sql = "SELECT o.*, c.customer_name as customer_name, c.customer_contact as phone, py.amt as total_amount
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN payment py ON o.order_id = py.order_id
                WHERE o.order_id = $order_id";
        $order = $db->db_fetch_one($sql);

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

        $sms = new SMSService();
        $result = $sms->sendOrderConfirmationSMS($order_id, $order['customer_id'], $phone);

        log_sms_activity('info', 'Order confirmation SMS attempt', [
            'order_id' => $order_id,
            'customer_id' => $order['customer_id'],
            'phone' => $phone,
            'result' => $result
        ]);

        return $result['success'] ?? false;

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
 * Send welcome SMS to new registered user
 * @param int $customer_id
 * @param string $customer_name
 * @param string $phone_number
 * @return bool
 */
function send_welcome_registration_sms($customer_id, $customer_name, $phone_number) {
    try {
        global $sms_urls;

        $phone = format_phone_number($phone_number);
        if (!$phone) {
            log_sms_activity('error', 'Invalid phone number for welcome SMS', [
                'customer_id' => $customer_id,
                'phone' => $phone_number
            ]);
            return false;
        }

        // Get welcome message template
        $template = get_sms_template(SMS_TYPE_WELCOME_REGISTRATION, 'en');
        if (!$template) {
            log_sms_activity('error', 'Welcome SMS template not found');
            return false;
        }

        // Replace template variables
        $message = process_sms_template($template, [
            'name' => $customer_name,
            'website_url' => $sms_urls['website_url']
        ]);

        $sms = new SMSService();
        $result = $sms->sendWelcomeRegistrationSMS($customer_id, $customer_name, $phone_number);

        log_sms_activity('info', 'Welcome SMS attempt', [
            'customer_id' => $customer_id,
            'phone' => $phone,
            'result' => $result
        ]);

        return $result['success'] ?? false;

    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to send welcome SMS', [
            'customer_id' => $customer_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Send appointment confirmation SMS
 * @param int $appointment_id
 * @param string $customer_name
 * @param string $phone_number
 * @param string $appointment_date
 * @param string $appointment_time
 * @param string $specialist_name
 * @param string $issue_name
 * @return bool
 */
function send_appointment_confirmation_sms($appointment_id, $customer_name, $phone_number, $appointment_date, $appointment_time, $specialist_name, $issue_name) {
    try {
        global $sms_urls;

        log_sms_activity('info', 'Starting appointment SMS', [
            'appointment_id' => $appointment_id,
            'original_phone' => $phone_number,
            'customer_name' => $customer_name
        ]);

        $phone = format_phone_number($phone_number);
        if (!$phone) {
            log_sms_activity('error', 'Invalid phone number for appointment SMS', [
                'appointment_id' => $appointment_id,
                'phone' => $phone_number,
                'formatted_phone' => $phone
            ]);
            error_log("Appointment SMS: Phone number formatting failed. Original: $phone_number");
            return false;
        }

        log_sms_activity('info', 'Phone number formatted successfully', [
            'appointment_id' => $appointment_id,
            'original_phone' => $phone_number,
            'formatted_phone' => $phone
        ]);

        // Format date and time for display
        $date_formatted = date('l, F j, Y', strtotime($appointment_date));
        $time_formatted = date('g:i A', strtotime($appointment_time));

        // Create message
        $message = "Hello {$customer_name}, your repair appointment has been confirmed!\n\n";
        $message .= "Issue: {$issue_name}\n";
        $message .= "Specialist: {$specialist_name}\n";
        $message .= "Date: {$date_formatted}\n";
        $message .= "Time: {$time_formatted}\n\n";
        $message .= "Appointment ID: #{$appointment_id}\n\n";
        $message .= "We look forward to helping you with your device repair. If you need to reschedule, please contact us.\n\n";
        $message .= "Thank you, GadgetGarage";

        // Get customer_id if available (for logging)
        $customer_id = null;
        try {
            require_once __DIR__ . '/../settings/db_class.php';
            $db = new db_connection();
            if ($db->db_connect()) {
                $appointment_query = "SELECT customer_id FROM repair_appointments WHERE appointment_id = $appointment_id";
                $appointment_data = $db->db_fetch_one($appointment_query);
                if ($appointment_data) {
                    $customer_id = $appointment_data['customer_id'];
                }
            }
        } catch (Exception $e) {
            // Ignore if can't get customer_id
        }
        
        $sms = new SMSService();
        $result = $sms->sendAppointmentConfirmationSMS(
            $appointment_id,
            $customer_id,
            $phone,
            $customer_name,
            $appointment_date,
            $appointment_time,
            $specialist_name,
            $issue_name
        );

        log_sms_activity('info', 'Appointment confirmation SMS attempt', [
            'appointment_id' => $appointment_id,
            'phone' => $phone,
            'result' => $result
        ]);

        return $result['success'] ?? false;

    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to send appointment confirmation SMS', [
            'appointment_id' => $appointment_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Send order status update SMS notification
 * @param int $order_id
 * @param string $status
 * @return bool
 */
function send_order_status_update_sms($order_id, $status) {
    try {
        global $sms_urls;

        // Get order and customer details
        require_once __DIR__ . '/../controllers/order_controller.php';
        $tracking_data = get_order_tracking_details($order_id);

        if (!$tracking_data || !isset($tracking_data['order'])) {
            log_sms_activity('error', 'Order not found for SMS notification', [
                'order_id' => $order_id,
                'status' => $status
            ]);
            return false;
        }

        $order = $tracking_data['order'];

        // Get customer details
        require_once __DIR__ . '/../settings/db_class.php';
        $db = new db_connection();
        $customer_query = "SELECT customer_name, customer_contact FROM customer WHERE customer_id = ?";
        $customer = $db->db_fetch_one($customer_query, [$order['customer_id']]);

        if (!$customer || !$customer['customer_contact']) {
            log_sms_activity('error', 'Customer contact not found for SMS notification', [
                'order_id' => $order_id,
                'customer_id' => $order['customer_id']
            ]);
            return false;
        }

        $phone = format_phone_number($customer['customer_contact']);
        if (!$phone) {
            log_sms_activity('error', 'Invalid phone number for order status SMS', [
                'order_id' => $order_id,
                'phone' => $customer['customer_contact']
            ]);
            return false;
        }

        // Get appropriate SMS template based on status
        $template_type = get_status_sms_template_type($status);
        $template = get_sms_template($template_type, 'en');

        if (!$template) {
            log_sms_activity('error', 'SMS template not found for status', [
                'order_id' => $order_id,
                'status' => $status,
                'template_type' => $template_type
            ]);
            return false;
        }

        // Create tracking URL
        $tracking_url = $sms_urls['tracking_base'] . urlencode($order['tracking_number'] ?? $order_id);

        // Prepare template variables
        $variables = [
            'name' => $customer['customer_name'],
            'order_id' => $order_id,
            'tracking_number' => $order['tracking_number'] ?? 'N/A',
            'amount' => number_format($order['total_amount'], 2),
            'delivery_date' => get_estimated_delivery_date($status),
            'tracking_url' => $tracking_url,
            'website_url' => $sms_urls['website_url']
        ];

        // Process template
        $message = process_sms_template($template, $variables);

        $sms = new SMSService();
        $result = $sms->send_sms($phone, $message, $template_type);

        if ($result) {
            log_sms_activity('info', 'Order status SMS sent successfully', [
                'order_id' => $order_id,
                'status' => $status,
                'phone' => $phone
            ]);
        }

        return $result;

    } catch (Exception $e) {
        log_sms_activity('error', 'Failed to send order status SMS', [
            'order_id' => $order_id,
            'status' => $status,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Get SMS template type based on order status
 * @param string $status
 * @return string
 */
function get_status_sms_template_type($status) {
    $template_mapping = [
        'pending' => SMS_TYPE_ORDER_CONFIRMATION,
        'processing' => SMS_TYPE_ORDER_CONFIRMATION,
        'shipped' => SMS_TYPE_ORDER_SHIPPED,
        'out_for_delivery' => SMS_TYPE_ORDER_SHIPPED,
        'delivered' => SMS_TYPE_ORDER_DELIVERED,
        'cancelled' => SMS_TYPE_ORDER_CONFIRMATION
    ];

    return $template_mapping[$status] ?? SMS_TYPE_ORDER_CONFIRMATION;
}

/**
 * Get estimated delivery date based on status
 * @param string $status
 * @return string
 */
function get_estimated_delivery_date($status) {
    $delivery_estimates = [
        'pending' => '+3 days',
        'processing' => '+2 days',
        'shipped' => '+1 day',
        'out_for_delivery' => 'today',
        'delivered' => 'completed',
        'cancelled' => 'cancelled'
    ];

    $estimate = $delivery_estimates[$status] ?? '+3 days';

    if ($estimate === 'today') {
        return date('M j, Y') . ' (Today)';
    } elseif ($estimate === 'completed' || $estimate === 'cancelled') {
        return ucfirst($estimate);
    } else {
        return date('M j, Y', strtotime($estimate));
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