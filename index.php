<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
	// Start session and include core functions
	require_once(__DIR__ . '/settings/core.php');
	require_once(__DIR__ . '/controllers/cart_controller.php');
	require_once(__DIR__ . '/helpers/image_helper.php');

	// Check login status and admin status
	$is_logged_in = check_login();
	$is_admin = false;

	if ($is_logged_in) {
		$is_admin = check_admin();

		// Redirect admins to admin dashboard (unless they specifically want to view customer homepage)
		if ($is_admin && !isset($_GET['view_customer'])) {
			header("Location: admin/index.php");
			exit();
		}
	}

	// Get cart count
	$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
	$ip_address = $_SERVER['REMOTE_ADDR'];
	$cart_count = get_cart_count_ctr($customer_id, $ip_address);

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
} catch (Exception $e) {
	// If core fails, show error
	die("Critical error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Gadget Garage - Premium Refurbrished Tech Devices & Repair Services</title>
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<link href="includes/chatbot-styles.css" rel="stylesheet">
	<link href="css/dark-mode.css" rel="stylesheet">
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



		@keyframes wave {

			0%,
			100% {
				transform: rotate(0deg);
			}

			25% {
				transform: rotate(-5deg);
			}

			75% {
				transform: rotate(5deg);
			}
		}

		.contact-number {
			font-size: 1.1 rem;
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
			padding: 14px;
			border-radius: 12px;
			transition: all 0.3s ease;
			color: #1f2937;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.header-icon i {
			font-size: 1.8rem;
			font-weight: 700;
			stroke-width: 2;
			-webkit-text-stroke: 1px currentColor;
		}

		.header-icon:hover {
			background: rgba(0, 128, 96, 0.15);
			color: #008060;
			transform: scale(1.15);
			box-shadow: 0 4px 12px rgba(0, 128, 96, 0.2);
		}

		.cart-badge {
			position: absolute;
			top: 4px;
			right: 4px;
			background: linear-gradient(135deg, #006b4e, #008060);
			color: white;
			font-size: 1rem;
			font-weight: 100;
			padding: 5px 9px;
			border-radius: 14px;
			min-width: 26px;
			text-align: center;
			border: 3px solid white;
			box-shadow: none;
			line-height: 1;
		}

		.wishlist-badge {
			position: absolute;
			top: 4px;
			right: 4px;
			background: linear-gradient(135deg, #ef4444, #dc2626);
			color: white;
			font-size: 1rem;
			font-weight: 100;
			padding: 5px 9px;
			border-radius: 14px;
			min-width: 26px;
			text-align: center;
			border: 3px solid white;
			box-shadow: none;
			line-height: 1;
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
			box-shadow: 0 3px 10px rgba(0, 128, 96, 0.4);
			border: 2px solid rgba(255, 255, 255, 0.3);
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
			z-index: 1000;
			opacity: 0;
			visibility: hidden;
			transform: translateY(-10px);
			transition: all 0.3s ease;
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
			font-size: 0.9rem;
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

		.toggle-switch.active {
			background: linear-gradient(135deg, #008060, #006b4e);
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
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.toggle-switch.active .toggle-slider {
			transform: translateX(20px);
		}

		.language-selector {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.language-flag {
			width: 20px;
			height: 15px;
			border-radius: 3px;
			background: linear-gradient(45deg, #4f46e5, #7c3aed);
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-size: 10px;
			font-weight: bold;
		}

		/* Top Picks Section */
		.top-picks-section {
			background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
			position: relative;
			overflow: hidden;
		}

		.section-title {
			font-size: 3.5rem;
			font-weight: 800;
			background: linear-gradient(135deg, #008060, #006b4e);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
			margin-bottom: 15px;
		}

		.section-subtitle {
			font-size: 1.1rem;
			color: #64748b;
			max-width: 600px;
			margin: 0 auto;
		}

		.top-pick-card {
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(20px);
			border-radius: 20px;
			padding: 25px;
			text-decoration: none;
			color: inherit;
			transition: all 0.4s ease;
			border: 1px solid rgba(139, 95, 191, 0.1);
			box-shadow: 0 8px 32px rgba(139, 95, 191, 0.1);
			height: 100%;
			display: flex;
			flex-direction: column;
		}

		.top-pick-card:hover {
			transform: translateY(-10px) scale(1.02);
			box-shadow: 0 16px 48px rgba(139, 95, 191, 0.2);
			color: inherit;
		}

		.pick-image {
			width: 100%;
			height: 200px;
			object-fit: cover;
			border-radius: 15px;
			margin-bottom: 20px;
			background: linear-gradient(135deg, #f8fafc, #e2e8f0);
		}

		.pick-title {
			font-size: 1.3rem;
			font-weight: 700;
			color: #1a202c;
			margin-bottom: 10px;
		}

		.pick-price {
			font-size: 1.5rem;
			font-weight: 800;
			background: linear-gradient(135deg, #008060, #006b4e);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
			margin-bottom: 15px;
		}

		.pick-description {
			color: #64748b;
			font-size: 0.95rem;
			line-height: 1.6;
			margin-bottom: 20px;
			flex-grow: 1;
		}

		.pick-rating {
			display: flex;
			align-items: center;
			gap: 5px;
			margin-bottom: 15px;
		}

		.rating-stars {
			color: #fbbf24;
			font-size: 0.9rem;
		}

		.rating-text {
			color: #64748b;
			font-size: 0.9rem;
		}

		.pick-badge {
			position: absolute;
			top: 15px;
			right: 15px;
			background: linear-gradient(135deg, #ef4444, #dc2626);
			color: white;
			padding: 6px 12px;
			border-radius: 20px;
			font-size: 0.8rem;
			font-weight: 600;
			text-transform: uppercase;
		}

		.loading-spinner {
			padding: 60px 20px;
			color: #64748b;
		}

		/* Dark Mode Styles */
		body.dark-mode {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
			color: #e2e8f0;
		}

		body.dark-mode .promo-banner {
			background: #001f3f !important;
		}

		body.dark-mode .main-header {
			background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
		}

		body.dark-mode .category-nav {
			background: #2d3748;
			border-top-color: #4a5568;
		}

		body.dark-mode .category-item {
			color: #cbd5e0;
		}

		body.dark-mode .category-item:hover,
		body.dark-mode .category-item.active {
			color: #008060;
		}

		body.dark-mode .hero-section {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
		}

		body.dark-mode .promo-card {
			background: rgba(70, 80, 100, 0.9);
			backdrop-filter: blur(20px);
			border: 1px solid rgba(139, 95, 191, 0.4);
		}

		body.dark-mode .dropdown-menu-custom {
			background: rgba(70, 80, 100, 0.95);
			border-color: rgba(139, 95, 191, 0.5);
		}

		body.dark-mode .dropdown-item-custom {
			color: #cbd5e0;
		}

		body.dark-mode .dropdown-item-custom:hover {
			background: rgba(139, 95, 191, 0.2);
			color: #006b4e;
		}







		/* Dark mode top picks section */
		body.dark-mode .top-picks-section {
			background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
		}

		body.dark-mode .top-pick-card {
			background: rgba(70, 80, 100, 0.9);
			border: 1px solid rgba(139, 95, 191, 0.3);
		}

		/* View All Products Button */
		.view-all-products-btn {
			display: inline-flex;
			align-items: center;
			padding: 15px 30px;
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			text-decoration: none;
			border-radius: 12px;
			font-size: 1.1rem;
			font-weight: 600;
			transition: all 0.3s ease;
			box-shadow: 0 4px 15px rgba(139, 95, 191, 0.3);
		}

		.view-all-products-btn:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(139, 95, 191, 0.4);
			color: white;
		}

		.view-all-products-btn i {
			margin-right: 8px;
		}

		/* Main Navigation */
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

		/* Hero Section */
		/* Hero Banner Styles */
		/* ——— Layout shell ——— */
		.hero-banner-section {
			/* taller section like your screenshot */
			width: 100%;
			max-width: 100%;
			margin: 0 auto;
			display: flex;
			justify-content: space-between;
			gap: 20px;
			padding: 0 30px;
			padding: 24px 0;
			background: #ffffff;
		}

		.hero-grid {
			display: grid;
			grid-template-columns: 2fr 1fr;
			/* big left + narrow right */
			gap: 28px;
			/* spacing between cards */
			align-items: stretch;
			min-height: 560px;
			/* height close to screenshot */
		}

		/* ——— Main (left) banner ——— */
		.main-banner {
			display: grid;
			grid-template-columns: 1.15fr 1fr;
			/* copy left, image right */
			gap: 24px;
			padding: 48px;
			border-radius: 14px;
			overflow: hidden;
			position: relative;
		}

		.main-banner.coral {
			/* coral/red like your image */
			padding-left: 0;
			background: #ff5b57;
			/* tweak to #ff5a54 if you prefer */
			color: #fff;
		}

		.banner-copy {
			display: grid;
			align-content: center;
			gap: 22px;
		}

		.banner-title {
			font-size: clamp(32px, 5vw, 48px);
			/* big multi-line headline */
			font-weight: 600;
			line-height: 1.08;
			color: #fff;
			margin: 0;
		}

		.banner-price {
			font-size: clamp(18px, 2vw, 28px);
			color: #fff;
			margin: 0 0 8px;
		}

		.banner-price .price {
			font-weight: 800;
			font-size: 1.2em;
		}

		.btn-primary {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			height: 56px;
			padding: 0 28px;
			background: #2252d1;
			/* your brand blue */
			color: #fff;
			border-radius: 10px;
			font-weight: 700;
			letter-spacing: .2px;
			text-decoration: none;
		}

		.banner-media {
			display: flex;
			align-items: end;
			justify-content: center;
		}

		.banner-media img {
			width: 100%;
			height: 100%;
			object-fit: contain;
			/* keep proportions */
			transform: translateY(8px);
			/* slight drop like screenshot */
		}

		/* ——— Right column (two stacked cards) ——— */
		.side-banners {
			display: grid;
			grid-template-rows: 1fr 1fr;
			gap: 28px;
		}

		/* SERVICES STRIP */
		.services-strip {
			background: #ecfff0;
			padding: 22px 0;
			margin-top: 18px;
			border-radius: 10px
		}

		.service-item {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			font-weight: 600;
			color: #004a1f
		}

		/* POPULAR CATEGORIES */
		.popular-categories {
			padding: 80px 0;
			background: var(--light-bg)
		}

		.section-title {
			font-weight: 800;
			font-size: 2rem;
			margin-bottom: 8px
		}

		.section-sub {
			color: var(--muted);
			margin-bottom: 36px
		}

		.category-card {
			background: #fff;
			border-radius: var(--card-radius);
			padding: 34px 18px;
			text-align: center;
			box-shadow: var(--shadow-1);
			transition: .25s;
			height: 100%;
			cursor: pointer;
		}

		.category-card:hover {
			transform: translateY(-6px);
			box-shadow: var(--shadow-2)
		}

		.category-icon {
			width: 100%;
			height: 140px;
			border-radius: 25px;
			overflow: hidden;
			margin-bottom: 14px
		}

		.category-icon img {
			width: 100%;
			height: 100%;
			object-fit: cover
		}

		.category-card h4 {
			font-size: 1.1rem;
			font-weight: 700;
			margin-bottom: 6px
		}

		.category-card p {
			color: #555;
			font-size: .95rem;
			margin: 0
		}

		.price {
			color: var(--brand-blue);
			font-weight: 800
		}

		/* DEALS OF THE WEEK — Special offers section */
		.deals-section {
			background: linear-gradient(135deg, #f8f9ff 0%, #e8efff 100%);
			padding: 80px 0;
			position: relative;
		}

		.deals-container {
			max-width: 1400px;
			margin: 0 auto;
			padding: 40px;
			background: white;
			border-radius: 20px;
			border: 2px solid #e5e7eb;
			box-shadow: 0 10px 50px rgba(0, 0, 0, 0.1);
		}

		.deals-title {
			color: #1f2937;
			font-size: 2.5rem;
			font-weight: 800;
			margin-bottom: 40px;
			text-align: left;
		}

		.deals-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 40px;
			align-items: center;
		}

		.deal-card {
			background: white;
			border-radius: 16px;
			padding: 30px;
			position: relative;
			transition: transform 0.3s ease;
		}

		.deal-card:hover {
			transform: translateY(-5px);
		}

		.deal-discount {
			position: absolute;
			top: -10px;
			left: 20px;
			background: #ef4444;
			color: white;
			padding: 8px 16px;
			border-radius: 20px;
			font-weight: 700;
			font-size: 0.9rem;
			z-index: 10;
		}

		.deal-image {
			width: 100%;
			height: 200px;
			object-fit: contain;
			margin-bottom: 20px;
			border-radius: 12px;
			background: #f8f9fa;
			padding: 20px;
		}

		.deal-brand {
			color: #6b7280;
			font-size: 0.9rem;
			font-weight: 600;
			margin-bottom: 8px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.deal-title {
			color: #1f2937;
			font-size: 1.2rem;
			font-weight: 700;
			margin-bottom: 12px;
			line-height: 1.3;
		}

		.deal-rating {
			display: flex;
			align-items: center;
			margin-bottom: 15px;
		}

		.deal-rating .stars {
			color: #fbbf24;
			margin-right: 8px;
		}

		.deal-pricing {
			margin-bottom: 20px;
		}

		.deal-original-price {
			color: #9ca3af;
			text-decoration: line-through;
			font-size: 1rem;
			margin-right: 8px;
		}

		.deal-current-price {
			color: #4f46e5;
			font-size: 1.5rem;
			font-weight: 800;
		}

		.countdown-timer {
			background: #f3f4f6;
			border-radius: 12px;
			padding: 15px;
			margin-bottom: 20px;
			text-align: center;
		}

		.countdown-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 10px;
			margin-bottom: 5px;
		}

		.countdown-item {
			text-align: center;
		}

		.countdown-number {
			display: block;
			font-size: 1.5rem;
			font-weight: 800;
			color: #1f2937;
			line-height: 1;
		}

		.countdown-label {
			display: block;
			font-size: 0.75rem;
			color: #6b7280;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-top: 2px;
		}

		.deal-options-btn {
			width: 100%;
			background: #e5e7eb;
			color: #4b5563;
			border: none;
			padding: 15px;
			border-radius: 12px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.deal-options-btn:hover {
			background: #d1d5db;
			transform: translateY(-2px);
		}

		@media (max-width: 1200px) {
			.deals-grid {
				grid-template-columns: 1fr;
				gap: 30px;
			}
		}

		@media (max-width: 768px) {
			.deals-container {
				margin: 0 20px;
				padding: 30px 20px;
			}

			.deals-title {
				font-size: 2rem;
				text-align: center;
			}

			.countdown-number {
				font-size: 1.2rem;
			}
		}

		/* Brands Section */
		.brands-area {
			background: #f8f9fa;
			padding: 60px 0;
			overflow: hidden;
		}

		.brands-area h2 {
			color: #333;
			margin-bottom: 20px;
			text-align: center;
		}

		.brands-area .section-sub {
			color: #666;
			margin-bottom: 40px;
			text-align: center;
		}

		.brands-container {
			display: flex;
			flex-direction: column;
			gap: 30px;
			overflow: hidden;
		}

		.brand-row {
			display: flex;
			gap: 30px;
			animation: scroll 30s linear infinite;
		}

		.brand-row:nth-child(2) {
			animation-direction: reverse;
			animation-duration: 35s;
		}

		.brand-card {
			background: white;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			padding: 20px;
			min-width: 150px;
			height: 80px;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.3s ease;
			flex-shrink: 0;
		}

		.brand-card:hover {
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
			transform: translateY(-2px);
			border-color: #d1d5db;
		}

		.brand-card img {
			max-width: 80px;
			max-height: 40px;
			object-fit: contain;
			filter: opacity(0.8);
			transition: all 0.3s ease;
		}

		.brand-card:hover img {
			filter: opacity(1);
			transform: scale(1.1);
		}

		@keyframes scroll {
			from {
				transform: translateX(0);
			}

			to {
				transform: translateX(-100%);
			}
		}

		/* Pause animation on hover */
		.brands-container:hover .brand-row {
			animation-play-state: paused;
		}




		/* TESTIMONIALS — Card Stack */
		.testimonials {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
			padding: 120px 0;
			min-height: 80vh;
			position: relative;
			overflow: hidden;
		}

		.testimonials::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
			opacity: 0.3;
		}

		.testimonials .section-title {
			color: #fff;
			font-size: 4.5rem;
			font-weight: 900;
			margin-bottom: 20px;
			text-align: left;
			font-family: 'Inter', sans-serif;
			letter-spacing: -0.02em;
			line-height: 1.1;
		}

		.testimonials .section-sub {
			color: rgba(255, 255, 255, 0.9);
			font-size: 1.8rem;
			font-weight: 400;
			margin-bottom: 80px;
			text-align: left;
			font-family: 'Inter', sans-serif;
			letter-spacing: -0.01em;
		}

		.card-swap-container {
			position: absolute;
			top: 50%;
			right: 10%;
			transform: translateY(-50%);
			transform-origin: center;
			perspective: 900px;
			overflow: visible;
			width: 550px;
			height: 450px;
		}

		.testimonial-card {
			position: absolute;
			top: 50%;
			left: 50%;
			border-radius: 20px;
			border: 1px solid rgba(255, 255, 255, 0.2);
			background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.08));
			backdrop-filter: blur(20px);
			transform-style: preserve-3d;
			will-change: transform;
			backface-visibility: hidden;
			-webkit-backface-visibility: hidden;
			width: 550px;
			height: 450px;
			padding: 50px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			color: #fff;
			box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
			cursor: pointer;
			transition: transform 0.3s ease;
		}

		.testimonial-card:hover {
			transform: translateZ(20px);
		}

		.card-features {
			position: absolute;
			top: 20px;
			right: 20px;
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.card-feature {
			background: rgba(0, 0, 0, 0.8);
			border: 1px solid rgba(255, 255, 255, 0.2);
			border-radius: 25px;
			padding: 12px 20px;
			font-size: 0.9rem;
			color: #fff;
			display: flex;
			align-items: center;
			gap: 10px;
			font-weight: 500;
			backdrop-filter: blur(10px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
		}

		.card-feature i {
			font-size: 1rem;
			opacity: 0.9;
		}

		.testimonial-content {
			flex: 1;
			display: flex;
			flex-direction: column;
			justify-content: center;
		}

		.testimonial-quote {
			font-size: 1.6rem;
			line-height: 1.5;
			margin-bottom: 40px;
			font-style: italic;
			color: #fff;
			font-weight: 400;
		}

		.star-rating {
			display: flex;
			gap: 4px;
			margin-bottom: 30px;
		}

		.star {
			color: #ffd700;
			font-size: 1.5rem;
		}

		.testimonial-author {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.author-info h4 {
			margin: 0;
			font-size: 1.4rem;
			color: #fff;
			font-weight: 600;
		}

		.author-info p {
			margin: 0;
			color: rgba(255, 255, 255, 0.8);
			font-size: 1.1rem;
			font-weight: 400;
		}

		.testimonials-text-section {
			max-width: 50%;
			z-index: 2;
			position: relative;
		}

		@media (max-width: 1200px) {
			.card-swap-container {
				right: 5%;
				transform: translateY(-50%) scale(0.85);
			}

			.testimonials-text-section {
				max-width: 55%;
			}

			.testimonials .section-title {
				font-size: 3.8rem;
			}

			.testimonials .section-sub {
				font-size: 1.5rem;
			}
		}

		@media (max-width: 768px) {
			.testimonials {
				padding: 80px 0;
			}

			.card-swap-container {
				position: relative;
				right: auto;
				top: auto;
				transform: scale(0.7);
				margin: 40px auto 0;
			}

			.testimonials-text-section {
				max-width: 100%;
				text-align: center;
				margin-bottom: 40px;
			}

			.testimonials .section-title {
				text-align: center;
				font-size: 3rem;
			}

			.testimonials .section-sub {
				text-align: center;
				font-size: 1.3rem;
			}
		}

		@media (max-width: 480px) {
			.card-swap-container {
				transform: scale(0.5);
			}

			.testimonial-card {
				padding: 40px;
			}

			.testimonials .section-title {
				font-size: 2.5rem;
			}

			.testimonials .section-sub {
				font-size: 1.1rem;
			}
		}

		/* Language Confirmation Modal */
		.language-modal-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.7);
			display: flex;
			justify-content: center;
			align-items: center;
			z-index: 10000;
			animation: fadeIn 0.3s ease;
		}

		.language-modal {
			background: #fff;
			border-radius: 16px;
			padding: 0;
			max-width: 450px;
			width: 90%;
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
			animation: slideUp 0.3s ease;
			overflow: hidden;
		}

		.language-modal-header {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: #fff;
			padding: 20px 25px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.language-modal-header h3 {
			margin: 0;
			font-size: 1.4rem;
			font-weight: 600;
		}

		.modal-close {
			background: none;
			border: none;
			color: #fff;
			font-size: 1.8rem;
			cursor: pointer;
			padding: 0;
			width: 30px;
			height: 30px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 50%;
			transition: background 0.2s ease;
		}

		.modal-close:hover {
			background: rgba(255, 255, 255, 0.2);
		}

		.language-modal-body {
			padding: 30px 25px;
			text-align: center;
		}

		.language-modal-icon {
			font-size: 3rem;
			color: #008060;
			margin-bottom: 20px;
		}

		.language-modal-body p {
			margin: 0 0 15px 0;
			color: #333;
			font-size: 1.1rem;
			line-height: 1.5;
		}

		.language-modal-note {
			color: #666 !important;
			font-size: 0.95rem !important;
			margin-bottom: 0 !important;
		}

		.language-modal-footer {
			padding: 20px 25px 25px;
			display: flex;
			gap: 15px;
			justify-content: flex-end;
		}

		.language-btn-cancel,
		.language-btn-confirm {
			padding: 12px 24px;
			border: none;
			border-radius: 8px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.language-btn-cancel {
			background: #f1f5f9;
			color: #64748b;
		}

		.language-btn-cancel:hover {
			background: #e2e8f0;
		}

		.language-btn-confirm {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: #fff;
		}

		.language-btn-confirm:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			transform: translateY(-1px);
		}

		@keyframes fadeIn {
			from {
				opacity: 0;
			}

			to {
				opacity: 1;
			}
		}

		@keyframes slideUp {
			from {
				transform: translateY(30px);
				opacity: 0;
			}

			to {
				transform: translateY(0);
				opacity: 1;
			}
		}

		.orbit-wrap {
			position: relative;
			width: 420px;
			height: 420px;
			margin: 0 auto
		}

		.orbit-center {
			position: absolute;
			inset: 0;
			margin: auto;
			width: 220px;
			height: 220px;
			border-radius: 18px;
			background: #f8f9fc;
			box-shadow: 0 3px 12px rgba(0, 0, 0, .08);
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 18px;
			text-align: center
		}

		.orbit-center p {
			margin: 0;
			font-size: .98rem;
			color: #333
		}

		.orbit {
			position: absolute;
			inset: 0;
			border-radius: 50%;
			animation: spin 24s linear infinite
		}

		@keyframes spin {
			from {
				transform: rotate(0)
			}

			to {
				transform: rotate(360deg)
			}
		}

		.avatar {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			width: 70px;
			height: 70px;
			border-radius: 50%;
			overflow: hidden;
			border: 3px solid #fff;
			box-shadow: 0 6px 16px rgba(0, 0, 0, .18);
			cursor: pointer;
			transition: transform .25s
		}

		.avatar img {
			width: 100%;
			height: 100%;
			object-fit: cover
		}

		.avatar:hover {
			transform: translate(-50%, -50%) scale(1.08)
		}

		/* positions (degrees) */
		.a1 {
			transform: translate(-50%, -50%) rotate(0deg) translate(180px) rotate(0deg)
		}

		.a2 {
			transform: translate(-50%, -50%) rotate(60deg) translate(180px) rotate(-60deg)
		}

		.a3 {
			transform: translate(-50%, -50%) rotate(120deg) translate(180px) rotate(-120deg)
		}

		.a4 {
			transform: translate(-50%, -50%) rotate(180deg) translate(180px) rotate(-180deg)
		}

		.a5 {
			transform: translate(-50%, -50%) rotate(240deg) translate(180px) rotate(-240deg)
		}

		.a6 {
			transform: translate(-50%, -50%) rotate(300deg) translate(180px) rotate(-300deg)
		}

		.orbit:hover {
			animation-play-state: paused
		}

		.side-card {
			border-radius: 14px;
			padding: 36px 28px;
			display: grid;
			grid-template-columns: 1fr auto;
			/* copy left, small image right */
			align-items: center;
			gap: 24px;
			overflow: hidden;
		}

		/* colors like the screenshot */
		.side-card.yellow {
			background: #ffd21f;
			/* rich yellow */
			color: #111;
		}

		.side-card.purple {
			background: #6f45d8;
			/* vibrant purple */
			color: #fff;
		}

		/* texts on side cards */
		.side-title {
			font-size: clamp(22px, 2.4vw, 34px);
			font-weight: 800;
			line-height: 1.15;
			margin: 0 0 10px;
		}

		.side-price {
			margin: 0 0 14px;
			font-weight: 600;
		}

		.side-price .price {
			font-weight: 800;
			font-size: 1.2em;
		}

		.side-link {
			font-weight: 800;
			text-decoration: underline;
			color: inherit;
			/* black on yellow, white on purple */
		}

		.side-media {
			width: 148px;
			/* small thumbnail on right */
			height: 148px;
			border-radius: 12px;
			overflow: hidden;
		}

		.side-media img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		/* ——— Responsiveness ——— */
		@media (max-width: 992px) {
			.hero-grid {
				grid-template-columns: 1fr;
				min-height: auto;
			}

			.side-banners {
				grid-template-rows: none;
				grid-template-columns: 1fr 1fr;
			}
		}

		@media (max-width: 640px) {
			.main-banner {
				grid-template-columns: 1fr;
				/* stack copy over image */
				padding: 28px;
			}

			.banner-media {
				order: -1;
			}

			/* image first on mobile (optional) */
			.side-banners {
				grid-template-columns: 1fr;
			}

			.side-media {
				width: 112px;
				height: 112px;
			}

			.orbit-wrap {
				width: 320px;
				height: 320px
			}

			.orbit-center {
				width: 200px;
				height: 200px
			}

			.a1,
			.a2,
			.a3,
			.a4,
			.a5,
			.a6 {
				transform: translate(-50%, -50%) rotate(var(--r, 0)) translate(135px) rotate(calc(var(--r, 0) * -1))
			}
		}

		/* Features Section */
		.features-section {
			background: #f0f8f0;
			padding: 40px 0;
		}

		.features-bar {
			background: linear-gradient(135deg, #008060, #006b4e);
			padding: 40px 0;
		}

		.features-bar .feature-item {
			display: flex;
			align-items: center;
			gap: 15px;
			margin-top: 0;
		}

		.features-bar .feature-icon {
			width: 50px;
			height: 50px;
			background: rgba(255, 255, 255, 0.2);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #ffffff;
			font-size: 1.2rem;
		}

		.features-bar .feature-content h5 {
			font-size: 1rem;
			font-weight: 600;
			color: #ffffff;
			margin-bottom: 5px;
		}

		.features-bar .feature-content p {
			color: rgba(255, 255, 255, 0.8);
			font-size: 0.9rem;
			margin: 0;
		}

		.feature-item {
			margin-top: 30px;
			display: flex;
			align-items: center;
			gap: 15px;
		}

		.feature-icon {
			width: 50px;
			height: 50px;
			background: #ffffff;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #008060;
			font-size: 1.2rem;
		}

		.feature-text h4 {
			font-size: 1rem;
			font-weight: 600;
			color: #1f2937;
			margin-bottom: 5px;
		}

		.feature-text p {
			color: #6b7280;
			font-size: 0.9rem;
			margin: 0;
		}

		/* Removed unused slideshow styles */

		/* Featured Collections Styles */
		.featured-collection {
			padding: 80px 0;
			background: #f8f9fa;
		}

		.section-header {
			margin-bottom: 50px;
		}

		.section-title {
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			margin-bottom: 16px;
		}

		.section-subtitle {
			font-size: 1.1rem;
			color: #6b7280;
			max-width: 600px;
			margin: 0 auto;
		}

		.collection-card {
			background: white;
			border-radius: 12px;
			overflow: hidden;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
			transition: all 0.3s ease;
			height: 100%;
		}

		.collection-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
		}

		.collection-image {
			position: relative;
			overflow: hidden;
		}

		.collection-image img {
			width: 100%;
			height: 250px;
			object-fit: cover;
			transition: transform 0.3s ease;
		}

		.collection-card:hover .collection-image img {
			transform: scale(1.05);
		}

		.collection-content {
			padding: 24px;
		}

		.collection-title {
			font-size: 1.5rem;
			font-weight: 600;
			color: #1f2937;
			margin-bottom: 12px;
		}

		.collection-description {
			color: #6b7280;
			margin-bottom: 20px;
			line-height: 1.6;
		}

		.collection-link {
			color: #008060;
			font-weight: 600;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			gap: 8px;
			transition: color 0.3s ease;
		}

		.collection-link:hover {
			color: #006b4e;
		}

		/* Special Product Section */
		.special-product {
			padding: 80px 0;
			background: white;
		}

		.special-content {
			padding: 0 20px;
		}

		.special-title {
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			margin-bottom: 20px;
		}

		.special-description {
			font-size: 1.2rem;
			color: #6b7280;
			margin-bottom: 30px;
			line-height: 1.6;
		}

		.btn-outline-primary {
			background: transparent;
			color: #008060;
			border: 2px solid #008060;
			padding: 12px 24px;
			border-radius: 8px;
			font-weight: 600;
			text-decoration: none;
			display: inline-block;
			transition: all 0.3s ease;
		}

		.btn-outline-primary:hover {
			background: #008060;
			color: white;
		}

		.special-image img {
			width: 100%;
			height: auto;
			border-radius: 12px;
		}


		/* Most Popular Categories - Grid Layout */
		.popular-categories {
			padding: 60px 0;
			background: white;
		}

		.popular-categories .section-title {
			color: #1a1a1a;
			font-size: 2rem;
			font-weight: 700;
			margin-bottom: 40px;
			text-align: left;
		}

		.category-grid-container {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
			max-width: 1400px;
			margin: 0 auto;
		}

		.category-large {
			grid-row: span 2;
			position: relative;
			border-radius: 12px;
			overflow: hidden;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.category-large:hover {
			transform: translateY(-5px);
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
		}

		.category-large img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.category-large-overlay {
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
			padding: 30px 20px 20px;
			color: white;
		}

		.category-large-overlay h4 {
			font-size: 1.5rem;
			font-weight: 700;
			margin: 0;
			text-align: center;
		}

		.category-grid-right {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
		}

		.category-small {
			position: relative;
			border-radius: 12px;
			overflow: hidden;
			cursor: pointer;
			transition: all 0.3s ease;
			aspect-ratio: 1;
		}

		.category-small:hover {
			transform: translateY(-5px);
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
		}

		.category-small img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.category-small-overlay {
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
			padding: 20px;
			color: white;
		}

		.category-small-overlay h4 {
			font-size: 1.1rem;
			font-weight: 700;
			margin: 0;
			text-align: center;
		}

		.category-small-overlay p {
			font-size: 0.9rem;
			margin: 5px 0 0;
			text-align: center;
			opacity: 0.9;
		}

		@media (max-width: 768px) {
			.category-grid-container {
				grid-template-columns: 1fr;
			}

			.category-large {
				grid-row: span 1;
				aspect-ratio: 4/3;
			}

			.category-grid-right {
				grid-template-columns: 1fr;
			}

			.popular-categories .section-title {
				text-align: center;
			}
		}

		.view-all-link {
			color: #4f63d2;
			text-decoration: none;
			font-weight: 600;
		}

		/* Smart Band Promo */
		/* Smart Band Promo */
		.smart-band-promo {
			padding: 40px 0;
			background: white;
		}

		/* main banner container */
		.promo-banner {
			display: flex;
			align-items: center;
			max-height: 270px;
			justify-content: space-between;
			border-radius: 15px;
			padding: 30px;
			color: white;
			position: relative;
			overflow: hidden;
			text-align: left;
			/* switched from center */
			gap: 30px;
			background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
		}

		/* text content */
		.promo-content {
			flex: 1;
			min-width: 260px;
			z-index: 1;
		}

		.promo-banner h2 {
			font-size: 3rem;
			font-weight: 700;
			margin-bottom: 10px;
			line-height: 1.2;
		}

		.promo-banner p {
			font-size: 1.5rem;
			margin-bottom: 25px;
			opacity: 0.9;
			max-width: 42ch;
		}

		/* call-to-action button */
		.cta-btn {
			display: inline-block;
			background: #fff;
			color: #6a5af9;
			padding: 12px 22px;
			border-radius: 10px;
			font-weight: 600;
			text-decoration: none;
			transition: all 0.3s ease;
		}

		.cta-btn:hover {
			background: #eceaff;
			transform: translateY(-2px);
		}

		/* hero image column */
		.promo-visual {
			flex: 1;
			display: flex;
			justify-content: flex-end;
		}

		.promo-visual img {
			width: 100%;
			max-width: 520px;
			height: auto;
			border-radius: 15px;
			object-fit: cover;
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
		}

		/* --- older promo-images (small icons) --- */
		/* you can keep this for other banners if needed */
		.promo-images {
			display: flex;
			justify-content: center;
			gap: 20px;
			flex-wrap: wrap;
		}

		.promo-images img {
			width: 80px;
			height: 80px;
			border-radius: 12px;
			border: 3px solid rgba(255, 255, 255, 0.3);
		}

		/* responsive tweaks */
		@media (max-width: 768px) {
			.promo-banner {
				flex-direction: column;
				text-align: center;
			}

			.promo-content {
				flex: none;
			}

			.promo-visual {
				justify-content: center;
				margin-top: 20px;
			}

			.promo-banner p {
				margin-bottom: 20px;
			}
		}





		/* Main Semi-Circle Design (like login page) */
		.hero-circle {
			position: absolute;
			right: -450px;
			top: 50%;
			transform: translateY(-50%);
			width: 1200px;
			height: 1200px;
			background: linear-gradient(135deg, #008060, #006b4e);
			border-radius: 50%;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			text-align: center;
			color: white;
			z-index: 1;
		}

		.hero-circle-content {
			position: relative;
			z-index: 2;
			left: -15%;
		}

		.hero-circle-title {
			font-size: 4.5rem;
			font-weight: 700;
			margin-bottom: 20px;
			line-height: 0.9;
		}

		.hero-circle-subtitle {
			font-size: 1.6rem;
			opacity: 0.9;
			max-width: 400px;
			line-height: 1.5;
			margin-bottom: 30px;
		}

		.hero-circle-btn {
			background: rgba(255, 255, 255, 0.2);
			color: white;
			border: 2px solid rgba(255, 255, 255, 0.3);
			padding: 16px 32px;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 500;
			transition: all 0.3s ease;
			display: inline-flex;
			align-items: center;
			gap: 12px;
			font-size: 1.1rem;
		}

		.hero-circle-btn:hover {
			background: rgba(255, 255, 255, 0.3);
			transform: translateY(-2px);
			color: white;
		}

		/* Main Content Container */
		.hero-main-content {
			position: relative;
			z-index: 10;
			max-width: 50%;
		}

		@keyframes float {

			0%,
			100% {
				transform: translateY(0px) rotate(0deg);
			}

			50% {
				transform: translateY(-20px) rotate(5deg);
			}
		}

		.hero-content {
			position: relative;
			z-index: 2;
		}

		.hero-title {
			font-size: 3.5rem;
			font-weight: 700;
			color: #1a202c;
			margin-bottom: 16px;
			line-height: 1.2;
		}

		.hero-highlight {
			background: linear-gradient(135deg, #008060, #006b4e);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
		}

		.hero-subtitle {
			font-size: 1.25rem;
			color: #4b5563;
			margin-bottom: 24px;
			font-weight: 400;
		}

		.hero-features {
			display: flex;
			gap: 24px;
			margin-bottom: 32px;
			flex-wrap: wrap;
		}

		.feature-item {
			display: flex;
			align-items: center;
			gap: 8px;
			color: #374151;
			font-weight: 500;
		}

		.feature-icon {
			color: #008060;
			font-size: 1.1rem;
		}

		.cta-buttons {
			display: flex;
			gap: 16px;
			flex-wrap: wrap;
		}

		.cta-primary {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			padding: 14px 28px;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 600;
			transition: all 0.3s ease;
			border: none;
			cursor: pointer;
		}

		.cta-primary:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(139, 95, 191, 0.3);
			color: white;
		}

		.cta-secondary {
			background: white;
			color: #008060;
			padding: 14px 28px;
			border: 2px solid #008060;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 600;
			transition: all 0.3s ease;
		}

		.cta-secondary:hover {
			background: #008060;
			color: white;
			transform: translateY(-2px);
		}

		/* Promotion Cards */
		.promo-cards {
			display: flex;
			gap: 20px;
			margin-top: 50px;
		}

		.promo-card {
			flex: 1;
			padding: 32px;
			border-radius: 20px;
			position: relative;
			overflow: hidden;
			transition: all 0.3s ease;
			cursor: pointer;
			min-height: 280px;
		}

		.promo-card:hover {
			transform: translateY(-4px);
			box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
		}

		.promo-card.tech-blue {
			background: linear-gradient(135deg, #f8fafc, #e2e8f0);
			color: #1f2937;
			border: 1px solid #e5e7eb;
		}

		.promo-card.white {
			background: white;
			border: 2px solid #e5e7eb;
			color: #1f2937;
		}

		.promo-badge {
			background: linear-gradient(135deg, #006b4e, #008060);
			color: white;
			padding: 6px 16px;
			border-radius: 16px;
			font-size: 1rem;
			font-weight: 400;
			margin-bottom: 20px;
			display: inline-block;
		}

		.promo-title {
			font-size: 1.8rem;
			font-weight: 700;
			margin-bottom: 12px;
		}

		.promo-subtitle {
			font-size: 1.1rem;
			margin-bottom: 20px;
			opacity: 0.8;
		}

		.promo-btn {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			padding: 12px 20px;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 500;
			font-size: 1rem;
			transition: all 0.3s ease;
		}

		.promo-btn:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			color: white;
			transform: scale(1.05);
		}

		/* Admin Panel Styles - Made bigger with purple theme */
		.admin-panel {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			padding: 40px;
			border-radius: 24px;
			margin: 60px 0;
			text-align: center;
			min-height: 250px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
		}

		.admin-panel h3 {
			margin-bottom: 20px;
			font-weight: 700;
			font-size: 2.2rem;
		}

		.admin-panel p {
			margin-bottom: 30px;
			opacity: 0.9;
			font-size: 1.8rem;
			font-weight: 600;
			line-height: 1.4;
			font-family: 'Dancing Script', 'Brush Script MT', 'Lucida Handwriting', cursive;
		}

		.admin-btn {
			background: white;
			color: #008060;
			padding: 16px 32px;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 600;
			font-size: 1.1rem;
			transition: all 0.3s ease;
		}

		.admin-btn:hover {
			background: rgba(255, 255, 255, 0.9);
			color: #006b4e;
			transform: translateY(-2px);
		}

		/* Mobile Responsiveness - Maintaining desktop layout proportions */
		@media (max-width: 768px) {
			.main-header {
				padding: 10px 0;
			}

			.main-nav {
				padding: 8px 0;
			}

			.nav-menu {
				gap: 20px;
				overflow-x: auto;
				scrollbar-width: none;
				-ms-overflow-style: none;
			}

			.nav-menu::-webkit-scrollbar {
				display: none;
			}

			.mega-dropdown {
				width: 350px;
				padding: 20px;
			}

			.dropdown-content {
				grid-template-columns: repeat(2, 1fr);
				gap: 20px;
			}

			.dropdown-column.featured {
				grid-column: span 2;
				border-left: none;
				border-top: 2px solid #f3f4f6;
				padding-left: 0;
				padding-top: 20px;
				margin-top: 20px;
			}

			.header-container {
				flex-wrap: wrap;
				gap: 12px;
			}

			.search-container {
				flex: 1;
				min-width: 300px;
			}

			.header-actions {
				gap: 10px;
			}

			.logo {
				font-size: 1.4rem;
			}

			.logo .garage {
				font-size: 0.85rem;
			}

			.search-input {
				padding: 10px 16px 10px 45px;
				font-size: 0.95rem;
			}

			.search-btn {
				padding: 6px 14px;
			}

			.category-nav {
				padding: 10px 0;
			}

			.category-item {
				font-size: 0.85rem;
				padding: 6px 12px;
			}

			.hero-section {
				padding: 50px 0;
				min-height: 85vh;
			}

			.hero-title {
				font-size: 2.8rem;
			}

			.hero-subtitle {
				font-size: 1.15rem;
			}

			.hero-features {
				gap: 20px;
			}

			.cta-primary,
			.cta-secondary {
				padding: 12px 24px;
				font-size: 1rem;
			}

			.promo-cards {
				gap: 15px;
				margin-top: 35px;
			}

			.promo-card {
				padding: 28px;
				min-height: 240px;
			}

			.admin-panel {
				padding: 35px 25px;
				margin: 50px 0;
			}

			.admin-panel h3 {
				font-size: 2rem;
			}

			.admin-panel p {
				font-size: 1.6rem;
			}

			.container {
				padding-left: 15px;
				padding-right: 15px;
			}
		}

		@media (max-width: 480px) {
			.main-header {
				padding: 8px 0;
			}

			.header-container {
				flex-direction: column;
				gap: 15px;
				align-items: stretch;
			}

			.logo {
				font-size: 1.4rem;
				justify-content: center;
				margin-right: 0;
			}

			.logo img {
				height: 25px !important;
			}

			.tech-revival-text {
				font-size: 1rem;
			}

			.tech-revival-icon {
				font-size: 1.1rem;
			}

			.header-icon i {
				font-size: 1rem;
			}

			.user-avatar {
				width: 42px;
				height: 42px;
				font-size: 1.1rem;
			}

			.search-container {
				order: 2;
				width: 200%;
				min-width: auto;
				margin-left: 0;
			}

			.tech-revival-section {
				order: 3;
				justify-content: center;
				padding: 10px;
				background: #f8f9fa;
				border-radius: 8px;
			}

			.user-actions {
				order: 4;
				justify-content: center;
				gap: 20px;
			}

			.vertical-separator {
				display: none;
			}

			.search-input {
				padding: 9px 14px 9px 40px;
				font-size: 0.3rem;
			}

			.search-btn {
				padding: 5px 12px;
				font-size: 0.5rem;
			}

			.category-item {
				font-size: 0.5rem;
				padding: 5px 10px;
			}

			.hero-section {
				padding: 40px 0;
				min-height: 80vh;
			}

			.hero-title {
				font-size: 2.2rem;
				text-align: center;
			}

			.hero-subtitle {
				font-size: 1rem;
				text-align: center;
			}

			.hero-features {
				justify-content: center;
				gap: 15px;
			}

			.cta-buttons {
				justify-content: center;
				gap: 10px;
			}

			.cta-primary,
			.cta-secondary {
				padding: 10px 20px;
				font-size: 0.9rem;
			}

			.promo-cards {
				flex-direction: column;
				gap: 12px;
				margin-top: 25px;
			}

			.promo-card {
				padding: 22px;
				min-height: 200px;
			}

			.promo-title {
				font-size: 1.4rem;
			}

			.admin-panel {
				padding: 25px 20px;
				margin: 35px 0;
			}

			.admin-panel h3 {
				font-size: 1.7rem;
			}

			.admin-panel p {
				font-size: 1.3rem;
			}

			.admin-btn {
				padding: 12px 28px;
				font-size: 1rem;
			}

			.header-icon {
				padding: 6px;
			}

			.login-btn {
				padding: 8px 16px;
				font-size: 0.9rem;
			}

			.logout-btn {
				padding: 6px 12px;
				font-size: 0.8rem;
			}

			.container {
				padding-left: 12px;
				padding-right: 12px;
			}
		}

		@media (max-width: 375px) {
			.logo {
				font-size: 1.1rem;
			}

			.search-input {
				padding: 8px 12px 8px 35px;
				font-size: 0.85rem;
			}

			.hero-title {
				font-size: 1.9rem;
			}

			.hero-subtitle {
				font-size: 0.95rem;
			}

			.category-item {
				font-size: 0.75rem;
				padding: 4px 8px;
			}

			.cta-primary,
			.cta-secondary {
				padding: 9px 18px;
				font-size: 0.85rem;
			}

			.promo-card {
				padding: 18px;
				min-height: 180px;
			}

			.promo-title {
				font-size: 1.2rem;
			}

			.admin-panel h3 {
				font-size: 1.4rem;
			}

			.admin-panel p {
				font-size: 1.1rem;
			}

			.admin-btn {
				padding: 10px 24px;
				font-size: 0.9rem;
			}

			.header-actions {
				gap: 8px;
			}

			.container {
				padding-left: 10px;
				padding-right: 10px;
			}
		}

		/* Footer Styles */
		.main-footer {
			background: #ffffff;
			border-top: 1px solid #e5e7eb;
			padding: 60px 0 20px;
			margin-top: 80px;
		}

		.footer-logo {
			font-size: 1.8rem;
			font-weight: 700;
			color: #1f2937;
			margin-bottom: 16px;
		}

		.footer-logo .garage {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			padding: 4px 8px;
			border-radius: 6px;
			font-size: 1rem;
			font-weight: 600;
		}

		.footer-description {
			color: #6b7280;
			font-size: 0.95rem;
			margin-bottom: 24px;
			line-height: 1.6;
		}

		.social-links {
			display: flex;
			gap: 12px;
		}

		.social-link {
			width: 40px;
			height: 40px;
			background: #f3f4f6;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #6b7280;
			text-decoration: none;
			transition: all 0.3s ease;
		}

		.social-link:hover {
			background: #008060;
			color: white;
			transform: translateY(-2px);
		}

		.footer-title {
			font-size: 1.1rem;
			font-weight: 600;
			color: #1f2937;
			margin-bottom: 20px;
		}

		.footer-links {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.footer-links li {
			margin-bottom: 12px;
		}

		.footer-links li a {
			color: #6b7280;
			text-decoration: none;
			font-size: 0.9rem;
			transition: all 0.3s ease;
		}

		.footer-links li a:hover {
			color: #008060;
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
			font-size: 0.9rem;
			margin: 0;
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
			display: none;
		}

		.chat-trigger {
			width: 60px;
			height: 60px;
			background: #008060;
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
			background: #008060;
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
			border-color: #008060;
		}

		.chat-send {
			width: 40px;
			height: 40px;
			background: #008060;
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

		/* Newsletter Popup Styles */
		.newsletter-popup {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			z-index: 9999;
			display: none;
		}

		.newsletter-popup.show {
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.newsletter-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.5);
			backdrop-filter: blur(4px);
		}

		.newsletter-modal {
			background: white;
			border-radius: 20px;
			padding: 40px;
			max-width: 500px;
			width: 90%;
			position: relative;
			z-index: 10000;
			text-align: center;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			animation: newsletterSlideIn 0.4s ease-out;
		}

		@keyframes newsletterSlideIn {
			from {
				opacity: 0;
				transform: scale(0.8) translateY(-20px);
			}

			to {
				opacity: 1;
				transform: scale(1) translateY(0);
			}
		}

		.newsletter-close {
			position: absolute;
			top: 15px;
			right: 15px;
			background: #f3f4f6;
			border: none;
			width: 35px;
			height: 35px;
			border-radius: 50%;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #6b7280;
			transition: all 0.3s ease;
		}

		.newsletter-close:hover {
			background: #e5e7eb;
			color: #374151;
		}

		.newsletter-icon {
			width: 80px;
			height: 80px;
			background: linear-gradient(135deg, #008060, #006b4e);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 20px;
			color: white;
			font-size: 2rem;
		}

		.newsletter-content h3 {
			color: #1f2937;
			font-size: 2rem;
			font-weight: 700;
			margin-bottom: 10px;
		}

		.newsletter-content p {
			color: #6b7280;
			font-size: 1.1rem;
			margin-bottom: 25px;
			line-height: 1.5;
		}

		.newsletter-form {
			display: flex;
			gap: 10px;
			margin-bottom: 20px;
		}

		.newsletter-input {
			flex: 1;
			padding: 15px 20px;
			border: 2px solid #e5e7eb;
			border-radius: 50px;
			font-size: 1rem;
			outline: none;
			transition: border-color 0.3s ease;
		}

		.newsletter-input:focus {
			border-color: #008060;
		}

		.newsletter-btn {
			background: #008060;
			color: white;
			border: none;
			padding: 15px 30px;
			border-radius: 50px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			white-space: nowrap;
		}

		.newsletter-btn:hover {
			background: #374151;
			transform: translateY(-1px);
		}

		.newsletter-disclaimer {
			color: #9ca3af;
			font-size: 0.9rem;
			margin: 0;
		}

		@media (max-width: 768px) {
			.newsletter-modal {
				margin: 20px;
				padding: 30px;
			}

			.newsletter-form {
				flex-direction: column;
			}

			.newsletter-content h3 {
				font-size: 1.7rem;
			}

			.newsletter-content p {
				font-size: 1rem;
			}
		}

		/* Animation Classes */
		.animate-fade-in {
			animation: fadeIn 0.6s ease-out;
		}

		.animate-slide-up {
			animation: slideUp 0.8s ease-out;
		}

		@keyframes fadeIn {
			from {
				opacity: 0;
				transform: translateY(20px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		@keyframes slideUp {
			from {
				opacity: 0;
				transform: translateY(40px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		/* Mobile Responsive Styles for Footer and Chat */
		@media (max-width: 768px) {
			.main-footer {
				padding: 40px 0 20px;
			}

			.footer-logo {
				font-size: 1.5rem;
			}

			.footer-title {
				font-size: 1rem;
			}

			.payment-methods {
				justify-content: center;
				margin-top: 20px;
			}

			.chat-panel {
				width: calc(100vw - 40px);
				height: 400px;
			}

			.live-chat-widget {
				bottom: 15px;
				left: 15px;
			}
		}

		/* Legacy menu tray (hidden) */
		.menu-tray {
			display: none;
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
					<?php if (isset($_SESSION['user_id'])): ?>
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
								<span class="cart-badge" id="cartBadge" style="display: none;">0</span>
							</a>
						</div>

						<!-- User Avatar Dropdown -->
						<div class="user-dropdown">
							<div class="user-avatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>" onclick="toggleUserDropdown()">
								<?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
							</div>
							<div class="dropdown-menu-custom" id="userDropdownMenu">
								<a href="views/account.php" class="dropdown-item-custom">
									<i class="fas fa-user"></i>
									<span data-translate="account">Account</span>
								</a>
								<a href="views/my_orders.php" class="dropdown-item-custom">
									<i class="fas fa-shopping-bag"></i>
									<span data-translate="my_orders">My Orders</span>
								</a>
								<a href="views/notifications.php" class="dropdown-item-custom">
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
						<!-- Register Button -->
						<a href="login/register.php" class="login-btn" style="margin-left: 10px;">
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
									<li><a href="views/all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
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
									<li><a href="views/all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
									<li><a href="views/all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="views/computing.php" style="text-decoration: none; color: inherit;">
										<span data-translate="computing">Computing</span>
									</a>
								</h4>
								<ul>
									<li><a href="views/all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
									<li><a href="views/all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="views/photography_video.php" style="text-decoration: none; color: inherit;">
										<span data-translate="photography_video">Photography & Video</span>
									</a>
								</h4>
								<ul>
									<li><a href="views/all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
									<li><a href="views/all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column featured">
								<h4>Shop All</h4>
								<div class="featured-item">
									<img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
									<div class="featured-text">
										<strong>New Arrivals</strong>
										<p>Latest tech gadgets</p>
										<a href="views/all_product.php" class="shop-now-btn">Shop </a>
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

	<!-- Hero Banner Section (matching demo) -->
	<section class="hero-banner-section">
		<div class="container">
			<div class="hero-grid">
				<!-- LEFT: MAIN BANNER -->
				<article class="main-banner coral">
					<div class="banner-copy">
						<h1 class="banner-title"><span data-translate="ipad_pro_title">Apple IPad Pro 11<br>Ultra Retina XDR<br>Display, 256GB</span></h1>
						<p class="banner-price"><span data-translate="starting_at">Starting At</span> <span class="price">GH₵ 236.00</span></p>
						<a href="views/all_product.php" class="btn-primary"><span data-translate="shop_now">SHOP NOW</span></a>
					</div>

					<div class="banner-media">
						<img
							src="https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?q=80&w=1600&auto=format&fit=crop"
							alt="iPad Pro" />
					</div>
				</article>

				<!-- RIGHT: TWO SIDE CARDS -->
				<div class="side-banners">
					<!-- Top -->
					<article class="side-card yellow">
						<div class="side-copy">
							<h3 class="side-title">T900 Ultra<br>Watch</h3>
							<p class="side-price">Starting <span class="price">GH₵ 19.00</span></p>
							<a href="views/all_product.php" class="side-link"><span data-translate="shop_now">SHOP NOW</span></a>
						</div>
						<div class="side-media">
							<img
								src="https://images.unsplash.com/photo-1603791452906-bcce5e6d47a5?q=80&w=1200&auto=format&fit=crop"
								alt="Watch" />
						</div>
					</article>

					<!-- Bottom -->
					<article class="side-card purple">
						<div class="side-copy">
							<h3 class="side-title">Kids Wireless<br>Headphones</h3>
							<p class="side-price">Starting <span class="price">GH₵ 36.00</span></p>
							<a href="views/all_product.php" class="side-link"><span data-translate="shop_now">SHOP NOW</span></a>
						</div>
						<div class="side-media">
							<img
								src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1200&auto=format&fit=crop"
								alt="Headphones" />
						</div>
					</article>
				</div>
			</div>
		</div>

	</section>

	<!-- Features Section -->
	<section class="features-bar">
		<div class="container-fluid">
			<div class="row g-4">
				<div class="col-lg-3 col-md-6">
					<div class="feature-item">
						<div class="feature-icon">
							<i class="fas fa-shipping-fast"></i>
						</div>
						<div class="feature-content">
							<h5>Free Shipping</h5>
							<p>Free shipping all order over GH₵ 2000</p>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="feature-item">
						<div class="feature-icon">
							<i class="fas fa-undo"></i>
						</div>
						<div class="feature-content">
							<h5>Free Returns</h5>
							<p>Back guarantee under 72 hours</p>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="feature-item">
						<div class="feature-icon">
							<i class="fas fa-user-friends"></i>
						</div>
						<div class="feature-content">
							<h5>Member Discount</h5>
							<p>On every order over GH₵ 2000.00</p>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="feature-item">
						<div class="feature-icon">
							<i class="fas fa-gift"></i>
						</div>
						<div class="feature-content">
							<h5>Special Gifts</h5>
							<p>New product get special gifts</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>


	<!-- Most Popular Categories -->
	<section class="popular-categories">
		<div class="container">
			<h2 class="section-title">SHOP BY CATEGORY</h2>
			<div class="category-grid-container">
				<!-- Large Category on Left -->
				<div class="category-large">
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/black-friday-sale-offer-deals-background_1055-8959.avif" alt="Flash Deals">
					<div class="category-large-overlay">
						<h4>Flash Deals</h4>
					</div>
				</div>

				<!-- Grid of Smaller Categories on Right -->
				<div class="category-grid-right">
					<div class="category-small">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/ipad.jpg" alt="iPads">
						<div class="category-small-overlay">
							<h4><span data-translate="ipads">IPads and Tablets</span></h4>
							<p>From GH₵ 3000</p>
						</div>
					</div>
					<div class="category-small">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/smartphones.webp" alt="Smartphones">
						<div class="category-small-overlay">
							<h4><span data-translate="smartphones">Smartphones</span></h4>
							<p>From GH₵ 2500</p>
						</div>
					</div>
					<div class="category-small">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/laptop.jpg" alt="Laptops">
						<div class="category-small-overlay">
							<h4><span data-translate="laptops">Laptops and Desktops</span></h4>
							<p>From GH₵ 4000</p>
						</div>
					</div>
					<div class="category-small">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/photography.jpg" alt="Photography">
						<div class="category-small-overlay">
							<h4><span data-translate="photography">Photography and Video Equipment</span></h4>
							<p>From GH₵ 5000</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Camera & Video Equipment Promo -->
	<!-- DJI Osmo Pocket 3 Promo -->
	<section class="smart-band-promo">
		<div class="container">
			<div class="promo-banner" style="background: linear-gradient(135deg, #7f56d9 0%, #6a5af9 100%);">
				<div class="promo-content">
					<h2>DJI Osmo Pocket 3 — Small Frame, Big Flex 🎥</h2>
					<p>
						Meet the vlogging beast that fits in your hand.
						4K clarity, buttery-smooth shots, and creator-level power —
						ready to shoot, edit, and post anywhere you go.
					</p>
					<a href="views/all_product.php" class="cta-btn">Shop Now →</a>
				</div>

				<div class="promo-image">
					<img
						src="http://169.239.251.102:442/~chelsea.somuah/uploads/4fx0b5kd-removebg-preview.png"
						alt="DJI Osmo Pocket 3 camera on table">
				</div>
			</div>
		</div>
	</section>



	<!-- Admin Panel (only visible to admins) -->
	<?php if ($is_admin): ?>
		<div class="admin-panel animate__animated animate__zoomIn">
			<h3>Admin Dashboard</h3>
			<p>Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>! Manage your store.</p>
			<a href="admin/category.php" class="admin-btn">Manage Store</a>
		</div>
	<?php endif; ?>


	<!-- DEALS OF THE WEEK — Special offers section -->
	<section class="deals-section">
		<div class="deals-container">
			<h2 class="deals-title"><span data-translate="deals_of_week">Deals Of The Week</span></h2>

			<div class="deals-grid">
				<!-- Deal 1: HP LAPTOP -->
				<div class="deal-card">
					<div class="deal-discount">-23%</div>
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-22at10.24.50AM.png" alt="HP LAPTOP " class="deal-image">
					<div class="deal-brand">HP Elitebook</div>
					<h3 class="deal-title">HP EliteBook X G1i 14 inch Notebook Next Gen AI PC Wolf Pro Security Edition</h3>
					<div class="deal-rating">
						<div class="stars">
							<i class="far fa-star"></i>
							<i class="far fa-star"></i>
							<i class="far fa-star"></i>
							<i class="far fa-star"></i>
							<i class="far fa-star"></i>
						</div>
					</div>
					<div class="deal-pricing">
						<span class="deal-original-price">GH₵ 15000.00</span>
						<span class="deal-current-price">GH₵ 9000.00</span>
					</div>
					<div class="countdown-timer">
						<div class="countdown-grid">
							<div class="countdown-item">
								<span class="countdown-number" id="days1">12</span>
								<span class="countdown-label">Days</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="hours1">15</span>
								<span class="countdown-label">Hour</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="minutes1">35</span>
								<span class="countdown-label">Min</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="seconds1">01</span>
								<span class="countdown-label">Sec</span>
							</div>
						</div>
					</div>
					<button class="deal-options-btn" onclick="window.location.href='views/all_product.php'">OPTIONS</button>
				</div>

				<!-- Deal 2: Apple iPad -->
				<div class="deal-card">
					<div class="deal-discount">-8%</div>
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-22at10.30.25AM.png" alt="iPad m2" class="deal-image">
					<div class="deal-brand">Apple M2</div>
					<h3 class="deal-title">Apple iPad Pro 12.9" 6th Gen M2 256GB Wi-Fi </h3>
					<div class="deal-rating">
						<div class="stars">
							<i class="fas fa-star"></i>
							<i class="fas fa-star"></i>
							<i class="fas fa-star"></i>
							<i class="fas fa-star"></i>
							<i class="far fa-star"></i>
						</div>
						<span>(4.4)</span>
					</div>
					<div class="deal-pricing">
						<span class="deal-original-price">GH₵5,000</span>
						<span class="deal-current-price">GH₵3,400</span>
					</div>
					<div class="countdown-timer">
						<div class="countdown-grid">
							<div class="countdown-item">
								<span class="countdown-number" id="days2">12</span>
								<span class="countdown-label">Days</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="hours2">15</span>
								<span class="countdown-label">Hour</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="minutes2">35</span>
								<span class="countdown-label">Min</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="seconds2">01</span>
								<span class="countdown-label">Sec</span>
							</div>
						</div>
					</div>
					<button class="deal-options-btn" onclick="window.location.href='views/all_product.php'">OPTIONS</button>
				</div>

				<!-- Deal 3: LG Apple iPad Mini -->
				<div class="deal-card">
					<div class="deal-discount">-19%</div>
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-22at10.33.38AM.png" alt="Apple iPad Mini" class="deal-image">
					<div class="deal-brand">Sony</div>
					<h3 class="deal-title">Sony a7R V Mirrorless Camera</h3>
					<div class="deal-rating">
						<div class="stars">
							<i class="fas fa-star"></i>
							<i class="fas fa-star"></i>
							<i class="fas fa-star"></i>
							<i class="fas fa-star"></i>
							<i class="fas fa-star"></i>
						</div>
						<span>(5)</span>
					</div>
					<div class="deal-pricing">
						<span class="deal-original-price">GH₵25000.00</span>
						<span class="deal-current-price">GH₵15000.00</span>
					</div>
					<div class="countdown-timer">
						<div class="countdown-grid">
							<div class="countdown-item">
								<span class="countdown-number" id="days3">12</span>
								<span class="countdown-label">Days</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="hours3">15</span>
								<span class="countdown-label">Hour</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="minutes3">35</span>
								<span class="countdown-label">Min</span>
							</div>
							<div class="countdown-item">
								<span class="countdown-number" id="seconds3">01</span>
								<span class="countdown-label">Sec</span>
							</div>
						</div>
					</div>
					<button class="deal-options-btn" onclick="window.location.href='views/all_product.php'">OPTIONS</button>
				</div>
			</div>
		</div>
	</section>

	<!-- BRANDS — Infinite scroll + magic bento hover -->
	<section class="brands-area">
		<div class="container">
			<h2 class="section-title text-center"><span data-translate="popular_brands">Popular Brands</span></h2>
			<p class="section-sub text-center"><span data-translate="trusted_partners">GadgetGarage's Trusted Brand Partners</span></p>

			<div class="brands-container">
				<div class="brand-row">
					<!-- First Row - duplicate for seamless loop -->
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg" alt="Apple">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/dell.com" alt="Dell">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/HP_logo_2012.svg/1200px-HP_logo_2012.svg.png" alt="HP">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Lenovo_logo_2015.svg/1200px-Lenovo_logo_2015.svg.png" alt="Lenovo">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/58/ASUS_Logo.svg/1200px-ASUS_Logo.svg.png" alt="ASUS">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/acer.com" alt="Acer">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/canon.com" alt="Canon">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/nikon.com" alt="Nikon">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/sony.com" alt="Sony">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/panasonic.com" alt="Panasonic">
					</div>
					<div class="brand-card">
						<img src="https://1000logos.net/wp-content/uploads/2018/02/Fujifilm-logo.png" alt="Fujifilm">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/gopro.com" alt="GoPro">
					</div>
					<!-- Duplicate for seamless loop -->
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg" alt="Apple">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/dell.com" alt="Dell">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/HP_logo_2012.svg/1200px-HP_logo_2012.svg.png" alt="HP">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Lenovo_logo_2015.svg/1200px-Lenovo_logo_2015.svg.png" alt="Lenovo">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/58/ASUS_Logo.svg/1200px-ASUS_Logo.svg.png" alt="ASUS">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/acer.com" alt="Acer">
					</div>
				</div>
				<div class="brand-row">
					<!-- Second Row -->
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/DJI_logo.svg/2560px-DJI_logo.svg.png" alt="DJI">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Samsung_Logo.svg/1280px-Samsung_Logo.svg.png" alt="Samsung">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/google.com" alt="Google">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ae/Xiaomi_logo_%282021-%29.svg/2048px-Xiaomi_logo_%282021-%29.svg.png" alt="Xiaomi">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/04/Huawei_Standard_logo.svg/2560px-Huawei_Standard_logo.svg.png" alt="Huawei">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/microsoft.com" alt="Microsoft">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/MSI_Logo.svg/2560px-MSI_Logo.svg.png" alt="MSI">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/Razer_Logo.svg/2560px-Razer_Logo.svg.png" alt="Razer">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/amazon.com" alt="Amazon">
					</div>
					<!-- Duplicate for seamless loop -->
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/DJI_logo.svg/2560px-DJI_logo.svg.png" alt="DJI">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Samsung_Logo.svg/1280px-Samsung_Logo.svg.png" alt="Samsung">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/google.com" alt="Google">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ae/Xiaomi_logo_%282021-%29.svg/2048px-Xiaomi_logo_%282021-%29.svg.png" alt="Xiaomi">
					</div>
					<div class="brand-card">
						<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/04/Huawei_Standard_logo.svg/2560px-Huawei_Standard_logo.svg.png" alt="Huawei">
					</div>
					<div class="brand-card">
						<img src="https://logo.clearbit.com/microsoft.com" alt="Microsoft">
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- TESTIMONIALS — Card Stack -->
	<section class="testimonials">
		<div class="container">
			<div class="testimonials-text-section">
				<h2 class="section-title" data-translate="what_customers_say">What Our Customers Say</h2>
				<p class="section-sub" data-translate="amazing_reviews">Stories from satisfied GadgetGarage customers</p>
			</div>

			<div class="card-swap-container" id="cardSwapContainer">
				<div class="testimonial-card">
					<div class="card-features">
						<div class="card-feature">
							<i class="fas fa-bolt"></i> <span data-translate="fast">Fast</span>
						</div>
						<div class="card-feature">
							<i class="fas fa-shield-alt"></i> <span data-translate="reliable">Reliable</span>
						</div>
					</div>
					<div class="testimonial-content">
						<div class="star-rating">
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
						</div>
						<div class="testimonial-quote">
							"Fantastic service and fast delivery. My refurbished laptop arrived in two days and works like new!"
						</div>
						<div class="testimonial-author">
							<div class="author-info">
								<h4>Yaw Mensah</h4>
								<p>Software Developer, Accra</p>
							</div>
						</div>
					</div>
				</div>

				<div class="testimonial-card">
					<div class="card-features">
						<div class="card-feature">
							<i class="fas fa-dollar-sign"></i> <span data-translate="affordable">Affordable</span>
						</div>
						<div class="card-feature">
							<i class="fas fa-star"></i> <span data-translate="quality">Quality</span>
						</div>
					</div>
					<div class="testimonial-content">
						<div class="star-rating">
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
						</div>
						<div class="testimonial-quote">
							"The prices are great and the checkout process was incredibly smooth. No hidden fees!"
						</div>
						<div class="testimonial-author">
							<div class="author-info">
								<h4>Akua Asante</h4>
								<p>Marketing Manager, Kumasi</p>
							</div>
						</div>
					</div>
				</div>

				<div class="testimonial-card">
					<div class="card-features">
						<div class="card-feature">
							<i class="fas fa-headset"></i> Support
						</div>
						<div class="card-feature">
							<i class="fas fa-wrench"></i> Expert
						</div>
					</div>
					<div class="testimonial-content">
						<div class="star-rating">
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
						</div>
						<div class="testimonial-quote">
							"Customer support was amazing! They helped me pick the right camera for my photography business."
						</div>
						<div class="testimonial-author">
							<div class="author-info">
								<h4>Kofi Boateng</h4>
								<p>Photographer, Tema</p>
							</div>
						</div>
					</div>
				</div>

				<div class="testimonial-card">
					<div class="card-features">
						<div class="card-feature">
							<i class="fas fa-certificate"></i> Authentic
						</div>
						<div class="card-feature">
							<i class="fas fa-tools"></i> Warranty
						</div>
					</div>
					<div class="testimonial-content">
						<div class="star-rating">
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
							<span class="star">★</span>
						</div>
						<div class="testimonial-quote">
							"Authentic brands with solid warranties. My iPhone has been working perfectly for months!"
						</div>
						<div class="testimonial-author">
							<div class="author-info">
								<h4>Ama Adjei</h4>
								<p>Student, Cape Coast</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<footer class="main-footer">
		<div class="container">
			<div class="footer-content">
				<div class="row">
					<div class="col-lg-4 col-md-6 mb-4">
						<div class="footer-brand">
							<div class="footer-logo" style="margin-bottom: 16px;">
								<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
									alt="Gadget Garage"
									style="height: 35px; width: auto; object-fit: contain;">
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
					<div class="col-lg-2 col-md-6 mb-4">
						<h5 class="footer-title">Shop</h5>
						<ul class="footer-links">
							<li><a href="views/all_product.php?category=phones">Smartphones</a></li>
							<li><a href="views/all_product.php?category=laptops">Laptops</a></li>
							<li><a href="views/all_product.php?category=ipads">Tablets</a></li>
							<li><a href="views/all_product.php?category=cameras">Cameras</a></li>
							<li><a href="views/all_product.php?category=video">Video Equipment</a></li>
						</ul>
					</div>
					<div class="col-lg-2 col-md-6 mb-4">
						<h5 class="footer-title">Services</h5>
						<ul class="footer-links">
							<li><a href="views/repair_services.php">Device Repair</a></li>
							<li><a href="#">Tech Support</a></li>
							<li><a href="#">Data Recovery</a></li>
							<li><a href="#">Setup Services</a></li>
							<li><a href="#">Warranty</a></li>
						</ul>
					</div>
					<div class="col-lg-2 col-md-6 mb-4">
						<h5 class="footer-title">Company</h5>
						<ul class="footer-links">
							<li><a href="#">About Us</a></li>
							<li><a href="views/contact.php">Contact</a></li>
							<li><a href="#">Careers</a></li>
							<li><a href="#">Blog</a></li>
							<li><a href="#">Press</a></li>
						</ul>
					</div>
					<div class="col-lg-2 col-md-6 mb-4">
						<h5 class="footer-title">Support</h5>
						<ul class="footer-links">
							<li><a href="#">Help Center</a></li>
							<li><a href="#">Shipping Info</a></li>
							<li><a href="#">Returns</a></li>
							<li><a href="views/legal.php">Privacy Policy</a></li>
							<li><a href="views/terms_conditions.php">Terms of Service</a></li>
						</ul>
					</div>
				</div>
				<hr class="footer-divider">
				<div class="footer-bottom">
					<div class="row align-items-center">
						<div class="col-md-6">
							<p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
						</div>
						<div class="col-md-6 text-end">
							<div class="payment-methods">
								<img src="<?php echo generate_placeholder_url('VISA', '40x25'); ?>" alt="Visa">
								<img src="<?php echo generate_placeholder_url('MC', '40x25'); ?>" alt="Mastercard">
								<img src="<?php echo generate_placeholder_url('AMEX', '40x25'); ?>" alt="American Express">
								<img src="<?php echo generate_placeholder_url('GPAY', '40x25'); ?>" alt="Google Pay">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>

	<!-- Newsletter Popup -->
	<div class="newsletter-popup" id="newsletterPopup">
		<div class="newsletter-overlay" onclick="closeNewsletter()"></div>
		<div class="newsletter-modal">
			<button class="newsletter-close" onclick="closeNewsletter()">
				<i class="fas fa-times"></i>
			</button>
			<div class="newsletter-content">
				<div class="newsletter-icon">
					<i class="fas fa-envelope"></i>
				</div>
				<h3><span data-translate="stay_updated">Stay Updated!</span></h3>
				<p><span data-translate="newsletter_description">Get the latest tech deals, new arrivals, and exclusive offers delivered to your inbox.</span></p>
				<form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
					<input type="email" placeholder="Enter your email address" required class="newsletter-input" data-translate-placeholder="email_placeholder">
					<button type="submit" class="newsletter-btn"><span data-translate="subscribe_now">Subscribe Now</span></button>
				</form>
				<p class="newsletter-disclaimer">We respect your privacy. Unsubscribe at any time.</p>
			</div>
		</div>
	</div>

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
					<p>Hello! How can we help you today?</p>
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

	<!-- Scripts -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="js/dark-mode.js"></script>
	<script src="js/cart.js"></script>
	<script src="js/header.js"></script>
	<script src="js/chatbot.js"></script>
	<script>
		// Search functionality
		document.querySelector('.search-input').addEventListener('keypress', function(e) {
			if (e.key === 'Enter') {
				performSearch();
			}
		});

		document.querySelector('.search-btn').addEventListener('click', performSearch);

		function performSearch() {
			const query = document.querySelector('.search-input').value.trim();
			if (query) {
				// Redirect to search results page
				window.location.href = 'product_search_result.php?query=' + encodeURIComponent(query);
			}
		}

		// Dropdown navigation functions
		let dropdownTimeout;

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
				// Clear any existing timeout
				clearTimeout(dropdownTimeout);
				// Set a delay before hiding to allow moving to dropdown
				dropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		}

		// Shop Category Dropdown Functions
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

		// Enhanced dropdown behavior
		document.addEventListener('DOMContentLoaded', function() {
			const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
			const dropdown = document.getElementById('shopDropdown');

			if (shopCategoriesBtn && dropdown) {
				// Show dropdown on button hover
				shopCategoriesBtn.addEventListener('mouseenter', showDropdown);

				// Hide dropdown when leaving button (with delay)
				shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);

				// Keep dropdown open when hovering over it
				dropdown.addEventListener('mouseenter', function() {
					clearTimeout(dropdownTimeout);
				});

				// Hide dropdown when leaving dropdown area
				dropdown.addEventListener('mouseleave', hideDropdown);
			}
		});

		// Smooth scrolling for internal links
		document.querySelectorAll('a[href^="#"]').forEach(anchor => {
			anchor.addEventListener('click', function(e) {
				e.preventDefault();
				const target = document.querySelector(this.getAttribute('href'));
				if (target) {
					target.scrollIntoView({
						behavior: 'smooth'
					});
				}
			});
		});

		// Add animation classes on scroll
		const observerOptions = {
			threshold: 0.1,
			rootMargin: '0px 0px -50px 0px'
		};

		const observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					entry.target.style.opacity = '1';
					entry.target.style.transform = 'translateY(0)';
				}
			});
		}, observerOptions);

		// Observe elements for animation
		document.querySelectorAll('.promo-card, .hero-content').forEach(el => {
			observer.observe(el);
		});

		// User dropdown functionality
		function toggleUserDropdown() {
			const dropdown = document.getElementById('userDropdownMenu');
			dropdown.classList.toggle('show');
		}

		// Close dropdown when clicking outside
		document.addEventListener('click', function(event) {
			const dropdown = document.getElementById('userDropdownMenu');
			const avatar = document.querySelector('.user-avatar');

			if (!dropdown.contains(event.target) && !avatar.contains(event.target)) {
				dropdown.classList.remove('show');
			}
		});

		// Profile picture modal functionality
		function openProfilePictureModal() {
			// For now, show alert - will be replaced with actual modal
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					title: 'Profile Picture',
					text: 'Profile picture upload functionality will be implemented',
					icon: 'info',
					confirmButtonColor: '#D19C97',
					confirmButtonText: 'OK'
				});
			} else {
				alert('Profile picture upload functionality will be implemented');
			}
		}

		// Language change functionality - instant translation
		function changeLanguage(language) {
			// Get current language
			const currentLang = localStorage.getItem('selectedLanguage') || 'en';

			// If same language, do nothing
			if (currentLang === language) return;

			// Store language preference immediately
			localStorage.setItem('selectedLanguage', language);

			// Apply translations instantly
			applyTranslations();

			// Update language selector to show current selection
			const languageSelect = document.querySelector('select[onchange="changeLanguage(this.value)"]');
			if (languageSelect) {
				languageSelect.value = language;
			}
		}

		// Enhanced translation application with better element detection
		function applyTranslationsEnhanced() {
			// Get stored language preference or default to English
			const currentLang = localStorage.getItem('selectedLanguage') || 'en';
			console.log('Current language:', currentLang); // Debug log

			// Update language dropdown to show current selection
			const languageSelectors = document.querySelectorAll('select[onchange="changeLanguage(this.value)"]');
			languageSelectors.forEach(selector => {
				if (selector) {
					selector.value = currentLang;
				}
			});

			// Find and translate all elements with data-translate attribute
			document.querySelectorAll('[data-translate]').forEach(element => {
				const key = element.getAttribute('data-translate');
				const translation = translate(key, currentLang);

				// Handle different element types
				if (element.tagName === 'INPUT' && (element.type === 'text' || element.type === 'email' || element.type === 'search')) {
					element.placeholder = translation;
				} else {
					element.textContent = translation;
				}
			});

			// Handle elements with data-translate-placeholder for placeholder translations
			document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
				const key = element.getAttribute('data-translate-placeholder');
				const translation = translate(key, currentLang);
				if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
					element.placeholder = translation;
				}
			});

			// Also update modal text if it appears
			updateModalTranslations(currentLang);
		}

		// Update modal translations dynamically
		function updateModalTranslations(lang) {
			const modalTexts = {
				'change_language': '.language-modal-header h3',
				'are_you_sure_change': '.language-modal-body p:first-of-type',
				'page_will_reload': '.language-modal-note',
				'cancel': '.language-btn-cancel',
				'change_language_btn': '.language-btn-confirm'
			};

			Object.keys(modalTexts).forEach(key => {
				const element = document.querySelector(modalTexts[key]);
				if (element) {
					element.textContent = translate(key, lang);
				}
			});
		}

		// Comprehensive Translation System
		const translations = {
			en: {
				// Header & Navigation
				"shop_by_brands": "SHOP BY BRANDS",
				"all_brands": "All Brands",
				"home": "HOME",
				"shop": "SHOP",
				"all_products": "All Products",
				"mobile_devices": "Mobile Devices",
				"computing": "Computing",
				"photography_video": "Photography & Video",
				"photography": "Photography",
				"more": "MORE",
				"device_drop": "Device Drop",
				"repair_studio": "Repair Studio",
				"flash_deal": "Flash Deal",
				"stay_updated": "Stay Updated!",
				"subscribe_now": "Subscribe Now",
				"email_placeholder": "Enter your email address",
				"newsletter_description": "Get the latest tech deals, new arrivals, and exclusive offers delivered to your inbox.",
				"view_all_products": "View All Products",
				"ipads": "iPads",
				"desktops": "Desktops",
				"video_equipment": "Video Equipment",
				"popular_brands": "Popular Brands",
				"trusted_partners": "GadgetGarage's Trusted Brand Partners",
				"repair_services": "Repair Services",
				"support": "Support",
				"help": "Help",
				"contact_us": "Contact Us",
				"faq": "FAQ",
				"search_placeholder": "Search for products...",
				"cart": "Cart",
				"login": "Login",
				"register": "Register",
				"logout": "Logout",
				"profile": "Profile",
				"my_orders": "My Orders",
				"language": "Language",
				"dark_mode": "Dark Mode",

				// Hero Section
				"hero_title": "Premium Refurbished Tech at Unbeatable Prices",
				"hero_subtitle": "Discover quality refurbished smartphones, laptops, and gadgets with warranty",
				"shop_now": "Shop Now",
				"learn_more": "Learn More",
				"starting_at": "Starting At",
				"free_shipping": "Free shipping all order over",
				"every_order_over": "On every order over",

				// Deals Section
				"limited_time_deals": "Limited Time Deals",
				"dont_miss_out": "Don't miss out on these amazing offers!",
				"days": "Days",
				"hours": "Hours",
				"minutes": "Minutes",
				"seconds": "Seconds",
				"ends_in": "Ends in",
				"view_options": "VIEW OPTIONS",
				"add_to_cart": "Add to Cart",

				// Testimonials
				"what_customers_say": "What Customers Say",
				"amazing_reviews": "Stories from satisfied GadgetGarage customers",
				"fast": "Fast",
				"reliable": "Reliable",
				"affordable": "Affordable",
				"quality": "Quality",
				"support": "Support",
				"expert": "Expert",
				"authentic": "Authentic",
				"warranty": "Warranty",
				"customizable": "Customizable",
				"smooth": "Smooth",

				// Features
				"why_choose_us": "Why Choose Gadget Garage",
				"free_delivery": "Free Delivery",
				"free_delivery_desc": "Free shipping on orders over GH₵ 99",
				"warranty_protection": "Warranty Protection",
				"warranty_protection_desc": "12-month warranty on all products",
				"expert_support": "Expert Support",
				"expert_support_desc": "24/7 customer support available",
				"secure_payment": "Secure Payment",
				"secure_payment_desc": "Safe and secure payment methods",

				// Newsletter
				"newsletter_title": "Stay Updated with Latest Deals",
				"newsletter_desc": "Subscribe to get notifications about new arrivals and exclusive offers",
				"email_placeholder": "Enter your email address",
				"subscribe": "Subscribe",

				// Footer
				"footer_description": "Your trusted partner for premium tech devices, expert repairs, and innovative solutions.",
				"quick_links": "Quick Links",
				"smartphones": "Smartphones",
				"laptops": "Laptops",
				"tablets": "Tablets",
				"cameras": "Cameras",
				"accessories": "Accessories",
				"services": "Services",
				"device_repairs": "Device Repairs",
				"data_recovery": "Data Recovery",
				"tech_consultation": "Tech Consultation",
				"customer_care": "Customer Care",
				"about_us": "About Us",
				"privacy_policy": "Privacy Policy",
				"terms_service": "Terms of Service",
				"return_policy": "Return Policy",
				"all_rights_reserved": "All rights reserved",

				// Modal texts
				"change_language": "Change Language",
				"are_you_sure_change": "Are you sure you want to change the language to",
				"page_will_reload": "The page will reload to apply the new language.",
				"cancel": "Cancel",
				"change_language_btn": "Change Language",

				// Common buttons
				"view_all": "View All",
				"see_more": "See More",
				"back": "Back",
				"next": "Next",
				"close": "Close",
				"save": "Save",
				"edit": "Edit",
				"delete": "Delete",
				"confirm": "Confirm",
				"submit": "Submit",

				// Product related
				"price": "Price",
				"description": "Description",
				"specifications": "Specifications",
				"reviews": "Reviews",
				"in_stock": "In Stock",
				"out_of_stock": "Out of Stock",
				"quantity": "Quantity",
				"total": "Total",
				"subtotal": "Subtotal",
				"checkout": "Checkout",
				"continue_shopping": "Continue Shopping",

				// New Content Translations
				"black_friday_deals": "BLACK FRIDAY DEALS! ON ORDERS OVER GH₵2,000!",
				"top_picks_title": "Gadget Garage's Top Picks for You",
				"top_picks_subtitle": "Discover our most popular and trending products this week",
				"deals_of_week": "Deals Of The Week",
				"ipad_pro_title": "Apple IPad Pro 11<br>Ultra Retina XDR<br>Display, 256GB"
			},

			es: {
				// Header & Navigation
				"shop_by_brands": "COMPRAR POR MARCAS",
				"all_brands": "Todas las Marcas",
				"home": "INICIO",
				"shop": "TIENDA",
				"all_products": "Todos los Productos",
				"mobile_devices": "Dispositivos Móviles",
				"computing": "Informática",
				"photography_video": "Fotografía y Video",
				"photography": "Fotografía",
				"more": "MÁS",
				"device_drop": "Entrega de Dispositivo",
				"repair_studio": "Estudio de Reparación",
				"flash_deal": "Oferta Flash",
				"stay_updated": "¡Mantente Actualizado!",
				"subscribe_now": "Suscríbete Ahora",
				"email_placeholder": "Ingresa tu dirección de correo",
				"newsletter_description": "Recibe las últimas ofertas tecnológicas, nuevos productos y promociones exclusivas en tu bandeja de entrada.",
				"view_all_products": "Ver Todos los Productos",
				"ipads": "iPads",
				"desktops": "Escritorios",
				"video_equipment": "Equipo de Video",
				"popular_brands": "Marcas Populares",
				"trusted_partners": "Socios de Marca de Confianza de GadgetGarage",
				"repair_services": "Servicios de Reparación",
				"support": "Soporte",
				"help": "Ayuda",
				"contact_us": "Contáctanos",
				"faq": "Preguntas Frecuentes",
				"search_placeholder": "Buscar productos...",
				"cart": "Carrito",
				"login": "Iniciar Sesión",
				"register": "Registrarse",
				"logout": "Cerrar Sesión",
				"profile": "Perfil",
				"my_orders": "Mis Pedidos",
				"language": "Idioma",
				"dark_mode": "Modo Oscuro",

				// Hero Section
				"hero_title": "Tecnología Reacondicionada Premium a Precios Inmejorables",
				"hero_subtitle": "Descubre smartphones, laptops y gadgets reacondicionados de calidad con garantía",
				"shop_now": "Comprar Ahora",
				"learn_more": "Saber Más",
				"starting_at": "Desde",
				"free_shipping": "Envío gratis en pedidos superiores a",
				"every_order_over": "En cada pedido superior a",

				// Deals Section
				"limited_time_deals": "Ofertas por Tiempo Limitado",
				"dont_miss_out": "¡No te pierdas estas increíbles ofertas!",
				"days": "Días",
				"hours": "Horas",
				"minutes": "Minutos",
				"seconds": "Segundos",
				"ends_in": "Termina en",
				"view_options": "VER OPCIONES",
				"add_to_cart": "Añadir al Carrito",

				// Testimonials
				"what_customers_say": "Lo que Dicen los Clientes",
				"amazing_reviews": "Historias de clientes satisfechos de GadgetGarage",
				"fast": "Rápido",
				"reliable": "Confiable",
				"affordable": "Asequible",
				"quality": "Calidad",
				"support": "Soporte",
				"expert": "Experto",
				"authentic": "Auténtico",
				"warranty": "Garantía",
				"customizable": "Personalizable",
				"smooth": "Suave",

				// Features
				"why_choose_us": "Por Qué Elegir Gadget Garage",
				"free_delivery": "Entrega Gratis",
				"free_delivery_desc": "Envío gratis en pedidos superiores a GH₵ 99",
				"warranty_protection": "Protección de Garantía",
				"warranty_protection_desc": "Garantía de 12 meses en todos los productos",
				"expert_support": "Soporte Experto",
				"expert_support_desc": "Atención al cliente 24/7 disponible",
				"secure_payment": "Pago Seguro",
				"secure_payment_desc": "Métodos de pago seguros y protegidos",

				// Newsletter
				"newsletter_title": "Mantente Actualizado con las Últimas Ofertas",
				"newsletter_desc": "Suscríbete para recibir notificaciones sobre nuevas llegadas y ofertas exclusivas",
				"email_placeholder": "Ingresa tu dirección de correo",
				"subscribe": "Suscribirse",

				// Footer
				"footer_description": "Tu socio de confianza para dispositivos tecnológicos premium, reparaciones expertas y soluciones innovadoras.",
				"quick_links": "Enlaces Rápidos",
				"smartphones": "Smartphones",
				"laptops": "Laptops",
				"tablets": "Tabletas",
				"cameras": "Cámaras",
				"accessories": "Accesorios",
				"services": "Servicios",
				"device_repairs": "Reparación de Dispositivos",
				"data_recovery": "Recuperación de Datos",
				"tech_consultation": "Consultoría Técnica",
				"customer_care": "Atención al Cliente",
				"about_us": "Acerca de Nosotros",
				"privacy_policy": "Política de Privacidad",
				"terms_service": "Términos de Servicio",
				"return_policy": "Política de Devoluciones",
				"all_rights_reserved": "Todos los derechos reservados",

				// Modal texts
				"change_language": "Cambiar Idioma",
				"are_you_sure_change": "¿Estás seguro de que quieres cambiar el idioma a",
				"page_will_reload": "La página se recargará para aplicar el nuevo idioma.",
				"cancel": "Cancelar",
				"change_language_btn": "Cambiar Idioma",

				// Common buttons
				"view_all": "Ver Todo",
				"see_more": "Ver Más",
				"back": "Atrás",
				"next": "Siguiente",
				"close": "Cerrar",
				"save": "Guardar",
				"edit": "Editar",
				"delete": "Eliminar",
				"confirm": "Confirmar",
				"submit": "Enviar",

				// Product related
				"price": "Precio",
				"description": "Descripción",
				"specifications": "Especificaciones",
				"reviews": "Reseñas",
				"in_stock": "En Stock",
				"out_of_stock": "Agotado",
				"quantity": "Cantidad",
				"total": "Total",
				"subtotal": "Subtotal",
				"checkout": "Finalizar Compra",
				"continue_shopping": "Continuar Comprando",

				// New Content Translations
				"black_friday_deals": "¡OFERTAS DE VIERNES NEGRO! ¡EN PEDIDOS SUPERIORES A GH₵2,000!",
				"top_picks_title": "Las Mejores Selecciones de Gadget Garage para Ti",
				"top_picks_subtitle": "Descubre nuestros productos más populares y de tendencia esta semana",
				"deals_of_week": "Ofertas de la Semana",
				"ipad_pro_title": "Apple IPad Pro 11<br>Pantalla Ultra Retina XDR<br>256GB"
			},

			fr: {
				// Header & Navigation
				"shop_by_brands": "ACHETER PAR MARQUES",
				"all_brands": "Toutes les Marques",
				"home": "ACCUEIL",
				"shop": "BOUTIQUE",
				"all_products": "Tous les Produits",
				"mobile_devices": "Appareils Mobiles",
				"computing": "Informatique",
				"photography_video": "Photo et Vidéo",
				"photography": "Photographie",
				"more": "PLUS",
				"device_drop": "Dépôt d'Appareil",
				"repair_studio": "Studio de Réparation",
				"flash_deal": "Vente Flash",
				"stay_updated": "Restez Informé!",
				"subscribe_now": "S'abonner Maintenant",
				"email_placeholder": "Entrez votre adresse email",
				"newsletter_description": "Recevez les dernières offres technologiques, nouveaux produits et promotions exclusives dans votre boîte de réception.",
				"view_all_products": "Voir Tous les Produits",
				"ipads": "iPads",
				"desktops": "Ordinateurs de Bureau",
				"video_equipment": "Équipement Vidéo",
				"popular_brands": "Marques Populaires",
				"trusted_partners": "Partenaires de Marques de Confiance de GadgetGarage",
				"repair_services": "Services de Réparation",
				"support": "Support",
				"help": "Aide",
				"contact_us": "Nous Contacter",
				"faq": "FAQ",
				"search_placeholder": "Rechercher des produits...",
				"cart": "Panier",
				"login": "Connexion",
				"register": "S'inscrire",
				"logout": "Déconnexion",
				"profile": "Profil",
				"my_orders": "Mes Commandes",
				"language": "Langue",
				"dark_mode": "Mode Sombre",

				// Hero Section
				"hero_title": "Technologie Reconditionnée Premium à Prix Imbattables",
				"hero_subtitle": "Découvrez des smartphones, ordinateurs portables et gadgets reconditionnés de qualité avec garantie",
				"shop_now": "Acheter Maintenant",
				"learn_more": "En Savoir Plus",
				"starting_at": "À partir de",
				"free_shipping": "Livraison gratuite sur les commandes de plus de",
				"every_order_over": "Sur chaque commande de plus de",

				// Deals Section
				"limited_time_deals": "Offres à Durée Limitée",
				"dont_miss_out": "Ne manquez pas ces offres incroyables !",
				"days": "Jours",
				"hours": "Heures",
				"minutes": "Minutes",
				"seconds": "Secondes",
				"ends_in": "Se termine dans",
				"view_options": "VOIR OPTIONS",
				"add_to_cart": "Ajouter au Panier",

				// Testimonials
				"what_customers_say": "Ce que Disent les Clients",
				"amazing_reviews": "Histoires de clients satisfaits de GadgetGarage",
				"fast": "Rapide",
				"reliable": "Fiable",
				"affordable": "Abordable",
				"quality": "Qualité",
				"support": "Support",
				"expert": "Expert",
				"authentic": "Authentique",
				"warranty": "Garantie",
				"customizable": "Personnalisable",
				"smooth": "Fluide",

				// Features
				"why_choose_us": "Pourquoi Choisir Gadget Garage",
				"free_delivery": "Livraison Gratuite",
				"free_delivery_desc": "Livraison gratuite sur les commandes de plus de GH₵ 99",
				"warranty_protection": "Protection Garantie",
				"warranty_protection_desc": "Garantie de 12 mois sur tous les produits",
				"expert_support": "Support Expert",
				"expert_support_desc": "Support client 24/7 disponible",
				"secure_payment": "Paiement Sécurisé",
				"secure_payment_desc": "Méthodes de paiement sûres et sécurisées",

				// Newsletter
				"newsletter_title": "Restez Informé des Dernières Offres",
				"newsletter_desc": "Abonnez-vous pour recevoir des notifications sur les nouvelles arrivées et offres exclusives",
				"email_placeholder": "Entrez votre adresse e-mail",
				"subscribe": "S'abonner",

				// Footer
				"footer_description": "Votre partenaire de confiance pour les appareils technologiques premium, les réparations expertes et les solutions innovantes.",
				"quick_links": "Liens Rapides",
				"smartphones": "Smartphones",
				"laptops": "Ordinateurs Portables",
				"tablets": "Tablettes",
				"cameras": "Appareils Photo",
				"accessories": "Accessoires",
				"services": "Services",
				"device_repairs": "Réparations d'Appareils",
				"data_recovery": "Récupération de Données",
				"tech_consultation": "Consultation Technique",
				"customer_care": "Service Client",
				"about_us": "À Propos",
				"privacy_policy": "Politique de Confidentialité",
				"terms_service": "Conditions de Service",
				"return_policy": "Politique de Retour",
				"all_rights_reserved": "Tous droits réservés",

				// Modal texts
				"change_language": "Changer de Langue",
				"are_you_sure_change": "Êtes-vous sûr de vouloir changer la langue vers",
				"page_will_reload": "La page se rechargera pour appliquer la nouvelle langue.",
				"cancel": "Annuler",
				"change_language_btn": "Changer de Langue",

				// Common buttons
				"view_all": "Voir Tout",
				"see_more": "Voir Plus",
				"back": "Retour",
				"next": "Suivant",
				"close": "Fermer",
				"save": "Enregistrer",
				"edit": "Modifier",
				"delete": "Supprimer",
				"confirm": "Confirmer",
				"submit": "Soumettre",

				// Product related
				"price": "Prix",
				"description": "Description",
				"specifications": "Spécifications",
				"reviews": "Avis",
				"in_stock": "En Stock",
				"out_of_stock": "Rupture de Stock",
				"quantity": "Quantité",
				"total": "Total",
				"subtotal": "Sous-total",
				"checkout": "Commande",
				"continue_shopping": "Continuer les Achats",

				// New Content Translations
				"black_friday_deals": "OFFRES VENDREDI NOIR! SUR COMMANDES SUPÉRIEURES À GH₵2,000!",
				"top_picks_title": "Les Meilleurs Choix de Gadget Garage pour Vous",
				"top_picks_subtitle": "Découvrez nos produits les plus populaires et tendances cette semaine",
				"deals_of_week": "Offres de la Semaine",
				"ipad_pro_title": "Apple IPad Pro 11<br>Écran Ultra Retina XDR<br>256GB"
			},

			de: {
				// Header & Navigation
				"shop_by_brands": "NACH MARKEN EINKAUFEN",
				"all_brands": "Alle Marken",
				"home": "STARTSEITE",
				"shop": "SHOP",
				"all_products": "Alle Produkte",
				"mobile_devices": "Mobile Geräte",
				"computing": "Computer",
				"photography_video": "Foto & Video",
				"photography": "Fotografie",
				"more": "MEHR",
				"device_drop": "Gerät Abgeben",
				"repair_studio": "Reparatur Studio",
				"flash_deal": "Blitz Angebot",
				"stay_updated": "Bleiben Sie Informiert!",
				"subscribe_now": "Jetzt Abonnieren",
				"email_placeholder": "Geben Sie Ihre E-Mail-Adresse ein",
				"newsletter_description": "Erhalten Sie die neuesten Tech-Angebote, neue Produkte und exklusive Aktionen in Ihrem Posteingang.",
				"view_all_products": "Alle Produkte Anzeigen",
				"ipads": "iPads",
				"desktops": "Desktop-Computer",
				"video_equipment": "Video-Ausrüstung",
				"popular_brands": "Beliebte Marken",
				"trusted_partners": "GadgetGarages Vertrauensvolle Markenpartner",
				"repair_services": "Reparaturdienste",
				"support": "Support",
				"help": "Hilfe",
				"contact_us": "Kontakt",
				"faq": "FAQ",
				"search_placeholder": "Nach Produkten suchen...",
				"cart": "Warenkorb",
				"login": "Anmelden",
				"register": "Registrieren",
				"logout": "Abmelden",
				"profile": "Profil",
				"my_orders": "Meine Bestellungen",
				"language": "Sprache",
				"dark_mode": "Dunkler Modus",

				// Hero Section
				"hero_title": "Premium Refurbished Technik zu Unschlagbaren Preisen",
				"hero_subtitle": "Entdecken Sie hochwertige refurbished Smartphones, Laptops und Gadgets mit Garantie",
				"shop_now": "Jetzt Einkaufen",
				"learn_more": "Mehr Erfahren",
				"starting_at": "Ab",
				"free_shipping": "Kostenloser Versand bei Bestellungen über",
				"every_order_over": "Bei jeder Bestellung über",

				// Deals Section
				"limited_time_deals": "Zeitlich Begrenzte Angebote",
				"dont_miss_out": "Verpassen Sie nicht diese fantastischen Angebote!",
				"days": "Tage",
				"hours": "Stunden",
				"minutes": "Minuten",
				"seconds": "Sekunden",
				"ends_in": "Endet in",
				"view_options": "OPTIONEN ANSEHEN",
				"add_to_cart": "In den Warenkorb",

				// Testimonials
				"what_customers_say": "Was Kunden Sagen",
				"amazing_reviews": "Geschichten von zufriedenen GadgetGarage-Kunden",
				"fast": "Schnell",
				"reliable": "Zuverlässig",
				"affordable": "Erschwinglich",
				"quality": "Qualität",
				"support": "Support",
				"expert": "Experte",
				"authentic": "Authentisch",
				"warranty": "Garantie",
				"customizable": "Anpassbar",
				"smooth": "Reibungslos",

				// Features
				"why_choose_us": "Warum Gadget Garage Wählen",
				"free_delivery": "Kostenlose Lieferung",
				"free_delivery_desc": "Kostenloser Versand bei Bestellungen über GH₵ 99",
				"warranty_protection": "Garantieschutz",
				"warranty_protection_desc": "12-monatige Garantie auf alle Produkte",
				"expert_support": "Expertenunterstützung",
				"expert_support_desc": "24/7 Kundensupport verfügbar",
				"secure_payment": "Sichere Bezahlung",
				"secure_payment_desc": "Sichere und geschützte Zahlungsmethoden",

				// Newsletter
				"newsletter_title": "Bleiben Sie über die Neuesten Angebote Informiert",
				"newsletter_desc": "Abonnieren Sie, um Benachrichtigungen über Neuankömmlinge und exklusive Angebote zu erhalten",
				"email_placeholder": "Geben Sie Ihre E-Mail-Adresse ein",
				"subscribe": "Abonnieren",

				// Footer
				"footer_description": "Ihr vertrauensvoller Partner für Premium-Technikgeräte, Expertenreparaturen und innovative Lösungen.",
				"quick_links": "Schnelle Links",
				"smartphones": "Smartphones",
				"laptops": "Laptops",
				"tablets": "Tablets",
				"cameras": "Kameras",
				"accessories": "Zubehör",
				"services": "Dienstleistungen",
				"device_repairs": "Gerätereparaturen",
				"data_recovery": "Datenwiederherstellung",
				"tech_consultation": "Technische Beratung",
				"customer_care": "Kundendienst",
				"about_us": "Über Uns",
				"privacy_policy": "Datenschutzrichtlinie",
				"terms_service": "Nutzungsbedingungen",
				"return_policy": "Rückgaberichtlinie",
				"all_rights_reserved": "Alle Rechte vorbehalten",

				// Modal texts
				"change_language": "Sprache Ändern",
				"are_you_sure_change": "Sind Sie sicher, dass Sie die Sprache ändern möchten zu",
				"page_will_reload": "Die Seite wird neu geladen, um die neue Sprache anzuwenden.",
				"cancel": "Abbrechen",
				"change_language_btn": "Sprache Ändern",

				// Common buttons
				"view_all": "Alle Anzeigen",
				"see_more": "Mehr Sehen",
				"back": "Zurück",
				"next": "Weiter",
				"close": "Schließen",
				"save": "Speichern",
				"edit": "Bearbeiten",
				"delete": "Löschen",
				"confirm": "Bestätigen",
				"submit": "Senden",

				// Product related
				"price": "Preis",
				"description": "Beschreibung",
				"specifications": "Spezifikationen",
				"reviews": "Bewertungen",
				"in_stock": "Auf Lager",
				"out_of_stock": "Nicht Verfügbar",
				"quantity": "Menge",
				"total": "Gesamt",
				"subtotal": "Zwischensumme",
				"checkout": "Kasse",
				"continue_shopping": "Weiter Einkaufen",

				// New Content Translations
				"black_friday_deals": "BLACK FRIDAY ANGEBOTE! BEI BESTELLUNGEN ÜBER GH₵2,000!",
				"top_picks_title": "Gadget Garage's Top Auswahl für Sie",
				"top_picks_subtitle": "Entdecken Sie unsere beliebtesten und trendigen Produkte diese Woche",
				"deals_of_week": "Angebote der Woche",
				"ipad_pro_title": "Apple IPad Pro 11<br>Ultra Retina XDR Display<br>256GB"
			}
		};

		// Translation function
		function translate(key, language = null) {
			const lang = language || localStorage.getItem('selectedLanguage') || 'en';
			return translations[lang] && translations[lang][key] ? translations[lang][key] : translations.en[key] || key;
		}

		// Apply translations to all elements on page load
		function applyTranslations() {
			applyTranslationsEnhanced();
		}

		function generatePlaceholderImage(text = 'Product', size = '300x200', bgColor = '#eef2ff', textColor = '#1f2937') {
			const [width, height] = size.split('x').map(Number);
			const safeText = (text || 'Gadget Garage').substring(0, 28).replace(/</g, '&lt;').replace(/>/g, '&gt;');
			const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
				<rect width="100%" height="100%" fill="${bgColor}"/>
				<rect x="1" y="1" width="${width - 2}" height="${height - 2}" fill="none" stroke="#cbd5f5" stroke-width="2"/>
				<text x="50%" y="50%" font-family="Arial, sans-serif" font-size="${Math.max(Math.floor(height * 0.12), 14)}" fill="${textColor}" text-anchor="middle" dominant-baseline="middle">${safeText}</text>
			</svg>`;
			return `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svg)))}`;
		}

		function handleImageError(event, text = 'Product', size = '300x200') {
			if (!event || !event.target) return;
			event.target.onerror = null;
			event.target.src = generatePlaceholderImage(text, size);
		}

		// Initialize translations on page load
		document.addEventListener('DOMContentLoaded', function() {
			// Force English as default if no language is set or if you want to reset
			// Reset to English to fix Spanish display issue
			localStorage.removeItem('selectedLanguage');

			// Uncomment the next line to reset newsletter popup (for testing)
			// localStorage.removeItem('newsletterShown');

			applyTranslations();
		});

		// Theme toggle functionality
		function toggleTheme() {
			const toggle = document.getElementById('themeToggle');
			const body = document.body;

			toggle.classList.toggle('active');
			body.classList.toggle('dark-mode');

			// Store theme preference
			const isDark = body.classList.contains('dark-mode');
			localStorage.setItem('darkMode', isDark);
		}

		// Load saved preferences on page load
		document.addEventListener('DOMContentLoaded', function() {
			// Load saved language
			const savedLanguage = localStorage.getItem('selectedLanguage');
			if (savedLanguage) {
				const languageSelect = document.querySelector('.language-selector select');
				if (languageSelect) {
					languageSelect.value = savedLanguage;
				}
			}

			// Load saved theme
			const isDarkMode = localStorage.getItem('darkMode') === 'true';
			if (isDarkMode) {
				document.body.classList.add('dark-mode');
				document.getElementById('themeToggle').classList.add('active');
			}


			// Load top picks
			loadTopPicks();
		});


		// Load top picks products
		function loadTopPicks() {
			fetch('actions/product_actions.php?action=view_all_products')
				.then(response => response.json())
				.then(products => {
					if (products && products.length > 0) {
						// Get first 4 products as top picks
						const topPicks = products.slice(0, 4);
						displayTopPicks(topPicks);
					} else {
						displayNoTopPicks();
					}
				})
				.catch(error => {
					console.error('Error loading top picks:', error);
					displayNoTopPicks();
				});
		}

		// Display top picks products
		function displayTopPicks(products) {
			const container = document.getElementById('topPicksContainer');
			container.innerHTML = '';

			products.forEach((product, index) => {
				const imagePath = (product.image_url && product.image_url.trim() !== '') ?
					product.image_url :
					generatePlaceholderImage(product.product_title, '300x200');
				const safeTitleAttr = (product.product_title || 'Product').replace(/"/g, '&quot;').replace(/'/g, '&apos;');

				const badges = ['Hot', 'Trending', 'Popular', 'Best Seller'];
				const ratings = [4.8, 4.9, 4.7, 4.6];

				const cardHtml = `
					<div class="col-lg-3 col-md-6 mb-4">
						<a href="views/single_product.php?id=${product.product_id}" class="top-pick-card">
							<div class="position-relative">
								<img src="${imagePath}" alt="${safeTitleAttr}" class="pick-image"
									 onerror="handleImageError(event, '${safeTitleAttr}', '300x200')">
								<div class="pick-badge">${badges[index]}</div>
							</div>
							<h4 class="pick-title">${product.product_title}</h4>
							<div class="pick-price">$${parseFloat(product.product_price).toFixed(2)}</div>
							<div class="pick-rating">
								<div class="rating-stars">
									${'★'.repeat(5)}
								</div>
								<span class="rating-text">(${ratings[index]})</span>
							</div>
							<p class="pick-description">${product.product_desc || 'Premium tech device from Gadget Garage.'}</p>
						</a>
					</div>
				`;
				container.innerHTML += cardHtml;
			});
		}

		// Display message when no top picks available
		function displayNoTopPicks() {
			const container = document.getElementById('topPicksContainer');
			container.innerHTML = `
				<div class="col-12 text-center">
					<div class="loading-spinner">
						<i class="fas fa-microchip fa-2x mb-3" style="color: #008060;"></i>
						<h4>Coming Soon!</h4>
						<p>We're preparing amazing tech picks for you.</p>
						<a href="views/all_product.php" class="btn btn-primary mt-3">Browse All Products</a>
					</div>
				</div>
			`;
		}

		// Live chat functionality
		function toggleLiveChat() {
			const chatPanel = document.getElementById('chatPanel');
			chatPanel.classList.toggle('active');
		}

		// Add live chat event listeners
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

		function sendChatMessage() {
			const chatInput = document.querySelector('.chat-input');
			const chatBody = document.querySelector('.chat-body');
			const message = chatInput.value.trim();

			if (message) {
				// Add user message
				const userMessage = document.createElement('div');
				userMessage.className = 'chat-message user';
				userMessage.innerHTML = `<p style="background: #008060; color: white; padding: 12px 16px; border-radius: 18px; margin: 0; font-size: 0.9rem; text-align: right;">${message}</p>`;
				chatBody.appendChild(userMessage);

				// Clear input
				chatInput.value = '';

				// Simulate bot response
				setTimeout(() => {
					const botMessage = document.createElement('div');
					botMessage.className = 'chat-message bot';
					botMessage.innerHTML = `<p>Thank you for your message! Our team will get back to you shortly. For immediate assistance, please call our support line.</p>`;
					chatBody.appendChild(botMessage);
					chatBody.scrollTop = chatBody.scrollHeight;
				}, 1000);

				// Scroll to bottom
				chatBody.scrollTop = chatBody.scrollHeight;
			}
		}

		// Newsletter popup functions
		function closeNewsletter() {
			document.getElementById('newsletterPopup').style.display = 'none';
			localStorage.setItem('newsletterShown', 'true');
		}

		function subscribeNewsletter(event) {
			event.preventDefault();
			const email = event.target.querySelector('.newsletter-input').value;

			// Here you would typically send the email to your backend
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					title: 'Thank You!',
					text: 'Thank you for subscribing! You will receive updates at ' + email,
					icon: 'success',
					confirmButtonColor: '#D19C97',
					confirmButtonText: 'OK'
				});
			} else {
				alert('Thank you for subscribing! You will receive updates at ' + email);
			}
			closeNewsletter();
		}

		// Hero banner functionality (no slideshow needed)

		// Show newsletter popup after 5 seconds if not shown before
		setTimeout(function() {
			if (!localStorage.getItem('newsletterShown')) {
				document.getElementById('newsletterPopup').style.display = 'flex';
			}
		}, 5000);

		// Card Stack Animation - Based on React CardSwap component
		class CardStack {
			constructor(containerSelector, options = {}) {
				this.container = document.querySelector(containerSelector);
				if (!this.container) return;

				// Configuration based on your React component props
				this.config = {
					cardDistance: options.cardDistance || 60,
					verticalDistance: options.verticalDistance || 70,
					delay: options.delay || 5000,
					pauseOnHover: options.pauseOnHover !== undefined ? options.pauseOnHover : true,
					skewAmount: options.skewAmount || 6,
					easing: options.easing || 'elastic'
				};

				// Animation settings based on easing type
				this.animConfig = this.config.easing === 'elastic' ? {
					ease: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
					durDrop: 2000,
					durMove: 2000,
					durReturn: 2000,
					promoteOverlap: 0.9,
					returnDelay: 0.05
				} : {
					ease: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
					durDrop: 800,
					durMove: 800,
					durReturn: 800,
					promoteOverlap: 0.45,
					returnDelay: 0.2
				};

				this.cards = Array.from(this.container.querySelectorAll('.testimonial-card'));
				this.order = Array.from({
					length: this.cards.length
				}, (_, i) => i);
				this.intervalRef = null;
				this.isAnimating = false;

				this.init();
			}

			// Create slot positions based on your React makeSlot function
			makeSlot(i, total) {
				return {
					x: i * this.config.cardDistance,
					y: -i * this.config.verticalDistance,
					z: -i * this.config.cardDistance * 1.5,
					zIndex: total - i
				};
			}

			// Place card now - equivalent to React placeNow function
			placeNow(element, slot) {
				element.style.transform = `
					translate(-50%, -50%)
					translate3d(${slot.x}px, ${slot.y}px, ${slot.z}px)
					skewY(${this.config.skewAmount}deg)
				`;
				element.style.transformOrigin = 'center center';
				element.style.zIndex = slot.zIndex;
			}

			// Main swap function - equivalent to React swap function
			swap() {
				if (this.order.length < 2 || this.isAnimating) return;
				this.isAnimating = true;

				const [front, ...rest] = this.order;
				const frontCard = this.cards[front];

				// Step 1: Drop front card
				frontCard.style.transition = `transform ${this.animConfig.durDrop}ms ${this.animConfig.ease}`;
				frontCard.style.transform = `
					translate(-50%, -50%)
					translate3d(0px, 500px, 0px)
					skewY(${this.config.skewAmount}deg)
				`;

				// Step 2: Promote other cards with delay
				setTimeout(() => {
					rest.forEach((idx, i) => {
						const card = this.cards[idx];
						const slot = this.makeSlot(i, this.cards.length);

						card.style.transition = `transform ${this.animConfig.durMove}ms ${this.animConfig.ease}`;
						card.style.zIndex = slot.zIndex;

						setTimeout(() => {
							this.placeNow(card, slot);
						}, i * 150);
					});
				}, this.animConfig.durDrop * this.animConfig.promoteOverlap);

				// Step 3: Return front card to back
				setTimeout(() => {
					const backSlot = this.makeSlot(this.cards.length - 1, this.cards.length);
					frontCard.style.zIndex = backSlot.zIndex;
					frontCard.style.transition = `transform ${this.animConfig.durReturn}ms ${this.animConfig.ease}`;
					this.placeNow(frontCard, backSlot);

					// Update order
					setTimeout(() => {
						this.order = [...rest, front];
						this.isAnimating = false;
					}, this.animConfig.durReturn);
				}, this.animConfig.durDrop * this.animConfig.promoteOverlap + this.animConfig.durMove * this.animConfig.returnDelay);
			}

			init() {
				// Initial positioning
				this.cards.forEach((card, i) => {
					const slot = this.makeSlot(i, this.cards.length);
					this.placeNow(card, slot);
				});

				// Start animation cycle
				this.swap();
				this.intervalRef = setInterval(() => this.swap(), this.config.delay);

				// Pause on hover functionality
				if (this.config.pauseOnHover) {
					this.container.addEventListener('mouseenter', () => {
						clearInterval(this.intervalRef);
					});

					this.container.addEventListener('mouseleave', () => {
						this.intervalRef = setInterval(() => this.swap(), this.config.delay);
					});
				}

				// Add click handlers for cards
				this.cards.forEach((card, index) => {
					card.addEventListener('click', () => {
						// Optional: handle card click
						console.log(`Card ${index} clicked`);
					});
				});
			}

			destroy() {
				if (this.intervalRef) {
					clearInterval(this.intervalRef);
				}
			}
		}

		// Initialize the card stack animation
		const cardStack = new CardStack('#cardSwapContainer', {
			cardDistance: 60,
			verticalDistance: 70,
			delay: 5000,
			pauseOnHover: true,
			skewAmount: 6,
			easing: 'elastic'
		});

		// Countdown timer functionality for deals
		function updateCountdown() {
			const timers = [{
					days: document.getElementById('days1'),
					hours: document.getElementById('hours1'),
					minutes: document.getElementById('minutes1'),
					seconds: document.getElementById('seconds1'),
					endTime: new Date().getTime() + (335 * 24 * 60 * 60 * 1000) + (15 * 60 * 60 * 1000) + (35 * 60 * 1000) + (1 * 1000)
				},
				{
					days: document.getElementById('days2'),
					hours: document.getElementById('hours2'),
					minutes: document.getElementById('minutes2'),
					seconds: document.getElementById('seconds2'),
					endTime: new Date().getTime() + (335 * 24 * 60 * 60 * 1000) + (15 * 60 * 60 * 1000) + (35 * 60 * 1000) + (1 * 1000)
				},
				{
					days: document.getElementById('days3'),
					hours: document.getElementById('hours3'),
					minutes: document.getElementById('minutes3'),
					seconds: document.getElementById('seconds3'),
					endTime: new Date().getTime() + (427 * 24 * 60 * 60 * 1000) + (15 * 60 * 60 * 1000) + (35 * 60 * 1000) + (1 * 1000)
				}
			];

			function updateAllTimers() {
				timers.forEach((timer, index) => {
					const now = new Date().getTime();
					const distance = timer.endTime - now;

					if (distance > 0) {
						const days = Math.floor(distance / (1000 * 60 * 60 * 24));
						const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
						const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
						const seconds = Math.floor((distance % (1000 * 60)) / 1000);

						timer.days.textContent = days.toString().padStart(3, '0');
						timer.hours.textContent = hours.toString().padStart(2, '0');
						timer.minutes.textContent = minutes.toString().padStart(2, '0');
						timer.seconds.textContent = seconds.toString().padStart(2, '0');
					} else {
						timer.days.textContent = '000';
						timer.hours.textContent = '00';
						timer.minutes.textContent = '00';
						timer.seconds.textContent = '00';
					}
				});
			}

			// Update immediately
			updateAllTimers();

			// Update every second
			setInterval(updateAllTimers, 1000);
		}

		// Initialize countdown when page loads
		document.addEventListener('DOMContentLoaded', updateCountdown);
	</script>

	<!-- Notification Modal -->
	<?php if ($is_logged_in && !$is_admin): ?>
		<div class="notification-modal" id="notificationModal">
			<div class="notification-content">
				<div class="notification-header">
					<h3><i class="fas fa-bell me-2"></i>Notifications</h3>
					<button class="notification-close" onclick="closeNotifications()">
						<i class="fas fa-times"></i>
					</button>
				</div>
				<div class="notification-body" id="notificationBody">
					<div class="no-notifications">
						<i class="fas fa-bell-slash"></i>
						<p>Loading notifications...</p>
					</div>
				</div>
			</div>
		</div>

		<script>
			// Notification functions
			function showNotifications() {
				const modal = document.getElementById('notificationModal');
				modal.classList.add('show');
				loadNotifications();
			}

			function closeNotifications() {
				const modal = document.getElementById('notificationModal');
				modal.classList.remove('show');
			}

			function loadNotifications() {
				fetch('actions/get_notifications_action.php')
					.then(response => response.json())
					.then(data => {
						const body = document.getElementById('notificationBody');

						if (data.status === 'success' && data.notifications.length > 0) {
							body.innerHTML = data.notifications.map(notification => {
								const timeAgo = getTimeAgo(notification.created_at);
								const unreadClass = notification.is_read == '0' ? 'unread' : '';

								return `
							<div class="notification-item ${unreadClass}" onclick="openNotification(${notification.notification_id}, '${notification.type}', ${notification.related_id})">
								<div class="notification-text">${notification.message}</div>
								<div class="notification-time">${timeAgo}</div>
							</div>
						`;
							}).join('');
						} else {
							body.innerHTML = `
						<div class="no-notifications">
							<i class="fas fa-bell-slash"></i>
							<p>No notifications yet</p>
						</div>
					`;
						}
					})
					.catch(error => {
						console.error('Error loading notifications:', error);
						document.getElementById('notificationBody').innerHTML = `
					<div class="no-notifications">
						<i class="fas fa-exclamation-triangle"></i>
						<p>Error loading notifications</p>
					</div>
				`;
					});
			}

			function openNotification(notificationId, type, relatedId) {
				// Mark as read
				fetch('actions/mark_notification_read_action.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						notification_id: notificationId
					})
				});

				// Handle different notification types
				if (type === 'support_response') {
					// Open support chat for this message
					window.location.href = `support_message.php?message_id=${relatedId}`;
				}

				closeNotifications();

				// Refresh notification badge
				setTimeout(() => {
					location.reload();
				}, 1000);
			}

			function getTimeAgo(dateString) {
				const date = new Date(dateString);
				const now = new Date();
				const diffInSeconds = Math.floor((now - date) / 1000);

				if (diffInSeconds < 60) return 'Just now';
				if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' min ago';
				if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hour ago';
				return Math.floor(diffInSeconds / 86400) + ' day ago';
			}

			// Close modal when clicking outside
			document.getElementById('notificationModal').addEventListener('click', function(e) {
				if (e.target === this) {
					closeNotifications();
				}
			});
		</script>
	<?php endif; ?>

	<script>
		// Countdown timer for promotional banner - Black Friday deals (12 days)
		function startPromoTimer() {
			const timer = document.getElementById('promoTimer');
			if (!timer) return;

			// Set timer to end in 12 days from now
			const now = new Date();
			const endDate = new Date();
			endDate.setDate(now.getDate() + 12);
			endDate.setHours(23, 59, 59, 999);

			const updateTimer = () => {
				const now = new Date();
				let timeLeft = endDate.getTime() - now.getTime();

				if (timeLeft < 0) {
					timer.textContent = "00d:00h:00m:00s";
					return;
				}

				const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
				const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
				const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
				const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

				timer.textContent = `${days.toString().padStart(2, '0')}d:${hours.toString().padStart(2, '0')}h:${minutes.toString().padStart(2, '0')}m:${seconds.toString().padStart(2, '0')}s`;
			};

			// Update immediately
			updateTimer();

			// Update every second
			const countdown = setInterval(() => {
				updateTimer();
				const now = new Date();
				if (now.getTime() >= endDate.getTime()) {
					clearInterval(countdown);
				}
			}, 1000);
		}

		// Start timer when page loads
		startPromoTimer();
	</script>

</body>

</html>