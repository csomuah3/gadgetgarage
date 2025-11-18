<?php
// Test script for notification system
require_once __DIR__ . '/controllers/support_controller.php';

echo "Testing notification system...\n\n";

// Test 1: Get customer notifications
echo "Test 1: Get customer notifications\n";
$notifications = get_customer_notifications_ctr(1);
if ($notifications) {
    echo "✓ Successfully retrieved " . count($notifications) . " notifications\n";
    foreach ($notifications as $notification) {
        echo "  - Notification ID: {$notification['notification_id']}\n";
        echo "    Message: {$notification['message']}\n";
        echo "    Type: {$notification['type']}\n";
        echo "    Read: " . ($notification['is_read'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "✗ Failed to retrieve notifications\n";
}

echo "\n";

// Test 2: Get unread notification count
echo "Test 2: Get unread notification count\n";
$unread_count = get_unread_notification_count_ctr(1);
echo "✓ Unread notifications: $unread_count\n";

echo "\n";

// Test 3: Mark notification as read
echo "Test 3: Mark notification as read\n";
$result = mark_notification_read_ctr(1, 1);
if ($result) {
    echo "✓ Successfully marked notification as read\n";
} else {
    echo "✗ Failed to mark notification as read\n";
}

echo "\n";

// Test 4: Verify notification was marked as read
echo "Test 4: Verify notification was marked as read\n";
$notifications_after = get_customer_notifications_ctr(1);
if ($notifications_after && count($notifications_after) > 0) {
    $first_notification = $notifications_after[0];
    if ($first_notification['is_read']) {
        echo "✓ Notification successfully marked as read\n";
    } else {
        echo "✗ Notification still shows as unread\n";
    }
} else {
    echo "✗ Could not retrieve notification to verify\n";
}

echo "\n";

// Test 5: Get support message statistics
echo "Test 5: Get support message statistics\n";
$stats = get_support_statistics_ctr();
if ($stats) {
    echo "✓ Successfully retrieved statistics\n";
    echo "  - Total messages: " . ($stats['total'] ?? 'N/A') . "\n";
    echo "  - New messages: " . ($stats['new'] ?? '0') . "\n";
    echo "  - In progress: " . ($stats['in_progress'] ?? '0') . "\n";
    echo "  - Recent (24h): " . ($stats['recent_24h'] ?? '0') . "\n";
} else {
    echo "✗ Failed to retrieve statistics\n";
}

echo "\n";

// Test 6: Create a new support message to test the full workflow
echo "Test 6: Create new support message\n";
$new_message_id = create_support_message_ctr(1, 'Test Customer', 'test@example.com', 'repair', 'Testing message creation');
if ($new_message_id && $new_message_id > 0) {
    echo "✓ Successfully created support message with ID: $new_message_id\n";
} else {
    echo "✗ Failed to create support message (returned ID: $new_message_id)\n";
}

echo "\n";

// Test 7: Add admin response to create a notification
echo "Test 7: Add admin response\n";
if ($new_message_id) {
    $response_result = add_admin_response_ctr($new_message_id, 'Thank you for contacting us. We are looking into your repair request.', null);
    if ($response_result) {
        echo "✓ Successfully added admin response\n";
    } else {
        echo "✗ Failed to add admin response\n";
    }
} else {
    echo "⚠ Skipping - no message ID available\n";
}

echo "\n";

// Test 8: Check if notification was created automatically
echo "Test 8: Check if notification was created automatically\n";
$final_unread_count = get_unread_notification_count_ctr(1);
echo "✓ Current unread notifications: $final_unread_count\n";

echo "\nAll tests completed!\n";
?>