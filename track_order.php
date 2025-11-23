<?php
session_start();
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/controllers/order_controller.php';

$tracking_result = null;
$error_message = '';

// Check if tracking request
if ($_GET['order'] ?? '') {
    $search_value = trim($_GET['order']);

    try {
        // Try to get order by order ID or tracking number
        $tracking_result = get_order_tracking_details($search_value);

        if (!$tracking_result) {
            $error_message = 'Order not found. Please check your order number or tracking number.';
        }
    } catch (Exception $e) {
        $error_message = 'Error retrieving order details. Please try again later.';
        error_log('Order tracking error: ' . $e->getMessage());
    }
}

// Get cart count for logged in users
$is_logged_in = check_login();
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = 0;

try {
    require_once __DIR__ . '/controllers/cart_controller.php';
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);
} catch (Exception $e) {
    // Silent fail for cart count
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a1a1a;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .tracking-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .tracking-search {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 30px;
            text-align: center;
        }

        .tracking-search h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .tracking-search p {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
            gap: 15px;
        }

        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .tracking-result {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .order-info h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .tracking-number {
            font-size: 1rem;
            color: #6b7280;
            font-family: 'Courier New', monospace;
        }

        .current-status {
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #e0e7ff; color: #5b21b6; }
        .status-out_for_delivery { background: #fed7aa; color: #c2410c; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }

        .tracking-timeline {
            position: relative;
            margin: 40px 0;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 50px;
            width: 2px;
            height: calc(100% - 10px);
            background: #e5e7eb;
        }

        .timeline-item.active::after {
            background: #10b981;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1rem;
            flex-shrink: 0;
            border: 3px solid #e5e7eb;
            background: white;
            color: #6b7280;
        }

        .timeline-item.active .timeline-icon {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }

        .timeline-item.current .timeline-icon {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .timeline-date {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .timeline-description {
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.5;
        }

        .order-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }

        .order-details h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: #4b5563;
            color: white;
        }

        @media (max-width: 768px) {
            .tracking-search,
            .tracking-result {
                padding: 25px;
            }

            .search-form {
                flex-direction: column;
            }

            .order-header {
                flex-direction: column;
                text-align: center;
            }

            .current-status {
                text-align: center;
            }

            .timeline-item {
                margin-bottom: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png" alt="Gadget Garage">
                </a>

                <div class="d-flex align-items-center gap-3">
                    <?php if ($is_logged_in): ?>
                        <a href="views/cart.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-shopping-cart"></i>
                            Cart <?php if ($cart_count > 0): ?>(<?php echo $cart_count; ?>)<?php endif; ?>
                        </a>
                        <a href="views/my_orders.php" class="btn btn-primary btn-sm">My Orders</a>
                    <?php else: ?>
                        <a href="login/login.php" class="btn btn-primary btn-sm">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="tracking-container">
        <!-- Search Form -->
        <div class="tracking-search">
            <h1><i class="fas fa-search-location me-3"></i>Track Your Order</h1>
            <p>Enter your order number or tracking number to see the latest status of your delivery</p>

            <form class="search-form" method="GET">
                <input
                    type="text"
                    name="order"
                    class="search-input"
                    placeholder="Enter Order ID or Tracking Number"
                    value="<?php echo htmlspecialchars($_GET['order'] ?? ''); ?>"
                    required
                >
                <button type="submit" class="search-btn">
                    <i class="fas fa-search me-2"></i>Track Order
                </button>
            </form>
        </div>

        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Tracking Results -->
        <?php if ($tracking_result): ?>
            <div class="tracking-result">
                <div class="order-header">
                    <div class="order-info">
                        <h2>Order #<?php echo htmlspecialchars($tracking_result['order']['order_id']); ?></h2>
                        <div class="tracking-number">
                            Tracking: <?php echo htmlspecialchars($tracking_result['order']['tracking_number']); ?>
                        </div>
                    </div>
                    <div class="current-status">
                        <span class="status-badge status-<?php echo strtolower($tracking_result['order']['order_status']); ?>">
                            <?php echo ucwords(str_replace('_', ' ', $tracking_result['order']['order_status'])); ?>
                        </span>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <div class="tracking-timeline">
                    <?php if (!empty($tracking_result['tracking'])): ?>
                        <?php
                        $current_status = $tracking_result['order']['order_status'];
                        $status_order = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
                        $current_index = array_search($current_status, $status_order);
                        ?>

                        <?php foreach ($tracking_result['tracking'] as $index => $track): ?>
                            <?php
                            $is_current = $track['status'] === $current_status;
                            $is_active = array_search($track['status'], $status_order) <= $current_index;
                            ?>
                            <div class="timeline-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                <div class="timeline-icon">
                                    <?php
                                    $icons = [
                                        'pending' => 'fa-clock',
                                        'processing' => 'fa-cogs',
                                        'shipped' => 'fa-truck',
                                        'out_for_delivery' => 'fa-route',
                                        'delivered' => 'fa-check-circle',
                                        'cancelled' => 'fa-times-circle'
                                    ];
                                    $icon = $icons[$track['status']] ?? 'fa-info-circle';
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <?php echo ucwords(str_replace('_', ' ', $track['status'])); ?>
                                    </div>
                                    <div class="timeline-date">
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($track['status_date'])); ?>
                                    </div>
                                    <?php if ($track['notes']): ?>
                                        <div class="timeline-description">
                                            <?php echo htmlspecialchars($track['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($track['location']): ?>
                                        <div class="timeline-description">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($track['location']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Order Details -->
                <div class="order-details">
                    <h3>Order Summary</h3>
                    <div class="order-summary">
                        <div class="summary-item">
                            <div class="summary-label">Order Date</div>
                            <div class="summary-value">
                                <?php echo date('M j, Y', strtotime($tracking_result['order']['order_date'])); ?>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total Amount</div>
                            <div class="summary-value">
                                GH¢<?php echo number_format($tracking_result['order']['total_amount'], 2); ?>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Items</div>
                            <div class="summary-value">
                                <?php echo $tracking_result['order']['item_count']; ?> item(s)
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Payment Status</div>
                            <div class="summary-value">
                                <span class="text-success">Paid</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Shop
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>