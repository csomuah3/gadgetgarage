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

	<style>
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

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

		/* Navigation Styles */
		.main-nav {
			background: #f8fafc;
			border-bottom: 1px solid #e5e7eb;
			position: sticky;
			top: 112px;
			z-index: 999;
		}

		.nav-menu {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 0;
		}

		.shop-categories-btn {
			position: relative;
		}

		.categories-button {
			background: linear-gradient(135deg, #3b82f6, #1e40af);
			color: white;
			border: none;
			padding: 15px 30px;
			border-radius: 8px;
			font-weight: 600;
			font-size: 1rem;
			cursor: pointer;
			transition: all 0.3s ease;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.categories-button:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
		}

		.nav-items {
			display: flex;
			list-style: none;
			margin: 0;
			padding: 0;
			gap: 30px;
			align-items: center;
			margin-left: 50px;
		}

		.nav-item a {
			color: #1f2937;
			text-decoration: none;
			font-weight: 500;
			font-size: 1rem;
			padding: 15px 0;
			transition: color 0.3s ease;
			position: relative;
		}

		.nav-item a:hover {
			color: #3b82f6;
		}

		.nav-item a::after {
			content: '';
			position: absolute;
			bottom: 10px;
			left: 0;
			width: 0;
			height: 2px;
			background: #3b82f6;
			transition: width 0.3s ease;
		}

		.nav-item a:hover::after {
			width: 100%;
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

		body.dark-mode .main-nav {
			background: #2d3748;
			border-bottom-color: #4a5568;
		}

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

		body.dark-mode .nav-item a {
			color: #e2e8f0;
		}

		body.dark-mode .nav-item a:hover {
			color: #60a5fa;
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
			min-height: calc(100vh - 200px);
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 50px 20px;
			background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
		}

		.login-form-wrapper {
			background: white;
			border-radius: 20px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
			overflow: hidden;
			width: 100%;
			max-width: 500px;
			min-height: 650px;
			position: relative;
		}

		.login-form-wrapper::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 6px;
			background: linear-gradient(90deg, #3b82f6, #1e40af, #7c3aed);
		}

		.login-form-header {
			text-align: center;
			padding: 25px 40px 0;
		}

		.login-form-header img {
			height: 80px;
			margin-bottom: 15px;
		}

		.login-form-title {
			font-size: 2.4rem;
			font-weight: 700;
			color: #1a1a1a;
			margin-bottom: 8px;
		}

		.login-form-subtitle {
			color: #6b7280;
			font-size: 1.2rem;
			margin-bottom: 25px;
		}

		.login-form-body {
			padding: 0 40px 40px;
		}

		.form-group {
			margin-bottom: 25px;
		}

		.form-label {
			display: block;
			font-weight: 600;
			color: #374151;
			margin-bottom: 8px;
			font-size: 1.1rem;
		}

		.form-control {
			width: 100%;
			padding: 18px 20px;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			font-size: 1.1rem;
			transition: all 0.3s ease;
			background: #f8fafc;
		}

		.form-control:focus {
			outline: none;
			border-color: #3b82f6;
			background: white;
			box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
		}

		.input-group {
			position: relative;
		}

		.input-icon {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: #9ca3af;
			font-size: 1.1rem;
		}

		.form-control.with-icon {
			padding-left: 50px;
		}

		.login-btn {
			width: 100%;
			background: linear-gradient(135deg, #3b82f6, #1e40af);
			color: white;
			border: none;
			padding: 18px;
			border-radius: 12px;
			font-size: 1.2rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.login-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 12px 30px rgba(59, 130, 246, 0.4);
		}

		.login-btn:active {
			transform: translateY(0);
		}

		.form-links {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #e5e7eb;
		}

		.forgot-password {
			color: #3b82f6;
			text-decoration: none;
			font-size: 0.9rem;
			font-weight: 500;
		}

		.forgot-password:hover {
			text-decoration: underline;
		}

		.signup-link {
			color: #3b82f6;
			text-decoration: none;
			font-weight: 600;
		}

		.signup-link:hover {
			text-decoration: underline;
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

			.login-form-wrapper {
				margin: 20px;
			}

			.login-form-header,
			.login-form-body {
				padding: 30px 25px;
			}

			.login-form-title {
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
		<div class="container-fluid" style="padding: 0 120px 0 95px;">
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
							<a href="../wishlist.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
								<i class="fas fa-heart"></i>
								<span class="wishlist-badge" id="wishlistBadge" style="display: none;">0</span>
							</a>
						</div>

						<!-- Cart Icon -->
						<div class="header-icon">
							<a href="../cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
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
								<a href="logout.php" class="dropdown-item-custom">
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
						<span>SHOP BY BRANDS</span>
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
								<li><a href="#"><i class="fas fa-tag"></i> Apple</a></li>
								<li><a href="#"><i class="fas fa-tag"></i> Samsung</a></li>
								<li><a href="#"><i class="fas fa-tag"></i> HP</a></li>
								<li><a href="#"><i class="fas fa-tag"></i> Dell</a></li>
								<li><a href="#"><i class="fas fa-tag"></i> Sony</a></li>
								<li><a href="#"><i class="fas fa-tag"></i> Canon</a></li>
								<li><a href="#"><i class="fas fa-tag"></i> Nikon</a></li>
								<li><a href="#"><i class="fas fa-tag"></i> Microsoft</a></li>
							<?php endif; ?>
						</ul>
					</div>
				</div>

				<!-- Navigation Items -->
				<ul class="nav-items">
					<li class="nav-item"><a href="../index.php">Home</a></li>
					<li class="nav-item"><a href="../all_product.php">All Products</a></li>
					<li class="nav-item"><a href="../mobile_devices.php">Mobile Devices</a></li>
					<li class="nav-item"><a href="../computing.php">Computing</a></li>
					<li class="nav-item"><a href="../photography_video.php">Photography & Video</a></li>
					<li class="nav-item"><a href="../repair_services.php">Repair Services</a></li>
				</ul>
			</div>
		</div>
	</nav>

	<!-- Login Form Section -->
	<div class="login-page-container">
		<div class="login-form-wrapper">
			<div class="login-form-header">
				<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png" alt="Gadget Garage">
				<h1 class="login-form-title">Welcome Back</h1>
				<p class="login-form-subtitle">Please sign in to your account</p>
			</div>

			<div class="login-form-body">
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
						// Add fly-up animation to the entire form
						document.querySelector('.login-form-wrapper').classList.add('animate__animated', 'animate__fadeOutUp');
						setTimeout(function() {
							window.location.href = '../index.php';
						}, 1500);
					</script>
				<?php else: ?>
					<form method="POST" id="loginForm">
						<div class="form-group">
							<label for="email" class="form-label">Email Address</label>
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

						<button type="submit" class="login-btn">
							<i class="fas fa-sign-in-alt me-2"></i>
							Sign In
						</button>

						<div class="form-links">
							<a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
							<a href="register.php" class="signup-link">Create Account</a>
						</div>
					</form>
				<?php endif; ?>
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
	</script>
</body>

</html>