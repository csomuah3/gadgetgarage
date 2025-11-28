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
        .promo-banner2 .promo-banner-left,
        .promo-banner2 .promo-banner-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 0 0 auto;
        }

        .promo-banner-center,
        .promo-banner2 .promo-banner-center,
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

        /* Header Styles */
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
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo img {
            height: 60px !important;
            width: auto !important;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        .logo .garage {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 450px;
            margin: 0 40px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .search-input:focus {
            outline: none;
            border-color: #008060;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #008060;
            font-size: 1.1rem;
        }

        .search-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #008060, #006b4e);
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 200;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-50%) scale(1.05);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .vertical-separator {
            width: 1px;
            height: 40px;
            background: #e5e7eb;
            margin: 0 15px;
        }

        .tech-revival-section {
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: center;
            margin: 0 60px;
        }

        .tech-revival-icon {
            font-size: 1.2rem;
            color: #008060;
            transition: transform 0.3s ease;
        }

        .tech-revival-icon:hover {
            transform: rotate(15deg) scale(1.1);
        }

        .tech-revival-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            letter-spacing: 0.5px;
            line-height: 1.3;
        }

        .contact-number {
            font-size: 1.1rem;
            font-weight: 600;
            color: #008060;
            margin: 0;
            margin-top: 4px;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .header-icon {
            position: relative;
            padding: 14px;
            border-radius: 12px;
            transition: all 0.3s ease;
            color: #1f2937;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-icon i {
            font-size: 1.8rem;
            font-weight: 700;
            stroke-width: 2;
            -webkit-text-stroke: 1px currentColor;
        }

        .header-icon:hover {
            background: rgba(0, 128, 96, 0.15);
            color: #008060;
            transform: scale(1.15);
            box-shadow: 0 4px 12px rgba(0, 128, 96, 0.2);
        }

        .cart-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: linear-gradient(135deg, #006b4e, #008060);
            color: white;
            font-size: 1rem;
            font-weight: 100;
            padding: 5px 9px;
            border-radius: 14px;
            min-width: 26px;
            text-align: center;
            border: 3px solid white;
            box-shadow: none;
            line-height: 1;
        }

        .wishlist-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 1rem;
            font-weight: 100;
            padding: 5px 9px;
            border-radius: 14px;
            min-width: 26px;
            text-align: center;
            border: 3px solid white;
            box-shadow: none;
            line-height: 1;
        }

        .login-btn {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-1px);
            color: white;
        }

        .user-dropdown {
            position: relative;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #008060, #006b4e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 128, 96, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .user-avatar:hover {
            transform: scale(1.15);
            box-shadow: 0 5px 15px rgba(0, 128, 96, 0.5);
        }

        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(139, 95, 191, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
            padding: 15px 0;
            min-width: 220px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .dropdown-menu-custom.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .dropdown-item-custom:hover {
            background: rgba(139, 95, 191, 0.1);
            color: #008060;
            transform: translateX(3px);
        }

        .dropdown-item-custom i {
            font-size: 1rem;
            width: 18px;
            text-align: center;
        }

        .dropdown-divider-custom {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(139, 95, 191, 0.2), transparent);
            margin: 8px 0;
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toggle-switch {
            position: relative;
            width: 40px;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: linear-gradient(135deg, #008060, #006b4e);
        }

        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(20px);
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e2e8f0;
        }

        body.dark-mode .promo-banner {
            background: #0f1419 !important;
        }

        body.dark-mode .main-header {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            border-bottom-color: #4a5568;
        }

        body.dark-mode .logo,
        body.dark-mode .tech-revival-text,
        body.dark-mode .contact-number {
            color: #e2e8f0;
        }

        body.dark-mode .search-input {
            background: #374151;
            border-color: #4a5568;
            color: #e2e8f0;
        }

        body.dark-mode .search-input::placeholder {
            color: #9ca3af;
        }

        body.dark-mode .search-input:focus {
            background: #4a5568;
            border-color: #60a5fa;
        }

        body.dark-mode .categories-button {
            background: linear-gradient(135deg, #374151, #1f2937);
        }

        body.dark-mode .categories-button:hover {
            background: linear-gradient(135deg, #4a5568, #374151);
        }

        body.dark-mode .brands-dropdown {
            background: rgba(45, 55, 72, 0.95);
            border-color: rgba(74, 85, 104, 0.5);
        }

        body.dark-mode .brands-dropdown h4 {
            color: #e2e8f0;
        }

        body.dark-mode .brands-dropdown a {
            color: #cbd5e0;
        }

        body.dark-mode .brands-dropdown a:hover {
            background: rgba(74, 85, 104, 0.3);
            color: #60a5fa;
        }

        body.dark-mode .header-icon {
            color: #e2e8f0;
        }

        body.dark-mode .header-icon:hover {
            background: rgba(74, 85, 104, 0.3);
        }

        body.dark-mode .dropdown-menu-custom {
            background: rgba(45, 55, 72, 0.95);
            border-color: rgba(74, 85, 104, 0.5);
        }

        body.dark-mode .dropdown-item-custom {
            color: #cbd5e0;
        }

        body.dark-mode .dropdown-item-custom:hover {
            background: rgba(74, 85, 104, 0.3);
            color: #60a5fa;
        }

        body.dark-mode .main-nav {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            border-bottom-color: #4a5568;
        }

        body.dark-mode .nav-item {
            color: #e2e8f0;
        }

        body.dark-mode .nav-item:hover {
            background: rgba(74, 85, 104, 0.3);
            color: #60a5fa;
        }

        body.dark-mode .mega-dropdown {
            background: rgba(45, 55, 72, 0.95);
            border-color: rgba(74, 85, 104, 0.5);
        }

        body.dark-mode .dropdown-column h4 {
            color: #e2e8f0;
        }

        body.dark-mode .dropdown-column ul li a {
            color: #cbd5e0;
        }

        body.dark-mode .dropdown-column ul li a:hover {
            color: #60a5fa;
        }

        body.dark-mode .simple-dropdown {
            background: rgba(45, 55, 72, 0.95);
            border-color: rgba(74, 85, 104, 0.5);
        }

        body.dark-mode .simple-dropdown ul li a {
            color: #cbd5e0;
        }

        body.dark-mode .simple-dropdown ul li a:hover {
            background: rgba(74, 85, 104, 0.3);
            color: #60a5fa;
        }

        .language-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Main Navigation */
        .main-nav {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 0;
            position: sticky;
            top: 85px;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            width: 100%;
            padding-left: 280px;
        }

        .nav-item {
            color: #1f2937;
            text-decoration: none;
            font-weight: 600;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            border-radius: 8px;
            white-space: nowrap;
        }

        .nav-item:hover {
            background: rgba(0, 128, 96, 0.1);
            color: #008060;
            transform: translateY(-2px);
        }

        .nav-item.flash-deal {
            color: #ef4444;
            font-weight: 700;
            margin-left: auto;
            padding-right: 600px;
        }

        .nav-item.flash-deal:hover {
            color: #dc2626;
        }

        /* Blue Shop by Categories Button */
        .shop-categories-btn {
            position: relative;
        }

        .categories-button {
            background: #4f63d2;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .categories-button:hover {
            background: #3d4fd1;
        }

        .categories-button i:last-child {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .shop-categories-btn:hover .categories-button i:last-child {
            transform: rotate(180deg);
        }

        .brands-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            width: 280px;
            max-height: 350px;
            overflow-y: auto;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .brands-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .brands-dropdown h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .brands-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .brands-dropdown li {
            padding: 0;
        }

        .brands-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .brands-dropdown a:hover {
            background: #f3f4f6;
            color: #008060;
        }

        /* Navigation Dropdown Styles */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .simple-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 160px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .simple-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .simple-dropdown ul {
            list-style: none;
            padding: 8px 0;
            margin: 0;
        }

        .simple-dropdown ul li {
            padding: 0;
        }

        .simple-dropdown ul li a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .simple-dropdown ul li a:hover {
            background: #f3f4f6;
            color: #008060;
        }

        /* Mega Dropdown */
        .mega-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 800px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 32px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .mega-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }

        .dropdown-column h4 {
            color: #1f2937;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 8px;
        }

        .dropdown-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .dropdown-column ul li {
            margin-bottom: 8px;
        }

        .dropdown-column ul li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            transition: all 0.3s ease;
        }

        .dropdown-column ul li a:hover {
            color: #008060;
            transform: translateX(4px);
        }

        .dropdown-column ul li a i {
            color: #9ca3af;
            width: 16px;
        }

        .dropdown-column.featured {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
        }

        .featured-item {
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .featured-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .featured-text strong {
            font-size: 1rem;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .featured-text p {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .shop-now-btn {
            background: #008060;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .shop-now-btn:hover {
            background: #006b4e;
            color: white;
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
                    <form class="search-container" method="GET" action="../product_search_result.php">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="query" class="search-input" placeholder="Search phones, laptops, cameras..." required>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <!-- Tech Revival Section -->
                    <div class="tech-revival-section">
                        <i class="fas fa-recycle tech-revival-icon"></i>
                        <div>
                            <p class="tech-revival-text">Bring Retired Devices</p>
                            <p class="contact-number">055-138-7578</p>
                        </div>
                    </div>
                </div>

                <!-- User Actions - Far Right -->
                <div class="user-actions" style="display: flex; align-items: center; gap: 18px;">
                    <span style="color: #ddd; font-size: 1.5rem; margin: 0 5px;">|</span>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Wishlist Icon -->
                        <div class="header-icon">
                            <a href="../views/wishlist.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
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

                        <!-- User Avatar Dropdown -->
                        <div class="user-dropdown">
                            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>" onclick="toggleUserDropdown()">
                                <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="dropdown-menu-custom" id="userDropdownMenu">
                                <a href="account.php" class="dropdown-item-custom">
                                    <i class="fas fa-user"></i>
                                    <span data-translate="account">Account</span>
                                </a>
                                <a href="my_orders.php" class="dropdown-item-custom">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span data-translate="my_orders">My Orders</span>
                                </a>
                                <a href="../track_order.php" class="dropdown-item-custom">
                                    <i class="fas fa-truck"></i>
                                    <span data-translate="track_orders">Track Orders</span>
                                </a>
                                <a href="notifications.php" class="dropdown-item-custom">
                                    <i class="fas fa-bell"></i>
                                    <span>Notifications</span>
                                </a>
                                <button class="dropdown-item-custom" onclick="openProfilePictureModal()">
                                    <i class="fas fa-camera"></i>
                                    <span>Profile Picture</span>
                                </button>
                                <div class="dropdown-divider-custom"></div>
                                <div class="dropdown-item-custom">
                                    <i class="fas fa-globe"></i>
                                    <div class="language-selector">
                                        <span>Language</span>
                                        <select class="form-select form-select-sm" style="border: none; background: transparent; font-size: 0.8rem;" onchange="changeLanguage(this.value)">
                                            <option value="en">🇬🇧 EN</option>
                                            <option value="es">🇪🇸 ES</option>
                                            <option value="fr">🇫🇷 FR</option>
                                            <option value="de">🇩🇪 DE</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="dropdown-item-custom">
                                    <i class="fas fa-moon"></i>
                                    <div class="theme-toggle">
                                        <span>Dark Mode</span>
                                        <div class="toggle-switch" id="themeToggle" onclick="toggleTheme()">
                                            <div class="toggle-slider"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider-custom"></div>
                                <a href="../login/logout.php" class="dropdown-item-custom">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login Button -->
                        <a href="../login/login.php" class="login-btn">
                            <i class="fas fa-user"></i>
                            Login
                        </a>
                        <!-- Register Button -->
                        <a href="../login/register.php" class="login-btn" style="margin-left: 10px;">
                            <i class="fas fa-user-plus"></i>
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="container-fluid px-0">
            <div class="nav-menu">
                <!-- Shop by Brands Button -->
                <div class="shop-categories-btn" onmouseenter="showDropdown()" onmouseleave="hideDropdown()">
                    <button class="categories-button">
                        <i class="fas fa-tags"></i>
                        <span data-translate="shop_by_brands">SHOP BY BRANDS</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="brands-dropdown" id="shopDropdown">
                        <h4>All Brands</h4>
                        <ul>
                            <?php if (!empty($brands)): ?>
                                <?php foreach ($brands as $brand): ?>
                                    <li><a href="all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="all_product.php?brand=Apple"><i class="fas fa-tag"></i> Apple</a></li>
                                <li><a href="all_product.php?brand=Samsung"><i class="fas fa-tag"></i> Samsung</a></li>
                                <li><a href="all_product.php?brand=HP"><i class="fas fa-tag"></i> HP</a></li>
                                <li><a href="all_product.php?brand=Dell"><i class="fas fa-tag"></i> Dell</a></li>
                                <li><a href="all_product.php?brand=Sony"><i class="fas fa-tag"></i> Sony</a></li>
                                <li><a href="all_product.php?brand=Canon"><i class="fas fa-tag"></i> Canon</a></li>
                                <li><a href="all_product.php?brand=Nikon"><i class="fas fa-tag"></i> Nikon</a></li>
                                <li><a href="all_product.php?brand=Microsoft"><i class="fas fa-tag"></i> Microsoft</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <a href="../index.php" class="nav-item"><span data-translate="home">HOME</span></a>

                <!-- Shop Dropdown -->
                <div class="nav-dropdown" onmouseenter="showShopDropdown()" onmouseleave="hideShopDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="shop">SHOP</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="mega-dropdown" id="shopCategoryDropdown">
                        <div class="dropdown-content">
                            <div class="dropdown-column">
                                <h4>
                                    <a href="mobile_devices.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="mobile_devices">Mobile Devices</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
                                    <li><a href="all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="computing.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="computing">Computing</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
                                    <li><a href="all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="photography_video.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="photography_video">Photography & Video</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
                                    <li><a href="all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column featured">
                                <h4>Shop All</h4>
                                <div class="featured-item">
                                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
                                    <div class="featured-text">
                                        <strong>New Arrivals</strong>
                                        <p>Latest tech gadgets</p>
                                        <a href="all_product.php" class="shop-now-btn">Shop </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="repair_services.php" class="nav-item"><span data-translate="repair_studio">REPAIR STUDIO</span></a>
                <a href="device_drop.php" class="nav-item"><span data-translate="device_drop">DEVICE DROP</span></a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="more">MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                            <li><a href="terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
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

        // Timer functionality
        function updateTimer() {
            const timerElement = document.getElementById('promoTimer');
            if (timerElement) {
                const now = new Date().getTime();
                const nextDay = new Date();
                nextDay.setDate(nextDay.getDate() + 1);
                nextDay.setHours(0, 0, 0, 0);

                const distance = nextDay.getTime() - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60 * 60)) / 1000);

                timerElement.textContent = `${days}d:${hours}h:${minutes}m:${seconds}s`;
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Dropdown navigation functions with timeout delays
        let dropdownTimeout;
        let shopDropdownTimeout;
        let moreDropdownTimeout;
        let userDropdownTimeout;

        function showDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        function showShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                shopDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        function showMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) {
                clearTimeout(moreDropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) {
                clearTimeout(moreDropdownTimeout);
                moreDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        function showUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                userDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        }

        // Enhanced dropdown behavior
        document.addEventListener('DOMContentLoaded', function() {
            const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
            const brandsDropdown = document.getElementById('shopDropdown');

            if (shopCategoriesBtn && brandsDropdown) {
                shopCategoriesBtn.addEventListener('mouseenter', showDropdown);
                shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);
                brandsDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(dropdownTimeout);
                });
                brandsDropdown.addEventListener('mouseleave', hideDropdown);
            }

            const userAvatar = document.querySelector('.user-avatar');
            const userDropdown = document.getElementById('userDropdownMenu');

            if (userAvatar && userDropdown) {
                userAvatar.addEventListener('mouseenter', showUserDropdown);
                userAvatar.addEventListener('mouseleave', hideUserDropdown);
                userDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(userDropdownTimeout);
                });
                userDropdown.addEventListener('mouseleave', hideUserDropdown);
            }
        });

        // Language and theme functions
        function changeLanguage(lang) {
            // Language change functionality
            console.log('Language changed to:', lang);
        }

        function toggleTheme() {
            const toggle = document.getElementById('themeToggle');
            const body = document.body;
            if (toggle) {
                toggle.classList.toggle('active');
                body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', body.classList.contains('dark-mode'));
            }
        }

        function openProfilePictureModal() {
            // Profile picture modal functionality
            console.log('Open profile picture modal');
        }

        // Load dark mode preference
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode') === 'true';
            const toggle = document.getElementById('themeToggle');
            if (darkMode && toggle) {
                toggle.classList.add('active');
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</body>

</html>