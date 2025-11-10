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

function record_payment_ctr($customer_id, $order_id, $amount, $currency = 'USD')
{
    $order = new Order();
    return $order->record_payment($customer_id, $order_id, $amount, $currency);
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
?>