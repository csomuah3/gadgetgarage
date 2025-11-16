<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../helpers/image_helper.php';

// Check if user is logged in and is admin
if (!check_login() || !check_admin()) {
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo '<div class="alert alert-danger">Invalid order ID.</div>';
    exit;
}

try {
    // Enhanced query to get order information with customer details
    $order_query = "SELECT o.*, c.customer_name, c.customer_email, c.customer_contact,
                           p.amt as payment_amount, p.currency, p.payment_date, p.payment_method
                    FROM orders o
                    JOIN customer c ON o.customer_id = c.customer_id
                    LEFT JOIN payment p ON o.order_id = p.order_id
                    WHERE o.order_id = ?";

    $db = new db_connection();
    $db->db_connect();
    $order = $db->db_fetch_one($order_query, [$order_id]);

    if (!$order) {
        echo '<div class="alert alert-danger">Order not found.</div>';
        exit;
    }

    // Get order details (products)
    $order_details = get_order_details_ctr($order_id);

    ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Order Information -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Order Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted">Order ID</small>
                                <div class="fw-bold">#<?php echo $order['order_id']; ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Invoice No</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($order['invoice_no']); ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Order Date</small>
                                <div><?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Status</small>
                                <div>
                                    <span class="badge <?php
                                        echo match(strtolower($order['order_status'])) {
                                            'pending' => 'bg-warning text-dark',
                                            'processing' => 'bg-info',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <i class="fas <?php
                                            echo match(strtolower($order['order_status'])) {
                                                'pending' => 'fa-clock',
                                                'processing' => 'fa-cog fa-spin',
                                                'completed' => 'fa-check',
                                                'cancelled' => 'fa-times',
                                                default => 'fa-question'
                                            };
                                        ?>"></i>
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-user"></i> Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="customer-avatar-large">
                                <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div class="text-muted mb-1">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['customer_email']); ?>
                                </div>
                                <?php if ($order['customer_contact']): ?>
                                <div class="text-muted">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_contact']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <?php if ($order['payment_amount']): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-credit-card"></i> Payment Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <small class="text-muted">Amount</small>
                                <div class="h5 text-success fw-bold mb-0">
                                    <?php echo $order['currency']; ?> <?php echo number_format($order['payment_amount'], 2); ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Currency</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($order['currency']); ?></div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Payment Date</small>
                                <div><?php echo $order['payment_date'] ? date('M j, Y g:i A', strtotime($order['payment_date'])) : 'Pending'; ?></div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Payment Method</small>
                                <div class="fw-bold"><?php echo $order['payment_method'] ? htmlspecialchars($order['payment_method']) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Items -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-shopping-bag"></i> Order Items (<?php echo count($order_details); ?>)</h6>
                <span class="badge bg-light text-dark"><?php echo array_sum(array_column($order_details, 'qty')); ?> total items</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">Product</th>
                                <th class="border-0 text-center">Quantity</th>
                                <th class="border-0 text-end">Unit Price</th>
                                <th class="border-0 text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_amount = 0;
                            foreach ($order_details as $item):
                                $subtotal = $item['product_price'] * $item['qty'];
                                $total_amount += $subtotal;
                            ?>
                                <tr>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo get_product_image_url($item['product_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                                 class="product-thumb border rounded"
                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['product_title']); ?></div>
                                                <small class="text-muted">Product ID: #<?php echo $item['product_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge bg-secondary fs-6 px-3 py-2"><?php echo $item['qty']; ?></span>
                                    </td>
                                    <td class="align-middle text-end">
                                        <span class="text-muted">GH₵ <?php echo number_format($item['product_price'], 2); ?></span>
                                    </td>
                                    <td class="align-middle text-end">
                                        <span class="fw-bold text-success">GH₵ <?php echo number_format($subtotal, 2); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="3" class="text-end border-0 py-3">
                                    <span class="h6 text-dark">Total Amount:</span>
                                </th>
                                <th class="text-end border-0 py-3">
                                    <span class="h5 text-success fw-bold">GH₵ <?php echo number_format($total_amount, 2); ?></span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-cogs"></i> Order Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap justify-content-center">
                            <?php if (strtolower($order['order_status']) === 'pending'): ?>
                                <form method="POST" action="../admin/orders.php" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <input type="hidden" name="status" value="processing">
                                    <button type="submit" name="update_status" class="btn btn-info">
                                        <i class="fas fa-cog"></i> Start Processing
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array(strtolower($order['order_status']), ['pending', 'processing'])): ?>
                                <form method="POST" action="../admin/orders.php" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" name="update_status" class="btn btn-success">
                                        <i class="fas fa-check"></i> Mark as Completed
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (strtolower($order['order_status']) !== 'cancelled'): ?>
                                <form method="POST" action="../admin/orders.php" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" name="update_status" class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
                                        <i class="fas fa-times"></i> Cancel Order
                                    </button>
                                </form>
                            <?php endif; ?>

                            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Order
                            </button>

                            <a href="mailto:<?php echo urlencode($order['customer_email']); ?>?subject=Order%20Update%20-%20<?php echo urlencode($order['invoice_no']); ?>"
                               class="btn btn-outline-info">
                                <i class="fas fa-envelope"></i> Email Customer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .customer-avatar-large {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #34d399);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .product-thumb {
            transition: transform 0.2s ease;
        }

        .product-thumb:hover {
            transform: scale(1.1);
        }

        @media print {
            .btn, .card-header {
                display: none !important;
            }

            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
        }
    </style>

    <?php
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            Error loading order details: ' . htmlspecialchars($e->getMessage()) . '
          </div>';
}
?>