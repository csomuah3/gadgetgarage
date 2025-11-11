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
	}

	// Get cart count
	$customer_id = $is_logged_in ? $_SESSION['customer_id'] : null;
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
	<title>FlavorHub - Your One-Stop Food Destination</title>
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

		body {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
			background-color: #f8fafc;
			color: #1a202c;
			overflow-x: hidden;
		}

		/* Header Styles */
		.main-header {
			background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
			box-shadow: 0 2px 10px rgba(139, 95, 191, 0.1);
			position: sticky;
			top: 0;
			z-index: 1000;
			padding: 12px 0;
		}

		.logo {
			font-size: 1.8rem;
			font-weight: 700;
			color: #8b5fbf;
			text-decoration: none;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.logo .co {
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
			color: white;
			padding: 4px 8px;
			border-radius: 6px;
			font-size: 1rem;
			font-weight: 600;
		}

		.search-container {
			position: relative;
			flex: 1;
			max-width: 500px;
		}

		.search-input {
			width: 100%;
			padding: 12px 20px 12px 50px;
			border: 2px solid #e2e8f0;
			border-radius: 25px;
			font-size: 1rem;
			transition: all 0.3s ease;
			background: #f8fafc;
		}

		.search-input:focus {
			outline: none;
			border-color: #8b5fbf;
			background: white;
			box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
		}

		.search-icon {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: #8b5fbf;
			font-size: 1.1rem;
		}

		.search-btn {
			position: absolute;
			right: 6px;
			top: 50%;
			transform: translateY(-50%);
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
			border: none;
			padding: 8px 16px;
			border-radius: 20px;
			color: white;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.search-btn:hover {
			background: linear-gradient(135deg, #764ba2, #8b5fbf);
			transform: translateY(-50%) scale(1.05);
		}

		.header-actions {
			display: flex;
			align-items: center;
			gap: 16px;
		}

		.header-icon {
			position: relative;
			padding: 8px;
			border-radius: 8px;
			transition: all 0.3s ease;
			color: #4b5563;
			cursor: pointer;
		}

		.header-icon:hover {
			background: rgba(139, 95, 191, 0.1);
			color: #8b5fbf;
		}

		.cart-badge {
			position: absolute;
			top: -2px;
			right: -2px;
			background: linear-gradient(135deg, #f093fb, #8b5fbf);
			color: white;
			font-size: 0.75rem;
			padding: 2px 6px;
			border-radius: 10px;
			min-width: 18px;
			text-align: center;
		}

		.user-menu {
			position: relative;
		}

		.login-btn {
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			background: linear-gradient(135deg, #764ba2, #8b5fbf);
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
			width: 36px;
			height: 36px;
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.user-avatar:hover {
			transform: scale(1.1);
			box-shadow: 0 4px 12px rgba(139, 95, 191, 0.3);
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
			color: #8b5fbf;
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
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			color: #8b5fbf;
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
			color: #f093fb;
		}

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
			border-radius: 50%;
			background: linear-gradient(135deg, rgba(139, 95, 191, 0.1), rgba(240, 147, 251, 0.05));
			animation: floatUp linear infinite;
			opacity: 0.8;
		}

		.bubble:nth-child(odd) {
			background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(139, 95, 191, 0.05));
		}

		.bubble:nth-child(3n) {
			background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 95, 191, 0.05));
		}

		.bubble:nth-child(5n) {
			background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(240, 147, 251, 0.05));
		}

		@keyframes floatUp {
			from {
				transform: translateY(100vh) rotate(0deg);
				opacity: 0;
			}

			10% {
				opacity: 0.8;
			}

			90% {
				opacity: 0.8;
			}

			to {
				transform: translateY(-100px) rotate(360deg);
				opacity: 0;
			}
		}

		/* Dark mode bubble adjustments */
		body.dark-mode .bubble {
			background: linear-gradient(135deg, rgba(139, 95, 191, 0.4), rgba(240, 147, 251, 0.3));
			box-shadow: 0 0 20px rgba(139, 95, 191, 0.3);
		}

		body.dark-mode .bubble:nth-child(odd) {
			background: linear-gradient(135deg, rgba(240, 147, 251, 0.4), rgba(139, 95, 191, 0.3));
			box-shadow: 0 0 20px rgba(240, 147, 251, 0.3);
		}

		body.dark-mode .bubble:nth-child(3n) {
			background: linear-gradient(135deg, rgba(99, 102, 241, 0.4), rgba(139, 95, 191, 0.3));
			box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
		}

		body.dark-mode .bubble:nth-child(5n) {
			background: linear-gradient(135deg, rgba(236, 72, 153, 0.4), rgba(240, 147, 251, 0.3));
			box-shadow: 0 0 20px rgba(236, 72, 153, 0.3);
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
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
			color: white;
			text-decoration: none;
			border-radius: 12px;
			font-size: 1.1rem;
			font-weight: 600;
			transition: all 0.3s ease;
			box-shadow: 0 4px 15px rgba(139, 95, 191, 0.3);
		}

		.view-all-products-btn:hover {
			background: linear-gradient(135deg, #764ba2, #8b5fbf);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(139, 95, 191, 0.4);
			color: white;
		}

		.view-all-products-btn i {
			margin-right: 8px;
		}

		/* Category Navigation */
		.category-nav {
			background: white;
			border-top: 1px solid #e2e8f0;
			padding: 12px 0;
			position: sticky;
			top: 76px;
			z-index: 999;
		}

		.category-list {
			display: flex;
			align-items: center;
			gap: 8px;
			overflow-x: auto;
			padding: 0 16px;
			scrollbar-width: none;
			-ms-overflow-style: none;
		}

		.category-list::-webkit-scrollbar {
			display: none;
		}

		.category-item {
			white-space: nowrap;
			padding: 8px 16px;
			background: #f8fafc;
			border: 2px solid #e2e8f0;
			border-radius: 20px;
			color: #4b5563;
			text-decoration: none;
			font-weight: 500;
			font-size: 0.9rem;
			transition: all 0.3s ease;
			cursor: pointer;
		}

		.category-item:hover,
		.category-item.active {
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
			color: white;
			border-color: #8b5fbf;
			transform: translateY(-1px);
		}

		.category-item.featured {
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
			color: white;
			border-color: #8b5fbf;
		}

		/* Hero Section */
		.hero-section {
			background: linear-gradient(135deg, #f8f9ff 0%, #f1f5f9 50%, #e2e8f0 100%);
			padding: 60px 0;
			margin-top: 0px;
			position: relative;
			overflow: hidden;
			min-height: 60vh;
		}

		/* Main Semi-Circle Design (like login page) */
		.hero-circle {
			position: absolute;
			right: -450px;
			top: 50%;
			transform: translateY(-50%);
			width: 1200px;
			height: 1200px;
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			color: #8b5fbf;
			font-size: 1.1rem;
		}

		.cta-buttons {
			display: flex;
			gap: 16px;
			flex-wrap: wrap;
		}

		.cta-primary {
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			background: linear-gradient(135deg, #764ba2, #8b5fbf);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(139, 95, 191, 0.3);
			color: white;
		}

		.cta-secondary {
			background: white;
			color: #8b5fbf;
			padding: 14px 28px;
			border: 2px solid #8b5fbf;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 600;
			transition: all 0.3s ease;
		}

		.cta-secondary:hover {
			background: #8b5fbf;
			color: white;
			transform: translateY(-2px);
		}

		/* Promotion Cards */
		.promo-cards {
			display: flex;
			gap: 20px;
			margin-top: 40px;
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

		.promo-card.yellow {
			background: linear-gradient(135deg, #fbbf24, #f59e0b);
			color: #1f2937;
		}

		.promo-card.white {
			background: white;
			border: 2px solid #e5e7eb;
			color: #1f2937;
		}

		.promo-badge {
			background: linear-gradient(135deg, #f093fb, #8b5fbf);
			color: white;
			padding: 6px 16px;
			border-radius: 16px;
			font-size: 1rem;
			font-weight: 600;
			margin-bottom: 16px;
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
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
			color: white;
			padding: 12px 20px;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 500;
			font-size: 1rem;
			transition: all 0.3s ease;
		}

		.promo-btn:hover {
			background: linear-gradient(135deg, #764ba2, #8b5fbf);
			color: white;
			transform: scale(1.05);
		}

		/* Admin Panel Styles - Made bigger with purple theme */
		.admin-panel {
			background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
			color: #8b5fbf;
			padding: 16px 32px;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 600;
			font-size: 1.1rem;
			transition: all 0.3s ease;
		}

		.admin-btn:hover {
			background: rgba(255, 255, 255, 0.9);
			color: #764ba2;
			transform: translateY(-2px);
		}

		/* Mobile Responsiveness - Maintaining desktop layout proportions */
		@media (max-width: 768px) {
			.main-header {
				padding: 10px 0;
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

			.logo .co {
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
				gap: 10px;
			}

			.search-container {
				order: 2;
				width: 100%;
				min-width: auto;
			}

			.header-actions {
				order: 1;
				justify-content: space-between;
				width: 100%;
			}

			.logo {
				font-size: 1.2rem;
			}

			.search-input {
				padding: 9px 14px 9px 40px;
				font-size: 0.9rem;
			}

			.search-btn {
				padding: 5px 12px;
				font-size: 0.85rem;
			}

			.category-item {
				font-size: 0.8rem;
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

		/* Legacy menu tray (hidden) */
		.menu-tray {
			display: none;
		}
	</style>
</head>

<body>
	<!-- Floating Bubbles Background -->
	<div class="floating-bubbles" id="floatingBubbles"></div>

	<!-- Main Header -->
	<header class="main-header animate__animated animate__fadeInDown">
		<div class="container">
			<div class="d-flex align-items-center justify-content-between header-container">
				<!-- Logo -->
				<a href="#" class="logo">
					flavorhub<span class="co">co</span>
				</a>

				<!-- Search Bar -->
				<form class="search-container" method="GET" action="product_search_result.php">
					<i class="fas fa-search search-icon"></i>
					<input type="text" name="query" class="search-input" placeholder="Search products in FlavorHub" required>
					<button type="submit" class="search-btn">
						<i class="fas fa-search"></i>
					</button>
				</form>

				<!-- Header Actions -->
				<div class="header-actions">
					<!-- Navigation based on login and admin status -->
					<?php if (!$is_logged_in): ?>
						<!-- Not logged in: Register | Login -->
						<a href="login/register.php" class="login-btn me-2">Register</a>
						<a href="login/login.php" class="login-btn">Login</a>
					<?php elseif ($is_admin): ?>
						<!-- Admin logged in: Category | Brand | Add Product | Logout -->
						<a href="admin/category.php" class="login-btn me-2">Category</a>
						<a href="admin/brand.php" class="login-btn me-2">Brand</a>
						<a href="admin/product.php" class="login-btn me-2">Add Product</a>
						<a href="login/logout.php" class="logout-btn">Logout</a>
					<?php else: ?>
						<!-- Regular user logged in: Cart | Logout -->
						<div class="header-icon me-2">
							<a href="cart.php" style="color: inherit; text-decoration: none;">
								<i class="fas fa-shopping-cart"></i>
								<span class="cart-badge" id="cartBadge" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>"><?php echo $cart_count; ?></span>
							</a>
						</div>
						<a href="login/logout.php" class="logout-btn">Logout</a>
					<?php endif; ?>

					<!-- User Avatar (if logged in) -->
					<?php if ($is_logged_in): ?>
						<div class="user-dropdown ms-2">
							<div class="user-avatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>" onclick="toggleUserDropdown()">
								<?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
							</div>
							<div class="dropdown-menu-custom" id="userDropdownMenu">
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
								<a href="wishlist.php" class="dropdown-item-custom">
									<i class="fas fa-heart"></i>
									<span>Wishlist</span>
								</a>
								<div class="dropdown-divider-custom"></div>
								<a href="login/logout.php" class="dropdown-item-custom">
									<i class="fas fa-sign-out-alt"></i>
									<span>Logout</span>
								</a>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</header>

	<!-- Category Navigation -->
	<nav class="category-nav animate__animated animate__fadeInUp">
		<div class="container">
			<div class="category-list">
				<a href="all_product.php" class="category-item featured">All Products</a>
				<?php if (!empty($categories)): ?>
					<?php foreach (array_slice($categories, 0, 8) as $category): ?>
						<a href="all_product.php?cat_id=<?php echo $category['cat_id']; ?>" class="category-item">
							<?php echo htmlspecialchars($category['cat_name']); ?>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</nav>

	<!-- Hero Section -->
	<section class="hero-section">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-6">
					<div class="hero-content animate-fade-in">
						<h1 class="hero-title">
							Your <span class="hero-highlight">Fresh Food Hub</span><br>
							for Every Craving!
						</h1>
						<p class="hero-subtitle">
							Fresh ingredients, quick delivery,<br>
							and quality guaranteed from farm to table!
						</p>

						<div class="hero-features">
							<div class="feature-item">
								<i class="fas fa-shipping-fast feature-icon"></i>
								<span>Fresh Delivery</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-leaf feature-icon"></i>
								<span>Farm Fresh</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-headset feature-icon"></i>
								<span>24/7 Support</span>
							</div>
						</div>

						<div class="cta-buttons">
							<?php if (!$is_logged_in): ?>
								<a href="login/register.php" class="cta-primary">Get Started</a>
								<a href="#" class="cta-secondary">Browse Menu</a>
							<?php elseif ($is_admin): ?>
								<a href="admin/category.php" class="cta-primary">Manage Kitchen</a>
								<a href="#" class="cta-secondary">View Analytics</a>
							<?php else: ?>
								<a href="#" class="cta-primary">Order Now</a>
								<a href="#" class="cta-secondary">View Specials</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<!-- Promotional Cards -->
					<div class="promo-cards animate-slide-up">
						<div class="promo-card yellow">
							<div class="promo-badge">Summer Special</div>
							<div class="promo-title">Fresh<br>Summer<br>Produce</div>
							<div class="promo-subtitle">Get the freshest fruits & vegetables<br>at unbeatable prices</div>
							<a href="#" class="promo-btn">Shop fresh</a>
						</div>
						<div class="promo-card white">
							<div class="promo-badge">20% OFF</div>
							<div class="promo-title">For All<br>Organic<br>Products</div>
							<a href="#" class="promo-btn">Go organic</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Admin Panel (only visible to admins) -->
			<?php if ($is_admin): ?>
				<div class="admin-panel animate__animated animate__zoomIn">
					<h3>Chef Dashboard</h3>
					<p>Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Chef') ?>! Manage your kitchen.</p>
					<a href="admin/category.php" class="admin-btn">Manage Kitchen</a>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- Top Picks Section -->
	<section class="top-picks-section py-2">
		<div class="container">
			<div class="text-center mb-3">
				<h2 class="section-title">FlavorHub's Top Picks for You</h2>
				<p class="section-subtitle">Discover our most popular and trending products this week</p>
			</div>

			<div class="row" id="topPicksContainer">
				<div class="col-12 text-center">
					<div class="loading-spinner">
						<i class="fas fa-spinner fa-spin fa-2x" style="color: #8b5fbf;"></i>
						<p class="mt-3">Loading top picks...</p>
					</div>
				</div>
			</div>

			<div class="text-center mt-4">
				<a href="all_product.php" class="view-all-products-btn">
					<i class="fas fa-eye me-2"></i>
					View All Products
				</a>
			</div>
		</div>
	</section>

	<!-- Scripts -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="js/cart.js"></script>
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

		// Category navigation
		document.querySelectorAll('.category-item').forEach(item => {
			item.addEventListener('click', function(e) {
				e.preventDefault();

				// Remove active class from all items
				document.querySelectorAll('.category-item').forEach(cat => {
					cat.classList.remove('active');
				});

				// Add active class to clicked item
				this.classList.add('active');

				// Add your category filtering logic here
				console.log('Category selected:', this.textContent);
			});
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
			alert('Profile picture upload functionality will be implemented');
		}

		// Language change functionality
		function changeLanguage(language) {
			// Store language preference
			localStorage.setItem('selectedLanguage', language);
			console.log('Language changed to:', language);
			// Here you would implement actual language switching
			// Language change is silent now - no notification
		}

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

			// Create floating bubbles
			createFloatingBubbles();

			// Load top picks
			loadTopPicks();
		});

		// Create 40+ floating bubbles with different sizes and animations
		function createFloatingBubbles() {
			const bubblesContainer = document.getElementById('floatingBubbles');
			const bubbleCount = 50; // Create 50 bubbles

			for (let i = 0; i < bubbleCount; i++) {
				const bubble = document.createElement('div');
				bubble.className = 'bubble';

				// Create distinct size categories: small, medium, large
				let size;
				const sizeCategory = Math.random();
				if (sizeCategory < 0.5) {
					// 50% small bubbles (15-35px)
					size = Math.random() * 20 + 15;
					bubble.classList.add('bubble-small');
				} else if (sizeCategory < 0.8) {
					// 30% medium bubbles (35-60px)
					size = Math.random() * 25 + 35;
					bubble.classList.add('bubble-medium');
				} else {
					// 20% large bubbles (60-90px)
					size = Math.random() * 30 + 60;
					bubble.classList.add('bubble-large');
				}

				bubble.style.width = size + 'px';
				bubble.style.height = size + 'px';

				// Random horizontal position
				bubble.style.left = Math.random() * 100 + '%';

				// Animation duration based on size (larger bubbles float slower)
				let duration;
				if (size < 35) {
					duration = Math.random() * 8 + 12; // Small: 12-20s
				} else if (size < 60) {
					duration = Math.random() * 6 + 15; // Medium: 15-21s
				} else {
					duration = Math.random() * 4 + 18; // Large: 18-22s
				}
				bubble.style.animationDuration = duration + 's';

				// Random delay between 0s and 15s
				const delay = Math.random() * 15;
				bubble.style.animationDelay = delay + 's';

				// Opacity based on size (larger bubbles slightly more visible)
				let opacity;
				if (size < 35) {
					opacity = Math.random() * 0.3 + 0.4; // Small: 0.4-0.7
				} else if (size < 60) {
					opacity = Math.random() * 0.3 + 0.5; // Medium: 0.5-0.8
				} else {
					opacity = Math.random() * 0.2 + 0.6; // Large: 0.6-0.8
				}
				bubble.style.opacity = opacity;

				bubblesContainer.appendChild(bubble);
			}
		}

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
				const imagePath = 'uploads/products/' + product.product_image;

				const badges = ['Hot', 'Trending', 'Popular', 'Best Seller'];
				const ratings = [4.8, 4.9, 4.7, 4.6];

				const cardHtml = `
					<div class="col-lg-3 col-md-6 mb-4">
						<a href="single_product.php?id=${product.product_id}" class="top-pick-card">
							<div class="position-relative">
								<img src="${imagePath}" alt="${product.product_title}" class="pick-image"
									 onerror="this.src='https://via.placeholder.com/300x200/8b5fbf/ffffff?text=${encodeURIComponent(product.product_title)}'">
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
							<p class="pick-description">${product.product_desc || 'Delicious and fresh product from FlavorHub kitchen.'}</p>
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
						<i class="fas fa-utensils fa-2x mb-3" style="color: #8b5fbf;"></i>
						<h4>Coming Soon!</h4>
						<p>Our chefs are preparing amazing top picks for you.</p>
						<a href="all_product.php" class="btn btn-primary mt-3">Browse All Products</a>
					</div>
				</div>
			`;
		}
	</script>
</body>

</html>