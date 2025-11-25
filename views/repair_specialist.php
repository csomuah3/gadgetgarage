<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');

// Check login status
$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Get wishlist count
$wishlist_count = 0;
if ($is_logged_in) {
    $wishlist_count = get_wishlist_count_ctr($customer_id);
}

// Get issue details from URL
$issue_id = isset($_GET['issue_id']) ? intval($_GET['issue_id']) : 0;
$issue_name = isset($_GET['issue_name']) ? $_GET['issue_name'] : '';

if ($issue_id <= 0) {
    header('Location: repair_services.php');
    exit;
}

try {
    $db = new db_connection();
    $db->db_connect();

    // Get issue details
    $issue = $db->db_fetch_one("SELECT * FROM repair_issue_types WHERE issue_id = $issue_id");

    if (!$issue) {
        header('Location: repair_services.php');
        exit;
    }

    // Get specialists for this issue
    $specialists_query = "SELECT s.*, si.issue_id
                         FROM specialists s
                         JOIN specialist_issues si ON s.specialist_id = si.specialist_id
                         WHERE si.issue_id = $issue_id AND s.is_available = 1
                         ORDER BY s.rating DESC, s.experience_years DESC";
    $specialists = $db->db_fetch_all($specialists_query);

} catch (Exception $e) {
    $error_message = "Unable to load specialists. Please try again later.";
    $specialists = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Specialist - <?php echo htmlspecialchars($issue_name); ?> - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="includes/header-styles.css" rel="stylesheet">
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

        /* Promo Banner */
        .promo-banner,
        .promo-banner2 {
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

        /* Animated Background */
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

        .btn-back {
            background: linear-gradient(135deg, #6b7280, #9ca3af);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #4b5563, #6b7280);
            color: white;
            transform: translateY(-1px);
        }

        /* Progress Steps */
        .hero-section {
            padding: 2rem 0 1rem;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin: 2rem 0;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-weight: 500;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .step.completed .step-number {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #047857, #059669);
            color: white;
        }

        .step-separator {
            width: 3rem;
            height: 2px;
            background: #e5e7eb;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
            position: relative;
            z-index: 10;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #047857;
            margin-bottom: 1rem;
            text-align: center;
        }

        .issue-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 0 auto 3rem;
            max-width: 600px;
            text-align: center;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.05);
        }

        .specialists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .specialist-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .specialist-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #10b981, #34d399);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .specialist-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.15);
        }

        .specialist-card:hover::before {
            transform: scaleX(1);
        }

        .specialist-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #34d399);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            font-weight: 700;
            color: white;
        }

        .specialist-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #047857;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .specialist-specialization {
            color: #6b7280;
            text-align: center;
            margin-bottom: 1.5rem;
            font-style: italic;
        }

        .specialist-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 1.5rem;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #047857;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 2px;
            margin-bottom: 1rem;
        }

        .star {
            color: #fbbf24;
            font-size: 1rem;
        }

        .star.empty {
            color: #e5e7eb;
        }

        .continue-btn {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            position: fixed;
            bottom: 30px;
            right: 30px;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            display: none;
        }

        .continue-btn:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
        }

        .continue-btn.show {
            display: block;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .no-specialists {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .no-specialists i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .progress-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .step-separator {
                display: none;
            }

            .specialists-grid {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .continue-btn {
                bottom: 20px;
                right: 20px;
                left: 20px;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Background Decorations -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>

    <!-- Promo Banner -->
    <div class="promo-banner2">
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

    <!-- Back to Issues Button -->
    <div class="container mt-3">
        <a href="repair_services.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Issues
        </a>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <span>Issue Type</span>
                </div>
                <div class="step-separator"></div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <span>Specialist</span>
                </div>
                <div class="step-separator"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <span>Schedule</span>
                </div>
            </div>

            <!-- Issue Info -->
            <div class="issue-info">
                <h2 style="color: #047857; margin-bottom: 0.5rem;">
                    <i class="<?php echo htmlspecialchars($issue['icon_class']); ?> me-2"></i>
                    <?php echo htmlspecialchars($issue['issue_name']); ?>
                </h2>
                <p style="color: #6b7280; margin: 0;">
                    <?php echo htmlspecialchars($issue['issue_description']); ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Specialists Section -->
    <section class="main-content">
        <div class="container">
            <h1 class="section-title">Choose Your Specialist</h1>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($specialists)): ?>
                <div class="no-specialists">
                    <i class="fas fa-user-times"></i>
                    <h4>No Specialists Available</h4>
                    <p>We're sorry, but there are no specialists available for this issue type at the moment.<br>
                       Please contact us directly or try again later.</p>
                    <a href="repair_services.php" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-arrow-left"></i> Choose Different Issue
                    </a>
                </div>
            <?php else: ?>
                <div class="specialists-grid">
                    <?php foreach ($specialists as $specialist): ?>
                        <div class="specialist-card" onclick="selectSpecialist(<?php echo $specialist['specialist_id']; ?>, '<?php echo htmlspecialchars($specialist['specialist_name']); ?>')">
                            <div class="specialist-avatar">
                                <?php echo strtoupper(substr($specialist['specialist_name'], 0, 2)); ?>
                            </div>

                            <h3 class="specialist-name"><?php echo htmlspecialchars($specialist['specialist_name']); ?></h3>
                            <p class="specialist-specialization"><?php echo htmlspecialchars($specialist['specialization']); ?></p>

                            <div class="specialist-stats">
                                <div class="stat">
                                    <div class="stat-value"><?php echo $specialist['experience_years']; ?>+</div>
                                    <div class="stat-label">Years Experience</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?php echo number_format($specialist['rating'], 1); ?></div>
                                    <div class="stat-label">Rating</div>
                                </div>
                            </div>

                            <div class="rating-stars">
                                <?php
                                $rating = $specialist['rating'];
                                $fullStars = floor($rating);
                                $halfStar = ($rating - $fullStars) >= 0.5;
                                $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

                                for ($i = 0; $i < $fullStars; $i++) {
                                    echo '<i class="fas fa-star star"></i>';
                                }
                                if ($halfStar) {
                                    echo '<i class="fas fa-star-half-alt star"></i>';
                                }
                                for ($i = 0; $i < $emptyStars; $i++) {
                                    echo '<i class="fas fa-star star empty"></i>';
                                }
                                ?>
                            </div>

                            <?php if ($specialist['specialist_email']): ?>
                                <p style="text-align: center; color: #6b7280; font-size: 0.9rem; margin: 0;">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?php echo htmlspecialchars($specialist['specialist_email']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Continue Button -->
    <button class="continue-btn" id="continueBtn" onclick="proceedToSchedule()">
        Continue
        <i class="fas fa-arrow-right ms-2"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSpecialist = null;
        let selectedSpecialistName = '';

        function selectSpecialist(specialistId, specialistName) {
            // Remove previous selection
            document.querySelectorAll('.specialist-card').forEach(card => {
                card.classList.remove('selected');
                card.style.background = '';
                card.style.border = '';
            });

            // Select current specialist
            event.currentTarget.classList.add('selected');
            event.currentTarget.style.background = 'linear-gradient(135deg, #ecfdf5, #d1fae5)';
            event.currentTarget.style.border = '2px solid #10b981';

            selectedSpecialist = specialistId;
            selectedSpecialistName = specialistName;

            // Show continue button
            document.getElementById('continueBtn').classList.add('show');
        }

        function proceedToSchedule() {
            if (selectedSpecialist) {
                const urlParams = new URLSearchParams(window.location.search);
                const issueId = urlParams.get('issue_id');
                const issueName = urlParams.get('issue_name');

                window.location.href = `repair_schedule.php?issue_id=${issueId}&issue_name=${encodeURIComponent(issueName)}&specialist_id=${selectedSpecialist}&specialist_name=${encodeURIComponent(selectedSpecialistName)}`;
            }
        }

        // Add hover effects
        document.querySelectorAll('.specialist-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('selected')) {
                    this.style.background = 'linear-gradient(135deg, #f8fafc, #f1f5f9)';
                }
            });

            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('selected')) {
                    this.style.background = '';
                }
            });
        });
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
</body>
</html>