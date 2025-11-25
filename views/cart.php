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

    // Debug cart total
    error_log("Cart debug - Customer ID: $customer_id, IP: $ip_address");
    error_log("Cart debug - Raw cart total: " . var_export($cart_total_raw, true));
    error_log("Cart debug - Final cart total: " . var_export($cart_total, true));
    error_log("Cart debug - Cart count: " . var_export($cart_count, true));

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
    <title>Shopping Cart - Gadget Garage</title>
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

        .cart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .cart-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.1"><polygon points="0,0 1000,0 1000,100 0,70"/></svg>') no-repeat bottom;
            background-size: cover;
        }

        .cart-header h1 {
            font-size: 3.5rem !important;
            font-weight: 800 !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 1rem !important;
        }

        .cart-header p {
            font-size: 1.4rem !important;
            opacity: 0.9;
            font-weight: 500;
        }

        .cart-item {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(99, 102, 241, 0.1);
            padding: 1.5rem;
        }

        .cart-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .product-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .cart-item:hover .product-image {
            transform: scale(1.05);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 700;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-danger {
            border: 3px solid #ff6b6b;
            color: #ff6b6b;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #ff6b6b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: inset 2px 2px 5px rgba(0,0,0,0.1);
        }

        .quantity-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .quantity-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: scale(1.15);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .quantity-input {
            width: 80px;
            text-align: center;
            border: 3px solid #e2e8f0;
            border-radius: 15px;
            padding: 12px 8px;
            font-size: 1.3rem;
            font-weight: 600;
            background: white;
            transition: all 0.3s ease;
        }

        .quantity-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .cart-summary {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            position: sticky;
            top: 120px;
            border: 2px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }

        .cart-summary:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.15);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .cart-summary h3 {
            font-size: 2.2rem !important;
            font-weight: 800 !important;
            color: #2d3748 !important;
            margin-bottom: 1.5rem !important;
            text-align: center;
            position: relative;
        }

        .cart-summary h3::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .empty-cart {
            text-align: center;
            padding: 5rem 3rem;
            background: linear-gradient(145deg, #f7fafc, #edf2f7);
            border-radius: 25px;
            margin: 2rem 0;
        }

        .empty-cart-icon {
            font-size: 5rem;
            color: #a0aec0;
            margin-bottom: 2rem;
            opacity: 0.7;
        }

        .empty-cart h3 {
            font-size: 2rem !important;
            font-weight: 700 !important;
            color: #4a5568 !important;
            margin-bottom: 1rem !important;
        }

        .empty-cart p {
            font-size: 1.3rem !important;
            color: #718096 !important;
            margin-bottom: 2rem !important;
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

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            padding: 1rem 0;
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f3f4f6;
            color: #000000;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Enhanced Cart Item Content Styling */
        .cart-item h5 {
            font-size: 1.6rem !important;
            font-weight: 700 !important;
            color: #2d3748 !important;
            margin-bottom: 0.8rem !important;
            line-height: 1.4 !important;
        }

        .cart-item .product-price {
            font-size: 1.5rem !important;
            font-weight: 800 !important;
            color: #667eea !important;
            margin-bottom: 0.5rem !important;
        }

        .cart-item .text-muted {
            font-size: 1.1rem !important;
            color: #718096 !important;
            font-weight: 500 !important;
        }

        .cart-item .badge {
            font-size: 1rem !important;
            padding: 0.6rem 1.2rem !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
        }

        .cart-item .card-text {
            font-size: 1.2rem !important;
            line-height: 1.6 !important;
            color: #4a5568 !important;
        }

        /* Cart Summary Styling */
        .cart-summary .list-group-item {
            font-size: 1.3rem !important;
            padding: 1rem 1.5rem !important;
            border: none !important;
            background: transparent !important;
        }

        .cart-summary .fw-bold {
            font-size: 1.6rem !important;
            font-weight: 800 !important;
            color: #2d3748 !important;
        }

        .cart-summary .text-success {
            font-size: 1.8rem !important;
            font-weight: 800 !important;
        }

        /* Page Title Styling */
        .container-fluid h1 {
            font-size: 3rem !important;
            font-weight: 800 !important;
            color: #2d3748 !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1) !important;
        }

        /* Enhanced Promo Code Section */
        .promo-section-redesign {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 2px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .promo-section-redesign:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.12);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .promo-section-redesign h4 {
            font-size: 1.8rem !important;
            font-weight: 700 !important;
            color: #2d3748 !important;
            margin-bottom: 1rem !important;
        }

        .promo-section-redesign .form-control {
            font-size: 1.2rem !important;
            padding: 15px 20px !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 15px !important;
            transition: all 0.3s ease !important;
        }

        .promo-section-redesign .form-control:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }

        .promo-section-redesign .btn {
            font-size: 1.1rem !important;
            padding: 15px 25px !important;
            border-radius: 15px !important;
            font-weight: 600 !important;
        }

        /* Enhanced Background Pattern */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(102, 126, 234, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(118, 75, 162, 0.02) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .promo-banner-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            padding: 12px 16px;
            text-align: center;
            margin-bottom: 12px;
            border: 1px solid #dee2e6;
        }

        .promo-banner-text {
            font-size: 14px;
            color: #495057;
            font-weight: 500;
            margin-right: 8px;
        }

        .promo-code-pill {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .promo-input-container {
            display: flex;
            gap: 0;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }

        .promo-input-redesign {
            flex: 1;
            border: none;
            padding: 12px 16px;
            font-size: 14px;
            background: #ffffff;
            color: #6c757d;
            outline: none;
        }

        .promo-input-redesign::placeholder {
            color: #adb5bd;
        }

        .promo-input-redesign:focus {
            outline: none;
            background: #ffffff;
        }

        .promo-apply-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 80px;
        }

        .promo-apply-btn:hover {
            background: #218838;
        }

        .promo-apply-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .applied-promo-redesign {
            background: #d1fae5;
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .promo-info-redesign {
            font-size: 0.9rem;
            font-weight: 600;
            color: #065f46;
        }

        .discount-row {
            border-top: 1px solid #e2e8f0;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }

        .alert-promo {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        @media (max-width: 768px) {
            .product-image {
                width: 80px;
                height: 80px;
            }

            .cart-header {
                padding: 2rem 0;
            }

            .cart-item {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
<script>console.log('JavaScript is working - body loaded');</script>
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

    <!-- Page Title -->
    <div class="container-fluid">
        <div class="text-center py-3">
            <h1 style="color: #1f2937; font-weight: 700; margin: 0;">Shopping Cart</h1>
        </div>
    </div>

    <div class="cart-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-shopping-cart me-3"></i>
                        Your Shopping Cart
                    </h1>
                    <p class="mb-0 fs-5">
                        <?php if ($cart_count > 0): ?>
                            You have <?php echo $cart_count; ?> item<?php echo $cart_count > 1 ? 's' : ''; ?> in your cart
                        <?php else: ?>
                            Your cart is currently empty
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="text-muted mb-3">Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="all_product.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Cart Items</h4>
                        <button type="button" class="btn btn-outline-danger" onclick="emptyCart()">
                            <i class="fas fa-trash me-2"></i>
                            Empty Cart
                        </button>
                    </div>

                    <div id="cartItemsContainer">
                        <?php foreach ($cart_items as $item):
                            // Create unique cart item ID combining product ID and condition
                            $condition = $item['condition_type'] ?? 'default';
                            $price = isset($item['final_price']) && $item['final_price'] > 0 ? $item['final_price'] : $item['product_price'];
                            // Clean the ID: remove spaces, dots, and any special characters
                            $clean_condition = preg_replace('/[^a-z0-9]/', '', strtolower($condition));
                            $clean_price = preg_replace('/[^0-9]/', '', $price);
                            $cart_item_id = 'cart_' . $item['p_id'] . '_' . $clean_condition . '_' . $clean_price;
                        ?>
                            <!-- Debug: Cart Item ID is <?php echo $cart_item_id; ?> -->
                            <div class="cart-item" data-product-id="<?php echo $item['p_id']; ?>" data-cart-item-id="<?php echo $cart_item_id; ?>">
                                <div class="row g-0 align-items-center p-3">
                                    <div class="col-auto">
                                        <img src="<?php echo get_product_image_url($item['product_image']); ?>"
                                             alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                             class="product-image">
                                    </div>
                                    <div class="col ms-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h5>
                                                <p class="text-muted mb-2 small"><?php echo htmlspecialchars($item['product_desc'] ?? ''); ?></p>
                                                <?php if (isset($item['condition_type'])): ?>
                                                <div class="condition-badge mb-2">
                                                    <span class="badge bg-secondary">Condition: <?php echo ucfirst($item['condition_type']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="fw-bold text-primary fs-5" id="unit-price-<?php echo $cart_item_id; ?>">
                                                    <?php
                                                    $price = (isset($item['final_price']) && $item['final_price'] > 0)
                                                        ? $item['final_price']
                                                        : $item['product_price'];
                                                    echo 'GH₵ ' . number_format($price, 2);
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-btn" onclick="decrementQuantityByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="quantity-input" value="<?php echo $item['qty']; ?>"
                                                           min="1" max="99" id="<?php echo $cart_item_id; ?>"
                                                           onchange="updateQuantityByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>, this.value)">
                                                    <button type="button" class="quantity-btn" onclick="incrementQuantityByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <div class="fw-bold fs-5 text-success mb-2" id="total-price-<?php echo $cart_item_id; ?>">
                                                    <?php
                                                    $price = (isset($item['final_price']) && $item['final_price'] > 0)
                                                        ? $item['final_price']
                                                        : $item['product_price'];
                                                    echo 'GH₵ ' . number_format($price * $item['qty'], 2);
                                                    ?>
                                                </div>
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                        onclick="removeFromCartByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>)">
                                                    <i class="fas fa-times"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4">
                        <a href="all_product.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Discount Code Section -->
                    <div class="promo-section-redesign">
                        <!-- Promotional Banner -->
                        <div class="promo-banner-card">
                            <span class="promo-banner-text">Get GH₵ 1,200 Off On Orders Above GH₵ 2,000! Use Code:</span>
                            <span class="promo-code-pill">BLACKFRIDAY20</span>
                        </div>

                        <!-- Input Section -->
                        <div class="promo-input-container">
                            <input type="text" id="promoCode" class="promo-input-redesign" placeholder="Enter discount code" maxlength="50">
                            <button type="button" id="applyPromoBtn" class="promo-apply-btn">Apply</button>
                        </div>

                        <div id="promoMessage" class="mt-2" style="display: none;"></div>
                        <div id="appliedPromo" class="applied-promo-redesign mt-2" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="promo-info-redesign">
                                    <i class="fas fa-tag text-success me-1"></i>
                                    <span id="promoCodeText"></span>
                                </span>
                                <button type="button" id="removePromoBtn" class="btn btn-sm btn-outline-danger">Remove</button>
                            </div>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <h4 class="mb-4">Order Summary</h4>

                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal (<?php echo $cart_count; ?> items):</span>
                            <span class="fw-bold" id="cartSubtotal">GH₵ <?php echo number_format($cart_total, 2); ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping:</span>
                            <span class="text-success fw-bold">FREE</span>
                        </div>

                        <!-- Discount Row (hidden by default) -->
                        <div class="d-flex justify-content-between mb-3 discount-row" id="discountRow" style="display: none;">
                            <span class="text-success">
                                <i class="fas fa-tag me-1"></i>
                                Discount:
                            </span>
                            <span class="text-success fw-bold" id="discountAmount">-GH₵ 0.00</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Total:</span>
                            <span class="fs-5 fw-bold text-primary" id="cartTotal">GH₵ <?php echo number_format($cart_total, 2); ?></span>
                        </div>

                        <?php if ($is_logged_in): ?>
                            <button type="button" class="btn btn-primary w-100 btn-lg" onclick="proceedToCheckout()">
                                <i class="fas fa-credit-card me-2"></i>
                                Proceed to Checkout
                            </button>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please <a href="login/user_login.php">login</a> to proceed with checkout.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/dark-mode.js"></script>
    <script src="../js/cart.js"></script>
    <script src="../js/promo-code.js"></script>

    <script>
    // Set base path for API calls using PHP to generate absolute URL
    (function() {
        // Use PHP to generate the correct base URL
        const baseUrl = '<?php 
            $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
            $host = $_SERVER["HTTP_HOST"] ?? "";
            $scriptPath = dirname($_SERVER["PHP_SELF"]);
            // Remove /views from path if it exists
            $scriptPath = str_replace("/views", "", $scriptPath);
            $scriptPath = rtrim($scriptPath, "/");
            echo $protocol . "://" . $host . $scriptPath;
        ?>';
        
        // Set ACTIONS_PATH to absolute URL
        window.ACTIONS_PATH = baseUrl + '/actions/';
        
        console.log('Base URL:', baseUrl);
        console.log('ACTIONS_PATH set to:', window.ACTIONS_PATH);
        console.log('Current pathname:', window.location.pathname);
    })();
    // User dropdown functionality - from login.php
    function toggleUserDropdown() {
        const dropdown = document.getElementById('userDropdownMenu');
        dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdownMenu');
        const avatar = document.querySelector('.user-avatar');

        if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Profile picture modal functionality
    function openProfilePictureModal() {
        // For now, show alert - will be replaced with actual modal
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Profile Picture',
                text: 'Profile picture upload functionality will be implemented',
                icon: 'info',
                confirmButtonColor: '#D19C97',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Profile picture upload functionality will be implemented');
        }
    }

    // Dropdown navigation functions
    let dropdownTimeout;

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
            // Clear any existing timeout
            clearTimeout(dropdownTimeout);
            // Set a delay before hiding to allow moving to dropdown
            dropdownTimeout = setTimeout(() => {
                dropdown.classList.remove('show');
            }, 300);
        }
    }

    // Shop Category Dropdown Functions
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

    // More Dropdown Functions
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

    // Timeout variables
    let shopDropdownTimeout;
    let moreDropdownTimeout;

    // Enhanced dropdown behavior
    document.addEventListener('DOMContentLoaded', function() {
        const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
        const dropdown = document.getElementById('shopDropdown');

        if (shopCategoriesBtn && dropdown) {
            // Show dropdown on button hover
            shopCategoriesBtn.addEventListener('mouseenter', showDropdown);

            // Hide dropdown when leaving button (with delay)
            shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);

            // Keep dropdown open when hovering over it
            dropdown.addEventListener('mouseenter', function() {
                clearTimeout(dropdownTimeout);
            });

            // Hide dropdown when leaving dropdown area
            dropdown.addEventListener('mouseleave', hideDropdown);
        }
    });

    // Promo timer countdown
    function startPromoTimer() {
        const timerElement = document.getElementById('promoTimer');
        if (!timerElement) return;

        function updateTimer() {
            // Set end date to 12 days from now
            const endDate = new Date();
            endDate.setDate(endDate.getDate() + 12);

            const now = new Date().getTime();
            const timeLeft = endDate.getTime() - now;

            if (timeLeft > 0) {
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                timerElement.textContent = `${days}d:${hours.toString().padStart(2, '0')}h:${minutes.toString().padStart(2, '0')}m:${seconds.toString().padStart(2, '0')}s`;
            } else {
                timerElement.textContent = "Deal Expired!";
            }
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    // Override cart action URLs for cart.php location
    const originalUpdateQuantityOnServer = updateQuantityOnServer;
    window.updateQuantityOnServer = function(productId, quantity) {
        console.log(`Updating server: Product ${productId} to quantity ${quantity}`);

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        // Use the actions path (use window.ACTIONS_PATH if available, otherwise default)
        const updateUrl = (window.ACTIONS_PATH || '../actions/') + 'update_quantity_action.php';
        
        console.log('Fetching from URL:', updateUrl);
        
        fetch(updateUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);

            if (data.success) {
                // Update cart badge and overall totals
                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(data.cart_count);
                } else {
                    console.warn('updateCartBadge function not found');
                }
                
                if (typeof updateCartTotals === 'function') {
                    updateCartTotals(data.cart_total);
                } else {
                    console.warn('updateCartTotals function not found');
                    // Fallback: manually update the totals
                    const cartSubtotal = document.getElementById('cartSubtotal');
                    const cartTotal = document.getElementById('cartTotal');
                    if (cartSubtotal) {
                        cartSubtotal.textContent = 'GH₵ ' + data.cart_total;
                    }
                    if (cartTotal) {
                        cartTotal.textContent = 'GH₵ ' + data.cart_total;
                    }
                }
                
                // Also update the quantity input value to reflect the change
                const quantityInput = document.getElementById(`qty-${productId}`);
                if (quantityInput) {
                    quantityInput.value = quantity;
                }
                
                console.log('Server update successful');

                // Show SweetAlert success (only if not already showing)
                if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                    Swal.fire({
                        title: 'Cart Updated',
                        text: 'Quantity updated successfully',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            } else {
                console.error('Server update failed:', data.message);
                if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                    Swal.fire({
                        title: 'Update Failed',
                        text: data.message || 'Failed to update cart',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Failed to update cart', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                url: updateUrl
            });
            
            // Show error to user
            const errorMessage = error.message || 'An error occurred while updating the cart';
            if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                Swal.fire({
                    title: 'Update Error',
                    text: errorMessage + '. Please try again or refresh the page.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else if (typeof showNotification === 'function') {
                showNotification('Network error - changes may not be saved', 'warning');
            }
        });
    };

    // Override remove from cart for SweetAlert
    // Quantity control functions
    window.incrementQuantityByCartId = function(cartItemId, productId) {
        const quantityInput = document.getElementById(cartItemId);
        if (quantityInput) {
            const currentQty = parseInt(quantityInput.value);
            const newQty = currentQty + 1;
            if (newQty <= 99) {
                quantityInput.value = newQty;
                updateQuantityByCartId(cartItemId, productId, newQty);
            }
        }
    };

    window.decrementQuantityByCartId = function(cartItemId, productId) {
        const quantityInput = document.getElementById(cartItemId);
        if (quantityInput) {
            const currentQty = parseInt(quantityInput.value);
            const newQty = currentQty - 1;
            if (newQty >= 1) {
                quantityInput.value = newQty;
                updateQuantityByCartId(cartItemId, productId, newQty);
            }
        }
    };

    window.updateQuantityByCartId = function(cartItemId, productId, quantity) {
        const qty = parseInt(quantity);
        if (qty < 1 || qty > 99) {
            console.log('Invalid quantity:', qty);
            return;
        }

        console.log('Updating quantity for cart item:', cartItemId, 'product:', productId, 'quantity:', qty);

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', qty);

        const updateUrl = (window.ACTIONS_PATH || '../actions/') + 'update_quantity_action.php';

        fetch(updateUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Update quantity response:', data);
            if (data.success) {
                // Update cart totals
                if (typeof updateCartTotals === 'function') {
                    updateCartTotals(data.cart_total);
                }
                // Update cart badge
                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(data.cart_count);
                }

                // Show success notification
                if (typeof showNotification === 'function') {
                    showNotification('Quantity updated successfully', 'success');
                }
            } else {
                console.error('Failed to update quantity:', data.message);
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Failed to update quantity', 'error');
                }
                // Reset input to original value
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error updating quantity:', error);
            if (typeof showNotification === 'function') {
                showNotification('Network error - please try again', 'error');
            }
            // Reset input to original value
            location.reload();
        });
    };

    window.removeFromCartByCartId = function(cartItemId, productId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Remove Item?',
                text: 'Are you sure you want to remove this item from your cart?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performRemoveFromCartByCartId(cartItemId, productId);
                }
            });
        } else {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                performRemoveFromCartByCartId(cartItemId, productId);
            }
        }
    };

    window.performRemoveFromCartByCartId = function(cartItemId, productId) {
        const formData = new FormData();
        formData.append('product_id', productId);

        const removeUrl = (window.ACTIONS_PATH || '../actions/') + 'remove_from_cart_action.php';
        console.log('Removing item from cart, URL:', removeUrl);

        fetch(removeUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Remove from cart response:', data);
            if (data.success) {
                if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                    Swal.fire({
                        title: 'Item Removed',
                        text: 'Item removed from cart successfully',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else if (typeof showNotification === 'function') {
                    showNotification('Item removed from cart', 'success');
                }

                // Remove the specific cart item from the DOM using the unique cart item ID
                const cartItem = document.querySelector(`[data-cart-item-id="${cartItemId}"]`);
                if (cartItem) {
                    cartItem.style.transition = 'all 0.3s ease';
                    cartItem.style.transform = 'translateX(-100%)';
                    cartItem.style.opacity = '0';

                    setTimeout(() => {
                        cartItem.remove();
                        if (typeof checkEmptyCart === 'function') {
                            checkEmptyCart();
                        }
                    }, 300);
                } else {
                    console.log('Cart item not found in DOM for cart ID:', cartItemId);
                    // Reload if DOM element not found
                    location.reload();
                }

                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(data.cart_count);
                } else {
                    // Fallback: manually update badge
                    const cartBadge = document.getElementById('cartBadge');
                    if (cartBadge) {
                        if (data.cart_count > 0) {
                            cartBadge.textContent = data.cart_count;
                            cartBadge.style.display = 'flex';
                        } else {
                            cartBadge.style.display = 'none';
                        }
                    }
                }
                
                if (typeof updateCartTotals === 'function') {
                    updateCartTotals(data.cart_total);
                } else {
                    // Fallback: manually update totals
                    const cartSubtotal = document.getElementById('cartSubtotal');
                    const cartTotal = document.getElementById('cartTotal');
                    if (cartSubtotal) {
                        cartSubtotal.textContent = 'GH₵ ' + data.cart_total;
                    }
                    if (cartTotal) {
                        cartTotal.textContent = 'GH₵ ' + data.cart_total;
                    }
                }
            } else {
                if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to remove item from cart',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Failed to remove item from cart', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error removing from cart:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                url: removeUrl
            });
            
            const errorMessage = error.message || 'An error occurred while removing the item';
            if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                Swal.fire({
                    title: 'Remove Error',
                    text: errorMessage + '. Please try again or refresh the page.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else if (typeof showNotification === 'function') {
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    };

    // Override empty cart function
    window.performEmptyCart = function() {
        const emptyUrl = (window.ACTIONS_PATH || '../actions/') + 'empty_cart_action.php';
        console.log('Emptying cart, URL:', emptyUrl);

        fetch(emptyUrl, {
            method: 'POST'
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Empty cart response:', data);
            if (data.success) {
                if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                    Swal.fire({
                        title: 'Cart Emptied',
                        text: 'Your cart has been emptied successfully',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else if (typeof showNotification === 'function') {
                    showNotification('Cart emptied successfully', 'success');
                }

                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(0);
                }
                if (typeof updateCartTotals === 'function') {
                    updateCartTotals('0.00');
                }

                // Hide cart items and show empty state
                const cartContainer = document.getElementById('cartItemsContainer');
                if (cartContainer) {
                    cartContainer.style.transition = 'all 0.5s ease';
                    cartContainer.style.opacity = '0';

                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    // If container not found, reload immediately
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            } else {
                if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to empty cart',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Failed to empty cart', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error emptying cart:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                url: emptyUrl
            });
            
            const errorMessage = error.message || 'An error occurred while emptying the cart';
            if (typeof Swal !== 'undefined' && !Swal.isLoading()) {
                Swal.fire({
                    title: 'Empty Cart Error',
                    text: errorMessage + '. Please try again or refresh the page.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else if (typeof showNotification === 'function') {
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    };

    // Helper functions for cart operations
    window.updateCartTotals = function(newTotal) {
        const cartSubtotal = document.getElementById('cartSubtotal');
        const cartTotal = document.getElementById('cartTotal');

        if (cartSubtotal) {
            cartSubtotal.textContent = `GH₵${parseFloat(newTotal || 0).toFixed(2)}`;
        }
        if (cartTotal) {
            cartTotal.textContent = `GH₵${parseFloat(newTotal || 0).toFixed(2)}`;
        }

        // Update any other total displays
        const totalDisplays = document.querySelectorAll('.cart-total-display, .total-amount');
        totalDisplays.forEach(display => {
            display.textContent = `GH₵${parseFloat(newTotal || 0).toFixed(2)}`;
        });
    };

    window.updateCartBadge = function(newCount) {
        const cartBadge = document.getElementById('cartBadge');
        if (cartBadge) {
            const count = newCount !== undefined ? newCount : <?php echo $cart_count; ?>;
            if (count > 0) {
                cartBadge.textContent = count;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }
        }
    };

    window.checkEmptyCart = function() {
        const cartItems = document.querySelectorAll('.cart-item[data-cart-item-id]');
        if (cartItems.length === 0) {
            // Show empty cart message
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    };

    window.emptyCart = function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Empty Cart?',
                text: 'Are you sure you want to remove all items from your cart?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, empty cart!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performEmptyCart();
                }
            });
        } else {
            if (confirm('Are you sure you want to remove all items from your cart?')) {
                performEmptyCart();
            }
        }
    };

    window.showNotification = function(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            const iconType = type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info';

            // Use toast for better UX
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: iconType,
                title: message
            });
        } else {
            // Fallback alert
            alert(message);
        }
    };

    // Initialize timer when page loads
    document.addEventListener('DOMContentLoaded', function() {
        startPromoTimer();

        // Update cart badge
        updateCartBadge();
    });

    // Timer functionality - from login.php
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
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerElement.innerHTML = days + "d:" +
                                     (hours < 10 ? "0" : "") + hours + "h:" +
                                     (minutes < 10 ? "0" : "") + minutes + "m:" +
                                     (seconds < 10 ? "0" : "") + seconds + "s";
        }
    }

    // Update timer every second
    setInterval(updateTimer, 1000);
    updateTimer(); // Initial call

    // Account page navigation
    function goToAccount() {
        window.location.href = 'my_orders.php';
    }

    // Language change functionality
    function changeLanguage(lang) {
        // Language change functionality can be implemented here
        console.log('Language changed to:', lang);
    }

    // Theme toggle functionality
    function toggleTheme() {
        const toggleSwitch = document.getElementById('themeToggle');
        const body = document.body;

        body.classList.toggle('dark-mode');
        toggleSwitch.classList.toggle('active');

        // Save theme preference to localStorage
        const isDarkMode = body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDarkMode);
    }

    // Load theme preference on page load
    document.addEventListener('DOMContentLoaded', function() {
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        const toggleSwitch = document.getElementById('themeToggle');

        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            if (toggleSwitch) {
                toggleSwitch.classList.add('active');
            }
        }
    });

    // Shop Dropdown Functions
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

    // More Dropdown Functions
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

    // Timeout variables
    let shopDropdownTimeout;
    let moreDropdownTimeout;
    </script>

    <style>
        /* Footer Styles */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 80px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo .garage {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .footer-description {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #000000;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #000000;
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
            font-size: 0.9rem;
            margin: 0;
        }

        .payment-methods {
            display: flex;
            gap: 8px;
            justify-content: end;
            align-items: center;
        }

        .payment-methods img {
            height: 25px;
            border-radius: 4px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .payment-methods img:hover {
            opacity: 1;
        }

        /* Live Chat Widget */
        .live-chat-widget {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
        }

        .chat-trigger {
            width: 60px;
            height: 60px;
            background: #000000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .chat-trigger:hover {
            background: #374151;
            transform: scale(1.1);
        }

        .chat-panel {
            position: absolute;
            bottom: 80px;
            left: 0;
            width: 350px;
            height: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            display: none;
            flex-direction: column;
        }

        .chat-panel.active {
            display: flex;
        }

        .chat-header {
            padding: 16px 20px;
            background: #000000;
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
        }

        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .chat-message {
            margin-bottom: 16px;
        }

        .chat-message.bot p {
            background: #f3f4f6;
            padding: 12px 16px;
            border-radius: 18px;
            margin: 0;
            color: #374151;
            font-size: 0.9rem;
        }

        .chat-footer {
            padding: 16px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 25px;
            outline: none;
            font-size: 0.9rem;
        }

        .chat-input:focus {
            border-color: #000000;
        }

        .chat-send {
            width: 40px;
            height: 40px;
            background: #000000;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .chat-send:hover {
            background: #374151;
        }

        @media (max-width: 768px) {
            .chat-panel {
                width: calc(100vw - 40px);
                height: 400px;
            }

            .live-chat-widget {
                bottom: 15px;
                left: 15px;
            }
        }
    </style>

    <script>
        // Live chat functionality
        function toggleLiveChat() {
            const chatPanel = document.getElementById('chatPanel');
            chatPanel.classList.toggle('active');
        }

        function sendChatMessage() {
            const chatInput = document.querySelector('.chat-input');
            const chatBody = document.querySelector('.chat-body');
            const message = chatInput.value.trim();

            if (message) {
                const userMessage = document.createElement('div');
                userMessage.className = 'chat-message user';
                userMessage.innerHTML = `<p style="background: #000000; color: white; padding: 12px 16px; border-radius: 18px; margin: 0; font-size: 0.9rem; text-align: right;">${message}</p>`;
                chatBody.appendChild(userMessage);

                chatInput.value = '';

                setTimeout(() => {
                    const botMessage = document.createElement('div');
                    botMessage.className = 'chat-message bot';
                    botMessage.innerHTML = `<p>I can help you with your cart! Need assistance with checkout or have questions about your items?</p>`;
                    chatBody.appendChild(botMessage);
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 1000);

                chatBody.scrollTop = chatBody.scrollHeight;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.querySelector('.chat-input');
            const chatSend = document.querySelector('.chat-send');

            if (chatInput && chatSend) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendChatMessage();
                    }
                });

                chatSend.addEventListener('click', sendChatMessage);
            }
        });

        // Promo Code Functionality
        let appliedPromo = null;
        const originalTotal = <?php echo $cart_total ?: 0; ?>;

        // Make originalTotal globally accessible for promo-code.js
        window.originalTotal = originalTotal;

        console.log('Cart total from PHP:', originalTotal);
        console.log('Type of originalTotal:', typeof originalTotal);
        console.log('Is originalTotal valid?', originalTotal > 0);
        console.log('PHP cart_total raw value: <?php echo var_export($cart_total, true); ?>');
        console.log('PHP cart_total_raw value: <?php echo var_export($cart_total_raw, true); ?>');
        console.log('PHP customer_id: <?php echo var_export($customer_id, true); ?>');
        console.log('PHP ip_address: <?php echo var_export($ip_address, true); ?>');

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded event fired');
            const promoInput = document.getElementById('promoCode');
            const applyBtn = document.getElementById('applyPromoBtn');
            const removeBtn = document.getElementById('removePromoBtn');

            console.log('Elements found:', {
                promoInput: !!promoInput,
                applyBtn: !!applyBtn,
                removeBtn: !!removeBtn
            });

            // Apply promo code on button click
            if (applyBtn) {
                applyBtn.addEventListener('click', applyPromoCode);
            }

            // Apply promo code on Enter key
            if (promoInput) {
                promoInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        applyPromoCode();
                    }
                });
            }

            // Remove promo code
            if (removeBtn) {
                removeBtn.addEventListener('click', removePromoCode);
            }
        });

        async function applyPromoCode() {
            console.log('ApplyPromoCode function called');

            const promoInput = document.getElementById('promoCode');
            const applyBtn = document.getElementById('applyPromoBtn');
            const promoMessage = document.getElementById('promoMessage');

            console.log('Elements found:', {
                promoInput: !!promoInput,
                applyBtn: !!applyBtn,
                promoMessage: !!promoMessage
            });

            const promoCode = promoInput.value.trim().toUpperCase();
            console.log('Promo code entered:', promoCode);

            if (!promoCode) {
                console.log('No promo code entered');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Missing Promo Code',
                        text: 'Please enter a promo code',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                } else {
                    showPromoMessage('Please enter a promo code', 'error');
                }
                return;
            }

            // Use cart total or default value - always allow promo code attempts
            console.log('Cart total from PHP:', originalTotal, 'Type:', typeof originalTotal);
            const useTotal = originalTotal && originalTotal > 0 ? originalTotal : 100; // Use 100 as fallback
            console.log('Using cart total for promo:', useTotal);

            // Disable button during processing
            applyBtn.disabled = true;
            applyBtn.textContent = 'Applying...';
            console.log('Button disabled, making request...');

            try {
                // Use the calculated total (with fallback)
                const requestData = {
                    promo_code: promoCode,
                    cart_total: useTotal
                };

                console.log('PROMO DEBUG: Request data being sent:', requestData);
                console.log('PROMO DEBUG: originalTotal from PHP:', originalTotal);
                console.log('PROMO DEBUG: useTotal calculated:', useTotal);
                console.log('Using total value:', useTotal);
                console.log('Promo code value:', promoCode);

                const jsonString = JSON.stringify(requestData);
                console.log('JSON string being sent:', jsonString);
                console.log('JSON string length:', jsonString.length);

                // Validate JSON before sending
                try {
                    const testParse = JSON.parse(jsonString);
                    console.log('JSON validation successful:', testParse);
                } catch (e) {
                    console.error('JSON validation failed:', e);
                    throw new Error('Invalid JSON being generated');
                }

                // Send POST request to validate promo code
                const requestData = {
                    promo_code: promoCode,
                    cart_total: useTotal
                };

                console.log('Making POST request to validate promo code with data:', requestData);

                const response = await fetch('../actions/validate_promo_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                console.log('Response received:', {
                    status: response.status,
                    statusText: response.statusText,
                    ok: response.ok,
                    headers: Object.fromEntries(response.headers.entries())
                });

                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                let data;
                try {
                    data = JSON.parse(responseText);
                    console.log('Parsed data:', data);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response: ' + responseText);
                }

                if (data.success) {
                    console.log('PROMO DEBUG: Server response data:', data);
                    console.log('PROMO DEBUG: discount_amount:', data.discount_amount);
                    console.log('PROMO DEBUG: new_total:', data.new_total);
                    console.log('PROMO DEBUG: original_total:', data.original_total);

                    // Store applied promo data
                    appliedPromo = data;

                    // Show SweetAlert success
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Promo Code Applied!',
                            text: data.message,
                            icon: 'success',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        showPromoMessage(data.message, 'success');
                    }

                    // Hide promo input section and show applied promo
                    document.querySelector('.promo-input-container').style.display = 'none';
                    document.getElementById('appliedPromo').style.display = 'block';
                    document.getElementById('promoCodeText').textContent = data.promo_code;

                    // Show discount row and update totals
                    updateOrderSummary(data);

                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Invalid Promo Code',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        showPromoMessage(data.message, 'error');
                    }
                }
            } catch (error) {
                console.error('Promo code error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    showPromoMessage('An error occurred. Please try again.', 'error');
                }
            } finally {
                // Re-enable button
                applyBtn.disabled = false;
                applyBtn.textContent = 'Apply';
            }
        }

        function removePromoCode() {
            // Clear applied promo
            appliedPromo = null;

            // Reset UI
            document.querySelector('.promo-input-container').style.display = 'flex';
            document.getElementById('appliedPromo').style.display = 'none';
            document.getElementById('promoCode').value = '';
            document.getElementById('promoMessage').style.display = 'none';

            // Hide discount row and reset totals
            document.getElementById('discountRow').style.display = 'none';
            const cartTotalElement = document.getElementById('cartTotal');
            if (cartTotalElement) {
                cartTotalElement.textContent = 'GH₵ ' + originalTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }

        function showPromoMessage(message, type) {
            const promoMessage = document.getElementById('promoMessage');
            promoMessage.textContent = message;
            promoMessage.className = `alert-promo alert-${type === 'success' ? 'success' : 'error'}`;
            promoMessage.style.display = 'block';

            // Hide message after 5 seconds for success, 7 seconds for error
            setTimeout(() => {
                if (type === 'error') {
                    promoMessage.style.display = 'none';
                }
            }, type === 'success' ? 5000 : 7000);
        }

        function updateOrderSummary(promoData) {
            console.log('UpdateOrderSummary called with data:', promoData);

            // Show discount row
            const discountRow = document.getElementById('discountRow');
            discountRow.style.display = 'flex';

            // Update discount details (fixed amount, not percentage)
            console.log('Updating discount amount to:', promoData.discount_amount);
            console.log('Updating new total to:', promoData.new_total);
            console.log('Original total:', promoData.original_total);

            // Update discount amount display
            const discountAmountElement = document.getElementById('discountAmount');
            if (discountAmountElement) {
                discountAmountElement.textContent = '-GH₵ ' + promoData.discount_amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            // Update cart total (final total after discount)
            const cartTotalElement = document.getElementById('cartTotal');
            if (cartTotalElement) {
                cartTotalElement.textContent = 'GH₵ ' + promoData.new_total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            // Keep subtotal as original total (don't change it)
            const cartSubtotalElement = document.getElementById('cartSubtotal');
            if (cartSubtotalElement && promoData.original_total) {
                cartSubtotalElement.textContent = 'GH₵ ' + promoData.original_total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            // Add some celebration animation
            discountRow.style.animation = 'fadeInUp 0.5s ease-out';
        }

        // Proceed to checkout function
        function proceedToCheckout() {
            // Check if promo is in localStorage (set by promo-code.js)
            const storedPromo = localStorage.getItem('appliedPromo');
            if (!storedPromo && appliedPromo) {
                // Fallback: if localStorage doesn't have it but appliedPromo variable does, store it
                localStorage.setItem('appliedPromo', JSON.stringify(appliedPromo));
            }
            console.log('Proceeding to checkout. Promo in localStorage:', storedPromo);
            window.location.href = 'checkout.php';
        }

        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);

        // Manual test function for debugging - call from browser console
        window.testPromoManual = async function(testTotal = null) {
            console.log('=== MANUAL PROMO TEST ===');
            const total = testTotal || originalTotal;
            const cartTotalValue = parseFloat(total) || 0;
            console.log('Testing with total:', total, 'Parsed:', cartTotalValue);

            if (cartTotalValue <= 0) {
                console.error('Cannot test with invalid cart total:', cartTotalValue);
                return;
            }

            const testData = {
                promo_code: 'BLACKFRIDAY20',
                cart_total: cartTotalValue
            };

            console.log('Test data:', testData);
            console.log('JSON string:', JSON.stringify(testData));

            try {
                console.log('Testing promo code with data:', testData);

                const response = await fetch('../actions/validate_promo_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testData)
                });

                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);

                try {
                    const data = JSON.parse(responseText);
                    console.log('Parsed response:', data);
                    return data;
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    return { error: 'Invalid JSON response', raw: responseText };
                }
            } catch (e) {
                console.error('Request failed:', e);
                return { error: e.message };
            }
        };
    </script>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="footer-brand">
                            <div class="footer-logo" style="margin-bottom: 16px;">
                                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                                     alt="Gadget Garage"
                                     style="height: 35px; width: auto; object-fit: contain;">
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
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Shop</h5>
                        <ul class="footer-links">
                            <li><a href="all_product.php?category=phones">Smartphones</a></li>
                            <li><a href="all_product.php?category=laptops">Laptops</a></li>
                            <li><a href="all_product.php?category=ipads">Tablets</a></li>
                            <li><a href="all_product.php?category=cameras">Cameras</a></li>
                            <li><a href="all_product.php?category=video">Video Equipment</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Services</h5>
                        <ul class="footer-links">
                            <li><a href="repair_services.php">Device Repair</a></li>
                            <li><a href="#">Tech Support</a></li>
                            <li><a href="#">Data Recovery</a></li>
                            <li><a href="#">Setup Services</a></li>
                            <li><a href="#">Warranty</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Company</h5>
                        <ul class="footer-links">
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Contact</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">Press</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Support</h5>
                        <ul class="footer-links">
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">Shipping Info</a></li>
                            <li><a href="#">Returns</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="payment-methods">
                                <img src="<?php echo generate_placeholder_url('VISA', '40x25'); ?>" alt="Visa">
                                <img src="<?php echo generate_placeholder_url('MC', '40x25'); ?>" alt="Mastercard">
                                <img src="<?php echo generate_placeholder_url('AMEX', '40x25'); ?>" alt="American Express">
                                <img src="<?php echo generate_placeholder_url('GPAY', '40x25'); ?>" alt="Google Pay">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Live Chat Widget -->
    <div class="live-chat-widget" id="liveChatWidget">
        <div class="chat-trigger" onclick="toggleLiveChat()">
            <i class="fas fa-comments"></i>
        </div>
        <div class="chat-panel" id="chatPanel">
            <div class="chat-header">
                <h4>Live Chat</h4>
                <button class="chat-close" onclick="toggleLiveChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chat-body">
                <div class="chat-message bot">
                    <p>Need help with your cart? I'm here to assist with your shopping experience!</p>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" class="chat-input" placeholder="Ask about your cart items...">
                <button class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

</body>
</html>