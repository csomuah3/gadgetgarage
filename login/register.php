<?php
session_start();
require_once __DIR__ . '/../controllers/user_controller.php';

$reg_success = '';
$reg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read posted fields
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $role = (int)($_POST['role'] ?? 1);

    // call your existing controller
    $res = register_user_ctr($name, $email, $password, $phone_number, $country, $city, $role);

    if (is_array($res) && ($res['status'] ?? '') === 'success') {
        $reg_success = $res['message'] ?? 'Registration successful. You can now log in.';
    } else {
        $reg_error = is_array($res) ? ($res['message'] ?? 'Registration failed') : 'Registration failed';
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
	<title>Register - Gadget Garage</title>
	<meta name="description" content="Create your Gadget Garage account to access premium tech devices and exclusive deals.">

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
			top: 32px;
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

		/* Register Form Container */
		.register-page-container {
			min-height: calc(100vh - 200px);
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 50px 20px;
			background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
		}

		.register-form-wrapper {
			background: white;
			border-radius: 20px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
			overflow: hidden;
			width: 100%;
			max-width: 600px;
			position: relative;
		}

		.register-form-wrapper::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 6px;
			background: linear-gradient(90deg, #3b82f6, #1e40af, #7c3aed);
		}

		.register-form-header {
			text-align: center;
			padding: 40px 40px 0;
		}

		.register-form-header img {
			height: 80px;
			margin-bottom: 20px;
		}

		.register-form-title {
			font-size: 2rem;
			font-weight: 700;
			color: #1a1a1a;
			margin-bottom: 10px;
		}

		.register-form-subtitle {
			color: #6b7280;
			font-size: 1rem;
			margin-bottom: 30px;
		}

		.register-form-body {
			padding: 0 40px 40px;
		}

		.form-row {
			display: flex;
			gap: 20px;
			margin-bottom: 25px;
		}

		.form-group {
			margin-bottom: 25px;
			flex: 1;
		}

		.form-group.full-width {
			flex: 1 1 100%;
		}

		.form-label {
			display: block;
			font-weight: 600;
			color: #374151;
			margin-bottom: 8px;
			font-size: 0.95rem;
		}

		.form-control {
			width: 100%;
			padding: 15px 18px;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			font-size: 1rem;
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

		.register-btn {
			width: 100%;
			background: linear-gradient(135deg, #3b82f6, #1e40af);
			color: white;
			border: none;
			padding: 16px;
			border-radius: 12px;
			font-size: 1.1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.register-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 12px 30px rgba(59, 130, 246, 0.4);
		}

		.register-btn:active {
			transform: translateY(0);
		}

		.form-links {
			display: flex;
			justify-content: center;
			align-items: center;
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #e5e7eb;
		}

		.login-link {
			color: #3b82f6;
			text-decoration: none;
			font-weight: 600;
		}

		.login-link:hover {
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

			.register-form-wrapper {
				margin: 20px;
			}

			.register-form-header,
			.register-form-body {
				padding: 30px 25px;
			}

			.register-form-title {
				font-size: 1.7rem;
			}

			.form-row {
				flex-direction: column;
				gap: 0;
			}

			.nav-items {
				display: none;
			}

			.nav-menu {
				justify-content: flex-start;
				padding: 0 20px;
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
					<div class="header-icon">
						<a href="../cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
							<i class="fas fa-shopping-cart"></i>
						</a>
					</div>
					<div class="header-icon">
						<a href="login.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
							<i class="fas fa-user"></i>
						</a>
					</div>
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

	<!-- Register Form Section -->
	<div class="register-page-container">
		<div class="register-form-wrapper">
			<div class="register-form-header">
				<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png" alt="Gadget Garage">
				<h1 class="register-form-title">Create Account</h1>
				<p class="register-form-subtitle">Join Gadget Garage today and unlock exclusive deals</p>
			</div>

			<div class="register-form-body">
				<?php if ($reg_error): ?>
					<div class="alert alert-danger">
						<i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($reg_error); ?>
					</div>
				<?php endif; ?>

				<?php if ($reg_success): ?>
					<div class="alert alert-success">
						<i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($reg_success); ?>
					</div>
					<script>
						setTimeout(function() {
							window.location.href = 'login.php';
						}, 2000);
					</script>
				<?php else: ?>
					<form method="POST" id="registerForm">
						<!-- Name and Email Row -->
						<div class="form-row">
							<div class="form-group">
								<label for="name" class="form-label">Full Name</label>
								<div class="input-group">
									<i class="fas fa-user input-icon"></i>
									<input type="text"
										   id="name"
										   name="name"
										   class="form-control with-icon"
										   placeholder="Enter your full name"
										   value="<?php echo htmlspecialchars($name ?? ''); ?>"
										   required>
								</div>
							</div>

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
						</div>

						<!-- Phone and Password Row -->
						<div class="form-row">
							<div class="form-group">
								<label for="phone_number" class="form-label">Phone Number</label>
								<div class="input-group">
									<i class="fas fa-phone input-icon"></i>
									<input type="tel"
										   id="phone_number"
										   name="phone_number"
										   class="form-control with-icon"
										   placeholder="Enter phone number"
										   value="<?php echo htmlspecialchars($phone_number ?? ''); ?>"
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
										   placeholder="Create password"
										   required>
								</div>
							</div>
						</div>

						<!-- Country and City Row -->
						<div class="form-row">
							<div class="form-group">
								<label for="country" class="form-label">Country</label>
								<div class="input-group">
									<i class="fas fa-globe input-icon"></i>
									<select id="country" name="country" class="form-control with-icon" required>
										<option value="">Select Country</option>
										<option value="Ghana" <?php echo (isset($country) && $country === 'Ghana') ? 'selected' : ''; ?>>Ghana</option>
										<option value="Nigeria" <?php echo (isset($country) && $country === 'Nigeria') ? 'selected' : ''; ?>>Nigeria</option>
										<option value="USA" <?php echo (isset($country) && $country === 'USA') ? 'selected' : ''; ?>>United States</option>
										<option value="UK" <?php echo (isset($country) && $country === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
										<option value="Canada" <?php echo (isset($country) && $country === 'Canada') ? 'selected' : ''; ?>>Canada</option>
										<option value="Australia" <?php echo (isset($country) && $country === 'Australia') ? 'selected' : ''; ?>>Australia</option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label for="city" class="form-label">City</label>
								<div class="input-group">
									<i class="fas fa-map-marker-alt input-icon"></i>
									<input type="text"
										   id="city"
										   name="city"
										   class="form-control with-icon"
										   placeholder="Enter your city"
										   value="<?php echo htmlspecialchars($city ?? ''); ?>"
										   required>
								</div>
							</div>
						</div>

						<!-- Hidden role field -->
						<input type="hidden" name="role" value="1">

						<button type="submit" class="register-btn">
							<i class="fas fa-user-plus me-2"></i>
							Create Account
						</button>

						<div class="form-links">
							<span>Already have an account? </span>
							<a href="login.php" class="login-link">Sign In</a>
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
	<script src="../js/register.js"></script>
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
	</script>
</body>

</html>