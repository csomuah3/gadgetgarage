<?php
/**
 * Cart Abandonment Cron Job
 * Run this script every 30 minutes to check for abandoned carts and send SMS reminders
 *
 * Setup Instructions:
 * 1. Add to crontab: */30 * * * * /usr/bin/php /path/to/cart_abandonment_cron.php
 * 2. Or set up via XAMPP control panel for Windows
 */

// Prevent direct web access
if (isset($_SERVER['HTTP_HOST']) && !isset($_GET['force'])) {
    die('This script should be run via cron job only. Add ?force=1 to test via browser.');
}

// Set time limit for long-running process
set_time_limit(300); // 5 minutes

// Include required files
require_once __DIR__ . '/../actions/cart_abandonment_action.php';

echo "Cart abandonment cron job completed at " . date('Y-m-d H:i:s') . "\n";
?>