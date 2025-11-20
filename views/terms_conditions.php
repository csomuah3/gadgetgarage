<?php
session_start();
require_once __DIR__ . '/../settings/core.php';

// Initialize variables
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = false;
$cart_count = 0;

if ($is_logged_in) {
    $is_admin = check_admin();

    // Get cart count for logged in users
    if (!$is_admin) {
        require_once __DIR__ . '/../controllers/cart_controller.php';
        $cart_count = get_cart_count_ctr($_SESSION['user_id']);
    }
}

// Get brands and categories for navigation
require_once __DIR__ . '/../controllers/brand_controller.php';
require_once __DIR__ . '/../controllers/category_controller.php';

$brands = get_all_brands_ctr() ?: [];
$categories = get_all_categories_ctr() ?: [];

$page_title = "Terms & Conditions - GadgetGarage";
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
    <link rel="stylesheet" href="includes/header-styles.css">

    <style>
        /* Header Styles */
        .main-header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 16px 0;
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

        .search-container {
            position: relative;
            flex: 1;
            max-width: 500px;
            margin: 0 40px;
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
            border-color: #008060;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
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
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-50%) scale(1.05);
        }

        .tech-revival-section {
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: center;
            margin: 0 60px;
        }

        .tech-revival-icon {
            font-size: 1.5rem;
            color: #008060;
        }

        .tech-revival-text {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .contact-number {
            font-size: 0.8rem;
            color: #6b7280;
            margin: 0;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 12px;
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
            background: rgba(0, 128, 96, 0.1);
            color: #008060;
        }

        .cart-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, #006b4e, #008060);
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
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
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #008060, #006b4e);
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
            box-shadow: 0 4px 12px rgba(0, 128, 96, 0.3);
        }

        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 128, 96, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 128, 96, 0.15);
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
            background: rgba(0, 128, 96, 0.1);
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
            background: linear-gradient(90deg, transparent, rgba(0, 128, 96, 0.2), transparent);
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

        /* Terms Page Styles */
        .terms-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .terms-content {
            padding: 60px 0;
            background: #f8f9fa;
        }

        .terms-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .terms-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .terms-section h3 {
            color: #374151;
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .terms-section p, .terms-section li {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .terms-section ul {
            padding-left: 20px;
        }

        .last-updated {
            background: #e0e7ff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: #4338ca;
            font-weight: 500;
        }

        .contact-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
        }

        .contact-info h3 {
            color: white !important;
            margin-bottom: 20px;
        }

        .contact-info a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 600;
        }

        .contact-info a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <!-- Promotional Banner -->
    <div class="promo-banner">
        <i class="fas fa-shipping-fast"></i>
        Free Next Day Delivery on Orders Above GH₵2,000!
    </div>

    <!-- Main Header -->
	<header class="main-header animate__animated animate__fadeInDown">
		<div class="container-fluid" style="padding: 0 40px;">
			<div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
				<!-- Logo - Far Left -->
				<a href="index.php" class="logo">
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
					     alt="Gadget Garage"
					     style="height: 40px; width: auto; object-fit: contain;">
				</a>

				<!-- Center Content -->
				<div class="d-flex align-items-center" style="flex: 1; justify-content: center; gap: 60px;">
					<!-- Search Bar -->
					<form class="search-container" method="GET" action="views/product_search_result.php">
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
							<p class="tech-revival-text">Bring Retired Tech</p>
							<p class="contact-number">055-138-7578</p>
						</div>
					</div>
				</div>

				<!-- User Actions - Far Right -->
				<div class="user-actions" style="display: flex; align-items: center; gap: 12px;">
					<span style="color: #ddd;">|</span>
					<?php if ($is_logged_in): ?>
						<!-- Wishlist Icon -->
						<div class="header-icon">
							<a href="wishlist.php" style="color: inherit; text-decoration: none;">
								<i class="fas fa-heart"></i>
							</a>
						</div>

						<!-- Cart Icon -->
						<div class="header-icon">
							<a href="cart.php" style="color: inherit; text-decoration: none;">
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
						<a href="login/login_view.php" class="login-btn">
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
							<li><a href="#"><i class="fas fa-tag"></i> Apple</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> Samsung</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> HP</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> Dell</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> Sony</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> Canon</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> Nikon</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> Microsoft</a></li>
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
									<a href="mobile_devices.php" style="text-decoration: none; color: inherit;">
										<span data-translate="mobile_devices">Mobile Devices</span>
									</a>
								</h4>
								<ul>
									<li><a href="all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
									<li><a href="all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="computing.php" style="text-decoration: none; color: inherit;">
										<span data-translate="computing">Computing</span>
									</a>
								</h4>
								<ul>
									<li><a href="all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
									<li><a href="all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="photography_video.php" style="text-decoration: none; color: inherit;">
										<span data-translate="photography_video">Photography & Video</span>
									</a>
								</h4>
								<ul>
									<li><a href="all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
									<li><a href="all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column featured">
								<h4>Shop All</h4>
								<div class="featured-item">
									<img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
									<div class="featured-text">
										<strong>New Arrivals</strong>
										<p>Latest tech gadgets</p>
										<a href="all_product.php" class="shop-now-btn">Shop Now</a>
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

    <!-- Terms Hero Section -->
    <section class="terms-hero">
        <div class="container">
            <h1><i class="fas fa-file-contract me-3"></i>Terms & Conditions</h1>
            <p class="lead">Please read these terms and conditions carefully before using our service</p>
        </div>
    </section>

    <!-- Terms Content -->
    <section class="terms-content">
        <div class="container">
            <div class="last-updated">
                <i class="fas fa-calendar-alt me-2"></i>
                <strong>Last Updated:</strong> November 2024
            </div>

            <!-- Introduction -->
            <div class="terms-section">
                <h2>1. Introduction</h2>
                <p>Welcome to GadgetGarage. These Terms and Conditions ("Terms", "Terms and Conditions") govern your relationship with GadgetGarage website (the "Service") operated by GadgetGarage ("us", "we", or "our").</p>
                <p>Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms. These Terms apply to all visitors, users and others who access or use the Service.</p>
                <p>By accessing or using our Service you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Service.</p>
            </div>

            <!-- Accounts -->
            <div class="terms-section">
                <h2>2. User Accounts</h2>
                <h3>Account Creation</h3>
                <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for any activities that occur under your account.</p>

                <h3>Account Security</h3>
                <ul>
                    <li>You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account</li>
                    <li>We will not be liable for any loss or damage arising from your failure to comply with this security obligation</li>
                    <li>You must not use another user's account without permission</li>
                </ul>
            </div>

            <!-- Products and Services -->
            <div class="terms-section">
                <h2>3. Products and Services</h2>
                <h3>Product Information</h3>
                <p>We strive to display our products as accurately as possible. However, we cannot guarantee that your device's display of colors or product details will be accurate.</p>

                <h3>Pricing</h3>
                <ul>
                    <li>All prices are listed in Ghana Cedis (GH₵) and are subject to change without notice</li>
                    <li>We reserve the right to modify prices at any time</li>
                    <li>The price charged will be the price displayed at the time of purchase</li>
                </ul>

                <h3>Availability</h3>
                <p>All products are subject to availability. We reserve the right to discontinue any product at any time.</p>
            </div>

            <!-- Orders and Payments -->
            <div class="terms-section">
                <h2>4. Orders and Payments</h2>
                <h3>Order Process</h3>
                <p>When you place an order, you will receive an email confirmation. This confirmation does not constitute our acceptance of your order.</p>

                <h3>Payment Terms</h3>
                <ul>
                    <li>Payment must be made at the time of order</li>
                    <li>We accept various payment methods as displayed during checkout</li>
                    <li>All transactions are processed securely</li>
                </ul>

                <h3>Order Cancellation</h3>
                <p>We reserve the right to refuse or cancel any order for any reason, including but not limited to product availability, errors in product information, or problems with your account.</p>
            </div>

            <!-- Shipping and Returns -->
            <div class="terms-section">
                <h2>5. Shipping and Returns</h2>
                <h3>Shipping Policy</h3>
                <p>We ship within Ghana. Delivery times are estimates and not guaranteed. Shipping costs will be calculated at checkout.</p>

                <h3>Return Policy</h3>
                <ul>
                    <li>Items may be returned within 14 days of delivery</li>
                    <li>Items must be in original condition and packaging</li>
                    <li>Customer is responsible for return shipping costs unless the item is defective</li>
                    <li>Refunds will be processed within 5-7 business days after receiving the returned item</li>
                </ul>
            </div>

            <!-- User Conduct -->
            <div class="terms-section">
                <h2>6. User Conduct</h2>
                <p>You agree not to:</p>
                <ul>
                    <li>Use the Service for any unlawful purpose or to solicit others to perform such acts</li>
                    <li>Violate any local, state, national, or international law</li>
                    <li>Infringe upon or violate our intellectual property rights or the intellectual property rights of others</li>
                    <li>Harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate</li>
                    <li>Submit false or misleading information</li>
                    <li>Upload viruses or any other type of malicious code</li>
                </ul>
            </div>

            <!-- Privacy -->
            <div class="terms-section">
                <h2>7. Privacy Policy</h2>
                <p>Your privacy is important to us. Our Privacy Policy explains how we collect, use, and protect your information when you use our Service. By using our Service, you agree to the collection and use of information in accordance with our Privacy Policy.</p>
            </div>

            <!-- Limitation of Liability -->
            <div class="terms-section">
                <h2>8. Limitation of Liability</h2>
                <p>In no event shall GadgetGarage, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your use of the Service.</p>
            </div>

            <!-- Governing Law -->
            <div class="terms-section">
                <h2>9. Governing Law</h2>
                <p>These Terms shall be interpreted and governed by the laws of Ghana, without regard to its conflict of law provisions. Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights.</p>
            </div>

            <!-- Changes to Terms -->
            <div class="terms-section">
                <h2>10. Changes to Terms</h2>
                <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days notice prior to any new terms taking effect.</p>
                <p>Your continued use of the Service after we post any modifications to the Terms on this page will constitute your acknowledgment of the modifications and your consent to abide and be bound by the modified Terms.</p>
            </div>

            <!-- Contact Information -->
            <div class="contact-info">
                <h3>Contact Information</h3>
                <p>If you have any questions about these Terms & Conditions, please contact us:</p>
                <p>
                    <i class="fas fa-envelope me-2"></i>
                    Email: <a href="mailto:support@gadgetgarage.com">support@gadgetgarage.com</a>
                </p>
                <p>
                    <i class="fas fa-phone me-2"></i>
                    Phone: <a href="tel:+233551387578">+233 55 138 7578</a>
                </p>
                <p>
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Address: Ghana, West Africa
                </p>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Header JavaScript -->
    <script>
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

        function openProfilePictureModal() {
            Swal.fire({title: 'Feature Coming Soon', text: 'Profile picture functionality will be implemented', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
        }

        function changeLanguage(language) {
            localStorage.setItem('selectedLanguage', language);
            console.log('Language changed to:', language);
        }

        function toggleTheme() {
            const toggle = document.getElementById('themeToggle');
            const body = document.body;

            toggle.classList.toggle('active');
            body.classList.toggle('dark-mode');
        }
    </script>
</body>
</html>