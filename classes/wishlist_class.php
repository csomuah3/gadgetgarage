<?php
require_once __DIR__ . '/../settings/db_class.php';

class Wishlist extends db_connection
{
    public function __construct()
    {
        $this->db_connect();
    }

    // Create wishlist table if it doesn't exist
    public function create_wishlist_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `wishlist` (
            `wishlist_id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `ip_address` varchar(50) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`wishlist_id`),
            UNIQUE KEY `unique_customer_product` (`customer_id`, `product_id`),
            KEY `product_id` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";

        return $this->db_write_query($sql);
    }

    public function add_to_wishlist($product_id, $customer_id, $ip_address = null)
    {
        // Ensure the table exists
        $this->create_wishlist_table();

        // Check if item already exists in wishlist
        if (!$this->check_wishlist_item($product_id, $customer_id)) {
            $product_id = mysqli_real_escape_string($this->db, $product_id);
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $ip_address = $ip_address ? "'" . mysqli_real_escape_string($this->db, $ip_address) . "'" : 'NULL';

            $sql = "INSERT INTO wishlist (customer_id, product_id, ip_address) VALUES ($customer_id, $product_id, $ip_address)";
            return $this->db_write_query($sql);
        }
        return false; // Item already in wishlist
    }

    public function remove_from_wishlist($product_id, $customer_id)
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);

        $sql = "DELETE FROM wishlist WHERE customer_id = $customer_id AND product_id = $product_id";
        return $this->db_write_query($sql);
    }

    public function check_wishlist_item($product_id, $customer_id)
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);

        $sql = "SELECT * FROM wishlist WHERE customer_id = $customer_id AND product_id = $product_id";
        $result = $this->db_read_query($sql);
        return $result && mysqli_num_rows($result) > 0;
    }

    public function get_wishlist_items($customer_id)
    {
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);

        $sql = "SELECT w.*, p.*
                FROM wishlist w
                JOIN products p ON w.product_id = p.product_id
                WHERE w.customer_id = $customer_id
                ORDER BY w.created_at DESC";

        $result = $this->db_read_query($sql);
        $items = array();

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }

        return $items;
    }

    public function get_wishlist_count($customer_id)
    {
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);

        $sql = "SELECT COUNT(*) as count FROM wishlist WHERE customer_id = $customer_id";
        $result = $this->db_read_query($sql);

        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row['count'];
        }

        return 0;
    }

    public function clear_wishlist($customer_id)
    {
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);

        $sql = "DELETE FROM wishlist WHERE customer_id = $customer_id";
        return $this->db_write_query($sql);
    }
}
?>