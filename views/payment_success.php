<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');

// Check if user is logged in
if (!check_login()) {
    header('Location: ../login/login.php');
    exit();
}

$is_logged_in = true;
$is_admin = check_admin();
$customer_id = $_SESSION['user_id'];
$order_id = isset($_GET['order']) ? intval($_GET['order']) : null;
$reference = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '';

// Get cart count
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Get wishlist count
$wishlist_count = get_wishlist_count_ctr($customer_id);

// Get order details
$order_details = null;
if ($order_id) {
    $order_details = get_order_by_id_ctr($order_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #1a1a1a;
        }

        /* Promo Banner */
        .promo-banner {
            background: #001f3f !important;
            color: white;
            text-align: center;
            padding: 8px 0;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            border-bottom: 1px solid #0066cc;
        }

        .promo-banner .container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .promo-text {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .countdown-timer {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .countdown-timer .time-unit {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            min-width: 24px;
            text-align: center;
        }

        /* Main Header */
        .main-header {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            text-decoration: none;
            flex-shrink: 0;
        }

        .logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .search-container {
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
        }

        .search-form {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input {
            width: 100%;
            padding: 10px 45px 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: scale(1.05);
        }

        .tech-revival {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tech-revival:hover {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            transform: translateY(-1px);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .action-item {
            position: relative;
            color: #374151;
            text-decoration: none;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            min-width: 50px;
        }

        .action-item:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .action-item i {
            font-size: 18px;
            margin-bottom: 2px;
        }

        .action-item span {
            font-size: 11px;
            font-weight: 500;
        }

        .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            padding: 8px;
            margin-top: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            z-index: 1000;
        }

        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: block;
            padding: 8px 12px;
            color: #374151;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .dropdown-item:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        /* Navigation */
        .main-nav {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0;
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0;
        }

        .nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 30px;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 15px 0;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-link:hover {
            color: #3b82f6;
        }

        .nav-link.has-dropdown::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: 4px;
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .nav-item:hover .nav-link.has-dropdown::after {
            transform: rotate(180deg);
        }

        .mega-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
            min-width: 600px;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(-10px);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            z-index: 1000;
        }

        .nav-item:hover .mega-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .mega-menu-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .mega-menu-category h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mega-menu-category h4 i {
            color: #3b82f6;
        }

        .mega-menu-category ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mega-menu-category li {
            margin-bottom: 6px;
        }

        .mega-menu-category a {
            color: #6b7280;
            text-decoration: none;
            padding: 4px 0;
            display: block;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .mega-menu-category a:hover {
            color: #3b82f6;
            padding-left: 8px;
        }

        .brands-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 250px;
            padding: 15px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            z-index: 1000;
        }

        .nav-item:hover .brands-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .brands-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .brand-item {
            padding: 8px 12px;
            color: #374151;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-item:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .flash-deals {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            margin-left: auto;
            position: relative;
            overflow: hidden;
        }

        .flash-deals::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .flash-deals:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: white;
            transform: translateY(-1px);
        }

        .flash-deals:hover::before {
            left: 100%;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            background: #f3f4f6;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6b7280;
        }

        .dark-mode-toggle:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .success-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }

        .success-box {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 3px solid #10b981;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.2);
        }

        .success-icon {
            font-size: 100px;
            color: #059669;
            margin-bottom: 20px;
            animation: bounce 1.5s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .success-title {
            font-size: 2.5rem;
            color: #065f46;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .success-subtitle {
            font-size: 1.2rem;
            color: #047857;
            margin-bottom: 40px;
        }

        .order-details {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .detail-value {
            color: #6b7280;
            font-family: monospace;
        }

        .btn-action {
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 15px;
            transition: all 0.3s ease;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 123, 255, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary-custom {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary-custom:hover {
            background: #f9fafb;
            color: #374151;
            text-decoration: none;
        }

        .confirmation-badge {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            padding: 20px;
            border-radius: 12px;
            color: #1e40af;
            margin-bottom: 30px;
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .logo-container img {
            max-height: 60px;
        }
    </style>
</head>

<body>
    <!-- Promo Banner -->
    <div class="promo-banner">
        <div class="container">
            <div class="promo-text">
                <i class="fas fa-bolt"></i>
                <span>LIMITED TIME OFFER</span>
            </div>
            <div class="countdown-timer" id="promoCountdown">
                <span>Ends in:</span>
                <div class="time-unit" id="hours">00</div>
                <span>:</span>
                <div class="time-unit" id="minutes">00</div>
                <span>:</span>
                <div class="time-unit" id="seconds">00</div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-container">
            <div class="header-top">
                <!-- Logo -->
                <a href="../index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png" alt="Gadget Garage">
                </a>

                <!-- Search Bar -->
                <div class="search-container">
                    <form class="search-form" action="../product_search_result.php" method="GET">
                        <input type="text" name="search" class="search-input" placeholder="Search for gadgets, phones, laptops..." required>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Tech Revival -->
                <a href="../repair_services.php" class="tech-revival">
                    <i class="fas fa-tools"></i>
                    Tech Revival
                </a>

                <!-- User Actions -->
                <div class="user-actions">
                    <?php if ($is_logged_in): ?>
                        <!-- Wishlist -->
                        <a href="../views/wishlist.php" class="action-item">
                            <i class="fas fa-heart"></i>
                            <span>Wishlist</span>
                            <?php if ($wishlist_count > 0): ?>
                                <span class="badge"><?php echo $wishlist_count; ?></span>
                            <?php endif; ?>
                        </a>

                        <!-- Cart -->
                        <a href="../views/cart.php" class="action-item">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart</span>
                            <?php if ($cart_count > 0): ?>
                                <span class="badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>

                        <!-- Account Dropdown -->
                        <div class="dropdown">
                            <a href="#" class="action-item">
                                <i class="fas fa-user"></i>
                                <span>Account</span>
                            </a>
                            <div class="dropdown-menu">
                                <a href="../views/account.php" class="dropdown-item">My Profile</a>
                                <a href="../views/my_orders.php" class="dropdown-item">My Orders</a>
                                <a href="../views/notifications.php" class="dropdown-item">Notifications</a>
                                <?php if ($is_admin): ?>
                                    <a href="../admin/dashboard.php" class="dropdown-item">Admin Panel</a>
                                <?php endif; ?>
                                <hr style="margin: 5px 0; border: none; border-top: 1px solid #e5e7eb;">
                                <a href="../login/logout.php" class="dropdown-item">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login/Register -->
                        <a href="../login/login.php" class="action-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="../views/register.php" class="action-item">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    <?php endif; ?>

                    <!-- Dark Mode Toggle -->
                    <button class="dark-mode-toggle" onclick="toggleDarkMode()" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="main-nav">
            <div class="container nav-container">
                <ul class="nav-links">
                    <!-- Shop Categories -->
                    <li class="nav-item">
                        <a href="#" class="nav-link has-dropdown">
                            <i class="fas fa-th-large"></i>
                            Shop Categories
                        </a>
                        <div class="mega-menu">
                            <div class="mega-menu-grid">
                                <div class="mega-menu-category">
                                    <h4><i class="fas fa-mobile-alt"></i> Mobile Devices</h4>
                                    <ul>
                                        <li><a href="../views/mobile_devices.php?category=smartphones">Smartphones</a></li>
                                        <li><a href="../views/mobile_devices.php?category=tablets">Tablets</a></li>
                                        <li><a href="../views/mobile_devices.php?category=smartwatches">Smartwatches</a></li>
                                        <li><a href="../views/mobile_devices.php?category=accessories">Phone Accessories</a></li>
                                    </ul>
                                </div>
                                <div class="mega-menu-category">
                                    <h4><i class="fas fa-laptop"></i> Computing</h4>
                                    <ul>
                                        <li><a href="../views/computing.php?category=laptops">Laptops</a></li>
                                        <li><a href="../views/computing.php?category=desktops">Desktops</a></li>
                                        <li><a href="../views/computing.php?category=components">Components</a></li>
                                        <li><a href="../views/computing.php?category=peripherals">Peripherals</a></li>
                                    </ul>
                                </div>
                                <div class="mega-menu-category">
                                    <h4><i class="fas fa-camera"></i> Photography</h4>
                                    <ul>
                                        <li><a href="../views/photography_video.php?category=cameras">Cameras</a></li>
                                        <li><a href="../views/photography_video.php?category=lenses">Lenses</a></li>
                                        <li><a href="../views/photography_video.php?category=tripods">Tripods</a></li>
                                        <li><a href="../views/photography_video.php?category=accessories">Photo Accessories</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Brands -->
                    <li class="nav-item">
                        <a href="#" class="nav-link has-dropdown">
                            <i class="fas fa-tags"></i>
                            Brands
                        </a>
                        <div class="brands-dropdown">
                            <div class="brands-grid">
                                <a href="../views/all_product.php?brand=apple" class="brand-item">
                                    <i class="fab fa-apple"></i> Apple
                                </a>
                                <a href="../views/all_product.php?brand=samsung" class="brand-item">
                                    <i class="fas fa-mobile"></i> Samsung
                                </a>
                                <a href="../views/all_product.php?brand=sony" class="brand-item">
                                    <i class="fas fa-tv"></i> Sony
                                </a>
                                <a href="../views/all_product.php?brand=canon" class="brand-item">
                                    <i class="fas fa-camera"></i> Canon
                                </a>
                                <a href="../views/all_product.php?brand=hp" class="brand-item">
                                    <i class="fas fa-laptop"></i> HP
                                </a>
                                <a href="../views/all_product.php?brand=dell" class="brand-item">
                                    <i class="fas fa-desktop"></i> Dell
                                </a>
                            </div>
                        </div>
                    </li>

                    <!-- Regular Navigation Links -->
                    <li class="nav-item">
                        <a href="../views/all_product.php" class="nav-link">
                            <i class="fas fa-shopping-bag"></i>
                            All Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../views/device_drop.php" class="nav-link">
                            <i class="fas fa-recycle"></i>
                            Device Drop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../views/contact.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            Contact
                        </a>
                    </li>
                </ul>

                <!-- Flash Deals -->
                <a href="../views/flash_deals.php" class="flash-deals">
                    <i class="fas fa-bolt"></i>
                    Flash Deals
                </a>
            </div>
        </nav>
    </header>
    <div class="success-container">
        <div class="success-box">
            <div class="logo-container">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                     alt="Gadget Garage" class="img-fluid">
            </div>

            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-subtitle">Your order has been confirmed and will be processed shortly</p>

            <div class="confirmation-badge">
                <i class="fas fa-shield-check me-2"></i>
                <strong>Payment Confirmed</strong><br>
                Thank you for shopping with Gadget Garage! Your payment was processed securely via PayStack.
            </div>

            <div class="order-details">
                <h4 class="mb-3"><i class="fas fa-receipt me-2"></i>Order Details</h4>

                <?php if ($order_details): ?>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-hashtag me-2"></i>Order ID</span>
                        <span class="detail-value">#<?= htmlspecialchars($order_details['order_id'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-file-invoice me-2"></i>Invoice Number</span>
                        <span class="detail-value"><?= htmlspecialchars($order_details['invoice_no'] ?? 'N/A') ?></span>
                    </div>
                <?php endif; ?>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-credit-card me-2"></i>Payment Reference</span>
                    <span class="detail-value"><?= htmlspecialchars($reference) ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-calendar me-2"></i>Order Date</span>
                    <span class="detail-value"><?= date('F j, Y \a\t g:i A') ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-info-circle me-2"></i>Status</span>
                    <span class="detail-value">
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-check me-1"></i>Paid
                        </span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-wallet me-2"></i>Payment Method</span>
                    <span class="detail-value">PayStack</span>
                </div>
            </div>

            <div class="text-center">
                <a href="my_orders.php" class="btn-action btn-primary-custom">
                    <i class="fas fa-box me-2"></i>View My Orders
                </a>
                <a href="../index.php" class="btn-action btn-secondary-custom">
                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                </a>
            </div>

            <div class="mt-4 text-muted">
                <small>
                    <i class="fas fa-lock me-1"></i>
                    Payment secured by PayStack |
                    <i class="fas fa-truck me-1"></i>
                    Delivery within 3-5 business days
                </small>
            </div>
        </div>
    </div>

    <!-- Confetti Animation -->
    <script>
        // Create confetti animation
        function createConfetti() {
            const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'];
            const confettiCount = 60;

            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        width: 12px;
                        height: 12px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        left: ${Math.random() * 100}%;
                        top: -20px;
                        border-radius: 50%;
                        z-index: 9999;
                        pointer-events: none;
                    `;

                    document.body.appendChild(confetti);

                    const duration = 3000 + Math.random() * 1000;
                    const startTime = Date.now();

                    function animateConfetti() {
                        const elapsed = Date.now() - startTime;
                        const progress = elapsed / duration;

                        if (progress < 1) {
                            const top = progress * (window.innerHeight + 50);
                            const wobble = Math.sin(progress * 8) * 30;

                            confetti.style.top = top + 'px';
                            confetti.style.left = `calc(${confetti.style.left} + ${wobble}px)`;
                            confetti.style.opacity = 1 - progress;
                            confetti.style.transform = `rotate(${progress * 720}deg)`;

                            requestAnimationFrame(animateConfetti);
                        } else {
                            confetti.remove();
                        }
                    }

                    animateConfetti();
                }, i * 50);
            }
        }

        // Start confetti when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(createConfetti, 500);

            // Show sweet alert success message
            setTimeout(function() {
                Swal.fire({
                    title: 'Payment Successful!',
                    text: 'Your order has been confirmed and will be processed shortly.',
                    icon: 'success',
                    confirmButtonText: 'Awesome!',
                    confirmButtonColor: '#28a745',
                    timer: 8000,
                    timerProgressBar: true,
                    showClass: {
                        popup: 'animate__animated animate__bounceIn'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__bounceOut'
                    }
                });
            }, 1000);
        });

        // Clean up cart-related localStorage items
        localStorage.removeItem('appliedPromo');

        // Optional: Store order data in localStorage for future reference
        if (typeof(Storage) !== "undefined") {
            const orderData = {
                order_id: '<?= $order_id ?>',
                reference: '<?= $reference ?>',
                date: '<?= date('Y-m-d H:i:s') ?>',
                status: 'completed'
            };
            localStorage.setItem('lastOrder', JSON.stringify(orderData));
        }
    </script>

    <script>
        // Promo Banner Countdown Timer
        function updateCountdown() {
            const now = new Date().getTime();
            const endOfDay = new Date();
            endOfDay.setHours(23, 59, 59, 999);
            const distance = endOfDay.getTime() - now;

            if (distance > 0) {
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            }
        }

        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call

        // Dark Mode Toggle
        function toggleDarkMode() {
            const body = document.body;
            const isDarkMode = body.getAttribute('data-theme') === 'dark';

            if (isDarkMode) {
                body.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.setAttribute('data-theme', 'dark');
            }
        });

        // Search functionality
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const searchInput = document.querySelector('.search-input');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>