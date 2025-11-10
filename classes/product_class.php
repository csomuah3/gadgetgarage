<?php
require_once __DIR__ . '/../settings/db_class.php';

class Product extends db_connection {

    public function __construct() {
        // Ensure database connection is established
        $this->db_connect();
    }

    // Add a new product
    public function add_product($product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id) {
        // Sanitize inputs
        $product_title = trim($product_title);
        $product_price = (float)$product_price;
        $product_desc = trim($product_desc);
        $product_image = trim($product_image);
        $product_keywords = trim($product_keywords);
        $category_id = (int)$category_id;
        $brand_id = (int)$brand_id;

        // Validate inputs
        if (empty($product_title) || $product_price <= 0) {
            return false;
        }

        // Simple INSERT query
        $sql = "INSERT INTO products (product_title, product_price, product_desc, product_image, product_keywords, product_cat, product_brand)
                VALUES ('$product_title', $product_price, '$product_desc', '$product_image', '$product_keywords', $category_id, $brand_id)";

        return $this->db_write_query($sql);
    }

    // Get all products
    public function get_all_products() {
        $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       p.product_cat, p.product_brand,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                ORDER BY p.product_title";

        return $this->db_fetch_all($sql);
    }

    // Get products by category
    public function get_products_by_category($category_id) {
        $category_id = (int)$category_id;

        $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       p.product_cat, p.product_brand,
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
                       p.product_cat, p.product_brand,
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
                       p.product_cat, p.product_brand,
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
        $sql = "DELETE FROM products WHERE product_id = $product_id";
        return $this->db_write_query($sql);
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
                       p.product_cat, p.product_brand,
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
}
?>