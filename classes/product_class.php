<?php
require_once __DIR__ . '/../settings/db_class.php';

class Product extends db_connection {

    public function __construct() {
        // Ensure database connection is established
        $this->db_connect();
    }

    // Add a new product
    public function add_product($product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity = 10) {
        // Sanitize inputs
        $product_title = trim($product_title);
        $product_price = (float)$product_price;
        $product_desc = trim($product_desc);
        $product_image = trim($product_image);
        $product_keywords = trim($product_keywords);
        $product_color = trim($product_color);
        $category_id = (int)$category_id;
        $brand_id = (int)$brand_id;
        $stock_quantity = (int)$stock_quantity;

        // Validate inputs
        if (empty($product_title) || $product_price <= 0) {
            return false;
        }

        // Validate that category exists
        $cat_check = "SELECT cat_id FROM categories WHERE cat_id = $category_id";
        $cat_result = $this->db_fetch_one($cat_check);
        if (!$cat_result) {
            return false; // Category doesn't exist
        }

        // Validate that brand exists
        $brand_check = "SELECT brand_id FROM brands WHERE brand_id = $brand_id";
        $brand_result = $this->db_fetch_one($brand_check);
        if (!$brand_result) {
            return false; // Brand doesn't exist
        }

        // Escape strings to prevent SQL injection
        $product_title = mysqli_real_escape_string($this->db, $product_title);
        $product_desc = mysqli_real_escape_string($this->db, $product_desc);
        $product_image = mysqli_real_escape_string($this->db, $product_image);
        $product_keywords = mysqli_real_escape_string($this->db, $product_keywords);
        $product_color = mysqli_real_escape_string($this->db, $product_color);

        // Check if stock_quantity column exists
        $check_stock = "SHOW COLUMNS FROM products LIKE 'stock_quantity'";
        $stock_exists = $this->db_fetch_one($check_stock);

        // Check if product_color column exists
        $check_color = "SHOW COLUMNS FROM products LIKE 'product_color'";
        $color_exists = $this->db_fetch_one($check_color);

        // Build INSERT query based on available columns
        if ($stock_exists && $color_exists) {
            $sql = "INSERT INTO products (product_title, product_price, product_desc, product_image, product_keywords, product_color, product_cat, product_brand, stock_quantity)
                    VALUES ('$product_title', $product_price, '$product_desc', '$product_image', '$product_keywords', '$product_color', $category_id, $brand_id, $stock_quantity)";
        } else if ($stock_exists && !$color_exists) {
            $sql = "INSERT INTO products (product_title, product_price, product_desc, product_image, product_keywords, product_cat, product_brand, stock_quantity)
                    VALUES ('$product_title', $product_price, '$product_desc', '$product_image', '$product_keywords', $category_id, $brand_id, $stock_quantity)";
        } else if (!$stock_exists && $color_exists) {
            $sql = "INSERT INTO products (product_title, product_price, product_desc, product_image, product_keywords, product_color, product_cat, product_brand)
                    VALUES ('$product_title', $product_price, '$product_desc', '$product_image', '$product_keywords', '$product_color', $category_id, $brand_id)";
        } else {
            $sql = "INSERT INTO products (product_title, product_price, product_desc, product_image, product_keywords, product_cat, product_brand)
                    VALUES ('$product_title', $product_price, '$product_desc', '$product_image', '$product_keywords', $category_id, $brand_id)";
        }

        return $this->db_write_query($sql);
    }

    // Get all products
    public function get_all_products() {
        // First check if stock_quantity column exists
        $check_column = "SHOW COLUMNS FROM products LIKE 'stock_quantity'";
        $column_exists = $this->db_fetch_one($check_column);

        if ($column_exists) {
            $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                           p.product_cat, p.product_brand, p.stock_quantity,
                           c.cat_name, b.brand_name
                    FROM products p
                    LEFT JOIN categories c ON p.product_cat = c.cat_id
                    LEFT JOIN brands b ON p.product_brand = b.brand_id
                    ORDER BY p.product_title";
        } else {
            $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                           p.product_cat, p.product_brand, 0 as stock_quantity,
                           c.cat_name, b.brand_name
                    FROM products p
                    LEFT JOIN categories c ON p.product_cat = c.cat_id
                    LEFT JOIN brands b ON p.product_brand = b.brand_id
                    ORDER BY p.product_title";
        }

        return $this->db_fetch_all($sql);
    }

    // Get products by category
    public function get_products_by_category($category_id) {
        $category_id = (int)$category_id;

        $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       p.product_cat, p.product_brand, p.stock_quantity,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = $category_id
                ORDER BY p.product_title";

        return $this->db_fetch_all($sql);
    }

    // Get products by brand
    public function get_products_by_brand($brand_id) {
        $brand_id = (int)$brand_id;

        $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       p.product_cat, p.product_brand, p.stock_quantity,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = $brand_id
                ORDER BY p.product_title";

        return $this->db_fetch_all($sql);
    }

    // Get a specific product by ID
    public function get_product_by_id($product_id) {
        $product_id = (int)$product_id;

        $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       p.product_cat, p.product_brand, p.stock_quantity,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_id = $product_id";

        return $this->db_fetch_one($sql);
    }

    // Update a product
    public function update_product($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id) {
        $product_id = (int)$product_id;
        $product_title = trim($product_title);
        $product_price = (float)$product_price;
        $product_desc = trim($product_desc);
        $product_image = trim($product_image);
        $product_keywords = trim($product_keywords);
        $category_id = (int)$category_id;
        $brand_id = (int)$brand_id;

        if (empty($product_title) || $product_price <= 0) {
            return false;
        }

        $sql = "UPDATE products SET
                product_title = '$product_title',
                product_price = $product_price,
                product_desc = '$product_desc',
                product_image = '$product_image',
                product_keywords = '$product_keywords',
                product_cat = $category_id,
                product_brand = $brand_id
                WHERE product_id = $product_id";

        return $this->db_write_query($sql);
    }

    // Delete a product
    public function delete_product($product_id) {
        $product_id = (int)$product_id;

        // Check for dependencies in various tables
        $dependencies = [];

        // Check cart items
        $cart_check = "SELECT COUNT(*) as count FROM cart WHERE p_id = $product_id";
        $cart_result = $this->db_fetch_one($cart_check);
        if ($cart_result && $cart_result['count'] > 0) {
            $dependencies[] = $cart_result['count'] . " item(s) in shopping carts";
        }

        // Check order details
        $order_check = "SELECT COUNT(*) as count FROM orderdetails WHERE product_id = $product_id";
        $order_result = $this->db_fetch_one($order_check);
        if ($order_result && $order_result['count'] > 0) {
            $dependencies[] = $order_result['count'] . " order(s)";
        }

        // If there are dependencies, return error with details
        if (!empty($dependencies)) {
            $message = "Cannot delete product. It is referenced by: " . implode(", ", $dependencies) . ". ";
            $message .= "Please remove these references first or contact support.";
            return ['status' => 'error', 'message' => $message];
        }

        // If no dependencies, proceed with deletion
        $sql = "DELETE FROM products WHERE product_id = $product_id";
        $result = $this->db_write_query($sql);

        if ($result) {
            return ['status' => 'success', 'message' => 'Product deleted successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete product from database'];
        }
    }

    // Check if product title exists
    public function check_product_exists($product_title, $product_id = null) {
        $product_title = trim($product_title);

        if ($product_id) {
            $product_id = (int)$product_id;
            $sql = "SELECT product_id FROM products WHERE product_title = '$product_title' AND product_id != $product_id";
        } else {
            $sql = "SELECT product_id FROM products WHERE product_title = '$product_title'";
        }

        $result = $this->db_fetch_one($sql);
        return $result ? true : false;
    }

    // Search products
    public function search_products($search_term) {
        $search_term = trim($search_term);

        $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       p.product_cat, p.product_brand, p.stock_quantity,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_title LIKE '%$search_term%'
                   OR p.product_desc LIKE '%$search_term%'
                   OR p.product_keywords LIKE '%$search_term%'
                ORDER BY p.product_title";

        return $this->db_fetch_all($sql);
    }

    // Required method aliases for assignment compliance
    public function view_all_products() {
        return $this->get_all_products();
    }

    public function filter_products_by_category($cat_id) {
        return $this->get_products_by_category($cat_id);
    }

    public function filter_products_by_brand($brand_id) {
        return $this->get_products_by_brand($brand_id);
    }

    public function view_single_product($id) {
        return $this->get_product_by_id($id);
    }

    // Remove product from all cart items (helper method for deletion)
    public function remove_from_all_carts($product_id) {
        $product_id = (int)$product_id;
        $sql = "DELETE FROM cart WHERE p_id = $product_id";
        return $this->db_write_query($sql);
    }

    // Force delete product (removes dependencies first)
    public function force_delete_product($product_id) {
        $product_id = (int)$product_id;

        // Remove from all carts first
        $this->remove_from_all_carts($product_id);

        // Remove from orderdetails (this will break order history but allows deletion)
        $delete_orderdetails = "DELETE FROM orderdetails WHERE product_id = $product_id";
        $this->db_query($delete_orderdetails);

        // Remove from product_images if exists
        $delete_product_images = "DELETE FROM product_images WHERE product_id = $product_id";
        $this->db_query($delete_product_images);

        // Now delete the product
        $sql = "DELETE FROM products WHERE product_id = $product_id";
        $result = $this->db_write_query($sql);

        if ($result) {
            return ['status' => 'success', 'message' => 'Product deleted successfully (removed from all references)'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete product from database'];
        }
    }
}
?>