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

// Get products from database filtered by mobile device categories
// Define mobile device categories (case-insensitive matching)
$mobile_categories = [
    'smartphones', 'ipads', 'tablets', 'mobile devices', 'phone', 'tablet', 'ipad',
    'Smartphones', 'iPads', 'Tablets', 'Mobile Devices', 'Phone', 'Tablet', 'iPad'
];

// Get all products first
$all_products = get_all_products_ctr();

// Filter for mobile device categories with improved logic
$mobile_products = array_filter($all_products, function ($product) use ($mobile_categories) {
    // Check category name directly
    if (in_array(strtolower($product['cat_name']), array_map('strtolower', $mobile_categories))) {
        return true;
    }

    // Check if category name contains mobile-related keywords
    $cat_lower = strtolower($product['cat_name']);
    if (strpos($cat_lower, 'mobile') !== false ||
        strpos($cat_lower, 'phone') !== false ||
        strpos($cat_lower, 'tablet') !== false ||
        strpos($cat_lower, 'ipad') !== false) {
        return true;
    }

    // Fallback: check product title for mobile keywords
    $title_lower = strtolower($product['product_title']);
    return (strpos($title_lower, 'phone') !== false ||
            strpos($title_lower, 'ipad') !== false ||
            strpos($title_lower, 'tablet') !== false ||
            strpos($title_lower, 'mobile') !== false);
});

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

// Filter products based on URL parameters
$category_filter = $_GET['category'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$condition_filter = $_GET['condition'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$filtered_products = $mobile_products;

if ($category_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function ($product) use ($category_filter) {
        return $product['cat_name'] === $category_filter;
    });
}

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
    <title>Mobile Devices - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        /* Import Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Transparent Logo Background */
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
            opacity: 0.15;
            z-index: -1;
            pointer-events: none;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background-color: #ffffff;
            color: #1a1a1a;
            overflow-x: hidden;
            position: relative;
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

        .nav-link {
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            font-size: 2rem;
            padding: 12px 0;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #008060;
        }

        .nav-link i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .dropdown:hover .nav-link i {
            transform: rotate(180deg);
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
            visibility: visible;
            transform: translateY(0px);
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
            border-left: 2px solid #f3f4f6;
            padding-left: 24px;
        }

        .featured-item {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 16px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .featured-item:hover {
            background: #f1f5f9;
        }

        .featured-item img {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }

        .featured-text {
            flex: 1;
        }

        .featured-text strong {
            color: #1f2937;
            font-size: 0.95rem;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }

        .featured-text p {
            color: #6b7280;
            font-size: 0.8rem;
            margin: 0;
        }

        .shop-now-btn {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 8px;
            display: inline-block;
        }

        .shop-now-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            color: white;
            transform: translateY(-1px);
        }

        /* Brands Dropdown - copied from index */
        .brands-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            min-width: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            color: #1f2937;
            font-size: 1rem;
            margin: 0 0 12px 0;
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
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .brands-dropdown a:hover {
            background: #f3f4f6;
            color: #008060;
        }

        /* Navigation Dropdown */
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

        .simple-dropdown li {
            padding: 0;
        }

        .simple-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .simple-dropdown a:hover {
            background: #f3f4f6;
            color: #008060;
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
        }

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

        /* Rating Filter Styles */
        .rating-filter {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rating-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 6px 0;
        }

        .rating-option input[type="radio"] {
            margin: 0;
        }

        .rating-option label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            margin: 0;
        }

        .stars {
            display: flex;
            gap: 2px;
            color: #fbbf24;
        }

        .stars i {
            font-size: 14px;
        }

        .rating-text {
            color: #6b7280;
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

        /* Size Filter Styles */
        .size-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .size-btn {
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
            flex-wrap: wrap;
        }

        .color-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .color-btn:hover {
            border-color: #008060;
            transform: scale(1.1);
        }

        .color-btn.active {
            border-color: #008060;
            transform: scale(1.1);
        }

        .color-btn.active::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .color-multicolor {
            background: conic-gradient(red, yellow, lime, cyan, blue, magenta, red);
        }

        .color-blue {
            background: #3b82f6;
        }

        .color-gray {
            background: #6b7280;
        }

        .color-green {
            background: #10b981;
        }

        .color-red {
            background: #ef4444;
        }

        .color-orange {
            background: #f97316;
        }

        /* Filter Action Buttons */
        .filter-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .apply-filters-btn {
            width: 100%;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .apply-filters-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-1px);
        }

        .clear-filters-btn {
            width: 100%;
            background: #dc2626;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .clear-filters-btn:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        /* Products Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .modern-product-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            transform-origin: center;
        }

        .modern-product-card:hover {
            transform: rotate(-2deg) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-info {
            padding: 20px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #008060;
            margin-bottom: 15px;
        }

        .add-to-cart-btn {
            width: 100%;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-1px);
        }

        .add-to-cart-btn:active {
            transform: translateY(0);
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 40px;
            gap: 10px;
        }

        .pagination-btn {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover {
            border-color: #008060;
            background: #008060;
            color: white;
        }

        .pagination-btn.active {
            background: linear-gradient(135deg, #008060, #006b4e);
            border-color: #008060;
            color: white;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn:disabled:hover {
            border-color: #e5e7eb;
            background: white;
            color: #374151;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                padding: 0 20px;
                gap: 15px;
                flex-wrap: wrap;
            }

            .page-title {
                font-size: 2rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .filters-container {
                padding: 15px;
            }

            .filter-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .filter-label {
                min-width: auto;
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
                                    <li><a href="../all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="../all_product.php?brand=Apple"><i class="fas fa-tag"></i> Apple</a></li>
                                <li><a href="../all_product.php?brand=Samsung"><i class="fas fa-tag"></i> Samsung</a></li>
                                <li><a href="../all_product.php?brand=HP"><i class="fas fa-tag"></i> HP</a></li>
                                <li><a href="../all_product.php?brand=Dell"><i class="fas fa-tag"></i> Dell</a></li>
                                <li><a href="../all_product.php?brand=Sony"><i class="fas fa-tag"></i> Sony</a></li>
                                <li><a href="../all_product.php?brand=Canon"><i class="fas fa-tag"></i> Canon</a></li>
                                <li><a href="../all_product.php?brand=Nikon"><i class="fas fa-tag"></i> Nikon</a></li>
                                <li><a href="../all_product.php?brand=Microsoft"><i class="fas fa-tag"></i> Microsoft</a></li>
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

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Left Sidebar - Filters -->
            <div class="col-lg-3 col-md-4" id="filterSidebar">
                <div class="filters-sidebar">
                    <div class="filter-header">
                        <h3 class="filter-title">
                            <i class="fas fa-sliders-h"></i>
                            Filter Mobile Devices
                        </h3>
                        <button class="filter-close d-lg-none" id="closeFilters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="filter-group">
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInput" placeholder="Search mobile devices..." autocomplete="off">
                            <i class="fas fa-search search-icon"></i>
                            <button type="button" class="search-clear-btn" id="searchClearBtn" style="display: none;" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                            </button>
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

                    <!-- Price Range -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Price Range</h6>
                        <div class="price-slider-container">
                            <div class="price-slider-track">
                                <div class="price-slider-range" id="priceRange"></div>
                                <input type="range" class="price-slider" id="minPriceSlider" min="0" max="50000" value="0" step="100">
                                <input type="range" class="price-slider" id="maxPriceSlider" min="0" max="50000" value="50000" step="100">
                            </div>
                            <div class="price-display">
                                <span class="price-min" id="priceMinDisplay">GH₵ 0</span>
                                <span class="price-separator">-</span>
                                <span class="price-max" id="priceMaxDisplay">GH₵ 50,000</span>
                            </div>
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
                        <button class="apply-filters-btn" id="applyFilters">
                            <i class="fas fa-filter"></i>
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
                    <h1 class="page-title mb-0" data-translate="mobile_devices">Mobile Devices</h1>
                    <button class="btn btn-outline-primary d-md-none" id="mobileFilterToggle">
                        <i class="fas fa-filter me-2"></i>
                        Filters
                    </button>
                </div>

                <!-- Results Info -->
                <div class="results-info" id="resultsInfo" style="display: none;">
                    <span id="resultsText">Showing all mobile devices</span>
                </div>

                <div id="productsContainer">
                    <?php if (empty($products_to_display)): ?>
                        <div class="no-products">
                            <div class="no-products-icon">📱</div>
                            <h3>No Mobile Devices Found</h3>
                            <p>There are no mobile devices available at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="product-grid" id="productGrid">
                            <?php foreach ($products_to_display as $product):
                                // Calculate random discount percentage
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
                                        <img src="<?= get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? 'Product') ?>"
                                            alt="<?= htmlspecialchars($product['product_title']) ?>"
                                            style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                            onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200';">
                                    </div>

                                    <!-- Product Info -->
                                    <div style="padding: 20px;">
                                        <h3 style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= htmlspecialchars($product['product_title']) ?>
                                        </h3>

                                        <!-- Rating Stars -->
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                            <div style="display: flex; color: #fbbf24;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= floor($rating) ? 'fas' : 'far' ?> fa-star" style="font-size: 14px;"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span style="font-size: 0.8rem; color: #6b7280;">(<?= $rating ?>)</span>
                                        </div>

                                        <!-- Price -->
                                        <div style="margin-bottom: 15px;">
                                            <div style="font-size: 1.3rem; font-weight: 700; color: #008060;">
                                                GH₵<?= number_format($product['product_price'], 2) ?>
                                            </div>
                                            <?php if ($discount_percentage > 0): ?>
                                                <div style="font-size: 0.9rem; color: #9ca3af; text-decoration: line-through;">
                                                    GH₵<?= number_format($original_price, 2) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- View Details Button -->
                                        <a href="single_product.php?id=<?= $product['product_id'] ?>" style="width: 100%; background: linear-gradient(135deg, #008060, #006b4e); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;"
                                            onmouseover="this.style.background='linear-gradient(135deg, #006b4e, #008060)'; this.style.transform='translateY(-1px)';"
                                            onmouseout="this.style.background='linear-gradient(135deg, #008060, #006b4e)'; this.style.transform='translateY(0)';">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>

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
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <?php if ($current_page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                class="pagination-btn <?= $i === $current_page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>" class="pagination-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Dropdown functions
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

            function showMoreDropdown() {
                document.getElementById('moreDropdown').classList.add('show');
            }

            function hideMoreDropdown() {
                document.getElementById('moreDropdown').classList.remove('show');
            }

            // User Dropdown Functions
            function toggleUserDropdown() {
                const dropdown = document.getElementById('userDropdownMenu');
                dropdown.classList.toggle('show');
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                const userDropdown = document.getElementById('userDropdownMenu');
                const userAvatar = document.querySelector('.user-avatar');

                if (userDropdown && !userAvatar.contains(event.target) && !userDropdown.contains(event.target)) {
                    userDropdown.classList.remove('show');
                }
            });

            // Add to Cart Function
            function addToCart(productId) {
                <?php if ($is_logged_in): ?>
                    fetch('../actions/add_to_cart_action.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'product_id=' + productId + '&qty=1'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update cart badge
                                const cartBadge = document.getElementById('cartBadge');
                                if (cartBadge) {
                                    cartBadge.textContent = data.cart_count;
                                    cartBadge.style.display = data.cart_count > 0 ? 'block' : 'none';
                                }

                                // Show success message
                                alert('Product added to cart successfully!');
                            } else {
                                alert('Error adding product to cart: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while adding the product to cart.');
                        });
                <?php else: ?>
                    alert('Please login to add items to cart.');
                    window.location.href = '../login/login.php';
                <?php endif; ?>
            }

            // Initialize cart badge
            document.addEventListener('DOMContentLoaded', function() {
                const cartBadge = document.getElementById('cartBadge');
                if (cartBadge) {
                    cartBadge.textContent = <?= $cart_count ?>;
                    cartBadge.style.display = <?= $cart_count ?> > 0 ? 'block' : 'none';
                }
            });

            // Promo Timer
            function updatePromoTimer() {
                const timer = document.getElementById('promoTimer');
                if (timer) {
                    const endDate = new Date();
                    endDate.setDate(endDate.getDate() + 12);

                    const now = new Date().getTime();
                    const distance = endDate.getTime() - now;

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    timer.textContent = days + "d:" + hours.toString().padStart(2, '0') + "h:" +
                        minutes.toString().padStart(2, '0') + "m:" +
                        seconds.toString().padStart(2, '0') + "s";

                    if (distance < 0) {
                        timer.textContent = "EXPIRED";
                    }
                }
            }

            // Update timer every second
            setInterval(updatePromoTimer, 1000);
            updatePromoTimer();

            // Advanced Filtering System
            let currentFilters = {
                search: '',
                rating: '',
                priceMin: 0,
                priceMax: 10000,
                brands: [],
                colors: []
            };

            function executeFilters() {
                // Show loading state
                const productGrid = document.getElementById('productGrid');
                if (productGrid) {
                    productGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Loading products...</div>';
                }

                // Apply filters via AJAX
                applyFilters();
            }

            function applyFilters() {
                const formData = new FormData();
                formData.append('action', 'combined_filter');
                formData.append('category_filter', 'Mobile'); // Fixed category for mobile page

                // Add all filter values
                if (currentFilters.search) formData.append('search_filter', currentFilters.search);
                if (currentFilters.rating) formData.append('rating_filter', currentFilters.rating);
                formData.append('price_min', currentFilters.priceMin);
                formData.append('price_max', currentFilters.priceMax);
                if (currentFilters.brands.length) formData.append('brand_filter', currentFilters.brands.join(','));
                if (currentFilters.colors.length) formData.append('color_filter', currentFilters.colors.join(','));

                fetch('../actions/product_actions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateProductGrid(data.products);
                            updateResultsInfo(data.total_count);
                        } else {
                            console.error('Filter error:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Network error:', error);
                        const productGrid = document.getElementById('productGrid');
                        if (productGrid) {
                            productGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><i class="fas fa-exclamation-triangle fa-2x"></i><br><br>Error loading products. Please try again.</div>';
                        }
                    });
            }

            function updateProductGrid(products) {
                const productGrid = document.getElementById('productGrid');
                if (!productGrid) return;

                if (!products || products.length === 0) {
                    productGrid.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-mobile-alt fa-4x mb-3" style="color: #cbd5e0;"></i>
                        <h3>No Mobile Devices Found</h3>
                        <p>Try adjusting your filters or search terms.</p>
                        <button onclick="clearAllFilters()" class="btn btn-primary mt-3">
                            <i class="fas fa-refresh"></i> Clear All Filters
                        </button>
                    </div>
                `;
                    return;
                }

                const productsHtml = products.map(product => {
                    const discount = Math.floor(Math.random() * 16) + 10;
                    const originalPrice = parseFloat(product.product_price) * (1 + discount / 100);
                    const rating = (Math.random() * 1 + 4).toFixed(1);
                    const imageUrl = product.image_url || generatePlaceholderUrl(product.product_title || 'Product', '400x300');

                    return `
                    <div class="modern-product-card" style="
                        background: white;
                        border-radius: 16px;
                        border: 1px solid #e5e7eb;
                        overflow: hidden;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        cursor: pointer;
                        position: relative;
                        transform-origin: center;
                    " onmouseover="this.style.transform='rotate(-1deg) scale(1.02)'; this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';"
                       onmouseout="this.style.transform='rotate(0deg) scale(1)'; this.style.boxShadow='0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';">

                        <div style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; z-index: 10;">
                            -${discount}%
                        </div>

                        <div style="position: absolute; top: 12px; right: 12px; z-index: 10;">
                            <button onclick="event.stopPropagation(); toggleWishlist(${product.product_id})"
                                    style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
                                    onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';">
                                <i class="far fa-heart" style="color: #6b7280; font-size: 16px;"></i>
                            </button>
                        </div>

                        <div style="padding: 20px; text-align: center; height: 200px; display: flex; align-items: center; justify-content: center; background: #f9fafb;">
                            <img src="${imageUrl}"
                                alt="${product.product_title || 'Product'}"
                                style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                onerror="this.src='${generatePlaceholderUrl(product.product_title || 'Product', '400x300')}'">
                        </div>

                        <div style="padding: 25px;">
                            <h3 style="color: #1f2937; font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; line-height: 1.4; cursor: pointer;" onclick="viewProductDetails(${product.product_id})">
                                ${product.product_title || 'Untitled Product'}
                            </h3>

                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div style="color: #fbbf24; margin-right: 8px;">
                                    ${'★'.repeat(Math.floor(rating))}${'☆'.repeat(5 - Math.floor(rating))}
                                </div>
                                <span style="color: #6b7280; font-size: 0.9rem; font-weight: 600;">(${rating})</span>
                            </div>

                            <div style="margin-bottom: 12px;">
                                <span style="background: #16a34a; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">In Stock</span>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                                <div>
                                    <span style="color: #6b7280; text-decoration: line-through; font-size: 1rem; margin-right: 8px;">
                                        GH₵${originalPrice.toFixed(2)}
                                    </span>
                                    <span style="color: #ef4444; font-size: 1.4rem; font-weight: 800;">
                                        GH₵${parseFloat(product.product_price).toFixed(2)}
                                    </span>
                                </div>
                            </div>

                            <button class="add-to-cart-btn" onclick="addToCart(${product.product_id})"
                                style="width: 100%; padding: 12px; background: linear-gradient(135deg, #008060, #006b4e); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                `;
                }).join('');

                productGrid.innerHTML = productsHtml;
            }

            function updateResultsInfo(totalCount) {
                const resultsInfo = document.getElementById('resultsInfo');
                const resultsText = document.getElementById('resultsText');

                if (resultsInfo && resultsText) {
                    if (totalCount > 0) {
                        resultsText.textContent = `Showing ${totalCount} mobile devices`;
                        resultsInfo.style.display = 'block';
                    } else {
                        resultsInfo.style.display = 'none';
                    }
                }
            }

            // Filter interaction handlers
            function setupFilterHandlers() {
                // Search input
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.addEventListener('input', debounce(function() {
                        currentFilters.search = this.value;
                        executeFilters();
                    }, 500));
                }

                // Rating filters
                document.querySelectorAll('.rating-option').forEach(option => {
                    option.addEventListener('click', function() {
                        document.querySelectorAll('.rating-option').forEach(opt => opt.classList.remove('active'));
                        this.classList.add('active');
                        currentFilters.rating = this.dataset.rating || '';
                        executeFilters();
                    });
                });

                // Price sliders
                const priceMin = document.getElementById('priceMin');
                const priceMax = document.getElementById('priceMax');
                const minDisplay = document.getElementById('minPriceDisplay');
                const maxDisplay = document.getElementById('maxPriceDisplay');

                if (priceMin && priceMax) {
                    function updatePriceDisplay() {
                        const min = parseInt(priceMin.value);
                        const max = parseInt(priceMax.value);

                        if (min > max) {
                            priceMin.value = max;
                        }
                        if (max < min) {
                            priceMax.value = min;
                        }

                        currentFilters.priceMin = parseInt(priceMin.value);
                        currentFilters.priceMax = parseInt(priceMax.value);

                        if (minDisplay) minDisplay.textContent = `GH₵${currentFilters.priceMin}`;
                        if (maxDisplay) maxDisplay.textContent = `GH₵${currentFilters.priceMax}`;
                    }

                    priceMin.addEventListener('input', debounce(function() {
                        updatePriceDisplay();
                        executeFilters();
                    }, 300));

                    priceMax.addEventListener('input', debounce(function() {
                        updatePriceDisplay();
                        executeFilters();
                    }, 300));

                    updatePriceDisplay();
                }

                // Brand tags
                document.querySelectorAll('#brandTags .tag-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const brand = this.dataset.brand;
                        if (brand === '') {
                            currentFilters.brands = [];
                            document.querySelectorAll('#brandTags .tag-btn').forEach(b => b.classList.remove('active'));
                            this.classList.add('active');
                        } else {
                            const allBtn = document.querySelector('#brandTags .tag-btn[data-brand=""]');
                            if (allBtn) allBtn.classList.remove('active');

                            this.classList.toggle('active');
                            const index = currentFilters.brands.indexOf(brand);
                            if (index > -1) {
                                currentFilters.brands.splice(index, 1);
                            } else {
                                currentFilters.brands.push(brand);
                            }

                            if (currentFilters.brands.length === 0) {
                                if (allBtn) allBtn.classList.add('active');
                            }
                        }
                        executeFilters();
                    });
                });

                // Color filters
                document.querySelectorAll('#colorTags .color-option').forEach(option => {
                    option.addEventListener('click', function() {
                        const color = this.dataset.color;
                        if (color === '') {
                            currentFilters.colors = [];
                            document.querySelectorAll('#colorTags .color-option').forEach(opt => opt.classList.remove('active'));
                            this.classList.add('active');
                        } else {
                            const allOption = document.querySelector('#colorTags .color-option[data-color=""]');
                            if (allOption) allOption.classList.remove('active');

                            this.classList.toggle('active');
                            const index = currentFilters.colors.indexOf(color);
                            if (index > -1) {
                                currentFilters.colors.splice(index, 1);
                            } else {
                                currentFilters.colors.push(color);
                            }

                            if (currentFilters.colors.length === 0) {
                                if (allOption) allOption.classList.add('active');
                            }
                        }
                        executeFilters();
                    });
                });

                // Clear all filters
                const clearAllBtn = document.getElementById('clearAllFilters');
                if (clearAllBtn) {
                    clearAllBtn.addEventListener('click', clearAllFilters);
                }
            }

            function clearAllFilters() {
                // Reset filter state
                currentFilters = {
                    search: '',
                    rating: '',
                    priceMin: 0,
                    priceMax: 10000,
                    categories: [],
                    brands: [],
                    sizes: [],
                    colors: []
                };

                // Reset UI elements
                const searchInput = document.getElementById('searchInput');
                if (searchInput) searchInput.value = '';

                document.querySelectorAll('.rating-option').forEach(opt => opt.classList.remove('active'));
                document.querySelector('.rating-option[data-rating=""]')?.classList.add('active');

                const priceMin = document.getElementById('priceMin');
                const priceMax = document.getElementById('priceMax');
                if (priceMin) priceMin.value = 0;
                if (priceMax) priceMax.value = 10000;

                document.getElementById('minPriceDisplay').textContent = 'GH₵0';
                document.getElementById('maxPriceDisplay').textContent = 'GH₵10,000';

                // Reset all tag filters
                document.querySelectorAll('.tag-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tag-btn[data-category=""], .tag-btn[data-brand=""], .tag-btn[data-size=""]').forEach(btn => btn.classList.add('active'));

                // Reset color filters
                document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('active'));
                document.querySelector('.color-option[data-color=""]')?.classList.add('active');

                // Apply cleared filters
                executeFilters();
            }

            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            function generatePlaceholderUrl(text, size = '320x240') {
                const [width, height] = size.split('x').map(Number);
                const safeText = (text || 'Mobile Device').substring(0, 32).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
                <rect width="100%" height="100%" fill="#eef2ff"/>
                <rect x="1" y="1" width="${width - 2}" height="${height - 2}" fill="none" stroke="#cbd5f5" stroke-width="2"/>
                <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="${Math.max(Math.floor(height * 0.12), 14)}" fill="#1f2937" text-anchor="middle" dominant-baseline="middle">${safeText}</text>
            </svg>`;
                return `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svg)))}`;
            }\
            n\ n // Enhanced Filter functionality\n        document.addEventListener('DOMContentLoaded', function() {\n            // Initialize price slider\n            initPriceSlider();\n            \n            // Category filter buttons\n            const categoryButtons = document.querySelectorAll('#categoryTags .tag-btn');\n            categoryButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    categoryButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Brand filter buttons\n            const brandButtons = document.querySelectorAll('#brandTags .tag-btn');\n            brandButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    brandButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Size filter buttons\n            const sizeButtons = document.querySelectorAll('#sizeTags .size-btn');\n            sizeButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    sizeButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Color filter buttons\n            const colorButtons = document.querySelectorAll('#colorTags .color-btn');\n            colorButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    colorButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Apply filters button\n            const applyButton = document.getElementById('applyFilters');\n            if (applyButton) {\n                applyButton.addEventListener('click', function() {\n                    filterProducts();\n                });\n            }\n\n            // Clear filters\n            const clearButton = document.getElementById('clearFilters');\n            if (clearButton) {\n                clearButton.addEventListener('click', function() {\n                    // Reset all filters\n                    categoryButtons.forEach(btn => btn.classList.remove('active'));\n                    brandButtons.forEach(btn => btn.classList.remove('active'));\n                    sizeButtons.forEach(btn => btn.classList.remove('active'));\n                    colorButtons.forEach(btn => btn.classList.remove('active'));\n                    \n                    document.querySelector('#categoryTags .tag-btn[data-category=\"all\"]').classList.add('active');\n                    document.querySelector('#brandTags .tag-btn[data-brand=\"all\"]').classList.add('active');\n                    document.querySelector('#sizeTags .size-btn[data-size=\"all\"]').classList.add('active');\n                    document.querySelector('#colorTags .color-btn[data-color=\"all\"]').classList.add('active');\n                    \n                    document.getElementById('searchInput').value = '';\n                    \n                    // Reset rating\n                    const ratingInputs = document.querySelectorAll('input[name=\"rating_filter\"]');\n                    ratingInputs.forEach(input => input.checked = false);\n                    \n                    // Reset price sliders\n                    document.getElementById('minPriceSlider').value = 0;\n                    document.getElementById('maxPriceSlider').value = 50000;\n                    updatePriceDisplay();\n                    \n                    // Show all products\n                    filterProducts();\n                });\n            }\n        });\n\n        // Price slider functionality\n        function initializePriceSlider() {\n            const minSlider = document.getElementById('minPriceSlider');\n            const maxSlider = document.getElementById('maxPriceSlider');\n            const priceRange = document.getElementById('priceRange');\n\n            function updatePriceSlider() {\n                const minVal = parseInt(minSlider.value);\n                const maxVal = parseInt(maxSlider.value);\n\n                if (minVal > maxVal - 1000) {\n                    if (this === minSlider) {\n                        minSlider.value = maxVal - 1000;\n                    } else {\n                        maxSlider.value = minVal + 1000;\n                    }\n                }\n\n                const minPercent = ((minSlider.value - minSlider.min) / (minSlider.max - minSlider.min)) * 100;\n                const maxPercent = ((maxSlider.value - minSlider.min) / (maxSlider.max - minSlider.min)) * 100;\n\n                priceRange.style.left = minPercent + '%';\n                priceRange.style.width = (maxPercent - minPercent) + '%';\n\n                updatePriceDisplay();\n            }\n\n            minSlider.addEventListener('input', updatePriceSlider);\n            maxSlider.addEventListener('input', updatePriceSlider);\n            \n            updatePriceSlider();\n        }\n\n        function updatePriceDisplay() {\n            const minVal = parseInt(document.getElementById('minPriceSlider').value);\n            const maxVal = parseInt(document.getElementById('maxPriceSlider').value);\n            \n            document.getElementById('priceMinDisplay').textContent = 'GH₵ ' + minVal.toLocaleString();\n            document.getElementById('priceMaxDisplay').textContent = 'GH₵ ' + maxVal.toLocaleString();\n        }\n\n        function filterProducts() {\n            const activeCategory = document.querySelector('#categoryTags .tag-btn.active')?.dataset.category || 'all';\n            const activeBrand = document.querySelector('#brandTags .tag-btn.active')?.dataset.brand || 'all';\n            const activeSize = document.querySelector('#sizeTags .size-btn.active')?.dataset.size || 'all';\n            const activeColor = document.querySelector('#colorTags .color-btn.active')?.dataset.color || 'all';\n            const searchTerm = document.getElementById('searchInput').value.toLowerCase();\n            const selectedRating = document.querySelector('input[name=\"rating_filter\"]:checked')?.value;\n            const minPrice = parseInt(document.getElementById('minPriceSlider').value);\n            const maxPrice = parseInt(document.getElementById('maxPriceSlider').value);\n            \n            const productCards = document.querySelectorAll('.modern-product-card');\n            let visibleCount = 0;\n            \n            productCards.forEach(card => {\n                const title = card.querySelector('h3').textContent.toLowerCase();\n                const priceText = card.querySelector('[style*=\"font-size: 1.3rem\"]').textContent;\n                const price = parseFloat(priceText.replace('GH₵', '').replace(',', ''));\n                \n                // Check if product matches filters\n                let matchesCategory = activeCategory === 'all' || title.includes(activeCategory.toLowerCase());\n                let matchesBrand = activeBrand === 'all' || title.includes(activeBrand.toLowerCase());\n                let matchesSearch = searchTerm === '' || title.includes(searchTerm);\n                let matchesPrice = price >= minPrice && price <= maxPrice;\n                let matchesSize = activeSize === 'all'; // Size logic can be enhanced based on product data\n                let matchesColor = activeColor === 'all'; // Color logic can be enhanced based on product data\n                let matchesRating = !selectedRating; // Rating logic can be enhanced based on product data\n                \n                if (matchesCategory && matchesBrand && matchesSearch && matchesPrice && matchesSize && matchesColor && matchesRating) {\n                    card.style.display = 'block';\n                    visibleCount++;\n                } else {\n                    card.style.display = 'none';\n                }\n            });\n            \n            // Update count display\n            const countDisplay = document.querySelector('.product-count');\n            if (countDisplay) {\n                countDisplay.innerHTML = `<i class=\"fas fa-mobile-alt\" style=\"margin-right: 8px;\"></i>Showing ${visibleCount} mobile devices`;\n            }\n        }
            // Additional functions from login.php\n        // Account page navigation\n        function goToAccount() {\n            window.location.href = 'my_orders.php';\n        }\n\n        // Language change functionality\n        function changeLanguage(lang) {\n            // Language change functionality can be implemented here\n            console.log('Language changed to:', lang);\n        }\n\n        // Theme toggle functionality\n        function toggleTheme() {\n            const toggleSwitch = document.getElementById('themeToggle');\n            const body = document.body;\n\n            body.classList.toggle('dark-mode');\n            toggleSwitch.classList.toggle('active');\n\n            // Save theme preference to localStorage\n            const isDarkMode = body.classList.contains('dark-mode');\n            localStorage.setItem('darkMode', isDarkMode);\n        }\n\n        // Load theme preference on page load\n        document.addEventListener('DOMContentLoaded', function() {\n            const isDarkMode = localStorage.getItem('darkMode') === 'true';\n            const toggleSwitch = document.getElementById('themeToggle');\n\n            if (isDarkMode) {\n                document.body.classList.add('dark-mode');\n                if (toggleSwitch) {\n                    toggleSwitch.classList.add('active');\n                }\n            }\n        });\n\n        // Timeout variables\n        let shopDropdownTimeout;\n        let moreDropdownTimeout;\n        // Wishlist functionality
        window.toggleWishlist = function(productId, button) {
            <?php if (!$is_logged_in): ?>
                window.location.href = '../login/login.php';
                return;
            <?php endif; ?>

            const icon = button.querySelector('i');
            const isActive = button.classList.contains('active');

            if (isActive) {
                // Remove from wishlist
                button.classList.remove('active');
                icon.className = 'far fa-heart';
                icon.style.color = '#6b7280';

                // Make AJAX call to remove from wishlist
                fetch('../actions/remove_from_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update wishlist badge if exists
                        const wishlistBadge = document.getElementById('wishlistBadge');
                        if (wishlistBadge) {
                            let count = parseInt(wishlistBadge.textContent) || 0;
                            count = Math.max(0, count - 1);
                            wishlistBadge.textContent = count;
                            wishlistBadge.style.display = count > 0 ? 'flex' : 'none';
                        }
                    } else {
                        // Revert if failed
                        button.classList.add('active');
                        icon.className = 'fas fa-heart';
                        icon.style.color = '#ef4444';
                        if (data.message) alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert if failed
                    button.classList.add('active');
                    icon.className = 'fas fa-heart';
                    icon.style.color = '#ef4444';
                });
            } else {
                // Add to wishlist
                button.classList.add('active');
                icon.className = 'fas fa-heart';
                icon.style.color = '#ef4444';

                // Make AJAX call to add to wishlist
                fetch('../actions/add_to_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update wishlist badge
                        const wishlistBadge = document.getElementById('wishlistBadge');
                        if (wishlistBadge) {
                            let count = parseInt(wishlistBadge.textContent) || 0;
                            count++;
                            wishlistBadge.textContent = count;
                            wishlistBadge.style.display = 'flex';
                        }
                    } else {
                        // Revert button state if failed
                        button.classList.remove('active');
                        icon.className = 'far fa-heart';
                        icon.style.color = '#6b7280';
                        if (data.message) {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert button state if failed
                    button.classList.remove('active');
                    icon.className = 'far fa-heart';
                    icon.style.color = '#6b7280';
                });
            }
        };

        // Load wishlist status on page load
        <?php if ($is_logged_in): ?>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('../actions/get_wishlist_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=0'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.wishlist_items) {
                    // Update wishlist badge
                    const wishlistBadge = document.getElementById('wishlistBadge');
                    if (wishlistBadge && data.count > 0) {
                        wishlistBadge.textContent = data.count;
                        wishlistBadge.style.display = 'flex';
                    }

                    // Update wishlist heart buttons
                    const wishlistButtons = document.querySelectorAll('.wishlist-btn');
                    wishlistButtons.forEach(button => {
                        const onclickAttr = button.getAttribute('onclick');
                        if (onclickAttr) {
                            const match = onclickAttr.match(/toggleWishlist\((\d+)/);
                            if (match) {
                                const productId = parseInt(match[1]);
                                if (data.wishlist_items.includes(productId)) {
                                    button.classList.add('active');
                                    const icon = button.querySelector('i');
                                    if (icon) {
                                        icon.className = 'fas fa-heart';
                                        icon.style.color = '#ef4444';
                                    }
                                }
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Error loading wishlist status:', error));
        });
        <?php endif; ?>

        </script>

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

    <script>
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