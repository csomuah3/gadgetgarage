<?php
session_start();
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../helpers/image_helper.php';

if (!check_login()) {
    echo '<div class="alert alert-danger">Please log in to view order details.</div>';
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo '<div class="alert alert-danger">Invalid order ID.</div>';
    exit;
}

try {
    // Get order information
    $order = get_order_by_id_ctr($order_id);

    if (!$order) {
        echo '<div class="alert alert-danger">Order not found.</div>';
        exit;
    }

    // Verify this order belongs to the logged-in user
    if ($order['customer_id'] != $_SESSION['customer_id']) {
        echo '<div class="alert alert-danger">Access denied.</div>';
        exit;
    }

    // Get order details (products)
    $order_details = get_order_details_ctr($order_id);

    ?>
    <div class="row">
        <div class="col-md-6">
            <h6 class="fw-bold text-primary">Order Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Order ID:</strong></td>
                    <td>#<?php echo $order['order_id']; ?></td>
                </tr>
                <tr>
                    <td><strong>Invoice No:</strong></td>
                    <td><?php echo htmlspecialchars($order['invoice_no']); ?></td>
                </tr>
                <tr>
                    <td><strong>Order Date:</strong></td>
                    <td><?php echo date('F j, Y', strtotime($order['order_date'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <span class="badge bg-<?php
                            echo match(strtolower($order['order_status'])) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold text-primary">Payment Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Payment Amount:</strong></td>
                    <td>$<?php echo number_format($order['payment_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Currency:</strong></td>
                    <td><?php echo htmlspecialchars($order['currency']); ?></td>
                </tr>
                <tr>
                    <td><strong>Payment Date:</strong></td>
                    <td><?php echo $order['payment_date'] ? date('F j, Y', strtotime($order['payment_date'])) : 'N/A'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <hr>

    <h6 class="fw-bold text-primary mb-3">Order Items</h6>
    <div class="table-responsive">
        <table class="table">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_details as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo get_product_image_url($item['product_image']); ?>"
                                     alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"
                                     class="me-3">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($item['product_title']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>$<?php echo number_format($item['product_price'], 2); ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo $item['qty']; ?></span>
                        </td>
                        <td class="fw-bold">$<?php echo number_format($item['product_price'] * $item['qty'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">Total:</th>
                    <th>$<?php echo number_format($order['payment_amount'], 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading order details: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>