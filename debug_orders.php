<?php
// Simple debug script to check orders
require_once 'settings/core.php';
require_once 'classes/order_class.php';

$order_obj = new Order();

try {
    // Check database connection first
    echo "Testing database connection...\n";

    // Try to get all orders
    $orders = $order_obj->get_all_orders();
    echo "Found " . count($orders) . " orders in total.\n";

    if (!empty($orders)) {
        echo "\nRecent orders:\n";
        foreach (array_slice($orders, 0, 5) as $order) {
            echo "Order ID: {$order['order_id']}, Invoice: {$order['invoice_no']}, Customer: {$order['customer_id']}\n";
        }
    }

    // Test tracking function with a known invoice
    if (!empty($orders)) {
        $test_invoice = $orders[0]['invoice_no'];
        echo "\nTesting tracking with invoice: $test_invoice\n";
        $result = $order_obj->get_order_tracking_details($test_invoice);
        if ($result) {
            echo "Tracking found! Order ID: " . $result['order']['order_id'] . "\n";
        } else {
            echo "Tracking failed for existing invoice!\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>