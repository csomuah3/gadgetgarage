<?php
try {
	require_once(__DIR__ . '/../settings/core.php');
	require_once(__DIR__ . '/../controllers/cart_controller.php');
	require_once(__DIR__ . '/../helpers/image_helper.php');

	$is_logged_in = check_login();
	$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
	$ip_address = $_SERVER['REMOTE_ADDR'];

	// Get cart items for both logged-in and guest users
	$cart_items = get_user_cart_ctr($customer_id, $ip_address);
	$cart_total = get_cart_total_ctr($customer_id, $ip_address);
	$cart_count = get_cart_count_ctr($customer_id, $ip_address);

	if (empty($cart_items)) {
		header("Location: cart.php");
		exit;
	}

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
} catch (Exception $e) {
	die("Critical error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Checkout - Gadget Garage</title>
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<link href="../includes/chatbot-styles.css" rel="stylesheet">
	<link href="../css/dark-mode.css" rel="stylesheet">
	<link href="../includes/header.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<style>
		/* Import Google Fonts */
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

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

		.user-actions {
			display: flex;
			align-items: center;
			gap: 11px;
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
			color: #008060;
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
			border-radius: 6px;
			transition: all 0.2s ease;
		}

		.brands-dropdown a:hover {
			background: #f3f4f6;
			color: #008060;
		}

		/* Scrollbar styling for brands dropdown */
		.brands-dropdown::-webkit-scrollbar {
			width: 6px;
		}

		.brands-dropdown::-webkit-scrollbar-track {
			background: #f1f1f1;
			border-radius: 3px;
		}

		.brands-dropdown::-webkit-scrollbar-thumb {
			background: #c1c1c1;
			border-radius: 3px;
		}

		.brands-dropdown::-webkit-scrollbar-thumb:hover {
			background: #a8a8a8;
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

		.user-menu {
			position: relative;
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

		.logout-btn {
			background: linear-gradient(135deg, #ef4444, #dc2626);
			color: white;
			border: none;
			padding: 8px 16px;
			border-radius: 16px;
			font-weight: 500;
			text-decoration: none;
			transition: all 0.3s ease;
			display: inline-block;
			font-size: 0.875rem;
		}

		.logout-btn:hover {
			background: linear-gradient(135deg, #dc2626, #b91c1c);
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

		/* Main Navigation */
		

		.nav-menu {
			display: flex;
			align-items: center;
			width: 100%;
			padding-left: 260px;
		}

		.nav-item {
			color: #1f2937;
			text-decoration: none;
			font-weight: 500;
			padding: 16px 20px;
			display: flex;
			align-items: center;
			gap: 5px;
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
			padding-right: 470px;
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
			font-size: 1rem;
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
			font-size: 0.8rem;
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
			font-size: 1.1rem;
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
			font-size: 0.95rem;
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
			background: #008060;
			color: white;
			padding: 4px 12px;
			border-radius: 6px;
			text-decoration: none;
			font-size: 0.8rem;
			font-weight: 500;
			transition: all 0.3s ease;
		}

		.shop-now-btn:hover {
			background: #374151;
			color: white;
		}

		/* Page Title */
		.page-title {
			text-align: center;
			padding: 20px 0;
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			margin: 0;
		}

		.checkout-header {
			background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
			color: #1f2937;
			padding: 1.5rem 0;
			margin-bottom: 1rem;
		}

		.checkout-steps {
			display: flex;
			justify-content: center;
			margin-bottom: 2rem;
		}

		.step {
			display: flex;
			align-items: center;
			color: rgba(255, 255, 255, 0.6);
			font-weight: 500;
		}

		.step.active {
			color: white;
		}

		.step-number {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.2);
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 10px;
			font-weight: 600;
		}

		.step.active .step-number {
			background: white;
			color: #000000;
		}

		.step-divider {
			width: 60px;
			height: 2px;
			background: rgba(255, 255, 255, 0.3);
			margin: 0 20px;
		}

		.checkout-card {
			background: white;
			border-radius: 15px;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
			padding: 1.5rem;
			margin-bottom: 1rem;
		}

		.order-item {
			border-bottom: 1px solid #f1f5f9;
			padding: 1rem 0;
		}

		.order-item:last-child {
			border-bottom: none;
		}

		.product-image-small {
			width: 60px;
			height: 60px;
			object-fit: cover;
			border-radius: 8px;
		}

		.btn-primary {
			background: #000000;
			border: none;
			border-radius: 25px;
			padding: 15px 40px;
			font-weight: 600;
			font-size: 1.1rem;
			transition: all 0.3s ease;
		}

		.btn-primary:hover {
			background: linear-gradient(135deg, #7c4dff, #e91e63);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(0, 128, 96, 0.3);
		}

		.btn-outline-secondary {
			border: 2px solid #6c757d;
			color: #6c757d;
			border-radius: 25px;
			padding: 15px 40px;
			font-weight: 600;
			transition: all 0.3s ease;
		}

		.btn-outline-secondary:hover {
			background: #6c757d;
			color: white;
		}

		.payment-methods {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 1rem;
			margin: 2rem 0;
		}

		.payment-option {
			border: 2px solid #e2e8f0;
			border-radius: 15px;
			padding: 1.5rem;
			text-align: center;
			cursor: pointer;
			transition: all 0.3s ease;
			background: white;
			position: relative;
			z-index: 1;
			user-select: none;
		}

		.payment-option:hover,
		.payment-option.selected {
			border-color: #000000;
			background: #f8f9ff;
		}

		.payment-option i {
			font-size: 2rem;
			color: #000000;
			margin-bottom: 0.5rem;
		}

		.order-summary {
			background: #f8f9ff;
			border-radius: 15px;
			padding: 2rem;
			position: sticky;
			top: 120px;
		}

		.summary-row {
			display: flex;
			justify-content: between;
			margin-bottom: 0.5rem;
		}

		.summary-row.total {
			border-top: 2px solid #e2e8f0;
			padding-top: 1rem;
			font-weight: 700;
			font-size: 1.2rem;
			color: #000000;
		}

		.secure-badge {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			color: #059669;
			background: #d1fae5;
			padding: 0.5rem 1rem;
			border-radius: 25px;
			font-size: 0.9rem;
			margin-top: 1rem;
		}

		.navbar-nav .nav-link {
			color: #4a5568 !important;
			font-weight: 500;
			transition: color 0.3s ease;
		}

		.navbar-nav .nav-link:hover {
			color: #8b5fbf !important;
		}

		.dropdown-menu {
			border: none;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
			border-radius: 15px;
			padding: 1rem 0;
		}

		.dropdown-item {
			padding: 0.75rem 1.5rem;
			transition: all 0.3s ease;
		}

		.dropdown-item:hover {
			background: #f8f9ff;
			color: #000000;
		}

		/* Payment Modal Styles */
		.payment-modal {
			backdrop-filter: blur(10px);
		}

		.payment-modal .modal-content {
			border: none;
			border-radius: 20px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
		}

		.payment-modal .modal-header {
			background: #000000;
			color: white;
			border-radius: 20px 20px 0 0;
			padding: 2rem;
		}

		.payment-modal .modal-body {
			padding: 3rem 2rem;
		}

		.payment-icon {
			font-size: 4rem;
			color: #000000;
			margin-bottom: 1rem;
		}

		@media (max-width: 768px) {
			.checkout-steps {
				display: none;
			}

			.checkout-header {
				padding: 2rem 0;
			}

			.payment-methods {
				grid-template-columns: 1fr;
			}

			.step-divider {
				display: none;
			}
		}


		/* Dark Mode Promotional Banner Styles */
		@media (prefers-color-scheme: dark) {
			
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

	<!-- Floating Bubbles Background -->
	<div class="floating-bubbles" id="floatingBubbles"></div>
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
					<?php if ($is_logged_in): ?>
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
								<div class="dropdown-divider-custom"></div>
								<a href="../login/logout.php" class="dropdown-item-custom">
									<i class="fas fa-sign-out-alt"></i>
									<span>Logout</span>
								</a>
							</div>
						</div>
					<?php else: ?>
						<!-- Login Button -->
						<a href="login.php" class="login-btn">
							<i class="fas fa-user"></i>
							Login
						</a>
					<?php endif; ?>

				</div>
			</div>
		</div>
	</header>

	<!-- Main Navigation -->
