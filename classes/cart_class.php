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

    public function add_to_cart_with_condition($product_id, $quantity = 1, $customer_id = null, $ip_address = null, $condition = 'excellent', $final_price = 0)
    {
        // First, check if the cart table has condition and final_price columns
        // If not, we'll add them or use a workaround

        if (!$this->check_product_condition_exists_in_cart($product_id, $customer_id, $ip_address, $condition)) {
            $product_id = mysqli_real_escape_string($this->db, $product_id);
            $customer_id = $customer_id ? mysqli_real_escape_string($this->db, $customer_id) : 'NULL';
            $ip_address = $ip_address ? "'" . mysqli_real_escape_string($this->db, $ip_address) . "'" : 'NULL';
            $quantity = mysqli_real_escape_string($this->db, $quantity);
            $condition = mysqli_real_escape_string($this->db, $condition);
            $final_price = mysqli_real_escape_string($this->db, $final_price);

            // Try to insert with condition and final_price columns
            // If columns don't exist, fall back to basic insert
            $sql = "INSERT INTO cart (p_id, c_id, ip_add, qty, condition_type, final_price) VALUES ($product_id, $customer_id, $ip_address, $quantity, '$condition', $final_price)";
            $result = $this->db_write_query($sql);

            if (!$result) {
                // If insert fails (likely due to missing columns), use basic insert
                $sql_basic = "INSERT INTO cart (p_id, c_id, ip_add, qty) VALUES ($product_id, $customer_id, $ip_address, $quantity)";
                return $this->db_write_query($sql_basic);
            }

            return $result;
        } else {
            return $this->increment_cart_quantity_with_condition($product_id, $quantity, $customer_id, $ip_address, $condition);
        }
    }

    public function check_product_condition_exists_in_cart($product_id, $customer_id = null, $ip_address = null, $condition = 'excellent')
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $condition = mysqli_real_escape_string($this->db, $condition);

        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            // Check if condition column exists and use it, otherwise just check product
            $sql = "SELECT * FROM cart WHERE p_id = $product_id AND c_id = $customer_id";
            if ($this->column_exists('cart', 'condition_type')) {
                $sql = "SELECT * FROM cart WHERE p_id = $product_id AND c_id = $customer_id AND condition_type = '$condition'";
            }
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "SELECT * FROM cart WHERE p_id = $product_id AND ip_add = '$ip_address'";
            if ($this->column_exists('cart', 'condition_type')) {
                $sql = "SELECT * FROM cart WHERE p_id = $product_id AND ip_add = '$ip_address' AND condition_type = '$condition'";
            }
        }

        $result = $this->db_fetch_one($sql);
        return $result ? true : false;
    }

    public function increment_cart_quantity_with_condition($product_id, $quantity = 1, $customer_id = null, $ip_address = null, $condition = 'excellent')
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $quantity = mysqli_real_escape_string($this->db, $quantity);
        $condition = mysqli_real_escape_string($this->db, $condition);

        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            if ($this->column_exists('cart', 'condition_type')) {
                $sql = "UPDATE cart SET qty = qty + $quantity WHERE p_id = $product_id AND c_id = $customer_id AND condition_type = '$condition'";
            } else {
                $sql = "UPDATE cart SET qty = qty + $quantity WHERE p_id = $product_id AND c_id = $customer_id";
            }
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            if ($this->column_exists('cart', 'condition_type')) {
                $sql = "UPDATE cart SET qty = qty + $quantity WHERE p_id = $product_id AND ip_add = '$ip_address' AND condition_type = '$condition'";
            } else {
                $sql = "UPDATE cart SET qty = qty + $quantity WHERE p_id = $product_id AND ip_add = '$ip_address'";
            }
        }

        return $this->db_write_query($sql);
    }

    private function column_exists($table, $column)
    {
        $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
        $result = $this->db_fetch_one($sql);
        return $result ? true : false;
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
        // Build base query with condition support
        $base_select = "c.*, p.product_title, p.product_price, p.product_image, p.product_desc";

        // Add condition columns if they exist
        if ($this->column_exists('cart', 'condition_type')) {
            $base_select .= ", c.condition_type";
        }
        if ($this->column_exists('cart', 'final_price')) {
            $base_select .= ", c.final_price";
        }

        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "SELECT $base_select
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    WHERE c.c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "SELECT $base_select
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
            // Use final_price if available AND NOT NULL AND > 0, otherwise fall back to product_price
            if ($this->column_exists('cart', 'final_price')) {
                $sql = "SELECT SUM(c.qty * CASE WHEN c.final_price IS NOT NULL AND c.final_price > 0 THEN c.final_price ELSE p.product_price END) as total
                        FROM cart c
                        JOIN products p ON c.p_id = p.product_id
                        WHERE c.c_id = $customer_id";
            } else {
                $sql = "SELECT SUM(c.qty * p.product_price) as total
                        FROM cart c
                        JOIN products p ON c.p_id = p.product_id
                        WHERE c.c_id = $customer_id";
            }
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            if ($this->column_exists('cart', 'final_price')) {
                $sql = "SELECT SUM(c.qty * CASE WHEN c.final_price IS NOT NULL AND c.final_price > 0 THEN c.final_price ELSE p.product_price END) as total
                        FROM cart c
                        JOIN products p ON c.p_id = p.product_id
                        WHERE c.ip_add = '$ip_address'";
            } else {
                $sql = "SELECT SUM(c.qty * p.product_price) as total
                        FROM cart c
                        JOIN products p ON c.p_id = p.product_id
                        WHERE c.ip_add = '$ip_address'";
            }
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

    public function get_abandoned_carts($min_idle_time_seconds = 1800)
    {
        $sql = "SELECT DISTINCT c.c_id as user_id, u.user_name as name, u.user_email as email, u.user_contact as phone
                FROM cart c
                JOIN users u ON c.c_id = u.user_id
                WHERE c.c_id IS NOT NULL
                AND c.c_id NOT IN (
                    SELECT customer_id FROM orders
                    WHERE DATE(order_date) = CURDATE()
                )
                AND TIMESTAMPDIFF(SECOND, IFNULL(c.updated_at, c.created_at), NOW()) > $min_idle_time_seconds
                GROUP BY c.c_id
                HAVING COUNT(c.p_id) > 0";

        return $this->db_fetch_all($sql);
    }

    public function update_cart_activity($customer_id = null, $ip_address = null)
    {
        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $sql = "UPDATE cart SET updated_at = NOW() WHERE c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $sql = "UPDATE cart SET updated_at = NOW() WHERE ip_add = '$ip_address'";
        }

        return $this->db_write_query($sql);
    }
}
?>