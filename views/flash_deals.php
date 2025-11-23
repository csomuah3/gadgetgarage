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

// Filter products for Flash Deals category only
$flash_deal_categories = ['Flash Deals', 'flash deals', 'flash_deals', 'Flash Deal', 'flash deal'];

$flash_deal_products = array_filter($all_products, function($product) use ($flash_deal_categories) {
    return in_array($product['cat_name'], $flash_deal_categories) ||
           stripos($product['product_title'], 'flash') !== false ||
           stripos($product['cat_name'], 'flash') !== false;
});

// Sort by newest first (assuming you have a date field, otherwise by product_id)
usort($flash_deal_products, function($a, $b) {
    return $b['product_id'] <=> $a['product_id'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>⚡ Flash Deals - Gadget Garage</title>
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

		.theme-toggle {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.toggle-switch {
			position: relative;
			width: 40px;
			height: 20px;
			background: #e2e8f0;
			border-radius: 10px;
			cursor: pointer;
			transition: all 0.3s ease;
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

		.toggle-switch.active {
			background: linear-gradient(135deg, #008060, #006b4e);
		}

		.toggle-switch.active .toggle-slider {
			transform: translateX(20px);
		}

		.language-selector {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		/* Dark Mode Styles */
		body.dark-mode {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
			color: #e2e8f0;
		}

		body.dark-mode .promo-banner {
			background: #0f1419 !important;
		}

		body.dark-mode .main-header {
			background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
			border-bottom-color: #4a5568;
		}

		/* Dark mode navigation styles removed - using external CSS */

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

		/* Flash Deal Page Styles */
		.flash-deal-hero {
			background: linear-gradient(135deg, #ff6b6b, #ffa500);
			padding: 80px 0;
			text-align: center;
			color: white;
			position: relative;
			overflow: hidden;
		}

		.flash-deal-hero::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='m36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
		}

		.flash-deal-title {
			font-size: 4rem;
			font-weight: 800;
			margin-bottom: 20px;
			text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
			position: relative;
		}

		.flash-deal-subtitle {
			font-size: 1.5rem;
			margin-bottom: 30px;
			opacity: 0.9;
			position: relative;
		}

		.deal-timer {
			background: rgba(255,255,255,0.2);
			backdrop-filter: blur(10px);
			border-radius: 20px;
			padding: 20px 40px;
			display: inline-block;
			margin-top: 20px;
			position: relative;
		}

		.timer-section {
			display: flex;
			gap: 20px;
			align-items: center;
		}

		.timer-unit {
			text-align: center;
		}

		.timer-number {
			font-size: 2.5rem;
			font-weight: bold;
			display: block;
		}

		.timer-label {
			font-size: 0.9rem;
			opacity: 0.8;
		}

		/* Product Grid */
		.flash-products-container {
			background: rgba(255,255,255,0.95);
			backdrop-filter: blur(20px);
			border-radius: 30px;
			margin: -50px auto 50px;
			max-width: 1400px;
			padding: 50px 30px;
			box-shadow: 0 20px 60px rgba(0,0,0,0.1);
		}

		.products-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
			gap: 30px;
			margin-top: 30px;
		}

		.flash-product-card,
		.modern-product-card {
			background: white;
			border-radius: 20px;
			padding: 0;
			box-shadow: 0 10px 30px rgba(0,0,0,0.1);
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.flash-product-card:hover,
		.modern-product-card:hover {
			transform: rotate(-2deg) scale(1.02);
			box-shadow: 0 20px 50px rgba(0,0,0,0.15);
		}

		.flash-badge {
			position: absolute;
			top: 15px;
			right: 15px;
			background: linear-gradient(135deg, #ff6b6b, #ffa500);
			color: white;
			padding: 8px 15px;
			border-radius: 25px;
			font-size: 0.8rem;
			font-weight: bold;
			animation: flash 2s infinite;
		}

		@keyframes flash {
			0%, 100% { opacity: 1; }
			50% { opacity: 0.7; }
		}

		.product-image {
			width: 100%;
			height: 200px;
			object-fit: cover;
			border-radius: 15px;
			margin-bottom: 20px;
		}

		.product-title {
			font-size: 1.1rem;
			font-weight: 600;
			margin-bottom: 10px;
			color: #2d3748;
		}

		.price-section {
			display: flex;
			align-items: center;
			gap: 15px;
			margin-bottom: 20px;
		}

		.flash-price {
			font-size: 1.5rem;
			font-weight: bold;
			color: #ff6b6b;
		}

		.original-price {
			font-size: 1.1rem;
			color: #999;
			text-decoration: line-through;
		}

		.discount-percent {
			background: #4caf50;
			color: white;
			padding: 4px 8px;
			border-radius: 10px;
			font-size: 0.8rem;
			font-weight: bold;
		}

		.add-to-cart-btn {
			width: 100%;
			background: linear-gradient(135deg, #ff6b6b, #ffa500);
			color: white;
			border: none;
			padding: 12px 20px;
			border-radius: 15px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.add-to-cart-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 5px 15px rgba(255,107,107,0.3);
		}

		/* Lightning animations */
		.lightning {
			position: absolute;
			color: #ffd700;
			font-size: 2rem;
			animation: lightning 3s infinite;
		}

		@keyframes lightning {
			0%, 100% { opacity: 0; transform: scale(0.5) rotate(0deg); }
			50% { opacity: 1; transform: scale(1) rotate(180deg); }
		}

		/* No products message */
		.no-products {
			text-align: center;
			padding: 60px 20px;
			color: #666;
		}

		.no-products i {
			font-size: 4rem;
			margin-bottom: 20px;
			opacity: 0.5;
		}

		/* Responsive Design */
		@media (max-width: 768px) {
			.flash-deal-title {
				font-size: 2.5rem;
			}

			.flash-deal-subtitle {
				font-size: 1.2rem;
			}

			.timer-section {
				gap: 10px;
			}

			.timer-number {
				font-size: 1.8rem;
			}

			.products-grid {
				grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
				gap: 20px;
			}

			.flash-products-container {
				margin: -30px 15px 30px;
				padding: 30px 15px;
			}
		}
    /* Dark Mode Promotional Banner Styles */
    @media (prefers-color-scheme: dark) {
        .promo-banner {
            background: linear-gradient(90deg, #1a202c, #2d3748);
            color: #f7fafc;
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
	</nav>

	<!-- Flash Deal Hero Section -->
	<div class="flash-deal-hero">
		<div class="lightning" style="top: 10%; left: 15%; animation-delay: 0s;">⚡</div>
		<div class="lightning" style="top: 20%; right: 20%; animation-delay: 1s;">⚡</div>
		<div class="lightning" style="bottom: 30%; left: 25%; animation-delay: 2s;">⚡</div>
		<div class="lightning" style="bottom: 15%; right: 15%; animation-delay: 0.5s;">⚡</div>

		<div class="container">
			<h1 class="flash-deal-title animate__animated animate__bounceInDown">
				⚡ FLASH DEALS ⚡
			</h1>
			<p class="flash-deal-subtitle animate__animated animate__fadeInUp animate__delay-1s">
				Lightning Fast Savings - Limited Time Only!
			</p>

			<div class="deal-timer animate__animated animate__fadeInUp animate__delay-2s">
				<div class="timer-section" id="flashTimer">
					<div class="timer-unit">
						<span class="timer-number" id="hours">24</span>
						<span class="timer-label">HOURS</span>
					</div>
					<div style="font-size: 2rem; font-weight: bold;">:</div>
					<div class="timer-unit">
						<span class="timer-number" id="minutes">59</span>
						<span class="timer-label">MINUTES</span>
					</div>
					<div style="font-size: 2rem; font-weight: bold;">:</div>
					<div class="timer-unit">
						<span class="timer-number" id="seconds">59</span>
						<span class="timer-label">SECONDS</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Flash Deal Products -->
	<div class="container-fluid px-0">
		<div class="flash-products-container">
			<div class="text-center mb-4">
				<h2 style="color: #2d3748; font-weight: bold;">🔥 Hot Flash Deals</h2>
				<p style="color: #666;">Grab these amazing deals before they're gone!</p>
			</div>

			<?php if (!empty($flash_deal_products)): ?>
				<div class="products-grid">
					<?php foreach ($flash_deal_products as $product):
						// Calculate flash deal discount (fixed at 30% for flash deals)
						$original_price = floatval($product['product_price']);
						$flash_discount_percentage = 30;
						$flash_price = $original_price * 0.7; // 30% discount for flash deals
						$rating = round(rand(40, 50) / 10, 1); // Random rating between 4.0-5.0
					?>
						<div class="modern-product-card animate__animated animate__fadeInUp" style="
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
							<!-- Flash Deal Badge (top-left) -->
							<div style="position: absolute; top: 12px; left: 12px; background: linear-gradient(135deg, #ff6b6b, #ffa500); color: white; padding: 8px 15px; border-radius: 25px; font-weight: 700; font-size: 0.8rem; z-index: 10; animation: flash 2s infinite;">
								⚡ FLASH DEAL
							</div>
							<!-- Discount Badge (below flash badge) -->
							<div style="position: absolute; top: 55px; left: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; z-index: 10;">
								-<?php echo $flash_discount_percentage; ?>%
							</div>
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
										<span style="color: #ff6b6b; font-size: 1.75rem; font-weight: 800;">
											GH₵<?php echo number_format($flash_price, 0); ?>
										</span>
										<span style="color: #9ca3af; font-size: 1.2rem; text-decoration: line-through;">
											GH₵<?php echo number_format($original_price, 0); ?>
										</span>
									</div>
									<div style="color: #ff6b6b; font-size: 0.85rem; margin-top: 4px; font-weight: 600;">
										⚡ Flash Deal - Limited time offer!
									</div>
								</div>
								<!-- View Details Button -->
								<button onclick="viewProductDetails(<?php echo $product['product_id']; ?>)"
										style="width: 100%; background: linear-gradient(135deg, #ff6b6b, #ffa500); color: white; border: none; padding: 15px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;"
										onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(255,107,107,0.3)';"
										onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<i class="fas fa-bolt"></i>
									View Details
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="no-products">
					<i class="fas fa-bolt"></i>
					<h3>No Flash Deals Available</h3>
					<p>Check back soon for amazing flash deals!</p>
					<a href="all_product.php" class="btn btn-primary mt-3">Browse All Products</a>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Scripts -->
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

		// Flash Deal Timer
		function updateFlashTimer() {
			const now = new Date().getTime();
			const endTime = now + (24 * 60 * 60 * 1000); // 24 hours from now

			const distance = endTime - now;

			const hours = Math.floor(distance / (1000 * 60 * 60));
			const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
			const seconds = Math.floor((distance % (1000 * 60)) / 1000);

			const hoursEl = document.getElementById('hours');
			const minutesEl = document.getElementById('minutes');
			const secondsEl = document.getElementById('seconds');

			if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, '0');
			if (minutesEl) minutesEl.textContent = minutes.toString().padStart(2, '0');
			if (secondsEl) secondsEl.textContent = seconds.toString().padStart(2, '0');
		}

		// Update timers every second
		setInterval(updateTimer, 1000);
		setInterval(updateFlashTimer, 1000);
		updateTimer(); // Initial call
		updateFlashTimer(); // Initial call

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
			window.location.href = '../my_orders.php';
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

		// Add to cart functionality
		function addToCart(productId) {
			// This would integrate with your existing cart system
			fetch('actions/add_to_cart.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					product_id: productId,
					qty: 1
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					Swal.fire({
						title: 'Success!',
						text: 'Item added to cart!',
						icon: 'success',
						confirmButtonColor: '#28a745',
						confirmButtonText: 'OK',
						timer: 2000,
						timerProgressBar: true
					});
					// Update cart badge if exists
					updateCartBadge();
				} else {
					Swal.fire({
						title: 'Error',
						text: 'Error adding item to cart: ' + data.message,
						icon: 'error',
						confirmButtonColor: '#dc3545',
						confirmButtonText: 'OK'
					});
				}
			})
			.catch(error => {
				console.error('Error:', error);
				Swal.fire({
					title: 'Error',
					text: 'Error adding item to cart',
					icon: 'error',
					confirmButtonColor: '#dc3545',
					confirmButtonText: 'OK'
				});
			});
		}

		function updateCartBadge() {
			// Update cart count in header
			fetch('actions/get_cart_count.php')
			.then(response => response.json())
			.then(data => {
				const badge = document.getElementById('cartBadge');
				if (badge && data.count > 0) {
					badge.textContent = data.count;
					badge.style.display = 'block';
				}
			});
		}

		// View product details
		function viewProductDetails(productId) {
			window.location.href = 'single_product.php?pid=' + productId;
		}

		// Toggle wishlist functionality
		function toggleWishlist(productId) {
			<?php if (isset($_SESSION['user_id'])): ?>
				fetch('actions/toggle_wishlist.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						product_id: productId
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						const heartIcon = event.target.closest('button').querySelector('i');
						if (data.added) {
							heartIcon.className = 'fas fa-heart';
							heartIcon.style.color = '#ef4444';
							Swal.fire({
								title: 'Added to Wishlist!',
								icon: 'success',
								confirmButtonColor: '#28a745',
								timer: 1500,
								timerProgressBar: true,
								showConfirmButton: false
							});
						} else {
							heartIcon.className = 'far fa-heart';
							heartIcon.style.color = '#6b7280';
							Swal.fire({
								title: 'Removed from Wishlist!',
								icon: 'info',
								confirmButtonColor: '#17a2b8',
								timer: 1500,
								timerProgressBar: true,
								showConfirmButton: false
							});
						}
					} else {
						Swal.fire({
							title: 'Error',
							text: data.message,
							icon: 'error',
							confirmButtonColor: '#dc3545',
							confirmButtonText: 'OK'
						});
					}
				})
				.catch(error => {
					console.error('Error:', error);
					Swal.fire({
						title: 'Error',
						text: 'Error updating wishlist',
						icon: 'error',
						confirmButtonColor: '#dc3545',
						confirmButtonText: 'OK'
					});
				});
			<?php else: ?>
				Swal.fire({
					title: 'Login Required',
					text: 'Please login to add items to wishlist',
					icon: 'warning',
					confirmButtonColor: '#ffc107',
					confirmButtonText: 'Login',
					showCancelButton: true,
					cancelButtonText: 'Cancel'
				}).then((result) => {
					if (result.isConfirmed) {
						window.location.href = '../login/login.php';
					}
				});
			<?php endif; ?>
		}
	</script>
</body>
</html>