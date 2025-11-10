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
        $sql = "INSERT INTO orders (customer_id, invoice_no, order_date, order_status) VALUES (?, ?, ?, ?)";
        $params = [$customer_id, $invoice_no, $order_date, $order_status];

        if ($this->db_query($sql, $params)) {
            return $this->get_last_insert_id();
        }
        return false;
    }

    public function add_order_details($order_id, $product_id, $quantity)
    {
        $sql = "INSERT INTO orderdetails (order_id, product_id, qty) VALUES (?, ?, ?)";
        $params = [$order_id, $product_id, $quantity];
        return $this->db_query($sql, $params);
    }

    public function record_payment($customer_id, $order_id, $amount, $currency = 'USD')
    {
        $payment_date = date('Y-m-d');
        $sql = "INSERT INTO payment (amt, customer_id, order_id, currency, payment_date) VALUES (?, ?, ?, ?, ?)";
        $params = [$amount, $customer_id, $order_id, $currency, $payment_date];
        return $this->db_query($sql, $params);
    }

    public function get_user_orders($customer_id)
    {
        $sql = "SELECT o.*, COUNT(od.product_id) as item_count, SUM(p.amt) as total_amount
                FROM orders o
                LEFT JOIN orderdetails od ON o.order_id = od.order_id
                LEFT JOIN payment p ON o.order_id = p.order_id
                WHERE o.customer_id = ?
                GROUP BY o.order_id
                ORDER BY o.order_date DESC";
        $params = [$customer_id];
        return $this->db_fetch_all($sql, $params);
    }

    public function get_order_details($order_id)
    {
        $sql = "SELECT od.*, p.product_title, p.product_price, p.product_image
                FROM orderdetails od
                JOIN products p ON od.product_id = p.product_id
                WHERE od.order_id = ?";
        $params = [$order_id];
        return $this->db_fetch_all($sql, $params);
    }

    public function get_order_by_id($order_id)
    {
        $sql = "SELECT o.*, py.amt as payment_amount, py.currency, py.payment_date
                FROM orders o
                LEFT JOIN payment py ON o.order_id = py.order_id
                WHERE o.order_id = ?";
        $params = [$order_id];
        return $this->db_fetch_one($sql, $params);
    }

    public function update_order_status($order_id, $status)
    {
        $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
        $params = [$status, $order_id];
        return $this->db_query($sql, $params);
    }

    public function generate_invoice_number()
    {
        return 'INV' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
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
        $cart_items_sql = $customer_id ?
            "SELECT c.*, p.product_price FROM cart c JOIN products p ON c.p_id = p.product_id WHERE c.c_id = ?" :
            "SELECT c.*, p.product_price FROM cart c JOIN products p ON c.p_id = p.product_id WHERE c.ip_add = ?";

        $params = $customer_id ? [$customer_id] : [$ip_address];
        $cart_items = $this->db_fetch_all($cart_items_sql, $params);

        if (!$cart_items) {
            return false;
        }

        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += $item['qty'] * $item['product_price'];
        }

        $order_id = $this->create_order($customer_id);
        if (!$order_id) {
            return false;
        }

        foreach ($cart_items as $item) {
            if (!$this->add_order_details($order_id, $item['p_id'], $item['qty'])) {
                return false;
            }
        }

        if (!$this->record_payment($customer_id, $order_id, $total_amount)) {
            return false;
        }

        $this->update_order_status($order_id, 'completed');

        return [
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'order_reference' => $this->generate_order_reference()
        ];
    }
}
?>