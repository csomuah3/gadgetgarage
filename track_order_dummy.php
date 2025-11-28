<?php
session_start();
require_once __DIR__ . '/settings/core.php';

$tracking_result = null;
$error_message = '';

// Check if tracking request
if (!empty($_GET['order'])) {
    $search_value = trim($_GET['order']);

    // DUMMY TRACKING SYSTEM - Always works!
    if (strlen($search_value) >= 3) {
        // Generate realistic fake data based on order number
        $order_id = crc32($search_value) % 9999 + 1000; // Generate consistent ID from order number
        $days_ago = (crc32($search_value) % 7) + 1; // 1-7 days ago

        // Calculate status based on days
        if ($days_ago >= 5) {
            $status = 'delivered';
            $status_text = 'Delivered';
        } elseif ($days_ago >= 3) {
            $status = 'out_for_delivery';
            $status_text = 'Out for Delivery';
        } elseif ($days_ago >= 2) {
            $status = 'shipped';
            $status_text = 'Shipped';
        } else {
            $status = 'processing';
            $status_text = 'Processing';
        }

        // Generate tracking number
        $tracking_number = 'GG' . strtoupper(substr(md5($search_value), 0, 8)) . rand(100, 999);

        // Generate realistic order date
        $order_date = date('Y-m-d H:i:s', strtotime("-{$days_ago} days"));

        // Generate realistic total amount
        $base_amount = (crc32($search_value) % 500) + 50; // $50-$550
        $total_amount = round($base_amount + ($base_amount * 0.1), 2); // Add 10% tax

        // Create fake tracking timeline
        $timeline = [];

        // Order placed
        $timeline[] = [
            'status' => 'pending',
            'status_date' => date('Y-m-d H:i:s', strtotime("-{$days_ago} days")),
            'notes' => 'Your order has been received and is being processed.',
            'location' => 'Gadget Garage Warehouse'
        ];

        // Processing (1 day later)
        if ($days_ago >= 1) {
            $timeline[] = [
                'status' => 'processing',
                'status_date' => date('Y-m-d H:i:s', strtotime("-" . ($days_ago - 1) . " days")),
                'notes' => 'Items picked and packed. Preparing for shipment.',
                'location' => 'Gadget Garage Fulfillment Center'
            ];
        }

        // Shipped (2 days later)
        if ($days_ago >= 2) {
            $timeline[] = [
                'status' => 'shipped',
                'status_date' => date('Y-m-d H:i:s', strtotime("-" . ($days_ago - 2) . " days")),
                'notes' => 'Package has been shipped and is in transit.',
                'location' => 'Accra Sorting Facility'
            ];
        }

        // Out for delivery (3 days later)
        if ($days_ago >= 3) {
            $timeline[] = [
                'status' => 'out_for_delivery',
                'status_date' => date('Y-m-d H:i:s', strtotime("-" . ($days_ago - 3) . " days")),
                'notes' => 'Package is out for delivery. Expected delivery today.',
                'location' => 'Local Delivery Hub'
            ];
        }

        // Delivered (5+ days later)
        if ($days_ago >= 5) {
            $timeline[] = [
                'status' => 'delivered',
                'status_date' => date('Y-m-d H:i:s', strtotime("-" . ($days_ago - 5) . " days")),
                'notes' => 'Package successfully delivered. Thank you for shopping with us!',
                'location' => 'Customer Address'
            ];
        }

        // Create fake order data
        $tracking_result = [
            'order' => [
                'order_id' => $order_id,
                'invoice_no' => $search_value,
                'order_date' => $order_date,
                'order_status' => $status,
                'tracking_number' => $tracking_number,
                'total_amount' => $total_amount,
                'item_count' => rand(1, 5),
                'customer_name' => 'Demo Customer',
                'delivery_address' => 'Sample Address, Accra, Ghana'
            ],
            'tracking' => $timeline
        ];
    } else {
        $error_message = 'Please enter a valid order reference number (minimum 3 characters).';
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

// Initialize arrays for navigation
$categories = [];
$brands = [];

try {
    require_once(__DIR__ . '/controllers/category_controller.php');
    $categories = get_all_categories_ctr();
} catch (Exception $e) {
    // Silent fail for categories
}

try {
    require_once(__DIR__ . '/controllers/brand_controller.php');
    $brands = get_all_brands_ctr();
} catch (Exception $e) {
    // Silent fail for brands
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a1a1a;
        }

        .tracking-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .tracking-search {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
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

        .error-message {
            background: rgba(248, 113, 113, 0.1);
            border: 2px solid #f87171;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: #dc2626;
            font-weight: 500;
            margin-bottom: 30px;
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

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #e0e7ff;
            color: #5b21b6;
        }

        .status-out_for_delivery {
            background: #fed7aa;
            color: #c2410c;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

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
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
        }

        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
        }

        .summary-label {
            font-weight: 500;
            color: #6b7280;
        }

        .summary-value {
            font-weight: 600;
            color: #1f2937;
        }

        .back-btn {
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #e5e7eb;
            color: #1f2937;
            text-decoration: none;
        }

        .demo-notice {
            background: linear-gradient(135deg, #fef3c7, #fde047);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            color: #92400e;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="tracking-container">
        <!-- Search Form -->
        <div class="tracking-search">
            <h1><i class="fas fa-search-location me-3"></i>Track Your Order</h1>
            <p>Enter any order number to see realistic tracking demo data</p>

            <div class="demo-notice">
                <i class="fas fa-info-circle me-2"></i>
                Demo Mode: This will generate realistic tracking data for any order number you enter!
            </div>

            <form class="search-form" method="GET">
                <input
                    type="text"
                    name="order"
                    class="search-input"
                    placeholder="Enter any Order ID (e.g., 4451968, ABC123, etc.)"
                    value="<?php echo htmlspecialchars($_GET['order'] ?? ''); ?>"
                    required>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search me-2"></i>Track Order
                </button>
            </form>
        </div>

        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
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
                                <span style="color: #059669;">Paid</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
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