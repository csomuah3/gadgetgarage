<?php
session_start();
require_once __DIR__ . '/../settings/core.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login/login.php');
    exit();
}

// Initialize variables
$is_logged_in = true;
$is_admin = check_admin();
$cart_count = 0;

// Get cart count for logged in users
if (!$is_admin) {
    require_once __DIR__ . '/../controllers/cart_controller.php';
    $cart_count = get_cart_count_ctr($_SESSION['user_id']);
}

// Get brands and categories for navigation
require_once __DIR__ . '/../controllers/brand_controller.php';
require_once __DIR__ . '/../controllers/category_controller.php';

$brands = get_all_brands_ctr() ?: [];
$categories = get_all_categories_ctr() ?: [];

// Get customer notifications
require_once __DIR__ . '/../controllers/support_controller.php';
$notifications = get_customer_notifications_ctr($_SESSION['user_id']);
$unread_count = get_unread_notification_count_ctr($_SESSION['user_id']);

$page_title = "Notifications - GadgetGarage";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../includes/header.css">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">

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
        .cart-badge,
        .notification-badge {
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

        /* Dark Mode Styles */
        body.dark-mode {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e2e8f0;
        }

        body.dark-mode 

        body.dark-mode 

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
        .notifications-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .notifications-content {
            padding: 60px 0;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f1ff 100%);
            border-left-color: #ff6b6b;
        }

        .notification-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }

        .notification-type {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .notification-date {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .notification-message {
            color: #374151;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .mark-read-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .mark-read-btn:hover {
            background: #059669;
        }

        .view-message-btn {
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: background-color 0.3s ease;
        }

        .view-message-btn:hover {
            background: #5a67d8;
            color: white;
        }

        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .no-notifications i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .no-notifications h3 {
            color: #374151;
            margin-bottom: 10px;
        }

        .no-notifications p {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .stats-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 12px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }

        .mark-all-read-btn {
            background: #059669;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .mark-all-read-btn:hover {
            background: #047857;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
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
                        <!-- Notifications Icon -->
                        <div class="header-icon">
                            <a href="notifications.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge" id="notificationBadge"><?php echo $unread_count; ?></span>
                                <?php else: ?>
                                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                                <?php endif; ?>
                            </a>
                        </div>

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
                                <?php if ($cart_count > 0): ?>
                                    <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                                <?php else: ?>
                                    <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                                <?php endif; ?>
                            </a>
                        </div>

                        <!-- User Avatar Dropdown -->
                        <div class="user-dropdown">
                            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>" onclick="toggleUserDropdown()">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
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
                                    <li><a href="../all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
                                    <li><a href="../all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="computing.php" style="text-decoration: none; color: inherit;">
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
                                    <a href="photography_video.php" style="text-decoration: none; color: inherit;">
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
                                        <a href="all_product.php" class="shop-now-btn">Shop</a>
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
    <!-- Notifications Hero Section -->
    <section class="notifications-hero">
        <div class="container">
            <h1><i class="fas fa-bell me-3"></i>Your Notifications</h1>
            <p class="lead">Stay updated with your support messages and system notifications</p>
        </div>
    </section>

    <!-- Notifications Content -->
    <section class="notifications-content">
        <div class="container">
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= count($notifications) ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $unread_count ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count($notifications) - $unread_count ?></div>
                        <div class="stat-label">Read Messages</div>
                    </div>
                </div>
            </div>

            <!-- Alert for success/error messages -->
            <div id="alertContainer"></div>

            <?php if (!empty($notifications)): ?>
                <!-- Mark All Read Button -->
                <?php if ($unread_count > 0): ?>
                    <button class="mark-all-read-btn" onclick="markAllAsRead()">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </button>
                <?php endif; ?>

                <!-- Notifications List -->
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" id="notification-<?= $notification['notification_id'] ?>">
                            <div class="notification-header">
                                <span class="notification-type">
                                    <?php
                                    switch($notification['type']) {
                                        case 'support_response':
                                            echo '<i class="fas fa-reply me-1"></i> Support Response';
                                            break;
                                        case 'order_update':
                                            echo '<i class="fas fa-box me-1"></i> Order Update';
                                            break;
                                        default:
                                            echo '<i class="fas fa-info me-1"></i> Notification';
                                    }
                                    ?>
                                </span>
                                <span class="notification-date">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                </span>
                            </div>

                            <div class="notification-message">
                                <?= htmlspecialchars($notification['message']) ?>
                            </div>

                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                    <button class="mark-read-btn" onclick="markAsRead(<?= $notification['notification_id'] ?>)">
                                        <i class="fas fa-check me-1"></i>Mark as Read
                                    </button>
                                <?php endif; ?>

                                <?php if ($notification['type'] === 'support_response' && $notification['related_id']): ?>
                                    <a href="admin/support_messages.php?view=<?= $notification['related_id'] ?>" class="view-message-btn">
                                        <i class="fas fa-eye me-1"></i>View Message
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Notifications State -->
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Notifications Yet</h3>
                    <p>You'll see notifications here when there are updates to your support messages or orders.</p>
                    <a href="contact.php" class="btn btn-primary">
                        <i class="fas fa-envelope me-2"></i>Contact Support
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Header JavaScript -->
    <script src="js/header.js"></script>

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

        // User dropdown functionality
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

        // Existing notification functions...
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show`;
            alert.innerHTML = `
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            alertContainer.appendChild(alert);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        function markAsRead(notificationId) {
            fetch('actions/mark_notification_read_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const notificationElement = document.getElementById(`notification-${notificationId}`);
                    if (notificationElement) {
                        notificationElement.classList.remove('unread');
                        const markReadBtn = notificationElement.querySelector('.mark-read-btn');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    }

                    // Update notification badge in header
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        let count = parseInt(badge.textContent) - 1;
                        if (count <= 0) {
                            badge.remove();
                        } else {
                            badge.textContent = count;
                        }
                    }

                    // Update stats
                    updateStats();

                    showAlert('Notification marked as read');
                } else {
                    showAlert(data.message || 'Failed to mark notification as read', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred', 'error');
            });
        }

        function markAllAsRead() {
            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
            const promises = [];

            unreadNotifications.forEach(notification => {
                const notificationId = notification.id.replace('notification-', '');
                promises.push(
                    fetch('actions/mark_notification_read_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            notification_id: parseInt(notificationId)
                        })
                    }).then(response => response.json())
                );
            });

            Promise.all(promises).then(results => {
                let successCount = 0;
                results.forEach((data, index) => {
                    if (data.status === 'success') {
                        successCount++;
                        const notificationElement = unreadNotifications[index];
                        notificationElement.classList.remove('unread');
                        const markReadBtn = notificationElement.querySelector('.mark-read-btn');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    }
                });

                if (successCount > 0) {
                    // Remove notification badge
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.remove();
                    }

                    // Remove mark all read button
                    const markAllBtn = document.querySelector('.mark-all-read-btn');
                    if (markAllBtn) {
                        markAllBtn.remove();
                    }

                    // Update stats
                    updateStats();

                    showAlert(`${successCount} notifications marked as read`);
                } else {
                    showAlert('Failed to mark notifications as read', 'error');
                }
            }).catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred', 'error');
            });
        }

        function updateStats() {
            const totalNotifications = document.querySelectorAll('.notification-item').length;
            const unreadNotifications = document.querySelectorAll('.notification-item.unread').length;
            const readNotifications = totalNotifications - unreadNotifications;

            const statNumbers = document.querySelectorAll('.stat-number');
            if (statNumbers.length >= 3) {
                statNumbers[1].textContent = unreadNotifications;
                statNumbers[2].textContent = readNotifications;
            }
        }
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
</body>
</html>