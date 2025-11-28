<?php
echo "<!-- PHP is working -->";
try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');
    require_once(__DIR__ . '/../helpers/image_helper.php');

    $is_logged_in = check_login();

    // Redirect to login if not logged in
    if (!$is_logged_in) {
        header("Location: ../login/user_login.php");
        exit;
    }

    $customer_id = $_SESSION['user_id'];
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

    // Get user's name
    $user_name = $_SESSION['name'] ?? 'User';
    $first_name = explode(' ', $user_name)[0];
} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <link href="includes/header-styles.css" rel="stylesheet">
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
            gap: 10px;
            font-size: 0.9rem;
        }

        .promo-banner-center,
        .promo-banner2 .promo-banner-center {
            flex: 1;
            text-align: center;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .promo-banner-right,
        .promo-banner2 .promo-banner-right {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        /* Main Header Styles */
        .main-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 0;
            position: sticky;
            top: 32px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

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

        /* Dashboard Content */
        .dashboard-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .welcome-subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 28px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quick-actions {
            margin-top: 40px;
        }

        .actions-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: #667eea;
            background: #f8f9ff;
            color: #667eea;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 18px;
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

        .footer-brand {
            margin-bottom: 30px;
        }

        .footer-logo {
            margin-bottom: 20px;
        }

        .footer-logo img {
            height: 50px;
            width: auto;
        }

        .footer-description {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #667eea;
            transform: translateY(-3px);
            color: white;
        }

        .footer-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links li a:hover {
            color: #667eea;
        }

        .newsletter-signup-section {
            margin-bottom: 20px;
        }

        .newsletter-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .newsletter-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .newsletter-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .newsletter-submit-btn {
            padding: 12px 20px;
            background: #667eea;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-submit-btn:hover {
            background: #5a67d8;
        }

        .newsletter-disclaimer {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .newsletter-disclaimer a {
            color: #667eea;
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 40px 0 20px;
        }

        .footer-bottom {
            text-align: center;
        }

        .copyright {
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
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

            .dashboard-container {
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

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .promo-banner-left,
            .promo-banner-right {
                display: none;
            }

            .welcome-title {
                font-size: 24px;
            }

            .stats-grid {
                margin: 20px 0;
            }

            .stat-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Promotional Banner -->
    <div class="promo-banner">
        <div class="promo-banner-left">
            <i class="fas fa-shipping-fast"></i>
            <span>Free Shipping on Orders Over GH₵200</span>
        </div>
        <div class="promo-banner-center">
            <strong>🎉 Black Friday Sale - Up to 50% Off Selected Items! 🎉</strong>
        </div>
        <div class="promo-banner-right">
            <i class="fas fa-phone"></i>
            <span>Support: +233 24 123 4567</span>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-content">
            <a href="index.php" class="logo-container">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png" alt="Gadget Garage" class="logo">
            </a>

            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" class="search-input" placeholder="Search for products, brands, categories..." id="searchInput">
                    <button type="button" class="search-btn" onclick="performSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="header-icons">
                <?php if ($is_logged_in): ?>
                    <a href="cart.php" class="icon-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="user-dropdown dropdown">
                        <a href="#" class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="account.php"><i class="fas fa-user-circle me-2"></i>My Account</a></li>
                            <li><a class="dropdown-item" href="my_orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="track_order.php"><i class="fas fa-truck me-2"></i>Track Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../login/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="../login/user_login.php" class="icon-btn">
                        <i class="fas fa-sign-in-alt"></i>
                    </a>
                    <a href="cart.php" class="icon-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

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
                <li><a href="account.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="my_orders.php"><i class="fas fa-box"></i>My Orders</a></li>
                <li><a href="track_order.php"><i class="fas fa-truck"></i>Track Orders</a></li>
                <li><a href="account_info.php"><i class="fas fa-user-edit"></i>My Info</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i>Notifications</a></li>
                <li><a href="help_center.php"><i class="fas fa-question-circle"></i>Help Center</a></li>
                <li><a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i>Sign Out</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content-area">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Welcome back to your account</p>
            </div>

            <div class="dashboard-container">
                <div class="welcome-section">
                    <h2 class="welcome-title">Hello, <?= htmlspecialchars($first_name) ?>!</h2>
                    <p class="welcome-subtitle">Here's an overview of your account activity</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-value">0</div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-value">0</div>
                        <div class="stat-label">Pending Deliveries</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-value">0</div>
                        <div class="stat-label">Reviews Written</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?= $cart_count ?></div>
                        <div class="stat-label">Items in Cart</div>
                    </div>
                </div>

                <div class="quick-actions">
                    <h3 class="actions-title">Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="my_orders.php" class="action-btn">
                            <i class="fas fa-box"></i>
                            <span>View My Orders</span>
                        </a>
                        <a href="track_order.php" class="action-btn">
                            <i class="fas fa-truck"></i>
                            <span>Track an Order</span>
                        </a>
                        <a href="cart.php" class="action-btn">
                            <i class="fas fa-shopping-cart"></i>
                            <span>View Cart</span>
                        </a>
                        <a href="products.php" class="action-btn">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Browse Products</span>
                        </a>
                        <a href="account_info.php" class="action-btn">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                        <a href="help_center.php" class="action-btn">
                            <i class="fas fa-question-circle"></i>
                            <span>Get Help</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
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
                            <p class="newsletter-disclaimer">
                                By submitting your phone number, you agree to receive recurring automated promotional and personalized marketing text messages (e.g. cart reminders) from Gadget Garage at the cell number used when signing up. Consent is not a condition of any purchase. Reply HELP for help and STOP to cancel. Msg frequency varies. Msg & data rates may apply. <a href="terms_conditions.php">View Terms</a> & <a href="legal.php">Privacy</a>.
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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

        // Newsletter form
        document.getElementById('newsletterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = this.querySelector('.newsletter-input');
            const value = input.value.trim();

            if (value) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thank you!',
                    text: 'You have been subscribed to our newsletter.',
                    confirmButtonColor: '#667eea'
                });
                input.value = '';
            }
        });

        console.log('Account page loaded successfully');
    </script>
</body>

</html>