<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

if (!check_login()) {
    header("Location: ../login/user_login.php");
    exit;
}

if (!check_admin()) {
    header("Location: ../index.php");
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];

    if (update_order_status_ctr($order_id, $new_status)) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Failed to update order status.";
    }
}

// Get all orders
try {
    $orders_query = "SELECT o.*, c.customer_name, c.customer_email,
                           COUNT(od.product_id) as item_count,
                           SUM(p.amt) as total_amount
                    FROM orders o
                    JOIN customer c ON o.customer_id = c.customer_id
                    LEFT JOIN orderdetails od ON o.order_id = od.order_id
                    LEFT JOIN payment p ON o.order_id = p.order_id
                    GROUP BY o.order_id
                    ORDER BY o.order_date DESC";

    $db = new db_connection();
    $db->db_connect();
    $orders = $db->db_fetch_all($orders_query);
} catch (Exception $e) {
    $error_message = "Error loading orders: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-dark min-vh-100">
                <div class="text-white p-3">
                    <h4>Admin Panel</h4>
                    <nav class="nav flex-column">
                        <a class="nav-link text-white" href="../index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                        <a class="nav-link text-white" href="product.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <a class="nav-link text-white" href="category.php">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                        <a class="nav-link text-white" href="brand.php">
                            <i class="fas fa-trademark"></i> Brands
                        </a>
                        <a class="nav-link text-white active" href="orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>Order Management</h1>
                        <a href="../login/logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Invoice No</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($orders)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                    No orders found
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['order_id']; ?></td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($order['invoice_no']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        switch(strtolower($order['order_status'])) {
                                                            case 'pending': $status_class = 'bg-warning'; break;
                                                            case 'processing': $status_class = 'bg-info'; break;
                                                            case 'completed': $status_class = 'bg-success'; break;
                                                            case 'cancelled': $status_class = 'bg-danger'; break;
                                                            default: $status_class = 'bg-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucfirst($order['order_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                    onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                                                                    data-bs-toggle="dropdown">
                                                                <span class="visually-hidden">Toggle Dropdown</span>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                        <input type="hidden" name="status" value="processing">
                                                                        <button type="submit" name="update_status" class="dropdown-item">
                                                                            <i class="fas fa-cog text-info"></i> Set Processing
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                                <li>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                        <input type="hidden" name="status" value="completed">
                                                                        <button type="submit" name="update_status" class="dropdown-item">
                                                                            <i class="fas fa-check text-success"></i> Mark Completed
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                                <li>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                        <input type="hidden" name="status" value="cancelled">
                                                                        <button type="submit" name="update_status" class="dropdown-item">
                                                                            <i class="fas fa-times text-danger"></i> Cancel Order
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrderDetails(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            const content = document.getElementById('orderDetailsContent');

            content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...</div>';
            modal.show();

            // Fetch order details via AJAX
            fetch(`../actions/get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    content.innerHTML = data;
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error loading order details. Please try again.
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>