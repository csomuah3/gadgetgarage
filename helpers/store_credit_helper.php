<?php
require_once(__DIR__ . '/../settings/db_class.php');

class StoreCreditHelper {

    private $db;

    public function __construct() {
        $this->db = new db_connection();
        $this->db->db_connect();
    }

    /**
     * Get available store credits for a customer
     */
    public function getAvailableCredits($customer_id) {
        if (!$customer_id) {
            error_log("StoreCreditHelper: No customer_id provided");
            return [];
        }

        $customer_id = intval($customer_id);
        error_log("StoreCreditHelper: Fetching credits for customer_id: $customer_id");

        // First, let's check all credits for this customer (for debugging)
        $all_sql = "SELECT * FROM store_credits WHERE customer_id = $customer_id";
        $all_credits = $this->db->db_fetch_all($all_sql) ?: [];
        error_log("StoreCreditHelper: Total credits found (all statuses): " . count($all_credits));
        if (!empty($all_credits)) {
            error_log("StoreCreditHelper: All credits data: " . print_r($all_credits, true));
        }

        $sql = "SELECT
                    credit_id,
                    credit_amount as amount,
                    (credit_amount - IFNULL(remaining_amount, credit_amount)) as used_amount,
                    remaining_amount as available_amount,
                    source as source_type,
                    device_drop_id as source_reference,
                    admin_notes as description,
                    created_at,
                    expires_at,
                    status
                FROM store_credits
                WHERE customer_id = $customer_id
                AND status = 'active'
                AND (expires_at IS NULL OR expires_at > NOW())
                AND remaining_amount > 0
                ORDER BY expires_at ASC, created_at ASC";

        $result = $this->db->db_fetch_all($sql) ?: [];
        error_log("StoreCreditHelper: Available credits found: " . count($result));
        if (!empty($result)) {
            error_log("StoreCreditHelper: Available credits data: " . print_r($result, true));
        } else {
            error_log("StoreCreditHelper: No available credits found. Query: $sql");
        }

        return $result;
    }

    /**
     * Get total available credit amount for a customer
     */
    public function getTotalAvailableCredit($customer_id) {
        $credits = $this->getAvailableCredits($customer_id);
        $total = 0;

        foreach ($credits as $credit) {
            $total += floatval($credit['available_amount']);
        }

        return $total;
    }

    /**
     * Apply store credits to an order
     * Returns array with applied amount and remaining credits
     */
    public function applyCreditsToOrder($customer_id, $order_total, $order_id = null) {
        $credits = $this->getAvailableCredits($customer_id);
        $remaining_total = floatval($order_total);
        $applied_amount = 0;
        $applied_credits = [];

        foreach ($credits as $credit) {
            if ($remaining_total <= 0) {
                break;
            }

            $available = floatval($credit['available_amount']);
            $to_use = min($available, $remaining_total);

            if ($to_use > 0) {
                // Update remaining amount (decrease it by the amount used)
                $current_remaining = floatval($credit['available_amount']);
                $new_remaining = max(0, $current_remaining - $to_use);
                $credit_id = intval($credit['credit_id']);

                $update_sql = "UPDATE store_credits
                              SET remaining_amount = $new_remaining,
                                  created_at = created_at
                              WHERE credit_id = $credit_id";

                if ($this->db->db_write_query($update_sql)) {
                    $applied_amount += $to_use;
                    $remaining_total -= $to_use;

                    $applied_credits[] = [
                        'credit_id' => $credit_id,
                        'amount_used' => $to_use,
                        'source_reference' => $credit['source_reference'],
                        'description' => $credit['description']
                    ];

                    // Note: Credit usage is tracked by remaining_amount decrease
                    // Detailed logging can be added to a separate audit table if needed
                }
            }
        }

        return [
            'applied_amount' => $applied_amount,
            'remaining_total' => max(0, $remaining_total),
            'applied_credits' => $applied_credits
        ];
    }

    /**
     * Log credit usage for tracking
     */
    private function logCreditUsage($credit_id, $order_id, $amount) {
        $credit_id = intval($credit_id);
        $amount = floatval($amount);
        $order_id_sql = $order_id ? "'$order_id'" : 'NULL';

        $log_sql = "INSERT INTO store_credit_usage (
                        credit_id, order_id, amount_used, used_at
                    ) VALUES (
                        $credit_id, $order_id_sql, $amount, NOW()
                    )";

        $this->db->db_write_query($log_sql);
    }

    /**
     * Preview credit application without actually applying
     */
    public function previewCreditApplication($customer_id, $order_total) {
        $credits = $this->getAvailableCredits($customer_id);
        $remaining_total = floatval($order_total);
        $total_applicable = 0;
        $credit_breakdown = [];

        foreach ($credits as $credit) {
            if ($remaining_total <= 0) {
                break;
            }

            $available = floatval($credit['available_amount']);
            $to_use = min($available, $remaining_total);

            if ($to_use > 0) {
                $total_applicable += $to_use;
                $remaining_total -= $to_use;

                $credit_breakdown[] = [
                    'source_reference' => $credit['source_reference'],
                    'description' => $credit['description'],
                    'available_amount' => $available,
                    'amount_to_use' => $to_use,
                    'expires_at' => $credit['expires_at']
                ];
            }
        }

        return [
            'total_applicable' => $total_applicable,
            'final_total' => max(0, floatval($order_total) - $total_applicable),
            'credit_breakdown' => $credit_breakdown
        ];
    }

    /**
     * Create a new store credit entry
     */
    public function createStoreCredit($customer_id, $amount, $source_type, $source_reference, $description, $expires_in_days = 365) {
        $customer_id = intval($customer_id);
        $amount = floatval($amount);
        $source_type = mysqli_real_escape_string($this->db->db_conn(), $source_type);
        $source_reference = mysqli_real_escape_string($this->db->db_conn(), $source_reference);
        $description = mysqli_real_escape_string($this->db->db_conn(), $description);
        $expires_in_days = intval($expires_in_days);

        $sql = "INSERT INTO store_credits (
                    customer_id, credit_amount, remaining_amount, source,
                    admin_notes, expires_at, created_at, status, admin_verified, verified_at
                ) VALUES (
                    $customer_id, $amount, $amount, '$source_type',
                    '$description', DATE_ADD(NOW(), INTERVAL $expires_in_days DAY), NOW(), 'active', 1, NOW()
                )";

        if ($this->db->db_write_query($sql)) {
            return mysqli_insert_id($this->db->db_conn());
        }

        return false;
    }
}
?>