<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/product_controller.php');
require_once(__DIR__ . '/../controllers/category_controller.php');
require_once(__DIR__ . '/../controllers/brand_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');
require_once(__DIR__ . '/../helpers/image_helper.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Get real products from database
$all_products = get_all_products_ctr();

// Get real categories and brands from database
try {
    $categories = get_all_categories_ctr();
} catch (Exception $e) {
    $categories = [];
}

try {
    $brands = get_all_brands_ctr();
} catch (Exception $e) {
    $brands = [];
}

// Products and categories fetched from database above

// Filter products to ONLY show Flash Deals category
// First, find the Flash Deals category
$flash_deals_category = null;
foreach ($categories as $cat) {
    if (stripos($cat['cat_name'], 'flash') !== false || stripos($cat['cat_name'], 'deal') !== false) {
        $flash_deals_category = $cat['cat_name'];
        break;
    }
}

// If no "Flash Deals" category found, try common variations
if (!$flash_deals_category) {
    $possible_names = ['Flash Deals', 'flash_deals', 'FlashDeals', 'flash deals', 'Flash Deal'];
    foreach ($possible_names as $name) {
        foreach ($categories as $cat) {
            if (strtolower(trim($cat['cat_name'])) === strtolower(trim($name))) {
                $flash_deals_category = $cat['cat_name'];
                break 2;
            }
        }
    }
}

// Filter products to ONLY show Flash Deals category products
$filtered_products = [];
if ($flash_deals_category) {
    $filtered_products = array_filter($all_products, function ($product) use ($flash_deals_category) {
        return $product['cat_name'] === $flash_deals_category;
    });
} else {
    // If category not found, show all products (fallback)
    $filtered_products = $all_products;
}

// Additional filters based on URL parameters
$brand_filter = $_GET['brand'] ?? 'all';
$condition_filter = $_GET['condition'] ?? 'all';
$search_query = $_GET['search'] ?? '';

if ($brand_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function ($product) use ($brand_filter) {
        return $product['brand_name'] === $brand_filter;
    });
}

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function ($product) use ($search_query) {
        return stripos($product['product_title'], $search_query) !== false ||
            stripos($product['product_desc'], $search_query) !== false;
    });
}

// Pagination
$products_per_page = 12;
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $products_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;
$products_to_display = array_slice($filtered_products, $offset, $products_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Flash Deals - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <link href="../css/product-card.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        /* Color Scheme Variables */
        :root {
            --light-blue: #E8F0FE;
            --medium-blue: #4285F4;
            --dark-blue: #1A73E8;
            --navy-blue: #0D47A1;
            --off-white: #FAFAFA;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --shadow: rgba(26, 115, 232, 0.1);
            --gradient-primary: linear-gradient(135deg, var(--navy-blue) 0%, var(--dark-blue) 50%, var(--medium-blue) 100%);
            --gradient-light: linear-gradient(135deg, var(--light-blue) 0%, var(--off-white) 100%);
        }

        /* Reset and Base Styles */
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

        /* Header Styles - Same as index */
        

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
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            background: #f8fafc;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .search-input:focus {
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1.1rem;
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .tech-revival-section {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1f2937;
        }

        .tech-revival-icon {
            font-size: 2.5rem;
            color: #10b981;
        }

        .tech-revival-text {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }

        .contact-number {
            font-size: 1rem;
            font-weight: 500;
            color: #6b7280;
            margin: 0;
            line-height: 1.2;
        }

        /* Main Navigation - Copied from index.php */
        

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
            font-size: 1.3rem;
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

        .nav-item.dropdown {
            position: relative;
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
            width: 20px;
        }

        .dropdown-column.featured {
            border-left: 2px solid #f3f4f6;
            padding-left: 24px;
        }

        .featured-item {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .featured-item img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        .featured-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .featured-text strong {
            color: #1f2937;
            font-size: 1rem;
        }

        .featured-text p {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0;
        }

        .shop-now-btn {
            background: #008060;
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 4px;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .shop-now-btn:hover {
            background: #006b4e;
            color: white;
        }

        /* Simple Dropdown */
        .simple-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 8px 0;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .simple-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .simple-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .simple-dropdown ul li a {
            color: #6b7280;
            text-decoration: none;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .simple-dropdown ul li a:hover {
            background: #f9fafb;
            color: #008060;
        }

        /* Dropdown Positioning */
        .nav-dropdown {
            position: relative;
        }

        .brands-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 20px;
            min-width: 300px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .shop-categories-btn:hover .brands-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .brands-dropdown h4 {
            margin-bottom: 15px;
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .brands-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .brands-dropdown li {
            margin-bottom: 8px;
        }

        .brands-dropdown a {
            color: #6b7280;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .brands-dropdown a:hover {
            background: #f3f4f6;
            color: #3b82f6;
        }

        /* User Interface Styles - Same as index */
        .user-actions {
            display: flex;
            align-items: center;
            gap: 11px;
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
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
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

        .header-icon {
            position: relative;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .header-icon:hover {
            background: rgba(139, 95, 191, 0.1);
            transform: scale(1.1);
        }

        .wishlist-badge,
        .cart-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Language and Theme Toggle Styles */
        .language-selector,
        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .toggle-switch {
            position: relative;
            width: 40px;
            height: 20px;
            background: #cbd5e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: #008060;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(20px);
        }

        /* Floating Bubbles Animation */
        .floating-bubbles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }

        .bubble {
            position: absolute;
            bottom: -100px;
            background: linear-gradient(135deg, rgba(0, 128, 96, 0.1), rgba(0, 107, 78, 0.1));
            border-radius: 50%;
            opacity: 0.6;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 0.6;
            }

            90% {
                opacity: 0.6;
            }

            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Flash Deals Hero Section */
        .flash-deals-hero {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            padding: 60px 20px;
            margin: 20px 0 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .flash-deals-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .flash-hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        .flash-hero-text {
            text-align: center;
        }

        .flash-main-title {
            font-size: 4rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 4px 20px rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .flash-main-title i {
            color: #ffd700;
            animation: flash 2s ease-in-out infinite;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8));
        }

        @keyframes flash {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        .flash-subtitle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            letter-spacing: 1px;
        }

        /* Large Countdown Timer */
        .flash-countdown-large {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 50px 80px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), inset 0 0 30px rgba(255, 255, 255, 0.1);
        }

        .countdown-label-large {
            text-align: center;
            color: #ffffff;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 30px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .countdown-display-large {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
        }

        .countdown-item-large {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 30px 40px;
            min-width: 140px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .countdown-item-large:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        .countdown-number-large {
            font-size: 4.5rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            font-family: 'Inter', sans-serif;
        }

        .countdown-text-large {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .countdown-separator {
            font-size: 3rem;
            font-weight: 700;
            color: #ffd700;
            margin: 0 5px;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
            animation: blink 1s ease-in-out infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        @media (max-width: 992px) {
            .flash-main-title {
                font-size: 2.5rem;
            }

            .flash-countdown-large {
                padding: 40px 30px;
            }

            .countdown-display-large {
                gap: 15px;
            }

            .countdown-item-large {
                padding: 20px 25px;
                min-width: 100px;
            }

            .countdown-number-large {
                font-size: 3rem;
            }

            .countdown-separator {
                font-size: 2rem;
                margin: 0 3px;
            }
        }

        @media (max-width: 768px) {
            .flash-deals-hero {
                padding: 40px 15px;
            }

            .flash-main-title {
                font-size: 2rem;
                flex-direction: column;
                gap: 10px;
            }

            .flash-subtitle {
                font-size: 1.1rem;
            }

            .flash-countdown-large {
                padding: 30px 20px;
            }

            .countdown-display-large {
                flex-wrap: wrap;
                gap: 10px;
            }

            .countdown-item-large {
                padding: 15px 20px;
                min-width: 80px;
            }

            .countdown-number-large {
                font-size: 2.5rem;
            }

            .countdown-text-large {
                font-size: 0.7rem;
            }

            .countdown-separator {
                font-size: 1.5rem;
            }
        }

        /* Sidebar Layout Styles */
        .filters-sidebar {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .filter-title {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-close {
            background: none;
            border: none;
            color: #666;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .filter-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #000000;
        }

        .filter-subtitle {
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 12px;
            display: block;
        }

        .filter-group {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        /* Search Input Styles */
        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
        }

        .search-input:focus {
            outline: none;
            border-color: #000000;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #000000;
            font-size: 0.9rem;
        }

        /* Checkbox Styles */
        .checkbox-group {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .checkbox-group::-webkit-scrollbar {
            width: 4px;
        }

        .checkbox-group::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .checkbox-group::-webkit-scrollbar-thumb {
            background: #008060;
            border-radius: 2px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            cursor: pointer;
            font-size: 0.9rem;
            color: #555;
            transition: all 0.3s ease;
            margin: 0;
        }

        .checkbox-item:hover {
            color: #000000;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 5px;
            padding-left: 5px;
        }

        .checkbox-item input[type="checkbox"] {
            display: none;
        }

        .checkbox-custom {
            width: 16px;
            height: 16px;
            border: 2px solid #ddd;
            border-radius: 3px;
            margin-right: 10px;
            position: relative;
            transition: all 0.3s ease;
        }

        .checkbox-item input[type="checkbox"]:checked+.checkbox-custom {
            background: #000000;
            border-color: #000000;
        }

        .checkbox-item input[type="checkbox"]:checked+.checkbox-custom::after {
            content: '✓';
            position: absolute;
            top: -2px;
            left: 2px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        /* Filter Actions */
        .filter-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid rgba(0, 128, 96, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .apply-filters-btn {
            background: #000000;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .apply-filters-btn:hover {
            background: #374151;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* Mobile Styles */
        @media (max-width: 991px) {
            #filterSidebar {
                position: fixed;
                left: -100%;
                top: 0;
                width: 320px;
                height: 100vh;
                z-index: 9999;
                transition: all 0.3s ease;
                background: white;
            }

            #filterSidebar.show {
                left: 0;
            }

            .filters-sidebar {
                height: 100vh;
                max-height: none;
                border-radius: 0;
                position: static;
                top: auto;
            }

            .filter-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                display: none;
            }

            .filter-overlay.show {
                display: block;
            }
        }

        /* Rating Filter Styles */
        .rating-filter {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rating-option {
            display: flex;
            align-items: center;
        }

        .rating-option input[type="radio"] {
            display: none;
        }

        .rating-option label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .rating-option label:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .rating-option input[type="radio"]:checked+label {
            background: rgba(0, 0, 0, 0.2);
            color: #000000;
        }

        .stars {
            display: flex;
            gap: 2px;
        }

        .stars i {
            color: #ffd700;
            font-size: 14px;
        }

        .rating-text {
            font-size: 14px;
            color: #666;
        }

        /* Price Range Slider Styles */
        .price-slider-container {
            padding: 10px 0;
        }

        .price-slider-track {
            position: relative;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin: 10px 0 20px 0;
        }

        .price-slider-range {
            position: absolute;
            height: 6px;
            background: #000000;
            border-radius: 3px;
            left: 0%;
            right: 0%;
        }

        .price-slider {
            position: absolute;
            top: -2px;
            width: 100%;
            height: 10px;
            background: transparent;
            outline: none;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
        }

        .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: var(--royal-blue);
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }

        .price-slider::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: var(--royal-blue);
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--text-dark);
        }

        .price-separator {
            color: #666;
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

        /* Tag Filter Styles */
        .tag-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tag-btn:hover {
            background: rgba(0, 128, 96, 0.1);
            border-color: #008060;
            color: #008060;
        }

        .tag-btn.active {
            background: linear-gradient(135deg, #008060, #006b4e);
            border-color: #008060;
            color: white;
        }

        /* Size Filter Styles */
        .size-filters {
            display: flex;
            gap: 8px;
        }

        .size-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 70px;
            text-align: center;
        }

        .size-btn:hover {
            background: rgba(0, 128, 96, 0.1);
            border-color: #008060;
            color: #008060;
        }

        .size-btn.active {
            background: linear-gradient(135deg, #008060, #006b4e);
            border-color: #008060;
            color: white;
        }

        /* Color Filter Styles */
        .color-filters {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .color-btn {
            background: none;
            border: 2px solid transparent;
            padding: 4px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .color-btn:hover {
            border-color: #008060;
            transform: scale(1.1);
        }

        .color-btn.active {
            border-color: #008060;
            background: rgba(0, 128, 96, 0.1);
        }

        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .color-circle.all-colors {
            background: conic-gradient(from 0deg,
                    #ff0000 0deg 60deg,
                    #ffff00 60deg 120deg,
                    #00ff00 120deg 180deg,
                    #00ffff 180deg 240deg,
                    #0000ff 240deg 300deg,
                    #ff00ff 300deg 360deg);
        }

        /* Clear Filters Button */
        .clear-filters-container {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 128, 96, 0.1);
        }

        .filter-select,
        .filter-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(10px);
        }

        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: #008060;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(0, 128, 96, 0.1);
            transform: translateY(-2px);
        }

        .price-input {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(10px);
        }

        .price-input:focus {
            outline: none;
            border-color: #008060;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
        }

        .preset-btn {
            border-radius: 8px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.85rem;
            padding: 6px 12px;
            transition: all 0.3s ease;
        }

        .preset-btn:hover,
        .preset-btn.active {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border-color: #008060;
        }

        .clear-filters-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .clear-filters-btn:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .stats-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 128, 96, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .product-count {
            font-weight: 600;
            color: #008060;
            font-size: 1.1rem;
        }

        .view-toggle {
            display: flex;
            gap: 8px;
        }

        .view-btn {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-btn.active,
        .view-btn:hover {
            background: #008060;
            color: white;
            border-color: #008060;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 35px;
            margin-bottom: 50px;
            width: 100%;
        }

        .product-grid.list-view {
            grid-template-columns: 1fr;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
            transition: all 0.4s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            width: 100%;
            max-width: none;
            min-height: 450px;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(139, 95, 191, 0.25);
            border-color: rgba(139, 95, 191, 0.4);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.05), rgba(240, 147, 251, 0.05));
            opacity: 0;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 16px 48px rgba(139, 95, 191, 0.25);
        }

        .product-card:hover::before {
            opacity: 1;
        }

        .product-image-container {
            position: relative;
            width: 100%;
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.4s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.1);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .product-content {
            padding: 25px;
            position: relative;
            z-index: 2;
        }

        .product-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #000000;
            margin-bottom: 15px;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .meta-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            font-size: 0.85rem;
            color: #000000;
            font-weight: 500;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 15px;
            background: #000000;
            color: white;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .add-to-cart-btn:hover {
            background: #374151;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 50px 0;
        }

        .page-btn {
            padding: 12px 18px;
            border: 2px solid rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.9);
            color: #000000;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .page-btn:hover,
        .page-btn.active {
            background: #000000;
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .no-products {
            text-align: center;
            padding: 80px 20px;
            color: #64748b;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 8px 32px rgba(0, 128, 96, 0.1);
        }

        .no-products-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(139, 95, 191, 0.3);
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.4);
        }

        /* Main Navigation */
        

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-item {
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            padding: 12px 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item:hover {
            color: #008060;
        }

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
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .categories-button:hover {
            background: #3d4fd1;
        }

        .nav-item.flash-deal {
            color: #ef4444;
            font-weight: 600;
        }

        .nav-item.flash-deal:hover {
            color: #dc2626;
        }

        .hero-actions .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            border-width: 2px;
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 5px;
        }

        .suggestion-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .suggestion-item:hover {
            background: linear-gradient(135deg, #f8f9ff, #f0f2ff);
            color: #008060;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-icon {
            color: #008060;
            font-size: 0.9rem;
        }

        .checkbox-dropdown {
            position: relative;
            width: 100%;
        }

        .dropdown-toggle {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1rem;
            color: #4a5568;
            transition: all 0.3s ease;
        }

        .dropdown-toggle:hover {
            border-color: #008060;
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
        }

        .dropdown-toggle.active {
            border-color: #008060;
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
            color: #008060;
        }

        .dropdown-toggle.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.15);
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 5px;
            display: none;
        }

        .dropdown-content.show {
            display: block;
        }

        .checkbox-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .checkbox-item:hover {
            background: linear-gradient(135deg, #f8f9ff, #f0f2ff);
        }

        .checkbox-item:last-child {
            border-bottom: none;
        }

        .checkbox-item input[type="checkbox"] {
            margin-right: 10px;
            width: 16px;
            height: 16px;
            accent-color: #008060;
            cursor: pointer;
        }

        .checkbox-item label {
            cursor: pointer;
            color: #4a5568;
            font-weight: 500;
            margin: 0;
        }

        .checkbox-item:hover label {
            color: #008060;
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .bubble {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(0, 128, 96, 0.1), rgba(240, 147, 251, 0.1));
            animation: float 6s ease-in-out infinite;
        }

        .bubble:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .bubble:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 20%;
            right: 15%;
            animation-delay: 2s;
        }

        .bubble:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        .bubble:nth-child(4) {
            width: 120px;
            height: 120px;
            bottom: 20%;
            right: 10%;
            animation-delay: 1s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 25px;
            }

            .filters-section {
                padding: 20px;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
        }

        /* Product Card Enhancements */
        @keyframes popupFade {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }

            15% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }

            85% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }

            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }
        }

        .product-image-container:hover .product-image {
            transform: rotate(-3deg) scale(1.05);
        }

        .customer-activity-popup {
            animation-delay: var(--delay, 0s);
        }

        .wishlist-btn.active i {
            color: #ef4444 !important;
        }

        .wishlist-btn.active {
            background: rgba(239, 68, 68, 0.1) !important;
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
    <?php include '../includes/header.php'; ?>

    <!-- Flash Deals Hero Section with Large Timer -->
    <div class="container-fluid">
        <div class="flash-deals-hero">
            <div class="flash-hero-content">
                <div class="flash-hero-text">
                    <h1 class="flash-main-title">
                        <i class="fas fa-bolt"></i>
                        FLASH DEALS
                        <i class="fas fa-bolt"></i>
                    </h1>
                    <p class="flash-subtitle">Unbeatable prices for a limited time only!</p>
                </div>

                <div class="flash-countdown-large">
                    <div class="countdown-label-large">Deals End In</div>
                    <div class="countdown-display-large">
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="days">12</div>
                            <div class="countdown-text-large">Days</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="hours">00</div>
                            <div class="countdown-text-large">Hours</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="minutes">00</div>
                            <div class="countdown-text-large">Minutes</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="seconds">00</div>
                            <div class="countdown-text-large">Seconds</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content with Filters -->
    <div class="container-fluid">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div id="filterSidebar" class="filters-sidebar">
                    <div class="filter-header">
                        <h4 class="filter-title">
                            <i class="fas fa-filter"></i>
                            Filters
                        </h4>
                        <button class="filter-close" onclick="hideFilters()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Search Filter -->
                    <div class="filter-group">
                        <label class="filter-subtitle">Search Products</label>
                        <div class="search-container">
                            <input type="text" id="searchFilter" class="search-input"
                                   placeholder="Search flash deals..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    <!-- Brand Filter -->
                    <div class="filter-group">
                        <label class="filter-subtitle">Brand</label>
                        <div class="checkbox-dropdown">
                            <div class="dropdown-toggle" onclick="toggleDropdown('brandDropdown')">
                                <span>Select Brands</span>
                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                            </div>
                            <div class="dropdown-content" id="brandDropdown">
                                <?php foreach ($brands as $brand): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="brand_<?php echo $brand['brand_id']; ?>"
                                           name="brand" value="<?php echo htmlspecialchars($brand['brand_name']); ?>"
                                           <?php echo ($brand_filter === $brand['brand_name']) ? 'checked' : ''; ?>>
                                    <label for="brand_<?php echo $brand['brand_id']; ?>">
                                        <?php echo htmlspecialchars($brand['brand_name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Condition Filter -->
                    <div class="filter-group">
                        <label class="filter-subtitle">Condition</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="radio" name="condition" value="all" id="condition_all"
                                       <?php echo ($condition_filter === 'all') ? 'checked' : ''; ?>>
                                <label for="condition_all">All Conditions</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" name="condition" value="excellent" id="condition_excellent"
                                       <?php echo ($condition_filter === 'excellent') ? 'checked' : ''; ?>>
                                <label for="condition_excellent">Excellent</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" name="condition" value="good" id="condition_good"
                                       <?php echo ($condition_filter === 'good') ? 'checked' : ''; ?>>
                                <label for="condition_good">Good</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" name="condition" value="fair" id="condition_fair"
                                       <?php echo ($condition_filter === 'fair') ? 'checked' : ''; ?>>
                                <label for="condition_fair">Fair</label>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="filter-actions">
                        <button class="apply-filters-btn" onclick="applyFilters()">
                            <i class="fas fa-check"></i>
                            Apply Filters
                        </button>
                        <div class="clear-filters-container">
                            <button class="clear-filters-btn" onclick="clearFilters()">
                                <i class="fas fa-times"></i>
                                Clear All Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Area -->
            <div class="col-lg-9">
                <!-- Stats Bar -->
                <div class="stats-bar">
                    <div class="product-count">
                        <i class="fas fa-fire"></i>
                        Showing <?php echo count($products_to_display); ?> of <?php echo $total_products; ?> flash deals
                    </div>
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('grid')">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" onclick="toggleView('list')">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="product-grid" id="productGrid">
                    <?php if (empty($products_to_display)): ?>
                        <div class="no-products">
                            <div class="no-products-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>No Flash Deals Found</h3>
                            <p>Try adjusting your filters or check back later for new deals.</p>
                            <a href="flash_deals.php" class="btn btn-primary">
                                <i class="fas fa-refresh me-2"></i>
                                View All Flash Deals
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products_to_display as $product): ?>
                            <div class="product-card" onclick="window.location.href='single_product.php?pid=<?php echo $product['product_id']; ?>'">
                                <div class="product-image-container">
                                    <img src="<?php echo get_product_image_url($product['product_image'], $product['product_title']); ?>"
                                         alt="<?php echo htmlspecialchars($product['product_title']); ?>"
                                         class="product-image"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIzMDAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNOTAgMTIwTDIxMCAyMTBIOTBWMTIwWiIgZmlsbD0iI0QxRDVEQiIvPjxjaXJjbGUgY3g9IjEzMiIgY3k9IjEzMiIgcj0iMTgiIGZpbGw9IiNEMUQ1REIiLz48L3N2Zz4='; this.onerror=null;">

                                    <!-- Flash Deal Badge -->
                                    <div class="product-badge">
                                        <i class="fas fa-bolt me-1"></i>
                                        FLASH DEAL
                                    </div>

                                    <!-- Wishlist Button -->
                                    <button class="wishlist-btn <?php echo $is_logged_in ? 'btn-wishlist' : 'btn-login-required'; ?>"
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            onclick="event.stopPropagation(); <?php echo $is_logged_in ? 'toggleWishlist(this)' : 'showLoginPrompt()'; ?>"
                                            title="<?php echo $is_logged_in ? 'Add to Wishlist' : 'Login to add to wishlist'; ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>

                                <div class="product-content">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['product_title']); ?></h3>

                                    <div class="product-meta">
                                        <div class="meta-tag">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($product['brand_name'] ?? 'Unknown Brand'); ?>
                                        </div>
                                        <div class="meta-tag">
                                            <i class="fas fa-layer-group"></i>
                                            <?php echo htmlspecialchars($product['cat_name'] ?? 'Category'); ?>
                                        </div>
                                    </div>

                                    <div class="product-price">
                                        GH₵ <?php echo number_format($product['product_price'], 2); ?>
                                    </div>

                                    <button class="add-to-cart-btn"
                                            onclick="event.stopPropagation(); addToCartWithCondition(<?php echo $product['product_id']; ?>)"
                                            data-product-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-fire"></i>
                                        Grab Deal Now
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>&<?php echo http_build_query(array_filter(['brand' => $brand_filter !== 'all' ? $brand_filter : '', 'condition' => $condition_filter !== 'all' ? $condition_filter : '', 'search' => $search_query])); ?>"
                               class="page-btn">
                                <i class="fas fa-chevron-left me-2"></i>Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['brand' => $brand_filter !== 'all' ? $brand_filter : '', 'condition' => $condition_filter !== 'all' ? $condition_filter : '', 'search' => $search_query])); ?>"
                               class="page-btn <?php echo $i === $current_page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>&<?php echo http_build_query(array_filter(['brand' => $brand_filter !== 'all' ? $brand_filter : '', 'condition' => $condition_filter !== 'all' ? $condition_filter : '', 'search' => $search_query])); ?>"
                               class="page-btn">
                                Next<i class="fas fa-chevron-right ms-2"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Include Central Filter Overlay (for mobile) -->
    <div class="filter-overlay" id="filterOverlay"></div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/dark-mode.js"></script>
    <script src="../js/product-card.js"></script>
    <script src="../js/cart-functions.js"></script>
    <script src="../js/wishlist.js"></script>

    <script>
        // Flash Deals 12-Day Countdown Timer
        function startCountdown() {
            const now = new Date().getTime();
            const endDate = new Date(now + (12 * 24 * 60 * 60 * 1000)).getTime(); // 12 days from now

            const timer = setInterval(function() {
                const now = new Date().getTime();
                const distance = endDate - now;

                if (distance < 0) {
                    clearInterval(timer);
                    document.getElementById("days").innerHTML = "00";
                    document.getElementById("hours").innerHTML = "00";
                    document.getElementById("minutes").innerHTML = "00";
                    document.getElementById("seconds").innerHTML = "00";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("days").innerHTML = String(days).padStart(2, '0');
                document.getElementById("hours").innerHTML = String(hours).padStart(2, '0');
                document.getElementById("minutes").innerHTML = String(minutes).padStart(2, '0');
                document.getElementById("seconds").innerHTML = String(seconds).padStart(2, '0');
            }, 1000);
        }

        // Filter Functions
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            const toggle = dropdown.previousElementSibling;

            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }

        function applyFilters() {
            const searchQuery = document.getElementById('searchFilter').value;
            const brandCheckboxes = document.querySelectorAll('input[name="brand"]:checked');
            const conditionRadio = document.querySelector('input[name="condition"]:checked');

            const brands = Array.from(brandCheckboxes).map(cb => cb.value);
            const condition = conditionRadio ? conditionRadio.value : 'all';

            const params = new URLSearchParams();
            if (searchQuery) params.set('search', searchQuery);
            if (brands.length > 0 && brands[0] !== 'all') params.set('brand', brands[0]);
            if (condition !== 'all') params.set('condition', condition);

            window.location.href = 'flash_deals.php?' + params.toString();
        }

        function clearFilters() {
            window.location.href = 'flash_deals.php';
        }

        function toggleView(view) {
            const productGrid = document.getElementById('productGrid');
            const viewBtns = document.querySelectorAll('.view-btn');

            viewBtns.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');

            if (view === 'list') {
                productGrid.classList.add('list-view');
            } else {
                productGrid.classList.remove('list-view');
            }
        }

        function hideFilters() {
            const sidebar = document.getElementById('filterSidebar');
            const overlay = document.getElementById('filterOverlay');

            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }

        // Enhanced Add to Cart Function for Flash Deals
        async function addToCartWithCondition(productId) {
            if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
                showLoginPrompt();
                return;
            }

            // Show flash deal notification
            const flashNotification = document.createElement('div');
            flashNotification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #ff6b6b, #ee5a52);
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                z-index: 10000;
                font-weight: 600;
                box-shadow: 0 4px 20px rgba(255, 107, 107, 0.3);
            `;
            flashNotification.innerHTML = '<i class="fas fa-fire me-2"></i>Adding flash deal to cart...';
            document.body.appendChild(flashNotification);

            try {
                const response = await fetch('../actions/add_to_cart_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                });

                const result = await response.json();

                setTimeout(() => {
                    document.body.removeChild(flashNotification);

                    if (result.success) {
                        showFlashDealSuccess();
                        updateCartBadge(result.cart_count);
                    } else {
                        alert(result.message || 'Failed to add item to cart');
                    }
                }, 800);

            } catch (error) {
                setTimeout(() => {
                    document.body.removeChild(flashNotification);
                    alert('Error adding item to cart: ' + error.message);
                }, 800);
            }
        }

        function showFlashDealSuccess() {
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                padding: 20px 30px;
                border-radius: 12px;
                z-index: 10000;
                font-weight: 600;
                box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
                animation: slideIn 0.3s ease-out;
            `;
            successDiv.innerHTML = '<i class="fas fa-bolt me-2"></i>Flash Deal Added to Cart! 🔥';
            document.body.appendChild(successDiv);

            setTimeout(() => {
                if (document.body.contains(successDiv)) {
                    document.body.removeChild(successDiv);
                }
            }, 3000);
        }

        function updateCartBadge(count) {
            const badge = document.getElementById('cartBadge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        }

        // Scroll to Top Functionality
        window.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollToTopBtn');
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        });

        document.getElementById('scrollToTopBtn').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Initialize countdown on page load
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown();

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.checkbox-dropdown')) {
                    document.querySelectorAll('.dropdown-content.show').forEach(dropdown => {
                        dropdown.classList.remove('show');
                        dropdown.previousElementSibling.classList.remove('active');
                    });
                }
            });
        });
    </script>

</body>
</html>
