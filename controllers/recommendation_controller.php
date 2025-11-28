<?php
require_once __DIR__ . '/../classes/recommendation_class.php';

function get_frequently_bought_together_ctr($product_ids)
{
    $recommendation = new Recommendation();
    return $recommendation->get_frequently_bought_together($product_ids);
}

function update_frequently_bought_together_ctr($order_id)
{
    $recommendation = new Recommendation();
    return $recommendation->update_frequently_bought_together($order_id);
}

