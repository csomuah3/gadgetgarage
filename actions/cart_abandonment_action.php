<?php
/**
 * Cart Abandonment Action Handler
 * Processes cart abandonment SMS notifications
 */

require_once __DIR__ . '/../helpers/sms_helper.php';
require_once __DIR__ . '/../settings/core.php';

// This script should be run via cron job every 30 minutes
// Example cron: */30 * * * * /usr/bin/php /path/to/cart_abandonment_action.php

try {
    // Log that the script is running
    log_sms_activity('info', 'Cart abandonment processor started');

    if (!CART_ABANDONMENT_ENABLED) {
        log_sms_activity('info', 'Cart abandonment is disabled, exiting');
        exit;
    }

    // Process cart abandonment reminders
    process_cart_abandonment_reminders();

    log_sms_activity('info', 'Cart abandonment processor completed successfully');

} catch (Exception $e) {
    log_sms_activity('error', 'Cart abandonment processor failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // If running via web (for testing), show error
    if (isset($_SERVER['HTTP_HOST'])) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}