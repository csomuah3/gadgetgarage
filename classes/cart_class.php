<?php
require_once __DIR__ . '/../settings/db_class.php';

class Cart extends db_connection
{
    public function __construct()
    {
        $this->db_connect();
    }

    public function add_to_cart($product_id, $customer_id = null, $ip_address = null, $quantity = 1)
    {
        if (!$this->check_product_exists_in_cart($product_id, $customer_id, $ip_address)) {
            $sql = "INSERT INTO cart (p_id, c_id, ip_add, qty) VALUES (?, ?, ?, ?)";
            return $this->db_query($sql, [$product_id, $customer_id, $ip_address, $quantity]);
        } else {
            return $this->increment_cart_quantity($product_id, $customer_id, $ip_address, $quantity);
        }
    }

    public function check_product_exists_in_cart($product_id, $customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $sql = "SELECT * FROM cart WHERE p_id = ? AND c_id = ?";
            $params = [$product_id, $customer_id];
        } else {
            $sql = "SELECT * FROM cart WHERE p_id = ? AND ip_add = ?";
            $params = [$product_id, $ip_address];
        }

        $result = $this->db_fetch_one($sql, $params);
        return $result ? true : false;
    }

    public function increment_cart_quantity($product_id, $customer_id = null, $ip_address = null, $quantity = 1)
    {
        if ($customer_id) {
            $sql = "UPDATE cart SET qty = qty + ? WHERE p_id = ? AND c_id = ?";
            $params = [$quantity, $product_id, $customer_id];
        } else {
            $sql = "UPDATE cart SET qty = qty + ? WHERE p_id = ? AND ip_add = ?";
            $params = [$quantity, $product_id, $ip_address];
        }

        return $this->db_query($sql, $params);
    }

    public function update_cart_quantity($product_id, $customer_id = null, $ip_address = null, $quantity)
    {
        if ($customer_id) {
            $sql = "UPDATE cart SET qty = ? WHERE p_id = ? AND c_id = ?";
            $params = [$quantity, $product_id, $customer_id];
        } else {
            $sql = "UPDATE cart SET qty = ? WHERE p_id = ? AND ip_add = ?";
            $params = [$quantity, $product_id, $ip_address];
        }

        return $this->db_query($sql, $params);
    }

    public function remove_from_cart($product_id, $customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $sql = "DELETE FROM cart WHERE p_id = ? AND c_id = ?";
            $params = [$product_id, $customer_id];
        } else {
            $sql = "DELETE FROM cart WHERE p_id = ? AND ip_add = ?";
            $params = [$product_id, $ip_address];
        }

        return $this->db_query($sql, $params);
    }

    public function get_user_cart($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $sql = "SELECT c.*, p.product_title, p.product_price, p.product_image, p.product_desc
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.c_id = ?";
            $params = [$customer_id];
        } else {
            $sql = "SELECT c.*, p.product_title, p.product_price, p.product_image, p.product_desc
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.ip_add = ?";
            $params = [$ip_address];
        }

        return $this->db_fetch_all($sql, $params);
    }

    public function empty_cart($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $sql = "DELETE FROM cart WHERE c_id = ?";
            $params = [$customer_id];
        } else {
            $sql = "DELETE FROM cart WHERE ip_add = ?";
            $params = [$ip_address];
        }

        return $this->db_query($sql, $params);
    }

    public function get_cart_total($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $sql = "SELECT SUM(c.qty * p.product_price) as total
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.c_id = ?";
            $params = [$customer_id];
        } else {
            $sql = "SELECT SUM(c.qty * p.product_price) as total
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.ip_add = ?";
            $params = [$ip_address];
        }

        $result = $this->db_fetch_one($sql, $params);
        return $result ? $result['total'] : 0;
    }

    public function get_cart_count($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $sql = "SELECT SUM(qty) as count FROM cart WHERE c_id = ?";
            $params = [$customer_id];
        } else {
            $sql = "SELECT SUM(qty) as count FROM cart WHERE ip_add = ?";
            $params = [$ip_address];
        }

        $result = $this->db_fetch_one($sql, $params);
        return $result ? $result['count'] : 0;
    }
}
?>