<?php
require_once(__DIR__ . '/../settings/db_class.php');

class Compare extends db_connection {
    
    public function __construct() {
        $this->db_connect();
    }
    
    /**
     * Add product to compare list
     */
    public function add_to_compare($product_id, $customer_id, $ip_address = null) {
        $product_id = intval($product_id);
        $customer_id = intval($customer_id);
        $ip_address = $ip_address ? mysqli_real_escape_string($this->db, $ip_address) : '';
        
        // Check if already in compare
        $check_sql = "SELECT compare_id FROM product_compare 
                     WHERE customer_id = $customer_id 
                     AND product_id = $product_id";
        $existing = $this->db_fetch_one($check_sql);
        
        if ($existing) {
            return ['status' => 'info', 'message' => 'Product already in compare list'];
        }
        
        // Check limit (max 4 products to compare)
        $count_sql = "SELECT COUNT(*) as count FROM product_compare 
                     WHERE customer_id = $customer_id";
        $count_result = $this->db_fetch_one($count_sql);
        
        if ($count_result && $count_result['count'] >= 4) {
            return ['status' => 'error', 'message' => 'You can compare maximum 4 products at a time. Please remove one to add another.'];
        }
        
        // Add to compare
        $sql = "INSERT INTO product_compare (customer_id, product_id, ip_address) 
                VALUES ($customer_id, $product_id, '$ip_address')";
        
        $result = $this->db_write_query($sql);
        
        if ($result) {
            $count = $this->get_compare_count($customer_id);
            return [
                'status' => 'success', 
                'message' => 'Product added to compare list',
                'count' => $count
            ];
        } else {
            return ['status' => 'error', 'message' => 'Failed to add product to compare'];
        }
    }
    
    /**
     * Remove product from compare list
     */
    public function remove_from_compare($product_id, $customer_id) {
        $product_id = intval($product_id);
        $customer_id = intval($customer_id);
        
        $sql = "DELETE FROM product_compare 
                WHERE customer_id = $customer_id 
                AND product_id = $product_id";
        
        $result = $this->db_write_query($sql);
        
        if ($result) {
            $count = $this->get_compare_count($customer_id);
            return [
                'status' => 'success',
                'message' => 'Product removed from compare list',
                'count' => $count
            ];
        } else {
            return ['status' => 'error', 'message' => 'Failed to remove product'];
        }
    }
    
    /**
     * Get all products in compare list for a customer
     */
    public function get_compare_products($customer_id) {
        $customer_id = intval($customer_id);
        
        $sql = "SELECT 
                    p.product_id,
                    p.product_title,
                    p.product_price,
                    p.product_desc,
                    p.product_image,
                    p.product_keywords,
                    p.product_color,
                    p.stock_quantity,
                    c.cat_name,
                    b.brand_name,
                    pc.added_at
                FROM product_compare pc
                JOIN products p ON pc.product_id = p.product_id
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE pc.customer_id = $customer_id
                ORDER BY pc.added_at DESC";
        
        return $this->db_fetch_all($sql);
    }
    
    /**
     * Get compare count for a customer
     */
    public function get_compare_count($customer_id) {
        $customer_id = intval($customer_id);
        
        $sql = "SELECT COUNT(*) as count FROM product_compare 
                WHERE customer_id = $customer_id";
        $result = $this->db_fetch_one($sql);
        
        return $result ? intval($result['count']) : 0;
    }
    
    /**
     * Clear all compare products for a customer
     */
    public function clear_compare($customer_id) {
        $customer_id = intval($customer_id);
        
        $sql = "DELETE FROM product_compare WHERE customer_id = $customer_id";
        return $this->db_write_query($sql);
    }
    
    /**
     * Check if product is in compare list
     */
    public function is_in_compare($product_id, $customer_id) {
        $product_id = intval($product_id);
        $customer_id = intval($customer_id);
        
        $sql = "SELECT compare_id FROM product_compare 
                WHERE customer_id = $customer_id 
                AND product_id = $product_id";
        
        $result = $this->db_fetch_one($sql);
        return !empty($result);
    }
}
?>

