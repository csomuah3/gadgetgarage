<?php
echo "<!-- PHP is working -->";
try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');
    require_once(__DIR__ . '/../helpers/image_helper.php');

    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    $cart_total_raw = get_cart_total_ctr($customer_id, $ip_address);
    $cart_total = $cart_total_raw ?: 0;
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Order - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
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

        /* Promotional Banner Styles */
        

        

        

        

        /* Main Header Styles */
        

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo {
            height: 50px;
            width: auto;
        }

        .search-container {
            flex: 1;
            max-width: 600px;
            position: relative;
        }

        .search-wrapper {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.1);
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-btn {
            position: relative;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .icon-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            border: 2px solid white;
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown .dropdown-menu {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 10px 0;
            min-width: 200px;
            margin-top: 10px;
        }

        .user-dropdown .dropdown-item {
            padding: 12px 20px;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .user-dropdown .dropdown-item:hover {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .user-dropdown .dropdown-item:last-child {
            border-bottom: none;
            color: #dc3545;
        }

        .user-dropdown .dropdown-item:last-child:hover {
            background: #dc3545;
            color: white;
        }

        /* Navigation Bar Styles */
        .navbar-custom {
            background: #001f3f;
            padding: 0;
            position: sticky;
            top: 82px;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-nav-custom {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0 15px;
        }

        .nav-item-custom {
            margin: 0;
        }

        .nav-link-custom {
            color: white !important;
            padding: 15px 25px !important;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .nav-link-custom:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff !important;
        }

        .nav-link-custom.active {
            background: rgba(255, 255, 255, 0.2);
            color: #fff !important;
        }

        .dropdown-menu-custom {
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            padding: 10px 0;
            margin-top: 0;
            min-width: 250px;
        }

        .dropdown-item-custom {
            padding: 12px 20px;
            color: #333;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-item-custom:hover {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .dropdown-item-custom:last-child {
            border-bottom: none;
        }

        /* Main Content Styles */
        .main-content {
            display: flex;
            min-height: calc(100vh - 114px);
            background: #f8f9fa;
        }

        .account-sidebar {
            width: 280px;
            background: white;
            padding: 30px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            border-right: 1px solid #e0e0e0;
        }

        .sidebar-header {
            padding: 0 30px 30px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .sidebar-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 15px 30px;
            color: #546e7a;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            border-left: 4px solid transparent;
        }

        .sidebar-nav a:hover {
            background: #f8f9fa;
            color: #667eea;
            border-left-color: #667eea;
        }

        .sidebar-nav a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-left-color: #764ba2;
        }

        .sidebar-nav i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        .content-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .page-subtitle {
            color: #7f8c8d;
            margin: 5px 0 0;
            font-size: 16px;
        }

        /* Track Order Specific Styles */
        .track-order-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .search-order-form {
            max-width: 500px;
            margin: 0 auto 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-track {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .order-timeline {
            display: none;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 0;
        }

        .order-details-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }

        .order-details-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .order-info-item {
            display: flex;
            flex-direction: column;
        }

        .order-info-label {
            font-weight: 600;
            color: #546e7a;
            font-size: 14px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-info-value {
            font-size: 16px;
            color: #2c3e50;
        }

        .timeline-container {
            position: relative;
            padding: 20px 0;
        }

        .timeline-line {
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e0e0e0;
            z-index: 1;
        }

        .timeline-progress {
            position: absolute;
            left: 30px;
            top: 0;
            width: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 2;
            transition: height 0.8s ease;
        }

        .timeline-item {
            position: relative;
            padding: 20px 0 20px 80px;
            margin-bottom: 10px;
        }

        .timeline-icon {
            position: absolute;
            left: 15px;
            top: 25px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            z-index: 3;
            transition: all 0.5s ease;
        }

        .timeline-icon.completed {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .timeline-icon.current {
            background: #ff6b6b;
            box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.2);
            animation: pulse 2s infinite;
        }

        .timeline-icon.pending {
            background: #e0e0e0;
            color: #999;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.2);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(255, 107, 107, 0.1);
            }
            100% {
                box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.2);
            }
        }

        .timeline-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .timeline-item.completed .timeline-content {
            border-left-color: #667eea;
        }

        .timeline-item.current .timeline-content {
            border-left-color: #ff6b6b;
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.15);
        }

        .timeline-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .timeline-description {
            color: #7f8c8d;
            margin-bottom: 8px;
        }

        .timeline-date {
            font-size: 14px;
            color: #95a5a6;
            font-weight: 500;
        }

        .estimated-delivery {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-top: 30px;
        }

        .estimated-delivery h4 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .estimated-delivery p {
            margin: 0;
            opacity: 0.9;
        }

        .no-order-found {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .no-order-found i {
            font-size: 64px;
            color: #e0e0e0;
            margin-bottom: 20px;
        }

        .no-order-found h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        /* Footer Styles */
        .main-footer {
            background: #001f3f;
            color: white;
            padding: 60px 0 30px;
            margin-top: 80px;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .footer-section {
            margin-bottom: 40px;
        }

        .footer-section h4 {
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 18px;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #667eea;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }

        .social-icons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .social-icons a {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            background: #667eea;
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }

            .account-sidebar {
                width: 100%;
                box-shadow: none;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                padding: 0 15px;
            }

            .sidebar-nav li {
                flex-shrink: 0;
            }

            .sidebar-nav a {
                padding: 15px 20px;
                white-space: nowrap;
            }

            .content-area {
                padding: 20px 15px;
            }

            .track-order-container {
                padding: 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .search-container {
                order: 3;
                max-width: 100%;
            }

            .timeline-item {
                padding-left: 60px;
            }

            .order-info {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 576px) {
            

            .timeline-item {
                padding-left: 50px;
            }

            .timeline-icon {
                left: 10px;
                width: 25px;
                height: 25px;
                font-size: 12px;
            }

            .timeline-line,
            .timeline-progress {
                left: 22px;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <div class="navbar-nav-custom">
                <div class="nav-item-custom">
                    <a class="nav-link-custom" href="index.php">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </div>
                <div class="nav-item-custom dropdown">
                    <a class="nav-link-custom dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-list me-2"></i>Categories
                    </a>
                    <ul class="dropdown-menu dropdown-menu-custom">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <li><a class="dropdown-item dropdown-item-custom" href="products.php?category=<?= urlencode($category['cat_id']) ?>"><?= htmlspecialchars($category['cat_name']) ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a class="dropdown-item dropdown-item-custom" href="#">No categories available</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="nav-item-custom dropdown">
                    <a class="nav-link-custom dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-tags me-2"></i>Brands
                    </a>
                    <ul class="dropdown-menu dropdown-menu-custom">
                        <?php if (!empty($brands)): ?>
                            <?php foreach ($brands as $brand): ?>
                                <li><a class="dropdown-item dropdown-item-custom" href="products.php?brand=<?= urlencode($brand['brand_id']) ?>"><?= htmlspecialchars($brand['brand_name']) ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a class="dropdown-item dropdown-item-custom" href="#">No brands available</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="nav-item-custom">
                    <a class="nav-link-custom" href="products.php">
                        <i class="fas fa-mobile-alt me-2"></i>All Products
                    </a>
                </div>
                <div class="nav-item-custom">
                    <a class="nav-link-custom" href="contact.php">
                        <i class="fas fa-envelope me-2"></i>Contact
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Account Sidebar -->
        <aside class="account-sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">My Account</h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="account.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="my_orders.php"><i class="fas fa-box"></i>My Orders</a></li>
                <li><a href="track_order.php" class="active"><i class="fas fa-truck"></i>Track Orders</a></li>
                <li><a href="account_info.php"><i class="fas fa-user-edit"></i>My Info</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i>Notifications</a></li>
                <li><a href="help_center.php"><i class="fas fa-question-circle"></i>Help Center</a></li>
                <li><a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i>Sign Out</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content-area">
            <div class="page-header">
                <h1 class="page-title">Track Your Order</h1>
                <p class="page-subtitle">Enter your order reference number to track your package</p>
            </div>

            <div class="track-order-container">
                <!-- Order Search Form -->
                <form class="search-order-form" id="trackOrderForm">
                    <div class="form-group">
                        <label for="orderReference" class="form-label">Order Reference Number</label>
                        <input type="text" class="form-control" id="orderReference" placeholder="Enter your invoice number (e.g., INV20241127001)" required>
                        <small class="form-text text-muted">Your invoice number was sent to your email after checkout</small>
                    </div>
                    <button type="submit" class="btn-track">
                        <i class="fas fa-search me-2"></i>Track Order
                    </button>
                </form>

                <!-- Order Timeline (Hidden by default) -->
                <div class="order-timeline" id="orderTimeline">
                    <!-- Order Details Card -->
                    <div class="order-details-card">
                        <h3 class="order-details-title">Order Details</h3>
                        <div class="order-info" id="orderInfo">
                            <!-- Order info will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Timeline Container -->
                    <div class="timeline-container">
                        <div class="timeline-line"></div>
                        <div class="timeline-progress" id="timelineProgress"></div>

                        <div class="timeline-item completed" id="step1">
                            <div class="timeline-icon completed">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="timeline-content">
                                <h4 class="timeline-title">Order Placed</h4>
                                <p class="timeline-description">Your order has been successfully placed and confirmed.</p>
                                <span class="timeline-date" id="orderDate"></span>
                            </div>
                        </div>

                        <div class="timeline-item completed" id="step2">
                            <div class="timeline-icon completed">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="timeline-content">
                                <h4 class="timeline-title">Processing</h4>
                                <p class="timeline-description">Your order is being prepared and packaged by our team.</p>
                                <span class="timeline-date" id="processingDate"></span>
                            </div>
                        </div>

                        <div class="timeline-item current" id="step3">
                            <div class="timeline-icon current">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="timeline-content">
                                <h4 class="timeline-title">Out for Delivery</h4>
                                <p class="timeline-description">Your order is on its way to your delivery address.</p>
                                <span class="timeline-date" id="shippingDate"></span>
                            </div>
                        </div>

                        <div class="timeline-item pending" id="step4">
                            <div class="timeline-icon pending">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="timeline-content">
                                <h4 class="timeline-title">Delivered</h4>
                                <p class="timeline-description">Your order has been successfully delivered.</p>
                                <span class="timeline-date" id="deliveryDate"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Estimated Delivery -->
                    <div class="estimated-delivery">
                        <h4><i class="fas fa-calendar-alt me-2"></i>Estimated Delivery</h4>
                        <p id="estimatedDeliveryText"></p>
                    </div>
                </div>

                <!-- No Order Found Message -->
                <div class="no-order-found d-none" id="noOrderFound">
                    <i class="fas fa-search"></i>
                    <h3>Order Not Found</h3>
                    <p>We couldn't find an order with that reference number. Please check your order reference and try again.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="row">
                <div class="col-md-3">
                    <div class="footer-section">
                        <h4>About Gadget Garage</h4>
                        <p style="color: rgba(255, 255, 255, 0.8);">Your trusted destination for the latest gadgets, electronics, and tech accessories. Quality products, competitive prices, and exceptional service.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="footer-section">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="products.php">All Products</a></li>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="track_order.php">Track Order</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="footer-section">
                        <h4>Customer Support</h4>
                        <ul>
                            <li><a href="help_center.php">Help Center</a></li>
                            <li><a href="shipping.php">Shipping Info</a></li>
                            <li><a href="returns.php">Returns & Refunds</a></li>
                            <li><a href="warranty.php">Warranty</a></li>
                            <li><a href="privacy.php">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="footer-section">
                        <h4>Contact Info</h4>
                        <ul>
                            <li><i class="fas fa-map-marker-alt me-2"></i>123 Tech Street, Accra, Ghana</li>
                            <li><i class="fas fa-phone me-2"></i>+233 24 123 4567</li>
                            <li><i class="fas fa-envelope me-2"></i>support@gadgetgarage.com</li>
                            <li><i class="fas fa-clock me-2"></i>Mon-Sat: 8AM-8PM</li>
                        </ul>
                        <div class="social-icons">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Gadget Garage. All rights reserved. | Powered by Innovation</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Track Order Form Handler
        document.getElementById('trackOrderForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const orderReference = document.getElementById('orderReference').value.trim();

            if (!orderReference) {
                showError('Please enter an order reference number');
                return;
            }

            try {
                // Show loading state
                const submitBtn = document.querySelector('.btn-track');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Tracking...';
                submitBtn.disabled = true;

                // Fetch order tracking information
                const response = await fetch('../actions/get_tracking_info.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_reference: orderReference
                    })
                });

                const data = await response.json();

                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                if (data.status === 'success') {
                    displayOrderTracking(data.order);
                } else {
                    showNoOrderFound();
                }
            } catch (error) {
                console.error('Error tracking order:', error);
                showError('Unable to track order. Please try again.');

                // Reset button
                const submitBtn = document.querySelector('.btn-track');
                submitBtn.innerHTML = '<i class="fas fa-search me-2"></i>Track Order';
                submitBtn.disabled = false;
            }
        });

        function displayOrderTracking(order) {
            // Hide no order found message
            document.getElementById('noOrderFound').classList.add('d-none');

            // Populate order details
            const orderInfo = document.getElementById('orderInfo');
            orderInfo.innerHTML = `
                <div class="order-info-item">
                    <span class="order-info-label">Order Reference</span>
                    <span class="order-info-value">${order.order_reference}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Total Amount</span>
                    <span class="order-info-value">GH₵ ${parseFloat(order.total_amount).toFixed(2)}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Order Status</span>
                    <span class="order-info-value">${order.order_status.toUpperCase()}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Tracking Number</span>
                    <span class="order-info-value">${order.tracking_number || 'TBD'}</span>
                </div>
            `;

            // Set order dates
            const orderDate = new Date(order.order_date);
            document.getElementById('orderDate').textContent = formatDate(orderDate);

            // Calculate processing date (same day or next day)
            const processingDate = new Date(orderDate);
            processingDate.setHours(processingDate.getHours() + 2);
            document.getElementById('processingDate').textContent = formatDate(processingDate);

            // Calculate shipping date (1-2 days after order)
            const shippingDate = new Date(orderDate);
            shippingDate.setDate(shippingDate.getDate() + 1);
            document.getElementById('shippingDate').textContent = formatDate(shippingDate);

            // Calculate estimated delivery (3-5 days after order)
            const estimatedDeliveryStart = new Date(orderDate);
            estimatedDeliveryStart.setDate(estimatedDeliveryStart.getDate() + 3);
            const estimatedDeliveryEnd = new Date(orderDate);
            estimatedDeliveryEnd.setDate(estimatedDeliveryEnd.getDate() + 5);

            document.getElementById('estimatedDeliveryText').textContent =
                `${formatDate(estimatedDeliveryStart)} - ${formatDate(estimatedDeliveryEnd)}`;

            // Update timeline based on order age
            const daysSinceOrder = Math.floor((new Date() - orderDate) / (1000 * 60 * 60 * 24));
            updateTimelineProgress(daysSinceOrder);

            // Show timeline
            document.getElementById('orderTimeline').style.display = 'block';
            document.getElementById('orderTimeline').scrollIntoView({ behavior: 'smooth' });
        }

        function updateTimelineProgress(daysSinceOrder) {
            // Reset all timeline items
            const timelineItems = document.querySelectorAll('.timeline-item');
            const timelineIcons = document.querySelectorAll('.timeline-icon');

            timelineItems.forEach(item => {
                item.classList.remove('completed', 'current', 'pending');
            });
            timelineIcons.forEach(icon => {
                icon.classList.remove('completed', 'current', 'pending');
            });

            let progressPercentage = 25; // Always show order placed

            if (daysSinceOrder >= 0) {
                // Order Placed - Always completed
                document.getElementById('step1').classList.add('completed');
                document.querySelector('#step1 .timeline-icon').classList.add('completed');
                progressPercentage = 25;
            }

            if (daysSinceOrder >= 0) {
                // Processing - Starts immediately
                document.getElementById('step2').classList.add('completed');
                document.querySelector('#step2 .timeline-icon').classList.add('completed');
                progressPercentage = 50;
            }

            if (daysSinceOrder >= 2) {
                // Out for Delivery - After 2+ days
                document.getElementById('step3').classList.add('completed');
                document.querySelector('#step3 .timeline-icon').classList.add('completed');
                progressPercentage = 75;
            } else if (daysSinceOrder >= 1) {
                // Currently Out for Delivery
                document.getElementById('step3').classList.add('current');
                document.querySelector('#step3 .timeline-icon').classList.add('current');
                progressPercentage = 65;
            } else {
                document.getElementById('step3').classList.add('pending');
                document.querySelector('#step3 .timeline-icon').classList.add('pending');
            }

            if (daysSinceOrder >= 4) {
                // Delivered - After 4+ days
                document.getElementById('step4').classList.add('completed');
                document.querySelector('#step4 .timeline-icon').classList.add('completed');
                progressPercentage = 100;

                // Update delivery date
                const orderDate = new Date();
                orderDate.setDate(orderDate.getDate() - daysSinceOrder + 4);
                document.getElementById('deliveryDate').textContent = formatDate(orderDate);
            } else {
                document.getElementById('step4').classList.add('pending');
                document.querySelector('#step4 .timeline-icon').classList.add('pending');
                document.getElementById('deliveryDate').textContent = 'Pending';
            }

            // Animate progress bar
            const progressBar = document.getElementById('timelineProgress');
            progressBar.style.height = progressPercentage + '%';
        }

        function showNoOrderFound() {
            document.getElementById('orderTimeline').style.display = 'none';
            document.getElementById('noOrderFound').classList.remove('d-none');
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#667eea'
            });
        }

        function formatDate(date) {
            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Search functionality
        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm) {
                window.location.href = `products.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }

        // Enter key search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Check for order reference in URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const orderRef = urlParams.get('ref');

            if (orderRef) {
                document.getElementById('orderReference').value = orderRef;
                document.getElementById('trackOrderForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>

</html>