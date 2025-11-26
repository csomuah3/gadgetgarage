<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');

// Check login status
$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Try to load categories and brands for navigation
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Device Drop - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

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

        .device-drop-container {
            padding: 40px 0;
            min-height: 80vh;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            text-align: center;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #008060;
            display: inline-block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #008060;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .condition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .condition-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .condition-option:hover {
            border-color: #008060;
            background: #f0fdf4;
        }

        .condition-option.selected {
            border-color: #008060;
            background: #ecfdf5;
        }

        .condition-option input[type="radio"] {
            display: none;
        }

        .condition-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .condition-description {
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .image-upload {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .image-upload:hover {
            border-color: #008060;
            background: #f0fdf4;
        }

        .image-upload.dragover {
            border-color: #008060;
            background: #ecfdf5;
        }

        .upload-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 15px;
        }

        .upload-text {
            color: #6b7280;
            margin-bottom: 10px;
        }

        .upload-subtext {
            font-size: 0.9rem;
            color: #9ca3af;
        }

        #imagePreview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }

        .preview-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .preview-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(239, 68, 68, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            cursor: pointer;
        }

        .submit-btn {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 128, 96, 0.3);
        }

        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .process-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .process-info h4 {
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .process-info p {
            color: #1e3a8a;
            margin: 0;
            line-height: 1.5;
        }

        /* Checkbox Styles */
        .checkbox-option {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
            position: relative;
        }

        .checkbox-option:hover {
            border-color: #008060;
            background: #f0fdf4;
        }

        .checkbox-option input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            margin-right: 12px;
            position: relative;
            transition: all 0.3s ease;
            background: white;
            flex-shrink: 0;
        }

        .checkbox-option input[type="checkbox"]:checked + .checkmark {
            background: #008060;
            border-color: #008060;
        }

        .checkbox-option input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .checkbox-option input[type="checkbox"]:checked ~ span:not(.checkmark) {
            color: #008060;
            font-weight: 500;
        }

        /* Success Modal Styles */
        .success-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        .success-modal-overlay.show {
            display: flex;
        }

        .success-modal {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 20px;
        }

        .success-message {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .success-button {
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .success-button:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }

            .condition-grid {
                grid-template-columns: 1fr;
            }

            .success-modal {
                padding: 30px 20px;
            }

            .success-title {
                font-size: 1.5rem;
            }

            .success-message {
                font-size: 1rem;
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

    <!-- Device Drop Content -->
    <div class="device-drop-container">
        <div class="container">
            <h1 class="page-title">Device Drop Request</h1>
            <p class="page-subtitle">
                Submit your device information for evaluation. We'll review your submission and get back to you within 3-7 business days to schedule a pickup appointment.
            </p>

            <div class="form-container">
                <form id="deviceDropForm" enctype="multipart/form-data" method="POST">
                    <!-- Device Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">Device Information</h3>

                        <div class="form-group">
                            <label for="deviceType" class="form-label">Device Type *</label>
                            <select id="deviceType" name="device_type" class="form-select" required>
                                <option value="">Select Device Type</option>
                                <option value="smartphone">Smartphone</option>
                                <option value="tablet">Tablet / iPad</option>
                                <option value="laptop">Laptop</option>
                                <option value="desktop">Desktop Computer</option>
                                <option value="camera">Camera</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="deviceBrand" class="form-label">Brand *</label>
                            <input type="text" id="deviceBrand" name="device_brand" class="form-input" placeholder="e.g., Apple, Samsung, Dell, Sony" required>
                        </div>

                        <div class="form-group">
                            <label for="deviceModel" class="form-label">Model *</label>
                            <input type="text" id="deviceModel" name="device_model" class="form-input" placeholder="e.g., iPhone 12 Pro, Galaxy S21, MacBook Pro 2020" required>
                        </div>
                    </div>

                    <!-- Condition Section -->
                    <div class="form-section">
                        <h3 class="section-title">Device Condition</h3>
                        <label class="form-label">Select the condition that best describes your device *</label>

                        <div class="condition-grid">
                            <div class="condition-option" onclick="selectCondition('excellent')">
                                <input type="radio" name="condition" value="excellent" id="excellent" required>
                                <div class="condition-title">Excellent</div>
                                <div class="condition-description">
                                    Device looks and functions like new. No visible scratches, dents, or wear. Screen is pristine. All buttons and ports work perfectly. Battery life is excellent.
                                </div>
                            </div>

                            <div class="condition-option" onclick="selectCondition('good')">
                                <input type="radio" name="condition" value="good" id="good" required>
                                <div class="condition-title">Good</div>
                                <div class="condition-description">
                                    Device has minor cosmetic wear but functions normally. May have light scratches or small scuffs. Screen is in good condition. All features work properly. Battery life is good.
                                </div>
                            </div>

                            <div class="condition-option" onclick="selectCondition('fair')">
                                <input type="radio" name="condition" value="fair" id="fair" required>
                                <div class="condition-title">Fair</div>
                                <div class="condition-description">
                                    Device shows noticeable wear but still functions. Visible scratches, dents, or cracks may be present. Some features may not work perfectly. Battery life may be reduced.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="form-section">
                        <h3 class="section-title">Additional Details</h3>

                        <div class="form-group">
                            <label class="form-label">Why are you giving up this device? (Select all that apply)</label>
                            <div class="checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; margin-top: 10px;">
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="upgraded_to_newer_model">
                                    <span class="checkmark"></span>
                                    Upgraded to newer model
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="screen_damaged">
                                    <span class="checkmark"></span>
                                    Screen is cracked/damaged
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="battery_issues">
                                    <span class="checkmark"></span>
                                    Battery issues/poor life
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="performance_issues">
                                    <span class="checkmark"></span>
                                    Slow performance/lagging
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="no_longer_needed">
                                    <span class="checkmark"></span>
                                    No longer needed/used
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="hardware_failure">
                                    <span class="checkmark"></span>
                                    Hardware malfunction/failure
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="too_old">
                                    <span class="checkmark"></span>
                                    Device is too old/outdated
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="switching_platforms">
                                    <span class="checkmark"></span>
                                    Switching platforms (iOS to Android, etc.)
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="need_cash">
                                    <span class="checkmark"></span>
                                    Need cash/emergency sale
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="askingPrice" class="form-label">Asking Price (Optional)</label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #6b7280; font-weight: 500;">GH₵</span>
                                <input type="number" id="askingPrice" name="asking_price" class="form-input" placeholder="Enter amount in GH₵" min="0" step="0.01" style="padding-left: 50px;">
                            </div>
                            <small style="color: #6b7280; font-size: 0.9rem;">Leave blank if you prefer our evaluation</small>
                        </div>
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-section">
                        <h3 class="section-title">Device Photos</h3>

                        <div class="form-group">
                            <label class="form-label">Upload images of your device (Recommended)</label>
                            <div class="image-upload" onclick="document.getElementById('images').click()">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <div class="upload-text">Click to upload photos or drag and drop</div>
                                <div class="upload-subtext">Supports: JPG, PNG, GIF (Max: 5MB each)</div>
                            </div>
                            <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewImages()">
                            <div id="imagePreview"></div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">Contact Information</h3>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name *</label>
                                <input type="text" id="firstName" name="first_name" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name *</label>
                                <input type="text" id="lastName" name="last_name" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="(xxx) xxx-xxxx" required>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Pickup Address</label>
                            <textarea id="address" name="address" class="form-textarea" placeholder="Enter your address for device pickup (optional - we can discuss this later)"></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Request Device Drop
                    </button>

                    <!-- Process Information -->
                    <div class="process-info">
                        <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                        <p>
                            <strong>Review:</strong> We'll evaluate your submission within 3-7 business days.<br>
                            <strong>Approval:</strong> Once approved, we'll contact you to schedule a convenient pickup appointment.<br>
                            <strong>Pickup:</strong> Our team will collect your device at the scheduled time and provide payment if applicable.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="success-modal-overlay" id="successModal">
        <div class="success-modal">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="success-title">Request Submitted!</h2>
            <p class="success-message">
                Your device drop request has been submitted successfully. We will review your submission and get back to you within 3-7 business days to schedule a pickup appointment.
            </p>
            <button class="success-button" onclick="returnToHome()">
                Return to Home
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        // Dropdown functions
        let dropdownTimeout;

        function showDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdown.style.opacity = '1';
                dropdown.style.visibility = 'visible';
                dropdown.style.transform = 'translateY(0)';
            }
        }

        function hideDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdownTimeout = setTimeout(() => {
                    dropdown.style.opacity = '0';
                    dropdown.style.visibility = 'hidden';
                    dropdown.style.transform = 'translateY(-10px)';
                }, 300);
            }
        }

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

        // Auth Panel Switching Functions (Not needed for device_drop but keeping for consistency)
        function switchToLogin() {
            // This function is not needed for device_drop.php but keeping for consistency
        }

        function switchToSignup() {
            // This function is not needed for device_drop.php but keeping for consistency
        }
        // Condition selection functionality
        function selectCondition(condition) {
            // Remove selected class from all options
            document.querySelectorAll('.condition-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');

            // Check the radio button
            document.getElementById(condition).checked = true;
        }

        // Image preview functionality
        let selectedFiles = [];

        function previewImages() {
            const input = document.getElementById('images');
            const previewContainer = document.getElementById('imagePreview');

            // Add new files to selectedFiles array
            Array.from(input.files).forEach(file => {
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'File Too Large',
                            text: `File ${file.name} is too large. Maximum size is 5MB.`,
                            icon: 'warning',
                            confirmButtonColor: '#D19C97',
                            confirmButtonText: 'OK'
                        });
                    }
                    return;
                }

                if (selectedFiles.length < 10) { // Limit to 10 images
                    selectedFiles.push(file);
                }
            });

            // Clear and rebuild preview
            previewContainer.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                        <button type="button" class="preview-remove" onclick="removeImage(${index})">×</button>
                    `;
                    previewContainer.appendChild(previewItem);
                };
                reader.readAsDataURL(file);
            });

            // Clear the input
            input.value = '';
        }

        function removeImage(index) {
            selectedFiles.splice(index, 1);
            previewImages(); // Rebuild preview
        }

        // Drag and drop functionality
        const imageUpload = document.querySelector('.image-upload');

        imageUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        imageUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        imageUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            const files = Array.from(e.dataTransfer.files);
            files.forEach(file => {
                if (file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024 && selectedFiles.length < 10) {
                    selectedFiles.push(file);
                }
            });

            previewImages();
        });

        // Form submission
        document.getElementById('deviceDropForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = document.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Request...';
            submitBtn.disabled = true;

            try {
                // Create form data with all fields including images
                const formData = new FormData(this);

                // Add selected images to form data
                selectedFiles.forEach((file, index) => {
                    formData.append(`images[${index}]`, file);
                });

                // Submit to backend
                const response = await fetch('../actions/submit_device_drop.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success modal
                    showSuccessModal();

                    // Reset form
                    this.reset();
                    selectedFiles = [];
                    document.getElementById('imagePreview').innerHTML = '';
                    document.querySelectorAll('.condition-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                } else {
                    console.error('Server error:', result);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error: ' + (result.message || 'There was an error submitting your request.'),
                            icon: 'error',
                            confirmButtonColor: '#D19C97',
                            confirmButtonText: 'OK'
                        });
                    }
                }

            } catch (error) {
                console.error('Network error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Network Error',
                        text: 'There was a network error submitting your request. Please try again or contact us directly.',
                        icon: 'error',
                        confirmButtonColor: '#D19C97',
                        confirmButtonText: 'OK'
                    });
                }
            } finally {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = `(${value.slice(0,3)}) ${value.slice(3,6)}-${value.slice(6,10)}`;
            } else if (value.length >= 3) {
                value = `(${value.slice(0,3)}) ${value.slice(3)}`;
            }
            e.target.value = value;
        });

        // Success modal functions
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('show');
        }

        function hideSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('show');
        }

        function returnToHome() {
            window.location.href = '../index.php';
        }

        // Close modal when clicking outside
        document.getElementById('successModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideSuccessModal();
            }
        });
    </script>
</body>

</html>