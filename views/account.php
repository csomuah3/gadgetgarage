<?php
try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');
    require_once(__DIR__ . '/../helpers/image_helper.php');

    $is_logged_in = check_login();

    // Redirect to login if not logged in
    if (!$is_logged_in) {
        header("Location: login/login.php");
        exit;
    }

    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Get cart items for both logged-in and guest users
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);

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
    <style>
        /* Import Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
            color: #1a202c;
            line-height: 1.6;
        }

        /* Account Layout */
        .account-container {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }

        /* Sidebar Navigation */
        .account-sidebar {
            width: 280px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 40px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-nav {
            padding: 0 20px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            margin-bottom: 8px;
            color: #4a5568;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item:hover {
            background: #f7fafc;
            color: #2d3748;
            text-decoration: none;
        }

        .nav-item.active {
            background: #edf2f7;
            color: #2d3748;
            font-weight: 600;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #000000;
            border-radius: 0 4px 4px 0;
        }

        .nav-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        .nav-item.sign-out {
            margin-top: auto;
            margin-bottom: 20px;
            background: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
        }

        .nav-item.sign-out:hover {
            background: #fed7d7;
            color: #c53030;
        }

        /* Main Content */
        .account-main {
            flex: 1;
            margin-left: 280px;
            padding: 40px 60px;
        }

        /* Welcome Section */
        .welcome-section {
            margin-bottom: 50px;
        }

        .welcome-title {
            font-size: 48px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0;
            letter-spacing: -0.5px;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 40px;
        }

        /* Section Styles */
        .content-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            padding: 30px 30px 20px 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-content {
            padding: 30px;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background: #f8fafc;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 120px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            line-height: 1.4;
        }

        .product-price {
            font-size: 12px;
            color: #718096;
            margin: 5px 0 0 0;
        }

        /* View All Button */
        .view-all-btn {
            width: 100%;
            padding: 16px 24px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: #4a5568;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
            color: #2d3748;
            text-decoration: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a5568;
        }

        .empty-state p {
            font-size: 14px;
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .account-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .account-main {
                margin-left: 0;
                padding: 20px;
            }

            .welcome-title {
                font-size: 32px;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .loading {
            animation: pulse 1.5s infinite;
        }

        /* Promotional Banner Styles */
        .promo-banner2 {
            background: #001f3f !important;
            color: white;
            padding: 6px 15px;
            text-align: center;
            font-size: 1rem;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            max-width: 100%;
        }

        .promo-banner2 .promo-banner-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 0 0 auto;
        }

        .promo-banner2 .promo-banner-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex: 1;
        }

        .promo-banner2 i {
            font-size: 1rem;
        }

        .promo-banner2 .promo-text {
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .promo-banner2 .promo-timer {
            background: transparent;
            padding: 0;
            border-radius: 0;
            font-size: 1.3rem;
            font-weight: 500;
            margin: 0;
            border: none;
        }

        .promo-banner2 .promo-shop-link {
            color: white;
            text-decoration: underline;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.3s ease;
            font-size: 1.2rem;
            flex: 0 0 auto;
        }

        .promo-banner2 .promo-shop-link:hover {
            opacity: 0.8;
        }

        /* Main Header Styles */
        .main-header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 38px;
            z-index: 1000;
            padding: 15px 0;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo img {
            height: 45px;
            width: auto;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .header-icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(139, 95, 191, 0.1);
            color: #8b5fbf;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .header-icon:hover {
            background: rgba(139, 95, 191, 0.2);
            transform: scale(1.1);
        }

        .cart-badge, .wishlist-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Update account container to account for fixed header */
        .account-container {
            margin-top: 20px;
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
            <span class="promo-text">BLACK FRIDAY DEALS STOREWIDE! SHOP AMAZING DISCOUNTS!</span>
            <span class="promo-timer" id="promoTimer">12d:00h:00m:00s</span>
        </div>
        <a href="all_product.php" class="promo-shop-link">Shop Now</a>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-container">
            <!-- Logo - Left -->
            <a href="index.php" class="logo">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                     alt="Gadget Garage"
                     style="height: 45px;">
            </a>

            <!-- User Actions - Right -->
            <div class="user-actions">
                <!-- Wishlist Icon -->
                <div class="header-icon">
                    <a href="wishlist.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-heart"></i>
                        <span class="wishlist-badge" id="wishlistBadge" style="display: none;">0</span>
                    </a>
                </div>

                <!-- Cart Icon -->
                <div class="header-icon">
                    <a href="cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                        <?php else: ?>
                            <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- User Dropdown -->
                <div class="header-icon dropdown" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white;">
                    <button class="dropdown-toggle" data-bs-toggle="dropdown" style="background: none; border: none; color: white; cursor: pointer;">
                        <i class="fas fa-user"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="account.php">My Account</a></li>
                        <li><a class="dropdown-item" href="my_orders.php">My Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../login/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="account-container">
        <!-- Sidebar Navigation -->
        <nav class="account-sidebar">
            <div class="sidebar-nav">
                <a href="account.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                <a href="my_orders.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>My orders</span>
                </a>

                <a href="#" class="nav-item" onclick="showSection('my-info')">
                    <i class="fas fa-edit"></i>
                    <span>My Info</span>
                </a>

                <a href="notifications.php" class="nav-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>

                <a href="#" class="nav-item" onclick="showSection('help')">
                    <i class="fas fa-question-circle"></i>
                    <span>Help Center</span>
                </a>

                <div style="margin-top: 100px;">
                    <a href="login/logout.php" class="nav-item sign-out">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="account-main">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">HI, <?php echo strtoupper($first_name); ?></h1>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Wishlist Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Wishlist</h2>
                    </div>
                    <div class="section-content">
                        <div id="wishlist-content">
                            <!-- Placeholder for wishlist items -->
                            <div class="empty-state">
                                <i class="fas fa-heart"></i>
                                <h4>No items in wishlist</h4>
                                <p>Start shopping to add items to your wishlist</p>
                            </div>
                        </div>
                        <a href="wishlist.php" class="view-all-btn">
                            <span>View all</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Viewed Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Viewed</h2>
                    </div>
                    <div class="section-content">
                        <div id="viewed-content">
                            <!-- Placeholder for recently viewed items -->
                            <div class="empty-state">
                                <i class="fas fa-eye"></i>
                                <h4>No recently viewed items</h4>
                                <p>Browse products to see them here</p>
                            </div>
                        </div>
                        <a href="#" class="view-all-btn">
                            <span>View all</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Navigation functionality
        function showSection(sectionId) {
            // This would handle showing different sections
            // For now, just updating active state
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.nav-item').classList.add('active');
        }

        // Load wishlist and viewed items on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadWishlistItems();
            loadViewedItems();
        });

        function loadWishlistItems() {
            // Placeholder function to load wishlist items
            // This would connect to your wishlist API/database
            const wishlistContent = document.getElementById('wishlist-content');

            // For now, keeping the empty state
            // You can integrate with your existing wishlist functionality
        }

        function loadViewedItems() {
            // Placeholder function to load recently viewed items
            // This would typically use localStorage or database
            const viewedContent = document.getElementById('viewed-content');

            // For now, keeping the empty state
            // You can integrate with your existing product viewing tracking
        }

        // Countdown timer for promotional banner
        function startPromoTimer() {
            const timer = document.getElementById('promoTimer');
            if (!timer) return;

            // Set timer to end in 12 days from now
            const endDate = new Date();
            endDate.setDate(endDate.getDate() + 12);

            function updateTimer() {
                const now = new Date().getTime();
                const distance = endDate.getTime() - now;

                if (distance > 0) {
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    timer.textContent = `${days}d:${String(hours).padStart(2, '0')}h:${String(minutes).padStart(2, '0')}m:${String(seconds).padStart(2, '0')}s`;
                } else {
                    timer.textContent = "00d:00h:00m:00s";
                    clearInterval(timerInterval);
                }
            }

            const timerInterval = setInterval(updateTimer, 1000);
            updateTimer(); // Run immediately
        }

        // Start timer when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadWishlistItems();
            loadViewedItems();
            startPromoTimer();
        });
    </script>
</body>
</html>