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

// Get real products from database
$all_products = get_all_products_ctr();

// Filter for mobile device categories
$mobile_categories = ['smartphones', 'ipads', 'tablets', 'Smartphones', 'iPads', 'Tablets', 'Phone', 'iPad', 'Tablet'];
$mobile_products = array_filter($all_products, function($product) use ($mobile_categories) {
    return in_array($product['cat_name'], $mobile_categories) ||
           stripos($product['product_title'], 'phone') !== false ||
           stripos($product['product_title'], 'ipad') !== false ||
           stripos($product['product_title'], 'tablet') !== false ||
           stripos($product['cat_name'], 'mobile') !== false;
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
    $filtered_products = array_filter($filtered_products, function($product) use ($category_filter) {
        return $product['cat_name'] === $category_filter;
    });
}

if ($brand_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function($product) use ($brand_filter) {
        return $product['brand_name'] === $brand_filter;
    });
}

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
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

        /* Promotional Banner Styles */
        .promo-banner {
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

        .promo-banner-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 0 0 auto;
        }

        .promo-banner-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex: 1;
        }

        .promo-banner i {
            font-size: 1rem;
        }

        .promo-banner .promo-text {
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
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            color: #4b5563;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .header-icon:hover {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 95, 191, 0.3);
        }

        .cart-badge, .wishlist-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }

        .login-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 95, 191, 0.3);
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(139, 95, 191, 0.3);
        }

        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            min-width: 220px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            padding: 8px 0;
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
            padding: 12px 16px;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item-custom:hover {
            background: #f8fafc;
            color: #008060;
        }

        .dropdown-divider-custom {
            height: 1px;
            background: #e5e7eb;
            margin: 8px 0;
        }

        .language-selector, .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .toggle-switch {
            position: relative;
            width: 36px;
            height: 20px;
            background: #e5e7eb;
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
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(16px);
        }

        /* Main Navigation - Exact copy from index.php */
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
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin: 20px 0;
        }

        .price-slider-range {
            position: absolute;
            height: 4px;
            background: #008060;
            border-radius: 2px;
        }

        .price-slider {
            position: absolute;
            width: 100%;
            height: 4px;
            background: transparent;
            -webkit-appearance: none;
            appearance: none;
            outline: none;
            pointer-events: auto;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: #008060;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #008060;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #000000;
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
    </style>
</head>

<body>
    <!-- Promotional Banner -->
    <div class="promo-banner">
        <div class="promo-banner-left">
            <i class="fas fa-bolt"></i>
        </div>
        <div class="promo-banner-center">
            <span class="promo-text" data-translate="black_friday_deals">BLACK FRIDAY DEALS STOREWIDE! SHOP AMAZING DISCOUNTS! </span>
            <span class="promo-timer" id="promoTimer">12d:00h:00m:00s</span>
        </div>
        <a href="#flash-deals" class="promo-shop-link" data-translate="shop_now">Shop Now</a>
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
                            <a href="wishlist.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-heart"></i>
                                <span class="wishlist-badge" id="wishlistBadge" style="display: none;">0</span>
                            </a>
                        </div>

                        <!-- Cart Icon -->
                        <div class="header-icon">
                            <a href="cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
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
                            <input type="text" class="search-input" id="searchInput" placeholder="Search products..." autocomplete="off">
                            <i class="fas fa-search search-icon"></i>
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

                    <!-- Filter by Category -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Category</h6>
                        <div class="tag-filters" id="categoryTags">
                            <button class="tag-btn active" data-category="all">All</button>
                            <button class="tag-btn" data-category="smartphones">Smartphones</button>
                            <button class="tag-btn" data-category="ipads">iPads</button>
                            <button class="tag-btn" data-category="tablets">Tablets</button>
                        </div>
                    </div>

                    <!-- Filter by Brand -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Brand</h6>
                        <div class="tag-filters" id="brandTags">
                            <button class="tag-btn active" data-brand="all">All</button>
                            <?php if (!empty($brands)): ?>
                                <?php $displayed_brands = array_slice($brands, 0, 8); ?>
                                <?php foreach ($displayed_brands as $brand): ?>
                                    <button class="tag-btn" data-brand="<?= htmlspecialchars($brand['brand_name']) ?>"><?= htmlspecialchars($brand['brand_name']) ?></button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Filter by Size -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Size</h6>
                        <div class="size-filters" id="sizeTags">
                            <button class="size-btn active" data-size="all">All</button>
                            <button class="size-btn" data-size="large">Large</button>
                            <button class="size-btn" data-size="medium">Medium</button>
                            <button class="size-btn" data-size="small">Small</button>
                        </div>
                    </div>

                    <!-- Filter by Color -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Color</h6>
                        <div class="color-filters" id="colorTags">
                            <button class="color-btn color-multicolor active" data-color="all" title="All Colors"></button>
                            <button class="color-btn color-blue" data-color="blue" title="Blue"></button>
                            <button class="color-btn color-gray" data-color="gray" title="Gray"></button>
                            <button class="color-btn color-green" data-color="green" title="Green"></button>
                            <button class="color-btn color-red" data-color="red" title="Red"></button>
                            <button class="color-btn color-orange" data-color="orange" title="Orange"></button>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="apply-filters-btn" id="applyFilters">
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
                <div class="stats-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div class="product-count" style="color: #6b7280; font-size: 0.9rem;">
                            <i class="fas fa-mobile-alt" style="margin-right: 8px;"></i>
                            Showing <?php echo count($products_to_display); ?> of <?php echo $total_products; ?> mobile devices
                        </div>
                        <!-- Sort Dropdown -->
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="color: #6b7280; font-size: 0.9rem; font-weight: 500;">Sort by:</span>
                            <select id="sortSelect" onchange="sortProducts()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; font-size: 0.9rem; cursor: pointer;">
                                <option value="alphabetically-az">Alphabetically, A-Z</option>
                                <option value="alphabetically-za">Alphabetically, Z-A</option>
                                <option value="price-low-high">Price, low to high</option>
                                <option value="price-high-low">Price, high to low</option>
                                <option value="newest">Date, new to old</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="color: #6b7280; font-size: 0.9rem; font-weight: 500;"><?php echo $total_products; ?> Products</span>
                        <div class="view-toggle" style="display: flex; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;">
                            <button class="view-btn active" onclick="toggleView('grid')" title="Grid View" style="padding: 8px 12px; border: none; background: #2563eb; color: white; cursor: pointer;">
                                <i class="fas fa-th"></i>
                            </button>
                            <button class="view-btn" onclick="toggleView('list')" title="List View" style="padding: 8px 12px; border: none; background: white; color: #6b7280; cursor: pointer;">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
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
                                        <img src="<?= function_exists('get_image_url') ? get_image_url($product['product_image'], 300, 200) : 'https://via.placeholder.com/300x200' ?>"
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
                                                <?php for($i = 1; $i <= 5; $i++): ?>
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
        // Navigation Dropdown Functions
        function showDropdown() {
            document.getElementById('shopDropdown').classList.add('show');
        }

        function hideDropdown() {
            document.getElementById('shopDropdown').classList.remove('show');
        }

        function showShopDropdown() {
            document.getElementById('shopCategoryDropdown').classList.add('show');
        }

        function hideShopDropdown() {
            document.getElementById('shopCategoryDropdown').classList.remove('show');
        }

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
        updatePromoTimer();\n\n        // Enhanced Filter functionality\n        document.addEventListener('DOMContentLoaded', function() {\n            // Initialize price slider\n            initializePriceSlider();\n            \n            // Category filter buttons\n            const categoryButtons = document.querySelectorAll('#categoryTags .tag-btn');\n            categoryButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    categoryButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Brand filter buttons\n            const brandButtons = document.querySelectorAll('#brandTags .tag-btn');\n            brandButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    brandButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Size filter buttons\n            const sizeButtons = document.querySelectorAll('#sizeTags .size-btn');\n            sizeButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    sizeButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Color filter buttons\n            const colorButtons = document.querySelectorAll('#colorTags .color-btn');\n            colorButtons.forEach(button => {\n                button.addEventListener('click', function() {\n                    colorButtons.forEach(btn => btn.classList.remove('active'));\n                    this.classList.add('active');\n                });\n            });\n\n            // Apply filters button\n            const applyButton = document.getElementById('applyFilters');\n            if (applyButton) {\n                applyButton.addEventListener('click', function() {\n                    filterProducts();\n                });\n            }\n\n            // Clear filters\n            const clearButton = document.getElementById('clearFilters');\n            if (clearButton) {\n                clearButton.addEventListener('click', function() {\n                    // Reset all filters\n                    categoryButtons.forEach(btn => btn.classList.remove('active'));\n                    brandButtons.forEach(btn => btn.classList.remove('active'));\n                    sizeButtons.forEach(btn => btn.classList.remove('active'));\n                    colorButtons.forEach(btn => btn.classList.remove('active'));\n                    \n                    document.querySelector('#categoryTags .tag-btn[data-category=\"all\"]').classList.add('active');\n                    document.querySelector('#brandTags .tag-btn[data-brand=\"all\"]').classList.add('active');\n                    document.querySelector('#sizeTags .size-btn[data-size=\"all\"]').classList.add('active');\n                    document.querySelector('#colorTags .color-btn[data-color=\"all\"]').classList.add('active');\n                    \n                    document.getElementById('searchInput').value = '';\n                    \n                    // Reset rating\n                    const ratingInputs = document.querySelectorAll('input[name=\"rating_filter\"]');\n                    ratingInputs.forEach(input => input.checked = false);\n                    \n                    // Reset price sliders\n                    document.getElementById('minPriceSlider').value = 0;\n                    document.getElementById('maxPriceSlider').value = 50000;\n                    updatePriceDisplay();\n                    \n                    // Show all products\n                    filterProducts();\n                });\n            }\n        });\n\n        // Price slider functionality\n        function initializePriceSlider() {\n            const minSlider = document.getElementById('minPriceSlider');\n            const maxSlider = document.getElementById('maxPriceSlider');\n            const priceRange = document.getElementById('priceRange');\n\n            function updatePriceSlider() {\n                const minVal = parseInt(minSlider.value);\n                const maxVal = parseInt(maxSlider.value);\n\n                if (minVal > maxVal - 1000) {\n                    if (this === minSlider) {\n                        minSlider.value = maxVal - 1000;\n                    } else {\n                        maxSlider.value = minVal + 1000;\n                    }\n                }\n\n                const minPercent = ((minSlider.value - minSlider.min) / (minSlider.max - minSlider.min)) * 100;\n                const maxPercent = ((maxSlider.value - minSlider.min) / (maxSlider.max - minSlider.min)) * 100;\n\n                priceRange.style.left = minPercent + '%';\n                priceRange.style.width = (maxPercent - minPercent) + '%';\n\n                updatePriceDisplay();\n            }\n\n            minSlider.addEventListener('input', updatePriceSlider);\n            maxSlider.addEventListener('input', updatePriceSlider);\n            \n            updatePriceSlider();\n        }\n\n        function updatePriceDisplay() {\n            const minVal = parseInt(document.getElementById('minPriceSlider').value);\n            const maxVal = parseInt(document.getElementById('maxPriceSlider').value);\n            \n            document.getElementById('priceMinDisplay').textContent = 'GH₵ ' + minVal.toLocaleString();\n            document.getElementById('priceMaxDisplay').textContent = 'GH₵ ' + maxVal.toLocaleString();\n        }\n\n        function filterProducts() {\n            const activeCategory = document.querySelector('#categoryTags .tag-btn.active')?.dataset.category || 'all';\n            const activeBrand = document.querySelector('#brandTags .tag-btn.active')?.dataset.brand || 'all';\n            const activeSize = document.querySelector('#sizeTags .size-btn.active')?.dataset.size || 'all';\n            const activeColor = document.querySelector('#colorTags .color-btn.active')?.dataset.color || 'all';\n            const searchTerm = document.getElementById('searchInput').value.toLowerCase();\n            const selectedRating = document.querySelector('input[name=\"rating_filter\"]:checked')?.value;\n            const minPrice = parseInt(document.getElementById('minPriceSlider').value);\n            const maxPrice = parseInt(document.getElementById('maxPriceSlider').value);\n            \n            const productCards = document.querySelectorAll('.modern-product-card');\n            let visibleCount = 0;\n            \n            productCards.forEach(card => {\n                const title = card.querySelector('h3').textContent.toLowerCase();\n                const priceText = card.querySelector('[style*=\"font-size: 1.3rem\"]').textContent;\n                const price = parseFloat(priceText.replace('GH₵', '').replace(',', ''));\n                \n                // Check if product matches filters\n                let matchesCategory = activeCategory === 'all' || title.includes(activeCategory.toLowerCase());\n                let matchesBrand = activeBrand === 'all' || title.includes(activeBrand.toLowerCase());\n                let matchesSearch = searchTerm === '' || title.includes(searchTerm);\n                let matchesPrice = price >= minPrice && price <= maxPrice;\n                let matchesSize = activeSize === 'all'; // Size logic can be enhanced based on product data\n                let matchesColor = activeColor === 'all'; // Color logic can be enhanced based on product data\n                let matchesRating = !selectedRating; // Rating logic can be enhanced based on product data\n                \n                if (matchesCategory && matchesBrand && matchesSearch && matchesPrice && matchesSize && matchesColor && matchesRating) {\n                    card.style.display = 'block';\n                    visibleCount++;\n                } else {\n                    card.style.display = 'none';\n                }\n            });\n            \n            // Update count display\n            const countDisplay = document.querySelector('.product-count');\n            if (countDisplay) {\n                countDisplay.innerHTML = `<i class=\"fas fa-mobile-alt\" style=\"margin-right: 8px;\"></i>Showing ${visibleCount} mobile devices`;\n            }\n        }
    </script>
</body>
</html>