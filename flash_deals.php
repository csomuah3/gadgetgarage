<?php
require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/cart_controller.php');
require_once(__DIR__ . '/controllers/product_controller.php');
require_once(__DIR__ . '/controllers/category_controller.php');
require_once(__DIR__ . '/controllers/brand_controller.php');
require_once(__DIR__ . '/helpers/image_helper.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Get all products from database
$all_products = get_all_products_ctr();

// Get all categories and brands from database
try {
    $categories = get_all_categories_ctr();
} catch (Exception $e) {
    $categories = [];
}

try {
    $brands = get_all_brands_ctr();
} catch (Exception $e) {
    $brands = [];
}

// Filter products for Flash Deals category only
$flash_deal_categories = ['Flash Deals', 'flash deals', 'flash_deals', 'Flash Deal', 'flash deal'];

$flash_deal_products = array_filter($all_products, function($product) use ($flash_deal_categories) {
    return in_array($product['cat_name'], $flash_deal_categories) ||
           stripos($product['product_title'], 'flash') !== false ||
           stripos($product['cat_name'], 'flash') !== false;
});

// Sort by newest first (assuming you have a date field, otherwise by product_id)
usort($flash_deal_products, function($a, $b) {
    return $b['product_id'] <=> $a['product_id'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>⚡ Flash Deals - Gadget Garage</title>
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
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: #1a1a1a;
			overflow-x: hidden;
			min-height: 100vh;
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

		/* Flash Deal Page Styles */
		.flash-deal-hero {
			background: linear-gradient(135deg, #ff6b6b, #ffa500);
			padding: 80px 0;
			text-align: center;
			color: white;
			position: relative;
			overflow: hidden;
		}

		.flash-deal-hero::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='m36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
		}

		.flash-deal-title {
			font-size: 4rem;
			font-weight: 800;
			margin-bottom: 20px;
			text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
			position: relative;
		}

		.flash-deal-subtitle {
			font-size: 1.5rem;
			margin-bottom: 30px;
			opacity: 0.9;
			position: relative;
		}

		.deal-timer {
			background: rgba(255,255,255,0.2);
			backdrop-filter: blur(10px);
			border-radius: 20px;
			padding: 20px 40px;
			display: inline-block;
			margin-top: 20px;
			position: relative;
		}

		.timer-section {
			display: flex;
			gap: 20px;
			align-items: center;
		}

		.timer-unit {
			text-align: center;
		}

		.timer-number {
			font-size: 2.5rem;
			font-weight: bold;
			display: block;
		}

		.timer-label {
			font-size: 0.9rem;
			opacity: 0.8;
		}

		/* Product Grid */
		.flash-products-container {
			background: rgba(255,255,255,0.95);
			backdrop-filter: blur(20px);
			border-radius: 30px;
			margin: -50px auto 50px;
			max-width: 1400px;
			padding: 50px 30px;
			box-shadow: 0 20px 60px rgba(0,0,0,0.1);
		}

		.products-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
			gap: 30px;
			margin-top: 30px;
		}

		.flash-product-card {
			background: white;
			border-radius: 20px;
			padding: 25px;
			box-shadow: 0 10px 30px rgba(0,0,0,0.1);
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.flash-product-card:hover {
			transform: translateY(-10px);
			box-shadow: 0 20px 50px rgba(0,0,0,0.15);
		}

		.flash-badge {
			position: absolute;
			top: 15px;
			right: 15px;
			background: linear-gradient(135deg, #ff6b6b, #ffa500);
			color: white;
			padding: 8px 15px;
			border-radius: 25px;
			font-size: 0.8rem;
			font-weight: bold;
			animation: flash 2s infinite;
		}

		@keyframes flash {
			0%, 100% { opacity: 1; }
			50% { opacity: 0.7; }
		}

		.product-image {
			width: 100%;
			height: 200px;
			object-fit: cover;
			border-radius: 15px;
			margin-bottom: 20px;
		}

		.product-title {
			font-size: 1.1rem;
			font-weight: 600;
			margin-bottom: 10px;
			color: #2d3748;
		}

		.price-section {
			display: flex;
			align-items: center;
			gap: 15px;
			margin-bottom: 20px;
		}

		.flash-price {
			font-size: 1.5rem;
			font-weight: bold;
			color: #ff6b6b;
		}

		.original-price {
			font-size: 1.1rem;
			color: #999;
			text-decoration: line-through;
		}

		.discount-percent {
			background: #4caf50;
			color: white;
			padding: 4px 8px;
			border-radius: 10px;
			font-size: 0.8rem;
			font-weight: bold;
		}

		.add-to-cart-btn {
			width: 100%;
			background: linear-gradient(135deg, #ff6b6b, #ffa500);
			color: white;
			border: none;
			padding: 12px 20px;
			border-radius: 15px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.add-to-cart-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 5px 15px rgba(255,107,107,0.3);
		}

		/* Lightning animations */
		.lightning {
			position: absolute;
			color: #ffd700;
			font-size: 2rem;
			animation: lightning 3s infinite;
		}

		@keyframes lightning {
			0%, 100% { opacity: 0; transform: scale(0.5) rotate(0deg); }
			50% { opacity: 1; transform: scale(1) rotate(180deg); }
		}

		/* No products message */
		.no-products {
			text-align: center;
			padding: 60px 20px;
			color: #666;
		}

		.no-products i {
			font-size: 4rem;
			margin-bottom: 20px;
			opacity: 0.5;
		}

		/* Responsive Design */
		@media (max-width: 768px) {
			.flash-deal-title {
				font-size: 2.5rem;
			}

			.flash-deal-subtitle {
				font-size: 1.2rem;
			}

			.timer-section {
				gap: 10px;
			}

			.timer-number {
				font-size: 1.8rem;
			}

			.products-grid {
				grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
				gap: 20px;
			}

			.flash-products-container {
				margin: -30px 15px 30px;
				padding: 30px 15px;
			}
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
								<a href="wishlist.php" class="dropdown-item-custom">
									<i class="fas fa-heart"></i>
									<span>Wishlist</span>
								</a>
								<?php if ($is_admin): ?>
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

	<!-- Flash Deal Hero Section -->
	<div class="flash-deal-hero">
		<div class="lightning" style="top: 10%; left: 15%; animation-delay: 0s;">⚡</div>
		<div class="lightning" style="top: 20%; right: 20%; animation-delay: 1s;">⚡</div>
		<div class="lightning" style="bottom: 30%; left: 25%; animation-delay: 2s;">⚡</div>
		<div class="lightning" style="bottom: 15%; right: 15%; animation-delay: 0.5s;">⚡</div>

		<div class="container">
			<h1 class="flash-deal-title animate__animated animate__bounceInDown">
				⚡ FLASH DEALS ⚡
			</h1>
			<p class="flash-deal-subtitle animate__animated animate__fadeInUp animate__delay-1s">
				Lightning Fast Savings - Limited Time Only!
			</p>

			<div class="deal-timer animate__animated animate__fadeInUp animate__delay-2s">
				<div class="timer-section" id="flashTimer">
					<div class="timer-unit">
						<span class="timer-number" id="hours">24</span>
						<span class="timer-label">HOURS</span>
					</div>
					<div style="font-size: 2rem; font-weight: bold;">:</div>
					<div class="timer-unit">
						<span class="timer-number" id="minutes">59</span>
						<span class="timer-label">MINUTES</span>
					</div>
					<div style="font-size: 2rem; font-weight: bold;">:</div>
					<div class="timer-unit">
						<span class="timer-number" id="seconds">59</span>
						<span class="timer-label">SECONDS</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Flash Deal Products -->
	<div class="container-fluid px-0">
		<div class="flash-products-container">
			<div class="text-center mb-4">
				<h2 style="color: #2d3748; font-weight: bold;">🔥 Hot Flash Deals</h2>
				<p style="color: #666;">Grab these amazing deals before they're gone!</p>
			</div>

			<?php if (!empty($flash_deal_products)): ?>
				<div class="products-grid">
					<?php foreach ($flash_deal_products as $product): ?>
						<div class="flash-product-card animate__animated animate__fadeInUp">
							<div class="flash-badge">⚡ FLASH DEAL</div>

							<?php
							$image_path = get_product_image_path($product['product_id']);
							if (!$image_path) {
								$image_path = 'https://via.placeholder.com/300x200/f8f9fa/6c757d?text=No+Image';
							}
							?>
							<img src="<?php echo htmlspecialchars($image_path); ?>"
								 alt="<?php echo htmlspecialchars($product['product_title']); ?>"
								 class="product-image">

							<h3 class="product-title"><?php echo htmlspecialchars($product['product_title']); ?></h3>

							<div class="price-section">
								<?php
								$original_price = floatval($product['product_price']);
								$flash_price = $original_price * 0.7; // 30% discount for flash deals
								$discount_percent = 30;
								?>
								<span class="flash-price">$<?php echo number_format($flash_price, 2); ?></span>
								<span class="original-price">$<?php echo number_format($original_price, 2); ?></span>
								<span class="discount-percent"><?php echo $discount_percent; ?>% OFF</span>
							</div>

							<button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['product_id']; ?>)">
								<i class="fas fa-bolt"></i> Add to Cart - Flash Deal!
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="no-products">
					<i class="fas fa-bolt"></i>
					<h3>No Flash Deals Available</h3>
					<p>Check back soon for amazing flash deals!</p>
					<a href="all_product.php" class="btn btn-primary mt-3">Browse All Products</a>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Scripts -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		// Flash Deal Timer
		function updateTimer() {
			const now = new Date().getTime();
			const endTime = now + (24 * 60 * 60 * 1000); // 24 hours from now

			const distance = endTime - now;

			const hours = Math.floor(distance / (1000 * 60 * 60));
			const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
			const seconds = Math.floor((distance % (1000 * 60)) / 1000);

			document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
			document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
			document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
		}

		// Update timer every second
		setInterval(updateTimer, 1000);
		updateTimer();

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
			alert('Profile picture functionality will be implemented');
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

		// Add to cart functionality
		function addToCart(productId) {
			// This would integrate with your existing cart system
			fetch('actions/add_to_cart.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					product_id: productId,
					qty: 1
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Item added to cart!');
					// Update cart badge if exists
					updateCartBadge();
				} else {
					alert('Error adding item to cart: ' + data.message);
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Error adding item to cart');
			});
		}

		function updateCartBadge() {
			// Update cart count in header
			fetch('actions/get_cart_count.php')
			.then(response => response.json())
			.then(data => {
				const badge = document.getElementById('cartBadge');
				if (badge && data.count > 0) {
					badge.textContent = data.count;
					badge.style.display = 'block';
				}
			});
		}
	</script>
</body>
</html>