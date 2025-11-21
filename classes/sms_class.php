<?php
/**
 * SMS Service Class for Arkesel Integration
 * Handles all SMS functionality including order confirmations, cart abandonment, etc.
 */

require_once(__DIR__ . '/../settings/db_class.php');
require_once(__DIR__ . '/../helpers/sms_helper.php');

class SMSService extends db_connection {

    private $api_key;
    private $api_url;
    private $sender_id;
    private $settings;

    public function __construct() {
        parent::__construct();
        $this->loadSettings();
    }

    /**
     * Load SMS settings from database
     */
    private function loadSettings() {
        $sql = "SELECT setting_name, setting_value FROM sms_settings WHERE is_active = 1";
        $result = $this->db_query($sql);

        $this->settings = [];
        if ($result) {
            while ($row = $this->db_fetch_array($result)) {
                $this->settings[$row['setting_name']] = $row['setting_value'];
            }
        }

        $this->api_key = $this->settings['arkesel_api_key'] ?? '';
        $this->api_url = $this->settings['arkesel_api_url'] ?? 'https://sms.arkesel.com/sms/api';
        $this->sender_id = $this->settings['arkesel_sender_id'] ?? 'GadgetGarage';
    }

    /**
     * Send order confirmation SMS
     */
    public function sendOrderConfirmationSMS($order_id, $customer_id, $phone_number) {
        try {
            if (!$this->isSMSEnabled('order_confirmation_enabled')) {
                return ['success' => false, 'message' => 'Order confirmation SMS disabled'];
            }

            // Get order details
            $order_details = $this->getOrderDetails($order_id);
            if (!$order_details) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            // Calculate delivery date
            $delivery_days = intval($this->settings['delivery_days'] ?? 3);
            $delivery_date = $this->calculateDeliveryDate($delivery_days);

            // Get message template
            $customer_language = $this->getCustomerLanguage($customer_id);
            $template = $this->getMessageTemplate('order_confirmation', $customer_language);

            // Replace variables in template
            $message = $this->replaceTemplateVariables($template, [
                'order_number' => $order_id,
                'delivery_date' => $delivery_date,
                'track_url' => $this->generateTrackingUrl($order_id)
            ]);

            // Send SMS
            $result = $this->sendSMS($phone_number, $message, 'order_confirmation', $order_id, $customer_id);

            // Update order SMS status
            if ($result['success']) {
                $this->updateOrderSMSStatus($order_id, 'sms_order_confirmation_sent', 1);
                $this->updateOrderDeliveryDate($order_id, $delivery_date);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Order Confirmation SMS Error', $e->getMessage(), $order_id);
            return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send shipping update SMS
     */
    public function sendShippingUpdateSMS($order_id, $tracking_number = null) {
        try {
            if (!$this->isSMSEnabled('shipping_update_enabled')) {
                return ['success' => false, 'message' => 'Shipping update SMS disabled'];
            }

            // Get order and customer details
            $order_details = $this->getOrderDetails($order_id);
            if (!$order_details) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $phone_number = $this->getCustomerPhone($order_details['customer_id']);
            if (!$phone_number) {
                return ['success' => false, 'message' => 'Customer phone number not found'];
            }

            // Get delivery date
            $delivery_date = $order_details['delivery_date'] ?? $this->calculateDeliveryDate(2);

            // Get message template
            $customer_language = $this->getCustomerLanguage($order_details['customer_id']);
            $template = $this->getMessageTemplate('shipping_update', $customer_language);

            // Replace variables
            $message = $this->replaceTemplateVariables($template, [
                'order_number' => $order_id,
                'delivery_date' => $delivery_date,
                'tracking_number' => $tracking_number ?: 'TRK' . str_pad($order_id, 6, '0', STR_PAD_LEFT)
            ]);

            // Send SMS
            $result = $this->sendSMS($phone_number, $message, 'shipping_update', $order_id, $order_details['customer_id']);

            // Update order SMS status and tracking
            if ($result['success']) {
                $this->updateOrderSMSStatus($order_id, 'sms_shipping_sent', 1);
                if ($tracking_number) {
                    $this->updateOrderTrackingNumber($order_id, $tracking_number);
                }
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Shipping Update SMS Error', $e->getMessage(), $order_id);
            return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send delivery confirmation SMS
     */
    public function sendDeliveryConfirmationSMS($order_id) {
        try {
            if (!$this->isSMSEnabled('delivery_confirmation_enabled')) {
                return ['success' => false, 'message' => 'Delivery confirmation SMS disabled'];
            }

            // Get order and customer details
            $order_details = $this->getOrderDetails($order_id);
            if (!$order_details) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $phone_number = $this->getCustomerPhone($order_details['customer_id']);
            if (!$phone_number) {
                return ['success' => false, 'message' => 'Customer phone number not found'];
            }

            // Get message template
            $customer_language = $this->getCustomerLanguage($order_details['customer_id']);
            $template = $this->getMessageTemplate('delivery_confirmation', $customer_language);

            // Replace variables
            $message = $this->replaceTemplateVariables($template, [
                'order_number' => $order_id,
                'review_url' => $this->generateReviewUrl($order_id)
            ]);

            // Send SMS
            $result = $this->sendSMS($phone_number, $message, 'delivery_confirmation', $order_id, $order_details['customer_id']);

            // Update order SMS status
            if ($result['success']) {
                $this->updateOrderSMSStatus($order_id, 'sms_delivery_sent', 1);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Delivery Confirmation SMS Error', $e->getMessage(), $order_id);
            return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send cart abandonment SMS
     */
    public function sendCartAbandonmentSMS($abandonment_id, $reminder_number = 1) {
        try {
            // Get cart abandonment details
            $sql = "SELECT * FROM cart_abandonment WHERE id = ?";
            $stmt = $this->db_query($sql, [$abandonment_id]);
            $abandonment = $this->db_fetch_array($stmt);

            if (!$abandonment) {
                return ['success' => false, 'message' => 'Cart abandonment record not found'];
            }

            if ($abandonment['cart_recovered']) {
                return ['success' => false, 'message' => 'Cart already recovered'];
            }

            // Check if reminder already sent
            $reminder_field = 'reminder_sent_' . $reminder_number;
            if ($abandonment[$reminder_field]) {
                return ['success' => false, 'message' => 'Reminder already sent'];
            }

            // Get customer language if customer_id exists
            $customer_language = 'en';
            if ($abandonment['customer_id']) {
                $customer_language = $this->getCustomerLanguage($abandonment['customer_id']);
            }

            // Get message template
            $template_name = 'cart_abandonment_' . $reminder_number;
            $template = $this->getMessageTemplate($template_name, $customer_language);

            if (!$template) {
                $template = $this->getMessageTemplate('cart_abandonment_1', $customer_language);
            }

            // Replace variables
            $message = $this->replaceTemplateVariables($template, [
                'cart_total' => 'GH₵ ' . number_format($abandonment['cart_total'], 2),
                'cart_url' => $this->generateCartRecoveryUrl($abandonment['session_id'])
            ]);

            // Send SMS
            $result = $this->sendSMS(
                $abandonment['phone_number'],
                $message,
                'cart_abandonment',
                null,
                $abandonment['customer_id']
            );

            // Update reminder status
            if ($result['success']) {
                $update_sql = "UPDATE cart_abandonment SET {$reminder_field} = 1 WHERE id = ?";
                $this->db_query($update_sql, [$abandonment_id]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Cart Abandonment SMS Error', $e->getMessage(), $abandonment_id);
            return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Core SMS sending function using Arkesel API
     */
    private function sendSMS($phone_number, $message, $message_type, $order_id = null, $customer_id = null) {
        try {
            // Validate phone number
            if (!validatePhoneNumber($phone_number)) {
                throw new Exception('Invalid phone number format');
            }

            // Check rate limiting
            if (!$this->checkRateLimit($phone_number)) {
                throw new Exception('Rate limit exceeded for this phone number');
            }

            // Format phone number for Ghana
            $formatted_phone = formatPhoneNumberForGhana($phone_number);

            // Prepare API request
            $url = $this->api_url . '?' . http_build_query([
                'action' => 'send-sms',
                'api_key' => $this->api_key,
                'to' => $formatted_phone,
                'from' => $this->sender_id,
                'sms' => $message
            ]);

            // Log SMS attempt
            $log_id = $this->logSMSAttempt($order_id, $customer_id, $formatted_phone, $message_type, $message);

            // Send HTTP request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'GadgetGarage SMS Service 1.0');

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('cURL Error: ' . $error);
            }

            // Parse response
            $response_data = json_decode($response, true);

            if ($http_code === 200) {
                // Update log as successful
                $this->updateSMSLog($log_id, 'sent', $response_data);
                $this->updateRateLimit($phone_number);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'log_id' => $log_id,
                    'response' => $response_data
                ];
            } else {
                throw new Exception('HTTP Error ' . $http_code . ': ' . $response);
            }

        } catch (Exception $e) {
            // Update log as failed
            if (isset($log_id)) {
                $this->updateSMSLog($log_id, 'failed', null, $e->getMessage());
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'log_id' => $log_id ?? null
            ];
        }
    }

    /**
     * Queue SMS for later sending
     */
    public function queueSMS($phone_number, $message, $message_type, $scheduled_at, $order_id = null, $customer_id = null, $priority = 'normal') {
        try {
            $sql = "INSERT INTO sms_queue (phone_number, message_content, message_type, scheduled_at, priority, order_id, customer_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $result = $this->db_query($sql, [
                $phone_number,
                $message,
                $message_type,
                $scheduled_at,
                $priority,
                $order_id,
                $customer_id
            ]);

            return [
                'success' => (bool)$result,
                'message' => $result ? 'SMS queued successfully' : 'Failed to queue SMS'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Queue error: ' . $e->getMessage()];
        }
    }

    /**
     * Process SMS queue (to be called by cron job)
     */
    public function processSMSQueue($limit = 10) {
        try {
            $sql = "SELECT * FROM sms_queue
                    WHERE status = 'pending'
                    AND scheduled_at <= NOW()
                    ORDER BY priority DESC, scheduled_at ASC
                    LIMIT ?";

            $result = $this->db_query($sql, [$limit]);
            $processed = 0;

            if ($result) {
                while ($row = $this->db_fetch_array($result)) {
                    // Update status to processing
                    $this->updateQueueStatus($row['id'], 'processing');

                    // Send SMS
                    $sms_result = $this->sendSMS(
                        $row['phone_number'],
                        $row['message_content'],
                        $row['message_type'],
                        $row['order_id'],
                        $row['customer_id']
                    );

                    // Update queue status
                    if ($sms_result['success']) {
                        $this->updateQueueStatus($row['id'], 'sent');
                    } else {
                        $retry_count = $row['retry_count'] + 1;
                        if ($retry_count >= $row['max_retries']) {
                            $this->updateQueueStatus($row['id'], 'failed');
                        } else {
                            $this->retryQueueItem($row['id'], $retry_count);
                        }
                    }

                    $processed++;
                }
            }

            return ['success' => true, 'processed' => $processed];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Queue processing error: ' . $e->getMessage()];
        }
    }

    /**
     * Get SMS statistics
     */
    public function getSMSStatistics($date_from = null, $date_to = null) {
        try {
            $where_clause = "1 = 1";
            $params = [];

            if ($date_from) {
                $where_clause .= " AND DATE(created_at) >= ?";
                $params[] = $date_from;
            }

            if ($date_to) {
                $where_clause .= " AND DATE(created_at) <= ?";
                $params[] = $date_to;
            }

            $sql = "SELECT
                        message_type,
                        COUNT(*) as total_sent,
                        COUNT(CASE WHEN status = 'sent' THEN 1 END) as successful,
                        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                        ROUND(COUNT(CASE WHEN status = 'sent' THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate
                    FROM sms_logs
                    WHERE {$where_clause}
                    GROUP BY message_type";

            $result = $this->db_query($sql, $params);
            $statistics = [];

            if ($result) {
                while ($row = $this->db_fetch_array($result)) {
                    $statistics[] = $row;
                }
            }

            return ['success' => true, 'data' => $statistics];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Statistics error: ' . $e->getMessage()];
        }
    }

    // Helper methods
    private function isSMSEnabled($setting_name) {
        return ($this->settings['sms_enabled'] ?? 0) == 1 && ($this->settings[$setting_name] ?? 0) == 1;
    }

    private function getOrderDetails($order_id) {
        $sql = "SELECT * FROM orders WHERE order_id = ?";
        $result = $this->db_query($sql, [$order_id]);
        return $result ? $this->db_fetch_array($result) : null;
    }

    private function getCustomerPhone($customer_id) {
        $sql = "SELECT customer_contact FROM customer WHERE customer_id = ?";
        $result = $this->db_query($sql, [$customer_id]);
        $row = $this->db_fetch_array($result);
        return $row ? $row['customer_contact'] : null;
    }

    private function getCustomerLanguage($customer_id) {
        // Default to English, can be enhanced to get from customer preferences
        return 'en';
    }

    private function getMessageTemplate($template_name, $language = 'en') {
        $sql = "SELECT template_content FROM sms_templates
                WHERE template_name = ? AND language_code = ? AND is_active = 1
                ORDER BY id DESC LIMIT 1";

        $result = $this->db_query($sql, [$template_name . '_' . $language]);
        $row = $this->db_fetch_array($result);

        if (!$row) {
            // Fallback to English
            $result = $this->db_query($sql, [$template_name . '_en']);
            $row = $this->db_fetch_array($result);
        }

        return $row ? $row['template_content'] : null;
    }

    private function replaceTemplateVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    private function calculateDeliveryDate($days = 3) {
        // Calculate business days (excluding weekends)
        $delivery_date = new DateTime();
        $added_days = 0;

        while ($added_days < $days) {
            $delivery_date->add(new DateInterval('P1D'));
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($delivery_date->format('w') != 0 && $delivery_date->format('w') != 6) {
                $added_days++;
            }
        }

        return $delivery_date->format('M d, Y');
    }

    private function generateTrackingUrl($order_id) {
        return "https://gadgetgarage.com/track?order=" . $order_id;
    }

    private function generateReviewUrl($order_id) {
        return "https://gadgetgarage.com/review?order=" . $order_id;
    }

    private function generateCartRecoveryUrl($session_id) {
        return "https://gadgetgarage.com/cart/recover?session=" . $session_id;
    }

    private function checkRateLimit($phone_number) {
        $limit = intval($this->settings['rate_limit_per_hour'] ?? 5);
        $window_start = date('Y-m-d H:00:00');

        $sql = "SELECT count FROM sms_rate_limits
                WHERE identifier = ? AND identifier_type = 'phone'
                AND window_start = ?";

        $result = $this->db_query($sql, [$phone_number, $window_start]);
        $row = $this->db_fetch_array($result);

        $current_count = $row ? intval($row['count']) : 0;
        return $current_count < $limit;
    }

    private function updateRateLimit($phone_number) {
        $window_start = date('Y-m-d H:00:00');
        $window_end = date('Y-m-d H:59:59');

        $sql = "INSERT INTO sms_rate_limits (identifier, identifier_type, count, window_start, window_end)
                VALUES (?, 'phone', 1, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + 1";

        $this->db_query($sql, [$phone_number, $window_start, $window_end]);
    }

    private function logSMSAttempt($order_id, $customer_id, $phone_number, $message_type, $message_content) {
        $sql = "INSERT INTO sms_logs (order_id, customer_id, phone_number, message_type, message_content, status)
                VALUES (?, ?, ?, ?, ?, 'pending')";

        $this->db_query($sql, [$order_id, $customer_id, $phone_number, $message_type, $message_content]);
        return $this->db_insert_id();
    }

    private function updateSMSLog($log_id, $status, $response_data = null, $error_message = null) {
        $sql = "UPDATE sms_logs SET status = ?, sent_at = NOW(), response_data = ?, error_message = ? WHERE id = ?";
        $this->db_query($sql, [$status, json_encode($response_data), $error_message, $log_id]);
    }

    private function updateOrderSMSStatus($order_id, $field, $value) {
        $sql = "UPDATE orders SET {$field} = ? WHERE order_id = ?";
        $this->db_query($sql, [$value, $order_id]);
    }

    private function updateOrderDeliveryDate($order_id, $delivery_date) {
        $sql = "UPDATE orders SET delivery_date = ? WHERE order_id = ?";
        $this->db_query($sql, [$delivery_date, $order_id]);
    }

    private function updateOrderTrackingNumber($order_id, $tracking_number) {
        $sql = "UPDATE orders SET tracking_number = ? WHERE order_id = ?";
        $this->db_query($sql, [$tracking_number, $order_id]);
    }

    private function updateQueueStatus($queue_id, $status) {
        $sql = "UPDATE sms_queue SET status = ?, processed_at = NOW() WHERE id = ?";
        $this->db_query($sql, [$status, $queue_id]);
    }

    private function retryQueueItem($queue_id, $retry_count) {
        // Reschedule for 10 minutes later
        $next_attempt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $sql = "UPDATE sms_queue SET retry_count = ?, scheduled_at = ?, status = 'pending' WHERE id = ?";
        $this->db_query($sql, [$retry_count, $next_attempt, $queue_id]);
    }

    private function logError($context, $message, $reference_id = null) {
        error_log("[SMS Service] {$context}: {$message} (Ref: {$reference_id})");
    }
}
?>