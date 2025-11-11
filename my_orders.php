<?php
try {
    require_once(__DIR__ . '/settings/core.php');
    require_once(__DIR__ . '/controllers/order_controller.php');

    $is_logged_in = check_login();

    if (!$is_logged_in) {
        header("Location: login/user_login.php");
        exit;
    }

    $customer_id = $_SESSION['user_id'];
    $orders = get_user_orders_ctr($customer_id);

} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - FlavorHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
            color: #1a202c;
        }

        .main-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            box-shadow: 0 2px 10px rgba(139, 95, 191, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #8b5fbf;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .co {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .page-header {
            background: linear-gradient(135deg, #8b5fbf 0%, #f093fb 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.15);
        }

        .order-header {
            background: #f8f9ff;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-body {
            padding: 1.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-pending {
            background: #fef3cd;
            color: #8b5cf6;
        }

        .status-processing {
            background: #d4f4ff;
            color: #0369a1;
        }

        .status-completed {
            background: #d1fae5;
            color: #059669;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #7c4dff, #e91e63);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a class="logo navbar-brand" href="index.php">
                    Flavor<span class="co">Hub</span>
                </a>

                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="index.php">Home</a>
                    <a class="nav-link" href="all_product.php">All Products</a>
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                    </a>
                    <a class="nav-link" href="my_orders.php">
                        <i class="fas fa-box"></i> My Orders
                    </a>
                    <a class="nav-link" href="login/logout.php">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-box me-3"></i>
                        My Orders
                    </h1>
                    <p class="mb-0 fs-5 opacity-90">
                        Track your orders and purchase history
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 class="text-muted mb-3">No orders yet</h3>
                <p class="text-muted mb-4">Start shopping to see your orders here.</p>
                <a href="all_product.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h5 class="mb-1">Order #<?php echo $order['order_id']; ?></h5>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Invoice No:</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($order['invoice_no']); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Status:</div>
                                <?php
                                $status_class = '';
                                switch(strtolower($order['order_status'])) {
                                    case 'pending': $status_class = 'status-pending'; break;
                                    case 'processing': $status_class = 'status-processing'; break;
                                    case 'completed': $status_class = 'status-completed'; break;
                                    case 'cancelled': $status_class = 'status-cancelled'; break;
                                    default: $status_class = 'status-pending';
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="text-muted small">Total:</div>
                                <div class="fs-5 fw-bold text-primary">
                                    GHS <?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-shopping-bag text-primary fa-lg"></i>
                                    <div>
                                        <div class="fw-bold">
                                            <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
                                        </div>
                                        <div class="text-muted small">
                                            Order placed on <?php echo date('l, F j, Y', strtotime($order['order_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye me-2"></i>
                                    View Details
                                </button>
                                <?php if (strtolower($order['order_status']) === 'completed'): ?>
                                    <button class="btn btn-outline-success btn-sm ms-2">
                                        <i class="fas fa-redo me-2"></i>
                                        Reorder
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-4">
                <a href="all_product.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
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

            content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading order details...</div>';
            modal.show();

            // Fetch order details via AJAX
            fetch(`actions/get_order_details.php?order_id=${orderId}`)
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