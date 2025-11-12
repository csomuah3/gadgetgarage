<?php
require_once __DIR__ . '/../settings/db_class.php';

class Brand extends db_connection {

    public function __construct() {
        // Ensure database connection is established
        $this->db_connect();
    }

    // Add a new brand (single category for now, compatible with existing structure)
    public function add_brand($brand_name, $category_ids, $user_id) {
        // Sanitize inputs
        $brand_name = trim($brand_name);
        $user_id = (int)$user_id;

        // For now, take the first category ID to work with existing structure
        $category_id = is_array($category_ids) ? $category_ids[0] : $category_ids;
        $category_id = (int)$category_id;

        // Validate inputs
        if (empty($brand_name) || $category_id <= 0) {
            return false;
        }

        // Ensure database connection
        if (!$this->db || mysqli_connect_errno()) {
            $this->db_connect();
        }

        $brand_name_escaped = mysqli_real_escape_string($this->db, $brand_name);

        // Insert brand with category_id (existing structure)
        $sql = "INSERT INTO brands (brand_name, category_id, user_id) VALUES ('$brand_name_escaped', $category_id, $user_id)";
        $result = $this->db_write_query($sql);

        if ($result) {
            return mysqli_insert_id($this->db);
        }

        return false;
    }

    // Legacy method for backward compatibility
    public function add_brand_single_category($brand_name, $category_id, $user_id) {
        return $this->add_brand($brand_name, $category_id, $user_id);
    }

    // Get all brands for a specific user
    public function get_brands_by_user($user_id) {
        $user_id = (int)$user_id;

        $sql = "SELECT b.brand_id, b.brand_name, b.category_id, c.cat_name
                FROM brands b
                LEFT JOIN categories c ON b.category_id = c.cat_id
                WHERE b.user_id = $user_id
                ORDER BY b.brand_name";

        return $this->db_fetch_all($sql);
    }

    // Get all brands (for admin)
    public function get_all_brands() {
        $sql = "SELECT b.brand_id, b.brand_name, b.category_id, c.cat_name
                FROM brands b
                LEFT JOIN categories c ON b.category_id = c.cat_id
                ORDER BY b.brand_name";

        return $this->db_fetch_all($sql);
    }

    // Get a specific brand by ID
    public function get_brand_by_id($brand_id) {
        $brand_id = (int)$brand_id;

        $sql = "SELECT b.brand_id, b.brand_name, b.category_id, c.cat_name
                FROM brands b
                LEFT JOIN categories c ON b.category_id = c.cat_id
                WHERE b.brand_id = $brand_id";

        return $this->db_fetch_one($sql);
    }

    // Update a brand
    public function update_brand($brand_id, $brand_name, $category_ids) {
        $brand_id = (int)$brand_id;
        $brand_name = trim($brand_name);

        // For now, take the first category ID to work with existing structure
        $category_id = is_array($category_ids) ? $category_ids[0] : $category_ids;
        $category_id = (int)$category_id;

        if (empty($brand_name) || $category_id <= 0) {
            return false;
        }

        // Ensure database connection
        if (!$this->db || mysqli_connect_errno()) {
            $this->db_connect();
        }

        $brand_name_escaped = mysqli_real_escape_string($this->db, $brand_name);

        // Update brand
        $sql = "UPDATE brands SET brand_name = '$brand_name_escaped', category_id = $category_id WHERE brand_id = $brand_id";
        return $this->db_write_query($sql);
    }

    // Legacy method for backward compatibility
    public function update_brand_single_category($brand_id, $brand_name, $category_id) {
        return $this->update_brand($brand_id, $brand_name, $category_id);
    }

    // Delete a brand
    public function delete_brand($brand_id) {
        $brand_id = (int)$brand_id;

        // Check for dependencies first
        $sql_check = "SELECT COUNT(*) as product_count FROM products WHERE brand_id = $brand_id";
        $result = $this->db_fetch_one($sql_check);

        if ($result && $result['product_count'] > 0) {
            return [
                'status' => 'error',
                'message' => 'Cannot delete brand. It is being used by ' . $result['product_count'] . ' product(s). Please remove or reassign these products first.'
            ];
        }

        // Delete the brand
        $sql = "DELETE FROM brands WHERE brand_id = $brand_id";
        $result = $this->db_write_query($sql);

        if ($result) {
            return ['status' => 'success', 'message' => 'Brand deleted successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete brand'];
        }
    }

    // Check if brand exists (for duplicate prevention) - now checks per category
    public function check_brand_exists($brand_name, $category_id = null, $user_id = null, $exclude_brand_id = null) {
        $brand_name = trim($brand_name);
        $brand_name_escaped = mysqli_real_escape_string($this->db, $brand_name);

        $sql = "SELECT brand_id FROM brands WHERE brand_name = '$brand_name_escaped'";

        // Check within the same category only (allows Apple for both iPhones and iPads)
        if ($category_id) {
            $sql .= " AND category_id = " . (int)$category_id;
        }

        if ($user_id) {
            $sql .= " AND user_id = " . (int)$user_id;
        }

        if ($exclude_brand_id) {
            $sql .= " AND brand_id != " . (int)$exclude_brand_id;
        }

        $result = $this->db_fetch_one($sql);
        return $result !== false;
    }

    // Get brands by category
    public function get_brands_by_category($category_id) {
        $category_id = (int)$category_id;

        $sql = "SELECT brand_id, brand_name, category_id FROM brands WHERE category_id = $category_id ORDER BY brand_name";
        return $this->db_fetch_all($sql);
    }

    // Get categories for a specific brand (for future many-to-many compatibility)
    public function get_brand_categories($brand_id) {
        $brand_id = (int)$brand_id;

        $sql = "SELECT c.cat_id, c.cat_name
                FROM categories c
                JOIN brands b ON c.cat_id = b.category_id
                WHERE b.brand_id = $brand_id";

        return $this->db_fetch_all($sql);
    }
}
?>