<?php
try {
    require_once(__DIR__ . '/settings/core.php');
    require_once(__DIR__ . '/controllers/order_controller.php');

    $is_logged_in = check_login();

    if (!$is_logged_in) {
        header("Location: login/user_login.php");
        exit;
    }

    $customer_id = $_SESSION['user_id'];
    $orders = get_user_orders_ctr($customer_id);

} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My Orders - Gadget Garage</title>
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
			background-color: #ffffff;
			color: #1a1a1a;
			overflow-x: hidden;
		}

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
			padding: 40px 0;
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			margin: 0;
		}

		/* Order Cards */
		.order-card {
			background: white;
			border-radius: 15px;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
			margin-bottom: 2rem;
			overflow: hidden;
			transition: all 0.3s ease;
		}

		.order-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
		}

		.order-header {
			background: #f8f9ff;
			padding: 1.5rem;
			border-bottom: 1px solid #e2e8f0;
		}

		.order-body {
			padding: 1.5rem;
		}

		.status-badge {
			padding: 0.5rem 1rem;
			border-radius: 25px;
			font-weight: 600;
			font-size: 0.9rem;
		}

		.status-pending {
			background: #fef3cd;
			color: #008060;
		}

		.status-processing {
			background: #d4f4ff;
			color: #0369a1;
		}

		.status-completed {
			background: #d1fae5;
			color: #059669;
		}

		.status-cancelled {
			background: #fee2e2;
			color: #dc2626;
		}

		.btn-primary {
			background: linear-gradient(135deg, #008060, #006b4e);
			border: none;
			border-radius: 25px;
			padding: 12px 30px;
			font-weight: 600;
			transition: all 0.3s ease;
		}

		.btn-primary:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(0, 128, 96, 0.3);
		}

		.empty-state {
			text-align: center;
			padding: 4rem 2rem;
		}

		.empty-state-icon {
			font-size: 4rem;
			color: #cbd5e0;
			margin-bottom: 1rem;
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
			background: linear-gradient(135deg, rgba(0, 128, 96, 0.1), rgba(240, 147, 251, 0.05));
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
    </style>
</head>

<body>
	<!-- Floating Bubbles Background -->
	<div class="floating-bubbles" id="floatingBubbles"></div>

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
								<span class="cart-badge" id="cartBadge" style="<?php echo isset($cart_count) && $cart_count > 0 ? '' : 'display: none;'; ?>"><?php echo $cart_count ?? 0; ?></span>
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
								<a href="my_orders.php" class="dropdown-item-custom">
									<i class="fas fa-box"></i>
									<span>My Orders</span>
								</a>
								<div class="dropdown-divider-custom"></div>
								<a href="wishlist.php" class="dropdown-item-custom">
									<i class="fas fa-heart"></i>
									<span>Wishlist</span>
								</a>
								<?php if (isset($is_admin) && $is_admin): ?>
									<div class="dropdown-divider-custom"></div>
									<a href="admin/category.php" class="dropdown-item-custom">
										<i class="fas fa-cog"></i>
										<span>Admin Panel</span>
									</a>
								<?php endif; ?>
								<div class="dropdown-divider-custom"></div>
								<a href="login/logout.php" class="dropdown-item-custom">
									<i class="fas fa-sign-out-alt"></i>
									<span>Logout</span>
								</a>
							</div>
						</div>
					<?php else: ?>
						<!-- Not logged in: Register | Login -->
						<a href="login/register.php" class="login-btn me-2">Register</a>
						<a href="login/login.php" class="login-btn">Login</a>
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
						SHOP BY BRANDS
						<i class="fas fa-chevron-down"></i>
					</button>
					<div class="brands-dropdown" id="shopDropdown">
						<h4>All Brands</h4>
						<ul>
							<?php if (isset($brands) && !empty($brands)): ?>
								<?php foreach ($brands as $brand): ?>
									<li><a href="all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
								<?php endforeach; ?>
							<?php else: ?>
								<li><a href="all_product.php"><i class="fas fa-tag"></i> All Products</a></li>
							<?php endif; ?>
						</ul>
					</div>
				</div>

				<a href="index.php" class="nav-item">HOME</a>

				<!-- Shop Dropdown -->
				<div class="nav-dropdown" onmouseenter="showShopDropdown()" onmouseleave="hideShopDropdown()">
					<a href="#" class="nav-item">
						SHOP
						<i class="fas fa-chevron-down"></i>
					</a>
					<div class="mega-dropdown" id="shopCategoryDropdown">
						<div class="dropdown-content">
							<div class="dropdown-column">
								<h4>
									<a href="mobile_devices.php" style="text-decoration: none; color: inherit;">
										Mobile Devices
									</a>
								</h4>
								<ul>
									<li><a href="all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> Smartphones</a></li>
									<li><a href="all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> iPads</a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="computing.php" style="text-decoration: none; color: inherit;">
										Computing
									</a>
								</h4>
								<ul>
									<li><a href="all_product.php?category=laptops"><i class="fas fa-laptop"></i> Laptops</a></li>
									<li><a href="all_product.php?category=desktops"><i class="fas fa-desktop"></i> Desktops</a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="photography_video.php" style="text-decoration: none; color: inherit;">
										Photography & Video
									</a>
								</h4>
								<ul>
									<li><a href="all_product.php?category=cameras"><i class="fas fa-camera"></i> Cameras</a></li>
									<li><a href="all_product.php?category=video_equipment"><i class="fas fa-video"></i> Video Equipment</a></li>
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

				<a href="repair_services.php" class="nav-item">REPAIR STUDIO</a>
				<a href="device_drop.php" class="nav-item">DEVICE DROP</a>

				<!-- More Dropdown -->
				<div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
					<a href="#" class="nav-item">
						MORE
						<i class="fas fa-chevron-down"></i>
					</a>
					<div class="simple-dropdown" id="moreDropdown">
						<ul>
							<li><a href="#contact"><i class="fas fa-phone"></i> Contact</a></li>
							<li><a href="#blog"><i class="fas fa-blog"></i> Blog</a></li>
						</ul>
					</div>
				</div>

				<!-- Flash Deal positioned at far right -->
				<a href="#" class="nav-item flash-deal">⚡ FLASH DEAL</a>
			</div>
		</div>
	</nav>

	<!-- Page Title -->
	<h1 class="page-title">My Orders</h1>

    <div class="container py-4">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 class="text-muted mb-3">No orders yet</h3>
                <p class="text-muted mb-4">Start shopping to see your orders here.</p>
                <a href="all_product.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h5 class="mb-1">Order #<?php echo $order['order_id']; ?></h5>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Invoice No:</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($order['invoice_no']); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Status:</div>
                                <?php
                                $status_class = '';
                                switch(strtolower($order['order_status'])) {
                                    case 'pending': $status_class = 'status-pending'; break;
                                    case 'processing': $status_class = 'status-processing'; break;
                                    case 'completed': $status_class = 'status-completed'; break;
                                    case 'cancelled': $status_class = 'status-cancelled'; break;
                                    default: $status_class = 'status-pending';
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="text-muted small">Total:</div>
                                <div class="fs-5 fw-bold text-primary">
                                    GH₵ <?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-shopping-bag text-primary fa-lg"></i>
                                    <div>
                                        <div class="fw-bold">
                                            <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
                                        </div>
                                        <div class="text-muted small">
                                            Order placed on <?php echo date('l, F j, Y', strtotime($order['order_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye me-2"></i>
                                    View Details
                                </button>
                                <?php if (strtolower($order['order_status']) === 'completed'): ?>
                                    <button class="btn btn-outline-success btn-sm ms-2">
                                        <i class="fas fa-redo me-2"></i>
                                        Reorder
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-4">
                <a href="all_product.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

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

		// Orders page specific functionality
		function viewOrderDetails(orderId) {
			const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
			const content = document.getElementById('orderDetailsContent');

			content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading order details...</div>';
			modal.show();

			// Fetch order details via AJAX
			fetch(`actions/get_order_details.php?order_id=${orderId}`)
				.then(response => response.text())
				.then(data => {
					content.innerHTML = data;
				})
				.catch(error => {
					content.innerHTML = `
						<div class="alert alert-danger">
							<i class="fas fa-exclamation-triangle"></i>
							Error loading order details. Please try again.
						</div>
					`;
				});
		}
	</script>
</body>
</html>