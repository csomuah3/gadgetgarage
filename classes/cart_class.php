<?php
require_once __DIR__ . '/../settings/db_class.php';

class Cart extends db_connection
{
    public function __construct()
    {
        $this->db_connect();
    }

    public function add_to_cart($product_id, $quantity = 1, $customer_id = null, $ip_address = null)
    {
        if (!$this->check_product_exists_in_cart($product_id, $customer_id, $ip_address)) {
            $product_id = mysqli_real_escape_string($this->db, $product_id);
            $customer_id = $customer_id ? mysqli_real_escape_string($this->db, $customer_id) : 'NULL';
            $ip_address = $ip_address ? "'" . mysqli_real_escape_string($this->db, $ip_address) . "'" : 'NULL';
            $quantity = mysqli_real_escape_string($this->db, $quantity);

            $sql = "INSERT INTO cart (p_id, c_id, ip_add, qty) VALUES ($product_id, $customer_id, $ip_address, $quantity)";
            return $this->db_write_query($sql);
        } else {
            return $this->increment_cart_quantity($product_id, $quantity, $customer_id, $ip_address);
        }
    }

    public function check_product_exists_in_cart($product_id, $customer_id = null, $ip_address = null)
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);

        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "SELECT * FROM cart WHERE p_id = $product_id AND c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "SELECT * FROM cart WHERE p_id = $product_id AND ip_add = '$ip_address'";
        }

        $result = $this->db_fetch_one($sql);
        return $result ? true : false;
    }

    public function increment_cart_quantity($product_id, $quantity = 1, $customer_id = null, $ip_address = null)
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $quantity = mysqli_real_escape_string($this->db, $quantity);

        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "UPDATE cart SET qty = qty + $quantity WHERE p_id = $product_id AND c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "UPDATE cart SET qty = qty + $quantity WHERE p_id = $product_id AND ip_add = '$ip_address'";
        }

        return $this->db_write_query($sql);
    }

    public function update_cart_quantity($product_id, $quantity, $customer_id = null, $ip_address = null)
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $quantity = mysqli_real_escape_string($this->db, $quantity);

        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "UPDATE cart SET qty = $quantity WHERE p_id = $product_id AND c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "UPDATE cart SET qty = $quantity WHERE p_id = $product_id AND ip_add = '$ip_address'";
        }

        return $this->db_write_query($sql);
    }

    public function remove_from_cart($product_id, $customer_id = null, $ip_address = null)
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);

        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "DELETE FROM cart WHERE p_id = $product_id AND c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "DELETE FROM cart WHERE p_id = $product_id AND ip_add = '$ip_address'";
        }

        return $this->db_write_query($sql);
    }

    public function get_user_cart($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "SELECT c.*, p.product_title, p.product_price, p.product_image, p.product_desc
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "SELECT c.*, p.product_title, p.product_price, p.product_image, p.product_desc
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.ip_add = '$ip_address'";
        }

        return $this->db_fetch_all($sql);
    }

    public function empty_cart($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "DELETE FROM cart WHERE c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "DELETE FROM cart WHERE ip_add = '$ip_address'";
        }

        return $this->db_write_query($sql);
    }

    public function get_cart_total($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "SELECT SUM(c.qty * p.product_price) as total
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "SELECT SUM(c.qty * p.product_price) as total
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.ip_add = '$ip_address'";
        }

        $result = $this->db_fetch_one($sql);
        return $result && $result['total'] !== null ? floatval($result['total']) : 0;
    }

    public function get_cart_count($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "SELECT SUM(qty) as count FROM cart WHERE c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "SELECT SUM(qty) as count FROM cart WHERE ip_add = '$ip_address'";
        }

        $result = $this->db_fetch_one($sql);
        return $result && $result['count'] !== null ? intval($result['count']) : 0;
    }
}
?>