<?php
require_once __DIR__ . '/../settings/db_class.php';

class Order extends db_connection
{
    public function __construct()
    {
        $this->db_connect();
    }

    public function create_order($customer_id, $invoice_no = null, $order_status = 'pending')
    {
        if (!$invoice_no) {
            $invoice_no = $this->generate_invoice_number();
        }

        $order_date = date('Y-m-d');
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);
        $invoice_no = mysqli_real_escape_string($this->db, $invoice_no);
        $order_date = mysqli_real_escape_string($this->db, $order_date);
        $order_status = mysqli_real_escape_string($this->db, $order_status);

        $sql = "INSERT INTO orders (customer_id, invoice_no, order_date, order_status) VALUES ($customer_id, '$invoice_no', '$order_date', '$order_status')";

        if ($this->db_write_query($sql)) {
            return $this->get_last_insert_id();
        }
        return false;
    }

    public function add_order_details($order_id, $product_id, $quantity)
    {
        $order_id = mysqli_real_escape_string($this->db, $order_id);
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $quantity = mysqli_real_escape_string($this->db, $quantity);

        $sql = "INSERT INTO orderdetails (order_id, product_id, qty) VALUES ($order_id, $product_id, $quantity)";
        return $this->db_write_query($sql);
    }

    public function record_payment($customer_id, $order_id, $amount, $currency = 'USD')
    {
        $payment_date = date('Y-m-d');
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);
        $order_id = mysqli_real_escape_string($this->db, $order_id);
        $amount = mysqli_real_escape_string($this->db, $amount);
        $currency = mysqli_real_escape_string($this->db, $currency);
        $payment_date = mysqli_real_escape_string($this->db, $payment_date);

        $sql = "INSERT INTO payment (amt, customer_id, order_id, currency, payment_date) VALUES ($amount, $customer_id, $order_id, '$currency', '$payment_date')";
        return $this->db_write_query($sql);
    }

    public function get_user_orders($customer_id)
    {
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);

        $sql = "SELECT o.*, COUNT(od.product_id) as item_count, SUM(p.amt) as total_amount
                FROM orders o
                LEFT JOIN orderdetails od ON o.order_id = od.order_id
                LEFT JOIN payment p ON o.order_id = p.order_id
                WHERE o.customer_id = $customer_id
                GROUP BY o.order_id
                ORDER BY o.order_date DESC";

        return $this->db_fetch_all($sql);
    }

    public function get_order_details($order_id)
    {
        $order_id = mysqli_real_escape_string($this->db, $order_id);

        $sql = "SELECT od.*, p.product_title, p.product_price, p.product_image
                FROM orderdetails od
                JOIN products p ON od.product_id = p.product_id
                WHERE od.order_id = $order_id";

        return $this->db_fetch_all($sql);
    }

    public function get_order_by_id($order_id)
    {
        $order_id = mysqli_real_escape_string($this->db, $order_id);

        $sql = "SELECT o.*, py.amt as payment_amount, py.currency, py.payment_date
                FROM orders o
                LEFT JOIN payment py ON o.order_id = py.order_id
                WHERE o.order_id = $order_id";

        return $this->db_fetch_one($sql);
    }

    public function update_order_status($order_id, $status)
    {
        $order_id = mysqli_real_escape_string($this->db, $order_id);
        $status = mysqli_real_escape_string($this->db, $status);

        $sql = "UPDATE orders SET order_status = '$status' WHERE order_id = $order_id";
        return $this->db_write_query($sql);
    }

    public function generate_invoice_number()
    {
        // Generate a simple 6-7 digit invoice number to ensure it fits in any integer column
        // Format: HHMMSSRR (hour, minute, second, 2-digit random)
        // This will generate numbers like: 14532301 (max 8 digits)
        return date('His') . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
    }

    public function generate_order_reference()
    {
        return 'ORD' . date('YmdHis') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    private function get_last_insert_id()
    {
        return mysqli_insert_id($this->db);
    }

    public function process_cart_to_order($customer_id, $ip_address = null)
    {
        // Get cart items
        if ($customer_id) {
            $customer_id = mysqli_real_escape_string($this->db, $customer_id);
            $cart_items_sql = "SELECT c.*, p.product_price FROM cart c JOIN products p ON c.p_id = p.product_id WHERE c.c_id = $customer_id";
        } else {
            $ip_address = mysqli_real_escape_string($this->db, $ip_address);
            $cart_items_sql = "SELECT c.*, p.product_price FROM cart c JOIN products p ON c.p_id = p.product_id WHERE c.ip_add = '$ip_address'";
        }

        $cart_items = $this->db_fetch_all($cart_items_sql);

        if (!$cart_items) {
            return false;
        }

        // Calculate total amount
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += $item['qty'] * $item['product_price'];
        }

        // Create order
        $order_id = $this->create_order($customer_id);
        if (!$order_id) {
            return false;
        }

        // Add order details for each cart item
        foreach ($cart_items as $item) {
            if (!$this->add_order_details($order_id, $item['p_id'], $item['qty'])) {
                return false;
            }
        }

        // Record payment
        if (!$this->record_payment($customer_id, $order_id, $total_amount)) {
            return false;
        }

        // Update order status
        $this->update_order_status($order_id, 'completed');

        return [
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'order_reference' => $this->generate_order_reference()
        ];
    }
}
?>