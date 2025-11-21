<?php
require_once __DIR__ . '/../classes/cart_class.php';

function add_to_cart_ctr($product_id, $quantity = 1, $customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->add_to_cart($product_id, $quantity, $customer_id, $ip_address);
}

function add_to_cart_with_condition_ctr($product_id, $quantity = 1, $customer_id = null, $ip_address = null, $condition = 'excellent', $final_price = 0)
{
    $cart = new Cart();
    return $cart->add_to_cart_with_condition($product_id, $quantity, $customer_id, $ip_address, $condition, $final_price);
}

function update_cart_item_ctr($product_id, $quantity, $customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->update_cart_quantity($product_id, $quantity, $customer_id, $ip_address);
}

function remove_from_cart_ctr($product_id, $customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->remove_from_cart($product_id, $customer_id, $ip_address);
}

function get_user_cart_ctr($customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->get_user_cart($customer_id, $ip_address);
}

function empty_cart_ctr($customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->empty_cart($customer_id, $ip_address);
}

function get_cart_total_ctr($customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->get_cart_total($customer_id, $ip_address);
}

function get_cart_count_ctr($customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->get_cart_count($customer_id, $ip_address);
}

function check_product_in_cart_ctr($product_id, $customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->check_product_exists_in_cart($product_id, $customer_id, $ip_address);
}

function get_cart_items_ctr($customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->get_user_cart($customer_id, $ip_address);
}

function get_abandoned_carts_ctr($min_idle_time_seconds = 1800)
{
    $cart = new Cart();
    return $cart->get_abandoned_carts($min_idle_time_seconds);
}

function update_cart_activity_ctr($customer_id = null, $ip_address = null)
{
    $cart = new Cart();
    return $cart->update_cart_activity($customer_id, $ip_address);
}
?>