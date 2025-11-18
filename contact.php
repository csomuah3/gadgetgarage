<?php
require_once(__DIR__ . '/settings/core.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count for logged in users
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
require_once(__DIR__ . '/controllers/cart_controller.php');
$cart_count = get_cart_count_ctr($customer_id, $ip_address);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - Gadget Garage</title>
    <meta name="description" content="Get in touch with Gadget Garage. Contact us for support, repairs, or any questions about our premium tech devices.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ffffff;
            color: #1a202c;
            line-height: 1.6;
        }

        /* Promotional Banner Styles */
        .promo-banner {
            background: linear-gradient(90deg, #16a085, #f39c12);
            color: white;
            text-align: center;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .promo-banner .fas {
            font-size: 16px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-5px);
            }
            60% {
                transform: translateY(-3px);
            }
        }

        /* Header Styles */
        .main-header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 44px;
            z-index: 1000;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .garage {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
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
            gap: 32px;
        }

        .nav-item {
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            padding: 12px 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item:hover, .nav-item.active {
            color: #008060;
        }

        .categories-button {
            background: #4f63d2;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .categories-button:hover {
            background: #3d4fd1;
        }

        /* Contact Hero Section */
        .contact-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .contact-hero .container {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            font-weight: 400;
        }

        /* Contact Content Section */
        .contact-content {
            padding: 80px 0;
            background: #f8fafc;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            color: #1a202c;
        }

        .section-subtitle {
            font-size: 1.1rem;
            text-align: center;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto 60px;
        }

        /* Contact Cards */
        .contact-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .contact-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .contact-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1a202c;
        }

        .contact-card p {
            color: #64748b;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .contact-detail {
            font-weight: 600;
            color: #1a202c;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .contact-detail a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-detail a:hover {
            color: #764ba2;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-top: 60px;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 15px;
            color: #1a202c;
        }

        .form-subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: block;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-control.textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Map Section */
        .map-section {
            padding: 80px 0;
            background: white;
        }

        .map-container {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            height: 450px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .map-placeholder {
            text-align: center;
            color: #64748b;
        }

        .map-placeholder i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e0;
        }

        /* Business Hours */
        .business-hours {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .hours-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .hours-item:last-child {
            border-bottom: none;
        }

        .hours-day {
            font-weight: 600;
            color: #374151;
        }

        .hours-time {
            color: #64748b;
        }

        .hours-item.today {
            background: #f0f9ff;
            margin: 0 -20px;
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
        }

        .hours-item.today .hours-day {
            color: #0369a1;
        }

        .hours-item.today .hours-time {
            color: #0369a1;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .contact-card {
                padding: 30px;
                margin-bottom: 30px;
            }

            .contact-form {
                padding: 30px;
                margin-top: 40px;
            }

            .nav-menu {
                gap: 20px;
                flex-wrap: wrap;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .contact-hero {
                padding: 60px 0;
            }

            .hero-title {
                font-size: 2rem;
            }

            .contact-content {
                padding: 60px 0;
            }

            .map-section {
                padding: 60px 0;
            }

            .contact-form {
                padding: 25px;
            }
        }

        /* Animation Classes */
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }

        .fade-in-up.delay-1 { animation-delay: 0.1s; }
        .fade-in-up.delay-2 { animation-delay: 0.2s; }
        .fade-in-up.delay-3 { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Success/Error Message Styles */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: none;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Footer Styles */
        .main-footer {
            background: #1f2937;
            color: #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 0;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
        }

        .footer-logo .garage {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .footer-description {
            color: #9ca3af;
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
            background: #374151;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
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
            color: #9ca3af;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #667eea;
            transform: translateX(4px);
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: #374151;
            margin: 40px 0 20px;
        }

        .copyright {
            color: #9ca3af;
            font-size: 0.9rem;
            margin: 0;
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
        <i class="fas fa-shipping-fast"></i>
        Free Next Day Delivery on Orders Above GH₵2,000!
    </div>

	<!-- Main Header -->
	<header class="main-header animate__animated animate__fadeInDown">
		<div class="container-fluid" style="padding: 0 120px 0 95px;">
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
							<p class="tech-revival-text">Bring Retired Tech</p>
							<p class="contact-number">055-138-7578</p>
						</div>
					</div>
				</div>

				<!-- User Actions - Far Right -->
				<div class="user-actions" style="display: flex; align-items: center; gap: 12px;">
					<span style="color: #ddd;">|</span>
					<?php if (isset($_SESSION['user_id'])): ?>
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

    <!-- Contact Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <h1 class="hero-title animate__animated animate__fadeInDown">Get In Touch</h1>
            <p class="hero-subtitle animate__animated animate__fadeInUp animate__delay-1s">
                Ready to experience the best in tech? We're here to help with expert advice, premium devices, and exceptional service.
            </p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="contact-content">
        <div class="container">
            <div class="text-center mb-5 fade-in-up">
                <h2 class="section-title">Contact Information</h2>
                <p class="section-subtitle">
                    Multiple ways to reach us. Choose what works best for you.
                </p>
            </div>

            <div class="row">
                <!-- Phone Contact -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="contact-card fade-in-up delay-1">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Call Us</h3>
                        <p>Speak directly with our tech experts for immediate assistance and personalized recommendations.</p>
                        <div class="contact-detail">
                            <a href="tel:055-138-7578">055-138-7578</a>
                        </div>
                        <div class="contact-detail">
                            <a href="tel:+233551387578">+233 55 138 7578</a>
                        </div>
                    </div>
                </div>

                <!-- Email Contact -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="contact-card fade-in-up delay-2">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Support</h3>
                        <p>Send us detailed inquiries and we'll respond within 24 hours with comprehensive solutions.</p>
                        <div class="contact-detail">
                            <a href="mailto:info@gadgetgarage.gh">info@gadgetgarage.gh</a>
                        </div>
                        <div class="contact-detail">
                            <a href="mailto:support@gadgetgarage.gh">support@gadgetgarage.gh</a>
                        </div>
                    </div>
                </div>

                <!-- Location Contact -->
                <div class="col-lg-4 col-md-12 mb-4">
                    <div class="contact-card fade-in-up delay-3">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Visit Our Store</h3>
                        <p>Experience our products firsthand at our flagship store with expert consultations and live demos.</p>
                        <div class="contact-detail">
                            Gadget Garage Plaza<br>
                            Oxford Street, Osu<br>
                            Accra, Ghana
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Hours -->
            <div class="row mt-5">
                <div class="col-lg-6 offset-lg-3">
                    <div class="business-hours fade-in-up">
                        <h3 class="text-center mb-4" style="color: #1a202c; font-weight: 600;">Business Hours</h3>
                        <div class="hours-item">
                            <span class="hours-day">Monday - Friday</span>
                            <span class="hours-time">9:00 AM - 7:00 PM</span>
                        </div>
                        <div class="hours-item">
                            <span class="hours-day">Saturday</span>
                            <span class="hours-time">10:00 AM - 6:00 PM</span>
                        </div>
                        <div class="hours-item">
                            <span class="hours-day">Sunday</span>
                            <span class="hours-time">12:00 PM - 5:00 PM</span>
                        </div>
                        <div class="hours-item">
                            <span class="hours-day">Public Holidays</span>
                            <span class="hours-time">Closed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form fade-in-up">
                <h3 class="form-title">Send Us a Message</h3>
                <p class="form-subtitle">Have a specific question or need personalized assistance? Drop us a line!</p>

                <div class="alert alert-success" id="successAlert">
                    <i class="fas fa-check-circle me-2"></i>
                    Thank you for your message! We'll get back to you within 24 hours.
                </div>

                <div class="alert alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="errorMessage">Something went wrong. Please try again.</span>
                </div>

                <form id="contactForm" action="actions/contact_action.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required placeholder="Enter your full name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required placeholder="Enter your email address">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="Enter your phone number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Subject *</label>
                                <select name="subject" class="form-control" required>
                                    <option value="">Select a subject</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="support">Technical Support</option>
                                    <option value="sales">Sales & Products</option>
                                    <option value="repair">Repair Services</option>
                                    <option value="warranty">Warranty Claims</option>
                                    <option value="feedback">Feedback & Suggestions</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-control textarea" required placeholder="Tell us how we can help you..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <div class="text-center mb-5 fade-in-up">
                <h2 class="section-title">Find Our Store</h2>
                <p class="section-subtitle">
                    Located in the heart of Accra, easily accessible by public transport and with ample parking.
                </p>
            </div>

            <div class="map-container fade-in-up">
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt"></i>
                    <h4>Interactive Map</h4>
                    <p>Gadget Garage Plaza<br>Oxford Street, Osu, Accra, Ghana</p>
                    <p><small>* Map integration can be added with Google Maps API</small></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-brand">
                        <div class="footer-logo" style="margin-bottom: 16px;">
                            <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                                 alt="Gadget Garage"
                                 style="height: 35px; width: auto; object-fit: contain;">
                        </div>
                        <p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions. Experience technology like never before.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="all_product.php">All Products</a></li>
                        <li><a href="cart.php">Shopping Cart</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="legal.php">Legal</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Products</h5>
                    <ul class="footer-links">
                        <li><a href="all_product.php?category=phones">Smartphones</a></li>
                        <li><a href="all_product.php?category=laptops">Laptops</a></li>
                        <li><a href="all_product.php?category=ipads">Tablets</a></li>
                        <li><a href="all_product.php?category=cameras">Cameras</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="footer-title">Contact Info</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt me-2"></i>Oxford Street, Osu, Accra</li>
                        <li><i class="fas fa-phone me-2"></i>055-138-7578</li>
                        <li><i class="fas fa-envelope me-2"></i>info@gadgetgarage.gh</li>
                        <li><i class="fas fa-clock me-2"></i>Mon-Fri: 9AM-7PM</li>
                    </ul>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="copyright">Designed with <i class="fas fa-heart" style="color: #667eea;"></i> for tech enthusiasts</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dark-mode.js"></script>
    <script src="js/header.js"></script>
    <script>
        // Animate elements on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.fade-in-up');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });

            elements.forEach(element => {
                observer.observe(element);
            });
        }

        // Handle contact form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('.submit-btn');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;

            // Hide previous alerts
            successAlert.style.display = 'none';
            errorAlert.style.display = 'none';

            fetch('actions/contact_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successAlert.style.display = 'block';
                    form.reset();

                    // Scroll to success message
                    successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    errorAlert.style.display = 'block';
                    document.getElementById('errorMessage').textContent = data.message || 'Something went wrong. Please try again.';

                    // Scroll to error message
                    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorAlert.style.display = 'block';
                document.getElementById('errorMessage').textContent = 'Network error. Please check your connection and try again.';

                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                submitBtn.disabled = false;
            });
        });

        // Add current day highlighting to business hours
        function highlightCurrentDay() {
            const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            const currentDay = days[new Date().getDay()];
            const hoursItems = document.querySelectorAll('.hours-item');

            hoursItems.forEach(item => {
                const dayText = item.querySelector('.hours-day').textContent.toLowerCase();
                if ((currentDay === 'monday' || currentDay === 'tuesday' || currentDay === 'wednesday' || currentDay === 'thursday' || currentDay === 'friday') && dayText.includes('monday')) {
                    item.classList.add('today');
                } else if (currentDay === 'saturday' && dayText.includes('saturday')) {
                    item.classList.add('today');
                } else if (currentDay === 'sunday' && dayText.includes('sunday')) {
                    item.classList.add('today');
                }
            });
        }

        // Update dark mode button icon
        function updateDarkModeIcon() {
            const icon = document.getElementById('darkModeIcon');
            const isDark = window.isDarkMode && window.isDarkMode();

            if (icon) {
                icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // Initialize animations and features when page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateOnScroll();
            highlightCurrentDay();
            updateDarkModeIcon();
        });

        // Listen for theme changes to update icon
        if (window.darkModeManager) {
            window.darkModeManager.onThemeChange(updateDarkModeIcon);
        }

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>