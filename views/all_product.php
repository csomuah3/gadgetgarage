<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/product_controller.php');
require_once(__DIR__ . '/../controllers/category_controller.php');
require_once(__DIR__ . '/../controllers/brand_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');
require_once(__DIR__ . '/../helpers/image_helper.php');
require_once(__DIR__ . '/../includes/product_filter_helper.php'); // Centralized filter helper

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

// ✅ CENTRALIZED FILTER SYSTEM - Just call the helper function!
$filtered_products = apply_product_filters($all_products);

// Get paginated products
$pagination = get_paginated_products($filtered_products, 12);
$products_to_display = $pagination['products'];
$total_products = $pagination['total'];
$total_pages = $pagination['pages'];
$current_page = $pagination['current_page'];

// Get filter values for UI (to preserve state in form inputs)
$filter_values = get_filter_values_from_url();
$category_filter = $filter_values['category'];
$brand_filter = $filter_values['brand'];
$search_query = $filter_values['search'];
$min_price = $filter_values['min_price'];
$max_price = $filter_values['max_price'];
$rating_filter = $filter_values['rating'];

// Get recommended products (4 random products)
$all_products_for_recommendations = get_all_products_ctr();
shuffle($all_products_for_recommendations);
$recommended_products = array_slice($all_products_for_recommendations, 0, 4);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Products - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <link href="../css/product-card.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        /* Color Scheme Variables - Bright Professional Theme */
        :root {
            --pale-blue: #E8F0F5;
            /* Very light blue-gray from image */
            --navy-blue: #1E3A5F;
            /* Deep navy blue from image */
            --royal-blue: #2563EB;
            /* Vibrant royal blue from image */
            --pure-white: #FFFFFF;
            /* Pure white from image */
            --text-dark: #1F2937;
            /* Dark gray for headings */
            --text-body: #374151;
            /* Medium gray for body text */
            --text-light: #6B7280;
            /* Light gray for secondary text */
            --border-light: #E5E7EB;
            /* Light gray borders */
            --bg-subtle: #F8FAFC;
            /* Subtle background tint */
            --shadow: rgba(30, 58, 95, 0.08);
            --shadow-hover: rgba(30, 58, 95, 0.12);
            --gradient-primary: linear-gradient(135deg, var(--navy-blue) 0%, var(--royal-blue) 100%);
            --gradient-subtle: linear-gradient(135deg, var(--pale-blue) 0%, var(--pure-white) 100%);
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background-color: var(--pure-white);
            color: var(--text-body);
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
            background: var(--pure-white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px var(--shadow);
            border: 1px solid var(--border-light);
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
            color: var(--text-dark);
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
            color: var(--text-dark);
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
            border-color: var(--royal-blue);
            background: var(--pure-white);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            border-top: 2px solid rgba(37, 99, 235, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .apply-filters-btn {
            background: var(--gradient-primary);
            color: var(--pure-white);
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
            box-shadow: 0 4px 12px var(--shadow);
            position: relative;
            width: 100%;
        }

        .apply-filters-btn:hover {
            background: linear-gradient(135deg, var(--royal-blue) 0%, var(--navy-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--shadow-hover);
        }

        .apply-filters-btn.has-changes {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            animation: subtle-pulse 2s infinite;
        }

        .apply-filters-btn.has-changes::after {
            content: '';
            position: absolute;
            top: -3px;
            right: -3px;
            width: 10px;
            height: 10px;
            background: #ef4444;
            border: 2px solid white;
            border-radius: 50%;
            animation: pulse-dot 1.5s infinite;
        }

        @keyframes subtle-pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.02);
                opacity: 0.95;
            }
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.2);
            }
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
            background: rgba(37, 99, 235, 0.08);
        }

        .rating-option input[type="radio"]:checked+label {
            background: rgba(37, 99, 235, 0.12);
            color: var(--navy-blue);
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
            margin-top: 150px;
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
            background: rgba(37, 99, 235, 0.1);
            border-color: var(--royal-blue);
            color: var(--royal-blue);
        }

        .tag-btn.active {
            background: var(--gradient-primary);
            border-color: var(--navy-blue);
            color: var(--pure-white);
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
            background: rgba(37, 99, 235, 0.1);
            border-color: var(--royal-blue);
            color: var(--royal-blue);
        }

        .size-btn.active {
            background: var(--gradient-primary);
            border-color: var(--navy-blue);
            color: var(--pure-white);
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
            border-color: var(--royal-blue);
            transform: scale(1.1);
        }

        .color-btn.active {
            border-color: var(--royal-blue);
            background: rgba(37, 99, 235, 0.1);
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
            background: var(--pure-white);
            backdrop-filter: blur(20px);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px var(--shadow);
            border: 1px solid var(--border-light);
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
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 50px;
            width: 100%;
        }
        
        @media (max-width: 1200px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .product-grid.list-view {
            grid-template-columns: 1fr;
        }

        .product-card {
            background: var(--pure-white);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 16px var(--shadow);
            transition: all 0.4s ease;
            cursor: pointer;
            border: 1px solid var(--border-light);
            position: relative;
            width: 100%;
            max-width: none;
            min-height: 450px;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 32px var(--shadow-hover);
            border-color: var(--royal-blue);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(232, 240, 245, 0.05));
            opacity: 0;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 16px 48px rgba(37, 99, 235, 0.25);
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
            background: var(--gradient-primary);
            color: var(--pure-white);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            box-shadow: 0 2px 8px var(--shadow);
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
            background: var(--gradient-primary);
            color: var(--pure-white);
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
            box-shadow: 0 4px 12px var(--shadow);
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, var(--royal-blue) 0%, var(--navy-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--shadow-hover);
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
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
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
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
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
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
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
            background: linear-gradient(135deg, rgba(0, 128, 96, 0.1), rgba(232, 240, 245, 0.1));
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
                grid-template-columns: 1fr;
                gap: 15px;
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

    <script>
        // Define viewProductDetails function early to prevent reference errors
        function viewProductDetails(productId) {
            console.log('viewProductDetails called with ID:', productId);

            if (!productId || productId === 0) {
                console.error('Invalid product ID:', productId);
                alert('Invalid product ID');
                return;
            }

            // Navigate to single product page using 'pid' parameter
            window.location.href = 'single_product.php?pid=' + productId;
        }

        // Make it globally accessible
        window.viewProductDetails = viewProductDetails;
        console.log('viewProductDetails function defined in head:', typeof window.viewProductDetails);

        // Define toggleWishlist function early to prevent reference errors
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
        console.log('toggleWishlist function defined in head:', typeof window.toggleWishlist);
    </script>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="container-fluid">
        <div class="text-center py-3">
            <h1 style="color: #1f2937; font-weight: 700; margin: 0;">All Products</h1>
        </div>
    </div>

    <div class="container-fluid mt-4">

        <div class="row">
            <!-- Left Sidebar - Filters -->
            <div class="col-lg-3 col-md-4" id="filterSidebar">
                <div class="filters-sidebar">
                    <div class="filter-header">
                        <h3 class="filter-title">
                            <i class="fas fa-sliders-h"></i>
                            Filter Products
                        </h3>
                        <button class="filter-close d-lg-none" id="closeFilters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="filter-group">
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInput" placeholder="Search products..." autocomplete="off" value="<?php echo htmlspecialchars($search_query); ?>">
                            <i class="fas fa-search search-icon"></i>
                            <div id="searchSuggestions" class="search-suggestions" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Rating</h6>
                        <div class="rating-filter">
                            <div class="rating-option" data-rating="5">
                                <input type="radio" id="rating_5" name="rating_filter" value="5" <?php echo ($rating_filter == 5) ? 'checked' : ''; ?>>
                                <label for="rating_5">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                    <span class="rating-text">5 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="4">
                                <input type="radio" id="rating_4" name="rating_filter" value="4" <?php echo ($rating_filter == 4) ? 'checked' : ''; ?>>
                                <label for="rating_4">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">4 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="3">
                                <input type="radio" id="rating_3" name="rating_filter" value="3" <?php echo ($rating_filter == 3) ? 'checked' : ''; ?>>
                                <label for="rating_3">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">3 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="2">
                                <input type="radio" id="rating_2" name="rating_filter" value="2" <?php echo ($rating_filter == 2) ? 'checked' : ''; ?>>
                                <label for="rating_2">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">2 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="1">
                                <input type="radio" id="rating_1" name="rating_filter" value="1" <?php echo ($rating_filter == 1) ? 'checked' : ''; ?>>
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
                                <input type="range" class="price-slider" id="minPriceSlider" min="0" max="50000" value="<?php echo $min_price; ?>" step="100">
                                <input type="range" class="price-slider" id="maxPriceSlider" min="0" max="50000" value="<?php echo $max_price; ?>" step="100">
                            </div>
                            <div class="price-display">
                                <span class="price-min" id="priceMinDisplay">GH₵ <?php echo number_format($min_price); ?></span>
                                <span class="price-separator">-</span>
                                <span class="price-max" id="priceMaxDisplay">GH₵ <?php echo number_format($max_price); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter by Category -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Category</h6>
                        <div class="tag-filters" id="categoryTags">
                            <button class="tag-btn <?php echo ($category_filter === 'all' || $category_filter === '') ? 'active' : ''; ?>" data-category="" id="category_all_btn">All</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="tag-btn <?php echo ($category_filter == $category['cat_id']) ? 'active' : ''; ?>" data-category="<?php echo $category['cat_id']; ?>" id="category_btn_<?php echo $category['cat_id']; ?>">
                                    <?php echo htmlspecialchars($category['cat_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by Brand -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Brand</h6>
                        <div class="tag-filters" id="brandTags">
                            <button class="tag-btn <?php echo ($brand_filter === 'all' || $brand_filter === '') ? 'active' : ''; ?>" data-brand="" id="brand_all_btn">All</button>
                            <?php foreach ($brands as $brand): ?>
                                <button class="tag-btn <?php echo ($brand_filter == $brand['brand_id']) ? 'active' : ''; ?>" data-brand="<?php echo $brand['brand_id']; ?>" id="brand_btn_<?php echo $brand['brand_id']; ?>">
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
                <div class="stats-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div class="product-count" style="color: #6b7280; font-size: 0.9rem;">
                            <i class="fas fa-box" style="margin-right: 8px;"></i>
                            Showing <?php echo count($products_to_display); ?> of <?php echo $total_products; ?> products
                        </div>
                        <!-- Sort Dropdown -->
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="color: #6b7280; font-size: 0.9rem; font-weight: 500;">Sort by:</span>
                            <select id="sortSelect" onchange="sortProducts()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; font-size: 0.9rem; cursor: pointer;">
                                <option value="alphabetically-az">Alphabetically, A-Z</option>
                                <option value="alphabetically-za">Alphabetically, Z-A</option>
                                <option value="price-low-high">Price, low to high</option>
                                <option value="price-high-low">Price, high to low</option>
                                <option value="rating-high-low">Rating, high to low</option>
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
                            <div class="no-products-icon">📦</div>
                            <h3>No Products Found</h3>
                            <p>There are no products available at the moment.</p>
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
                                    overflow: visible;
                                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                                    cursor: pointer;
                                    position: relative;
                                    transform-origin: center;
                                " onmouseover="this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';"
                                    onmouseout="this.style.boxShadow='0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';">


                                    <!-- Discount Badge -->
                                    <?php if ($discount_percentage > 0): ?>
                                        <div style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; z-index: 10;">
                                            -<?php echo $discount_percentage; ?>%
                                        </div>
                                    <?php endif; ?>

                                    <!-- Wishlist Heart & Compare Button -->
                                    <div style="position: absolute; top: 12px; right: 12px; z-index: 10; display: flex; gap: 8px;">
                                        <!-- Compare Button -->
                                        <button onclick="event.stopPropagation(); addToCompare(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_title']); ?>')"
                                            class="compare-btn"
                                            style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
                                            onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
                                            onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';"
                                            title="Add to Compare">
                                            <i class="fas fa-balance-scale" style="color: #2563eb; font-size: 14px;"></i>
                                        </button>
                                        
                                        <!-- Wishlist Heart -->
                                        <?php
                                        $is_in_wishlist = false;
                                        if ($is_logged_in) {
                                            $is_in_wishlist = check_wishlist_item_ctr($product['product_id'], $customer_id);
                                        }
                                        $heart_class = $is_in_wishlist ? 'fas fa-heart' : 'far fa-heart';
                                        $btn_class = $is_in_wishlist ? 'wishlist-btn active' : 'wishlist-btn';
                                        ?>
                                        <button onclick="event.stopPropagation(); toggleWishlist(<?php echo $product['product_id']; ?>, this)"
                                            class="<?php echo $btn_class; ?>"
                                            style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
                                            onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
                                            onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';">
                                            <i class="<?php echo $heart_class; ?>" style="color: <?php echo $is_in_wishlist ? '#ef4444' : '#6b7280'; ?>; font-size: 16px;"></i>
                                        </button>
                                    </div>

                                    <!-- Product Image -->
                                    <div class="product-image-container" style="padding: 20px; text-align: center; height: 200px; display: flex; align-items: center; justify-content: center; background: #f9fafb; overflow: hidden; position: relative;">
                                        <?php
                                        $image_url = get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? 'Product');
                                        $fallback_url = generate_placeholder_url($product['product_title'] ?? 'Product', '400x300');
                                        ?>
                                        <img src="<?php echo htmlspecialchars($image_url); ?>"
                                            alt="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>"
                                            class="product-image"
                                            style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.3s ease;"
                                            onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($fallback_url); ?>';">

                                        <!-- Customer Activity Popup - Now inside image frame -->
                                        <?php if (rand(1, 3) === 1): // Show on 33% of cards only 
                                        ?>
                                            <div class="customer-activity-popup" style="
                                            position: absolute;
                                            bottom: 12px;
                                            left: 50%;
                                            transform: translateX(-50%);
                                            background: rgba(59, 130, 246, 0.9);
                                            color: white;
                                            padding: 8px 16px;
                                            border-radius: 25px;
                                            font-size: 0.7rem;
                                            font-weight: 600;
                                            z-index: 20;
                                            opacity: 0;
                                            animation: popupFade 6s ease-in-out infinite;
                                            white-space: nowrap;
                                            pointer-events: none;
                                            animation-delay: <?php echo rand(1, 15); ?>s;
                                        ">
                                                <?php
                                                $activities = [
                                                    rand(2, 8) . ' customers viewing this',
                                                    rand(1, 5) . ' customers added to cart',
                                                    rand(3, 12) . ' customers wishlisted this',
                                                    rand(1, 4) . ' customers bought recently',
                                                    rand(5, 15) . ' customers interested',
                                                    rand(2, 6) . ' customers comparing this'
                                                ];
                                                echo $activities[array_rand($activities)];
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Product Content -->
                                    <div style="padding: 28px 30px; min-height: 200px; overflow: visible;">
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

                                        <!-- Stock Status - Only show if out of stock -->
                                        <?php
                                        $stock_quantity = isset($product['stock_quantity']) ? intval($product['stock_quantity']) : 10;
                                        if ($stock_quantity <= 0):
                                        ?>
                                            <div style="margin-bottom: 12px;">
                                                <span style="background: #ef4444; color: white; padding: 6px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                                    <i class="fas fa-times-circle" style="margin-right: 4px;"></i>Out of Stock
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Pricing -->
                                        <div style="margin-bottom: 25px;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <span style="color: #4f46e5; font-size: 1.75rem; font-weight: 900;">
                                                    GH₵<?php echo number_format($product['product_price'], 0); ?>
                                                </span>
                                                <span style="color: #9ca3af; font-size: 1.2rem; text-decoration: line-through; font-weight: 600;">
                                                    GH₵<?php echo number_format($original_price, 0); ?>
                                                </span>
                                            </div>
                                            <div style="color: #6b7280; font-size: 0.85rem; margin-top: 4px; line-height: 1.4; word-wrap: break-word; overflow: visible; min-height: 36px; white-space: normal; display: block; width: 100%;">
                                                Limited time offer - While supplies last
                                            </div>
                                        </div>

                                        <!-- View Details Button -->
                                        <?php if ($stock_quantity > 0): ?>
                                            <button onclick="viewProductDetails(<?php echo isset($product['product_id']) ? $product['product_id'] : 0; ?>)"
                                                data-product-id="<?php echo isset($product['product_id']) ? $product['product_id'] : 0; ?>"
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
                                        <?php else: ?>
                                            <button onclick="showOutOfStockAlert()"
                                                disabled
                                                style="width: 100%; background: #94a3b8; color: white; border: none; padding: 15px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: not-allowed; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; opacity: 0.6;">
                                                <i class="fas fa-times-circle"></i>
                                                Out of Stock
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
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?>" class="page-btn">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>"
                                        class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?>" class="page-btn">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cart.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/chatbot.js"></script>
    <script src="../js/compare.js"></script>
    <script>
        // Define functions first before DOM content loads
        function viewProduct(productId) {
            window.location.href = 'single_product.php?id=' + productId;
        }

        function viewProductDetails(productId) {
            console.log('viewProductDetails called with ID:', productId);

            if (!productId || productId === 0) {
                console.error('Invalid product ID:', productId);
                alert('Invalid product ID');
                return;
            }

            // Navigate to single product page using 'pid' parameter
            window.location.href = 'single_product.php?pid=' + productId;
        }

        // Also assign to window for global access
        window.viewProductDetails = viewProductDetails;
        console.log('viewProductDetails function loaded:', typeof window.viewProductDetails);

        window.showOutOfStockAlert = function() {
            Swal.fire({
                title: 'Out of Stock!',
                text: 'This product is currently out of stock. Please check back later or browse our other available products.',
                icon: 'warning',
                iconColor: '#f59e0b',
                confirmButtonText: 'Browse Other Products',
                confirmButtonColor: '#4f46e5',
                showCancelButton: true,
                cancelButtonText: 'OK',
                cancelButtonColor: '#6b7280',
                background: '#ffffff',
                color: '#1f2937',
                customClass: {
                    popup: 'swal-out-of-stock-popup-card',
                    title: 'swal-out-of-stock-title-card',
                    content: 'swal-out-of-stock-content-card'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Scroll to top of products or redirect to all products
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        };


        // Event delegation removed - using onclick handlers instead

        function selectCondition(element, price, condition) {
            // Remove active class from all condition options in this product
            const productCard = element.closest('.product-card');
            const allConditions = productCard.querySelectorAll('.condition-option');
            allConditions.forEach(opt => {
                opt.style.border = '2px solid transparent';
                opt.style.background = 'rgba(255,255,255,0.1)';
            });

            // Highlight selected condition
            element.style.border = '2px solid #ffd700';
            element.style.background = 'rgba(255,215,0,0.2)';

            // Update price display
            const priceElement = productCard.querySelector('.current-price');
            priceElement.textContent = 'GHS ' + price.toLocaleString();

            // Update add to cart button data
            const cartBtn = productCard.querySelector('.add-to-cart-btn');
            cartBtn.setAttribute('data-condition', condition);
            cartBtn.setAttribute('data-price', price);
        }

        function addToCart(button) {
            const productId = button.getAttribute('data-product-id');
            const condition = button.getAttribute('data-condition');
            const price = button.getAttribute('data-price');

            // Add visual feedback
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;

            // Send AJAX request to add to cart
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('condition', condition);
            formData.append('final_price', price);
            formData.append('quantity', 1);

            fetch('../actions/add_to_cart_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.innerHTML = '<i class="fas fa-check"></i> Added!';
                        button.style.background = '#10b981';

                        // Update cart count if available
                        const cartCounter = document.getElementById('cartBadge');
                        if (cartCounter && data.cart_count) {
                            cartCounter.textContent = data.cart_count;
                            cartCounter.style.display = 'inline';
                        }

                        // Show success notification
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '🛒 Added to Cart',
                                text: data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end',
                                timerProgressBar: true
                            });
                        }

                        // Show cart sidebar after successful addition
                        setTimeout(() => {
                            if (window.showCartSidebar) {
                                window.showCartSidebar();
                            }
                        }, 500);
                    } else {
                        button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        button.style.background = '#ef4444';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '❌ Cart Error',
                                text: data.message,
                                icon: 'error',
                                timer: 3000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end',
                                timerProgressBar: true
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                    button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                    button.style.background = '#ef4444';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '🌐 Connection Error',
                            text: 'Failed to add product to cart. Please try again.',
                            icon: 'error',
                            timer: 4000,
                            showConfirmButton: true,
                            toast: true,
                            position: 'top-end',
                            timerProgressBar: true
                        });
                    }
                })
                .finally(() => {
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.style.background = '#ffd700';
                        button.disabled = false;
                    }, 2000);
                });
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 9999;
                font-weight: 500;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;

            // Add animation keyframes
            if (!document.getElementById('notificationStyles')) {
                const style = document.createElement('style');
                style.id = 'notificationStyles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(notification);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function showCart() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Cart',
                    text: 'Cart functionality will be implemented soon!\nThis will show your cart items.',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    title: 'Cart Feature Coming Soon',
                    text: 'Cart functionality will be implemented soon!',
                    icon: 'info',
                    confirmButtonColor: '#007bff',
                    confirmButtonText: 'OK'
                });
            }
        }

        function updateCartCount() {
            // This would normally get the actual cart count from storage/database
            const cartCountElement = document.getElementById('cartCount');
            let currentCount = parseInt(cartCountElement.textContent);
            cartCountElement.textContent = currentCount + 1;
        }

        function toggleView(viewType) {
            const productGrid = document.getElementById('productGrid');
            const viewBtns = document.querySelectorAll('.view-btn');

            // Update button states
            viewBtns.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');

            // Update grid layout
            if (viewType === 'list') {
                productGrid.classList.add('list-view');
            } else {
                productGrid.classList.remove('list-view');
            }
        }

        // Images now load directly using get_product_image_url() helper function

        function generatePlaceholderUrl(text, size = '320x240') {
            // Use inline SVG to avoid network requests
            const svg = `data:image/svg+xml;base64,${btoa(`
                <svg width="320" height="240" xmlns="http://www.w3.org/2000/svg">
                    <rect width="100%" height="100%" fill="#f8f9fa"/>
                    <rect x="1" y="1" width="318" height="238" fill="none" stroke="#dee2e6" stroke-width="2"/>
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="14" fill="#6c757d" text-anchor="middle" dominant-baseline="middle">No Image</text>
                </svg>
            `)}`;
            return svg;
        }

        // Autocomplete functionality
        let allProducts = <?php echo json_encode($products); ?>;

        function showSearchSuggestions(query) {
            const suggestions = document.getElementById('searchSuggestions');
            const filteredProducts = allProducts.filter(product =>
                product.product_title.toLowerCase().includes(query.toLowerCase()) ||
                (product.product_keywords && product.product_keywords.toLowerCase().includes(query.toLowerCase()))
            ).slice(0, 5); // Show only top 5 suggestions

            if (filteredProducts.length > 0) {
                let suggestionsHTML = '';
                filteredProducts.forEach(product => {
                    suggestionsHTML += `
                        <div class="suggestion-item" onclick="selectSuggestion('${product.product_title}', ${product.product_id})">
                            <i class="fas fa-search suggestion-icon"></i>
                            <span>${highlightMatch(product.product_title, query)}</span>
                        </div>
                    `;
                });

                // Add "Search for..." option
                suggestionsHTML += `
                    <div class="suggestion-item" onclick="performSearchFor('${query}')">
                        <i class="fas fa-arrow-right suggestion-icon"></i>
                        <span>Search for "<strong>${query}</strong>"</span>
                    </div>
                `;

                suggestions.innerHTML = suggestionsHTML;
                suggestions.style.display = 'block';
            } else {
                suggestions.innerHTML = `
                    <div class="suggestion-item" onclick="performSearchFor('${query}')">
                        <i class="fas fa-search suggestion-icon"></i>
                        <span>Search for "<strong>${query}</strong>"</span>
                    </div>
                `;
                suggestions.style.display = 'block';
            }
        }

        function hideSearchSuggestions() {
            document.getElementById('searchSuggestions').style.display = 'none';
        }

        function highlightMatch(text, query) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }

        function selectSuggestion(productTitle, productId) {
            document.getElementById('searchInput').value = productTitle;
            hideSearchSuggestions();
            applyFilters();
        }

        function performSearchFor(query) {
            document.getElementById('searchInput').value = query;
            hideSearchSuggestions();
            applyFilters();
        }

        function performInstantSearch() {
            applyFilters();
        }

        // Alias function for applyFilters to call executeFilters
        function applyFilters() {
            console.log('⚡ applyFilters() alias called, forwarding to executeFilters()');
            executeFilters();
        }
        
        // Test that JavaScript is loading
        console.log('✅✅✅ ALL_PRODUCT.PHP JAVASCRIPT LOADED - VERSION 2.0 ✅✅✅');

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-autocomplete-container')) {
                hideSearchSuggestions();
            }
        });

        // New Filter System with Apply Button
        let filtersChanged = false;
        let initialState = null;

        function initNewFilters() {
            // Store initial state
            captureInitialState();

            // Initialize all filter components
            initPriceSlider();
            initRatingFilter();
            initCategoryFilter();
            initTagFilters();
            initColorFilters();
            initMobileFilters();
        }

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
                applyBtn.classList.add('has-changes');
                applyBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Apply Changes';
            }
        }

        function hideApplyButton() {
            filtersChanged = false;
            const applyBtn = document.getElementById('applyFilters');
            applyBtn.classList.remove('has-changes');
            applyBtn.innerHTML = '<i class="fas fa-filter"></i> Apply Filters';
        }

        function initPriceSlider() {
            const minSlider = document.getElementById('minPriceSlider');
            const maxSlider = document.getElementById('maxPriceSlider');
            const minDisplay = document.getElementById('priceMinDisplay');
            const maxDisplay = document.getElementById('priceMaxDisplay');
            const rangeDisplay = document.getElementById('priceRange');
            
            console.log('🎚️ Price Slider Init:', {
                minSlider: !!minSlider,
                maxSlider: !!maxSlider,
                minDisplay: !!minDisplay,
                maxDisplay: !!maxDisplay,
                rangeDisplay: !!rangeDisplay
            });
            
            if (!minSlider || !maxSlider || !minDisplay || !maxDisplay || !rangeDisplay) {
                console.error('❌ Price slider elements not found!');
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

            // Real-time display updates
            minSlider.addEventListener('input', updatePriceDisplay);
            maxSlider.addEventListener('input', updatePriceDisplay);

            // Check for changes on mouse up or touch end
            minSlider.addEventListener('change', checkForChanges);
            maxSlider.addEventListener('change', checkForChanges);

            // Initialize
            updatePriceDisplay();
        }

        function initRatingFilter() {
            const ratingInputs = document.querySelectorAll('input[name="rating_filter"]');

            ratingInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (initialState && this.value !== initialState.rating) {
                        showApplyButton();
                    }
                });
            });
        }

        function initCategoryFilter() {
            // Category filters using tag buttons (same as brand filter)
            const categoryBtns = document.querySelectorAll('#categoryTags .tag-btn');
            console.log('🏷️ Category buttons found:', categoryBtns.length);
            
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    console.log('Category clicked:', this.getAttribute('data-category'));
                    // Remove active from all category buttons
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedCategory = this.getAttribute('data-category');
                    if (initialState && selectedCategory !== initialState.categories[0]) {
                        showApplyButton();
                    }
                });
            });
        }

        function initTagFilters() {
            // Brand filters
            const brandBtns = document.querySelectorAll('#brandTags .tag-btn');
            console.log('🏢 Brand buttons found:', brandBtns.length);
            
            brandBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    console.log('Brand clicked:', this.getAttribute('data-brand'));
                    // Remove active from all brand buttons
                    brandBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedBrand = this.getAttribute('data-brand');
                    if (initialState && selectedBrand !== initialState.brand) {
                        showApplyButton();
                    }
                });
            });
        }

        function initSizeFilters() {
            const sizeBtns = document.querySelectorAll('.size-btn');
            sizeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active from all size buttons
                    sizeBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedSize = this.getAttribute('data-size');
                    if (initialState && selectedSize !== initialState.size) {
                        showApplyButton();
                    }
                });
            });
        }

        function initColorFilters() {
            const colorBtns = document.querySelectorAll('.color-btn');
            colorBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active from all color buttons
                    colorBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedColor = this.getAttribute('data-color');
                    if (initialState && selectedColor !== initialState.color) {
                        showApplyButton();
                    }
                });
            });
        }

        function initMobileFilters() {
            const mobileToggle = document.getElementById('mobileFilterToggle');
            const closeFilters = document.getElementById('closeFilters');
            const filterSidebar = document.getElementById('filterSidebar');

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    filterSidebar.classList.add('show');
                    // Add overlay
                    const overlay = document.createElement('div');
                    overlay.className = 'filter-overlay show';
                    document.body.appendChild(overlay);

                    overlay.addEventListener('click', function() {
                        filterSidebar.classList.remove('show');
                        overlay.remove();
                    });
                });
            }

            if (closeFilters) {
                closeFilters.addEventListener('click', function() {
                    filterSidebar.classList.remove('show');
                    const overlay = document.querySelector('.filter-overlay');
                    if (overlay) overlay.remove();
                });
            }
        }

        // Updated Filter functionality for new design with multiple categories
        // Apply all filters function (same logic as photography_video.php)
        function applyAllFilters() {
            const categoryBtn = document.querySelector('#categoryTags .tag-btn.active');
            const brandBtn = document.querySelector('#brandTags .tag-btn.active');
            const searchInput = document.getElementById('searchInput');
            const minPrice = document.getElementById('minPriceSlider').value;
            const maxPrice = document.getElementById('maxPriceSlider').value;
            const selectedRating = document.querySelector('input[name="rating_filter"]:checked');

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
            if (selectedRating && selectedRating.value) {
                params.append('rating', selectedRating.value);
            }

            window.location.href = 'all_product.php?' + params.toString();
        }

        // Alias for backward compatibility
        function executeFilters() {
            applyAllFilters();
        }

        function updateInitialState() {
            // Update the initial state to current values to prevent showing apply button again
            const searchInput = document.getElementById('searchInput');
            const selectedRating = document.querySelector('input[name="rating_filter"]:checked');
            const activeCategory = document.querySelector('#categoryTags .tag-btn.active');
            const activeBrand = document.querySelector('#brandTags .tag-btn.active');
            const activeSize = document.querySelector('.size-btn.active');
            const activeColor = document.querySelector('.color-btn.active');

            initialState = {
                search: searchInput.value,
                rating: selectedRating ? selectedRating.value : '',
                minPrice: parseInt(document.getElementById('minPriceSlider').value),
                maxPrice: parseInt(document.getElementById('maxPriceSlider').value),
                categories: [activeCategory ? activeCategory.getAttribute('data-category') : ''],
                brand: activeBrand ? activeBrand.getAttribute('data-brand') : '',
                size: activeSize ? activeSize.getAttribute('data-size') : '',
                color: activeColor ? activeColor.getAttribute('data-color') : ''
            };
        }

        function updateProductGrid(products) {
            const productGrid = document.getElementById('productGrid');
            const productCount = document.querySelector('.product-count');

            // Update product count
            productCount.innerHTML = `<i class="fas fa-box"></i> Showing ${products.length} products`;

            if (products.length === 0) {
                productGrid.innerHTML = `
                    <div class="no-products" style="grid-column: 1 / -1;">
                        <div class="no-products-icon">🔍</div>
                        <h3>No Products Found</h3>
                        <p>Try adjusting your filters or search terms.</p>
                    </div>
                `;
            } else {
                productGrid.innerHTML = products.map(product => {
                    return `
        <div class="product-card" onclick="viewProduct(${product.product_id})">
            <div class="product-image-container">
                <img src="${product.image_url || 'http://169.239.251.102:442/~chelsea.somuah/uploads/' + (product.product_image || '')}"
                     alt="${product.product_title}"
                     class="product-image"
                     data-product-id="${product.product_id}"
                     data-product-image="${product.product_image || ''}"
                     data-product-title="${product.product_title}"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4='; this.onerror=null;">
                <div class="product-badge">New</div>
            </div>
            <div class="product-content">
                <h5 class="product-title">${product.product_title}</h5>
                <div class="product-price">GHS ${parseFloat(product.product_price).toFixed(2)}</div>
                <div class="product-meta">
                    <span class="meta-tag">
                        <i class="fas fa-tag"></i>
                        ${product.cat_name || 'N/A'}
                    </span>
                    <span class="meta-tag">
                        <i class="fas fa-store"></i>
                        ${product.brand_name || 'N/A'}
                    </span>
                </div>
                <button class="add-to-cart-btn" onclick="event.stopPropagation(); showAddToCartModal(${product.product_id}, '${product.product_title.replace(/'/g, "\\'")}', ${product.product_price}, '${product.image_url || ('http://169.239.251.102:442/~chelsea.somuah/uploads/' + (product.product_image || ''))}')">
                    <i class="fas fa-shopping-cart"></i>
                    Add to Cart
                </button>
            </div>
        </div>
    `;
                }).join('');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🌟🌟🌟 FILTER SYSTEM LOADING - VERSION 2.0 🌟🌟🌟');
            
            const searchInput = document.getElementById('searchInput');
            const clearFilters = document.getElementById('clearFilters');
            const applyFiltersBtn = document.getElementById('applyFilters');
            
            console.log('🚀 Filter System Init:', {
                searchInput: !!searchInput,
                clearFilters: !!clearFilters,
                applyFiltersBtn: !!applyFiltersBtn,
                applyFiltersBtn_element: applyFiltersBtn
            });

            if (!applyFiltersBtn) {
                console.error('❌ Apply filters button not found!');
                console.error('Available buttons:', document.querySelectorAll('button'));
                return;
            }

            // Add a visible indicator that the script loaded
            applyFiltersBtn.style.border = '3px solid #10b981';
            console.log('✅ Apply button found and marked with green border');

            // Initialize new filter system
            setTimeout(() => {
                console.log('🔧 Initializing filters...');
                try {
                    initNewFilters();
                    console.log('✅ Filters initialized successfully');
                } catch (error) {
                    console.error('❌ Filter initialization error:', error);
                }
            }, 100);

            // Images loaded via PHP helper function

            // Apply Filters button click handler (same as photography_video.php)
            applyFiltersBtn.addEventListener('click', function() {
                applyAllFilters();
            });

            // Search input change detection
            searchInput.addEventListener('input', function() {
                if (initialState && this.value !== initialState.search) {
                    showApplyButton();
                }
            });

            // Search input - show suggestions but don't auto-apply (user clicks Apply button)
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length >= 2) {
                    // Show autocomplete suggestions
                    showSearchSuggestions(query);
                } else {
                    hideSearchSuggestions();
                }
                // Show apply button when search changes
                if (initialState && this.value !== initialState.search) {
                    showApplyButton();
                }
            });


            clearFilters.addEventListener('click', function() {
                // Simple redirect to clear all filters (same as photography_video.php)
                window.location.href = 'all_product.php';
            });
        });

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
                // Add user message
                const userMessage = document.createElement('div');
                userMessage.className = 'chat-message user';
                userMessage.innerHTML = `<p style="background: #000000; color: white; padding: 12px 16px; border-radius: 18px; margin: 0; font-size: 0.9rem; text-align: right;">${message}</p>`;
                chatBody.appendChild(userMessage);

                // Clear input
                chatInput.value = '';

                // Simulate bot response
                setTimeout(() => {
                    const botMessage = document.createElement('div');
                    botMessage.className = 'chat-message bot';
                    botMessage.innerHTML = `<p>Thank you for your message! Our team will get back to you shortly.</p>`;
                    chatBody.appendChild(botMessage);
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 1000);

                // Scroll to bottom
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        }

        // Add chat event listeners
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

        // Product Modal Functions
        function showConditionModal(productId) {
            // Find the product data
            const products = <?php echo json_encode($all_products); ?>;
            const product = products.find(p => p.id == productId);

            if (!product) return;

            // Populate product info
            document.getElementById('modalProductInfo').innerHTML = `
                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
                    <img src="${product.image_url || ('http://169.239.251.102:442/~chelsea.somuah/uploads/' + (product.image || ''))}" alt="${product.name}" style="width: 80px; height: 80px; object-fit: contain; border-radius: 8px; border: 1px solid #e5e7eb;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4='; this.onerror=null;">
                    <div>
                        <h6 style="color: #6b7280; font-size: 0.9rem; margin: 0 0 5px 0;">${product.brand.charAt(0).toUpperCase() + product.brand.slice(1)}</h6>
                        <h5 style="color: #1f2937; margin: 0; font-size: 1.1rem;">${product.name}</h5>
                    </div>
                </div>
            `;

            // Populate condition options
            let conditionsHtml = '<div style="display: flex; flex-direction: column; gap: 12px;">';

            Object.entries(product.conditions).forEach(([condition, details]) => {
                const original_price = details.price + 800;
                conditionsHtml += `
                    <div onclick="selectModalCondition(this, ${product.id}, '${condition}', ${details.price})"
                         style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.2s; background: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">${condition.charAt(0).toUpperCase() + condition.slice(1)} Condition</div>
                                <div style="color: #6b7280; font-size: 0.9rem;">${details.description}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="text-decoration: line-through; color: #9ca3af; font-size: 0.9rem;">GHS ${original_price.toLocaleString()}</div>
                                <div style="color: #2563eb; font-weight: 600; font-size: 1.1rem;">GHS ${details.price.toLocaleString()}</div>
                            </div>
                        </div>
                    </div>
                `;
            });

            conditionsHtml += '</div>';
            document.getElementById('modalConditions').innerHTML = conditionsHtml;

            // Show modal
            document.getElementById('conditionModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function selectModalCondition(element, productId, condition, price) {
            // Remove active styling from all options
            document.querySelectorAll('#modalConditions > div > div').forEach(opt => {
                opt.style.border = '2px solid #e5e7eb';
                opt.style.background = 'white';
            });

            // Apply active styling to selected option
            element.style.border = '2px solid #2563eb';
            element.style.background = '#eff6ff';

            // Add "Add to Cart" button if not already present
            let addToCartBtn = document.getElementById('modalAddToCartBtn');
            if (!addToCartBtn) {
                addToCartBtn = document.createElement('button');
                addToCartBtn.id = 'modalAddToCartBtn';
                addToCartBtn.style.cssText = 'width: 100%; background: #2563eb; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; margin-top: 20px; cursor: pointer; transition: background-color 0.2s;';
                addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart" style="margin-right: 8px;"></i>Add to Cart';
                document.getElementById('modalConditions').appendChild(addToCartBtn);
            }

            // Update button data and click handler
            addToCartBtn.setAttribute('data-product-id', productId);
            addToCartBtn.setAttribute('data-condition', condition);
            addToCartBtn.setAttribute('data-price', price);
            addToCartBtn.onclick = function() {
                addToCart(this);
                closeConditionModal();
            };
        }

        function closeConditionModal() {
            document.getElementById('conditionModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Product Sorting Functions
        function sortProducts() {
            const sortValue = document.getElementById('sortSelect').value;
            const productGrid = document.getElementById('productGrid');
            const products = Array.from(productGrid.children);

            products.sort((a, b) => {
                switch (sortValue) {
                    case 'alphabetically-az':
                        return a.querySelector('h5').textContent.localeCompare(b.querySelector('h5').textContent);
                    case 'alphabetically-za':
                        return b.querySelector('h5').textContent.localeCompare(a.querySelector('h5').textContent);
                    case 'price-low-high':
                        const priceA = parseInt(a.querySelector('[style*="color: #2563eb"]').textContent.replace(/[^0-9]/g, ''));
                        const priceB = parseInt(b.querySelector('[style*="color: #2563eb"]').textContent.replace(/[^0-9]/g, ''));
                        return priceA - priceB;
                    case 'price-high-low':
                        const priceA2 = parseInt(a.querySelector('[style*="color: #2563eb"]').textContent.replace(/[^0-9]/g, ''));
                        const priceB2 = parseInt(b.querySelector('[style*="color: #2563eb"]').textContent.replace(/[^0-9]/g, ''));
                        return priceB2 - priceA2;
                    case 'rating-high-low':
                        const ratingA = a.querySelectorAll('.fas.fa-star').length;
                        const ratingB = b.querySelectorAll('.fas.fa-star').length;
                        return ratingB - ratingA;
                    default:
                        return 0;
                }
            });

            // Clear and re-append sorted products
            productGrid.innerHTML = '';
            products.forEach(product => productGrid.appendChild(product));
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('conditionModal');
            if (event.target === modal) {
                closeConditionModal();
            }
        });

        // Dropdown Functions
        function toggleMegaDropdown() {
            const dropdown = document.getElementById('megaDropdown');
            dropdown.classList.toggle('show');
            closeAllDropdowns(['megaDropdown']);
        }

        function toggleBrandsDropdown() {
            const dropdown = document.getElementById('brandsDropdown');
            dropdown.classList.toggle('show');
            closeAllDropdowns(['brandsDropdown']);
        }

        function toggleHelpDropdown() {
            const dropdown = document.getElementById('helpDropdown');
            dropdown.classList.toggle('show');
            closeAllDropdowns(['helpDropdown']);
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            dropdown.classList.toggle('show');
            closeAllDropdowns(['userDropdownMenu']);
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

        function closeAllDropdowns(except = []) {
            const dropdowns = ['megaDropdown', 'brandsDropdown', 'helpDropdown', 'userDropdownMenu', 'shopDropdown', 'shopCategoryDropdown', 'moreDropdown'];
            dropdowns.forEach(id => {
                if (!except.includes(id)) {
                    const dropdown = document.getElementById(id);
                    if (dropdown) {
                        dropdown.classList.remove('show');
                    }
                }
            });
        }

        function confirmLogout() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Logout',
                    text: 'Are you sure you want to logout?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#D19C97',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Logout',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../login/logout.php';
                    }
                });
            }
        }
        }

        document.addEventListener('DOMContentLoaded', function() {
        });

        // Dropdown functions are now in header.php - removed duplicate code

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
            window.location.href = 'my_orders.php';
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

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const target = event.target;
            const isDropdownButton = target.closest('.categories-button, .nav-item, .user-avatar');
            const isDropdownContent = target.closest('.mega-dropdown, .brands-dropdown, .simple-dropdown, .dropdown-menu-custom');

            if (!isDropdownButton && !isDropdownContent) {
                closeAllDropdowns();
            }
        });


        // Initialize wishlist status
        document.addEventListener('DOMContentLoaded', function() {
            // Popup animations now handled by PHP random delays
        });

        // Load wishlist status
        loadWishlistStatus();
        });

        function loadWishlistStatus() {
            fetch('../actions/get_wishlist_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.is_logged_in) {
                        // Update wishlist badge
                        const wishlistBadge = document.getElementById('wishlistBadge');
                        if (wishlistBadge) {
                            wishlistBadge.textContent = data.count;
                            wishlistBadge.style.display = data.count > 0 ? 'flex' : 'none';
                        }

                        // Update wishlist heart buttons
                        const wishlistButtons = document.querySelectorAll('.wishlist-btn');
                        wishlistButtons.forEach(button => {
                            const productId = parseInt(button.getAttribute('onclick').match(/\d+/)[0]);
                            if (data.wishlist_items.includes(productId)) {
                                button.classList.add('active');
                                const icon = button.querySelector('i');
                                icon.className = 'fas fa-heart';
                            }
                        });
                    }
                })
                .catch(error => console.error('Error loading wishlist status:', error));
        }

        // toggleWishlist function is now defined in the head section to prevent reference errors

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
                    <p>Hello! How can we help you find the perfect tech device today?</p>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" class="chat-input" placeholder="Type your message...">
                <button class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Condition Selection Modal -->
    <div id="conditionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb;">
                <h4 style="color: #1f2937; margin: 0; font-size: 1.3rem; font-weight: 600;">Select Condition</h4>
                <button onclick="closeConditionModal()" style="background: none; border: none; font-size: 1.5rem; color: #6b7280; cursor: pointer; padding: 5px; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>

            <div id="modalProductInfo" style="margin-bottom: 25px;">
                <!-- Product info will be populated here -->
            </div>

            <div id="modalConditions">
                <!-- Condition options will be populated here -->
            </div>
        </div>
    </div>

    <?php include '../includes/cart_sidebar.php'; ?>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- ✅ CENTRALIZED FILTER SYSTEM - Just include this! -->
    <script src="../js/product-filters-redirect.js"></script>
    <script>
        // Initialize with page name
        productFiltersRedirect.init('all_product.php');
    </script>

</body>

</html>