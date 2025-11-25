<?php
session_start();
require_once '../settings/db_class.php';

// Handle login
$login_error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';

	if (empty($email) || empty($password)) {
		$login_error = 'Please enter both email and password.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$login_error = 'Please enter a valid email address.';
	} else {
		$db = new db_connection();

		$email_escaped = mysqli_real_escape_string($db->db_conn(), $email);
		$sql = "SELECT * FROM customer WHERE customer_email = '$email_escaped'";

		$user = $db->db_fetch_one($sql);

		if ($user && password_verify($password, $user['customer_pass'])) {
			$_SESSION['user_id'] = $user['customer_id'];
			$_SESSION['user_name'] = $user['customer_name'];
			$_SESSION['user_email'] = $user['customer_email'];
			$_SESSION['email'] = $user['customer_email'];
			$_SESSION['role'] = $user['user_role'];
			$_SESSION['name'] = $user['customer_name'];
			$_SESSION['just_logged_in'] = true; // Flag for newsletter popup

			$login_success = true;
		} else {
			$login_error = 'Invalid email or password.';
		}
	}
}

// Try to load categories and brands for navigation
$categories = [];
$brands = [];

try {
	require_once('../controllers/category_controller.php');
	$categories = get_all_categories_ctr();
} catch (Exception $e) {
	error_log("Failed to load categories: " . $e->getMessage());
}

try {
	require_once('../controllers/brand_controller.php');
	$brands = get_all_brands_ctr();
} catch (Exception $e) {
	error_log("Failed to load brands: " . $e->getMessage());
}

// Get cart count - same as index.php
$is_logged_in = isset($_SESSION['user_id']);
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = 0;
try {
	require_once('../controllers/cart_controller.php');
	$cart_count = get_cart_count_ctr($customer_id, $ip_address);
} catch (Exception $e) {
	error_log("Failed to load cart count: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Login - Gadget Garage</title>
	<meta name="description" content="Log in to your Gadget Garage account to access premium tech devices and exclusive deals.">

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
	<link href="../includes/chatbot-styles.css" rel="stylesheet">
	<link href="../css/dark-mode.css" rel="stylesheet">

	<style>
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

		/* Color Scheme Variables - GadgetGarage Colors */
		:root {
			--gg-teal: #008060;
			--gg-teal-dark: #006b4e;
			--gg-teal-light: #00a67e;
			--gg-green: #10b981;
			--gg-green-dark: #059669;
			--off-white: #FAFAFA;
			--text-dark: #1F2937;
			--text-light: #6B7280;
			--shadow: rgba(0, 128, 96, 0.15);
			--gradient-primary: linear-gradient(135deg, var(--gg-teal-dark) 0%, var(--gg-teal) 50%, var(--gg-teal-light) 100%);
			--gradient-light: linear-gradient(135deg, rgba(0, 128, 96, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
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

		/* Dark Mode Form Styles */
		body.dark-mode .login-page-container {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
		}

		body.dark-mode .login-form-wrapper {
			background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
			border: 1px solid #4a5568;
		}

		body.dark-mode .login-form-wrapper::before {
			background: linear-gradient(90deg, #60a5fa, #3b82f6, #8b5cf6);
		}

		body.dark-mode .login-form-title {
			color: #e2e8f0;
		}

		body.dark-mode .login-form-subtitle {
			color: #cbd5e0;
		}

		body.dark-mode .form-label {
			color: #e2e8f0;
		}

		body.dark-mode .form-control {
			background: #374151;
			border-color: #4a5568;
			color: #e2e8f0;
		}

		body.dark-mode .form-control::placeholder {
			color: #9ca3af;
		}

		body.dark-mode .form-control:focus {
			background: #4a5568;
			border-color: #60a5fa;
			box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
		}

		body.dark-mode .input-icon {
			color: #cbd5e0;
		}

		body.dark-mode .alert-danger {
			background: #374151;
			border: 1px solid #ef4444;
			color: #fca5a5;
		}

		body.dark-mode .alert-success {
			background: #374151;
			border: 1px solid #10b981;
			color: #86efac;
		}

		/* Login Form Container */
		.login-page-container {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 40px 20px;
			min-height: calc(100vh - 200px);
			background: transparent;
		}

		.auth-container {
			width: 100%;
			max-width: 1000px;
			height: 650px;
			position: relative;
			border-radius: 25px;
			overflow: hidden;
			box-shadow: 0 25px 80px var(--shadow);
			backdrop-filter: blur(15px);
			border: 1px solid rgba(255, 255, 255, 0.2);
			display: flex;
		}

		.auth-panels {
			display: flex;
			height: 100%;
			width: 100%;
			position: relative;
		}

		/* Welcome Panel - GadgetGarage Teal/Green Gradient - RIGHT SIDE */
		.welcome-panel {
			flex: 0 0 50%;
			background: var(--gradient-primary);
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			color: white;
			padding: 80px 60px;
			text-align: center;
			position: relative;
			overflow: hidden;
			border-top-left-radius: 50px;
			border-bottom-left-radius: 50px;
		}

		.welcome-panel::before {
			content: '';
			position: absolute;
			top: -50%;
			left: -50%;
			width: 200%;
			height: 200%;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
			animation: float 6s ease-in-out infinite;
		}

		@keyframes float {

			0%,
			100% {
				transform: translateY(0) rotate(0deg);
			}

			50% {
				transform: translateY(-20px) rotate(180deg);
			}
		}

		.brand-logo {
			width: 140px;
			height: auto;
			margin-bottom: 40px;
			filter: brightness(0) invert(1);
			z-index: 2;
			position: relative;
		}

		.welcome-title {
			font-size: 2.8rem;
			font-weight: 700;
			margin-bottom: 20px;
			z-index: 2;
			position: relative;
		}

		.welcome-message {
			font-size: 1.2rem;
			line-height: 1.6;
			opacity: 0.95;
			max-width: 350px;
			margin-bottom: 40px;
			z-index: 2;
			position: relative;
		}

		.welcome-signup-btn,
		.welcome-signin-btn {
			background: transparent;
			border: 2px solid white;
			color: white;
			padding: 16px 40px;
			border-radius: 12px;
			font-size: 1.1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			z-index: 2;
			position: relative;
			text-transform: uppercase;
		}

		.welcome-signup-btn:hover,
		.welcome-signin-btn:hover {
			background: white;
			color: var(--gg-teal);
			transform: translateY(-2px);
			box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
		}

		/* Form Panel - LEFT SIDE */
		.form-panel {
			flex: 0 0 50%;
			background: rgba(255, 255, 255, 0.98);
			backdrop-filter: blur(20px);
			display: flex;
			flex-direction: column;
			justify-content: center;
			padding: 60px 50px;
			position: relative;
			overflow-y: auto;
			max-height: 100%;
		}

		.form-panel::-webkit-scrollbar {
			width: 8px;
		}

		.form-panel::-webkit-scrollbar-track {
			background: rgba(0, 0, 0, 0.05);
		}

		.form-panel::-webkit-scrollbar-thumb {
			background: var(--gg-teal);
			border-radius: 4px;
		}

		.form-container {
			width: 100%;
			max-width: 420px;
			margin: 0 auto;
		}

		.form-header {
			text-align: center;
			margin-bottom: 50px;
		}

		.form-title {
			font-size: 2rem;
			font-weight: 700;
			color: var(--text-dark);
			margin-bottom: 8px;
			text-align: center;
		}

		.form-subtitle {
			color: var(--text-light);
			font-size: 1rem;
			text-align: center;
			margin-bottom: 30px;
		}

		/* Toggle Buttons */
		.form-toggle {
			display: flex;
			background: #f8f9ff;
			border-radius: 15px;
			padding: 6px;
			margin-bottom: 40px;
			position: relative;
		}

		.toggle-btn {
			flex: 1;
			padding: 16px 25px;
			background: transparent;
			border: none;
			border-radius: 12px;
			font-weight: 600;
			font-size: 1.1rem;
			color: var(--text-light);
			transition: all 0.3s ease;
			cursor: pointer;
			position: relative;
			z-index: 2;
		}

		.toggle-btn.active {
			color: white;
		}

		.toggle-slider {
			position: absolute;
			top: 6px;
			left: 6px;
			width: calc(50% - 6px);
			height: calc(100% - 12px);
			background: var(--gradient-primary);
			border-radius: 12px;
			transition: transform 0.3s ease;
			box-shadow: 0 4px 20px rgba(0, 128, 96, 0.3);
		}

		/* Social Login Buttons */
		.social-login {
			margin-bottom: 30px;
		}

		.social-login h4 {
			text-align: center;
			color: var(--text-dark);
			font-size: 1.1rem;
			font-weight: 600;
			margin-bottom: 20px;
		}

		.social-buttons {
			display: flex;
			gap: 15px;
			justify-content: center;
			margin-bottom: 25px;
		}

		.social-btn {
			width: 50px;
			height: 50px;
			border-radius: 12px;
			border: 2px solid #e5e7eb;
			background: white;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: all 0.3s ease;
			font-size: 1.3rem;
		}

		.social-btn.google {
			color: #ea4335;
		}

		.social-btn.facebook {
			color: #1877f2;
		}

		.social-btn.pinterest {
			color: #bd081c;
		}

		.social-btn.linkedin {
			color: #0077b5;
		}

		.social-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
		}

		.divider {
			text-align: center;
			position: relative;
			margin: 25px 0;
		}

		.divider::before {
			content: '';
			position: absolute;
			top: 50%;
			left: 0;
			right: 0;
			height: 1px;
			background: #e5e7eb;
		}

		.divider span {
			background: rgba(255, 255, 255, 0.98);
			padding: 0 20px;
			color: var(--text-light);
			font-size: 1.1rem;
			font-weight: 500;
		}



		.form-group {
			margin-bottom: 20px;
		}

		.form-label {
			display: block;
			font-weight: 600;
			color: var(--text-dark);
			margin-bottom: 10px;
			font-size: 1rem;
		}

		.form-control {
			width: 100%;
			padding: 16px 20px 16px 50px;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			background: #f8fafc;
			color: var(--text-dark);
			font-size: 1rem;
			transition: all 0.3s ease;
			outline: none;
		}

		.form-control:focus {
			border-color: var(--gg-teal);
			background: white;
			box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
		}

		.input-group {
			position: relative;
		}

		.input-icon {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: var(--gg-teal);
			font-size: 1.1rem;
			z-index: 2;
		}

		.ghana-flag {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			width: 24px;
			height: 16px;
			z-index: 2;
		}

		.form-control.with-icon {
			padding-left: 55px;
		}

		.form-control.with-flag {
			padding-left: 55px;
		}

		.submit-btn {
			width: 100%;
			background: var(--gradient-primary);
			color: white;
			border: none;
			padding: 18px;
			border-radius: 12px;
			font-size: 1.1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
			margin-top: 10px;
		}

		.submit-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 12px 30px rgba(0, 128, 96, 0.4);
		}

		.submit-btn:active {
			transform: translateY(0);
		}

		.form-links {
			display: flex;
			justify-content: center;
			align-items: center;
			margin-top: 25px;
			gap: 30px;
		}

		.forgot-password {
			color: var(--gg-teal);
			text-decoration: none;
			font-size: 1rem;
			font-weight: 500;
		}

		.forgot-password:hover {
			text-decoration: underline;
		}

		.signup-link {
			color: var(--gg-teal);
			text-decoration: none;
			font-weight: 600;
			font-size: 1rem;
		}

		.signup-link:hover {
			text-decoration: underline;
		}

		/* Hide content initially */
		.form-content {
			display: none;
		}

		.form-content.active {
			display: block;
		}

		.alert {
			border-radius: 12px;
			margin-bottom: 20px;
			border: none;
			padding: 15px 18px;
		}

		.alert-danger {
			background: #fee2e2;
			color: #dc2626;
		}

		.alert-success {
			background: #d1fae5;
			color: #059669;
		}

		/* Mobile Responsive */
		@media (max-width: 768px) {
			.main-header .container-fluid {
				padding: 0 20px !important;
			}

			.search-container {
				max-width: 300px;
			}

			.tech-revival-section {
				display: none;
			}

			.auth-container {
				height: auto;
				min-height: 600px;
				margin: 20px;
			}

			.auth-container {
				flex-direction: column;
			}

			.welcome-panel,
			.form-panel {
				flex: 0 0 100%;
				min-height: 500px;
			}

			.welcome-panel {
				border-top-left-radius: 25px;
				border-top-right-radius: 25px;
				border-bottom-left-radius: 0;
			}

			.welcome-panel {
				padding: 50px 30px;
			}

			.form-panel {
				padding: 40px 30px;
			}

			.welcome-title {
				font-size: 2rem;
			}

			.form-title {
				font-size: 1.7rem;
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

	<!-- Login Form Section -->
	<div class="login-page-container">
		<div class="auth-container">
			<div class="auth-panels" id="authPanels">

				<!-- Welcome Panel (Teal/Green) - RIGHT SIDE -->
				<div class="welcome-panel">
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/ChatGPT_Image_Nov_19__2025__11_50_42_PM-removebg-preview.png"
						alt="Gadget Garage Logo" class="brand-logo">
					<h1 class="welcome-title" id="welcomeTitle">Welcome Back!</h1>
					<p class="welcome-message" id="welcomeMessage">Provide your personal details to use all features</p>
					<button class="welcome-signin-btn" id="welcomeSigninBtn" onclick="switchToLogin()" style="display: none;">SIGN IN</button>
					<button class="welcome-signup-btn" id="welcomeSignupBtn" onclick="switchToSignup()">SIGN UP</button>
				</div>

				<!-- Form Panel (White) -->
				<div class="form-panel">
					<div class="form-container">
						<div class="form-header">
							<h2 class="form-title" id="formTitle">Login With</h2>
							<p class="form-subtitle" id="formSubtitle">Login With Your Email & Password</p>
						</div>

						<!-- Toggle Buttons -->
						<div class="form-toggle">
							<div class="toggle-slider" id="toggleSlider"></div>
							<button class="toggle-btn active" id="loginTab" onclick="switchToLogin()">Login</button>
							<button class="toggle-btn" id="signupTab" onclick="switchToSignup()">Join GadgetGarage</button>
						</div>

						<!-- Social Login Buttons -->
						<div class="social-login">
							<div class="social-buttons">
								<div class="social-btn google">
									<span style="font-weight: 700; color: #ea4335;">G</span>
								</div>
								<div class="social-btn facebook">
									<span style="font-weight: 700; color: #1877f2;">f</span>
								</div>
								<div class="social-btn pinterest">
									<span style="font-weight: 700; color: #bd081c;">P</span>
								</div>
								<div class="social-btn linkedin">
									<span style="font-weight: 700; color: #0077b5;">in</span>
								</div>
							</div>
							<div class="divider">
								<span>OR</span>
							</div>
						</div>

						<!-- Login Form -->
						<div id="loginForm" class="form-content active">
							<?php if ($login_error): ?>
								<div class="alert alert-danger">
									<i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($login_error); ?>
								</div>
							<?php endif; ?>

							<?php if ($login_success): ?>
								<div class="alert alert-success animate__animated animate__fadeInUp">
									<i class="fas fa-check-circle me-2"></i>Login successful! Redirecting...
								</div>
								<script>
									setTimeout(function() {
										window.location.href = '../index.php';
									}, 1500);
								</script>
							<?php else: ?>
								<form method="POST" id="actualLoginForm">
									<div class="form-group">
										<label for="email" class="form-label">Email</label>
										<div class="input-group">
											<i class="fas fa-envelope input-icon"></i>
											<input type="email"
												id="email"
												name="email"
												class="form-control with-icon"
												placeholder="Enter your email"
												value="<?php echo htmlspecialchars($email ?? ''); ?>"
												required>
										</div>
									</div>

									<div class="form-group">
										<label for="password" class="form-label">Password</label>
										<div class="input-group">
											<i class="fas fa-lock input-icon"></i>
											<input type="password"
												id="password"
												name="password"
												class="form-control with-icon"
												placeholder="Enter your password"
												required>
										</div>
									</div>

									<button type="submit" class="submit-btn">
										LOGIN
									</button>

									<div class="form-links">
										<a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
									</div>
								</form>
							<?php endif; ?>
						</div>

						<!-- Sign Up Form -->
						<div id="signupForm" class="form-content">
							<div id="signupAlert" style="display: none;"></div>
							<form id="actualSignupForm" method="POST" action="../actions/register_user_action.php">
								<div class="form-group">
									<label for="signup_name" class="form-label">Full Name</label>
									<div class="input-group">
										<i class="fas fa-user input-icon"></i>
										<input type="text"
											id="signup_name"
											name="name"
											class="form-control with-icon"
											placeholder="Enter your full name"
											required>
									</div>
								</div>

								<div class="form-group">
									<label for="signup_email" class="form-label">Email</label>
									<div class="input-group">
										<i class="fas fa-envelope input-icon"></i>
										<input type="email"
											id="signup_email"
											name="email"
											class="form-control with-icon"
											placeholder="Enter your email"
											required>
									</div>
								</div>

								<div class="form-group">
									<label for="signup_phone" class="form-label">Phone Number</label>
									<div class="input-group">
										<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3Crect width='300' height='67' fill='%23CE1126'/%3E%3Crect y='67' width='300' height='67' fill='%23FCD116'/%3E%3Crect y='133' width='300' height='67' fill='%23006B3F'/%3E%3Cpolygon points='150,80 160,110 190,110 170,130 180,160 150,140 120,160 130,130 110,110 140,110' fill='%23000'/%3E%3C/svg%3E" alt="Ghana Flag" class="ghana-flag">
										<input type="tel"
											id="signup_phone"
											name="phone_number"
											class="form-control with-flag"
											placeholder="your phone number"
											required>
									</div>
								</div>

								<div class="form-group">
									<label for="signup_country" class="form-label">Country</label>
									<div class="input-group">
										<i class="fas fa-globe input-icon"></i>
										<select id="signup_country" name="country" class="form-control with-icon" required>
											<option value="">Select Country</option>
											<option value="Ghana" selected>Ghana</option>
											<option value="Nigeria">Nigeria</option>
											<option value="USA">United States</option>
											<option value="UK">United Kingdom</option>
											<option value="Canada">Canada</option>
											<option value="Australia">Australia</option>
										</select>
									</div>
								</div>

								<div class="form-group">
									<label for="signup_city" class="form-label">City</label>
									<div class="input-group">
										<i class="fas fa-map-marker-alt input-icon"></i>
										<input type="text"
											id="signup_city"
											name="city"
											class="form-control with-icon"
											placeholder="Enter your city"
											required>
									</div>
								</div>

								<input type="hidden" name="role" value="1">

								<div class="form-group">
									<label for="signup_password" class="form-label">Password</label>
									<div class="input-group">
										<i class="fas fa-lock input-icon"></i>
										<input type="password"
											id="signup_password"
											name="password"
											class="form-control with-icon"
											placeholder="Create a password"
											required>
									</div>
								</div>

								<button type="submit" class="submit-btn">
									SIGN UP
								</button>
							</form>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Footer spacer -->
	<div style="height: 100px;"></div>

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
			window.location.href = '../views/my_orders.php';
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

			// Initialize login view on page load
			switchToLogin();
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

		// Auth Panel Switching Functions
		function switchToLogin() {
			const loginForm = document.getElementById('loginForm');
			const signupForm = document.getElementById('signupForm');
			const loginTab = document.getElementById('loginTab');
			const signupTab = document.getElementById('signupTab');
			const toggleSlider = document.getElementById('toggleSlider');
			const welcomeTitle = document.getElementById('welcomeTitle');
			const welcomeMessage = document.getElementById('welcomeMessage');
			const formTitle = document.getElementById('formTitle');
			const formSubtitle = document.getElementById('formSubtitle');
			const welcomeSigninBtn = document.getElementById('welcomeSigninBtn');
			const welcomeSignupBtn = document.getElementById('welcomeSignupBtn');

			// Update form visibility
			loginForm.classList.add('active');
			signupForm.classList.remove('active');

			// Update toggle buttons
			loginTab.classList.add('active');
			signupTab.classList.remove('active');

			// Move toggle slider to left
			toggleSlider.style.transform = 'translateX(0)';

			// Update welcome panel content for LOGIN view
			welcomeTitle.textContent = 'Welcome Back!';
			welcomeMessage.textContent = 'Provide your personal details to use all features';
			welcomeSigninBtn.style.display = 'block';
			welcomeSignupBtn.style.display = 'none';

			// Update form title and subtitle
			formTitle.textContent = 'Login With';
			formSubtitle.textContent = 'Login With Your Email & Password';
		}

		function switchToSignup() {
			const loginForm = document.getElementById('loginForm');
			const signupForm = document.getElementById('signupForm');
			const loginTab = document.getElementById('loginTab');
			const signupTab = document.getElementById('signupTab');
			const toggleSlider = document.getElementById('toggleSlider');
			const welcomeTitle = document.getElementById('welcomeTitle');
			const welcomeMessage = document.getElementById('welcomeMessage');
			const formTitle = document.getElementById('formTitle');
			const formSubtitle = document.getElementById('formSubtitle');
			const welcomeSigninBtn = document.getElementById('welcomeSigninBtn');
			const welcomeSignupBtn = document.getElementById('welcomeSignupBtn');

			// Update form visibility
			loginForm.classList.remove('active');
			signupForm.classList.add('active');

			// Update toggle buttons
			loginTab.classList.remove('active');
			signupTab.classList.add('active');

			// Move toggle slider to right
			toggleSlider.style.transform = 'translateX(100%)';

			// Update welcome panel content for SIGNUP view
			welcomeTitle.textContent = 'Hello!';
			welcomeMessage.textContent = 'Register to use all features in our site';
			welcomeSigninBtn.style.display = 'none';
			welcomeSignupBtn.style.display = 'block';

			// Update form title and subtitle
			formTitle.textContent = 'Register With';
			formSubtitle.textContent = 'Fill Out The Following Info For Registration';
		}

		// Handle signup form submission
		document.addEventListener('DOMContentLoaded', function() {
			const signupForm = document.getElementById('actualSignupForm');
			const signupAlert = document.getElementById('signupAlert');

			if (signupForm) {
				signupForm.addEventListener('submit', async function(e) {
					e.preventDefault();

					const formData = new FormData(signupForm);
					const submitBtn = signupForm.querySelector('.submit-btn');
					const originalBtnText = submitBtn.textContent;

					// Disable button
					submitBtn.disabled = true;
					submitBtn.textContent = 'Signing Up...';

					// Hide previous alerts
					signupAlert.style.display = 'none';

					try {
						const response = await fetch('../actions/register_user_action.php', {
							method: 'POST',
							body: formData
						});

						const result = await response.json();

						if (result.status === 'success') {
							signupAlert.className = 'alert alert-success';
							signupAlert.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + result.message;
							signupAlert.style.display = 'block';

							// Redirect to login after 2 seconds
							setTimeout(() => {
								window.location.href = 'login.php';
							}, 2000);
						} else {
							signupAlert.className = 'alert alert-danger';
							signupAlert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (result.message || 'Registration failed');
							signupAlert.style.display = 'block';
							submitBtn.disabled = false;
							submitBtn.textContent = originalBtnText;
						}
					} catch (error) {
						signupAlert.className = 'alert alert-danger';
						signupAlert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.';
						signupAlert.style.display = 'block';
						submitBtn.disabled = false;
						submitBtn.textContent = originalBtnText;
						console.error('Signup error:', error);
					}
				});
			}
		});
	</script>
</body>

</html>