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

// Define mobile device categories
$mobile_categories = ['smartphones', 'ipads', 'tablets', 'Smartphones', 'iPads', 'Tablets', 'Phone', 'iPad', 'Tablet'];

// Filter products for mobile devices only
$mobile_products = array_filter($all_products, function($product) use ($mobile_categories) {
    return in_array($product['cat_name'], $mobile_categories) ||
           stripos($product['product_title'], 'phone') !== false ||
           stripos($product['product_title'], 'ipad') !== false ||
           stripos($product['product_title'], 'tablet') !== false ||
           stripos($product['cat_name'], 'mobile') !== false;
});

// Apply additional filters based on URL parameters
$category_filter = $_GET['category'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$condition_filter = $_GET['condition'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$filtered_products = $mobile_products;

if ($category_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function($product) use ($category_filter) {
        return strcasecmp($product['cat_name'], $category_filter) === 0;
    });
}

if ($brand_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function($product) use ($brand_filter) {
        return $product['brand_id'] == $brand_filter;
    });
}

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
        return stripos($product['product_title'], $search_query) !== false ||
               stripos($product['product_desc'], $search_query) !== false;
    });
}

// Get unique categories and brands from mobile products
$mobile_cats = array_unique(array_column($mobile_products, 'cat_name'));
$mobile_brand_ids = array_unique(array_column($mobile_products, 'brand_id'));
$mobile_brands = array_filter($brands, function($brand) use ($mobile_brand_ids) {
    return in_array($brand['brand_id'], $mobile_brand_ids);
});

// Pagination
$products_per_page = 12;
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $products_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;
$products_to_display = array_slice($filtered_products, $offset, $products_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mobile Devices - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="includes/header-styles.css" rel="stylesheet">
    <link href="includes/chatbot-styles.css" rel="stylesheet">
    <style>
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
            bottom: -100px;
            background: linear-gradient(135deg, rgba(0, 128, 96, 0.1), rgba(0, 107, 78, 0.1));
            border-radius: 50%;
            opacity: 0.6;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Page specific styles */
        .page-title {
            text-align: center;
            padding: 40px 0;
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .filters-sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            position: sticky;
            top: 20px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .filter-title {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-close {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .filter-group {
            margin-bottom: 25px;
        }

        .filter-subtitle {
            color: #374151;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #f9fafb;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #059669;
            background: white;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .rating-filters {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rating-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .rating-option:hover {
            background: #f3f4f6;
        }

        .rating-option.active {
            background: #dcfce7;
            color: #059669;
        }

        .stars {
            color: #fbbf24;
            font-size: 0.85rem;
        }

        .stars .far {
            color: #d1d5db;
        }

        .rating-option span {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .price-range-container {
            padding: 10px 0;
        }

        .price-slider {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
            outline: none;
            -webkit-appearance: none;
            margin-bottom: 15px;
        }

        .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #059669;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #059669;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #374151;
            font-weight: 500;
        }

        .tag-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.8rem;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .tag-btn:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .tag-btn.active {
            background: #059669;
            color: white;
            border-color: #059669;
        }

        .color-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .color-option {
            cursor: pointer;
            padding: 3px;
            border-radius: 50%;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .color-option.active {
            border-color: #059669;
            box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2);
        }

        .color-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
        }

        .clear-all-filters-btn {
            width: 100%;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .clear-all-filters-btn:hover {
            background: #dc2626;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 128, 96, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 128, 96, 0.2);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8fafc;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #008060;
            margin-bottom: 10px;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #64748b;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: scale(1.02);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 40px 0;
        }

        .page-btn {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .page-btn:hover, .page-btn.active {
            background: #008060;
            color: white;
            border-color: #008060;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .clear-filters-btn {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-filters-btn:hover {
            background: #cbd5e0;
            color: #2d3748;
        }

        .results-info {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Promotional Banner -->
    <div class="promo-banner">
        <i class="fas fa-shipping-fast"></i>
        <span data-translate="free_next_day_delivery">Free Next Day Delivery on Orders Above GH₵2,000!</span>
    </div>

    <!-- Floating Bubbles Background -->
    <div class="floating-bubbles" id="floatingBubbles"></div>

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
						<input type="text" name="query" class="search-input" data-translate="search_placeholder" placeholder="Search phones, laptops, cameras..." required>
						<button type="submit" class="search-btn">
							<i class="fas fa-search"></i>
						</button>
					</form>

					<!-- Tech Revival Section -->
					<div class="tech-revival-section">
						<i class="fas fa-recycle tech-revival-icon"></i>
						<div>
							<p class="tech-revival-text" data-translate="bring_retired_tech">Bring Retired Tech</p>
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
                        <h4 data-translate="all_brands">All Brands</h4>
                        <ul>
                            <?php if (!empty($brands)): ?>
                                <?php foreach ($brands as $brand): ?>
                                    <li><a href="all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="all_product.php"><i class="fas fa-tag"></i> All Products</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <a href="index.php" class="nav-item" data-translate="home">HOME</a>

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
                                    <li><a href="mobile_devices.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
                                    <li><a href="mobile_devices.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
                                    <li><a href="mobile_devices.php?category=tablets"><i class="fas fa-tablet-alt"></i> <span data-translate="tablets">Tablets</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="computing.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="computing">Computing</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="computing.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
                                    <li><a href="computing.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="photography_video.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="photography_video">Photography & Video</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="photography_video.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
                                    <li><a href="photography_video.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column featured">
                                <h4 data-translate="shop_all">Shop All</h4>
                                <div class="featured-item">
                                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
                                    <div class="featured-text">
                                        <strong data-translate="new_arrivals">New Arrivals</strong>
                                        <p data-translate="latest_tech_gadgets">Latest tech gadgets</p>
                                        <a href="all_product.php" class="shop-now-btn" data-translate="shop_now">Shop Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="repair_services.php" class="nav-item" data-translate="repair_studio">REPAIR STUDIO</a>
                <a href="device_drop.php" class="nav-item" data-translate="device_drop">DEVICE DROP</a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="more">MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="#contact"><i class="fas fa-phone"></i> <span data-translate="contact_us">Contact</span></a></li>
                            <li><a href="#blog"><i class="fas fa-blog"></i> <span data-translate="blog">Blog</span></a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
            </div>
        </div>
    </nav>

    <!-- Page Title -->
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Left Sidebar - Filters -->
            <div class="col-lg-3 col-md-4" id="filterSidebar">
                <div class="filters-sidebar">
                    <div class="filter-header">
                        <h3 class="filter-title">
                            <i class="fas fa-filter"></i>
                            <span data-translate="filter_products">Filter Products</span>
                        </h3>
                        <button class="filter-close d-lg-none" id="closeFilters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="filter-group">
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInput" data-translate="search_products_placeholder" placeholder="Search products..." autocomplete="off">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle" data-translate="rating">Rating</h6>
                        <div class="rating-filters">
                            <div class="rating-option" data-rating="5">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <span data-translate="five_star">5 Star</span>
                            </div>
                            <div class="rating-option" data-rating="4">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span data-translate="four_star">4 Star</span>
                            </div>
                            <div class="rating-option" data-rating="3">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span data-translate="three_star">3 Star</span>
                            </div>
                            <div class="rating-option" data-rating="2">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span data-translate="two_star">2 Star</span>
                            </div>
                            <div class="rating-option" data-rating="1">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span data-translate="one_star">1 Star</span>
                            </div>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle" data-translate="price_range">Price Range</h6>
                        <div class="price-range-container">
                            <input type="range" class="price-slider" id="priceRange" min="0" max="5000" value="2500" step="10">
                            <div class="price-display">
                                <span>GH₵ 0</span>
                                <span>-</span>
                                <span>GH₵ 500</span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter by Category -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle" data-translate="filter_by_category">Filter By Category</h6>
                        <div class="tag-filters" id="categoryTags">
                            <button class="tag-btn active" data-category="" data-translate="all">All</button>
                            <button class="tag-btn" data-category="smartphones" data-translate="smartphones">Smartphones</button>
                            <button class="tag-btn" data-category="ipads" data-translate="ipads_tablets">iPads and Tablets</button>
                        </div>
                    </div>

                    <!-- Filter by Brand -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle" data-translate="filter_by_brand">Filter By Brand</h6>
                        <div class="tag-filters" id="brandTags">
                            <button class="tag-btn active" data-brand="">All</button>
                            <button class="tag-btn" data-brand="apple">Apple</button>
                            <button class="tag-btn" data-brand="samsung">Samsung</button>
                            <button class="tag-btn" data-brand="google">Google</button>
                            <button class="tag-btn" data-brand="oneplus">OnePlus</button>
                            <button class="tag-btn" data-brand="xiaomi">Xiaomi</button>
                            <button class="tag-btn" data-brand="huawei">Huawei</button>
                        </div>
                    </div>

                    <!-- Filter by Size -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle" data-translate="filter_by_size">Filter By Size</h6>
                        <div class="tag-filters" id="sizeTags">
                            <button class="tag-btn active" data-size="">All</button>
                            <button class="tag-btn" data-size="large">Large</button>
                            <button class="tag-btn" data-size="medium">Medium</button>
                            <button class="tag-btn" data-size="small">Small</button>
                        </div>
                    </div>

                    <!-- Filter by Color -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle" data-translate="filter_by_color">Filter By Color</h6>
                        <div class="color-filters" id="colorTags">
                            <div class="color-option active" data-color="">
                                <div class="color-circle" style="background: conic-gradient(red, yellow, lime, cyan, blue, magenta, red);"></div>
                            </div>
                            <div class="color-option" data-color="blue">
                                <div class="color-circle" style="background: #3b82f6;"></div>
                            </div>
                            <div class="color-option" data-color="gray">
                                <div class="color-circle" style="background: #6b7280;"></div>
                            </div>
                            <div class="color-option" data-color="green">
                                <div class="color-circle" style="background: #10b981;"></div>
                            </div>
                            <div class="color-option" data-color="red">
                                <div class="color-circle" style="background: #ef4444;"></div>
                            </div>
                            <div class="color-option" data-color="yellow">
                                <div class="color-circle" style="background: #f59e0b;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Clear All Filters Button -->
                    <div class="filter-actions">
                        <button class="clear-all-filters-btn" id="clearAllFilters">
                            <i class="fas fa-times"></i>
                            <span data-translate="clear_all_filters">Clear All Filters</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Content - Products -->
            <div class="col-lg-9 col-md-8" id="productContent">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title mb-0" data-translate="mobile_devices">Mobile Devices</h1>
                    <button class="btn btn-outline-primary d-md-none" id="mobileFilterToggle">
                        <i class="fas fa-filter me-2"></i>
                        <span data-translate="filters">Filters</span>
                    </button>
                </div>

        <!-- Products Grid -->
        <?php if (empty($products_to_display)): ?>
            <div class="no-results">
                <i class="fas fa-mobile-alt fa-4x mb-3" style="color: #cbd5e0;"></i>
                <h3 data-translate="no_mobile_devices_found">No Mobile Devices Found</h3>
                <p data-translate="try_adjusting_filters">Try adjusting your filters or search terms.</p>
                <a href="mobile_devices.php" class="btn btn-primary mt-3">
                    <i class="fas fa-refresh"></i> <span data-translate="view_all_mobile_devices">View All Mobile Devices</span>
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products_to_display as $product): ?>
                    <div class="product-card" onclick="viewProduct(<?php echo $product['product_id']; ?>)">
                        <?php 
                        $image_url = get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? 'Product');
                        $fallback_url = generate_placeholder_url($product['product_title'] ?? 'Product', '400x300');
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>"
                             alt="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>"
                             class="product-image"
                             data-product-id="<?php echo $product['product_id']; ?>"
                             data-product-image="<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>"
                             data-product-title="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>"
                             onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($fallback_url); ?>';">
                        <div class="product-content">
                            <h5 class="product-title">
                                <?php echo htmlspecialchars($product['product_title']); ?>
                            </h5>
                            <div class="product-price">$<?php echo number_format($product['product_price'], 2); ?></div>
                            <div class="product-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['cat_name'] ?? 'N/A'); ?></span>
                                <span><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></span>
                            </div>
                            <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(<?php echo $product['product_id']; ?>)">
                                <i class="fas fa-shopping-cart"></i> <span data-translate="add_to_cart">Add to Cart</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&brand=<?php echo $brand_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page - 1; ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i> <span data-translate="previous">Previous</span>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&brand=<?php echo $brand_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>"
                           class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&brand=<?php echo $brand_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $current_page + 1; ?>" class="page-btn">
                            <span data-translate="next">Next</span> <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/chatbot.js"></script>
    <script>
        // Filter System JavaScript
        let filtersChanged = false;
        let initialState = { search: '', category: '', brand: '' };

        function initMobileFilters() {
            initCategoryFilter();
            initBrandFilter();
            initMobileFilterToggle();
        }

        function initCategoryFilter() {
            const categoryBtns = document.querySelectorAll('#categoryTags .tag-btn');
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    showApplyButton();
                });
            });
        }

        function initBrandFilter() {
            const brandBtns = document.querySelectorAll('#brandTags .tag-btn');
            brandBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    brandBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    showApplyButton();
                });
            });
        }

        function initMobileFilterToggle() {
            const mobileToggle = document.getElementById('mobileFilterToggle');
            const closeFilters = document.getElementById('closeFilters');
            const filterSidebar = document.getElementById('filterSidebar');

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    filterSidebar.classList.add('show');
                });
            }

            if (closeFilters) {
                closeFilters.addEventListener('click', function() {
                    filterSidebar.classList.remove('show');
                });
            }
        }

        function showApplyButton() {
            if (!filtersChanged) {
                filtersChanged = true;
                const applyBtn = document.getElementById('applyFilters');
                applyBtn.style.display = 'flex';
            }
        }

        function hideApplyButton() {
            filtersChanged = false;
            const applyBtn = document.getElementById('applyFilters');
            applyBtn.style.display = 'none';
        }

        // Apply filters function
        document.getElementById('applyFilters')?.addEventListener('click', function() {
            const searchQuery = document.getElementById('searchInput').value;
            const activeCategory = document.querySelector('#categoryTags .tag-btn.active');
            const activeBrand = document.querySelector('#brandTags .tag-btn.active');

            const category = activeCategory ? activeCategory.getAttribute('data-category') : '';
            const brand = activeBrand ? activeBrand.getAttribute('data-brand') : '';

            const params = new URLSearchParams();
            if (searchQuery) params.append('search', searchQuery);
            if (category) params.append('category', category);
            if (brand) params.append('brand', brand);

            window.location.href = 'mobile_devices.php?' + params.toString();
        });

        // Clear filters function
        document.getElementById('clearFilters')?.addEventListener('click', function() {
            window.location.href = 'mobile_devices.php';
        });

        // Search input functionality
        document.getElementById('searchInput')?.addEventListener('input', function() {
            showApplyButton();
        });

        // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            initMobileFilters();
        });
    </script>
    <script>
        function viewProduct(productId) {
            window.location.href = 'single_product.php?id=' + productId;
        }

        function addToCart(productId) {
            const btn = event.target.closest('.add-to-cart-btn');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('condition', 'excellent');
            formData.append('final_price', 0);

            fetch('actions/add_to_cart_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = 'linear-gradient(135deg, #008060, #006b4e)';
                        btn.disabled = false;
                    }, 1500);

                    // Update cart count
                    const cartBadge = document.getElementById('cartBadge');
                    if (cartBadge && data.cart_count) {
                        cartBadge.textContent = data.cart_count;
                        cartBadge.style.display = 'inline';
                    }
                } else {
                    btn.innerHTML = 'Error!';
                    btn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = 'linear-gradient(135deg, #008060, #006b4e)';
                        btn.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = 'Error!';
                btn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';

                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(135deg, #008060, #006b4e)';
                    btn.disabled = false;
                }, 2000);
            });
        }

        function applyFilters() {
            const category = document.getElementById('categoryFilter').value;
            const brand = document.getElementById('brandFilter').value;
            const search = '<?php echo addslashes($search_query); ?>';

            const params = new URLSearchParams();
            if (category !== 'all') params.append('category', category);
            if (brand !== 'all') params.append('brand', brand);
            if (search) params.append('search', search);

            window.location.href = 'mobile_devices.php?' + params.toString();
        }

        function clearFilters() {
            window.location.href = 'mobile_devices.php';
        }


        function generatePlaceholderUrl(text, size = '320x240') {
            if (typeof generatePlaceholderImage === 'function') {
                return generatePlaceholderImage(text, size);
            }

            const [width, height] = size.split('x').map(Number);
            const safeText = (text || 'Gadget Garage').substring(0, 32).replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
                <rect width="100%" height="100%" fill="#eef2ff"/>
                <rect x="1" y="1" width="${width - 2}" height="${height - 2}" fill="none" stroke="#cbd5f5" stroke-width="2"/>
                <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="${Math.max(Math.floor(height * 0.12), 14)}" fill="#1f2937" text-anchor="middle" dominant-baseline="middle">${safeText}</text>
            </svg>`;
            return `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svg)))}`;
        }

        // Floating Bubbles Animation
        function createFloatingBubbles() {
            const bubblesContainer = document.querySelector('.floating-bubbles');
            const colors = [
                'rgba(0, 128, 96, 0.1)',
                'rgba(0, 107, 78, 0.1)',
                'rgba(0, 150, 112, 0.1)'
            ];

            function createBubble() {
                const bubble = document.createElement('div');
                bubble.className = 'bubble';

                const size = Math.random() * 60 + 20;
                const color = colors[Math.floor(Math.random() * colors.length)];
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 10 + 10;
                const delay = Math.random() * 5;

                bubble.style.width = size + 'px';
                bubble.style.height = size + 'px';
                bubble.style.background = color;
                bubble.style.left = left + '%';
                bubble.style.animationDuration = animationDuration + 's';
                bubble.style.animationDelay = delay + 's';

                bubblesContainer.appendChild(bubble);

                setTimeout(() => {
                    if (bubblesContainer.contains(bubble)) {
                        bubblesContainer.removeChild(bubble);
                    }
                }, (animationDuration + delay) * 1000);
            }

            setInterval(createBubble, 300);
            for (let i = 0; i < 5; i++) {
                setTimeout(createBubble, i * 200);
            }
        }

        // Header dropdown functions
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            dropdown.classList.toggle('show');
        }

        function openProfilePictureModal() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Profile Picture',
                    text: 'Profile picture modal not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Profile picture modal not implemented yet');
            }
        }

        function changeLanguage(lang) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Language Change',
                    text: 'Language change to ' + lang + ' not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Language change to ' + lang + ' not implemented yet');
            }
        }

        function toggleTheme() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Theme Toggle',
                    text: 'Theme toggle not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Theme toggle not implemented yet');
            }
        }

        function showDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) dropdown.classList.add('show');
        }

        function hideDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function showShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) dropdown.classList.add('show');
        }

        function hideShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function showMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) dropdown.classList.add('show');
        }

        function hideMoreDropdown() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createFloatingBubbles();
            // Images now load directly using get_product_image_url() helper function
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const target = event.target;
            const isDropdownButton = target.closest('.categories-button, .nav-item, .user-avatar');
            const isDropdownContent = target.closest('.mega-dropdown, .brands-dropdown, .simple-dropdown, .dropdown-menu-custom');

            if (!isDropdownButton && !isDropdownContent) {
                document.querySelectorAll('.mega-dropdown, .brands-dropdown, .simple-dropdown, .dropdown-menu-custom').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    </script>

    <script>
        // Translation System
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
                "search_placeholder": "Search phones, laptops, cameras...",
                "cart": "Cart",
                "login": "Login",
                "register": "Register",
                "logout": "Logout",
                "profile": "Profile",
                "my_orders": "My Orders",
                "language": "Language",
                "dark_mode": "Dark Mode",
                "bring_retired_tech": "Bring Retired Tech",

                // Page specific content
                "mobile_devices_title": "Mobile Devices - Gadget Garage",
                "free_next_day_delivery": "Free Next Day Delivery on Orders Above GH₵2,000!",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "tablets": "Tablets",
                "laptops": "Laptops",
                "desktops": "Desktops",
                "cameras": "Cameras",
                "video_equipment": "Video Equipment",
                "shop_all": "Shop All",
                "new_arrivals": "New Arrivals",
                "latest_tech_gadgets": "Latest tech gadgets",
                "shop_now": "Shop Now",
                "contact_us": "Contact",
                "blog": "Blog",

                // Filter section
                "filter_products": "Filter Products",
                "search_products_placeholder": "Search products...",
                "rating": "Rating",
                "five_star": "5 Star",
                "four_star": "4 Star",
                "three_star": "3 Star",
                "two_star": "2 Star",
                "one_star": "1 Star",
                "price_range": "Price Range",
                "filter_by_category": "Filter By Category",
                "filter_by_brand": "Filter By Brand",
                "filter_by_size": "Filter By Size",
                "filter_by_color": "Filter By Color",
                "all": "All",
                "ipads_tablets": "iPads and Tablets",
                "clear_all_filters": "Clear All Filters",
                "filters": "Filters",

                // Product listing
                "no_mobile_devices_found": "No Mobile Devices Found",
                "try_adjusting_filters": "Try adjusting your filters or search terms.",
                "view_all_mobile_devices": "View All Mobile Devices",
                "add_to_cart": "Add to Cart",
                "previous": "Previous",
                "next": "Next",

                // Common buttons
                "view_all": "View All",
                "see_more": "See More",
                "back": "Back",
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
                "continue_shopping": "Continue Shopping"
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
                "search_placeholder": "Buscar teléfonos, laptops, cámaras...",
                "cart": "Carrito",
                "login": "Iniciar Sesión",
                "register": "Registrarse",
                "logout": "Cerrar Sesión",
                "profile": "Perfil",
                "my_orders": "Mis Pedidos",
                "language": "Idioma",
                "dark_mode": "Modo Oscuro",
                "bring_retired_tech": "Traer Tecnología Retirada",

                // Page specific content
                "mobile_devices_title": "Dispositivos Móviles - Gadget Garage",
                "free_next_day_delivery": "¡Entrega Gratis al Día Siguiente en Pedidos Superiores a GH₵2,000!",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "tablets": "Tabletas",
                "laptops": "Laptops",
                "desktops": "Escritorios",
                "cameras": "Cámaras",
                "video_equipment": "Equipo de Video",
                "shop_all": "Comprar Todo",
                "new_arrivals": "Nuevas Llegadas",
                "latest_tech_gadgets": "Los últimos gadgets tecnológicos",
                "shop_now": "Comprar Ahora",
                "contact_us": "Contáctanos",
                "blog": "Blog",

                // Filter section
                "filter_products": "Filtrar Productos",
                "search_products_placeholder": "Buscar productos...",
                "rating": "Calificación",
                "five_star": "5 Estrellas",
                "four_star": "4 Estrellas",
                "three_star": "3 Estrellas",
                "two_star": "2 Estrellas",
                "one_star": "1 Estrella",
                "price_range": "Rango de Precios",
                "filter_by_category": "Filtrar por Categoría",
                "filter_by_brand": "Filtrar por Marca",
                "filter_by_size": "Filtrar por Tamaño",
                "filter_by_color": "Filtrar por Color",
                "all": "Todos",
                "ipads_tablets": "iPads y Tabletas",
                "clear_all_filters": "Limpiar Todos los Filtros",
                "filters": "Filtros",

                // Product listing
                "no_mobile_devices_found": "No se Encontraron Dispositivos Móviles",
                "try_adjusting_filters": "Intenta ajustar tus filtros o términos de búsqueda.",
                "view_all_mobile_devices": "Ver Todos los Dispositivos Móviles",
                "add_to_cart": "Añadir al Carrito",
                "previous": "Anterior",
                "next": "Siguiente",

                // Common buttons
                "view_all": "Ver Todo",
                "see_more": "Ver Más",
                "back": "Atrás",
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
                "continue_shopping": "Continuar Comprando"
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
                "search_placeholder": "Rechercher téléphones, ordinateurs, appareils photo...",
                "cart": "Panier",
                "login": "Connexion",
                "register": "S'inscrire",
                "logout": "Déconnexion",
                "profile": "Profil",
                "my_orders": "Mes Commandes",
                "language": "Langue",
                "dark_mode": "Mode Sombre",
                "bring_retired_tech": "Apporter de la Technologie Retraite",

                // Page specific content
                "mobile_devices_title": "Appareils Mobiles - Gadget Garage",
                "free_next_day_delivery": "Livraison Gratuite le Lendemain sur Commandes Supérieures à GH₵2,000!",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "tablets": "Tablettes",
                "laptops": "Ordinateurs Portables",
                "desktops": "Ordinateurs de Bureau",
                "cameras": "Appareils Photo",
                "video_equipment": "Équipement Vidéo",
                "shop_all": "Tout Acheter",
                "new_arrivals": "Nouvelles Arrivées",
                "latest_tech_gadgets": "Les derniers gadgets technologiques",
                "shop_now": "Acheter Maintenant",
                "contact_us": "Nous Contacter",
                "blog": "Blog",

                // Filter section
                "filter_products": "Filtrer Produits",
                "search_products_placeholder": "Rechercher des produits...",
                "rating": "Évaluation",
                "five_star": "5 Étoiles",
                "four_star": "4 Étoiles",
                "three_star": "3 Étoiles",
                "two_star": "2 Étoiles",
                "one_star": "1 Étoile",
                "price_range": "Fourchette de Prix",
                "filter_by_category": "Filtrer par Catégorie",
                "filter_by_brand": "Filtrer par Marque",
                "filter_by_size": "Filtrer par Taille",
                "filter_by_color": "Filtrer par Couleur",
                "all": "Tout",
                "ipads_tablets": "iPads et Tablettes",
                "clear_all_filters": "Effacer Tous les Filtres",
                "filters": "Filtres",

                // Product listing
                "no_mobile_devices_found": "Aucun Appareil Mobile Trouvé",
                "try_adjusting_filters": "Essayez d'ajuster vos filtres ou termes de recherche.",
                "view_all_mobile_devices": "Voir Tous les Appareils Mobiles",
                "add_to_cart": "Ajouter au Panier",
                "previous": "Précédent",
                "next": "Suivant",

                // Common buttons
                "view_all": "Voir Tout",
                "see_more": "Voir Plus",
                "back": "Retour",
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
                "continue_shopping": "Continuer les Achats"
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
                "search_placeholder": "Telefone, Laptops, Kameras suchen...",
                "cart": "Warenkorb",
                "login": "Anmelden",
                "register": "Registrieren",
                "logout": "Abmelden",
                "profile": "Profil",
                "my_orders": "Meine Bestellungen",
                "language": "Sprache",
                "dark_mode": "Dunkler Modus",
                "bring_retired_tech": "Alte Technologie Bringen",

                // Page specific content
                "mobile_devices_title": "Mobile Geräte - Gadget Garage",
                "free_next_day_delivery": "Kostenlose Lieferung am nächsten Tag bei Bestellungen über GH₵2,000!",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "tablets": "Tablets",
                "laptops": "Laptops",
                "desktops": "Desktop-Computer",
                "cameras": "Kameras",
                "video_equipment": "Video-Ausrüstung",
                "shop_all": "Alles Kaufen",
                "new_arrivals": "Neue Ankömmlinge",
                "latest_tech_gadgets": "Die neuesten Tech-Gadgets",
                "shop_now": "Jetzt Einkaufen",
                "contact_us": "Kontakt",
                "blog": "Blog",

                // Filter section
                "filter_products": "Produkte Filtern",
                "search_products_placeholder": "Produkte suchen...",
                "rating": "Bewertung",
                "five_star": "5 Sterne",
                "four_star": "4 Sterne",
                "three_star": "3 Sterne",
                "two_star": "2 Sterne",
                "one_star": "1 Stern",
                "price_range": "Preisbereich",
                "filter_by_category": "Nach Kategorie Filtern",
                "filter_by_brand": "Nach Marke Filtern",
                "filter_by_size": "Nach Größe Filtern",
                "filter_by_color": "Nach Farbe Filtern",
                "all": "Alle",
                "ipads_tablets": "iPads und Tablets",
                "clear_all_filters": "Alle Filter Löschen",
                "filters": "Filter",

                // Product listing
                "no_mobile_devices_found": "Keine Mobile Geräte Gefunden",
                "try_adjusting_filters": "Versuchen Sie, Ihre Filter oder Suchbegriffe anzupassen.",
                "view_all_mobile_devices": "Alle Mobile Geräte Anzeigen",
                "add_to_cart": "In den Warenkorb",
                "previous": "Zurück",
                "next": "Weiter",

                // Common buttons
                "view_all": "Alle Anzeigen",
                "see_more": "Mehr Sehen",
                "back": "Zurück",
                "close": "Schließen",
                "save": "Speichern",
                "edit": "Bearbeiten",
                "delete": "Löschen",
                "confirm": "Bestätigen",
                "submit": "Absenden",

                // Product related
                "price": "Preis",
                "description": "Beschreibung",
                "specifications": "Spezifikationen",
                "reviews": "Bewertungen",
                "in_stock": "Auf Lager",
                "out_of_stock": "Ausverkauft",
                "quantity": "Menge",
                "total": "Gesamt",
                "subtotal": "Zwischensumme",
                "checkout": "Zur Kasse",
                "continue_shopping": "Weiter Einkaufen"
            }
        };

        function translate(key, language = null) {
            const lang = language || localStorage.getItem('selectedLanguage') || 'en';
            return translations[lang] && translations[lang][key] ? translations[lang][key] : translations.en[key] || key;
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
                } else if (element.tagName === 'INPUT' && (element.type === 'button' || element.type === 'submit')) {
                    element.value = translation;
                } else if (element.tagName === 'TITLE') {
                    element.textContent = translation;
                } else {
                    // For other elements, replace text content
                    element.textContent = translation;
                }
            });
        }

        // Apply translations to all elements on page load
        function applyTranslations() {
            applyTranslationsEnhanced();
        }

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

        // Initialize translations on page load
        document.addEventListener('DOMContentLoaded', function() {
            applyTranslations();
        });
    </script>
</body>
</html>