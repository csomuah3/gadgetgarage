<?php
require_once __DIR__ . '/../settings/db_class.php';

class Product extends db_connection {

    /**
     * Add a new product to the database
     */
    public function add_product($product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity) {
        try {
            $sql = "INSERT INTO products (product_title, product_price, product_desc, product_image, product_keywords, product_color, product_cat, product_brand, stock_quantity)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            return $this->db_prepare_execute($sql, 'sdssssiid', [
                $product_title,
                $product_price,
                $product_desc,
                $product_image,
                $product_keywords,
                $product_color,
                $category_id,
                $brand_id,
                $stock_quantity
            ]);
        } catch (Exception $e) {
            error_log("Add product error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if product title already exists
     */
    public function check_product_exists($product_title, $exclude_id = null) {
        try {
            if ($exclude_id) {
                $sql = "SELECT product_id FROM products WHERE product_title = ? AND product_id != ?";
                $result = $this->db_prepare_fetch_one($sql, 'si', [$product_title, $exclude_id]);
            } else {
                $sql = "SELECT product_id FROM products WHERE product_title = ?";
                $result = $this->db_prepare_fetch_one($sql, 's', [$product_title]);
            }

            return !empty($result);
        } catch (Exception $e) {
            error_log("Check product exists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get last inserted product ID
     */
    public function get_last_inserted_id() {
        try {
            $connection = $this->db_connect();
            return $connection->lastInsertId();
        } catch (Exception $e) {
            error_log("Get last inserted ID error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all products
     */
    public function get_all_products() {
        try {
            $sql = "SELECT * FROM products ORDER BY product_id DESC";
            return $this->db_prepare_fetch_all($sql);
        } catch (Exception $e) {
            error_log("Get all products error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get product by ID
     */
    public function get_product_by_id($product_id) {
        try {
            $sql = "SELECT * FROM products WHERE product_id = ?";
            return $this->db_prepare_fetch_one($sql, 'i', [$product_id]);
        } catch (Exception $e) {
            error_log("Get product by ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get products by category
     */
    public function get_products_by_category($category_id) {
        try {
            $sql = "SELECT * FROM products WHERE product_cat = ? ORDER BY product_id DESC";
            return $this->db_prepare_fetch_all($sql, 'i', [$category_id]);
        } catch (Exception $e) {
            error_log("Get products by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get products by brand
     */
    public function get_products_by_brand($brand_id) {
        try {
            $sql = "SELECT * FROM products WHERE product_brand = ? ORDER BY product_id DESC";
            return $this->db_prepare_fetch_all($sql, 'i', [$brand_id]);
        } catch (Exception $e) {
            error_log("Get products by brand error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update product
     */
    public function update_product($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity) {
        try {
            // If image is empty, don't update it
            if (empty($product_image)) {
                $sql = "UPDATE products SET
                        product_title = ?, product_price = ?, product_desc = ?, product_keywords = ?,
                        product_color = ?, product_cat = ?, product_brand = ?, stock_quantity = ?
                        WHERE product_id = ?";

                return $this->db_prepare_execute($sql, 'sdsssiiid', [
                    $product_title, $product_price, $product_desc, $product_keywords,
                    $product_color, $category_id, $brand_id, $stock_quantity, $product_id
                ]);
            } else {
                $sql = "UPDATE products SET
                        product_title = ?, product_price = ?, product_desc = ?, product_image = ?,
                        product_keywords = ?, product_color = ?, product_cat = ?, product_brand = ?,
                        stock_quantity = ?
                        WHERE product_id = ?";

                return $this->db_prepare_execute($sql, 'sdssssiidi', [
                    $product_title, $product_price, $product_desc, $product_image, $product_keywords,
                    $product_color, $category_id, $brand_id, $stock_quantity, $product_id
                ]);
            }
        } catch (Exception $e) {
            error_log("Update product error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete product (soft delete by default)
     */
    public function delete_product($product_id) {
        try {
            $sql = "DELETE FROM products WHERE product_id = ?";
            return $this->db_prepare_execute($sql, 'i', [$product_id]);
        } catch (Exception $e) {
            error_log("Delete product error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Force delete product (removes all dependencies)
     */
    public function force_delete_product($product_id) {
        try {
            // Start transaction
            $connection = $this->db_connect();
            $connection->beginTransaction();

            try {
                // Delete from cart first (if exists)
                $sql = "DELETE FROM cart WHERE p_id = ?";
                $this->db_prepare_execute($sql, 'i', [$product_id]);

                // Delete from order items (if exists)
                $sql = "DELETE FROM orderdetails WHERE product_id = ?";
                $this->db_prepare_execute($sql, 'i', [$product_id]);

                // Delete product images (if exists)
                $sql = "DELETE FROM product_images WHERE product_id = ?";
                $this->db_prepare_execute($sql, 'i', [$product_id]);

                // Finally delete the product
                $sql = "DELETE FROM products WHERE product_id = ?";
                $result = $this->db_prepare_execute($sql, 'i', [$product_id]);

                if ($result) {
                    $connection->commit();
                    return [
                        'status' => 'success',
                        'message' => 'Product and all related data deleted successfully'
                    ];
                } else {
                    $connection->rollBack();
                    return [
                        'status' => 'error',
                        'message' => 'Failed to delete product'
                    ];
                }
            } catch (Exception $e) {
                $connection->rollBack();
                error_log("Force delete product transaction error: " . $e->getMessage());
                return [
                    'status' => 'error',
                    'message' => 'Database error during deletion: ' . $e->getMessage()
                ];
            }
        } catch (Exception $e) {
            error_log("Force delete product error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to connect to database'
            ];
        }
    }

    /**
     * Search products by title or keywords
     */
    public function search_products($search_term) {
        try {
            $search_pattern = "%$search_term%";
            $sql = "SELECT * FROM products
                    WHERE product_title LIKE ? OR product_keywords LIKE ? OR product_desc LIKE ?
                    ORDER BY product_title";

            return $this->db_prepare_fetch_all($sql, 'sss', [$search_pattern, $search_pattern, $search_pattern]);
        } catch (Exception $e) {
            error_log("Search products error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get products with low stock (below threshold)
     */
    public function get_low_stock_products($threshold = 10) {
        try {
            $sql = "SELECT * FROM products WHERE stock_quantity < ? AND stock_quantity > 0 ORDER BY stock_quantity ASC";
            return $this->db_prepare_fetch_all($sql, 'i', [$threshold]);
        } catch (Exception $e) {
            error_log("Get low stock products error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get out of stock products
     */
    public function get_out_of_stock_products() {
        try {
            $sql = "SELECT * FROM products WHERE stock_quantity = 0 ORDER BY product_title";
            return $this->db_prepare_fetch_all($sql);
        } catch (Exception $e) {
            error_log("Get out of stock products error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update stock quantity
     */
    public function update_stock_quantity($product_id, $new_quantity) {
        try {
            $sql = "UPDATE products SET stock_quantity = ? WHERE product_id = ?";
            return $this->db_prepare_execute($sql, 'ii', [$new_quantity, $product_id]);
        } catch (Exception $e) {
            error_log("Update stock quantity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reduce stock quantity (for purchases)
     */
    public function reduce_stock($product_id, $quantity) {
        try {
            $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?";
            return $this->db_prepare_execute($sql, 'iii', [$quantity, $product_id, $quantity]);
        } catch (Exception $e) {
            error_log("Reduce stock error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total inventory value
     */
    public function get_total_inventory_value() {
        try {
            $sql = "SELECT SUM(product_price * stock_quantity) as total_value FROM products";
            $result = $this->db_prepare_fetch_one($sql);
            return $result ? floatval($result['total_value']) : 0;
        } catch (Exception $e) {
            error_log("Get total inventory value error: " . $e->getMessage());
            return 0;
        }
    }

    // Assignment required methods (aliases for backward compatibility)
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