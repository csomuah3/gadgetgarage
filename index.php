<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
	// Start session and include core functions
	require_once(__DIR__ . '/settings/core.php');
	require_once(__DIR__ . '/controllers/cart_controller.php');
	require_once(__DIR__ . '/controllers/wishlist_controller.php');
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

	// Check for payment success parameters
	$payment_success = isset($_GET['payment']) && $_GET['payment'] === 'success';
	$order_id_from_payment = isset($_GET['order']) ? intval($_GET['order']) : null;
	$payment_reference = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : null;

	// Get order details if payment was successful
	$order_details = null;
	if ($payment_success && $order_id_from_payment) {
		try {
			require_once(__DIR__ . '/controllers/order_controller.php');
			$order_details = get_order_by_id_ctr($order_id_from_payment);
			// Verify order belongs to user
			if ($order_details && isset($order_details['customer_id']) && $order_details['customer_id'] != $customer_id) {
				$order_details = null; // Don't show order if it doesn't belong to user
			}
		} catch (Exception $e) {
			error_log("Failed to load order details: " . $e->getMessage());
		}
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

	// Fetch Featured on IG products
	$featured_ig_products = [];
	try {
		require_once(__DIR__ . '/controllers/product_controller.php');
		require_once(__DIR__ . '/helpers/image_helper.php');

		// Find the "As featured on IG" category
		$featured_ig_category = null;
		$possible_names = ['As featured on IG', 'As Featured on IG', 'as featured on ig', 'Featured on IG', 'featured on ig', 'Instagram Featured'];

		foreach ($categories as $cat) {
			$cat_name = trim($cat['cat_name']);
			foreach ($possible_names as $name) {
				if (strtolower($cat_name) === strtolower(trim($name))) {
					$featured_ig_category = $cat;
					break 2;
				}
			}
		}

		// If category found, get products (max 5)
		if ($featured_ig_category) {
			$all_ig_products = get_products_by_category_ctr($featured_ig_category['cat_id']);
			$featured_ig_products = array_slice($all_ig_products, 0, 5);

			// Enrich products with image URLs
			foreach ($featured_ig_products as &$product) {
				$product['image_url'] = get_product_image_url(
					$product['product_image'] ?? '',
					$product['product_title'] ?? ''
				);
			}
			unset($product);
		}
	} catch (Exception $e) {
		error_log("Failed to load Featured on IG products: " . $e->getMessage());
	}

	// Newsletter popup logic for new users
	$show_newsletter_popup = false;
	if ($is_logged_in) {
		try {
			require_once(__DIR__ . '/helpers/newsletter_helper.php');
			$is_new_session = is_new_login_session();
			$should_show = should_show_newsletter_popup($customer_id);
			$show_newsletter_popup = $is_new_session && $should_show;

			// Debug logging
			error_log("Newsletter debug - Customer ID: $customer_id, New session: " . ($is_new_session ? 'yes' : 'no') . ", Should show: " . ($should_show ? 'yes' : 'no') . ", Final show: " . ($show_newsletter_popup ? 'yes' : 'no'));
		} catch (Exception $e) {
			error_log("Newsletter popup error: " . $e->getMessage());
		}
	} else {
		error_log("Newsletter debug - User not logged in");
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
	<link href="includes/header-styles.css" rel="stylesheet">
	<link href="includes/chatbot-styles.css" rel="stylesheet">
	<link href="css/dark-mode.css" rel="stylesheet">
	<link href="views/circular-gallery.css" rel="stylesheet">
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

		/* Promotional Banner Styles - Same as index */
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
			font-size: 1.1rem;
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			border: 1px solid rgba(37, 99, 235, 0.1);
			box-shadow: 0 8px 32px rgba(30, 58, 95, 0.08);
			height: 100%;
			display: flex;
			flex-direction: column;
		}

		.top-pick-card:hover {
			transform: translateY(-10px) scale(1.02);
			box-shadow: 0 16px 48px rgba(30, 58, 95, 0.12);
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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

		body.dark-mode .promo-banner2 {
			background: #0f1419 !important;
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
			color: #2563EB;
		}

		body.dark-mode .hero-section {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
		}

		body.dark-mode .promo-card {
			background: rgba(70, 80, 100, 0.9);
			backdrop-filter: blur(20px);
			border: 1px solid rgba(37, 99, 235, 0.3);
		}

		body.dark-mode .dropdown-menu-custom {
			background: rgba(70, 80, 100, 0.95);
			border-color: rgba(37, 99, 235, 0.5);
		}

		body.dark-mode .dropdown-item-custom {
			color: #cbd5e0;
		}

		body.dark-mode .dropdown-item-custom:hover {
			background: rgba(37, 99, 235, 0.2);
			color: #006b4e;
		}







		/* Dark mode top picks section */
		body.dark-mode .top-picks-section {
			background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
		}

		body.dark-mode .top-pick-card {
			background: rgba(70, 80, 100, 0.9);
			border: 1px solid rgba(37, 99, 235, 0.3);
		}

		/* View All Products Button */
		.view-all-products-btn {
			display: inline-flex;
			align-items: center;
			padding: 15px 30px;
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
			color: white;
			text-decoration: none;
			border-radius: 12px;
			font-size: 1.1rem;
			font-weight: 600;
			transition: all 0.3s ease;
			box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
		}

		.view-all-products-btn:hover {
			background: linear-gradient(135deg, #2563EB, #1E3A5F);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
			color: white;
		}

		.view-all-products-btn i {
			margin-right: 8px;
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
			grid-template-columns: 3.5fr 1fr;
			/* much wider left + narrow right */
			gap: 28px;
			/* spacing between cards */
			align-items: stretch;
			min-height: 620px;
			/* increased height for better text arrangement */
		}

		/* ——— Hero Carousel Wrapper ——— */
		.hero-carousel-wrapper {
			position: relative;
			width: 100%;
			height: 100%;
			min-height: 620px;
			border-radius: 14px;
			overflow: hidden;
			background: #f0f4f8;
			/* Fallback background */
		}

		.hero-carousel {
			position: relative;
			width: 100%;
			height: 100%;
		}

		/* ——— Hero Slide (Main Banner) ——— */
		.hero-slide {
			display: none;
			grid-template-columns: 1.5fr 1fr;
			gap: 40px;
			padding: 60px 60px;
			border-radius: 14px;
			overflow: hidden;
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}

		.hero-slide.active {
			display: grid !important;
		}

		/* Apple-style Product Gradients - Premium & Sophisticated */
		.hero-slide[data-gradient="ipad-gradient"] {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #8e9aaf 100%) !important;
			color: #ffffff !important;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
		}

		.hero-slide[data-gradient="iphone-gradient"] {
			background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%) !important;
			color: #ffffff !important;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
		}

		.hero-slide[data-gradient="polaroid-gradient"] {
			background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 30%, #ff8a80 70%, #ff7043 100%) !important;
			color: #ffffff !important;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
		}

		.hero-slide[data-gradient="samsung-gradient"] {
			background: linear-gradient(135deg, #2c3e50 0%, #34495e 30%, #5d4e75 60%, #a0416e 100%) !important;
			color: #ffffff !important;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
		}

		/* Consistent white text for all gradients - Apple style */
		.hero-slide .text-line {
			color: #ffffff !important;
			text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
		}

		.banner-copy {
			display: flex;
			flex-direction: column;
			justify-content: center;
			gap: 24px;
			padding: 20px 0;
		}

		/* Brand Logo Section */
		.brand-logo-section {
			margin-bottom: 16px;
			opacity: 0;
			transform: translateY(15px);
			transition: opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1) 0.05s,
				transform 0.8s cubic-bezier(0.23, 1, 0.32, 1) 0.05s;
		}

		.hero-slide.active .brand-logo-section {
			opacity: 1;
			transform: translateY(0);
		}

		.hero-slide.exiting .brand-logo-section {
			opacity: 0;
			transform: translateY(-10px);
			transition-delay: 0s;
			transition-duration: 0.3s;
		}

		.brand-logo {
			height: 32px;
			width: auto;
			filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
		}

		.apple-logo {
			height: 36px;
		}

		.fujifilm-logo,
		.samsung-logo {
			font-family: 'Arial', sans-serif;
			font-size: 20px;
			font-weight: 800;
			letter-spacing: 2px;
			color: white;
			text-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
			padding: 8px 16px;
			background: rgba(255, 255, 255, 0.1);
			border-radius: 20px;
			backdrop-filter: blur(10px);
			border: 1px solid rgba(255, 255, 255, 0.2);
			display: inline-block;
		}

		/* Vertically stacked text - Left aligned */
		.banner-text-stack {
			display: flex;
			flex-direction: column;
			gap: 4px;
			text-align: left;
			align-items: flex-start;
		}

		.text-line {
			font-size: clamp(42px, 6vw, 64px);
			font-weight: 400;
			line-height: 1.2;
			color: inherit;
			margin: 0;
			transition: opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1),
				transform 0.8s cubic-bezier(0.23, 1, 0.32, 1);
			transform: translateY(0);
			font-family: 'Georgia', 'Times New Roman', serif;
			letter-spacing: 0.5px;
		}

		/* Enhanced staggered delays for text lines */
		.hero-slide .text-line:nth-child(1) {
			transition-delay: 0.1s;
		}

		.hero-slide .text-line:nth-child(2) {
			transition-delay: 0.18s;
		}

		.hero-slide .text-line:nth-child(3) {
			transition-delay: 0.26s;
		}

		.hero-slide .text-line:nth-child(4) {
			transition-delay: 0.34s;
		}

		.hero-slide .text-line:nth-child(5) {
			transition-delay: 0.42s;
		}

		.hero-slide .text-line:nth-child(6) {
			transition-delay: 0.5s;
		}

		.hero-slide .text-line:nth-child(7) {
			transition-delay: 0.58s;
		}

		.hero-slide .text-line:nth-child(8) {
			transition-delay: 0.66s;
		}

		.hero-slide:not(.active) .text-line {
			opacity: 0;
			transform: translateY(25px) translateX(-8px);
		}

		.hero-slide.active .text-line {
			opacity: 1;
			transform: translateY(0) translateX(0);
		}

		/* Exiting slide text animation */
		.hero-slide.exiting .text-line {
			opacity: 0;
			transform: translateY(-15px) translateX(8px);
			transition-duration: 0.4s;
		}

		/* Typography Hierarchy - Reduced Sizes */
		.brand-name {
			font-size: clamp(18px, 2.5vw, 26px);
			font-weight: 300;
			letter-spacing: 2px;
			text-transform: uppercase;
			opacity: 0.9;
		}

		.product-name {
			font-size: clamp(32px, 4.5vw, 48px);
			font-weight: 700;
			letter-spacing: -1px;
			margin-bottom: 4px;
		}

		.product-desc {
			font-size: clamp(14px, 1.5vw, 18px);
			font-weight: 400;
			line-height: 1.4;
			opacity: 0.9;
			font-style: italic;
			margin-bottom: 16px;
			max-width: 80%;
		}

		.tagline-1,
		.tagline-2,
		.tagline-3,
		.tagline-4 {
			font-size: clamp(24px, 3.5vw, 36px);
			font-weight: 500;
			letter-spacing: 0px;
		}

		.price-line {
			font-size: clamp(16px, 2vw, 24px);
			font-weight: 600;
			margin-top: 8px;
			opacity: 0.8;
			text-transform: uppercase;
			letter-spacing: 1px;
		}

		.price-amount {
			font-size: clamp(22px, 3vw, 34px);
			font-weight: 800;
			color: #FFD700;
			text-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
		}

		/* Social Media Buttons */
		.social-buttons {
			display: flex;
			gap: 12px;
			margin: 16px 0;
			opacity: 0;
			transform: translateY(15px);
			transition: opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1) 0.7s,
				transform 0.8s cubic-bezier(0.23, 1, 0.32, 1) 0.7s;
		}

		.hero-slide.active .social-buttons {
			opacity: 1;
			transform: translateY(0);
		}

		.hero-slide.exiting .social-buttons {
			opacity: 0;
			transform: translateY(-10px);
			transition-delay: 0s;
			transition-duration: 0.3s;
		}

		.social-btn {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			text-decoration: none;
			transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
			background: rgba(255, 255, 255, 0.2);
			color: inherit;
			border: 1px solid rgba(255, 255, 255, 0.3);
			backdrop-filter: blur(10px);
		}

		.social-btn:hover {
			background: rgba(255, 255, 255, 0.3);
			transform: scale(1.08) translateY(-1px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
		}

		.social-btn i {
			font-size: 18px;
		}

		.hero-slide[data-gradient="iphone-gradient"] .social-btn,
		.hero-slide[data-gradient="polaroid-gradient"] .social-btn {
			background: rgba(0, 0, 0, 0.1);
			border-color: rgba(0, 0, 0, 0.2);
			color: inherit;
		}

		.hero-slide[data-gradient="iphone-gradient"] .social-btn:hover,
		.hero-slide[data-gradient="polaroid-gradient"] .social-btn:hover {
			background: rgba(0, 0, 0, 0.15);
		}

		.btn-primary {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			height: 56px;
			padding: 0 28px;
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
			color: #fff;
			border-radius: 10px;
			font-weight: 700;
			letter-spacing: .2px;
			text-decoration: none;
			transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
			box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
			width: fit-content;
			margin-top: 8px;
			opacity: 0;
			transform: translateY(20px);
		}

		.hero-slide.active .btn-primary {
			opacity: 1;
			transform: translateY(0);
			transition-delay: 0.8s;
		}

		.hero-slide.exiting .btn-primary {
			opacity: 0;
			transform: translateY(-10px);
			transition-delay: 0s;
			transition-duration: 0.3s;
		}

		.btn-primary:hover {
			background: linear-gradient(135deg, #2563EB, #1e40af);
			transform: translateY(-3px);
			box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5);
		}

		.hero-slide[data-gradient="samsung-gradient"] .btn-primary {
			background: linear-gradient(135deg, #ffffff, #f0f0f0);
			color: #0d4a2e;
			box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
		}

		.hero-slide[data-gradient="samsung-gradient"] .btn-primary:hover {
			background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
			color: #1a6b47;
		}

		.banner-media {
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			height: 100%;
			min-height: 350px;
		}

		.banner-media .product-image {
			width: auto;
			height: auto;
			max-height: 480px;
			min-height: 420px;
			max-width: 100%;
			object-fit: contain;
			transform: translateY(0) translateX(0);
			transition: opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1),
				transform 0.8s cubic-bezier(0.23, 1, 0.32, 1);
		}

		/* Image animations - fade out for exiting */
		.hero-slide.exiting .product-image {
			opacity: 0;
			transform: translateY(0) translateX(-30px) scale(0.95);
			transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.23, 1, 0.32, 1);
		}

		/* Image animations - initial state (hidden, off to the right) */
		.hero-slide:not(.active):not(.exiting) .product-image {
			opacity: 0;
			transform: translateY(0) translateX(60px) scale(0.9);
		}

		/* Image animations - active state (visible, animated entrance) */
		.hero-slide.active .product-image {
			opacity: 1;
			transform: translateY(0) translateX(0) scale(1);
			transition: opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1) 0.6s,
				transform 1s cubic-bezier(0.23, 1, 0.32, 1) 0.6s;
		}

		/* Navigation Dots */
		.hero-dots {
			position: absolute;
			bottom: 20px;
			left: 50%;
			transform: translateX(-50%);
			display: flex;
			gap: 12px;
			z-index: 20;
		}

		.carousel-dot {
			width: 12px;
			height: 12px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.4);
			cursor: pointer;
			transition: all 0.3s ease;
			border: 2px solid transparent;
		}

		.carousel-dot.active {
			background: rgba(255, 255, 255, 0.9);
			transform: scale(1.2);
		}

		.carousel-dot:hover {
			background: rgba(255, 255, 255, 0.7);
			transform: scale(1.1);
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
			font-weight: 900;
			margin-bottom: 40px;
			text-align: center;
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

		.deal-image-container {
			width: 100%;
			height: 200px;
			margin-bottom: 20px;
			border-radius: 12px;
			background: #f8f9fa;
			padding: 20px;
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
			border: 3px solid #e5e7eb;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		}

		.deal-image {
			width: 100%;
			height: 100%;
			object-fit: contain;
			transition: transform 0.3s ease;
		}

		/* Frame for images without container */
		.deal-card>img.deal-image {
			border: 3px solid #e5e7eb;
			border-radius: 12px;
			padding: 20px;
			background: #f8f9fa;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
			margin-bottom: 20px;
		}

		.deal-image-container:hover .deal-image {
			transform: rotate(-3deg) scale(1.05);
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

		.wishlist-btn.active i {
			color: #ef4444 !important;
		}

		.wishlist-btn.active {
			background: rgba(239, 68, 68, 0.1) !important;
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




		/* TESTIMONIALS — Circular Gallery */
		.testimonials {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
			padding: 80px 0 120px;
			min-height: 700px;
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

		/* Header Section - Top and Centered */
		.testimonials-header {
			text-align: center;
			margin-bottom: 60px;
			position: relative;
			z-index: 2;
		}

		.testimonials-title {
			color: #fff;
			font-size: 3.5rem;
			font-weight: 900;
			margin-bottom: 15px;
			font-family: 'Inter', sans-serif;
			letter-spacing: -0.02em;
			line-height: 1.1;
		}

		.testimonials-subtitle {
			color: rgba(255, 255, 255, 0.9);
			font-size: 1.4rem;
			font-weight: 400;
			font-family: 'Inter', sans-serif;
		}

		/* Circular Gallery Wrapper */
		.circular-testimonials-wrapper {
			width: 100%;
			height: 600px;
			min-height: 600px;
			position: relative;
			overflow: visible !important;
			cursor: grab;
			background: transparent;
			margin: 40px auto;
			display: block;
		}

		.circular-testimonials-wrapper:active {
			cursor: grabbing;
		}

		.circular-testimonials-track {
			position: relative;
			width: 100%;
			height: 100%;
			overflow: visible;
		}

		/* Individual Testimonial Card */
		.circular-testimonial-card {
			position: absolute !important;
			width: 350px;
			height: 320px;
			border-radius: 20px;
			border: 1px solid rgba(255, 255, 255, 0.2);
			background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.08)) !important;
			backdrop-filter: blur(20px);
			padding: 30px;
			color: #fff !important;
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
			transition: transform 0.3s ease, opacity 0.3s ease;
			display: flex !important;
			flex-direction: column;
			justify-content: space-between;
			visibility: visible !important;
			opacity: 1 !important;
			pointer-events: auto;
			z-index: 10;
		}

		.circular-testimonial-card:hover {
			transform: translateZ(30px) scale(1.05);
			box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
		}

		.circular-testimonial-quote {
			font-size: 1.1rem;
			line-height: 1.6;
			margin-bottom: 20px;
			font-style: italic;
			color: #fff;
			font-weight: 400;
			flex: 1;
		}

		.circular-star-rating {
			display: flex;
			gap: 4px;
			margin-bottom: 20px;
		}

		.circular-star {
			color: #ffd700;
			font-size: 1.2rem;
		}

		.circular-testimonial-author {
			display: flex;
			flex-direction: column;
			gap: 5px;
			border-top: 1px solid rgba(255, 255, 255, 0.2);
			padding-top: 15px;
		}

		.circular-author-name {
			margin: 0;
			font-size: 1.1rem;
			color: #fff;
			font-weight: 600;
		}

		.circular-author-location {
			margin: 0;
			color: rgba(255, 255, 255, 0.8);
			font-size: 0.95rem;
			font-weight: 400;
		}

		@media (max-width: 1200px) {
			.testimonials-title {
				font-size: 3rem;
			}

			.testimonials-subtitle {
				font-size: 1.2rem;
			}

			.circular-testimonial-card {
				width: 340px;
				height: 260px;
				padding: 25px;
			}
		}

		@media (max-width: 768px) {
			.testimonials {
				padding: 60px 0 100px;
				min-height: 600px;
			}

			.testimonials-title {
				font-size: 2.5rem;
			}

			.testimonials-subtitle {
				font-size: 1.1rem;
			}

			.circular-testimonials-wrapper {
				height: 500px;
			}

			.circular-testimonial-card {
				width: 300px;
				height: 240px;
				padding: 20px;
			}

			.circular-testimonial-quote {
				font-size: 1rem;
			}
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			color: #2563EB;
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
			color: #fff;
		}

		.language-btn-confirm:hover {
			background: linear-gradient(135deg, #2563EB, #1E3A5F);
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			color: #2563EB;
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
			color: #2563EB;
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

		/* Featured on IG Section - Carousel Style */
		.featured-ig-section {
			padding: 80px 0;
			background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
			position: relative;
			overflow: hidden;
		}

		.featured-ig-section::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: radial-gradient(circle at 20% 50%, rgba(138, 43, 226, 0.03) 0%, transparent 50%),
				radial-gradient(circle at 80% 80%, rgba(255, 20, 147, 0.03) 0%, transparent 50%);
			pointer-events: none;
		}

		.featured-ig-title {
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			text-align: center;
			margin-bottom: 50px;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 15px;
		}

		.featured-ig-title i {
			background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
			font-size: 2.8rem;
		}

		/* Carousel Wrapper */
		.featured-ig-carousel-wrapper {
			position: relative;
			max-width: 1400px;
			margin: 0 auto;
			padding: 0 60px;
		}

		.featured-ig-carousel {
			display: flex;
			gap: 30px;
			overflow-x: auto;
			scroll-behavior: smooth;
			scroll-snap-type: x mandatory;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: none;
			-ms-overflow-style: none;
			padding: 20px 0;
			cursor: grab;
		}

		.featured-ig-carousel:active {
			cursor: grabbing;
		}

		.featured-ig-carousel::-webkit-scrollbar {
			display: none;
		}

		/* Instagram Phone Frame */
		.featured-ig-slide {
			flex: 0 0 320px;
			scroll-snap-align: center;
			position: relative;
		}

		.ig-phone-frame {
			background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
			border-radius: 35px;
			padding: 12px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3),
				0 0 0 2px rgba(255, 255, 255, 0.1) inset;
			position: relative;
			transition: transform 0.3s ease, box-shadow 0.3s ease;
		}

		.ig-phone-frame::before {
			content: '';
			position: absolute;
			top: 15px;
			left: 50%;
			transform: translateX(-50%);
			width: 60px;
			height: 6px;
			background: #333;
			border-radius: 3px;
			z-index: 10;
		}

		.ig-phone-frame:hover {
			transform: translateY(-10px) scale(1.02);
			box-shadow: 0 25px 70px rgba(138, 43, 226, 0.4),
				0 0 0 3px rgba(138, 43, 226, 0.2) inset;
		}

		.ig-screen {
			background: #000;
			border-radius: 25px;
			overflow: hidden;
			position: relative;
		}

		.featured-ig-link {
			text-decoration: none;
			color: inherit;
			display: block;
		}

		/* Image Container with Instagram Gradient */
		.ig-image-container {
			position: relative;
			width: 100%;
			height: 350px;
			overflow: hidden;
			background: #1a1a1a;
		}

		.ig-image {
			width: 100%;
			height: 100%;
			object-fit: cover;
			transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
		}

		.ig-phone-frame:hover .ig-image {
			transform: scale(1.15);
		}

		/* Instagram Gradient Overlay */
		.ig-gradient-overlay {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: linear-gradient(135deg,
					rgba(240, 148, 51, 0.3) 0%,
					rgba(230, 104, 60, 0.3) 25%,
					rgba(220, 39, 67, 0.3) 50%,
					rgba(204, 35, 102, 0.3) 75%,
					rgba(188, 24, 136, 0.3) 100%);
			opacity: 0;
			transition: opacity 0.4s ease;
			pointer-events: none;
		}

		.ig-phone-frame:hover .ig-gradient-overlay {
			opacity: 1;
		}

		/* Instagram Icon */
		.ig-instagram-icon {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%) scale(0.8);
			opacity: 0;
			transition: all 0.4s ease;
			z-index: 2;
		}

		.ig-instagram-icon i {
			font-size: 4rem;
			background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
			filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.5));
		}

		.ig-phone-frame:hover .ig-instagram-icon {
			opacity: 1;
			transform: translate(-50%, -50%) scale(1);
		}

		/* Placeholder for empty frames */
		.ig-placeholder {
			width: 100%;
			height: 100%;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
		}

		.ig-placeholder i {
			font-size: 4rem;
			margin-bottom: 15px;
			opacity: 0.7;
		}

		.ig-placeholder p {
			font-size: 1.1rem;
			font-weight: 600;
			margin: 0;
		}

		/* Content Section */
		.ig-content {
			padding: 20px;
			background: white;
		}

		.ig-product-title {
			font-size: 1rem;
			font-weight: 600;
			color: #1f2937;
			margin-bottom: 10px;
			line-height: 1.4;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			line-clamp: 2;
			-webkit-box-orient: vertical;
			overflow: hidden;
		}

		.ig-price {
			font-size: 1.3rem;
			font-weight: 700;
			color: #2563EB;
			margin-bottom: 15px;
		}

		.ig-add-cart-btn {
			width: 100%;
			padding: 12px 20px;
			background: linear-gradient(135deg, #2563EB 0%, #1e40af 100%);
			color: white;
			border: none;
			border-radius: 8px;
			font-weight: 600;
			font-size: 0.95rem;
			cursor: pointer;
			transition: all 0.3s ease;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
		}

		.ig-add-cart-btn:hover {
			background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
		}

		/* Carousel Navigation */
		.ig-carousel-prev,
		.ig-carousel-next {
			position: absolute;
			top: 50%;
			transform: translateY(-50%);
			background: white;
			border: 2px solid #e5e7eb;
			border-radius: 50%;
			width: 50px;
			height: 50px;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: all 0.3s ease;
			z-index: 10;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
		}

		.ig-carousel-prev {
			left: 0;
		}

		.ig-carousel-next {
			right: 0;
		}

		.ig-carousel-prev:hover,
		.ig-carousel-next:hover {
			background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
			border-color: transparent;
			color: white;
			transform: translateY(-50%) scale(1.1);
			box-shadow: 0 6px 20px rgba(138, 43, 226, 0.3);
		}

		.ig-carousel-prev i,
		.ig-carousel-next i {
			font-size: 1.2rem;
		}

		/* Responsive */
		@media (max-width: 768px) {
			.featured-ig-carousel-wrapper {
				padding: 0 50px;
			}

			.featured-ig-slide {
				flex: 0 0 280px;
			}

			.ig-image-container {
				height: 300px;
			}

			.ig-carousel-prev,
			.ig-carousel-next {
				width: 40px;
				height: 40px;
			}
		}

		box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
		}

		.featured-ig-add-cart:active {
			transform: translateY(0);
		}

		/* Mobile Responsive */
		@media (max-width: 768px) {
			.featured-ig-title {
				font-size: 2rem;
			}

			.featured-ig-title i {
				font-size: 2.2rem;
			}

			.featured-ig-grid {
				grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
				gap: 20px;
				padding: 0 15px;
			}

			.featured-ig-image-wrapper {
				height: 220px;
			}

			.featured-ig-content {
				padding: 15px;
			}

			.featured-ig-product-title {
				font-size: 0.9rem;
			}

			.featured-ig-price {
				font-size: 1.1rem;
			}
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
			color: #2563EB;
			border: 2px solid #008060;
			padding: 12px 24px;
			border-radius: 8px;
			font-weight: 600;
			text-decoration: none;
			display: inline-block;
			transition: all 0.3s ease;
		}

		.btn-outline-primary:hover {
			background: #2563EB;
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

		.promo-banner2 {
			display: flex;
			align-items: center;
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

		/* main banner container */
		.promo-banner {
			display: flex;
			align-items: center;
			height: 450px;
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
			;
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			color: #2563EB;
			font-size: 1.1rem;
		}

		.cta-buttons {
			display: flex;
			gap: 16px;
			flex-wrap: wrap;
		}

		.cta-primary {
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			background: linear-gradient(135deg, #2563EB, #1E3A5F);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
			color: white;
		}

		.cta-secondary {
			background: white;
			color: #2563EB;
			padding: 14px 28px;
			border: 2px solid #008060;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 600;
			transition: all 0.3s ease;
		}

		.cta-secondary:hover {
			background: #2563EB;
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
			background: linear-gradient(135deg, #2563EB, #1E3A5F);
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
			color: white;
			padding: 12px 20px;
			border-radius: 25px;
			text-decoration: none;
			font-weight: 500;
			font-size: 1rem;
			transition: all 0.3s ease;
		}

		.promo-btn:hover {
			background: linear-gradient(135deg, #2563EB, #1E3A5F);
			color: white;
			transform: scale(1.05);
		}

		/* Admin Panel Styles - Made bigger with purple theme */
		.admin-panel {
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			color: #2563EB;
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

		/* Newsletter Section Styles */
		.newsletter-section {
			background: linear-gradient(135deg, #008060 0%, #006b4e 100%);
			padding: 60px 0;
			margin-top: 80px;
			position: relative;
			overflow: hidden;
		}

		.newsletter-section::before {
			content: '';
			position: absolute;
			top: -50%;
			right: -10%;
			width: 400px;
			height: 400px;
			background: rgba(255, 255, 255, 0.1);
			border-radius: 50%;
			z-index: 0;
		}

		.newsletter-section::after {
			content: '';
			position: absolute;
			bottom: -30%;
			left: -5%;
			width: 300px;
			height: 300px;
			background: rgba(255, 255, 255, 0.08);
			border-radius: 50%;
			z-index: 0;
		}

		.newsletter-container {
			position: relative;
			z-index: 1;
			max-width: 800px;
			margin: 0 auto;
			text-align: center;
		}

		.newsletter-icon-wrapper {
			width: 80px;
			height: 80px;
			background: rgba(255, 255, 255, 0.2);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 25px;
			backdrop-filter: blur(10px);
		}

		.newsletter-icon-wrapper i {
			font-size: 2.5rem;
			color: white;
		}

		.newsletter-title {
			color: white;
			font-size: 2.2rem;
			font-weight: 700;
			margin-bottom: 15px;
			line-height: 1.2;
		}

		.newsletter-description {
			color: rgba(255, 255, 255, 0.95);
			font-size: 1.1rem;
			margin-bottom: 35px;
			line-height: 1.6;
		}

		.newsletter-form {
			display: flex;
			gap: 12px;
			max-width: 600px;
			margin: 0 auto;
			background: white;
			border-radius: 50px;
			padding: 8px;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
		}

		.newsletter-input {
			flex: 1;
			border: none;
			padding: 18px 25px;
			font-size: 1rem;
			border-radius: 50px;
			outline: none;
			color: #1f2937;
		}

		.newsletter-input::placeholder {
			color: #9ca3af;
		}

		.newsletter-submit-btn {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			border: none;
			padding: 18px 35px;
			border-radius: 50px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			white-space: nowrap;
			box-shadow: 0 4px 15px rgba(0, 128, 96, 0.3);
		}

		.newsletter-submit-btn:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(0, 128, 96, 0.4);
		}

		.newsletter-submit-btn:active {
			transform: translateY(0);
		}

		.newsletter-submit-btn:disabled {
			opacity: 0.7;
			cursor: not-allowed;
		}

		.newsletter-message {
			margin-top: 20px;
			padding: 12px 20px;
			border-radius: 8px;
			font-size: 0.95rem;
			display: none;
		}

		.newsletter-message.success {
			background: rgba(16, 185, 129, 0.2);
			color: white;
			border: 1px solid rgba(16, 185, 129, 0.4);
			display: block;
		}

		.newsletter-message.error {
			background: rgba(239, 68, 68, 0.2);
			color: white;
			border: 1px solid rgba(239, 68, 68, 0.4);
			display: block;
		}

		.newsletter-privacy {
			margin-top: 20px;
			color: rgba(255, 255, 255, 0.8);
			font-size: 0.85rem;
		}

		.newsletter-privacy i {
			margin-right: 5px;
		}

		@media (max-width: 768px) {
			.newsletter-title {
				font-size: 1.8rem;
			}

			.newsletter-description {
				font-size: 1rem;
			}

			.newsletter-form {
				flex-direction: column;
				border-radius: 15px;
				padding: 15px;
			}

			.newsletter-input {
				border-radius: 12px;
				padding: 15px 20px;
			}

			.newsletter-submit-btn {
				border-radius: 12px;
				padding: 15px 25px;
				width: 100%;
			}
		}

		/* Footer Styles */
		.main-footer {
			background: #ffffff;
			border-top: 1px solid #e5e7eb;
			padding: 60px 0 20px;
			margin-top: 0;
		}

		.footer-logo {
			font-size: 1.8rem;
			font-weight: 700;
			color: #1f2937;
			margin-bottom: 16px;
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
			background: #2563EB;
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
			background: #2563EB;
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
			background: #2563EB;
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
			background: #2563EB;
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
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
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
			background: #2563EB;
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
	<div class="promo-banner2">
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
								<a href="views/account.php" class="dropdown-item-custom">
									<i class="fas fa-user"></i>
									<span data-translate="account">Account</span>
								</a>
								<a href="views/my_orders.php" class="dropdown-item-custom">
									<i class="fas fa-shopping-bag"></i>
									<span data-translate="my_orders">My Orders</span>
								</a>
								<a href="track_order.php" class="dropdown-item-custom">
									<i class="fas fa-truck"></i>
									<span data-translate="track_orders">Track Orders</span>
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

	<!-- Hero Banner Section (matching demo) -->
	<section class="hero-banner-section">
		<div class="container">
			<div class="hero-grid">
				<!-- LEFT: MAIN BANNER CAROUSEL -->
				<div class="hero-carousel-wrapper">
					<div class="hero-carousel" id="heroCarousel">
						<!-- Product 1: iPad -->
						<article class="hero-slide active" data-product="ipad" data-gradient="ipad-gradient">
							<div class="banner-copy">
								<div class="brand-logo-section">
									<img src="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'><path d='M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z'/></svg>" alt="Apple" class="brand-logo apple-logo" />
								</div>
								<div class="banner-text-stack">
									<div class="text-line brand-name">Apple</div>
									<div class="text-line product-name">iPad Pro</div>
									<div class="text-line product-desc">Powerful performance meets portability in this refurbished iPad Pro</div>
									<div class="text-line tagline-1">SMART TECH</div>
									<div class="text-line tagline-2">SMARTER SPENDING</div>
									<div class="text-line price-line">Starting At</div>
									<div class="text-line price-amount">GH₵ 2,500.00</div>
								</div>
								<div class="social-buttons">
									<a href="#" class="social-btn instagram" aria-label="Share on Instagram"><i class="fab fa-instagram"></i></a>
									<a href="#" class="social-btn facebook" aria-label="Share on Facebook"><i class="fab fa-facebook"></i></a>
									<a href="#" class="social-btn twitter" aria-label="Share on Twitter"><i class="fab fa-twitter"></i></a>
								</div>
								<a href="views/all_product.php?category=ipads" class="btn-primary"><span data-translate="shop_now">SHOP NOW</span></a>
							</div>
							<div class="banner-media">
								<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/ipad-removebg-preview.png" alt="Apple iPad Pro" class="product-image" />
							</div>
						</article>

						<!-- Product 2: iPhone -->
						<article class="hero-slide" data-product="iphone" data-gradient="iphone-gradient">
							<div class="banner-copy">
								<div class="brand-logo-section">
									<img src="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'><path d='M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z'/></svg>" alt="Apple" class="brand-logo apple-logo" />
								</div>
								<div class="banner-text-stack">
									<div class="text-line brand-name">Apple</div>
									<div class="text-line product-name">iPhone</div>
									<div class="text-line product-desc">Experience premium features and reliability in this certified refurbished iPhone</div>
									<div class="text-line tagline-1">SMART TECH</div>
									<div class="text-line tagline-2">SMARTER SPENDING</div>
									<div class="text-line price-line">Starting At</div>
									<div class="text-line price-amount">GH₵ 1,800.00</div>
								</div>
								<div class="social-buttons">
									<a href="#" class="social-btn instagram" aria-label="Share on Instagram"><i class="fab fa-instagram"></i></a>
									<a href="#" class="social-btn facebook" aria-label="Share on Facebook"><i class="fab fa-facebook"></i></a>
									<a href="#" class="social-btn twitter" aria-label="Share on Twitter"><i class="fab fa-twitter"></i></a>
								</div>
								<a href="views/all_product.php?category=smartphones" class="btn-primary"><span data-translate="shop_now">SHOP NOW</span></a>
							</div>
							<div class="banner-media">
								<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/iphone_-removebg-preview.png" alt="Apple iPhone" class="product-image" />
							</div>
						</article>

						<!-- Product 3: Polaroid Camera -->
						<article class="hero-slide" data-product="polaroid" data-gradient="polaroid-gradient">
							<div class="banner-copy">
								<div class="brand-logo-section">
									<div class="brand-logo fujifilm-logo">FUJIFILM</div>
								</div>
								<div class="banner-text-stack">
									<div class="text-line brand-name">Fujifilm</div>
									<div class="text-line product-name">Instax Mini</div>
									<div class="text-line product-desc">Instant photography fun with this popular refurbished instant camera</div>
									<div class="text-line tagline-1">SMART TECH</div>
									<div class="text-line tagline-2">SMARTER SPENDING</div>
									<div class="text-line price-line">Starting At</div>
									<div class="text-line price-amount">GH₵ 450.00</div>
								</div>
								<div class="social-buttons">
									<a href="#" class="social-btn instagram" aria-label="Share on Instagram"><i class="fab fa-instagram"></i></a>
									<a href="#" class="social-btn facebook" aria-label="Share on Facebook"><i class="fab fa-facebook"></i></a>
									<a href="#" class="social-btn twitter" aria-label="Share on Twitter"><i class="fab fa-twitter"></i></a>
								</div>
								<a href="views/all_product.php?category=cameras" class="btn-primary"><span data-translate="shop_now">SHOP NOW</span></a>
							</div>
							<div class="banner-media">
								<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/polaroid-removebg-preview.png" alt="Fujifilm Instax Mini" class="product-image" />
							</div>
						</article>

						<!-- Product 4: Samsung Phone -->
						<article class="hero-slide" data-product="samsung" data-gradient="samsung-gradient">
							<div class="banner-copy">
								<div class="brand-logo-section">
									<div class="brand-logo samsung-logo">SAMSUNG</div>
								</div>
								<div class="banner-text-stack">
									<div class="text-line brand-name">Samsung</div>
									<div class="text-line product-name">Galaxy Z Fold</div>
									<div class="text-line product-desc">Cutting-edge foldable technology at an unbeatable refurbished price</div>
									<div class="text-line tagline-1">SMART TECH</div>
									<div class="text-line tagline-2">SMARTER SPENDING</div>
									<div class="text-line price-line">Starting At</div>
									<div class="text-line price-amount">GH₵ 3,200.00</div>
								</div>
								<div class="social-buttons">
									<a href="#" class="social-btn instagram" aria-label="Share on Instagram"><i class="fab fa-instagram"></i></a>
									<a href="#" class="social-btn facebook" aria-label="Share on Facebook"><i class="fab fa-facebook"></i></a>
									<a href="#" class="social-btn twitter" aria-label="Share on Twitter"><i class="fab fa-twitter"></i></a>
								</div>
								<a href="views/all_product.php?category=smartphones" class="btn-primary"><span data-translate="shop_now">SHOP NOW</span></a>
							</div>
							<div class="banner-media">
								<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/images-42.jpeg-removebg-preview.png" alt="Samsung Galaxy Z Fold" class="product-image" />
							</div>
						</article>
					</div>
					<!-- Navigation Dots -->
					<div class="hero-dots">
						<span class="carousel-dot active" data-slide="0"></span>
						<span class="carousel-dot" data-slide="1"></span>
						<span class="carousel-dot" data-slide="2"></span>
						<span class="carousel-dot" data-slide="3"></span>
					</div>
				</div>

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
				<div class="category-large" onclick="window.location.href='views/flash_deals.php'" style="cursor: pointer;">
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/black-friday-sale-offer-deals-background_1055-8959.avif" alt="Flash Deals">
					<div class="category-large-overlay">
						<h4>Flash Deals</h4>
					</div>
				</div>

				<!-- Grid of Smaller Categories on Right -->
				<div class="category-grid-right">
					<div class="category-small" onclick="window.location.href='views/ipads.php'" style="cursor: pointer;">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/ipad.jpg" alt="iPads">
						<div class="category-small-overlay">
							<h4><span data-translate="ipads">IPads and Tablets</span></h4>
							<p>From GH₵ 3000</p>
						</div>
					</div>
					<div class="category-small" onclick="window.location.href='views/smartphones.php'" style="cursor: pointer;">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/smartphones.webp" alt="Smartphones">
						<div class="category-small-overlay">
							<h4><span data-translate="smartphones">Smartphones</span></h4>
							<p>From GH₵ 2500</p>
						</div>
					</div>
					<div class="category-small" onclick="window.location.href='views/laptops.php'" style="cursor: pointer;">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/laptop.jpg" alt="Laptops">
						<div class="category-small-overlay">
							<h4><span data-translate="laptops">Laptops and Desktops</span></h4>
							<p>From GH₵ 4000</p>
						</div>
					</div>
					<div class="category-small" onclick="window.location.href='views/photography_video.php'" style="cursor: pointer;">
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

	<!-- Featured on IG this Week -->
	<section class="featured-ig-section">
		<div class="container">
			<div class="section-header">
				<h2 class="featured-ig-title">
					<i class="fab fa-instagram"></i>
					Featured on IG this Week
				</h2>
			</div>

			<div class="featured-ig-carousel-wrapper">
				<div class="featured-ig-carousel" id="featuredIgCarousel">
					<?php if (!empty($featured_ig_products)): ?>
						<?php foreach ($featured_ig_products as $index => $product): ?>
							<div class="featured-ig-slide" data-index="<?= $index ?>">
								<div class="ig-phone-frame">
									<div class="ig-screen">
										<a href="views/single_product.php?id=<?= $product['product_id'] ?>" class="featured-ig-link">
											<div class="ig-image-container">
												<img src="<?= htmlspecialchars($product['image_url']) ?>"
													alt="<?= htmlspecialchars($product['product_title']) ?>"
													class="ig-image">
												<div class="ig-gradient-overlay"></div>
												<div class="ig-instagram-icon">
													<i class="fab fa-instagram"></i>
												</div>
											</div>
											<div class="ig-content">
												<h3 class="ig-product-title"><?= htmlspecialchars($product['product_title']) ?></h3>
												<div class="ig-price">GH₵ <?= number_format($product['product_price'], 2) ?></div>
												<button class="ig-add-cart-btn" onclick="event.preventDefault(); addToCart(<?= $product['product_id'] ?>, 1);">
													<i class="fas fa-shopping-cart"></i> Add to Cart
												</button>
											</div>
										</a>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<!-- Placeholder frames when no products -->
						<?php for ($i = 0; $i < 5; $i++): ?>
							<div class="featured-ig-slide" data-index="<?= $i ?>">
								<div class="ig-phone-frame">
									<div class="ig-screen">
										<div class="ig-image-container">
											<div class="ig-placeholder">
												<i class="fab fa-instagram"></i>
												<p>Product Frame <?= $i + 1 ?></p>
											</div>
											<div class="ig-gradient-overlay"></div>
										</div>
										<div class="ig-content">
											<h3 class="ig-product-title">Coming Soon</h3>
											<div class="ig-price">GH₵ 0.00</div>
										</div>
									</div>
								</div>
							</div>
						<?php endfor; ?>
					<?php endif; ?>
				</div>

				<!-- Navigation arrows -->
				<button class="ig-carousel-prev" aria-label="Previous">
					<i class="fas fa-chevron-left"></i>
				</button>
				<button class="ig-carousel-next" aria-label="Next">
					<i class="fas fa-chevron-right"></i>
				</button>
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
					<!-- Customer Activity Popup -->
					<div class="customer-activity-popup" style="
						position: absolute;
						top: 15px;
						left: 50%;
						transform: translateX(-50%);
						background: rgba(0,0,0,0.8);
						color: white;
						padding: 8px 12px;
						border-radius: 20px;
						font-size: 0.75rem;
						font-weight: 600;
						z-index: 20;
						opacity: 0;
						animation: popupFade 4s ease-in-out infinite;
						white-space: nowrap;
						pointer-events: none;
						animation-delay: 1.2s;
					">
						4 customers viewing this
					</div>

					<div class="deal-discount">-23%</div>

					<!-- Wishlist Heart -->
					<div style="position: absolute; top: 12px; right: 12px; z-index: 10;">
						<?php
						$product_id = 1; // This appears to be a static product
						$is_in_wishlist = false;
						if ($is_logged_in && $customer_id) {
							$is_in_wishlist = check_wishlist_item_ctr($product_id, $customer_id);
						}
						$heart_class = $is_in_wishlist ? 'fas fa-heart' : 'far fa-heart';
						$btn_class = $is_in_wishlist ? 'wishlist-btn active' : 'wishlist-btn';
						?>
						<button onclick="event.stopPropagation(); toggleWishlist(<?php echo $product_id; ?>, this)"
							class="<?php echo $btn_class; ?>"
							style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
							onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
							onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';">
							<i class="<?php echo $heart_class; ?>" style="color: <?php echo $is_in_wishlist ? '#ef4444' : '#6b7280'; ?>; font-size: 16px;"></i>
						</button>
					</div>

					<div class="deal-image-container">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-22at10.24.50AM.png" alt="HP LAPTOP " class="deal-image">
					</div>
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
						<span class="deal-current-price" style="font-weight: 900;">GH₵ 9000.00</span>
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
					<!-- Customer Activity Popup -->
					<div class="customer-activity-popup" style="
						position: absolute;
						top: 15px;
						left: 50%;
						transform: translateX(-50%);
						background: rgba(0,0,0,0.8);
						color: white;
						padding: 8px 12px;
						border-radius: 20px;
						font-size: 0.75rem;
						font-weight: 600;
						z-index: 20;
						opacity: 0;
						animation: popupFade 4s ease-in-out infinite;
						white-space: nowrap;
						pointer-events: none;
						animation-delay: 3.5s;
					">
						5 customers added to cart
					</div>
					<div class="deal-discount">-8%</div>
					<div class="deal-image-container">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-22at10.30.25AM.png" alt="iPad m2" class="deal-image">
					</div>
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
					<!-- Customer Activity Popup -->
					<div class="customer-activity-popup" style="
						position: absolute;
						top: 15px;
						left: 50%;
						transform: translateX(-50%);
						background: rgba(0,0,0,0.8);
						color: white;
						padding: 8px 12px;
						border-radius: 20px;
						font-size: 0.75rem;
						font-weight: 600;
						z-index: 20;
						opacity: 0;
						animation: popupFade 4s ease-in-out infinite;
						white-space: nowrap;
						pointer-events: none;
						animation-delay: 2.8s;
					">
						10 customers interested
					</div>
					<div class="deal-discount">-19%</div>
					<div class="deal-image-container">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-22at10.33.38AM.png" alt="Apple iPad Mini" class="deal-image">
					</div>
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
	<!-- Customer Testimonials - Auto-Scrolling Circular Gallery -->
	<section class="testimonials-section">
		<div class="container">
			<h2 data-translate="what_customers_say">What Our Customers Say</h2>
			<p class="section-subtitle" data-translate="amazing_reviews">Stories from satisfied GadgetGarage customers</p>

			<!-- Auto-Scrolling Circular Carousel Container -->
			<div id="testimonial-carousel-container">
				<!-- Carousel will be dynamically inserted here -->
			</div>
		</div>
	</section>

	<!-- Newsletter Section -->
	<section class="newsletter-section">
		<div class="container">
			<div class="newsletter-container">
				<div class="newsletter-icon-wrapper">
					<i class="fas fa-envelope"></i>
				</div>
				<h2 class="newsletter-title">Be the First to Discover Amazing Mid-Week Deals!</h2>
				<p class="newsletter-description">
					Subscribe to our newsletter and get exclusive access to special offers, new arrivals, and limited-time deals delivered straight to your inbox.
				</p>
				<form class="newsletter-form" id="newsletterForm" onsubmit="subscribeNewsletterSection(event)">
					<input
						type="email"
						class="newsletter-input"
						id="newsletterEmailInput"
						placeholder="Enter your email address"
						required
						autocomplete="email">
					<button type="submit" class="newsletter-submit-btn" id="newsletterSubmitBtn">
						<i class="fas fa-paper-plane"></i> Subscribe
					</button>
				</form>
				<div class="newsletter-message" id="newsletterMessage"></div>
				<p class="newsletter-privacy">
					<i class="fas fa-lock"></i> We respect your privacy. Unsubscribe at any time.
				</p>
			</div>
		</div>
	</section>

	<script>
		async function subscribeNewsletterSection(event) {
			event.preventDefault();

			const form = document.getElementById('newsletterForm');
			const emailInput = document.getElementById('newsletterEmailInput');
			const submitBtn = document.getElementById('newsletterSubmitBtn');
			const messageDiv = document.getElementById('newsletterMessage');

			const email = emailInput.value.trim();

			if (!email) {
				showNewsletterMessage('Please enter your email address', 'error');
				return;
			}

			// Validate email format
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(email)) {
				showNewsletterMessage('Please enter a valid email address', 'error');
				return;
			}

			// Disable button and show loading state
			submitBtn.disabled = true;
			submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
			messageDiv.className = 'newsletter-message';

			try {
				const response = await fetch('actions/subscribe_newsletter_action.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						email: email
					})
				});

				const data = await response.json();

				if (data.success) {
					showNewsletterMessage('Thank you for joining Gadget Garage Premium list!', 'success');
					emailInput.value = '';

					// Reset button after 2 seconds
					setTimeout(() => {
						submitBtn.disabled = false;
						submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Subscribe';
					}, 2000);
				} else {
					showNewsletterMessage(data.message || 'Failed to subscribe. Please try again.', 'error');
					submitBtn.disabled = false;
					submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Subscribe';
				}
			} catch (error) {
				console.error('Newsletter subscription error:', error);
				showNewsletterMessage('An error occurred. Please try again later.', 'error');
				submitBtn.disabled = false;
				submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Subscribe';
			}
		}

		function showNewsletterMessage(message, type) {
			const messageDiv = document.getElementById('newsletterMessage');
			messageDiv.textContent = message;
			messageDiv.className = `newsletter-message ${type}`;

			// Auto-hide success messages after 5 seconds
			if (type === 'success') {
				setTimeout(() => {
					messageDiv.className = 'newsletter-message';
				}, 5000);
			}
		}
	</script>

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
							<li><a href="views/all_product.php?category=smartphones">Smartphones</a></li>
							<li><a href="views/all_product.php?category=laptops">Laptops</a></li>
							<li><a href="views/all_product.php?category=ipads">Tablets</a></li>
							<li><a href="views/all_product.php?category=cameras">Cameras</a></li>
							<li><a href="views/all_product.php?category=video_equipment">Video Equipment</a></li>
						</ul>
					</div>
					<div class="col-lg-2 col-md-6 mb-4">
						<h5 class="footer-title">Services</h5>
						<ul class="footer-links">
							<li><a href="views/repair_services.php">Device Repair</a></li>
							<li><a href="views/contact.php">Tech Support</a></li>
							<li><a href="views/repair_services.php">Data Recovery</a></li>
							<li><a href="views/contact.php">Setup Services</a></li>
							<li><a href="views/terms_conditions.php">Warranty</a></li>
						</ul>
					</div>
					<div class="col-lg-2 col-md-6 mb-4">
						<h5 class="footer-title">Company</h5>
						<ul class="footer-links">
							<li><a href="views/contact.php">About Us</a></li>
							<li><a href="views/contact.php">Contact</a></li>
							<li><a href="views/contact.php">Careers</a></li>
							<li><a href="views/contact.php">Blog</a></li>
							<li><a href="views/contact.php">Press</a></li>
						</ul>
					</div>
					<div class="col-lg-2 col-md-6 mb-4">
						<h5 class="footer-title">Support</h5>
						<ul class="footer-links">
							<li><a href="views/contact.php">Help Center</a></li>
							<li><a href="views/terms_conditions.php">Shipping Info</a></li>
							<li><a href="views/terms_conditions.php">Returns</a></li>
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
	<script src="js/newsletter-popup.js"></script>
	<script src="views/circular-gallery.js"></script>
	<script>
		// Make functions globally accessible
		window.dropdownTimeout = null;
		window.shopDropdownTimeout = null;
		window.moreDropdownTimeout = null;
		window.userDropdownTimeout = null;

		// Search functionality
		document.addEventListener('DOMContentLoaded', function() {
			const searchInput = document.querySelector('.search-input');
			const searchBtn = document.querySelector('.search-btn');
			
			if (searchInput) {
				searchInput.addEventListener('keypress', function(e) {
					if (e.key === 'Enter') {
						performSearch();
					}
				});
			}
			
			if (searchBtn) {
				searchBtn.addEventListener('click', performSearch);
			}
		});

		function performSearch() {
			const query = document.querySelector('.search-input')?.value.trim();
			if (query) {
				// Redirect to search results page
				window.location.href = 'product_search_result.php?query=' + encodeURIComponent(query);
			}
		}

		// Dropdown functions - must be global for inline handlers
		let dropdownTimeout;
		let shopDropdownTimeout;
		let moreDropdownTimeout;
		let userDropdownTimeout;

		function showDropdown() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				clearTimeout(dropdownTimeout);
				dropdown.style.cssText = 'opacity: 1 !important; visibility: visible !important; transform: translateY(0) !important;';
			}
		}

		function hideDropdown() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				clearTimeout(dropdownTimeout);
				dropdownTimeout = setTimeout(() => {
					dropdown.style.cssText = 'opacity: 0 !important; visibility: hidden !important; transform: translateY(-10px) !important;';
				}, 300);
			}
		}

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

		// Enhanced dropdown behavior - keep dropdowns open when hovering over them
		document.addEventListener('DOMContentLoaded', function() {
			// Brands dropdown - keep it open when hovering over the dropdown itself
			const brandsDropdown = document.getElementById('shopDropdown');
			if (brandsDropdown) {
				brandsDropdown.addEventListener('mouseenter', function() {
					clearTimeout(dropdownTimeout);
				});
				brandsDropdown.addEventListener('mouseleave', hideDropdown);
			}

			// Shop category dropdown - keep it open when hovering over the dropdown itself
			const shopCategoryDropdown = document.getElementById('shopCategoryDropdown');
			if (shopCategoryDropdown) {
				shopCategoryDropdown.addEventListener('mouseenter', function() {
					clearTimeout(shopDropdownTimeout);
				});
				shopCategoryDropdown.addEventListener('mouseleave', hideShopDropdown);
			}

			// More dropdown - keep it open when hovering over the dropdown itself
			const moreDropdown = document.getElementById('moreDropdown');
			if (moreDropdown) {
				moreDropdown.addEventListener('mouseenter', function() {
					clearTimeout(moreDropdownTimeout);
				});
				moreDropdown.addEventListener('mouseleave', hideMoreDropdown);
			}

			// User dropdown - keep it open when hovering over the dropdown itself
			const userDropdown = document.getElementById('userDropdownMenu');
			if (userDropdown) {
				userDropdown.addEventListener('mouseenter', function() {
					clearTimeout(userDropdownTimeout);
				});
				userDropdown.addEventListener('mouseleave', hideUserDropdown);
			}
		});

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

			if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
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

			// Featured on IG scroll animations
			initFeaturedIGAnimations();

			// Hero Carousel - Super Simple Version
			console.log('🚀 Starting hero carousel from DOMContentLoaded...');
			initSimpleCarousel();
		});

		// SUPER SIMPLE CAROUSEL - GUARANTEED TO WORK
		let slideTimer;
		let currentSlide = 0;

		function initSimpleCarousel() {
			console.log('🔥 STARTING SIMPLE CAROUSEL');

			const slides = document.querySelectorAll('.hero-slide');
			const dots = document.querySelectorAll('.carousel-dot');

			console.log('Found slides:', slides.length);
			console.log('Found dots:', dots.length);

			if (slides.length === 0) return;

			function showSlideNumber(num) {
				console.log('🎯 SWITCHING TO SLIDE:', num);

				// Hide ALL slides
				for (let i = 0; i < slides.length; i++) {
					slides[i].classList.remove('active');
					slides[i].style.display = 'none';
				}

				// Hide ALL dots
				for (let i = 0; i < dots.length; i++) {
					dots[i].classList.remove('active');
				}

				// Show current slide
				slides[num].classList.add('active');
				slides[num].style.display = 'grid';

				// Show current dot
				if (dots[num]) {
					dots[num].classList.add('active');
				}

				console.log('✅ NOW SHOWING:', slides[num].dataset.product);
			}

			function goToNextSlide() {
				currentSlide = (currentSlide + 1) % slides.length;
				showSlideNumber(currentSlide);
			}

			// Show first slide immediately
			showSlideNumber(0);

			// Start timer - change every 2 seconds
			if (slideTimer) clearInterval(slideTimer);
			slideTimer = setInterval(goToNextSlide, 2000);

			console.log('🚀 TIMER STARTED - CHANGES EVERY 2 SECONDS');
		}

		// FORCE START IMMEDIATELY
		setTimeout(initSimpleCarousel, 500);
		setTimeout(initSimpleCarousel, 1500);
		setTimeout(initSimpleCarousel, 3000);
		// Initialize Featured IG Carousel
		initFeaturedIgCarousel();
		});

		// Featured on IG Carousel Function
		function initFeaturedIgCarousel() {
			const carousel = document.getElementById('featuredIgCarousel');
			if (!carousel) {
				console.log('Featured IG carousel not found');
				return;
			}

			const slides = carousel.querySelectorAll('.featured-ig-slide');
			if (slides.length === 0) {
				console.log('No slides found in Featured IG carousel');
				return;
			}

			const prevBtn = document.querySelector('.ig-carousel-prev');
			const nextBtn = document.querySelector('.ig-carousel-next');

			let currentIndex = 0;
			const slideWidth = slides[0].offsetWidth + 30; // width + gap
			let autoScrollInterval;

			// Function to scroll to a specific index
			function scrollToIndex(index) {
				if (index < 0) index = 0;
				if (index >= slides.length) index = slides.length - 1;

				currentIndex = index;
				const scrollPosition = currentIndex * slideWidth;

				carousel.scrollTo({
					left: scrollPosition,
					behavior: 'smooth'
				});
			}

			// Previous button
			if (prevBtn) {
				prevBtn.addEventListener('click', () => {
					scrollToIndex(currentIndex - 1);
					resetAutoScroll();
				});
			}

			// Next button
			if (nextBtn) {
				nextBtn.addEventListener('click', () => {
					scrollToIndex(currentIndex + 1);
					resetAutoScroll();
				});
			}

			// Auto-scroll function
			function startAutoScroll() {
				autoScrollInterval = setInterval(() => {
					currentIndex++;
					if (currentIndex >= slides.length) {
						currentIndex = 0;
						// Smooth scroll to start
						carousel.scrollTo({
							left: 0,
							behavior: 'smooth'
						});
					} else {
						scrollToIndex(currentIndex);
					}
				}, 4000); // Auto-scroll every 4 seconds
			}

			function resetAutoScroll() {
				clearInterval(autoScrollInterval);
				startAutoScroll();
			}

			// Pause on hover
			carousel.addEventListener('mouseenter', () => {
				clearInterval(autoScrollInterval);
			});

			carousel.addEventListener('mouseleave', () => {
				startAutoScroll();
			});

			// Update current index on scroll
			carousel.addEventListener('scroll', () => {
				const scrollLeft = carousel.scrollLeft;
				currentIndex = Math.round(scrollLeft / slideWidth);
			});

			// Start auto-scroll
			startAutoScroll();

			// Touch/swipe support
			let isDown = false;
			let startX;
			let scrollLeft;

			carousel.addEventListener('mousedown', (e) => {
				isDown = true;
				carousel.style.cursor = 'grabbing';
				startX = e.pageX - carousel.offsetLeft;
				scrollLeft = carousel.scrollLeft;
			});

			carousel.addEventListener('mouseleave', () => {
				isDown = false;
				carousel.style.cursor = 'grab';
			});

			carousel.addEventListener('mouseup', () => {
				isDown = false;
				carousel.style.cursor = 'grab';
			});

			carousel.addEventListener('mousemove', (e) => {
				if (!isDown) return;
				e.preventDefault();
				const x = e.pageX - carousel.offsetLeft;
				const walk = (x - startX) * 2;
				carousel.scrollLeft = scrollLeft - walk;
			});

			console.log('Featured IG carousel initialized with', slides.length, 'slides');
		}

		// Featured on IG scroll animation function
		function initFeaturedIGAnimations() {
			const cards = document.querySelectorAll('.featured-ig-card');

			if (cards.length === 0) return;

			// Create Intersection Observer for scroll animations
			const observerOptions = {
				root: null,
				rootMargin: '0px 0px -100px 0px',
				threshold: 0.1
			};

			const observer = new IntersectionObserver((entries) => {
				entries.forEach((entry, index) => {
					if (entry.isIntersecting) {
						// Stagger animation with delay based on index
						setTimeout(() => {
							entry.target.classList.add('animate-in');
						}, index * 150); // 150ms delay between each card
						observer.unobserve(entry.target);
					}
				});
			}, observerOptions);

			// Observe each card
			cards.forEach(card => {
				observer.observe(card);
			});
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

		// Old testimonials code removed - using new auto-scrolling carousel

		// Countdown timer functionality for deals
		function updateCountdown() {
			const timers = [{
					days: document.getElementById('days1'),
					hours: document.getElementById('hours1'),
					minutes: document.getElementById('minutes1'),
					seconds: document.getElementById('seconds1'),
					endTime: new Date().getTime() + (10 * 24 * 60 * 60 * 1000)
				},
				{
					days: document.getElementById('days2'),
					hours: document.getElementById('hours2'),
					minutes: document.getElementById('minutes2'),
					seconds: document.getElementById('seconds2'),
					endTime: new Date().getTime() + (10 * 24 * 60 * 60 * 1000)
				},
				{
					days: document.getElementById('days3'),
					hours: document.getElementById('hours3'),
					minutes: document.getElementById('minutes3'),
					seconds: document.getElementById('seconds3'),
					endTime: new Date().getTime() + (10 * 24 * 60 * 60 * 1000)
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

		// Wishlist functionality
		function toggleWishlist(productId, button) {
			const icon = button.querySelector('i');
			const isActive = button.classList.contains('active');

			if (isActive) {
				// Remove from wishlist
				button.classList.remove('active');
				icon.className = 'far fa-heart';

				// Make AJAX call to remove from wishlist
				fetch('actions/remove_from_wishlist.php', {
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
						}
					})
					.catch(error => console.error('Error:', error));
			} else {
				// Add to wishlist
				button.classList.add('active');
				icon.className = 'fas fa-heart';

				// Make AJAX call to add to wishlist
				fetch('actions/add_to_wishlist.php', {
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
					});
			}
		}

		// Newsletter popup for new users
		<?php if ($show_newsletter_popup): ?>
			console.log('Newsletter popup should be shown!');
			document.addEventListener('DOMContentLoaded', function() {
				console.log('DOM loaded, showing newsletter popup...');
				// Show newsletter popup after a short delay
				setTimeout(function() {
					console.log('Calling showNewsletterPopup()...');
					showNewsletterPopup();
				}, 2000); // 2 second delay after page loads
			});
		<?php else: ?>
			console.log('Newsletter popup NOT showing. Logged in: <?php echo $is_logged_in ? "yes" : "no"; ?>');
		<?php endif; ?>
	</script>

	<!-- Rating Popup -->
	<div id="ratingPopup" class="rating-popup" style="display: none;">
		<div class="popup-overlay" onclick="closeRatingPopup()"></div>
		<div class="rating-popup-container">
			<div class="rating-header">
				<h3>Rate Your Experience</h3>
				<button class="popup-close-btn" onclick="closeRatingPopup()" aria-label="Close popup">
					<i class="fas fa-times"></i>
				</button>
			</div>

			<div class="rating-content">
				<p>How was your shopping experience with us?</p>

				<div class="star-rating">
					<span class="star" data-rating="1">★</span>
					<span class="star" data-rating="2">★</span>
					<span class="star" data-rating="3">★</span>
					<span class="star" data-rating="4">★</span>
					<span class="star" data-rating="5">★</span>
				</div>

				<textarea id="ratingComment" placeholder="Tell us about your experience (optional)" rows="3"></textarea>

				<div class="rating-buttons">
					<button type="button" onclick="submitRating()" id="submitRatingBtn" disabled>
						Submit Rating
					</button>
					<button type="button" onclick="closeRatingPopup()" class="skip-btn">
						Skip
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Flash Deals Popup -->
	<div id="flashDealsPopup" class="flash-deals-popup" style="display: none;">
		<div class="popup-overlay" onclick="closeFlashDealsPopup()"></div>
		<div class="popup-container">
			<!-- Close button -->
			<button class="popup-close-btn" onclick="closeFlashDealsPopup()" aria-label="Close popup">
				<i class="fas fa-times"></i>
			</button>

			<!-- Background image -->
			<div class="popup-image-container">
				<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/SecondLife.FirstQuality..png" alt="Flash Deals Promotion" class="popup-background-image">
			</div>

			<!-- Shop Flash Deals Button -->
			<button class="shop-flash-deals-btn" onclick="goToFlashDeals()">
				<i class="fas fa-bolt"></i>
				Shop Flash Deals
			</button>
		</div>
	</div>

	<style>
		/* Rating Popup Styles */
		.rating-popup {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			z-index: 10001;
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 0;
			visibility: hidden;
			transition: all 0.3s ease;
		}

		.rating-popup.show {
			opacity: 1;
			visibility: visible;
		}

		.rating-popup-container {
			background: white;
			border-radius: 20px;
			padding: 0;
			max-width: 450px;
			width: 90%;
			margin: 20px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			transform: scale(0.8);
			transition: transform 0.3s ease;
			overflow: hidden;
		}

		.rating-popup.show .rating-popup-container {
			transform: scale(1);
		}

		.rating-header {
			background: linear-gradient(135deg, #4f46e5, #3b82f6);
			color: white;
			padding: 20px 30px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.rating-header h3 {
			margin: 0;
			font-size: 1.5rem;
			font-weight: 600;
		}

		.rating-header .popup-close-btn {
			background: rgba(255, 255, 255, 0.2);
			border: none;
			color: white;
			width: 35px;
			height: 35px;
			border-radius: 50%;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.3s ease;
		}

		.rating-header .popup-close-btn:hover {
			background: rgba(255, 255, 255, 0.3);
			transform: scale(1.1);
		}

		.rating-content {
			padding: 30px;
			text-align: center;
		}

		.rating-content p {
			margin: 0 0 25px 0;
			font-size: 1.1rem;
			color: #374151;
		}

		.star-rating {
			display: flex;
			justify-content: center;
			gap: 8px;
			margin-bottom: 25px;
		}

		.star {
			font-size: 2.5rem;
			color: #d1d5db;
			cursor: pointer;
			transition: all 0.3s ease;
			user-select: none;
		}

		.star:hover,
		.star.active {
			color: #fbbf24;
			transform: scale(1.2);
		}

		.star.hover-effect {
			color: #fbbf24;
		}

		#ratingComment {
			width: 100%;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			padding: 15px;
			font-size: 1rem;
			resize: vertical;
			margin-bottom: 25px;
			transition: border-color 0.3s ease;
			font-family: inherit;
		}

		#ratingComment:focus {
			outline: none;
			border-color: #4f46e5;
			box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
		}

		.rating-buttons {
			display: flex;
			gap: 15px;
			justify-content: center;
		}

		.rating-buttons button {
			padding: 12px 24px;
			border-radius: 10px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			border: none;
		}

		#submitRatingBtn {
			background: linear-gradient(135deg, #10b981, #059669);
			color: white;
		}

		#submitRatingBtn:enabled:hover {
			background: linear-gradient(135deg, #059669, #047857);
			transform: translateY(-2px);
		}

		#submitRatingBtn:disabled {
			background: #d1d5db;
			color: #9ca3af;
			cursor: not-allowed;
		}

		.skip-btn {
			background: #f3f4f6;
			color: #6b7280;
			border: 2px solid #e5e7eb;
		}

		.skip-btn:hover {
			background: #e5e7eb;
			color: #374151;
		}

		/* Mobile responsiveness */
		@media (max-width: 600px) {
			.rating-popup-container {
				max-width: 350px;
			}

			.rating-header {
				padding: 15px 20px;
			}

			.rating-header h3 {
				font-size: 1.3rem;
			}

			.rating-content {
				padding: 20px;
			}

			.star {
				font-size: 2rem;
			}

			.rating-buttons {
				flex-direction: column;
			}
		}

		.flash-deals-popup {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			z-index: 10000;
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 0;
			visibility: hidden;
			transition: all 0.3s ease;
		}

		.flash-deals-popup.show {
			opacity: 1;
			visibility: visible;
		}

		.popup-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.7);
			backdrop-filter: blur(5px);
		}

		.popup-container {
			position: relative;
			max-width: 900px;
			width: 95%;
			margin: 10px;
			border-radius: 30px;
			overflow: hidden;
			box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
			transform: scale(0.8);
			transition: transform 0.3s ease;
		}

		.flash-deals-popup.show .popup-container {
			transform: scale(1);
		}

		.popup-close-btn {
			position: absolute;
			top: 15px;
			right: 15px;
			width: 40px;
			height: 40px;
			background: rgba(0, 0, 0, 0.7);
			border: none;
			border-radius: 50%;
			color: white;
			font-size: 18px;
			cursor: pointer;
			z-index: 10001;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.3s ease;
		}

		.popup-close-btn:hover {
			background: rgba(0, 0, 0, 0.9);
			transform: scale(1.1);
		}

		.popup-image-container {
			position: relative;
			width: 100%;
		}

		.popup-background-image {
			width: 100%;
			height: auto;
			display: block;
		}

		.shop-flash-deals-btn {
			position: absolute;
			bottom: 30px;
			left: 50%;
			transform: translateX(-50%);
			background: linear-gradient(135deg, #ff4757, #ff3838);
			color: white;
			border: none;
			padding: 15px 30px;
			border-radius: 50px;
			font-size: 18px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.3s ease;
			box-shadow: 0 8px 25px rgba(255, 71, 87, 0.4);
			display: flex;
			align-items: center;
			gap: 10px;
			text-transform: uppercase;
			letter-spacing: 1px;
		}

		.shop-flash-deals-btn:hover {
			background: linear-gradient(135deg, #ff3838, #ff2828);
			transform: translateX(-50%) translateY(-3px);
			box-shadow: 0 12px 35px rgba(255, 71, 87, 0.6);
		}

		.shop-flash-deals-btn i {
			font-size: 20px;
			animation: flash 1.5s infinite;
		}

		@keyframes flash {

			0%,
			50% {
				opacity: 1;
			}

			25%,
			75% {
				opacity: 0.5;
			}
		}

		/* Mobile responsiveness */
		@media (max-width: 600px) {
			.popup-container {
				max-width: 95%;
				margin: 5px;
				width: 95%;
			}

			.shop-flash-deals-btn {
				bottom: 20px;
				padding: 12px 25px;
				font-size: 16px;
			}

			.popup-close-btn {
				top: 10px;
				right: 10px;
				width: 35px;
				height: 35px;
				font-size: 16px;
			}
		}
	</style>

	<script>
		// Rating system variables
		let selectedRating = 0;

		function showRatingPopup() {
			const popup = document.getElementById('ratingPopup');
			if (popup) {
				popup.style.display = 'flex';
				setTimeout(() => {
					popup.classList.add('show');
				}, 10);
			}
		}

		function closeRatingPopup() {
			const popup = document.getElementById('ratingPopup');
			if (popup) {
				popup.classList.remove('show');
				setTimeout(() => {
					popup.style.display = 'none';
					// Mark as seen so it doesn't show again soon
					localStorage.setItem('ratingPopupSeen', Date.now().toString());
				}, 300);
			}
		}

		function setupStarRating() {
			const stars = document.querySelectorAll('.star');
			const submitBtn = document.getElementById('submitRatingBtn');

			stars.forEach((star, index) => {
				star.addEventListener('mouseover', () => {
					highlightStars(index + 1);
				});

				star.addEventListener('mouseout', () => {
					highlightStars(selectedRating);
				});

				star.addEventListener('click', () => {
					selectedRating = index + 1;
					highlightStars(selectedRating);
					submitBtn.disabled = false;
				});
			});
		}

		function highlightStars(rating) {
			const stars = document.querySelectorAll('.star');
			stars.forEach((star, index) => {
				if (index < rating) {
					star.classList.add('active');
				} else {
					star.classList.remove('active');
				}
			});
		}

		function submitRating() {
			const comment = document.getElementById('ratingComment').value;
			const submitBtn = document.getElementById('submitRatingBtn');

			if (selectedRating === 0) {
				alert('Please select a star rating');
				return;
			}

			// Disable submit button to prevent double submission
			submitBtn.disabled = true;
			submitBtn.innerHTML = 'Submitting...';

			// Send rating to server
			fetch('actions/submit_rating_action.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						rating: selectedRating,
						comment: comment
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						alert('Thank you for your feedback!');
						closeRatingPopup();
					} else {
						alert('Error submitting rating. Please try again.');
						submitBtn.disabled = false;
						submitBtn.innerHTML = 'Submit Rating';
					}
				})
				.catch(error => {
					console.error('Error:', error);
					alert('Error submitting rating. Please try again.');
					submitBtn.disabled = false;
					submitBtn.innerHTML = 'Submit Rating';
				});
		}

		// Initialize star rating when DOM loads
		document.addEventListener('DOMContentLoaded', function() {
			setupStarRating();
		});

		function showFlashDealsPopup() {
			const popup = document.getElementById('flashDealsPopup');
			if (popup) {
				popup.style.display = 'flex';
				setTimeout(() => {
					popup.classList.add('show');
				}, 10);
			}
		}

		function closeFlashDealsPopup() {
			const popup = document.getElementById('flashDealsPopup');
			if (popup) {
				popup.classList.remove('show');
				setTimeout(() => {
					popup.style.display = 'none';
					// Store the current timestamp when popup is closed
					localStorage.setItem('flashDealsPopupLastSeen', Date.now().toString());
				}, 300);
			}
		}

		function goToFlashDeals() {
			window.location.href = 'views/flash_deals.php';
		}

		// Manual function to reset flash deals popup timer (for testing/admin use)
		function resetFlashDealsTimer() {
			localStorage.removeItem('flashDealsPopupLastSeen');
			console.log('Flash deals popup timer reset. Next page refresh will show popup.');
		}

		// Manual function to show flash deals popup (for testing)
		function forceShowFlashDealsPopup() {
			showFlashDealsPopup();
			console.log('Flash deals popup forced to show.');
		}

		// Manual function to force close rating popup (emergency close)
		function forceCloseRatingPopup() {
			const overlay = document.getElementById('ratingPopupOverlay');
			if (overlay) {
				overlay.style.display = 'none';
				overlay.classList.remove('show');
				console.log('Rating popup force closed');
			}
		}

		// Manual function to test rating popup
		function testRatingPopup() {
			console.log('Testing rating popup...');
			showRatingPopup('TEST123');
		}

		// Manual function to test ONLY the simple popup
		function testSimpleRatingPopup() {
			console.log('Testing simple rating popup...');
			showSimpleRatingPopup('TEST123');
		}

		// Sweet Alert rating popup
		function showSweetAlertRating(orderId) {
			console.log('Showing Sweet Alert rating for order:', orderId);

			// Show after 10 seconds delay on index page after payment
			setTimeout(() => {
				Swal.fire({
					title: 'How satisfied are you?',
					text: 'We\'d love to hear about your experience!',
					icon: 'question',
					showCancelButton: true,
					confirmButtonText: 'Very Satisfied',
					cancelButtonText: 'See More Options',
					confirmButtonColor: '#10b981',
					cancelButtonColor: '#6b7280',
					allowOutsideClick: false,
					allowEscapeKey: false
				}).then((result) => {
					if (result.isConfirmed) {
						// User selected "Very Satisfied" (5 stars)
						submitRating(orderId, 5);
						showThankYouMessage(5);
					} else if (result.dismiss === Swal.DismissReason.cancel) {
						// Show rating options
						showRatingOptions(orderId);
					}
				});
			}, 10000); // 10 second delay
		}

		function showRatingOptions(orderId) {
			Swal.fire({
				title: 'Rate Your Experience',
				html: `
					<div style="text-align: center; margin: 20px 0;">
						<p style="margin-bottom: 20px; color: #374151;">Please select your rating:</p>
						<div style="display: flex; justify-content: center; gap: 10px; margin: 20px 0;">
							<button class="rating-btn" data-rating="1" style="padding: 10px 15px; border: 2px solid #ef4444; color: #ef4444; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">1 ⭐</button>
							<button class="rating-btn" data-rating="2" style="padding: 10px 15px; border: 2px solid #f97316; color: #f97316; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">2 ⭐</button>
							<button class="rating-btn" data-rating="3" style="padding: 10px 15px; border: 2px solid #eab308; color: #eab308; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">3 ⭐</button>
							<button class="rating-btn" data-rating="4" style="padding: 10px 15px; border: 2px solid #22c55e; color: #22c55e; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">4 ⭐</button>
							<button class="rating-btn" data-rating="5" style="padding: 10px 15px; border: 2px solid #10b981; color: #10b981; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">5 ⭐</button>
						</div>
					</div>
				`,
				showConfirmButton: false,
				showCancelButton: true,
				cancelButtonText: 'Skip Rating',
				cancelButtonColor: '#6b7280',
				allowOutsideClick: false,
				allowEscapeKey: false,
				didOpen: () => {
					// Add click handlers to rating buttons
					document.querySelectorAll('.rating-btn').forEach(btn => {
						btn.addEventListener('click', function() {
							const rating = parseInt(this.getAttribute('data-rating'));
							submitRating(orderId, rating);
							showThankYouMessage(rating);
							Swal.close();
						});

						// Hover effects
						btn.addEventListener('mouseenter', function() {
							this.style.background = this.style.borderColor;
							this.style.color = 'white';
						});

						btn.addEventListener('mouseleave', function() {
							this.style.background = 'white';
							this.style.color = this.style.borderColor;
						});
					});
				}
			});
		}

		function submitRating(orderId, rating) {
			console.log(`Sweet Alert rating submitted: ${rating} stars for order ${orderId}`);

			// Send rating to server
			try {
				fetch('actions/submit_rating.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						order_id: orderId,
						rating: rating,
						method: 'sweet_alert'
					})
				}).catch(e => console.log('Rating submission failed (non-critical):', e));
			} catch (e) {
				console.log('Rating system unavailable, but feedback recorded locally');
			}
		}

		function showThankYouMessage(rating) {
			let message = '';
			let icon = 'success';

			switch (rating) {
				case 1:
				case 2:
					message = `Thank you for your ${rating}-star rating. We'll work to improve your experience!`;
					icon = 'info';
					break;
				case 3:
					message = `Thank you for your ${rating}-star rating. We appreciate your feedback!`;
					icon = 'success';
					break;
				case 4:
				case 5:
					message = `Thank you for your ${rating}-star rating! We're so glad you had a great experience!`;
					icon = 'success';
					break;
			}

			Swal.fire({
				title: 'Thank You!',
				text: message,
				icon: icon,
				confirmButtonText: 'You\'re Welcome!',
				confirmButtonColor: '#10b981',
				timer: 4000,
				timerProgressBar: true
			});
		}

		// Global accessibility for testing
		window.testRatingPopup = testRatingPopup;
		window.testSimpleRatingPopup = testSimpleRatingPopup;
		window.testSweetAlertRating = function() {
			showSweetAlertRating('TEST123');
		};
		window.forceCloseRatingPopup = forceCloseRatingPopup;

		// Show popup for logged-in users (limited to once every 5 hours)
		<?php if ($is_logged_in): ?>
			document.addEventListener('DOMContentLoaded', function() {
				// Check if 5 hours have passed since last popup
				const lastSeen = localStorage.getItem('flashDealsPopupLastSeen');
				const fiveHoursInMs = 5 * 60 * 60 * 1000; // 5 hours in milliseconds
				const now = Date.now();

				if (!lastSeen || (now - parseInt(lastSeen)) > fiveHoursInMs) {
					// Show popup if never seen before or if 5 hours have passed
					setTimeout(showFlashDealsPopup, 1500);
				} else {
					// Calculate time remaining for debugging
					const timeRemaining = fiveHoursInMs - (now - parseInt(lastSeen));
					const hoursRemaining = Math.floor(timeRemaining / (60 * 60 * 1000));
					const minutesRemaining = Math.floor((timeRemaining % (60 * 60 * 1000)) / (60 * 1000));
					console.log(`Flash deals popup suppressed. Next popup in ${hoursRemaining}h ${minutesRemaining}m`);
				}
			});
		<?php endif; ?>
	</script>

	<style>
		/* Rating Popup Styles */
		.rating-popup-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.5);
			backdrop-filter: blur(5px);
			z-index: 10001;
			display: none;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}

		.rating-popup-overlay.show {
			display: flex;
		}

		.rating-popup {
			background: white;
			border-radius: 20px;
			padding: 30px;
			max-width: 500px;
			width: 100%;
			max-height: 90vh;
			overflow-y: auto;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			position: relative;
			z-index: 10002;
			transform: scale(1);
			opacity: 1;
			border: 3px solid #008060;
			animation: ratingPopupAppear 0.3s ease-out;
		}

		@keyframes ratingPopupAppear {
			0% {
				transform: scale(0.8);
				opacity: 0;
			}

			100% {
				transform: scale(1);
				opacity: 1;
			}
		}

		.rating-popup-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 25px;
		}

		.rating-popup-title {
			font-size: 1.5rem;
			font-weight: 600;
			color: #1f2937;
			margin: 0;
		}

		.rating-close-btn {
			background: none;
			border: none;
			font-size: 1.5rem;
			color: #6b7280;
			cursor: pointer;
			padding: 5px;
			line-height: 1;
		}

		.rating-close-btn:hover {
			color: #1f2937;
		}

		.rating-question {
			margin-bottom: 30px;
		}

		.rating-question-title {
			font-size: 1rem;
			font-weight: 500;
			color: #374151;
			margin-bottom: 15px;
		}

		.rating-stars {
			display: flex;
			gap: 8px;
			margin-bottom: 10px;
		}

		.rating-star {
			font-size: 2rem;
			color: #d1d5db;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.rating-star:hover,
		.rating-star.active {
			color: #fbbf24;
		}

		.rating-labels {
			display: flex;
			justify-content: space-between;
			font-size: 0.85rem;
			color: #6b7280;
			margin-top: 5px;
		}

		.rating-comment-section {
			margin-top: 30px;
		}

		.rating-comment-input {
			width: 100%;
			min-height: 100px;
			padding: 15px;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			font-size: 0.95rem;
			font-family: inherit;
			resize: vertical;
			transition: border-color 0.3s ease;
		}

		.rating-comment-input:focus {
			outline: none;
			border-color: #008060;
		}

		.rating-buttons {
			display: flex;
			gap: 12px;
			margin-top: 25px;
		}

		.rating-btn {
			flex: 1;
			padding: 14px 24px;
			border-radius: 12px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			border: none;
		}

		.rating-btn-skip {
			background: white;
			color: #6b7280;
			border: 2px solid #e5e7eb;
		}

		.rating-btn-skip:hover {
			background: #f9fafb;
			border-color: #d1d5db;
		}

		.rating-btn-done {
			background: #008060;
			color: white;
		}

		.rating-btn-done:hover {
			background: #006b4e;
			transform: translateY(-1px);
			box-shadow: 0 4px 12px rgba(0, 128, 96, 0.3);
		}

		.rating-btn-done:disabled {
			opacity: 0.6;
			cursor: not-allowed;
			transform: none;
		}

		@media (max-width: 768px) {
			.rating-popup {
				padding: 20px;
				border-radius: 15px;
			}

			.rating-popup-title {
				font-size: 1.3rem;
			}

			.rating-star {
				font-size: 1.8rem;
			}
	</style>

	<script>
		// Close popup with Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				closeFlashDealsPopup();
			}
		});

		// Rating popup variables
		let currentOrderId = null;
		let easeRating = 0;
		let satisfactionRating = 0;

		// Set rating for ease or satisfaction
		function setRating(type, rating) {
			if (type === 'ease') {
				easeRating = rating;
				updateStars('easeStars', rating);
				document.getElementById('easeRating').value = rating;
			} else if (type === 'satisfaction') {
				satisfactionRating = rating;
				updateStars('satisfactionStars', rating);
				document.getElementById('satisfactionRating').value = rating;
			}
		}

		// Update star display
		function updateStars(containerId, rating) {
			const stars = document.querySelectorAll(`#${containerId} .rating-star`);
			stars.forEach((star, index) => {
				if (index < rating) {
					star.textContent = '★';
					star.classList.add('active');
				} else {
					star.textContent = '☆';
					star.classList.remove('active');
				}
			});
		}

		// Show rating popup - ULTRA SIMPLE VERSION
		function showRatingPopup(orderId) {
			console.log('Showing rating popup for order:', orderId);

			// Skip all fancy stuff and go straight to bulletproof method
			showSweetAlertRating(orderId);
		}

		// Original fancy popup function
		function showFancyRatingPopup(orderId) {
			currentOrderId = orderId;
			easeRating = 0;
			satisfactionRating = 0;

			const overlay = document.getElementById('ratingPopupOverlay');
			if (overlay) {
				// Force popup to be visible with explicit styles
				overlay.style.display = 'flex';
				overlay.style.position = 'fixed';
				overlay.style.top = '0';
				overlay.style.left = '0';
				overlay.style.width = '100%';
				overlay.style.height = '100%';
				overlay.style.zIndex = '10001';
				overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
				overlay.classList.add('show');

				const popup = overlay.querySelector('.rating-popup');
				if (popup) {
					popup.style.display = 'block';
					popup.style.position = 'relative';
					popup.style.zIndex = '10002';
					popup.style.margin = 'auto';
					popup.style.border = '5px solid #ff0000';
					popup.style.backgroundColor = '#ffffff';
				}
			}
		}

		// Simple fallback popup using basic HTML
		function showSimpleRatingPopup(orderId) {
			console.log('Creating simple rating popup for order:', orderId);

			// Create a simple popup div
			const simplePopup = document.createElement('div');
			simplePopup.id = 'simpleRatingPopup';
			simplePopup.style.cssText = `
				position: fixed;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				background: white;
				padding: 30px;
				border: 5px solid #008060;
				border-radius: 10px;
				box-shadow: 0 0 20px rgba(0,0,0,0.5);
				z-index: 99999;
				width: 400px;
				text-align: center;
				font-family: Arial, sans-serif;
			`;

			simplePopup.innerHTML = `
				<h3 style="color: #008060; margin-bottom: 20px;">Rate Your Experience</h3>
				<p style="margin-bottom: 20px;">How satisfied are you with your order?</p>
				<div style="margin-bottom: 20px;">
					<button onclick="submitSimpleRating(1)" style="margin: 5px; padding: 10px; background: #ff4444; color: white; border: none; border-radius: 5px;">⭐ Poor</button>
					<button onclick="submitSimpleRating(2)" style="margin: 5px; padding: 10px; background: #ff8800; color: white; border: none; border-radius: 5px;">⭐⭐ Fair</button>
					<button onclick="submitSimpleRating(3)" style="margin: 5px; padding: 10px; background: #ffaa00; color: white; border: none; border-radius: 5px;">⭐⭐⭐ Good</button>
					<button onclick="submitSimpleRating(4)" style="margin: 5px; padding: 10px; background: #88cc00; color: white; border: none; border-radius: 5px;">⭐⭐⭐⭐ Great</button>
					<button onclick="submitSimpleRating(5)" style="margin: 5px; padding: 10px; background: #00cc44; color: white; border: none; border-radius: 5px;">⭐⭐⭐⭐⭐ Excellent</button>
				</div>
				<button onclick="closeSimpleRating()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px;">Skip</button>
			`;

			// Add overlay
			const overlay = document.createElement('div');
			overlay.style.cssText = `
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0,0,0,0.5);
				z-index: 99998;
			`;

			document.body.appendChild(overlay);
			document.body.appendChild(simplePopup);

			// Global functions for the simple popup
			window.submitSimpleRating = function(rating) {
				console.log('Simple rating submitted:', rating);
				alert(`Thank you for rating us ${rating} stars! Your feedback helps us improve.`);
				closeSimpleRating();
			};

			window.closeSimpleRating = function() {
				if (overlay.parentNode) overlay.remove();
				if (simplePopup.parentNode) simplePopup.remove();
			};
		}

		// Close rating popup
		function closeRatingPopup() {
			if (document.getElementById('ratingPopupOverlay')) {
				document.getElementById('ratingPopupOverlay').classList.remove('show');
				// Clean URL
				if (window.history.replaceState) {
					const url = new URL(window.location);
					url.searchParams.delete('payment');
					url.searchParams.delete('order');
					url.searchParams.delete('ref');
					window.history.replaceState({}, '', url);
				}
			}
		}

		// Skip rating
		function skipRating() {
			closeRatingPopup();
		}

		// Submit rating
		async function submitRating(event) {
			event.preventDefault();

			if (!currentOrderId) {
				alert('Order ID is missing');
				return;
			}

			const comment = document.getElementById('ratingComment').value.trim();
			const doneBtn = document.getElementById('ratingDoneBtn');

			// Disable button
			doneBtn.disabled = true;
			doneBtn.textContent = 'Submitting...';

			try {
				const response = await fetch('actions/submit_rating_action.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						order_id: currentOrderId,
						ease_rating: easeRating,
						satisfaction_rating: satisfactionRating,
						comment: comment
					})
				});

				const data = await response.json();

				if (data.success) {
					if (typeof Swal !== 'undefined') {
						Swal.fire({
							title: 'Thank You!',
							text: 'Your feedback has been submitted successfully.',
							icon: 'success',
							confirmButtonColor: '#008060',
							timer: 2000
						});
					}
					closeRatingPopup();
				} else {
					alert(data.message || 'Failed to submit rating. Please try again.');
					doneBtn.disabled = false;
					doneBtn.textContent = 'Done';
				}
			} catch (error) {
				console.error('Rating submission error:', error);
				alert('An error occurred. Please try again.');
				doneBtn.disabled = false;
				doneBtn.textContent = 'Done';
			}
		}

		// Clear cart after successful payment
		function clearCartAfterPayment() {
			fetch('actions/empty_cart_action.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({})
				}).then(response => response.json())
				.then(data => {
					if (data.success) {
						console.log('Cart cleared successfully after payment');
						// Update cart count in header
						updateCartBadge(0);
					} else {
						console.error('Failed to clear cart:', data.message);
					}
				}).catch(err => {
					console.log('Cart clearing error (non-critical):', err);
				});
		}

		// Debug payment success variables
		console.log('Payment success check:', {
			payment_success: <?php echo json_encode($payment_success); ?>,
			order_id_from_payment: <?php echo json_encode($order_id_from_payment); ?>,
			payment_reference: <?php echo json_encode($payment_reference); ?>,
			order_details: <?php echo json_encode($order_details); ?>
		});

		// Handle payment success
		<?php if ($payment_success && $order_id_from_payment): ?>
			console.log('Payment success handler triggered!');
			document.addEventListener('DOMContentLoaded', function() {
				const orderId = <?php echo json_encode($order_id_from_payment); ?>;
				const orderRef = <?php echo json_encode($payment_reference); ?>;
				const orderDetails = <?php echo json_encode($order_details); ?>;

				// Clear the cart immediately after successful payment
				clearCartAfterPayment();

				// Show order confirmation Sweet Alert
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						title: 'Payment Successful!',
						html: `
						<div style="text-align: left; padding: 10px 0;">
							<p style="margin-bottom: 10px;"><strong>Order ID:</strong> ${orderDetails?.invoice_no || orderDetails?.order_reference || orderId}</p>
							<p style="margin-bottom: 10px;"><strong>Payment Reference:</strong> ${orderRef || 'N/A'}</p>
							<p style="margin-bottom: 10px;"><strong>Total Amount:</strong> GH₵ ${orderDetails?.payment_amount || orderDetails?.total_amount || '0.00'}</p>
							<p style="margin-bottom: 10px;"><strong>Tracking Number:</strong> ${orderDetails?.tracking_number || 'Generating...'}</p>
							<p style="color: #10b981; margin-top: 15px;">Your order has been confirmed and will be processed shortly.</p>
							<p style="color: #6b7280; font-size: 14px; margin-top: 10px;">You can track your order using the tracking number above.</p>
						</div>
					`,
						icon: 'success',
						confirmButtonColor: '#008060',
						confirmButtonText: 'Great!',
						allowOutsideClick: false
					}).then(() => {
						// Send SMS notification
						fetch('actions/send_order_sms_action.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
							},
							body: JSON.stringify({
								order_id: orderId
							})
						}).catch(err => {
							console.log('SMS notification error (non-critical):', err);
						});

						// Show rating popup after confirmation
						setTimeout(() => {
							showRatingPopup(orderId);
						}, 500);
					});
				} else {
					// Fallback if SweetAlert not available
					alert('Payment successful! Order ID: ' + orderId);
					setTimeout(() => {
						showRatingPopup(orderId);
					}, 500);
				}
			});
		<?php else: ?>
			console.log('Payment success handler NOT triggered - PHP conditions not met');
		<?php endif; ?>

		// Fallback: Check URL parameters directly in JavaScript for testing
		document.addEventListener('DOMContentLoaded', function() {
			const urlParams = new URLSearchParams(window.location.search);
			const paymentParam = urlParams.get('payment');
			const orderParam = urlParams.get('order');
			const refParam = urlParams.get('ref');

			console.log('URL parameters:', {
				payment: paymentParam,
				order: orderParam,
				ref: refParam
			});

			// If PHP handler didn't work but we have URL parameters, try JavaScript fallback
			if (paymentParam === 'success' && orderParam) {
				console.log('Triggering fallback payment success handler');

				// Clear cart
				clearCartAfterPayment();

				// Check SweetAlert availability
				console.log('SweetAlert available:', typeof Swal !== 'undefined');

				// Show Sweet Alert
				if (typeof Swal !== 'undefined') {
					console.log('Showing SweetAlert with orderParam:', orderParam);

					// Get a display-friendly order ID - use payment reference if order ID is null/unavailable
					let displayOrderId;
					if (orderParam && orderParam !== 'null' && orderParam !== null) {
						displayOrderId = orderParam;
					} else if (refParam) {
						// Use last part of payment reference as order identifier
						displayOrderId = refParam.split('-').pop() || refParam;
					} else {
						displayOrderId = 'Processing...';
					}

					Swal.fire({
						title: 'Payment Successful!',
						html: `
							<div style="text-align: left; padding: 10px 0;">
								<p style="margin-bottom: 10px;"><strong>Order ID:</strong> ${displayOrderId}</p>
								<p style="margin-bottom: 10px;"><strong>Payment Reference:</strong> ${refParam || 'N/A'}</p>
								<p style="color: #10b981; margin-top: 15px;">Your payment has been confirmed and your order is being processed.</p>
								${orderParam === 'null' || orderParam === null ? '<p style="color: #f59e0b; font-size: 0.9em; margin-top: 10px;">Order details will be available shortly in your account.</p>' : ''}
							</div>
						`,
						icon: 'success',
						confirmButtonColor: '#008060',
						confirmButtonText: 'Great!',
						allowOutsideClick: false
					}).then((result) => {
						console.log('SweetAlert confirmed, result:', result);
						console.log('About to show rating popup for order:', orderParam);

						// Send SMS notification (only if orderParam is valid)
						if (orderParam && orderParam !== 'null' && orderParam !== null) {
							fetch('actions/send_order_sms_action.php', {
								method: 'POST',
								headers: {
									'Content-Type': 'application/json',
								},
								body: JSON.stringify({
									order_id: orderParam
								})
							}).then(response => {
								if (!response.ok) {
									console.log('SMS response not OK:', response.status, response.statusText);
								}
								return response.json();
							}).then(data => {
								console.log('SMS notification result:', data);
							}).catch(err => {
								console.log('SMS notification error (non-critical):', err);
							});
						} else {
							console.log('Skipping SMS notification - orderParam is null/invalid');
						}

						// Show rating popup immediately instead of setTimeout
						console.log('Calling showRatingPopup now...');
						showRatingPopup(orderParam);

						// Also try with a short delay as backup
						setTimeout(() => {
							console.log('Backup rating popup call...');
							if (!document.getElementById('ratingPopupOverlay').classList.contains('show')) {
								console.log('Rating popup not visible, trying again...');
								showRatingPopup(orderParam);
							}
						}, 100);
					});
				} else {
					console.log('SweetAlert not available, using standard alert');
					alert(`Payment Successful!\n\nOrder ID: ${orderParam}\nPayment Reference: ${refParam || 'N/A'}\n\nYour order has been confirmed!`);
					setTimeout(() => {
						showRatingPopup(orderParam);
					}, 1000);
				}
			}
		});
	</script>

	<!-- Rating Popup -->
	<div class="rating-popup-overlay" id="ratingPopupOverlay" onclick="closeRatingPopup()">
		<div class="rating-popup" onclick="event.stopPropagation()">
			<div class="rating-popup-header">
				<h3 class="rating-popup-title">Rate your experience</h3>
				<button class="rating-close-btn" onclick="closeRatingPopup()">×</button>
			</div>

			<form id="ratingForm" onsubmit="submitRating(event)">
				<!-- Question 1: Transaction Ease -->
				<div class="rating-question">
					<div class="rating-question-title">How easy was it for you to complete your transaction?</div>
					<div class="rating-stars" id="easeStars">
						<span class="rating-star" data-rating="1" onclick="setRating('ease', 1)">☆</span>
						<span class="rating-star" data-rating="2" onclick="setRating('ease', 2)">☆</span>
						<span class="rating-star" data-rating="3" onclick="setRating('ease', 3)">☆</span>
						<span class="rating-star" data-rating="4" onclick="setRating('ease', 4)">☆</span>
						<span class="rating-star" data-rating="5" onclick="setRating('ease', 5)">☆</span>
					</div>
					<div class="rating-labels">
						<span>Very difficult</span>
						<span>Neither easy difficult</span>
						<span>Very easy</span>
					</div>
					<input type="hidden" id="easeRating" name="ease_rating" value="0">
				</div>

				<!-- Question 2: Satisfaction -->
				<div class="rating-question">
					<div class="rating-question-title">How satisfied are you with this service?</div>
					<div class="rating-stars" id="satisfactionStars">
						<span class="rating-star" data-rating="1" onclick="setRating('satisfaction', 1)">☆</span>
						<span class="rating-star" data-rating="2" onclick="setRating('satisfaction', 2)">☆</span>
						<span class="rating-star" data-rating="3" onclick="setRating('satisfaction', 3)">☆</span>
						<span class="rating-star" data-rating="4" onclick="setRating('satisfaction', 4)">☆</span>
						<span class="rating-star" data-rating="5" onclick="setRating('satisfaction', 5)">☆</span>
					</div>
					<div class="rating-labels">
						<span>Very dissatisfied</span>
						<span>Neutral</span>
						<span>Very satisfied</span>
					</div>
					<input type="hidden" id="satisfactionRating" name="satisfaction_rating" value="0">
				</div>

				<!-- Comment Section -->
				<div class="rating-comment-section">
					<div class="rating-question-title">Tell us the reason for your score</div>
					<textarea
						class="rating-comment-input"
						id="ratingComment"
						name="comment"
						placeholder="Share your feedback (optional)"></textarea>
				</div>

				<div class="rating-buttons">
					<button type="button" class="rating-btn rating-btn-skip" onclick="skipRating()">Skip</button>
					<button type="submit" class="rating-btn rating-btn-done" id="ratingDoneBtn">Done</button>
				</div>
			</form>
		</div>
	</div>

</body>

</html>