<?php
require_once __DIR__ . '/../classes/compare_class.php';

function add_to_compare_ctr($product_id, $customer_id, $ip_address = null) {
    $compare = new Compare();
    return $compare->add_to_compare($product_id, $customer_id, $ip_address);
}

function remove_from_compare_ctr($product_id, $customer_id) {
    $compare = new Compare();
    return $compare->remove_from_compare($product_id, $customer_id);
}

function get_compare_products_ctr($customer_id) {
    $compare = new Compare();
    return $compare->get_compare_products($customer_id);
}

function get_compare_count_ctr($customer_id) {
    $compare = new Compare();
    return $compare->get_compare_count($customer_id);
}

function clear_compare_ctr($customer_id) {
    $compare = new Compare();
    return $compare->clear_compare($customer_id);
}

function is_in_compare_ctr($product_id, $customer_id) {
    $compare = new Compare();
    return $compare->is_in_compare($product_id, $customer_id);
}
?>

