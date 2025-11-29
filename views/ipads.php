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

// Get all products and filter for IPADS ONLY
$all_products = get_all_products_ctr();

// Filter for iPads specifically
$ipad_products = array_filter($all_products, function ($product) {
    $cat_name = isset($product['cat_name']) ? strtolower($product['cat_name']) : '';
    $title = isset($product['product_title']) ? strtolower($product['product_title']) : '';

    // iPad-specific filtering
    return (strpos($cat_name, 'ipad') !== false ||
            strpos($cat_name, 'tablet') !== false ||
            strpos($title, 'ipad') !== false ||
            strpos($title, 'tablet') !== false ||
            (strpos($title, 'apple') !== false && strpos($title, 'tab') !== false));
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

$filtered_products = $ipad_products;

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

// Configure filters for iPads/Tablets (individual category page)
$filter_config = [
    'show_category_filter' => false,  // Hide category filter (already on iPads page)
    'show_brand_filter' => true,      // Show brand filter only
    'show_price_filter' => true,      // Show price slider
    'show_rating_filter' => true,     // Show ratings
    'fixed_category_id' => 2,         // Fixed to tablets/iPads category
    'allowed_categories' => []
];

// Get recommended products (3 random products)
$all_products_for_recommendations = get_all_products_ctr();
shuffle($all_products_for_recommendations);
$recommended_products = array_slice($all_products_for_recommendations, 0, 3);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>iPads and Tablets - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <link href="../css/product-card.css" rel="stylesheet">
    
    <!-- NEW: Product Filters CSS -->
    <link href="../css/product-filters.css" rel="stylesheet">
    
    <style>
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

        .page-title {
            color: #1f2937;
            background-clip: text;
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: #000000;
            border-radius: 2px;
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

<body>
    <?php include '../includes/header.php'; ?>

    <!-- Main Content with Filters -->
    <div class="container mt-4">
        <div class="row">
            <!-- Left: Filters Sidebar (3 columns) -->
            <div class="col-lg-3">
                <?php include '../includes/product-filters.php'; ?>
            </div>

            <!-- Right: Products Grid (9 columns) -->
            <div class="col-lg-9 product-grid-container">
                <h2 style="margin-bottom: 20px; color: #1f2937;">iPads & Tablets</h2>
                
                <!-- Product Count -->
                <div class="product-count" style="padding: 15px 20px; background: white; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                    <i class="fas fa-box" style="margin-right: 8px; color: #2563eb;"></i>
                    Showing <strong><?php echo count($products_to_display); ?></strong> of <strong><?php echo $total_products; ?></strong> products
                </div>

                <!-- Products Grid -->
                <div id="productGrid" class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
                    <?php foreach ($products_to_display as $product): ?>
                        <div class="product-card" onclick="viewProduct(<?php echo $product['product_id']; ?>)" style="cursor: pointer; background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; transition: transform 0.2s;">
                            <div class="product-image-container" style="position: relative; overflow: hidden; background: #f9fafb;">
                                <img src="<?php echo get_product_image_url($product['product_image'], $product['product_title']); ?>"
                                     alt="<?php echo htmlspecialchars($product['product_title']); ?>"
                                     class="product-image"
                                     style="width: 100%; height: 280px; object-fit: cover;"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4=';">
                            </div>
                            <div class="product-content" style="padding: 15px;">
                                <h5 class="product-title" style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($product['product_title']); ?>
                                </h5>
                                <div class="product-price" style="font-size: 1.3rem; font-weight: 700; color: #2563eb; margin-bottom: 10px;">
                                    GH₵ <?php echo number_format($product['product_price'], 2); ?>
                                </div>
                                <div class="product-meta" style="display: flex; gap: 10px; font-size: 0.85rem; color: #6b7280;">
                                    <span><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination (if needed) -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 30px;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>"
                           style="padding: 10px 15px; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: <?php echo $i == $current_page ? 'white' : '#1f2937'; ?>; background: <?php echo $i == $current_page ? '#2563eb' : 'white'; ?>;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Product Filters JavaScript -->
    <script src="../js/product-filters.js"></script>
    <script>
        function viewProduct(productId) {
            window.location.href = 'single_product.php?pid=' + productId;
        }

        document.addEventListener('DOMContentLoaded', function() {
            
            // Scroll to top button functionality
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

    <!-- Recommended for You Section -->
    <div class="container mt-5 mb-5">
        <h2 style="text-align: center; margin-bottom: 30px; color: #1f2937; font-weight: 700;">RECOMMENDED FOR YOU</h2>
        <div class="row">
            <?php foreach ($recommended_products as $product): 
                $product_image_url = get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? '');
            ?>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="product-card" onclick="window.location.href='single_product.php?pid=<?php echo $product['product_id']; ?>'" style="cursor: pointer; background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; transition: transform 0.2s;">
                        <div style="overflow: hidden; background: #f9fafb;">
                            <img src="<?php echo htmlspecialchars($product_image_url); ?>" alt="<?php echo htmlspecialchars($product['product_title']); ?>" style="width: 100%; height: 250px; object-fit: cover;">
                        </div>
                        <div style="padding: 15px;">
                            <h5 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 8px; min-height: 40px;"><?php echo htmlspecialchars($product['product_title']); ?></h5>
                            <div style="font-size: 1.2rem; font-weight: 700; color: #2563eb;">GH₵ <?php echo number_format($product['product_price'], 2); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>
</html>
