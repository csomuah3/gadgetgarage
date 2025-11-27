<?php
try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/order_controller.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');
    require_once(__DIR__ . '/../helpers/image_helper.php');

    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!$is_logged_in) {
        header("Location: ../login/login.php");
        exit;
    }

    // Get user orders
    $orders = [];
    try {
        $orders = get_user_orders_ctr($customer_id);
        if (!$orders) {
            $orders = [];
        }
    } catch (Exception $e) {
        error_log("Error fetching orders for customer $customer_id: " . $e->getMessage());
        $orders = [];
    }

    // Get cart and wishlist counts for header
    $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

    $categories = [];
    $brands = [];

    try {
        require_once(__DIR__ . '/../controllers/category_controller.php');
        $categories = get_all_categories_ctr();
    } catch (Exception $e) {
        error_log("Failed to load categories: " . $e->getMessage());
    }

    try {
        require_once(__DIR__ . '/../controllers/brand_controller.php');
        $brands = get_all_brands_ctr();
    } catch (Exception $e) {
        error_log("Failed to load brands: " . $e->getMessage());
    }

} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}

// Function to determine order status based on date
function getOrderStatus($order_date) {
    $order_timestamp = strtotime($order_date);
    $current_timestamp = time();
    $days_difference = floor(($current_timestamp - $order_timestamp) / (60 * 60 * 24));

    if ($days_difference == 0) {
        return "PROCESSING";
    } elseif ($days_difference >= 2) {
        return "OUT FOR DELIVERY";
    } else {
        return "PROCESSING";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <link href="../includes/header-styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background-color: #ffffff;
            color: #1a1a1a;
            overflow-x: hidden;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('http://169.239.251.102:442/~chelsea.somuah/uploads/ChatGPTImageNov19202511_50_42PM.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.45;
            z-index: -1;
            pointer-events: none;
        }

        /* Promotional Banner Styles - Same as cart */
        .promo-banner,
        .promo-banner2 {
            background: #001f3f !important;
            color: white;
            padding: 6px 15px;
            text-align: center;
            font-size: 1rem;
            font-weight: 400;
            position: sticky;
            top: 0;
            z-index: 1001;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            max-width: 100%;
        }

        .promo-banner-left,
        .promo-banner2 .promo-banner-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 0 0 auto;
        }

        .promo-banner-center,
        .promo-banner2 .promo-banner-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex: 1;
        }

        .promo-banner i,
        .promo-banner2 i {
            font-size: 1rem;
        }

        .promo-banner .promo-text,
        .promo-banner2 .promo-text {
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .promo-timer {
            background: transparent;
            padding: 0;
            border-radius: 0;
            font-size: 1.3rem;
            font-weight: 500;
            margin: 0;
            border: none;
        }

        .promo-shop-link {
            color: white;
            text-decoration: underline;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.3s ease;
            font-size: 1.2rem;
            flex: 0 0 auto;
        }

        .promo-shop-link:hover {
            opacity: 0.8;
        }

        /* Header Styles - Same as cart */
        .main-header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 38px;
            z-index: 1000;
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo img {
            height: 50px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .header-icon {
            font-size: 1.3rem;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .header-icon:hover {
            color: #1a202c;
            transform: scale(1.1);
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Navigation Bar - Same as cart */
        .navbar {
            background: #f8f9fa;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 108px;
            z-index: 999;
        }

        .navbar-nav .nav-link {
            color: #4a5568 !important;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            color: #000000 !important;
        }

        /* My Orders Content */
        .orders-content {
            min-height: 60vh;
            padding: 60px 0;
            position: relative;
            z-index: 1;
        }

        .orders-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .orders-title {
            font-size: 3rem;
            font-weight: 900;
            color: #000000;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .orders-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .order-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .order-status {
            font-size: 1.2rem;
            font-weight: 900;
            margin-bottom: 20px;
            color: #000000;
            letter-spacing: 0.5px;
        }

        .order-status.processing {
            color: #f59e0b;
        }

        .order-status.out-for-delivery {
            color: #10b981;
        }

        .order-images {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .order-image {
            width: 80px;
            height: 80px;
            background: #f8fafc;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .order-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-more {
            width: 80px;
            height: 80px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .order-details {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .order-number {
            color: #1e293b;
            font-weight: 600;
            text-decoration: underline;
        }

        .view-details-btn {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .view-details-btn:hover {
            background: #cbd5e1;
            color: #334155;
        }

        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .no-orders i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .no-orders h3 {
            color: #475569;
            margin-bottom: 15px;
        }

        .no-orders p {
            margin-bottom: 30px;
        }

        .start-shopping-btn {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .start-shopping-btn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: white;
            transform: translateY(-2px);
        }

        /* Footer Styles - Same as cart */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 0;
        }

        .footer-brand {
            margin-bottom: 30px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo img {
            height: 50px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .footer-description {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social-link:hover {
            background: #2563EB;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 24px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 14px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #2563EB;
            transform: translateX(4px);
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 40px 0 20px;
        }

        .footer-bottom {
            padding-top: 20px;
        }

        .copyright {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }

        /* Order Details Modal - Same as admin */
        .order-modal {
            z-index: 1060;
        }

        .order-modal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .order-modal .modal-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .order-modal .modal-body {
            padding: 30px;
        }

        .order-info-section {
            margin-bottom: 25px;
        }

        .order-info-section h6 {
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .order-items-table {
            background: #f8fafc;
            border-radius: 10px;
            overflow: hidden;
        }

        .order-items-table table {
            margin: 0;
        }

        .order-items-table th {
            background: #e2e8f0;
            color: #475569;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .order-items-table td {
            padding: 15px;
            border: none;
            color: #64748b;
        }

        .order-total-section {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .order-total-amount {
            font-size: 2rem;
            font-weight: 900;
            color: #1e293b;
        }

        @media (max-width: 768px) {
            .orders-title {
                font-size: 2rem;
            }

            .order-card {
                padding: 20px;
            }

            .order-images {
                gap: 10px;
            }

            .order-image,
            .order-more {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>

<body>
    <!-- Promotional Banner -->
    <div class="promo-banner2">
        <div class="promo-banner-left">
            <i class="fas fa-bolt"></i>
        </div>
        <div class="promo-banner-center">
            <span class="promo-text" data-translate="black_friday_deals">BLACK FRIDAY DEALS STOREWIDE! SHOP AMAZING DISCOUNTS! </span>
            <span class="promo-timer" id="promoTimer">12d:00h:00m:00s</span>
        </div>
        <a href="../index.php#flash-deals" class="promo-shop-link" data-translate="shop_now">Shop Now</a>
    </div>

    <!-- Main Header -->
    <header class="main-header animate__animated animate__fadeInDown">
        <div class="container-fluid" style="padding: 0 120px 0 95px;">
            <div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
                <!-- Logo - Far Left -->
                <a href="../index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                        alt="Gadget Garage">
                </a>

                <!-- Center Content -->
                <div class="d-flex align-items-center" style="flex: 1; justify-content: center; gap: 60px;">
                    <!-- Search Bar -->
                    <div class="search-container" style="position: relative; width: 400px;">
                        <input type="text" class="form-control search-input" id="headerSearchInput"
                            placeholder="Search for products..." style="border-radius: 25px; padding: 12px 45px 12px 20px; border: 2px solid #e5e7eb; font-size: 0.95rem;">
                        <button class="search-btn" type="button" onclick="performHeaderSearch()"
                            style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, #1E3A5F, #2563EB); border: none; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fas fa-search" style="font-size: 0.9rem;"></i>
                        </button>
                    </div>

                    <!-- Contact Info -->
                    <div class="tech-revival-text" style="color: #4a5568; font-size: 0.95rem; font-weight: 500; letter-spacing: 0.5px;">
                        <i class="fas fa-tools" style="margin-right: 8px; color: #059669;"></i>
                        Tech Revival & Innovation Hub
                    </div>

                    <div class="contact-number" style="color: #1f2937; font-weight: 600; font-size: 1rem;">
                        <i class="fas fa-phone" style="margin-right: 8px; color: #059669;"></i>
                        +233 (0) 123 456 789
                    </div>
                </div>

                <!-- Right side actions -->
                <div class="header-actions">
                    <a href="wishlist.php" class="header-icon">
                        <i class="fas fa-heart"></i>
                        <span class="badge" id="wishlist-count">0</span>
                    </a>
                    <a href="cart.php" class="header-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge" id="cart-count"><?= $cart_count ?></span>
                    </a>
                    <div class="dropdown">
                        <a href="#" class="header-icon dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="my_orders.php">My Orders</a></li>
                            <li><a class="dropdown-item" href="wishlist.php">My Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../actions/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid" style="padding: 0 120px;">
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <?php if (!empty($categories)): ?>
                        <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../views/shop.php?category=<?= htmlspecialchars($category['cat_id']) ?>">
                                    <?= htmlspecialchars($category['cat_name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">All Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- My Orders Content -->
    <main class="orders-content">
        <div class="container">
            <div class="orders-header">
                <h1 class="orders-title">MY ORDERS</h1>
            </div>

            <div class="orders-container">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $order_status = getOrderStatus($order['order_date']);
                        $order_items = get_order_details_ctr($order['order_id']);
                        $total_items = count($order_items);
                        ?>
                        <div class="order-card">
                            <div class="order-status <?= strtolower(str_replace(' ', '-', $order_status)) ?>">
                                <?= $order_status ?>
                            </div>

                            <div class="order-images">
                                <?php
                                $display_items = array_slice($order_items, 0, 4);
                                foreach ($display_items as $item):
                                ?>
                                    <div class="order-image">
                                        <img src="<?= get_image_url($item['product_image']) ?>"
                                             alt="<?= htmlspecialchars($item['product_title']) ?>"
                                             onerror="this.src='http://169.239.251.102:442/~chelsea.somuah/uploads/no-image.jpg'">
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($total_items > 4): ?>
                                    <div class="order-more">
                                        + <?= $total_items - 4 ?> more
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-details">
                                Order <span class="order-number">#<?= htmlspecialchars($order['order_reference']) ?></span> •
                                <strong>GH₵<?= number_format($order['total_amount'], 2) ?></strong> •
                                <?= date('d. M/Y', strtotime($order['order_date'])) ?>
                            </div>

                            <button class="view-details-btn" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">
                                View details
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                        <a href="../index.php" class="start-shopping-btn">Start Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Order Details Modal -->
    <div class="modal fade order-modal" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>
                        Order Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading order details...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row align-items-start">
                    <!-- First Column: Logo and Social -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-brand">
                            <div class="footer-logo" style="margin-bottom: 20px;">
                                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                                    alt="Gadget Garage">
                            </div>
                            <p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- Navigation Links -->
                    <div class="col-lg-5 col-md-12">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Get Help</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Help Center</a></li>
                                    <li><a href="contact.php">Track Order</a></li>
                                    <li><a href="terms_conditions.php">Shipping Info</a></li>
                                    <li><a href="terms_conditions.php">Returns</a></li>
                                    <li><a href="contact.php">Contact Us</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Company</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Careers</a></li>
                                    <li><a href="contact.php">About</a></li>
                                    <li><a href="contact.php">Stores</a></li>
                                    <li><a href="contact.php">Want to Collab?</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Quick Links</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Size Guide</a></li>
                                    <li><a href="contact.php">Sitemap</a></li>
                                    <li><a href="contact.php">Gift Cards</a></li>
                                    <li><a href="contact.php">Check Gift Card Balance</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Right Side: Email Signup Form -->
                    <div class="col-lg-4 col-md-12 mb-4">
                        <div class="newsletter-signup-section">
                            <h3 class="newsletter-title">SIGN UP FOR DISCOUNTS + UPDATES</h3>
                            <form class="newsletter-form" id="newsletterForm">
                                <input type="text" class="newsletter-input" placeholder="Phone Number or Email" required>
                                <button type="submit" class="newsletter-submit-btn">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                            <p class="newsletter-disclaimer">
                                By signing up for email, you agree to Gadget Garage's <a href="terms_conditions.php">Terms of Service</a> and <a href="legal.php">Privacy Policy</a>.
                            </p>
                        </div>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-12 text-center">
                            <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/header.js"></script>

    <script>
        // View Order Details Function
        function viewOrderDetails(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();

            // Fetch order details
            fetch('../actions/get_order_details_action.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayOrderDetails(data.order);
                    } else {
                        document.getElementById('orderDetailsContent').innerHTML =
                            '<div class="alert alert-danger">Failed to load order details: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailsContent').innerHTML =
                        '<div class="alert alert-danger">Failed to load order details. Please try again.</div>';
                });
        }

        // Display Order Details in Modal
        function displayOrderDetails(order) {
            const content = `
                <div class="order-info-section">
                    <h6><i class="fas fa-info-circle me-2"></i>Order Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order ID:</strong> #${order.order_reference}</p>
                            <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleDateString()}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tracking Number:</strong> ${order.tracking_number || 'N/A'}</p>
                            <p><strong>Status:</strong>
                                <span class="badge bg-${order.order_status === 'completed' ? 'success' : 'warning'}">
                                    ${order.order_status || 'Processing'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="order-info-section">
                    <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> ${order.customer_name}</p>
                            <p><strong>Email:</strong> ${order.customer_email}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Phone:</strong> ${order.customer_contact}</p>
                            <p><strong>Location:</strong> ${order.customer_city}, ${order.customer_country}</p>
                        </div>
                    </div>
                </div>

                <div class="order-info-section">
                    <h6><i class="fas fa-shopping-cart me-2"></i>Order Items</h6>
                    <div class="order-items-table">
                        <table class="table table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map(item => `
                                    <tr>
                                        <td>${item.product_title}</td>
                                        <td>${item.qty}</td>
                                        <td>GH₵${parseFloat(item.product_price).toFixed(2)}</td>
                                        <td>GH₵${(parseFloat(item.product_price) * parseInt(item.qty)).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="order-total-section">
                    <h6 class="mb-3">Total Amount</h6>
                    <div class="order-total-amount">GH₵${parseFloat(order.total_amount || order.payment_amount || 0).toFixed(2)}</div>
                    <small class="text-muted">Currency: Ghana Cedis</small>
                </div>
            `;

            document.getElementById('orderDetailsContent').innerHTML = content;
        }

        // Promo Timer
        function updatePromoTimer() {
            const timer = document.getElementById('promoTimer');
            if (timer) {
                const now = new Date();
                const endOfDay = new Date(now);
                endOfDay.setHours(23, 59, 59, 999);

                const diff = endOfDay - now;

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                timer.textContent = `${hours.toString().padStart(2, '0')}h:${minutes.toString().padStart(2, '0')}m:${seconds.toString().padStart(2, '0')}s`;
            }
        }

        // Update timer every second
        setInterval(updatePromoTimer, 1000);
        updatePromoTimer();

        // Header search functionality
        function performHeaderSearch() {
            const searchInput = document.getElementById('headerSearchInput');
            const searchTerm = searchInput.value.trim();

            if (searchTerm) {
                window.location.href = `shop.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }

        // Search on Enter key
        document.getElementById('headerSearchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performHeaderSearch();
            }
        });
    </script>
</body>
</html>