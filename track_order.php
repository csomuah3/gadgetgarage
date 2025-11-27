<?php
session_start();
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/controllers/order_controller.php';

$tracking_result = null;
$error_message = '';

// Check if tracking request
if ($_GET['order'] ?? '') {
    $search_value = trim($_GET['order']);

    try {
        // Try to get order by order ID or tracking number
        $tracking_result = get_order_tracking_details($search_value);

        if (!$tracking_result) {
            $error_message = 'Order not found. Please check your order number or tracking number.';
        }
    } catch (Exception $e) {
        $error_message = 'Error retrieving order details. Please try again later.';
        error_log('Order tracking error: ' . $e->getMessage());
    }
}

// Get cart count for logged in users
$is_logged_in = check_login();
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = 0;

try {
    require_once __DIR__ . '/controllers/cart_controller.php';
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);
} catch (Exception $e) {
    // Silent fail for cart count
}

// Initialize arrays for navigation
$categories = [];
$brands = [];

// Try to load categories and brands safely
try {
    require_once(__DIR__ . '/controllers/category_controller.php');
    $categories = get_all_categories_ctr();
} catch (Exception $e) {
    // If categories fail to load, continue with empty array
    error_log("Failed to load categories: " . $e->getMessage());
}

try {
    require_once(__DIR__ . '/controllers/brand_controller.php');
    $brands = get_all_brands_ctr();
} catch (Exception $e) {
    // If brands fail to load, continue with empty array
    error_log("Failed to load brands: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a1a1a;
        }

        /* Promotional Banner Styles - First Banner (promo-banner2) matches index page */
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

        .promo-banner2 .promo-banner-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 0 0 auto;
        }

        .promo-banner2 .promo-banner-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex: 1;
        }

        .promo-banner2 i {
            font-size: 1rem;
        }

        .promo-banner2 .promo-text {
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .promo-banner2 .promo-timer {
            background: transparent;
            padding: 0;
            border-radius: 0;
            font-size: 1.3rem;
            font-weight: 500;
            margin: 0;
            border: none;
        }

        .promo-banner2 .promo-shop-link {
            color: white;
            text-decoration: underline;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.3s ease;
            font-size: 1.2rem;
            flex: 0 0 auto;
        }

        .promo-banner2 .promo-shop-link:hover {
            opacity: 0.8;
        }

        /* Header Styles */
        .main-header {
            background: #FFFFFF;
            box-shadow: 0 2px 8px rgba(30, 58, 95, 0.08);
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
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
            border: 2px solid #E5E7EB;
            border-radius: 25px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            background: #F8FAFC;
        }

        .search-input:focus {
            outline: none;
            border-color: #2563EB;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #2563EB;
            font-size: 1.1rem;
        }

        .search-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 200;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #2563EB, #1E3A5F);
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
            color: #2563EB;
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
            color: #2563EB;
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
            background: rgba(37, 99, 235, 0.1);
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
            border: 2px solid white;
        }

        .user-dropdown {
            position: relative;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.5);
        }

        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(37, 99, 235, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.15);
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
            background: rgba(37, 99, 235, 0.1);
            color: #2563EB;
            transform: translateX(3px);
        }

        .dropdown-item-custom i {
            font-size: 1rem;
            width: 18px;
            text-align: center;
        }

        .dropdown-divider-custom {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.2), transparent);
            margin: 8px 0;
        }

        .login-btn {
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
            background: linear-gradient(135deg, #2563EB, #1E3A5F);
            transform: translateY(-1px);
            color: white;
        }

        /* Navigation Separator Styles */
        .nav-separator {
            color: #e5e7eb;
            font-weight: 300;
            margin: 0 8px;
            font-size: 1.2rem;
        }

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
            color: #2563EB;
        }

        .flash-deal-spacer {
            flex: 1;
            min-width: 100px;
        }

        /* Brands Dropdown Styles */
        .brands-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
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
        }

        .brands-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .brands-dropdown li {
            margin-bottom: 4px;
        }

        .brands-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            color: #4b5563;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .brands-dropdown a:hover {
            background: #f3f4f6;
            color: #2563EB;
        }

        .brands-dropdown a i {
            color: #9ca3af;
            width: 16px;
        }

        /* Main Navigation */
        .main-nav {
            background: #FFFFFF;
            border-bottom: 1px solid #E5E7EB;
            padding: 12px 0;
            position: sticky;
            top: 85px;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(30, 58, 95, 0.08);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            width: 100%;
            padding-left: 280px;
        }

        .nav-item {
            color: #1F2937;
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
            background: rgba(37, 99, 235, 0.08);
            color: #2563EB;
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
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
            color: #2563EB;
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
            color: #2563EB;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .featured-item img {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }

        .featured-text strong {
            color: #1f2937;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .featured-text p {
            color: #6b7280;
            font-size: 0.8rem;
            margin: 4px 0;
        }

        .shop-now-btn {
            background: #2563EB;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.6rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .shop-now-btn:hover {
            background: #374151;
            color: white;
        }

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
            background: #2563EB;
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

        .tracking-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .tracking-search {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 30px;
            text-align: center;
        }

        .tracking-search h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .tracking-search p {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
            gap: 15px;
        }

        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .tracking-result {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .order-info h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .tracking-number {
            font-size: 1rem;
            color: #6b7280;
            font-family: 'Courier New', monospace;
        }

        .current-status {
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #e0e7ff;
            color: #5b21b6;
        }

        .status-out_for_delivery {
            background: #fed7aa;
            color: #c2410c;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .tracking-timeline {
            position: relative;
            margin: 40px 0;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 50px;
            width: 2px;
            height: calc(100% - 10px);
            background: #e5e7eb;
        }

        .timeline-item.active::after {
            background: #10b981;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1rem;
            flex-shrink: 0;
            border: 3px solid #e5e7eb;
            background: white;
            color: #6b7280;
        }

        .timeline-item.active .timeline-icon {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }

        .timeline-item.current .timeline-icon {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .timeline-date {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .timeline-description {
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.5;
        }

        .order-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }

        .order-details h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: #4b5563;
            color: white;
        }

        @media (max-width: 768px) {

            .tracking-search,
            .tracking-result {
                padding: 25px;
            }

            .search-form {
                flex-direction: column;
            }

            .order-header {
                flex-direction: column;
                text-align: center;
            }

            .current-status {
                text-align: center;
            }

            .timeline-item {
                margin-bottom: 25px;
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
        <a href="index.php#flash-deals" class="promo-shop-link">Shop Now</a>
    </div>

    <!-- Floating Bubbles Background -->
    <div class="floating-bubbles"></div>

    <!-- Main Header -->
    <header class="main-header animate__animated animate__fadeInDown">
        <div class="container-fluid" style="padding: 0 40px;">
            <div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
                <!-- Logo - Far Left -->
                <a href="index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                        alt="Gadget Garage">
                </a>

                <!-- Center Content -->
                <div class="d-flex align-items-center" style="flex: 1; justify-content: center; gap: 60px;">
                    <!-- Search Bar -->
                    <form class="search-container" method="GET" action="product_search_result.php">
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
                    <?php if ($is_logged_in): ?>
                        <!-- Wishlist Icon -->
                        <div class="header-icon">
                            <a href="views/wishlist.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-heart"></i>
                                <span class="wishlist-badge" id="wishlistBadge" style="display: none;">0</span>
                            </a>
                        </div>

                        <!-- Cart Icon -->
                        <div class="header-icon">
                            <a href="views/cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
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
                            <div class="user-avatar" id="userAvatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>">
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
                                <a href="login/logout.php" class="dropdown-item-custom">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login Button -->
                        <a href="login/login.php" class="login-btn">
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
                                    <li><a href="all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="views/all_product.php?brand=Apple"><i class="fas fa-tag"></i> Apple</a></li>
                                <li><a href="views/all_product.php?brand=Samsung"><i class="fas fa-tag"></i> Samsung</a></li>
                                <li><a href="views/all_product.php?brand=HP"><i class="fas fa-tag"></i> HP</a></li>
                                <li><a href="views/all_product.php?brand=Dell"><i class="fas fa-tag"></i> Dell</a></li>
                                <li><a href="views/all_product.php?brand=Sony"><i class="fas fa-tag"></i> Sony</a></li>
                                <li><a href="views/all_product.php?brand=Canon"><i class="fas fa-tag"></i> Canon</a></li>
                                <li><a href="views/all_product.php?brand=Nikon"><i class="fas fa-tag"></i> Nikon</a></li>
                                <li><a href="views/all_product.php?brand=Microsoft"><i class="fas fa-tag"></i> Microsoft</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <a href="index.php" class="nav-item"><span data-translate="home">HOME</span></a>

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
                                    <a href="views/mobile_devices.php" style="text-decoration: none; color: inherit;">
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
                                    <a href="views/computing.php" style="text-decoration: none; color: inherit;">
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
                                    <a href="views/photography_video.php" style="text-decoration: none; color: inherit;">
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
                                        <a href="views/all_product.php" class="shop-now-btn">Shop</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="views/repair_services.php" class="nav-item"><span data-translate="repair_studio">REPAIR STUDIO</span></a>
                <a href="views/device_drop.php" class="nav-item"><span data-translate="device_drop">DEVICE DROP</span></a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="more">MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="views/contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                            <li><a href="views/terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="views/flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
            </div>
        </div>
    </nav>

    <div class="tracking-container">
        <!-- Search Form -->
        <div class="tracking-search">
            <h1><i class="fas fa-search-location me-3"></i>Track Your Order</h1>
            <p>Enter your order number or tracking number to see the latest status of your delivery</p>

            <form class="search-form" method="GET">
                <input
                    type="text"
                    name="order"
                    class="search-input"
                    placeholder="Enter Order ID or Tracking Number"
                    value="<?php echo htmlspecialchars($_GET['order'] ?? ''); ?>"
                    required>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search me-2"></i>Track Order
                </button>
            </form>
        </div>

        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Tracking Results -->
        <?php if ($tracking_result): ?>
            <div class="tracking-result">
                <div class="order-header">
                    <div class="order-info">
                        <h2>Order #<?php echo htmlspecialchars($tracking_result['order']['order_id']); ?></h2>
                        <div class="tracking-number">
                            Tracking: <?php echo htmlspecialchars($tracking_result['order']['tracking_number']); ?>
                        </div>
                    </div>
                    <div class="current-status">
                        <span class="status-badge status-<?php echo strtolower($tracking_result['order']['order_status']); ?>">
                            <?php echo ucwords(str_replace('_', ' ', $tracking_result['order']['order_status'])); ?>
                        </span>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <div class="tracking-timeline">
                    <?php if (!empty($tracking_result['tracking'])): ?>
                        <?php
                        $current_status = $tracking_result['order']['order_status'];
                        $status_order = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
                        $current_index = array_search($current_status, $status_order);
                        ?>

                        <?php foreach ($tracking_result['tracking'] as $index => $track): ?>
                            <?php
                            $is_current = $track['status'] === $current_status;
                            $is_active = array_search($track['status'], $status_order) <= $current_index;
                            ?>
                            <div class="timeline-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                <div class="timeline-icon">
                                    <?php
                                    $icons = [
                                        'pending' => 'fa-clock',
                                        'processing' => 'fa-cogs',
                                        'shipped' => 'fa-truck',
                                        'out_for_delivery' => 'fa-route',
                                        'delivered' => 'fa-check-circle',
                                        'cancelled' => 'fa-times-circle'
                                    ];
                                    $icon = $icons[$track['status']] ?? 'fa-info-circle';
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <?php echo ucwords(str_replace('_', ' ', $track['status'])); ?>
                                    </div>
                                    <div class="timeline-date">
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($track['status_date'])); ?>
                                    </div>
                                    <?php if ($track['notes']): ?>
                                        <div class="timeline-description">
                                            <?php echo htmlspecialchars($track['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($track['location']): ?>
                                        <div class="timeline-description">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($track['location']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Order Details -->
                <div class="order-details">
                    <h3>Order Summary</h3>
                    <div class="order-summary">
                        <div class="summary-item">
                            <div class="summary-label">Order Date</div>
                            <div class="summary-value">
                                <?php echo date('M j, Y', strtotime($tracking_result['order']['order_date'])); ?>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total Amount</div>
                            <div class="summary-value">
                                GH¢<?php echo number_format($tracking_result['order']['total_amount'], 2); ?>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Items</div>
                            <div class="summary-value">
                                <?php echo $tracking_result['order']['item_count']; ?> item(s)
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Payment Status</div>
                            <div class="summary-value">
                                <span class="text-success">Paid</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Shop
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dropdown timeout variables
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
                if (dropdown.classList.contains('show')) {
                    hideUserDropdown();
                } else {
                    showUserDropdown();
                }
            }
        }

        // Enhanced dropdown behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Shop by Brands dropdown
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

            // User dropdown hover and click functionality
            const userAvatar = document.getElementById('userAvatar');
            const userDropdown = document.getElementById('userDropdownMenu');

            if (userAvatar && userDropdown) {
                userAvatar.addEventListener('mouseenter', showUserDropdown);
                userAvatar.addEventListener('mouseleave', hideUserDropdown);
                userAvatar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    toggleUserDropdown();
                });
                userDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(userDropdownTimeout);
                });
                userDropdown.addEventListener('mouseleave', hideUserDropdown);
            }

            // Click outside to close dropdowns
            document.addEventListener('click', function(event) {
                if (userDropdown && !userAvatar.contains(event.target) && !userDropdown.contains(event.target)) {
                    hideUserDropdown();
                }
            });
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
            window.location.href = 'views/account.php';
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
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
                const toggleSwitch = document.getElementById('themeToggle');
                if (toggleSwitch) {
                    toggleSwitch.classList.add('active');
                }
            }
        });

        // Language change function
        function changeLanguage(lang) {
            // Language change logic can be added here
            console.log('Language changed to:', lang);
        }
    </script>
</body>

</html>