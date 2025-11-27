<?php
require_once __DIR__ . '/../settings/db_class.php';

class Discount extends db_connection
{
    /**
     * Get all discount codes
     */
    public function get_all_discounts()
    {
        try {
            $sql = "SELECT * FROM discount_codes ORDER BY created_at DESC";
            return $this->db_prepare_fetch_all($sql);
        } catch (Exception $e) {
            error_log("Get all discounts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get discount by ID
     */
    public function get_discount_by_id($discount_id)
    {
        try {
            $sql = "SELECT * FROM discount_codes WHERE promo_id = ?";
            return $this->db_prepare_fetch_one($sql, 'i', [$discount_id]);
        } catch (Exception $e) {
            error_log("Get discount by ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get discount by code
     */
    public function get_discount_by_code($promo_code)
    {
        try {
            $sql = "SELECT * FROM discount_codes WHERE promo_code = ?";
            return $this->db_prepare_fetch_one($sql, 's', [$promo_code]);
        } catch (Exception $e) {
            error_log("Get discount by code error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add new discount code
     */
    public function add_discount($promo_code, $promo_description, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $start_date, $end_date, $usage_limit, $is_active)
    {
        try {
            if (!$this->db_connect()) {
                error_log("Add discount error: Database connection failed");
                return false;
            }

            // Check if promo code already exists
            $existing = $this->get_discount_by_code($promo_code);
            if ($existing) {
                return ['status' => 'error', 'message' => 'Promo code already exists'];
            }

            $sql = "INSERT INTO discount_codes (promo_code, promo_description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())";

            $result = $this->db_prepare_execute($sql, 'sssdddssii', [
                $promo_code,
                $promo_description,
                $discount_type,
                $discount_value,
                $min_order_amount,
                $max_discount_amount,
                $start_date,
                $end_date,
                $usage_limit,
                $is_active
            ]);

            if ($result) {
                return ['status' => 'success', 'message' => 'Discount code created successfully'];
            } else {
                error_log("Add discount error: Failed to execute insert query");
                return ['status' => 'error', 'message' => 'Failed to create discount code'];
            }
        } catch (Exception $e) {
            error_log("Add discount error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Update discount code
     */
    public function update_discount($promo_id, $promo_code, $promo_description, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $start_date, $end_date, $usage_limit, $is_active)
    {
        try {
            if (!$this->db_connect()) {
                error_log("Update discount error: Database connection failed");
                return false;
            }

            // Check if promo code exists for other records
            $sql_check = "SELECT promo_id FROM discount_codes WHERE promo_code = ? AND promo_id != ?";
            $existing = $this->db_prepare_fetch_one($sql_check, 'si', [$promo_code, $promo_id]);
            if ($existing) {
                return ['status' => 'error', 'message' => 'Promo code already exists'];
            }

            $sql = "UPDATE discount_codes SET
                    promo_code = ?,
                    promo_description = ?,
                    discount_type = ?,
                    discount_value = ?,
                    min_order_amount = ?,
                    max_discount_amount = ?,
                    start_date = ?,
                    end_date = ?,
                    usage_limit = ?,
                    is_active = ?,
                    updated_at = NOW()
                    WHERE promo_id = ?";

            $result = $this->db_prepare_execute($sql, 'sssdddssiii', [
                $promo_code,
                $promo_description,
                $discount_type,
                $discount_value,
                $min_order_amount,
                $max_discount_amount,
                $start_date,
                $end_date,
                $usage_limit,
                $is_active,
                $promo_id
            ]);

            if ($result) {
                return ['status' => 'success', 'message' => 'Discount code updated successfully'];
            } else {
                error_log("Update discount error: Failed to execute update query");
                return ['status' => 'error', 'message' => 'Failed to update discount code'];
            }
        } catch (Exception $e) {
            error_log("Update discount error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete discount code
     */
    public function delete_discount($promo_id)
    {
        try {
            if (!$this->db_connect()) {
                error_log("Delete discount error: Database connection failed");
                return false;
            }

            $sql = "DELETE FROM discount_codes WHERE promo_id = ?";
            $result = $this->db_prepare_execute($sql, 'i', [$promo_id]);

            if ($result) {
                return ['status' => 'success', 'message' => 'Discount code deleted successfully'];
            } else {
                error_log("Delete discount error: Failed to execute delete query");
                return ['status' => 'error', 'message' => 'Failed to delete discount code'];
            }
        } catch (Exception $e) {
            error_log("Delete discount error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle discount active status
     */
    public function toggle_discount_status($promo_id)
    {
        try {
            if (!$this->db_connect()) {
                error_log("Toggle discount status error: Database connection failed");
                return false;
            }

            $sql = "UPDATE discount_codes SET is_active = NOT is_active, updated_at = NOW() WHERE promo_id = ?";
            $result = $this->db_prepare_execute($sql, 'i', [$promo_id]);

            if ($result) {
                return ['status' => 'success', 'message' => 'Discount status updated successfully'];
            } else {
                error_log("Toggle discount status error: Failed to execute update query");
                return ['status' => 'error', 'message' => 'Failed to update discount status'];
            }
        } catch (Exception $e) {
            error_log("Toggle discount status error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get active discount codes
     */
    public function get_active_discounts()
    {
        try {
            $sql = "SELECT * FROM discount_codes WHERE is_active = 1 AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) ORDER BY created_at DESC";
            return $this->db_prepare_fetch_all($sql);
        } catch (Exception $e) {
            error_log("Get active discounts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate discount code for use
     */
    public function validate_discount_code($promo_code, $order_amount = 0)
    {
        try {
            $discount = $this->get_discount_by_code($promo_code);

            if (!$discount) {
                return ['valid' => false, 'message' => 'Invalid discount code'];
            }

            if (!$discount['is_active']) {
                return ['valid' => false, 'message' => 'Discount code is not active'];
            }

            // Check date validity
            $now = date('Y-m-d H:i:s');
            if ($discount['start_date'] && $discount['start_date'] > $now) {
                return ['valid' => false, 'message' => 'Discount code is not yet active'];
            }

            if ($discount['end_date'] && $discount['end_date'] < $now) {
                return ['valid' => false, 'message' => 'Discount code has expired'];
            }

            // Check usage limit
            if ($discount['usage_limit'] && $discount['used_count'] >= $discount['usage_limit']) {
                return ['valid' => false, 'message' => 'Discount code usage limit reached'];
            }

            // Check minimum order amount
            if ($discount['min_order_amount'] && $order_amount < $discount['min_order_amount']) {
                return ['valid' => false, 'message' => 'Minimum order amount not met'];
            }

            return ['valid' => true, 'discount' => $discount];
        } catch (Exception $e) {
            error_log("Validate discount code error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error validating discount code'];
        }
    }
}
?>