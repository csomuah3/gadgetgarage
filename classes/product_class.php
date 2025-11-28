<?php
require_once __DIR__ . '/../settings/db_class.php';

class Product extends db_connection
{
    public function __construct()
    {
        if ($this->db === null) {
            $this->db_connect();
        }
    }

    /**
     * Add a new product to the database
     */
    public function add_product($product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity)
    {
        try {
            error_log("========== ADD PRODUCT START ==========");
            error_log("Product Title: " . $product_title);
            error_log("Product Price: " . $product_price);
            error_log("Category ID: " . $category_id);
            error_log("Brand ID: " . $brand_id);
            error_log("Stock Quantity: " . $stock_quantity);

            // Check database connection
            if (!isset($this->db) || !$this->db) {
                error_log("ERROR: Database connection not available");
                return false;
            }
            error_log("Database connection: OK");

            // Escape all string inputs
            $product_title = mysqli_real_escape_string($this->db, $product_title);
            $product_desc = mysqli_real_escape_string($this->db, $product_desc);
            $product_image = mysqli_real_escape_string($this->db, $product_image);
            $product_keywords = mysqli_real_escape_string($this->db, $product_keywords);
            $product_color = mysqli_real_escape_string($this->db, $product_color);

            $sql = "INSERT INTO products (product_title, product_price, product_desc, product_image, product_keywords, product_color, product_cat, product_brand, stock_quantity)
                    VALUES ('$product_title', '$product_price', '$product_desc', '$product_image', '$product_keywords', '$product_color', '$category_id', '$brand_id', '$stock_quantity')";

            error_log("SQL Query: " . $sql);

            // Use db_write_query for INSERT operations
            $result = $this->db_write_query($sql);

            error_log("db_write_query result type: " . gettype($result));
            error_log("db_write_query result value: " . var_export($result, true));

            if ($result === false) {
                $mysql_error = mysqli_error($this->db);
                $mysql_errno = mysqli_errno($this->db);
                error_log("========== DATABASE ERROR ==========");
                error_log("MySQL Error Number: " . $mysql_errno);
                error_log("MySQL Error Message: " . $mysql_error);
                error_log("Failed SQL: " . $sql);
                error_log("=====================================");
                return false;
            } else {
                $inserted_id = mysqli_insert_id($this->db);
                error_log("✅ Product inserted successfully!");
                error_log("Inserted Product ID: " . $inserted_id);
                error_log("========== ADD PRODUCT END ==========");
            }

            return $result;
        } catch (Exception $e) {
            error_log("========== EXCEPTION IN ADD_PRODUCT ==========");
            error_log("Exception Message: " . $e->getMessage());
            error_log("Exception Code: " . $e->getCode());
            error_log("Stack Trace: " . $e->getTraceAsString());
            error_log("==============================================");
            return false;
        }
    }

    /**
     * Check if product title already exists
     */
    public function check_product_exists($product_title, $exclude_id = null)
    {
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
    public function get_last_inserted_id()
    {
        try {
            if (!$this->db_connect()) {
                return 0;
            }
            return mysqli_insert_id($this->db);
        } catch (Exception $e) {
            error_log("Get last inserted ID error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get last MySQL error
     */
    public function get_last_error()
    {
        try {
            if (!isset($this->db) || !$this->db) {
                return 'No database connection';
            }
            $errno = mysqli_errno($this->db);
            $error = mysqli_error($this->db);
            return "Error $errno: $error";
        } catch (Exception $e) {
            return 'Unable to get error: ' . $e->getMessage();
        }
    }

    /**
     * Get all products (without category/brand names - for internal use)
     */
    public function get_all_products_basic()
    {
        try {
            $sql = "SELECT * FROM products ORDER BY product_id DESC";
            return $this->db_prepare_fetch_all($sql);
        } catch (Exception $e) {
            error_log("Get all products error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all products with category and brand names
     */
    public function get_all_products()
    {
        try {
            $sql = "SELECT p.*,
                           c.cat_name,
                           b.brand_name
                    FROM products p
                    LEFT JOIN categories c ON p.product_cat = c.cat_id
                    LEFT JOIN brands b ON p.product_brand = b.brand_id
                    ORDER BY p.product_id DESC";
            return $this->db_prepare_fetch_all($sql);
        } catch (Exception $e) {
            error_log("Get all products with details error: " . $e->getMessage());
            // Fallback to basic product data
            return $this->get_all_products_basic();
        }
    }

    /**
     * Get product by ID
     */
    public function get_product_by_id($product_id)
    {
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
    public function get_products_by_category($category_id)
    {
        try {
            $sql = "SELECT p.*,
                           c.cat_name,
                           b.brand_name
                    FROM products p
                    LEFT JOIN categories c ON p.product_cat = c.cat_id
                    LEFT JOIN brands b ON p.product_brand = b.brand_id
                    WHERE p.product_cat = ?
                    ORDER BY p.product_id DESC";
            return $this->db_prepare_fetch_all($sql, 'i', [$category_id]);
        } catch (Exception $e) {
            error_log("Get products by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get products by brand
     */
    public function get_products_by_brand($brand_id)
    {
        try {
            $sql = "SELECT p.*,
                           c.cat_name,
                           b.brand_name
                    FROM products p
                    LEFT JOIN categories c ON p.product_cat = c.cat_id
                    LEFT JOIN brands b ON p.product_brand = b.brand_id
                    WHERE p.product_brand = ?
                    ORDER BY p.product_id DESC";
            return $this->db_prepare_fetch_all($sql, 'i', [$brand_id]);
        } catch (Exception $e) {
            error_log("Get products by brand error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update product
     */
    public function update_product($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity)
    {
        try {
            error_log("Product update attempt - ID: $product_id, Title: $product_title, Image: $product_image");

            // If image is empty, don't update it
            if (empty($product_image)) {
                $sql = "UPDATE products SET
                        product_title = ?, product_price = ?, product_desc = ?, product_keywords = ?,
                        product_color = ?, product_cat = ?, product_brand = ?, stock_quantity = ?
                        WHERE product_id = ?";

                error_log("Executing update without image - Parameters: " . json_encode([
                    $product_title, $product_price, $product_desc, $product_keywords,
                    $product_color, $category_id, $brand_id, $stock_quantity, $product_id
                ]));

                $result = $this->db_prepare_execute($sql, 'sdsssiiii', [
                    $product_title,
                    $product_price,
                    $product_desc,
                    $product_keywords,
                    $product_color,
                    $category_id,
                    $brand_id,
                    $stock_quantity,
                    $product_id
                ]);

                error_log("Update result (no image): " . ($result ? 'SUCCESS' : 'FAILED'));
                return $result;
            } else {
                $sql = "UPDATE products SET
                        product_title = ?, product_price = ?, product_desc = ?, product_image = ?,
                        product_keywords = ?, product_color = ?, product_cat = ?, product_brand = ?,
                        stock_quantity = ?
                        WHERE product_id = ?";

                error_log("Executing update with image - Parameters: " . json_encode([
                    $product_title, $product_price, $product_desc, $product_image,
                    $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity, $product_id
                ]));

                $result = $this->db_prepare_execute($sql, 'sdssssiiii', [
                    $product_title,
                    $product_price,
                    $product_desc,
                    $product_image,
                    $product_keywords,
                    $product_color,
                    $category_id,
                    $brand_id,
                    $stock_quantity,
                    $product_id
                ]);

                error_log("Update result (with image): " . ($result ? 'SUCCESS' : 'FAILED'));
                return $result;
            }
        } catch (Exception $e) {
            error_log("Update product error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Delete product (soft delete by default)
     */
    public function delete_product($product_id)
    {
        try {
            if (!$this->db_connect()) {
                error_log("Delete product error: Database connection failed");
                return false;
            }

            $sql = "DELETE FROM products WHERE product_id = ?";
            $result = $this->db_prepare_execute($sql, 'i', [$product_id]);

            if ($result === false) {
                error_log("Delete product error: Failed to execute delete query for product_id: $product_id");
                error_log("MySQL error: " . mysqli_error($this->db));
            }

            return $result;
        } catch (Exception $e) {
            error_log("Delete product error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Force delete product (removes all dependencies)
     */
    public function force_delete_product($product_id)
    {
        try {
            if (!$this->db_connect()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to connect to database'
                ];
            }

            // Start transaction using mysqli
            mysqli_autocommit($this->db, false);
            $error_occurred = false;

            try {
                // Delete from cart first (if exists)
                $sql = "DELETE FROM cart WHERE p_id = ?";
                if (!$this->db_prepare_execute($sql, 'i', [$product_id])) {
                    $error_occurred = true;
                }

                // Delete from order items (if exists)
                if (!$error_occurred) {
                    $sql = "DELETE FROM orderdetails WHERE product_id = ?";
                    if (!$this->db_prepare_execute($sql, 'i', [$product_id])) {
                        $error_occurred = true;
                    }
                }

                // Note: Product images are stored as files on the server, not in a database table
                // So we don't need to delete from a product_images table
                // If you want to delete the actual image files, that would need to be done separately
                // using PHP's unlink() function to delete files from the uploads directory

                // Finally delete the product
                if (!$error_occurred) {
                    $sql = "DELETE FROM products WHERE product_id = ?";
                    $result = $this->db_prepare_execute($sql, 'i', [$product_id]);

                    if ($result) {
                        mysqli_commit($this->db);
                        mysqli_autocommit($this->db, true);
                        return [
                            'status' => 'success',
                            'message' => 'Product and all related data deleted successfully'
                        ];
                    } else {
                        $error_occurred = true;
                    }
                }

                if ($error_occurred) {
                    mysqli_rollback($this->db);
                    mysqli_autocommit($this->db, true);
                    return [
                        'status' => 'error',
                        'message' => 'Failed to delete product'
                    ];
                }
            } catch (Exception $e) {
                mysqli_rollback($this->db);
                mysqli_autocommit($this->db, true);
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
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Search products by title or keywords
     */
    public function search_products($search_term)
    {
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
    public function get_low_stock_products($threshold = 10)
    {
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
    public function get_out_of_stock_products()
    {
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
    public function update_stock_quantity($product_id, $new_quantity)
    {
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
    public function reduce_stock($product_id, $quantity)
    {
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
    public function get_total_inventory_value()
    {
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
    public function view_all_products()
    {
        return $this->get_all_products();
    }

    public function filter_products_by_category($cat_id)
    {
        return $this->get_products_by_category($cat_id);
    }

    public function filter_products_by_brand($brand_id)
    {
        return $this->get_products_by_brand($brand_id);
    }

    public function view_single_product($id)
    {
        return $this->get_product_by_id($id);
    }
}
