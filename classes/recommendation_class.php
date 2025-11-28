<?php
require_once __DIR__ . '/../settings/db_class.php';

class Recommendation extends db_connection
{
    public function __construct()
    {
        $this->db_connect();
    }

    public function get_frequently_bought_together($product_ids)
    {
        if (empty($product_ids) || !is_array($product_ids)) {
            return [];
        }

        $product_ids = array_map(function($id) {
            $id = preg_replace('/_\w+$/', '', $id);
            return intval($id);
        }, $product_ids);
        $product_ids = array_filter($product_ids, function($id) {
            return $id > 0;
        });
        
        if (empty($product_ids)) {
            return [];
        }
        
        $product_ids_str = implode(',', $product_ids);

        $sql = "SELECT fbt.related_product_id, 
                       SUM(fbt.purchase_count) as total_purchases,
                       AVG(fbt.confidence_score) as avg_confidence,
                       p.product_id,
                       p.product_title,
                       p.product_price,
                       p.product_image,
                       p.product_desc,
                       c.cat_name,
                       b.brand_name
                FROM frequently_bought_together fbt
                JOIN products p ON fbt.related_product_id = p.product_id
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE fbt.product_id IN ($product_ids_str)
                AND fbt.related_product_id NOT IN ($product_ids_str)
                GROUP BY fbt.related_product_id
                ORDER BY total_purchases DESC, avg_confidence DESC
                LIMIT 6";

        return $this->db_fetch_all($sql) ?: [];
    }

    public function update_frequently_bought_together($order_id)
    {
        $order_id = intval($order_id);
        
        $sql = "SELECT product_id FROM orderdetails WHERE order_id = $order_id";
        $products = $this->db_fetch_all($sql);
        
        if (count($products) < 2) {
            return false;
        }

        $product_ids = array_column($products, 'product_id');
        
        for ($i = 0; $i < count($product_ids); $i++) {
            for ($j = $i + 1; $j < count($product_ids); $j++) {
                $pid1 = intval($product_ids[$i]);
                $pid2 = intval($product_ids[$j]);
                
                $check_sql = "SELECT fbt_id, purchase_count FROM frequently_bought_together 
                             WHERE (product_id = $pid1 AND related_product_id = $pid2) 
                             OR (product_id = $pid2 AND related_product_id = $pid1) 
                             LIMIT 1";
                $existing = $this->db_fetch_one($check_sql);
                
                if ($existing) {
                    $new_count = intval($existing['purchase_count']) + 1;
                    $confidence = min(100, ($new_count / 10) * 10);
                    
                    $update_sql = "UPDATE frequently_bought_together 
                                  SET purchase_count = $new_count, 
                                      confidence_score = $confidence 
                                  WHERE fbt_id = " . intval($existing['fbt_id']);
                    $this->db_write_query($update_sql);
                } else {
                    $insert_sql = "INSERT INTO frequently_bought_together 
                                  (product_id, related_product_id, purchase_count, confidence_score) 
                                  VALUES ($pid1, $pid2, 1, 10.00)";
                    $this->db_write_query($insert_sql);
                }
            }
        }
        
        return true;
    }
}

