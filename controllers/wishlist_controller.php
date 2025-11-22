<?php
require_once __DIR__ . '/../classes/wishlist_class.php';

function add_to_wishlist_ctr($product_id, $customer_id, $ip_address = null)
{
    $wishlist = new Wishlist();
    return $wishlist->add_to_wishlist($product_id, $customer_id, $ip_address);
}

function remove_from_wishlist_ctr($product_id, $customer_id)
{
    $wishlist = new Wishlist();
    return $wishlist->remove_from_wishlist($product_id, $customer_id);
}

function get_wishlist_items_ctr($customer_id)
{
    $wishlist = new Wishlist();
    return $wishlist->get_wishlist_items($customer_id);
}

function get_wishlist_count_ctr($customer_id)
{
    $wishlist = new Wishlist();
    return $wishlist->get_wishlist_count($customer_id);
}

function check_wishlist_item_ctr($product_id, $customer_id)
{
    $wishlist = new Wishlist();
    return $wishlist->check_wishlist_item($product_id, $customer_id);
}

function clear_wishlist_ctr($customer_id)
{
    $wishlist = new Wishlist();
    return $wishlist->clear_wishlist($customer_id);
}
?>