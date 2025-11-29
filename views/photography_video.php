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

// Get all products and filter for photography & video devices ONLY
$all_products = get_all_products_ctr();

// Simple photo/video filtering - ONLY show photography/video-related products
$photo_video_products = array_filter($all_products, function ($product) {
    $cat_name = isset($product['cat_name']) ? strtolower($product['cat_name']) : '';
    $title = isset($product['product_title']) ? strtolower($product['product_title']) : '';

    // Photo/Video categories and keywords
    return (strpos($cat_name, 'camera') !== false ||
            strpos($cat_name, 'video') !== false ||
            strpos($cat_name, 'photo') !== false ||
            strpos($cat_name, 'lens') !== false ||
            strpos($title, 'camera') !== false ||
            strpos($title, 'video') !== false ||
            strpos($title, 'photo') !== false ||
            strpos($title, 'lens') !== false ||
            strpos($title, 'canon') !== false ||
            strpos($title, 'nikon') !== false ||
            strpos($title, 'sony') !== false ||
            strpos($title, 'camcorder') !== false);
});

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

// Apply additional filters based on URL parameters
$category_filter = $_GET['category'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$condition_filter = $_GET['condition'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$filtered_products = $photo_video_products;

if ($category_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function ($product) use ($category_filter) {
        return strcasecmp($product['cat_name'], $category_filter) === 0;
    });
}

if ($brand_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function ($product) use ($brand_filter) {
        return $product['brand_id'] == $brand_filter;
    });
}

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function ($product) use ($search_query) {
        return stripos($product['product_title'], $search_query) !== false ||
            stripos($product['product_desc'], $search_query) !== false;
    });
}

// Get unique categories and brands from photo/video products
$photo_video_cats = array_unique(array_column($photo_video_products, 'cat_name'));
$photo_video_brand_ids = array_unique(array_column($photo_video_products, 'brand_id'));
$photo_video_brands = array_filter($brands, function ($brand) use ($photo_video_brand_ids) {
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
    <link href="../includes/header.css" rel="stylesheet">
    <link href="includes/chatbot-styles.css" rel="stylesheet">
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


        /* Header styles are now in header.css */

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

        .search-clear-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .search-clear-btn:hover {
            background: #dc2626;
            transform: translateY(-50%) scale(1.1);
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
            padding: 15px 0;
            position: relative;
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

        .page-btn:hover,
        .page-btn.active {
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

        .apply-filters-btn:hover {
            background: linear-gradient(135deg, #16a34a, #22c55e) !important;
            transform: scale(1.03) !important;
        }

        .apply-filters-btn.has-changes {
            background: linear-gradient(135deg, #22c55e, #16a34a) !important;
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .results-info {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
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
    <?php include '../includes/header.php'; ?>

    <!-- Floating Bubbles Background -->
    <div class="floating-bubbles" id="floatingBubbles"></div>

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

                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Price Range</h6>
                        <div class="price-range-container">
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
                    <div class="filter-actions" style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
                        <button class="apply-filters-btn" id="applyFilters">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>
                        <button class="clear-filters-btn" id="clearFilters" onclick="clearAllFilters()" style="
                            background: #ef4444;
                            color: white;
                            border: none;
                            border-radius: 8px;
                            padding: 12px 20px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 8px;
                        ">
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
                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                            $full_stars++;
                                        }
                                        for ($i = $full_stars; $i < 5; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <span style="color: #6b7280; font-size: 0.9rem; font-weight: 600;">(<?php echo $rating; ?>)</span>
                                </div>
                                <!-- Optional Status Text -->
                                <?php if (rand(1, 3) === 1): // Only show for some products 
                                ?>
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
        let initialState = null;

        function captureInitialState() {
            initialState = {
                search: '',
                rating: '',
                minPrice: 0,
                maxPrice: 50000,
                categories: [''],
                brand: '',
                size: '',
                color: ''
            };
        }

        function showApplyButton() {
            if (!filtersChanged) {
                filtersChanged = true;
                const applyBtn = document.getElementById('applyFilters');
                if (applyBtn) {
                    applyBtn.classList.add('has-changes');
                    applyBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Apply Changes';
                }
            }
        }

        function hideApplyButton() {
            filtersChanged = false;
            const applyBtn = document.getElementById('applyFilters');
            if (applyBtn) {
                applyBtn.classList.remove('has-changes');
                applyBtn.innerHTML = '<i class="fas fa-filter"></i> Apply Filters';
            }
        }

        function initPhotoFilters() {
            captureInitialState();
            initBrandFilter();
            initMobileFilterToggle();
            initPriceSlider();
            initSearchFeatures();
        }

        // Initialize price slider with real-time updates
        function initPriceSlider() {
            const minSlider = document.getElementById('minPriceSlider');
            const maxSlider = document.getElementById('maxPriceSlider');
            const minDisplay = document.getElementById('priceMinDisplay');
            const maxDisplay = document.getElementById('priceMaxDisplay');
            const rangeDisplay = document.getElementById('priceRange');

            if (!minSlider || !maxSlider || !minDisplay || !maxDisplay || !rangeDisplay) {
                console.warn('Price slider elements not found');
                return;
            }

            function updatePriceDisplay() {
                const minVal = parseInt(minSlider.value);
                const maxVal = parseInt(maxSlider.value);

                // Ensure min is not greater than max
                if (minVal > maxVal - 100) {
                    minSlider.value = maxVal - 100;
                }

                if (maxVal < minVal + 100) {
                    maxSlider.value = minVal + 100;
                }

                const finalMin = parseInt(minSlider.value);
                const finalMax = parseInt(maxSlider.value);

                // Always update the display in real-time
                minDisplay.textContent = `GH₵ ${finalMin.toLocaleString()}`;
                maxDisplay.textContent = `GH₵ ${finalMax.toLocaleString()}`;

                // Update range display
                const minPercent = (finalMin / parseInt(minSlider.max)) * 100;
                const maxPercent = (finalMax / parseInt(maxSlider.max)) * 100;

                rangeDisplay.style.left = `${minPercent}%`;
                rangeDisplay.style.right = `${100 - maxPercent}%`;
            }

            function checkForChanges() {
                const finalMin = parseInt(minSlider.value);
                const finalMax = parseInt(maxSlider.value);

                // Show apply button if values changed from initial (only if initialState exists)
                if (initialState && (finalMin !== initialState.minPrice || finalMax !== initialState.maxPrice)) {
                    showApplyButton();
                }
            }

            // Real-time display updates on input (as user drags)
            minSlider.addEventListener('input', updatePriceDisplay);
            maxSlider.addEventListener('input', updatePriceDisplay);

            // Also update on mousemove for smoother real-time updates
            minSlider.addEventListener('mousemove', updatePriceDisplay);
            maxSlider.addEventListener('mousemove', updatePriceDisplay);

            // Check for changes on mouse up or touch end
            minSlider.addEventListener('change', checkForChanges);
            maxSlider.addEventListener('change', checkForChanges);

            // Initialize
            updatePriceDisplay();
        }

        // Global function for HTML oninput calls
        function updatePriceDisplay() {
            const minSlider = document.getElementById('minPriceSlider');
            const maxSlider = document.getElementById('maxPriceSlider');
            const minDisplay = document.getElementById('priceMinDisplay');
            const maxDisplay = document.getElementById('priceMaxDisplay');
            const priceRange = document.getElementById('priceRange');

            if (minSlider && maxSlider && minDisplay && maxDisplay) {
                let minVal = parseInt(minSlider.value);
                let maxVal = parseInt(maxSlider.value);

                // Ensure min doesn't exceed max
                if (minVal >= maxVal) {
                    minVal = maxVal - 100;
                    minSlider.value = minVal;
                }

                // Ensure max doesn't go below min
                if (maxVal <= minVal) {
                    maxVal = minVal + 100;
                    maxSlider.value = maxVal;
                }

                // Update display text with Ghana Cedis
                minDisplay.textContent = 'GH₵ ' + minVal.toLocaleString();
                maxDisplay.textContent = 'GH₵ ' + maxVal.toLocaleString();

                // Update slider range visualization
                if (priceRange) {
                    const minPercent = (minVal / 50000) * 100;
                    const maxPercent = (maxVal / 50000) * 100;
                    priceRange.style.left = minPercent + '%';
                    priceRange.style.width = (maxPercent - minPercent) + '%';
                }

                showApplyButtonWithChanges();
            }
        }

        function initSearchFeatures() {
            const searchInput = document.getElementById('searchInput');
            const searchClearBtn = document.getElementById('searchClearBtn');

            if (searchInput && searchClearBtn) {
                searchInput.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        searchClearBtn.style.display = 'flex';
                    } else {
                        searchClearBtn.style.display = 'none';
                    }
                    showApplyButtonWithChanges();
                });

                searchClearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    this.style.display = 'none';
                    searchInput.focus();
                    showApplyButtonWithChanges();
                });
            }
        }

        function clearSearch() {
            const searchInput = document.getElementById('searchInput');
            const searchClearBtn = document.getElementById('searchClearBtn');

            if (searchInput) searchInput.value = '';
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            showApplyButtonWithChanges();
        }

        function initBrandFilter() {
            const brandBtns = document.querySelectorAll('#brandTags .tag-btn');
            brandBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    brandBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    showApplyButtonWithChanges();
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

        function showApplyButtonWithChanges() {
            const applyBtn = document.getElementById('applyFilters');
            if (applyBtn) {
                applyBtn.classList.add('has-changes');
                applyBtn.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
                applyBtn.style.transform = 'scale(1.02)';
                applyBtn.style.boxShadow = '0 4px 12px rgba(34, 197, 94, 0.3)';
            }
        }

        function showApplyButton() {
            showApplyButtonWithChanges();
        }

        // Apply all filters function
        function applyAllFilters() {
            const categoryBtn = document.querySelector('#categoryTags .tag-btn.active');
            const brandBtn = document.querySelector('#brandTags .tag-btn.active');
            const searchInput = document.getElementById('searchInput');
            const minPrice = document.getElementById('minPriceSlider').value;
            const maxPrice = document.getElementById('maxPriceSlider').value;

            const params = new URLSearchParams();

            if (categoryBtn && categoryBtn.dataset.category) {
                params.append('category', categoryBtn.dataset.category);
            }
            if (brandBtn && brandBtn.dataset.brand) {
                params.append('brand', brandBtn.dataset.brand);
            }
            if (searchInput && searchInput.value) {
                params.append('search', searchInput.value);
            }
            if (minPrice !== '0') {
                params.append('min_price', minPrice);
            }
            if (maxPrice !== '50000') {
                params.append('max_price', maxPrice);
            }

            window.location.href = 'photography_video.php?' + params.toString();
        }

        // Clear all filters function
        function clearAllFilters() {
            window.location.href = 'photography_video.php';
        }

        // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            initPhotoFilters();

            // Apply Filters button click handler
            const applyFiltersBtn = document.getElementById('applyFilters');
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', function() {
                    if (typeof executeFilters === 'function') {
                        executeFilters();
                    } else if (typeof applyAllFilters === 'function') {
                        applyAllFilters();
                    }
                });
            }
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

            fetch('../actions/add_to_cart_action.php', {
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
                Swal.fire({
                    title: 'Feature Coming Soon',
                    text: 'Profile picture modal not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#007bff',
                    confirmButtonText: 'OK'
                });
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
                Swal.fire({
                    title: 'Feature Coming Soon',
                    text: 'Language change to ' + lang + ' not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#007bff',
                    confirmButtonText: 'OK'
                });
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
                Swal.fire({
                    title: 'Feature Coming Soon',
                    text: 'Theme toggle not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#007bff',
                    confirmButtonText: 'OK'
                });
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