<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/product_controller.php');
require_once(__DIR__ . '/../controllers/category_controller.php');
require_once(__DIR__ . '/../controllers/brand_controller.php');
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

// Get all products from database
$all_products = get_all_products_ctr();

// Get all categories and brands from database
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

// Define photography & video categories
$photo_video_categories = ['cameras', 'video_equipment', 'Cameras', 'Video Equipment', 'Camera', 'Video', 'Photography'];

// Filter products for photography & video devices only
$photo_video_products = array_filter($all_products, function($product) use ($photo_video_categories) {
    return in_array($product['cat_name'], $photo_video_categories) ||
           stripos($product['product_title'], 'camera') !== false ||
           stripos($product['product_title'], 'video') !== false ||
           stripos($product['product_title'], 'photography') !== false ||
           stripos($product['product_title'], 'photo') !== false ||
           stripos($product['product_title'], 'lens') !== false ||
           stripos($product['product_title'], 'camcorder') !== false ||
           stripos($product['cat_name'], 'photo') !== false ||
           stripos($product['cat_name'], 'video') !== false;
});

// Apply additional filters based on URL parameters
$category_filter = $_GET['category'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$condition_filter = $_GET['condition'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$filtered_products = $photo_video_products;

if ($category_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function($product) use ($category_filter) {
        return strcasecmp($product['cat_name'], $category_filter) === 0;
    });
}

if ($brand_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function($product) use ($brand_filter) {
        return $product['brand_id'] == $brand_filter;
    });
}

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
        return stripos($product['product_title'], $search_query) !== false ||
               stripos($product['product_desc'], $search_query) !== false;
    });
}

// Get unique categories and brands from photo/video products
$photo_video_cats = array_unique(array_column($photo_video_products, 'cat_name'));
$photo_video_brand_ids = array_unique(array_column($photo_video_products, 'brand_id'));
$photo_video_brands = array_filter($brands, function($brand) use ($photo_video_brand_ids) {
    return in_array($brand['brand_id'], $photo_video_brand_ids);
});

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
    <title>Photography & Video - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="includes/header-styles.css" rel="stylesheet">
    <link href="includes/chatbot-styles.css" rel="stylesheet">
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

        /* Promotional Banner Styles - Same as index */
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

        /* Header Styles - Same as index */
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

        /* Page specific styles */
        .page-title {
            text-align: center;
            padding: 40px 0;
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .filters-sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            position: sticky;
            top: 20px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .filter-title {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-close {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .filter-group {
            margin-bottom: 25px;
        }

        .filter-subtitle {
            color: #374151;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #f9fafb;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #059669;
            background: white;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .rating-filters {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rating-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .rating-option:hover {
            background: #f3f4f6;
        }

        .rating-option.active {
            background: #dcfce7;
            color: #059669;
        }

        .stars {
            color: #fbbf24;
            font-size: 0.85rem;
        }

        .stars .far {
            color: #d1d5db;
        }

        .rating-option span {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .price-range-container {
            padding: 10px 0;
        }

        .price-slider {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
            outline: none;
            -webkit-appearance: none;
            margin-bottom: 15px;
        }

        .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #059669;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #059669;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #374151;
            font-weight: 500;
        }

        .tag-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.8rem;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .tag-btn:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .tag-btn.active {
            background: #059669;
            color: white;
            border-color: #059669;
        }

        .color-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .color-option {
            cursor: pointer;
            padding: 3px;
            border-radius: 50%;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .color-option.active {
            border-color: #059669;
            box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2);
        }

        .color-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
        }

        .clear-all-filters-btn {
            width: 100%;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .clear-all-filters-btn:hover {
            background: #dc2626;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 128, 96, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 128, 96, 0.2);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8fafc;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #008060;
            margin-bottom: 10px;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #64748b;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: scale(1.02);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 40px 0;
        }

        .page-btn {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .page-btn:hover, .page-btn.active {
            background: #008060;
            color: white;
            border-color: #008060;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .clear-filters-btn {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-filters-btn:hover {
            background: #cbd5e0;
            color: #2d3748;
        }

        .results-info {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
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
        <a href="../index.php#flash-deals" class="promo-shop-link">Shop Now</a>
    </div>

    <!-- Floating Bubbles Background -->
    <div class="floating-bubbles" id="floatingBubbles"></div>

    <!-- Main Header -->
    <header class="main-header animate__animated animate__fadeInDown">
        <div class="container-fluid" style="padding: 0 40px;">
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
                            <a href="../views/cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
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
                                <button class="dropdown-item-custom" onclick="goToAccount()">
                                    <i class="fas fa-user"></i>
                                    <span>Account</span>
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
                                    <li><a href="../all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="../views/all_product.php?brand=Apple"><i class="fas fa-tag"></i> Apple</a></li>
                                <li><a href="../views/all_product.php?brand=Samsung"><i class="fas fa-tag"></i> Samsung</a></li>
                                <li><a href="../views/all_product.php?brand=HP"><i class="fas fa-tag"></i> HP</a></li>
                                <li><a href="../views/all_product.php?brand=Dell"><i class="fas fa-tag"></i> Dell</a></li>
                                <li><a href="../views/all_product.php?brand=Sony"><i class="fas fa-tag"></i> Sony</a></li>
                                <li><a href="../views/all_product.php?brand=Canon"><i class="fas fa-tag"></i> Canon</a></li>
                                <li><a href="../views/all_product.php?brand=Nikon"><i class="fas fa-tag"></i> Nikon</a></li>
                                <li><a href="../views/all_product.php?brand=Microsoft"><i class="fas fa-tag"></i> Microsoft</a></li>
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
                                    <a href="../views/mobile_devices.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="mobile_devices">Mobile Devices</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
                                    <li><a href="../all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../views/computing.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="computing">Computing</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
                                    <li><a href="../all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../views/photography_video.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="photography_video">Photography & Video</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
                                    <li><a href="../all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column featured">
                                <h4>Shop All</h4>
                                <div class="featured-item">
                                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
                                    <div class="featured-text">
                                        <strong>New Arrivals</strong>
                                        <p>Latest tech gadgets</p>
                                        <a href="../views/all_product.php" class="shop-now-btn">Shop</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="../views/repair_services.php" class="nav-item"><span data-translate="repair_studio">REPAIR STUDIO</span></a>
                <a href="../views/device_drop.php" class="nav-item"><span data-translate="device_drop">DEVICE DROP</span></a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="more">MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="../views/contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                            <li><a href="../views/terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="../views/flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
            </div>
        </div>
    </nav>

    <!-- Page Title -->
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Left Sidebar - Filters -->
            <div class="col-lg-3 col-md-4" id="filterSidebar">
                <div class="filters-sidebar">
                    <div class="filter-header">
                        <h3 class="filter-title">
                            <i class="fas fa-filter"></i>
                            Filter Products
                        </h3>
                        <button class="filter-close d-lg-none" id="closeFilters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="filter-group">
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInput" placeholder="Search products..." autocomplete="off">
                            <i class="fas fa-search search-icon"></i>
                            <div id="searchSuggestions" class="search-suggestions" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Rating</h6>
                        <div class="rating-filter">
                            <div class="rating-option" data-rating="5">
                                <input type="radio" id="rating_5" name="rating_filter" value="5">
                                <label for="rating_5">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                    <span class="rating-text">5 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="4">
                                <input type="radio" id="rating_4" name="rating_filter" value="4">
                                <label for="rating_4">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">4 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="3">
                                <input type="radio" id="rating_3" name="rating_filter" value="3">
                                <label for="rating_3">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">3 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="2">
                                <input type="radio" id="rating_2" name="rating_filter" value="2">
                                <label for="rating_2">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">2 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="1">
                                <input type="radio" id="rating_1" name="rating_filter" value="1">
                                <label for="rating_1">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">1 Star</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Price Range</h6>
                        <div class="price-range-container">
                            <input type="range" class="price-slider" id="priceRange" min="0" max="5000" value="2500" step="10">
                            <div class="price-display">
                                <span>GH₵ 0</span>
                                <span>-</span>
                                <span>GH₵ 500</span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter by Category -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Category</h6>
                        <div class="tag-filters" id="categoryTags">
                            <button class="tag-btn active" data-category="" id="category_all_btn">All</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="tag-btn" data-category="<?php echo $category['cat_id']; ?>" id="category_btn_<?php echo $category['cat_id']; ?>">
                                    <?php echo htmlspecialchars($category['cat_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by Brand -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Brand</h6>
                        <div class="tag-filters" id="brandTags">
                            <button class="tag-btn active" data-brand="" id="brand_all_btn">All</button>
                            <?php foreach ($brands as $brand): ?>
                                <button class="tag-btn" data-brand="<?php echo $brand['brand_id']; ?>" id="brand_btn_<?php echo $brand['brand_id']; ?>">
                                    <?php echo htmlspecialchars($brand['brand_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by Size -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Size</h6>
                        <div class="size-filters">
                            <button class="size-btn active" data-size="">All</button>
                            <button class="size-btn" data-size="large">Large</button>
                            <button class="size-btn" data-size="medium">Medium</button>
                            <button class="size-btn" data-size="small">Small</button>
                        </div>
                    </div>

                    <!-- Filter by Color -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Color</h6>
                        <div class="color-filters">
                            <button class="color-btn active" data-color="" title="All Colors">
                                <span class="color-circle all-colors"></span>
                            </button>
                            <button class="color-btn" data-color="blue" title="Blue">
                                <span class="color-circle" style="background-color: #0066cc;"></span>
                            </button>
                            <button class="color-btn" data-color="gray" title="Gray">
                                <span class="color-circle" style="background-color: #808080;"></span>
                            </button>
                            <button class="color-btn" data-color="green" title="Green">
                                <span class="color-circle" style="background-color: #00aa00;"></span>
                            </button>
                            <button class="color-btn" data-color="red" title="Red">
                                <span class="color-circle" style="background-color: #dd0000;"></span>
                            </button>
                            <button class="color-btn" data-color="yellow" title="Yellow">
                                <span class="color-circle" style="background-color: #ffdd00;"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Apply/Clear Filters Buttons -->
                    <div class="filter-actions">
                        <button class="apply-filters-btn" id="applyFilters" style="display: none;">
                            <i class="fas fa-check"></i>
                            Apply Filters
                        </button>
                        <button class="clear-filters-btn" id="clearFilters">
                            <i class="fas fa-times"></i>
                            Clear All Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Content - Products -->
            <div class="col-lg-9 col-md-8" id="productContent">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title mb-0">Photography & Video</h1>
                    <button class="btn btn-outline-primary d-md-none" id="mobileFilterToggle">
                        <i class="fas fa-filter me-2"></i>
                        Filters
                    </button>
                </div>

        <!-- Products Grid -->
        <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="filter-select" id="categoryFilter" onchange="applyFilters()">
                        <option value="all">All Photography & Video</option>
                        <?php foreach ($photo_video_cats as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <select class="filter-select" id="brandFilter" onchange="applyFilters()">
                        <option value="all">All Brands</option>
                        <?php foreach ($photo_video_brands as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>" <?php echo $brand_filter == $brand['brand_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="clear-filters-btn w-100" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products_to_display)): ?>
            <div class="no-results">
                <i class="fas fa-camera fa-4x mb-3" style="color: #cbd5e0;"></i>
                <h3>No Photography & Video Products Found</h3>
                <p>Try adjusting your filters or search terms.</p>
                <a href="photography_video.php" class="btn btn-primary mt-3">
                    <i class="fas fa-refresh"></i> View All Photography & Video
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products_to_display as $product):
                    // Calculate random discount percentage (13% shown in your example)
                    $discount_percentage = rand(10, 25);
                    $original_price = $product['product_price'] * (1 + $discount_percentage / 100);
                    $rating = round(rand(40, 50) / 10, 1); // Random rating between 4.0-5.0
                ?>
                    <div class="modern-product-card" style="
                        background: white;
                        border-radius: 16px;
                        border: 1px solid #e5e7eb;
                        overflow: hidden;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        cursor: pointer;
                        position: relative;
                        transform-origin: center;
                    " onmouseover="this.style.transform='rotate(-2deg) scale(1.02)'; this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';"
                       onmouseout="this.style.transform='rotate(0deg) scale(1)'; this.style.boxShadow='0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';">
                        <!-- Discount Badge -->
                        <?php if ($discount_percentage > 0): ?>
                        <div style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; z-index: 10;">
                            -<?php echo $discount_percentage; ?>%
                        </div>
                        <?php endif; ?>
                        <!-- Wishlist Heart -->
                        <div style="position: absolute; top: 12px; right: 12px; z-index: 10;">
                            <button onclick="event.stopPropagation(); toggleWishlist(<?php echo $product['product_id']; ?>)"
                                    style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
                                    onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';">
                                <i class="far fa-heart" style="color: #6b7280; font-size: 16px;"></i>
                            </button>
                        </div>
                        <!-- Product Image -->
                        <div style="padding: 20px; text-align: center; height: 200px; display: flex; align-items: center; justify-content: center; background: #f9fafb;">
                            <?php
                            $image_url = get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? 'Product');
                            $fallback_url = generate_placeholder_url($product['product_title'] ?? 'Product', '400x300');
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>"
                                alt="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>"
                                style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($fallback_url); ?>';">
                        </div>
                        <!-- Product Content -->
                        <div style="padding: 25px;">
                            <!-- Product Title -->
                            <h3 style="color: #1f2937; font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; line-height: 1.4; cursor: pointer;" onclick="viewProductDetails(<?php echo $product['product_id']; ?>)">
                                <?php echo htmlspecialchars($product['product_title']); ?>
                            </h3>
                            <!-- Rating -->
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div style="color: #fbbf24; margin-right: 8px;">
                                    <?php
                                    $full_stars = floor($rating);
                                    $half_star = $rating - $full_stars >= 0.5;
                                    for($i = 0; $i < $full_stars; $i++) {
                                        echo '<i class="fas fa-star"></i>';
                                    }
                                    if($half_star) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                        $full_stars++;
                                    }
                                    for($i = $full_stars; $i < 5; $i++) {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <span style="color: #6b7280; font-size: 0.9rem; font-weight: 600;">(<?php echo $rating; ?>)</span>
                            </div>
                            <!-- Optional Status Text -->
                            <?php if (rand(1, 3) === 1): // Only show for some products ?>
                                <div style="margin-bottom: 12px;">
                                    <span style="background: #16a34a; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">In Stock</span>
                                </div>
                            <?php endif; ?>
                            <!-- Pricing -->
                            <div style="margin-bottom: 25px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span style="color: #4f46e5; font-size: 1.75rem; font-weight: 800;">
                                        GH₵<?php echo number_format($product['product_price'], 0); ?>
                                    </span>
                                    <span style="color: #9ca3af; font-size: 1.2rem; text-decoration: line-through;">
                                        GH₵<?php echo number_format($original_price, 0); ?>
                                    </span>
                                </div>
                                <div style="color: #6b7280; font-size: 0.85rem; margin-top: 4px;">
                                    Limited time offer - While supplies last
                                </div>
                            </div>
                            <!-- View Details Button -->
                            <button onclick="viewProductDetails(<?php echo $product['product_id']; ?>)"
                                    style="width: 100%; background: #4f46e5; color: white; border: none; padding: 15px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fas fa-eye"></i>
                                View Details
                            </button>

                            <!-- Installment Payment Info -->
                            <div style="margin-top: 12px; text-align: center;">
                                <p style="font-size: 0.75rem; color: #6b7280; margin: 4px 0; line-height: 1.3;">
                                    Pay in installment, with only your Ghana Card
                                </p>
                                <p style="font-size: 0.7rem; color: #9ca3af; margin: 4px 0; line-height: 1.3;">
                                    Contact us to Enroll in GadgetGarage's installment Plans
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&brand=<?php echo $brand_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page - 1; ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&brand=<?php echo $brand_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>"
                           class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&brand=<?php echo $brand_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page + 1; ?>" class="page-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/chatbot.js"></script>
    <script>
        // Filter System JavaScript
        let filtersChanged = false;

        function initPhotoFilters() {
            initCategoryFilter();
            initBrandFilter();
            initMobileFilterToggle();
        }

        function initCategoryFilter() {
            const categoryBtns = document.querySelectorAll('#categoryTags .tag-btn');
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    showApplyButton();
                });
            });
        }

        function initBrandFilter() {
            const brandBtns = document.querySelectorAll('#brandTags .tag-btn');
            brandBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    brandBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    showApplyButton();
                });
            });
        }

        function initMobileFilterToggle() {
            const mobileToggle = document.getElementById('mobileFilterToggle');
            const closeFilters = document.getElementById('closeFilters');
            const filterSidebar = document.getElementById('filterSidebar');

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    filterSidebar.classList.add('show');
                });
            }

            if (closeFilters) {
                closeFilters.addEventListener('click', function() {
                    filterSidebar.classList.remove('show');
                });
            }
        }

        function showApplyButton() {
            if (!filtersChanged) {
                filtersChanged = true;
                const applyBtn = document.getElementById('applyFilters');
                if (applyBtn) applyBtn.style.display = 'flex';
            }
        }

        // Clear filters function
        document.getElementById('clearAllFilters')?.addEventListener('click', function() {
            window.location.href = 'photography_video.php';
        });

        // Search input functionality
        document.getElementById('searchInput')?.addEventListener('input', function() {
            showApplyButton();
        });

        // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            initPhotoFilters();
        });
    </script>
    <script>
        function viewProduct(productId) {
            window.location.href = 'single_product.php?id=' + productId;
        }

        function viewProductDetails(productId) {
            window.location.href = 'single_product.php?pid=' + productId;
        }

        function toggleWishlist(productId) {
            // Check if user is logged in
            <?php if (!$is_logged_in): ?>
            window.location.href = 'login/login.php';
            return;
            <?php endif; ?>

            const heartIcon = event.target;
            const isWishlisted = heartIcon.classList.contains('fas');

            // Optimistic UI update
            if (isWishlisted) {
                heartIcon.className = 'far fa-heart';
                heartIcon.style.color = '#6b7280';
            } else {
                heartIcon.className = 'fas fa-heart';
                heartIcon.style.color = '#ef4444';
            }

            // Make API call
            fetch('actions/wishlist_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${isWishlisted ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert the UI change if the API call failed
                    if (isWishlisted) {
                        heartIcon.className = 'fas fa-heart';
                        heartIcon.style.color = '#ef4444';
                    } else {
                        heartIcon.className = 'far fa-heart';
                        heartIcon.style.color = '#6b7280';
                    }
                    console.error('Failed to update wishlist');
                }
            })
            .catch(error => {
                // Revert the UI change if there was an error
                if (isWishlisted) {
                    heartIcon.className = 'fas fa-heart';
                    heartIcon.style.color = '#ef4444';
                } else {
                    heartIcon.className = 'far fa-heart';
                    heartIcon.style.color = '#6b7280';
                }
                console.error('Error:', error);
            });
        }

        function addToCart(productId) {
            const btn = event.target.closest('.add-to-cart-btn');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('condition', 'excellent');
            formData.append('final_price', 0);

            fetch('actions/add_to_cart_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = 'linear-gradient(135deg, #008060, #006b4e)';
                        btn.disabled = false;
                    }, 1500);

                    // Update cart count
                    const cartBadge = document.getElementById('cartBadge');
                    if (cartBadge && data.cart_count) {
                        cartBadge.textContent = data.cart_count;
                        cartBadge.style.display = 'inline';
                    }
                } else {
                    btn.innerHTML = 'Error!';
                    btn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = 'linear-gradient(135deg, #008060, #006b4e)';
                        btn.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = 'Error!';
                btn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';

                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(135deg, #008060, #006b4e)';
                    btn.disabled = false;
                }, 2000);
            });
        }

        function applyFilters() {
            const category = document.getElementById('categoryFilter').value;
            const brand = document.getElementById('brandFilter').value;
            const search = '<?php echo addslashes($search_query); ?>';

            const params = new URLSearchParams();
            if (category !== 'all') params.append('category', category);
            if (brand !== 'all') params.append('brand', brand);
            if (search) params.append('search', search);

            window.location.href = 'photography_video.php?' + params.toString();
        }

        function clearFilters() {
            window.location.href = 'photography_video.php';
        }


        function generatePlaceholderUrl(text, size = '320x240') {
            if (typeof generatePlaceholderImage === 'function') {
                return generatePlaceholderImage(text, size);
            }

            const [width, height] = size.split('x').map(Number);
            const safeText = (text || 'Gadget Garage').substring(0, 32).replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
                <rect width="100%" height="100%" fill="#eef2ff"/>
                <rect x="1" y="1" width="${width - 2}" height="${height - 2}" fill="none" stroke="#cbd5f5" stroke-width="2"/>
                <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="${Math.max(Math.floor(height * 0.12), 14)}" fill="#1f2937" text-anchor="middle" dominant-baseline="middle">${safeText}</text>
            </svg>`;
            return `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svg)))}`;
        }

        // Floating Bubbles Animation
        function createFloatingBubbles() {
            const bubblesContainer = document.querySelector('.floating-bubbles');
            const colors = [
                'rgba(0, 128, 96, 0.1)',
                'rgba(0, 107, 78, 0.1)',
                'rgba(0, 150, 112, 0.1)'
            ];

            function createBubble() {
                const bubble = document.createElement('div');
                bubble.className = 'bubble';

                const size = Math.random() * 60 + 20;
                const color = colors[Math.floor(Math.random() * colors.length)];
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 10 + 10;
                const delay = Math.random() * 5;

                bubble.style.width = size + 'px';
                bubble.style.height = size + 'px';
                bubble.style.background = color;
                bubble.style.left = left + '%';
                bubble.style.animationDuration = animationDuration + 's';
                bubble.style.animationDelay = delay + 's';

                bubblesContainer.appendChild(bubble);

                setTimeout(() => {
                    if (bubblesContainer.contains(bubble)) {
                        bubblesContainer.removeChild(bubble);
                    }
                }, (animationDuration + delay) * 1000);
            }

            setInterval(createBubble, 300);
            for (let i = 0; i < 5; i++) {
                setTimeout(createBubble, i * 200);
            }
        }

        // Header dropdown functions
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            dropdown.classList.toggle('show');
        }

        function openProfilePictureModal() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Profile Picture',
                    text: 'Profile picture modal not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({title: 'Feature Coming Soon', text: 'Profile picture modal not implemented yet', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
            }
        }

        function changeLanguage(lang) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Language Change',
                    text: 'Language change to ' + lang + ' not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({title: 'Feature Coming Soon', text: 'Language change to ' + lang + ' not implemented yet', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
            }
        }

        function toggleTheme() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Theme Toggle',
                    text: 'Theme toggle not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({title: 'Feature Coming Soon', text: 'Theme toggle not implemented yet', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
            }
        }

        function showDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) dropdown.classList.add('show');
        }

        function hideDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function showShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) dropdown.classList.add('show');
        }

        function hideShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function showMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) dropdown.classList.add('show');
        }

        function hideMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createFloatingBubbles();
            // Images now load directly using get_product_image_url() helper function
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const target = event.target;
            const isDropdownButton = target.closest('.categories-button, .nav-item, .user-avatar');
            const isDropdownContent = target.closest('.mega-dropdown, .brands-dropdown, .simple-dropdown, .dropdown-menu-custom');

            if (!isDropdownButton && !isDropdownContent) {
                document.querySelectorAll('.mega-dropdown, .brands-dropdown, .simple-dropdown, .dropdown-menu-custom').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });

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
            window.location.href = '../views/my_orders.php';
        }

        // Theme toggle functionality
        function toggleTheme() {
            const toggleSwitch = document.getElementById('themeToggle');
            const body = document.body;

            body.classList.toggle('dark-mode');
            if (toggleSwitch) {
                toggleSwitch.classList.toggle('active');
            }

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
    </script>
</body>
</html>