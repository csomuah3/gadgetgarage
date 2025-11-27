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

    public function record_payment($customer_id, $order_id, $amount, $currency = 'GHS', $payment_method = 'paystack', $transaction_ref = null, $authorization_code = null, $payment_channel = null)
    {
        $payment_date = date('Y-m-d');
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);
        $order_id = mysqli_real_escape_string($this->db, $order_id);
        $amount = mysqli_real_escape_string($this->db, $amount);
        $currency = mysqli_real_escape_string($this->db, $currency);
        $payment_date = mysqli_real_escape_string($this->db, $payment_date);
        $payment_method = mysqli_real_escape_string($this->db, $payment_method);

        // Build SQL dynamically based on provided parameters
        $columns = ['amt', 'customer_id', 'order_id', 'currency', 'payment_date', 'payment_method'];
        $values = [$amount, $customer_id, $order_id, "'$currency'", "'$payment_date'", "'$payment_method'"];

        if ($transaction_ref !== null) {
            $columns[] = 'transaction_ref';
            $values[] = "'" . mysqli_real_escape_string($this->db, $transaction_ref) . "'";
        }

        if ($authorization_code !== null) {
            $columns[] = 'authorization_code';
            $values[] = "'" . mysqli_real_escape_string($this->db, $authorization_code) . "'";
        }

        if ($payment_channel !== null) {
            $columns[] = 'payment_channel';
            $values[] = "'" . mysqli_real_escape_string($this->db, $payment_channel) . "'";
        }

        $sql = "INSERT INTO payment (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";

        if ($this->db_write_query($sql)) {
            return $this->get_last_insert_id();
        }
        return false;
    }

    public function get_user_orders($customer_id)
    {
        $customer_id = mysqli_real_escape_string($this->db, $customer_id);

        $sql = "SELECT o.order_id,
                       o.customer_id,
                       o.invoice_no,
                       o.order_date,
                       o.order_status,
                       o.tracking_number,
                       COUNT(DISTINCT od.product_id) as item_count,
                       COALESCE(SUM(p.amt),
                           COALESCE(SUM(od.qty * pr.product_price), 0)
                       ) as total_amount,
                       GROUP_CONCAT(DISTINCT p.payment_method) as payment_method,
                       GROUP_CONCAT(DISTINCT p.currency) as currency
                FROM orders o
                LEFT JOIN orderdetails od ON o.order_id = od.order_id
                LEFT JOIN products pr ON od.product_id = pr.product_id
                LEFT JOIN payment p ON o.order_id = p.order_id
                WHERE o.customer_id = $customer_id
                GROUP BY o.order_id
                ORDER BY o.order_date DESC";

        try {
            $result = $this->db_fetch_all($sql);
            return $result ? $result : [];
        } catch (Exception $e) {
            error_log("Error fetching user orders: " . $e->getMessage());
            return [];
        }
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

    private function reduce_product_stock($product_id, $quantity)
    {
        $product_id = mysqli_real_escape_string($this->db, $product_id);
        $quantity = mysqli_real_escape_string($this->db, $quantity);

        // First check current stock to prevent negative stock
        $check_sql = "SELECT stock_quantity FROM products WHERE product_id = $product_id";
        $current_stock = $this->db_fetch_one($check_sql);

        if (!$current_stock || $current_stock['stock_quantity'] < $quantity) {
            return false; // Not enough stock available
        }

        // Reduce stock quantity
        $sql = "UPDATE products SET stock_quantity = stock_quantity - $quantity WHERE product_id = $product_id";
        return $this->db_write_query($sql);
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

        // Add order details for each cart item and reduce stock
        foreach ($cart_items as $item) {
            if (!$this->add_order_details($order_id, $item['p_id'], $item['qty'])) {
                return false;
            }

            // Reduce stock quantity for the product
            if (!$this->reduce_product_stock($item['p_id'], $item['qty'])) {
                return false;
            }
        }

        return [
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'order_reference' => $this->generate_order_reference()
        ];
    }

    public function process_cart_to_order_without_payment($customer_id, $ip_address = null)
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

        // Add order details for each cart item and reduce stock
        foreach ($cart_items as $item) {
            if (!$this->add_order_details($order_id, $item['p_id'], $item['qty'])) {
                return false;
            }

            // Reduce stock quantity for the product
            if (!$this->reduce_product_stock($item['p_id'], $item['qty'])) {
                return false;
            }
        }

        return [
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'order_reference' => $this->generate_order_reference()
        ];
    }

    public function get_all_orders()
    {
        $sql = "SELECT o.order_id, o.customer_id, o.invoice_no, o.order_date, o.order_status,
                       o.tracking_number,
                       c.customer_name as customer_name,
                       c.customer_email as customer_email,
                       c.customer_contact as customer_contact,
                       COUNT(DISTINCT od.product_id) as item_count,
                       SUM(od.qty) as total_items,
                       COALESCE(SUM(p.amt),
                           COALESCE(SUM(od.qty * pr.product_price), 0)
                       ) as total_amount,
                       COALESCE(MAX(p.currency), 'GHS') as currency,
                       MAX(p.payment_date) as payment_date
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN orderdetails od ON o.order_id = od.order_id
                LEFT JOIN products pr ON od.product_id = pr.product_id
                LEFT JOIN payment p ON o.order_id = p.order_id
                GROUP BY o.order_id, o.customer_id, o.invoice_no, o.order_date, o.order_status,
                         o.tracking_number, c.customer_name, c.customer_email, c.customer_contact
                ORDER BY o.order_date DESC";

        return $this->db_fetch_all($sql);
    }

    public function get_order_tracking_details($search_value)
    {
        $search_value = mysqli_real_escape_string($this->db, $search_value);

        // First, try to find the order by order_id or tracking_number
        $order_sql = "SELECT o.*,
                             COALESCE(p.amt, 0) as total_amount,
                             COUNT(od.product_id) as item_count
                      FROM orders o
                      LEFT JOIN payment p ON o.order_id = p.order_id
                      LEFT JOIN orderdetails od ON o.order_id = od.order_id
                      WHERE o.order_id = '$search_value'
                         OR o.tracking_number = '$search_value'
                         OR o.invoice_no = '$search_value'
                      GROUP BY o.order_id";

        $order = $this->db_fetch_one($order_sql);

        if (!$order) {
            return null;
        }

        // Get tracking history
        $tracking_sql = "SELECT * FROM order_tracking
                         WHERE order_id = '{$order['order_id']}'
                         ORDER BY status_date ASC";

        $tracking = $this->db_fetch_all($tracking_sql);

        return [
            'order' => $order,
            'tracking' => $tracking
        ];
    }

    public function update_order_tracking($order_id, $status, $notes = null, $location = null, $updated_by = null)
    {
        $order_id = mysqli_real_escape_string($this->db, $order_id);
        $status = mysqli_real_escape_string($this->db, $status);
        $notes = $notes ? "'" . mysqli_real_escape_string($this->db, $notes) . "'" : 'NULL';
        $location = $location ? "'" . mysqli_real_escape_string($this->db, $location) . "'" : 'NULL';
        $updated_by = $updated_by ? mysqli_real_escape_string($this->db, $updated_by) : 'NULL';

        // Update order status
        $update_order_sql = "UPDATE orders SET order_status = '$status' WHERE order_id = $order_id";
        $this->db_write_query($update_order_sql);

        // Add tracking entry
        $tracking_sql = "INSERT INTO order_tracking (order_id, status, notes, location, updated_by)
                         VALUES ($order_id, '$status', $notes, $location, $updated_by)";

        return $this->db_write_query($tracking_sql);
    }

    public function generate_tracking_number()
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return 'GG' . $year . $month . $day . $random;
    }
}
?>