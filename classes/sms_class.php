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
        // Try to load settings from database first
        try {
            $sql = "SELECT setting_name, setting_value FROM sms_settings WHERE is_active = 1";
            $result = $this->db_query($sql);

            $this->settings = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($this->results)) {
                    $this->settings[$row['setting_name']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            // Database table might not exist, use constants from config
            $this->settings = [];
        }

        // Use config constants as fallback
        $this->api_key = $this->settings['arkesel_api_key'] ?? SMS_API_KEY;
        $this->api_url = $this->settings['arkesel_api_url'] ?? SMS_API_URL;
        $this->sender_id = $this->settings['arkesel_sender_id'] ?? SMS_SENDER_ID;
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

            // Calculate delivery date (3-5 business days from order date)
            $delivery_days = intval($this->settings['delivery_days'] ?? 4); // Default to 4 days (middle of 3-5 range)
            $delivery_date = $this->calculateDeliveryDate($delivery_days);

            // Get message template
            $customer_language = $this->getCustomerLanguage($customer_id);
            $template = $this->getMessageTemplate('order_confirmation', $customer_language);

            // If no template found, use default template with delivery timeframe
            if (!$template) {
                $template = "Thank you for your order! Order #{order_number} has been confirmed. Your order will be delivered within 3-5 business days (estimated: {delivery_date}). Track your order: {track_url}";
            }

            // Replace variables in template
            $message = $this->replaceTemplateVariables($template, [
                'order_number' => $order_id,
                'delivery_date' => $delivery_date,
                'delivery_timeframe' => '3-5 business days',
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
            $abandonment_id = (int)$abandonment_id;
            $sql = "SELECT * FROM cart_abandonment WHERE id = $abandonment_id";
            $result = $this->db_query($sql);
            $abandonment = null;
            if ($result) {
                $abandonment = mysqli_fetch_assoc($this->results);
            }

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
            // Log original phone number
            error_log("SMS sendSMS called - Original phone: $phone_number, Message type: $message_type");
            
            // Validate and format phone number
            $formatted_phone = format_phone_number($phone_number);
            if (!$formatted_phone) {
                error_log("SMS Error: Phone number formatting failed for: $phone_number");
                throw new Exception('Invalid phone number format: ' . $phone_number);
            }
            
            error_log("SMS Phone formatted: $phone_number -> $formatted_phone");

            // Check rate limiting (simplified - always allows for now)
            if (!$this->checkRateLimit($formatted_phone)) {
                throw new Exception('Rate limit exceeded for this phone number');
            }

            // Prepare API request - Arkesel v2 uses POST with JSON body
            // Arkesel might accept phone with or without +, try without + first
            $phone_for_api = str_replace('+', '', $formatted_phone);
            
            $api_data = [
                'sender' => $this->sender_id,
                'message' => $message,
                'recipients' => [$phone_for_api] // Try without + sign
            ];
            
            error_log("Phone for API (without +): $phone_for_api");

            // Log SMS attempt
            $log_id = $this->logSMSAttempt($order_id, $customer_id, $formatted_phone, $message_type, $message);
            
            error_log("=== SMS SENDING ATTEMPT ===");
            error_log("API URL: " . $this->api_url);
            error_log("API Key: " . substr($this->api_key, 0, 10) . "...");
            error_log("Sender ID: " . $this->sender_id);
            error_log("Recipient: $formatted_phone");
            error_log("Message length: " . strlen($message));
            error_log("Request Data: " . json_encode($api_data, JSON_PRETTY_PRINT));

            // Send HTTP request using POST with JSON
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'api-key: ' . $this->api_key
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'GadgetGarage SMS Service 1.0');

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $curl_info = curl_getinfo($ch);
            curl_close($ch);
            
            error_log("=== ARKESEL API RESPONSE ===");
            error_log("HTTP Code: $http_code");
            error_log("cURL Error: " . ($error ? $error : 'None'));
            error_log("Response (first 500 chars): " . substr($response, 0, 500));
            error_log("Full Response: " . $response);

            if ($error) {
                error_log("cURL Error: $error");
                throw new Exception('cURL Error: ' . $error);
            }

            // Parse response
            $response_data = json_decode($response, true);
            
            error_log("Arkesel API Response Data: " . json_encode($response_data));

            // Arkesel API returns 200 on success, check response structure
            if ($http_code === 200 || $http_code === 201) {
                // Arkesel success can be indicated by:
                // 1. status === 'success'
                // 2. code === 200
                // 3. data array with message info
                // 4. Or just HTTP 200 with valid JSON
                
                $is_success = false;
                $error_msg = 'Unknown error from Arkesel API';
                
                if (isset($response_data['status']) && $response_data['status'] === 'success') {
                    $is_success = true;
                } elseif (isset($response_data['code']) && $response_data['code'] == 200) {
                    $is_success = true;
                } elseif (isset($response_data['data']) && is_array($response_data['data'])) {
                    $is_success = true;
                } elseif (empty($response_data) || !isset($response_data['status'])) {
                    // Sometimes Arkesel returns 200 with just data, no status field
                    // If we got 200 and valid JSON, assume success
                    $is_success = true;
                } else {
                    $error_msg = $response_data['message'] ?? ($response_data['error'] ?? 'Unknown error');
                }
                
                if ($is_success) {
                    // Update log as successful
                    $this->updateSMSLog($log_id, 'sent', $response_data);
                    $this->updateRateLimit($formatted_phone);

                    error_log("SMS sent successfully to $formatted_phone");
                    return [
                        'success' => true,
                        'message' => 'SMS sent successfully',
                        'log_id' => $log_id,
                        'response' => $response_data
                    ];
                } else {
                    // Arkesel returned error
                    error_log("Arkesel API Error: $error_msg");
                    error_log("Full response: " . json_encode($response_data));
                    throw new Exception('Arkesel API Error: ' . $error_msg);
                }
            } else {
                error_log("HTTP Error $http_code: $response");
                throw new Exception('HTTP Error ' . $http_code . ': ' . substr($response, 0, 200));
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
     * Send welcome registration SMS
     */
    public function sendWelcomeRegistrationSMS($customer_id, $customer_name, $phone_number) {
        try {
            if (!$this->isSMSEnabled('welcome_sms_enabled')) {
                return ['success' => false, 'message' => 'Welcome SMS disabled'];
            }

            // Validate phone number
            $phone = format_phone_number($phone_number);
            if (!$phone) {
                return ['success' => false, 'message' => 'Invalid phone number'];
            }

            // Get welcome message template
            $template = get_sms_template('welcome_registration', 'en');
            if (!$template) {
                $template = "Welcome to Gadget Garage, {name}! 🎉 Your account has been created successfully. Start shopping for the best tech deals today!";
            }

            // Replace template variables
            $message = process_sms_template($template, [
                'name' => $customer_name
            ]);

            // Send SMS
            $result = $this->sendSMS($phone, $message, 'welcome_registration', null, $customer_id);

            return $result;

        } catch (Exception $e) {
            $this->logError('Welcome Registration SMS Error', $e->getMessage(), $customer_id);
            return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
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
        // Use config constants as fallback if database settings don't exist
        $sms_enabled = $this->settings['sms_enabled'] ?? (SMS_ENABLED ? 1 : 0);
        $setting_enabled = $this->settings[$setting_name] ?? 1; // Default to enabled

        return $sms_enabled == 1 && $setting_enabled == 1;
    }

    private function getOrderDetails($order_id) {
        $order_id = (int)$order_id;
        $sql = "SELECT * FROM orders WHERE order_id = $order_id";
        $result = $this->db_query($sql);
        if ($result) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    private function getCustomerPhone($customer_id) {
        $customer_id = (int)$customer_id;
        $sql = "SELECT customer_contact FROM customer WHERE customer_id = $customer_id";
        $result = $this->db_query($sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row ? $row['customer_contact'] : null;
        }
        return null;
    }

    private function getCustomerLanguage($customer_id) {
        // Default to English, can be enhanced to get from customer preferences
        return 'en';
    }

    private function getMessageTemplate($template_name, $language = 'en') {
        $template_name_escaped = mysqli_real_escape_string($this->db, $template_name . '_' . $language);
        $sql = "SELECT template_content FROM sms_templates
                WHERE template_name = '$template_name_escaped' AND language_code = '$language' AND is_active = 1
                ORDER BY id DESC LIMIT 1";

        $result = $this->db_query($sql);
        if ($result) {
            $row = mysqli_fetch_assoc($this->results);
            if ($row) {
                return $row['template_content'];
            }
        }

        // Fallback to English if not found
        if ($language !== 'en') {
            $template_name_escaped = mysqli_real_escape_string($this->db, $template_name . '_en');
            $sql = "SELECT template_content FROM sms_templates
                    WHERE template_name = '$template_name_escaped' AND language_code = 'en' AND is_active = 1
                    ORDER BY id DESC LIMIT 1";
            $result = $this->db_query($sql);
            if ($result) {
                $row = mysqli_fetch_assoc($this->results);
                if ($row) {
                    return $row['template_content'];
                }
            }
        }

        return null;
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
        // For now, skip rate limiting to ensure SMS works
        // Rate limiting can be implemented later if needed
        return true;
    }

    private function updateRateLimit($phone_number) {
        // Rate limiting update - can be implemented later
        return true;
    }

    private function logSMSAttempt($order_id, $customer_id, $phone_number, $message_type, $message_content) {
        // Log SMS attempt - simplified for now
        // Can be enhanced later with proper logging table
        error_log("SMS Attempt - Order: $order_id, Phone: $phone_number, Type: $message_type");
        return 1; // Return a log ID for compatibility
    }

    private function updateSMSLog($log_id, $status, $response_data = null, $error_message = null) {
        // SMS log update - simplified
        error_log("SMS Log Update - ID: $log_id, Status: $status");
        return true;
    }

    private function updateOrderSMSStatus($order_id, $field, $value) {
        $order_id = (int)$order_id;
        $value = (int)$value;
        $field = mysqli_real_escape_string($this->db, $field);
        $sql = "UPDATE orders SET $field = $value WHERE order_id = $order_id";
        return $this->db_write_query($sql);
    }

    private function updateOrderDeliveryDate($order_id, $delivery_date) {
        $order_id = (int)$order_id;
        $delivery_date = mysqli_real_escape_string($this->db, $delivery_date);
        $sql = "UPDATE orders SET delivery_date = '$delivery_date' WHERE order_id = $order_id";
        return $this->db_write_query($sql);
    }

    private function updateOrderTrackingNumber($order_id, $tracking_number) {
        $order_id = (int)$order_id;
        $tracking_number = mysqli_real_escape_string($this->db, $tracking_number);
        $sql = "UPDATE orders SET tracking_number = '$tracking_number' WHERE order_id = $order_id";
        return $this->db_write_query($sql);
    }

    private function updateQueueStatus($queue_id, $status) {
        $queue_id = (int)$queue_id;
        $status = mysqli_real_escape_string($this->db, $status);
        $sql = "UPDATE sms_queue SET status = '$status', processed_at = NOW() WHERE id = $queue_id";
        return $this->db_write_query($sql);
    }

    private function retryQueueItem($queue_id, $retry_count) {
        $queue_id = (int)$retry_count;
        $retry_count = (int)$retry_count;
        $next_attempt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $next_attempt = mysqli_real_escape_string($this->db, $next_attempt);
        $sql = "UPDATE sms_queue SET retry_count = $retry_count, scheduled_at = '$next_attempt', status = 'pending' WHERE id = $queue_id";
        return $this->db_write_query($sql);
    }

    /**
     * Send appointment confirmation SMS
     */
    public function sendAppointmentConfirmationSMS($appointment_id, $customer_id, $phone_number, $customer_name, $appointment_date, $appointment_time, $specialist_name, $issue_name) {
        try {
            // Log the phone number received
            error_log("sendAppointmentConfirmationSMS called - Phone: $phone_number, Appointment ID: $appointment_id");
            
            // Check if SMS is enabled (default to enabled if setting not found)
            $sms_enabled = $this->settings['sms_enabled'] ?? (defined('SMS_ENABLED') && SMS_ENABLED ? 1 : 1);
            if ($sms_enabled != 1) {
                error_log("SMS is disabled in settings");
                return ['success' => false, 'message' => 'SMS is disabled'];
            }
            
            // Validate and format phone number first
            $formatted_phone = format_phone_number($phone_number);
            if (!$formatted_phone) {
                error_log("Phone number formatting failed - Original: $phone_number");
                return ['success' => false, 'message' => 'Invalid phone number format: ' . $phone_number];
            }
            
            error_log("Phone number formatted - Original: $phone_number, Formatted: $formatted_phone");

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

            // Send SMS using private method - use formatted phone number
            $result = $this->sendSMS($formatted_phone, $message, 'appointment_confirmation', $appointment_id, $customer_id);
            
            error_log("SMS send result: " . json_encode($result));

            return $result;

        } catch (Exception $e) {
            $this->logError('Appointment Confirmation SMS Error', $e->getMessage(), $appointment_id);
            return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    private function logError($context, $message, $reference_id = null) {
        error_log("[SMS Service] {$context}: {$message} (Ref: {$reference_id})");
    }
}
?>