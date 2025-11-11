<?php
require_once __DIR__ . '/../settings/db_class.php';

class Brand extends db_connection {

    public function __construct() {
        // Ensure database connection is established
        $this->db_connect();
    }

    // Add a new brand with multiple categories
    public function add_brand($brand_name, $category_ids, $user_id) {
        // Sanitize inputs
        $brand_name = trim($brand_name);
        $user_id = (int)$user_id;

        // Handle both single category (legacy) and multiple categories
        if (!is_array($category_ids)) {
            $category_ids = [$category_ids]; // Convert single to array for compatibility
        }
        $category_ids = array_filter(array_map('intval', $category_ids), function($id) { return $id > 0; });

        // Validate inputs
        if (empty($brand_name)) {
            return false;
        }

        if (empty($category_ids)) {
            return false;
        }

        // Ensure database connection
        if (!$this->db || mysqli_connect_errno()) {
            $this->db_connect();
        }

        $brand_name_escaped = mysqli_real_escape_string($this->db, $brand_name);

        // Insert brand (without category_id)
        $sql = "INSERT INTO brands (brand_name, user_id) VALUES ('$brand_name_escaped', $user_id)";
        $result = $this->db_write_query($sql);

        if ($result) {
            $brand_id = mysqli_insert_id($this->db);

            // Insert brand-category relationships
            foreach ($category_ids as $category_id) {
                $sql_junction = "INSERT INTO brand_categories (brand_id, category_id) VALUES ($brand_id, $category_id)";
                $this->db_write_query($sql_junction);
            }

            return $brand_id;
        }

        return false;
    }

    // Legacy method for backward compatibility
    public function add_brand_single_category($brand_name, $category_id, $user_id) {
        return $this->add_brand($brand_name, [$category_id], $user_id);
    }

    // Get all brands for a specific user with their categories
    public function get_brands_by_user($user_id) {
        $user_id = (int)$user_id;

        $sql = "SELECT DISTINCT b.brand_id, b.brand_name,
                GROUP_CONCAT(c.cat_name ORDER BY c.cat_name SEPARATOR ', ') as category_names,
                GROUP_CONCAT(c.cat_id ORDER BY c.cat_name SEPARATOR ',') as category_ids
                FROM brands b
                LEFT JOIN brand_categories bc ON b.brand_id = bc.brand_id
                LEFT JOIN categories c ON bc.category_id = c.cat_id
                WHERE b.user_id = $user_id
                GROUP BY b.brand_id, b.brand_name
                ORDER BY b.brand_name";

        return $this->db_fetch_all($sql);
    }

    // Get all brands (for admin) with their categories
    public function get_all_brands() {
        $sql = "SELECT DISTINCT b.brand_id, b.brand_name,
                GROUP_CONCAT(c.cat_name ORDER BY c.cat_name SEPARATOR ', ') as category_names,
                GROUP_CONCAT(c.cat_id ORDER BY c.cat_name SEPARATOR ',') as category_ids
                FROM brands b
                LEFT JOIN brand_categories bc ON b.brand_id = bc.brand_id
                LEFT JOIN categories c ON bc.category_id = c.cat_id
                GROUP BY b.brand_id, b.brand_name
                ORDER BY b.brand_name";

        return $this->db_fetch_all($sql);
    }

    // Get a specific brand by ID with categories
    public function get_brand_by_id($brand_id) {
        $brand_id = (int)$brand_id;

        $sql = "SELECT DISTINCT b.brand_id, b.brand_name,
                GROUP_CONCAT(c.cat_name ORDER BY c.cat_name SEPARATOR ', ') as category_names,
                GROUP_CONCAT(c.cat_id ORDER BY c.cat_name SEPARATOR ',') as category_ids
                FROM brands b
                LEFT JOIN brand_categories bc ON b.brand_id = bc.brand_id
                LEFT JOIN categories c ON bc.category_id = c.cat_id
                WHERE b.brand_id = $brand_id
                GROUP BY b.brand_id, b.brand_name";

        return $this->db_fetch_one($sql);
    }

    // Update a brand with multiple categories
    public function update_brand($brand_id, $brand_name, $category_ids) {
        $brand_id = (int)$brand_id;
        $brand_name = trim($brand_name);

        // Handle both single category (legacy) and multiple categories
        if (!is_array($category_ids)) {
            $category_ids = [$category_ids]; // Convert single to array for compatibility
        }
        $category_ids = array_filter(array_map('intval', $category_ids), function($id) { return $id > 0; });

        if (empty($brand_name)) {
            return false;
        }

        if (empty($category_ids)) {
            return false;
        }

        // Ensure database connection
        if (!$this->db || mysqli_connect_errno()) {
            $this->db_connect();
        }

        $brand_name_escaped = mysqli_real_escape_string($this->db, $brand_name);

        // Update brand name
        $sql = "UPDATE brands SET brand_name = '$brand_name_escaped' WHERE brand_id = $brand_id";
        $result = $this->db_write_query($sql);

        if ($result) {
            // Delete existing brand-category relationships
            $sql_delete = "DELETE FROM brand_categories WHERE brand_id = $brand_id";
            $this->db_write_query($sql_delete);

            // Insert new brand-category relationships
            foreach ($category_ids as $category_id) {
                $sql_insert = "INSERT INTO brand_categories (brand_id, category_id) VALUES ($brand_id, $category_id)";
                $this->db_write_query($sql_insert);
            }

            return true;
        }

        return false;
    }

    // Legacy method for backward compatibility
    public function update_brand_single_category($brand_id, $brand_name, $category_id) {
        return $this->update_brand($brand_id, $brand_name, [$category_id]);
    }

    // Delete a brand
    public function delete_brand($brand_id) {
        $brand_id = (int)$brand_id;

        // First check if there are any products using this brand
        $check_sql = "SELECT COUNT(*) as product_count FROM products WHERE product_brand = $brand_id";
        $result = $this->db_fetch_one($check_sql);

        if ($result && $result['product_count'] > 0) {
            // Cannot delete brand because products are using it
            return [
                'status' => 'error',
                'message' => 'Cannot delete brand: ' . $result['product_count'] . ' product(s) are using this brand. Please reassign or delete the products first.'
            ];
        }

        // Also check cart items that might reference products with this brand
        $cart_check_sql = "SELECT COUNT(*) as cart_count FROM cart c
                          INNER JOIN products p ON c.p_id = p.product_id
                          WHERE p.product_brand = $brand_id";
        $cart_result = $this->db_fetch_one($cart_check_sql);

        if ($cart_result && $cart_result['cart_count'] > 0) {
            return [
                'status' => 'error',
                'message' => 'Cannot delete brand: Products from this brand are currently in shopping carts. Please remove them from carts first.'
            ];
        }

        // Also check order details
        $order_check_sql = "SELECT COUNT(*) as order_count FROM orderdetails od
                           INNER JOIN products p ON od.product_id = p.product_id
                           WHERE p.product_brand = $brand_id";
        $order_result = $this->db_fetch_one($order_check_sql);

        if ($order_result && $order_result['order_count'] > 0) {
            return [
                'status' => 'error',
                'message' => 'Cannot delete brand: This brand has products in existing orders and cannot be deleted for record keeping purposes.'
            ];
        }

        // If no dependencies found, proceed with deletion
        $sql = "DELETE FROM brands WHERE brand_id = $brand_id";
        $delete_result = $this->db_write_query($sql);

        if ($delete_result) {
            return ['status' => 'success', 'message' => 'Brand deleted successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete brand'];
        }
    }

    // Check if brand name exists (updated for many-to-many)
    public function check_brand_exists($brand_name, $category_id = null, $user_id = null, $brand_id = null) {
        $brand_name = trim($brand_name);

        if ($brand_id) {
            $brand_id = (int)$brand_id;
            $sql = "SELECT brand_id FROM brands WHERE brand_name = '$brand_name' AND brand_id != $brand_id";
        } else {
            $sql = "SELECT brand_id FROM brands WHERE brand_name = '$brand_name'";
        }

        $result = $this->db_fetch_one($sql);
        return $result ? true : false;
    }

    // Get brands by category
    public function get_brands_by_category($category_id) {
        $category_id = (int)$category_id;

        $sql = "SELECT DISTINCT b.brand_id, b.brand_name
                FROM brands b
                INNER JOIN brand_categories bc ON b.brand_id = bc.brand_id
                WHERE bc.category_id = $category_id
                ORDER BY b.brand_name";

        return $this->db_fetch_all($sql);
    }

    // Get categories for a specific brand
    public function get_brand_categories($brand_id) {
        $brand_id = (int)$brand_id;

        $sql = "SELECT c.cat_id, c.cat_name
                FROM categories c
                INNER JOIN brand_categories bc ON c.cat_id = bc.category_id
                WHERE bc.brand_id = $brand_id
                ORDER BY c.cat_name";

        return $this->db_fetch_all($sql);
    }

    // Add category to existing brand
    public function add_brand_category($brand_id, $category_id) {
        $brand_id = (int)$brand_id;
        $category_id = (int)$category_id;

        // Check if relationship already exists
        $sql_check = "SELECT id FROM brand_categories WHERE brand_id = $brand_id AND category_id = $category_id";
        $existing = $this->db_fetch_one($sql_check);

        if (!$existing) {
            $sql = "INSERT INTO brand_categories (brand_id, category_id) VALUES ($brand_id, $category_id)";
            return $this->db_write_query($sql);
        }

        return true; // Already exists
    }

    // Remove category from brand
    public function remove_brand_category($brand_id, $category_id) {
        $brand_id = (int)$brand_id;
        $category_id = (int)$category_id;

        $sql = "DELETE FROM brand_categories WHERE brand_id = $brand_id AND category_id = $category_id";
        return $this->db_write_query($sql);
    }
}
?>