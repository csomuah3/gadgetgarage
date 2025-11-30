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

    // Get store credits for logged-in users
    $store_credit_balance = 0;
    $available_store_credits = [];
    if ($is_logged_in && $customer_id) {
        try {
            require_once(__DIR__ . '/../helpers/store_credit_helper.php');
            $storeCreditHelper = new StoreCreditHelper();
            $store_credit_balance = $storeCreditHelper->getTotalAvailableCredit($customer_id);
            $available_store_credits = $storeCreditHelper->getAvailableCredits($customer_id);
            
            // Debug logging
            error_log("Store Credits Debug - Customer ID: $customer_id");
            error_log("Store Credits Debug - Balance: " . $store_credit_balance);
            error_log("Store Credits Debug - Available Credits Count: " . count($available_store_credits));
            if (!empty($available_store_credits)) {
                error_log("Store Credits Debug - Credits Data: " . print_r($available_store_credits, true));
            }
        } catch (Exception $e) {
            error_log("Failed to load store credits: " . $e->getMessage());
            error_log("Store Credits Error Trace: " . $e->getTraceAsString());
        }
    } else {
        error_log("Store Credits Debug - Not logged in or no customer ID. Logged in: " . ($is_logged_in ? 'yes' : 'no') . ", Customer ID: " . ($customer_id ?? 'null'));
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

    <!-- Reusable Header CSS -->
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">

    <link href="css/dark-mode.css" rel="stylesheet">
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
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.15);
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
            background: rgba(59, 130, 246, 0.1);
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
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.2), transparent);
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
            background: linear-gradient(135deg, #2563EB 0%, #1E3A5F 100%);
            color: white;
            padding: 60px 0 50px;
            margin-top: 0;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
        }

        .cart-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.4;
        }

        .cart-header h1 {
            font-size: 2.8rem;
            font-weight: 800;
            margin: 0 0 12px 0;
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .cart-header h1 i {
            font-size: 2.5rem;
            margin-right: 15px;
            opacity: 0.95;
        }

        .cart-header p {
            font-size: 1.25rem;
            opacity: 0.92;
            position: relative;
            z-index: 1;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .cart-item {
            background: #ffffff;
            border-radius: 0;
            box-shadow: none;
            margin-bottom: 0;
            overflow: visible;
            transition: none;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            padding: 2rem 0;
        }

        .cart-item:hover {
            transform: none;
            box-shadow: none;
            border-color: #e5e7eb;
        }

        .product-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 0;
            border: none;
            box-shadow: none;
            transition: none;
        }

        .cart-item:hover .product-image {
            transform: none;
        }

        .btn-primary {
            background: #4285F4;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: none;
        }

        .btn-primary:hover {
            background: #3367d6;
            transform: none;
            box-shadow: none;
        }

        .btn-outline-danger {
            border: 1px solid #d1d5db;
            color: #6b7280;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 400;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #f3f4f6;
            color: #374151;
            transform: none;
            box-shadow: none;
            border-color: #9ca3af;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .quantity-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            font-weight: 400;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: none;
        }

        .quantity-btn:hover {
            background: #e5e7eb;
            transform: none;
            box-shadow: none;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px;
            font-size: 1rem;
            font-weight: 400;
            background: white;
            transition: all 0.3s ease;
        }

        .quantity-input:focus {
            border-color: #4285F4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
            outline: none;
        }

        .cart-summary {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            position: sticky;
            top: 100px;
            border: 1px solid #e5e7eb;
            transition: none;
        }

        .cart-summary:hover {
            transform: none;
            box-shadow: none;
            border-color: #e5e7eb;
        }

        .cart-summary h3 {
            font-size: 1.25rem !important;
            font-weight: 600 !important;
            color: #1f2937 !important;
            margin-bottom: 1.5rem !important;
            text-align: left;
            position: relative;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.75rem;
        }

        .cart-summary h3::after {
            display: none;
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
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
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
            color: #3b82f6 !important;
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
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1) !important;
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
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .promo-section-redesign .btn {
            font-size: 1.1rem !important;
            padding: 15px 25px !important;
            border-radius: 15px !important;
            font-weight: 600 !important;
        }

        /* Store Credits Box (Similar to Discount Box) */
        .store-credits-box {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.3s ease;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .store-credits-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.12);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .store-credits-box.disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        .store-credits-header {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .store-credits-content {
            padding: 0;
        }

        .store-credits-label {
            font-size: 0.85rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .store-credits-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
        }

        .store-credits-checkbox-container {
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .store-credits-checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .store-credits-checkbox-container input[type="checkbox"]:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .store-credits-checkbox-container label {
            cursor: pointer;
            font-size: 1rem;
        }

        .store-credits-checkbox-container input[type="checkbox"]:disabled + label {
            cursor: not-allowed;
            opacity: 0.6;
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

        .promo-code-display {
            margin-bottom: 16px;
        }

        .promo-code-display strong {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: 0.5px;
        }

        .discount-code-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .promo-input-container {
            display: flex;
            gap: 0;
            border-radius: 0;
            overflow: visible;
            border: none;
            border-bottom: 1px solid #9ca3af;
            align-items: center;
            padding-bottom: 4px;
        }

        .promo-input-redesign {
            flex: 1;
            border: none;
            padding: 8px 0;
            font-size: 14px;
            background: transparent;
            color: #1a1a1a;
            outline: none;
            border-bottom: none;
        }

        .promo-input-redesign::placeholder {
            color: transparent;
        }

        .promo-input-redesign:focus {
            outline: none;
            background: transparent;
        }

        .promo-apply-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px 12px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .promo-apply-btn:hover {
            background: #1d4ed8;
        }

        .promo-apply-btn i {
            font-size: 12px;
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

        /* Footer Styles */
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

        .footer-logo .garage {
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
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

        /* Newsletter Signup Section */
        .newsletter-signup-section {
            background: transparent;
            padding: 0;
            text-align: left;
            max-width: 100%;
            height: fit-content;
        }

        .newsletter-title {
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .newsletter-form {
            display: flex;
            width: 100%;
            margin: 0 0 15px 0;
            gap: 0;
            border-radius: 50px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .newsletter-input {
            flex: 1;
            padding: 14px 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1a1a1a;
            background: #e5e7eb;
        }

        .newsletter-input::placeholder {
            color: #6b7280;
        }

        .newsletter-submit-btn {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border: none;
            background: #9ca3af;
            color: #ffffff;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            padding: 0;
        }

        .newsletter-submit-btn:hover {
            background: #6b7280;
            transform: scale(1.05);
        }

        .newsletter-disclaimer {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 8px 0 0 0;
            text-align: left;
        }

        .newsletter-disclaimer a {
            color: #2563EB;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .newsletter-disclaimer a:hover {
            color: #1d4ed8;
        }

        @media (max-width: 991px) {
            .newsletter-signup-section {
                margin-top: 20px;
            }
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }

        .scroll-to-top.show {
            display: flex;
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            background: linear-gradient(135deg, #2563EB, #1E3A5F);
            transform: translateX(-50%) translateY(-3px);
            box-shadow: 0 6px 16px rgba(30, 58, 95, 0.4);
        }

        .scroll-to-top:active {
            transform: translateX(-50%) translateY(-1px);
        }

        @media (max-width: 768px) {
            .scroll-to-top {
                bottom: 20px;
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }
    </style>
</head>

<body class="page-background">
    <!-- Reusable Header Component -->
    <?php include '../includes/header.php'; ?>

    <!-- Cart Content -->
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
                                            class="product-image"
                                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4='; this.onerror=null;">
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
                                                    <button type="button" class="quantity-btn" onclick="decrementQuantity(<?php echo $item['p_id']; ?>, '<?php echo $cart_item_id; ?>')">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="quantity-input" value="<?php echo $item['qty']; ?>"
                                                        min="1" max="99"
                                                        data-product-id="<?php echo $item['p_id']; ?>"
                                                        data-cart-item-id="<?php echo $cart_item_id; ?>"
                                                        id="qty-<?php echo $cart_item_id; ?>"
                                                        onchange="updateQuantity(<?php echo $item['p_id']; ?>, this.value, '<?php echo $cart_item_id; ?>')">
                                                    <button type="button" class="quantity-btn" onclick="incrementQuantity(<?php echo $item['p_id']; ?>, '<?php echo $cart_item_id; ?>')">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <div class="fw-bold fs-5 text-success mb-2" id="total-price-<?php echo $cart_item_id; ?>"
                                                    data-unit-price="<?php
                                                                        $price = (isset($item['final_price']) && $item['final_price'] > 0)
                                                                            ? $item['final_price']
                                                                            : $item['product_price'];
                                                                        echo $price;
                                                                        ?>">
                                                    <?php
                                                    $price = (isset($item['final_price']) && $item['final_price'] > 0)
                                                        ? $item['final_price']
                                                        : $item['product_price'];
                                                    echo 'GH₵ ' . number_format($price * $item['qty'], 2);
                                                    ?>
                                                </div>
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="removeItem(<?php echo $item['p_id']; ?>, '<?php echo $cart_item_id; ?>')">
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
                        <!-- Promo Code Display -->
                        <div class="promo-code-display">
                            <strong>BLACKFRIDAY20</strong>
                        </div>

                        <!-- Discount Code Label -->
                        <div class="discount-code-label">DISCOUNT CODE</div>

                        <!-- Input Section -->
                        <div class="promo-input-container">
                            <input type="text" id="promoCode" class="promo-input-redesign" placeholder="" maxlength="50">
                            <button type="button" id="applyPromoBtn" class="promo-apply-btn">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>

                        <div id="promoMessage" class="mt-2" style="display: none;"></div>
                    </div>

                    <!-- Store Credits Section (Separate Box) -->
                    <?php if ($is_logged_in && $store_credit_balance > 0): ?>
                    <div class="store-credits-box" id="storeCreditsBox">
                        <div class="store-credits-header">
                            <i class="fas fa-credit-card me-2"></i>
                            <strong>STORE CREDITS</strong>
                        </div>
                        <div class="store-credits-content">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <div class="store-credits-label">Available Balance</div>
                                    <div class="store-credits-amount" id="storeCreditBalanceDisplay">
                                        GH₵ <?php echo number_format($store_credit_balance, 2); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check store-credits-checkbox-container">
                                <input class="form-check-input" type="checkbox" id="applyStoreCredits" style="cursor: pointer;">
                                <label class="form-check-label" for="applyStoreCredits" style="cursor: pointer;">
                                    <strong>Apply Store Credits to this order</strong>
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2" id="storeCreditsInfo">
                                <i class="fas fa-info-circle me-1"></i>
                                <span id="storeCreditsInfoText">
                                    You can use up to GH₵ <?php 
                                    $max_usable = max(0, $store_credit_balance - 500);
                                    $display_max = min($max_usable, $cart_total);
                                    echo number_format($display_max, 2); 
                                    ?> from your store credits (GH₵500 minimum reserve)
                                </span>
                            </small>
                            <div id="storeCreditsExclusiveMessage" class="mt-2 text-warning" style="display: none;">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <small>Discount code is active. Store credits cannot be used.</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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

                        <!-- Store Credits Applied Row (hidden by default) -->
                        <?php if ($is_logged_in && $store_credit_balance > 0): ?>
                        <div class="d-flex justify-content-between mb-3 store-credits-row" id="storeCreditsRow" style="display: none;">
                            <span class="text-success">
                                <i class="fas fa-credit-card me-1"></i>
                                Store Credits Applied:
                            </span>
                            <span class="text-success fw-bold" id="storeCreditsAmount">-GH₵ 0.00</span>
                        </div>
                        <?php endif; ?>

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

    <!-- AI Recommendations Section -->
    <?php include '../includes/ai_recommendations_section.php'; ?>

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/dark-mode.js"></script>
    <script src="new_cart_script.js"></script>
    <script src="../js/promo-code.js"></script>

    <script>
        // Store Credits Functions - Define before DOMContentLoaded
        function handleStoreCreditsToggle(isChecked) {
            const storeCreditsRow = document.getElementById('storeCreditsRow');
            const storeCreditsAmount = document.getElementById('storeCreditsAmount');
            const cartTotal = document.getElementById('cartTotal');
            const cartSubtotal = document.getElementById('cartSubtotal');
            const storeCreditBalanceDisplay = document.getElementById('storeCreditBalanceDisplay');
            const discountRow = document.getElementById('discountRow');
            const promoCodeInput = document.getElementById('promoCode');
            const applyPromoBtn = document.getElementById('applyPromoBtn');
            const storeCreditsExclusiveMessage = document.getElementById('storeCreditsExclusiveMessage');
            const storeCreditsInfoText = document.getElementById('storeCreditsInfoText');

            if (!storeCreditsRow || !storeCreditsAmount || !cartTotal || !cartSubtotal) {
                return;
            }

            // Check if discount is active
            const isDiscountActive = discountRow && discountRow.style.display !== 'none' && discountRow.style.display !== '';

            if (isChecked) {
                // If discount is active, prevent applying store credits
                if (isDiscountActive) {
                    const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
                    if (applyStoreCreditsCheckbox) {
                        applyStoreCreditsCheckbox.checked = false;
                    }
                    if (storeCreditsExclusiveMessage) {
                        storeCreditsExclusiveMessage.style.display = 'block';
                    }
                    return;
                }

                // Hide exclusive message if shown
                if (storeCreditsExclusiveMessage) {
                    storeCreditsExclusiveMessage.style.display = 'none';
                }

                // Disable discount code input
                if (promoCodeInput) {
                    promoCodeInput.disabled = true;
                    promoCodeInput.placeholder = 'Store credits applied';
                }
                if (applyPromoBtn) {
                    applyPromoBtn.disabled = true;
                }

                // Get values
                const subtotalText = cartSubtotal.textContent.replace(/[^0-9.]/g, '');
                const subtotal = parseFloat(subtotalText) || 0;
                const creditBalanceText = storeCreditBalanceDisplay?.textContent.replace(/[^0-9.]/g, '') || '0';
                const creditBalance = parseFloat(creditBalanceText) || 0;

                // Apply 500 minimum reserve rule: max usable = balance - 500
                const maxUsableCredits = Math.max(0, creditBalance - 500);
                // Credits to apply = minimum of (max usable, cart subtotal)
                const creditsToApply = Math.min(maxUsableCredits, subtotal);

                if (creditsToApply > 0) {
                    storeCreditsAmount.textContent = '-GH₵ ' + creditsToApply.toFixed(2);
                    storeCreditsRow.style.display = 'flex';
                    
                    // Update total (subtract store credits only, discount should not be active)
                    const discountAmountText = discountRow && discountRow.style.display !== 'none' 
                        ? document.getElementById('discountAmount')?.textContent.replace(/[^0-9.]/g, '') || '0'
                        : '0';
                    const discountAmount = parseFloat(discountAmountText) || 0;
                    const newTotal = Math.max(0, subtotal - discountAmount - creditsToApply);
                    cartTotal.textContent = 'GH₵ ' + newTotal.toFixed(2);
                    
                    // Update session storage
                    sessionStorage.setItem('appliedStoreCredits', creditsToApply.toFixed(2));
                } else {
                    // Not enough credits (would leave less than 500)
                    const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
                    if (applyStoreCreditsCheckbox) {
                        applyStoreCreditsCheckbox.checked = false;
                    }
                    alert('You cannot use store credits that would leave less than GH₵500 in your balance.');
                    return;
                }
            } else {
                // Remove store credits
                storeCreditsRow.style.display = 'none';
                storeCreditsAmount.textContent = '-GH₵ 0.00';
                
                // Re-enable discount code input
                if (promoCodeInput) {
                    promoCodeInput.disabled = false;
                    promoCodeInput.placeholder = '';
                }
                if (applyPromoBtn) {
                    applyPromoBtn.disabled = false;
                }

                // Recalculate total (subtract discount if any)
                const subtotalText = cartSubtotal.textContent.replace(/[^0-9.]/g, '');
                const subtotal = parseFloat(subtotalText) || 0;
                const discountAmountText = discountRow && discountRow.style.display !== 'none' 
                    ? document.getElementById('discountAmount')?.textContent.replace(/[^0-9.]/g, '') || '0'
                    : '0';
                const discountAmount = parseFloat(discountAmountText) || 0;
                const newTotal = Math.max(0, subtotal - discountAmount);
                cartTotal.textContent = 'GH₵ ' + newTotal.toFixed(2);
                
                // Remove from session storage
                sessionStorage.removeItem('appliedStoreCredits');
            }
        }

        // Make handleStoreCreditsToggle globally available
        window.handleStoreCreditsToggle = handleStoreCreditsToggle;

        // Simple cart functionality - all complex code moved to new_cart_script.js
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Cart page loaded successfully');

            // Global functions for dropdown functionality
            window.toggleUserDropdown = function() {
                const dropdown = document.getElementById('userDropdownMenu');
                if (dropdown) dropdown.classList.toggle('show');
            };

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('userDropdownMenu');
                const avatar = document.querySelector('.user-avatar');
                if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // Checkout function
            window.proceedToCheckout = function() {
                // Store applied credits in sessionStorage to pass to checkout
                const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
                if (applyStoreCreditsCheckbox && applyStoreCreditsCheckbox.checked) {
                    const storeCreditsAmount = parseFloat(document.getElementById('storeCreditsAmount')?.textContent.replace(/[^0-9.]/g, '') || 0);
                    sessionStorage.setItem('appliedStoreCredits', storeCreditsAmount);
                } else {
                    sessionStorage.removeItem('appliedStoreCredits');
                }
                window.location.href = 'checkout.php';
            };

            // Store Credits functionality - attach to existing DOMContentLoaded
            const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
            if (applyStoreCreditsCheckbox) {
                applyStoreCreditsCheckbox.addEventListener('change', function() {
                    handleStoreCreditsToggle(this.checked);
                });

                // Check on page load if discount is active, disable store credits if so
                const discountRow = document.getElementById('discountRow');
                if (discountRow && discountRow.style.display !== 'none' && discountRow.style.display !== '') {
                    applyStoreCreditsCheckbox.disabled = true;
                    const storeCreditsLabel = document.querySelector('label[for="applyStoreCredits"]');
                    if (storeCreditsLabel) {
                        storeCreditsLabel.style.cursor = 'not-allowed';
                        storeCreditsLabel.style.opacity = '0.6';
                    }
                    const storeCreditsExclusiveMessage = document.getElementById('storeCreditsExclusiveMessage');
                    if (storeCreditsExclusiveMessage) {
                        storeCreditsExclusiveMessage.style.display = 'block';
                    }
                }
            }

            // Store Credits Functions (already defined above)
                const storeCreditsRow = document.getElementById('storeCreditsRow');
                const storeCreditsAmount = document.getElementById('storeCreditsAmount');
                const cartTotal = document.getElementById('cartTotal');
                const cartSubtotal = document.getElementById('cartSubtotal');
                const storeCreditBalanceDisplay = document.getElementById('storeCreditBalanceDisplay');
                const discountRow = document.getElementById('discountRow');
                const promoCodeInput = document.getElementById('promoCode');
                const applyPromoBtn = document.getElementById('applyPromoBtn');
                const storeCreditsExclusiveMessage = document.getElementById('storeCreditsExclusiveMessage');
                const storeCreditsInfoText = document.getElementById('storeCreditsInfoText');

                if (!storeCreditsRow || !storeCreditsAmount || !cartTotal || !cartSubtotal) {
                    return;
                }

                // Check if discount is active
                const isDiscountActive = discountRow && discountRow.style.display !== 'none' && discountRow.style.display !== '';

                if (isChecked) {
                    // If discount is active, prevent applying store credits
                    if (isDiscountActive) {
                        const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
                        if (applyStoreCreditsCheckbox) {
                            applyStoreCreditsCheckbox.checked = false;
                        }
                        if (storeCreditsExclusiveMessage) {
                            storeCreditsExclusiveMessage.style.display = 'block';
                        }
                        return;
                    }

                    // Hide exclusive message if shown
                    if (storeCreditsExclusiveMessage) {
                        storeCreditsExclusiveMessage.style.display = 'none';
                    }

                    // Disable discount code input
                    if (promoCodeInput) {
                        promoCodeInput.disabled = true;
                        promoCodeInput.placeholder = 'Store credits applied';
                    }
                    if (applyPromoBtn) {
                        applyPromoBtn.disabled = true;
                    }

                    // Get values
                    const subtotalText = cartSubtotal.textContent.replace(/[^0-9.]/g, '');
                    const subtotal = parseFloat(subtotalText) || 0;
                    const creditBalanceText = storeCreditBalanceDisplay?.textContent.replace(/[^0-9.]/g, '') || '0';
                    const creditBalance = parseFloat(creditBalanceText) || 0;

                    // Apply 500 minimum reserve rule: max usable = balance - 500
                    const maxUsableCredits = Math.max(0, creditBalance - 500);
                    // Credits to apply = minimum of (max usable, cart subtotal)
                    const creditsToApply = Math.min(maxUsableCredits, subtotal);

                    if (creditsToApply > 0) {
                        storeCreditsAmount.textContent = '-GH₵ ' + creditsToApply.toFixed(2);
                        storeCreditsRow.style.display = 'flex';
                        
                        // Update total (subtract store credits)
                        const discountAmountText = discountRow && discountRow.style.display !== 'none' 
                            ? document.getElementById('discountAmount')?.textContent.replace(/[^0-9.]/g, '') || '0'
                            : '0';
                        const discountAmount = parseFloat(discountAmountText) || 0;
                        const newTotal = Math.max(0, subtotal - discountAmount - creditsToApply);
                        cartTotal.textContent = 'GH₵ ' + newTotal.toFixed(2);
                        
                        // Update session storage
                        sessionStorage.setItem('appliedStoreCredits', creditsToApply.toFixed(2));
                    } else {
                        // Not enough credits (would leave less than 500)
                        const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
                        if (applyStoreCreditsCheckbox) {
                            applyStoreCreditsCheckbox.checked = false;
                        }
                        alert('You cannot use store credits that would leave less than GH₵500 in your balance.');
                        return;
                    }
                } else {
                    // Remove store credits
                    storeCreditsRow.style.display = 'none';
                    storeCreditsAmount.textContent = '-GH₵ 0.00';
                    
                    // Re-enable discount code input
                    if (promoCodeInput) {
                        promoCodeInput.disabled = false;
                        promoCodeInput.placeholder = '';
                    }
                    if (applyPromoBtn) {
                        applyPromoBtn.disabled = false;
                    }

                    // Recalculate total (subtract discount if any)
                    const subtotalText = cartSubtotal.textContent.replace(/[^0-9.]/g, '');
                    const subtotal = parseFloat(subtotalText) || 0;
                    const discountAmountText = discountRow && discountRow.style.display !== 'none' 
                        ? document.getElementById('discountAmount')?.textContent.replace(/[^0-9.]/g, '') || '0'
                        : '0';
                    const discountAmount = parseFloat(discountAmountText) || 0;
                    const newTotal = Math.max(0, subtotal - discountAmount);
                    cartTotal.textContent = 'GH₵ ' + newTotal.toFixed(2);
                    
                    // Remove from session storage
                    sessionStorage.removeItem('appliedStoreCredits');
                }
            }

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
        });

        // Scroll to Top Button Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const scrollToTopBtn = document.getElementById('scrollToTopBtn');

            if (scrollToTopBtn) {
                // Show/hide button based on scroll position
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollToTopBtn.classList.add('show');
                    } else {
                        scrollToTopBtn.classList.remove('show');
                    }
                });

                // Scroll to top when button is clicked
                scrollToTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>

</html>