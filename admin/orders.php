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

// Get all orders with enhanced query
try {
    $orders_query = "SELECT o.*, c.customer_name, c.customer_email, c.customer_contact,
                           COUNT(od.product_id) as item_count,
                           SUM(od.qty) as total_items,
                           p.amt as total_amount,
                           p.currency,
                           p.payment_date
                    FROM orders o
                    JOIN customer c ON o.customer_id = c.customer_id
                    LEFT JOIN orderdetails od ON o.order_id = od.order_id
                    LEFT JOIN payment p ON o.order_id = p.order_id
                    GROUP BY o.order_id
                    ORDER BY o.order_date DESC";

    $db = new db_connection();
    $db->db_connect();
    $orders = $db->db_fetch_all($orders_query);

    // Get order statistics
    $stats_query = "SELECT
                      COUNT(*) as total_orders,
                      SUM(CASE WHEN o.order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                      SUM(CASE WHEN o.order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                      SUM(CASE WHEN o.order_status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                      SUM(CASE WHEN o.order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                    FROM orders o";
    $stats = $db->db_fetch_one($stats_query);

} catch (Exception $e) {
    $error_message = "Error loading orders: " . $e->getMessage();
    $orders = [];
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Gadget Garage Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Import Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
            color: #065f46;
            min-height: 100vh;
        }

        /* Animated Background Elements */
        .bg-decoration {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            opacity: 0.6;
        }

        .bg-decoration-1 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
            top: 10%;
            right: 15%;
            animation: float 8s ease-in-out infinite;
        }

        .bg-decoration-2 {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.08), rgba(16, 185, 129, 0.08));
            bottom: 20%;
            left: 10%;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(90deg); }
            50% { transform: translateY(-10px) rotate(180deg); }
            75% { transform: translateY(-15px) rotate(270deg); }
        }

        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.08);
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #047857;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .garage {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .sidebar {
            background: linear-gradient(135deg, #047857, #059669);
            color: white;
            min-height: calc(100vh - 100px);
            position: relative;
            z-index: 10;
            border-radius: 0 20px 20px 0;
            box-shadow: 4px 0 20px rgba(16, 185, 129, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .stats-cards .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.05);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stats-cards .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #047857;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .orders-table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.05);
            position: relative;
            z-index: 10;
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
            border: none;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: #047857;
            font-weight: 600;
            border: none;
            padding: 18px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 16px 18px;
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(16, 185, 129, 0.05);
            transform: scale(1.01);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-action {
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            transform: translateY(-1px);
            color: white;
        }

        .btn-status {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            margin: 2px;
            transition: all 0.2s ease;
        }

        .btn-status:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-1px);
            color: white;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #047857;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #dc2626;
        }

        .order-customer {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #34d399);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .sidebar {
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Background Decorations -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>

    <!-- Header -->
    <header class="main-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="../index.php" class="logo">
                    Gadget<span class="garage">Garage</span>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">Admin Panel</span>
                    <a href="../login/logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h5 class="text-white mb-4">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </h5>
                        <nav class="nav flex-column">
                            <a class="nav-link" href="../index.php">
                                <i class="fas fa-home"></i> Home
                            </a>
                            <a class="nav-link" href="product.php">
                                <i class="fas fa-box"></i> Products
                            </a>
                            <a class="nav-link" href="category.php">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                            <a class="nav-link" href="brand.php">
                                <i class="fas fa-trademark"></i> Brands
                            </a>
                            <a class="nav-link active" href="orders.php">
                                <i class="fas fa-shopping-bag"></i> Orders
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h2 mb-1">Order Management</h1>
                            <p class="text-muted mb-0">Monitor and manage customer orders</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-action btn-view" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <?php if (!empty($stats)): ?>
                    <div class="stats-cards row g-3 mb-4">
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-number"><?php echo number_format($stats['pending_orders']); ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="stat-number"><?php echo number_format($stats['processing_orders']); ?></div>
                                <div class="stat-label">Processing</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="stat-number"><?php echo number_format($stats['completed_orders']); ?></div>
                                <div class="stat-label">Completed</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Orders Table -->
                    <div class="orders-table-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Recent Orders</h5>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" style="width: auto;" onchange="filterOrders(this.value)">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <?php if (empty($orders)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4>No Orders Found</h4>
                                <p>When customers place orders, they will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table" id="ordersTable">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Customer</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr data-status="<?php echo strtolower($order['order_status']); ?>">
                                                <td>
                                                    <div>
                                                        <strong>#<?php echo $order['order_id']; ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($order['invoice_no']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="order-customer">
                                                        <div class="customer-avatar">
                                                            <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info rounded-pill">
                                                        <?php echo $order['total_items']; ?> items
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>GHS <?php echo number_format($order['total_amount'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = match(strtolower($order['order_status'])) {
                                                        'pending' => 'bg-warning text-dark',
                                                        'processing' => 'bg-info text-white',
                                                        'completed' => 'bg-success text-white',
                                                        'cancelled' => 'bg-danger text-white',
                                                        default => 'bg-secondary text-white'
                                                    };
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($order['order_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo date('M d, Y', strtotime($order['order_date'])); ?><br>
                                                        <small class="text-muted"><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        <a href="#" class="btn-action btn-view" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </a>

                                                        <?php if (strtolower($order['order_status']) !== 'completed'): ?>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn-status dropdown-toggle" data-bs-toggle="dropdown">
                                                                    <i class="fas fa-cog"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <?php if (strtolower($order['order_status']) !== 'processing'): ?>
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                            <input type="hidden" name="status" value="processing">
                                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                                <i class="fas fa-cog text-info"></i> Set Processing
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                    <?php endif; ?>
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                            <input type="hidden" name="status" value="completed">
                                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                                <i class="fas fa-check text-success"></i> Mark Completed
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                            <input type="hidden" name="status" value="cancelled">
                                                                            <button type="submit" name="update_status" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to cancel this order?')">
                                                                                <i class="fas fa-times text-danger"></i> Cancel Order
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt text-primary"></i> Order Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrderDetails(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            const content = document.getElementById('orderDetailsContent');

            content.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-3 mb-0">Loading order details...</p>
                </div>
            `;
            modal.show();

            // Fetch order details via AJAX
            fetch(`../actions/get_admin_order_details.php?order_id=${orderId}`)
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

        function filterOrders(status) {
            const table = document.getElementById('ordersTable');
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                if (status === '' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Auto-refresh orders every 30 seconds
        setInterval(() => {
            // Only refresh if no modal is open
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>