<?php
require_once __DIR__ . '/../classes/order_class.php';

function create_order_ctr($customer_id, $invoice_no = null, $order_status = 'pending')
{
    $order = new Order();
    return $order->create_order($customer_id, $invoice_no, $order_status);
}

function add_order_details_ctr($order_id, $product_id, $quantity)
{
    $order = new Order();
    return $order->add_order_details($order_id, $product_id, $quantity);
}

function record_payment_ctr($customer_id, $order_id, $amount, $currency = 'GHS', $payment_method = 'paystack', $transaction_ref = null, $authorization_code = null, $payment_channel = null)
{
    $order = new Order();
    return $order->record_payment($customer_id, $order_id, $amount, $currency, $payment_method, $transaction_ref, $authorization_code, $payment_channel);
}

function get_user_orders_ctr($customer_id)
{
    $order = new Order();
    return $order->get_user_orders($customer_id);
}

function get_order_details_ctr($order_id)
{
    $order = new Order();
    return $order->get_order_details($order_id);
}

function get_order_by_id_ctr($order_id)
{
    $order = new Order();
    return $order->get_order_by_id($order_id);
}

function update_order_status_ctr($order_id, $status)
{
    $order = new Order();
    return $order->update_order_status($order_id, $status);
}

function process_cart_to_order_ctr($customer_id, $ip_address = null)
{
    $order = new Order();
    return $order->process_cart_to_order($customer_id, $ip_address);
}

function process_cart_to_order_without_payment_ctr($customer_id, $ip_address = null)
{
    $order = new Order();
    return $order->process_cart_to_order_without_payment($customer_id, $ip_address);
}

function get_order_tracking_details($search_value)
{
    $order = new Order();
    return $order->get_order_tracking_details($search_value);
}

function update_order_tracking_ctr($order_id, $status, $notes = null, $location = null, $updated_by = null)
{
    $order = new Order();
    return $order->update_order_tracking($order_id, $status, $notes, $location, $updated_by);
}

function generate_invoice_number_ctr()
{
    $order = new Order();
    return $order->generate_invoice_number();
}

function generate_order_reference_ctr()
{
    $order = new Order();
    return $order->generate_order_reference();
}

function get_all_orders_ctr()
{
    $order = new Order();
    return $order->get_all_orders();
}

function assign_tracking_number_ctr($order_id)
{
    $order = new Order();
    return $order->assign_tracking_number($order_id);
}
?>