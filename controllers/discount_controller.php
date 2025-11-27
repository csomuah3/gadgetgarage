<?php
require_once __DIR__ . '/../classes/discount_class.php';

/**
 * Get all discount codes
 */
function get_all_discounts_ctr()
{
    try {
        $discount = new Discount();
        return $discount->get_all_discounts();
    } catch (Exception $e) {
        error_log("Get all discounts controller error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get discount by ID
 */
function get_discount_by_id_ctr($discount_id)
{
    try {
        $discount = new Discount();
        return $discount->get_discount_by_id($discount_id);
    } catch (Exception $e) {
        error_log("Get discount by ID controller error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get discount by code
 */
function get_discount_by_code_ctr($promo_code)
{
    try {
        $discount = new Discount();
        return $discount->get_discount_by_code($promo_code);
    } catch (Exception $e) {
        error_log("Get discount by code controller error: " . $e->getMessage());
        return false;
    }
}

/**
 * Add new discount code
 */
function add_discount_ctr($promo_code, $promo_description, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $start_date, $end_date, $usage_limit, $is_active)
{
    try {
        // Validate inputs
        if (empty($promo_code)) {
            return ['status' => 'error', 'message' => 'Promo code is required'];
        }

        if (empty($discount_type) || !in_array($discount_type, ['percentage', 'fixed'])) {
            return ['status' => 'error', 'message' => 'Valid discount type is required'];
        }

        if ($discount_value <= 0) {
            return ['status' => 'error', 'message' => 'Discount value must be greater than 0'];
        }

        if ($discount_type === 'percentage' && $discount_value > 100) {
            return ['status' => 'error', 'message' => 'Percentage discount cannot exceed 100%'];
        }

        if ($min_order_amount < 0) {
            return ['status' => 'error', 'message' => 'Minimum order amount cannot be negative'];
        }

        if ($max_discount_amount < 0) {
            return ['status' => 'error', 'message' => 'Maximum discount amount cannot be negative'];
        }

        if ($usage_limit < 0) {
            return ['status' => 'error', 'message' => 'Usage limit cannot be negative'];
        }

        // Validate dates
        if (!empty($start_date) && !empty($end_date)) {
            if (strtotime($start_date) > strtotime($end_date)) {
                return ['status' => 'error', 'message' => 'End date must be after start date'];
            }
        }

        $discount = new Discount();
        return $discount->add_discount(
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
        );
    } catch (Exception $e) {
        error_log("Add discount controller error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Controller error: ' . $e->getMessage()];
    }
}

/**
 * Update discount code
 */
function update_discount_ctr($promo_id, $promo_code, $promo_description, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $start_date, $end_date, $usage_limit, $is_active)
{
    try {
        // Validate inputs
        if (empty($promo_code)) {
            return ['status' => 'error', 'message' => 'Promo code is required'];
        }

        if (empty($discount_type) || !in_array($discount_type, ['percentage', 'fixed'])) {
            return ['status' => 'error', 'message' => 'Valid discount type is required'];
        }

        if ($discount_value <= 0) {
            return ['status' => 'error', 'message' => 'Discount value must be greater than 0'];
        }

        if ($discount_type === 'percentage' && $discount_value > 100) {
            return ['status' => 'error', 'message' => 'Percentage discount cannot exceed 100%'];
        }

        if ($min_order_amount < 0) {
            return ['status' => 'error', 'message' => 'Minimum order amount cannot be negative'];
        }

        if ($max_discount_amount < 0) {
            return ['status' => 'error', 'message' => 'Maximum discount amount cannot be negative'];
        }

        if ($usage_limit < 0) {
            return ['status' => 'error', 'message' => 'Usage limit cannot be negative'];
        }

        // Validate dates
        if (!empty($start_date) && !empty($end_date)) {
            if (strtotime($start_date) > strtotime($end_date)) {
                return ['status' => 'error', 'message' => 'End date must be after start date'];
            }
        }

        $discount = new Discount();
        return $discount->update_discount(
            $promo_id,
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
        );
    } catch (Exception $e) {
        error_log("Update discount controller error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Controller error: ' . $e->getMessage()];
    }
}

/**
 * Delete discount code
 */
function delete_discount_ctr($promo_id)
{
    try {
        if (empty($promo_id) || !is_numeric($promo_id)) {
            return ['status' => 'error', 'message' => 'Valid discount ID is required'];
        }

        $discount = new Discount();
        return $discount->delete_discount($promo_id);
    } catch (Exception $e) {
        error_log("Delete discount controller error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Controller error: ' . $e->getMessage()];
    }
}

/**
 * Toggle discount active status
 */
function toggle_discount_status_ctr($promo_id)
{
    try {
        if (empty($promo_id) || !is_numeric($promo_id)) {
            return ['status' => 'error', 'message' => 'Valid discount ID is required'];
        }

        $discount = new Discount();
        return $discount->toggle_discount_status($promo_id);
    } catch (Exception $e) {
        error_log("Toggle discount status controller error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Controller error: ' . $e->getMessage()];
    }
}

/**
 * Get active discount codes
 */
function get_active_discounts_ctr()
{
    try {
        $discount = new Discount();
        return $discount->get_active_discounts();
    } catch (Exception $e) {
        error_log("Get active discounts controller error: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate discount code for use
 */
function validate_discount_code_ctr($promo_code, $order_amount = 0)
{
    try {
        $discount = new Discount();
        return $discount->validate_discount_code($promo_code, $order_amount);
    } catch (Exception $e) {
        error_log("Validate discount code controller error: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Error validating discount code'];
    }
}
?>