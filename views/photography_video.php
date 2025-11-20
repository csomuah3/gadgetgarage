<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/product_controller.php');
require_once(__DIR__ . '/../controllers/category_controller.php');
require_once(__DIR__ . '/../controllers/brand_controller.php');
require_once(__DIR__ . '/../helpers/image_helper.php');

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

// Define photography & video categories
$photo_video_categories = ['cameras', 'video_equipment', 'Cameras', 'Video Equipment', 'Camera', 'Video', 'Photography'];

// Filter products for photography & video devices only
$photo_video_products = array_filter($all_products, function($product) use ($photo_video_categories) {
    return in_array($product['cat_name'], $photo_video_categories) ||
           stripos($product['product_title'], 'camera') !== false ||
           stripos($product['product_title'], 'video') !== false ||
           stripos($product['product_title'], 'photography') !== false ||
           stripos($product['product_title'], 'photo') !== false ||
           stripos($product['product_title'], 'lens') !== false ||
           stripos($product['product_title'], 'camcorder') !== false ||
           stripos($product['cat_name'], 'photo') !== false ||
           stripos($product['cat_name'], 'video') !== false;
});

// Apply additional filters based on URL parameters
$category_filter = $_GET['category'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$condition_filter = $_GET['condition'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$filtered_products = $photo_video_products;

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

// Get unique categories and brands from photo/video products
$photo_video_cats = array_unique(array_column($photo_video_products, 'cat_name'));
$photo_video_brand_ids = array_unique(array_column($photo_video_products, 'brand_id'));
$photo_video_brands = array_filter($brands, function($brand) use ($photo_video_brand_ids) {
    return in_array($brand['brand_id'], $photo_video_brand_ids);
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
    <title>Photography & Video - Gadget Garage</title>
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
    <!-- Floating Bubbles Background -->
    <div class="floating-bubbles" id="floatingBubbles"></div>

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
                                <span class="cart-badge" id="cartBadge" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>"><?php echo $cart_count; ?></span>
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
                                    <li><a href="mobile_devices.php?category=smartphones"><i class="fas fa-mobile-alt"></i> Smartphones</a></li>
                                    <li><a href="mobile_devices.php?category=ipads"><i class="fas fa-tablet-alt"></i> iPads</a></li>
                                    <li><a href="mobile_devices.php?category=tablets"><i class="fas fa-tablet-alt"></i> Tablets</a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="computing.php" style="text-decoration: none; color: inherit;">
                                        Computing
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="computing.php?category=laptops"><i class="fas fa-laptop"></i> Laptops</a></li>
                                    <li><a href="computing.php?category=desktops"><i class="fas fa-desktop"></i> Desktops</a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="photography_video.php" style="text-decoration: none; color: inherit;">
                                        Photography & Video
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="photography_video.php?category=cameras"><i class="fas fa-camera"></i> Cameras</a></li>
                                    <li><a href="photography_video.php?category=video_equipment"><i class="fas fa-video"></i> Video Equipment</a></li>
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
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Left Sidebar - Filters -->
            <div class="col-lg-3 col-md-4" id="filterSidebar">
                <div class="filters-sidebar">
                    <div class="filter-header">
                        <h3 class="filter-title">
                            <i class="fas fa-filter"></i>
                            Filter Products
                        </h3>
                        <button class="filter-close d-lg-none" id="closeFilters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="filter-group">
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInput" placeholder="Search products..." autocomplete="off">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Rating</h6>
                        <div class="rating-filters">
                            <div class="rating-option" data-rating="5">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <span>5 Star</span>
                            </div>
                            <div class="rating-option" data-rating="4">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span>4 Star</span>
                            </div>
                            <div class="rating-option" data-rating="3">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span>3 Star</span>
                            </div>
                            <div class="rating-option" data-rating="2">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span>2 Star</span>
                            </div>
                            <div class="rating-option" data-rating="1">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span>1 Star</span>
                            </div>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Price Range</h6>
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
                        <h6 class="filter-subtitle">Filter By Category</h6>
                        <div class="tag-filters" id="categoryTags">
                            <button class="tag-btn active" data-category="">All</button>
                            <button class="tag-btn" data-category="cameras">Cameras and Video Devices</button>
                        </div>
                    </div>

                    <!-- Filter by Brand -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Brand</h6>
                        <div class="tag-filters" id="brandTags">
                            <button class="tag-btn active" data-brand="">All</button>
                            <button class="tag-btn" data-brand="canon_cameras">Canon Cameras</button>
                            <button class="tag-btn" data-brand="canon_video_equipment">Canon Video Equipment</button>
                            <button class="tag-btn" data-brand="dji_cameras">DJI Cameras</button>
                            <button class="tag-btn" data-brand="dji_video_equipment">DJI Video Equipment</button>
                            <button class="tag-btn" data-brand="fujifilm_cameras">FujiFilm Cameras</button>
                            <button class="tag-btn" data-brand="gopro_cameras">GoPro Cameras</button>
                            <button class="tag-btn" data-brand="nikon_cameras">Nikon Cameras</button>
                            <button class="tag-btn" data-brand="nikon_video_equipment">Nikon Video Equipment</button>
                            <button class="tag-btn" data-brand="panasonic_cameras">Panasonic Cameras</button>
                            <button class="tag-btn" data-brand="sony_cameras">Sony Cameras</button>
                            <button class="tag-btn" data-brand="sony_video_equipment">Sony Video Equipment</button>
                        </div>
                    </div>

                    <!-- Filter by Size -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Size</h6>
                        <div class="tag-filters" id="sizeTags">
                            <button class="tag-btn active" data-size="">All</button>
                            <button class="tag-btn" data-size="large">Large</button>
                            <button class="tag-btn" data-size="medium">Medium</button>
                            <button class="tag-btn" data-size="small">Small</button>
                        </div>
                    </div>

                    <!-- Filter by Color -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Color</h6>
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
                            Clear All Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Content - Products -->
            <div class="col-lg-9 col-md-8" id="productContent">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title mb-0">Photography & Video</h1>
                    <button class="btn btn-outline-primary d-md-none" id="mobileFilterToggle">
                        <i class="fas fa-filter me-2"></i>
                        Filters
                    </button>
                </div>

        <!-- Products Grid -->
        <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="filter-select" id="categoryFilter" onchange="applyFilters()">
                        <option value="all">All Photography & Video</option>
                        <?php foreach ($photo_video_cats as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <select class="filter-select" id="brandFilter" onchange="applyFilters()">
                        <option value="all">All Brands</option>
                        <?php foreach ($photo_video_brands as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>" <?php echo $brand_filter == $brand['brand_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="clear-filters-btn w-100" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products_to_display)): ?>
            <div class="no-results">
                <i class="fas fa-camera fa-4x mb-3" style="color: #cbd5e0;"></i>
                <h3>No Photography & Video Products Found</h3>
                <p>Try adjusting your filters or search terms.</p>
                <a href="photography_video.php" class="btn btn-primary mt-3">
                    <i class="fas fa-refresh"></i> View All Photography & Video
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products_to_display as $product):
                    // Calculate random discount percentage (13% shown in your example)
                    $discount_percentage = rand(10, 25);
                    $original_price = $product['product_price'] * (1 + $discount_percentage / 100);
                    $rating = round(rand(40, 50) / 10, 1); // Random rating between 4.0-5.0
                ?>
                    <div class="modern-product-card" style="
                        background: white;
                        border-radius: 16px;
                        border: 1px solid #e5e7eb;
                        overflow: hidden;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        cursor: pointer;
                        position: relative;
                        transform-origin: center;
                    " onmouseover="this.style.transform='rotate(-2deg) scale(1.02)'; this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';"
                       onmouseout="this.style.transform='rotate(0deg) scale(1)'; this.style.boxShadow='0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';">
                        <!-- Discount Badge -->
                        <?php if ($discount_percentage > 0): ?>
                        <div style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; z-index: 10;">
                            -<?php echo $discount_percentage; ?>%
                        </div>
                        <?php endif; ?>
                        <!-- Wishlist Heart -->
                        <div style="position: absolute; top: 12px; right: 12px; z-index: 10;">
                            <button onclick="event.stopPropagation(); toggleWishlist(<?php echo $product['product_id']; ?>)"
                                    style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
                                    onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';">
                                <i class="far fa-heart" style="color: #6b7280; font-size: 16px;"></i>
                            </button>
                        </div>
                        <!-- Product Image -->
                        <div style="padding: 20px; text-align: center; height: 200px; display: flex; align-items: center; justify-content: center; background: #f9fafb;">
                            <?php
                            $image_url = get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? 'Product');
                            $fallback_url = generate_placeholder_url($product['product_title'] ?? 'Product', '400x300');
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>"
                                alt="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>"
                                style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($fallback_url); ?>';">
                        </div>
                        <!-- Product Content -->
                        <div style="padding: 25px;">
                            <!-- Product Title -->
                            <h3 style="color: #1f2937; font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; line-height: 1.4; cursor: pointer;" onclick="viewProductDetails(<?php echo $product['product_id']; ?>)">
                                <?php echo htmlspecialchars($product['product_title']); ?>
                            </h3>
                            <!-- Rating -->
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div style="color: #fbbf24; margin-right: 8px;">
                                    <?php
                                    $full_stars = floor($rating);
                                    $half_star = $rating - $full_stars >= 0.5;
                                    for($i = 0; $i < $full_stars; $i++) {
                                        echo '<i class="fas fa-star"></i>';
                                    }
                                    if($half_star) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                        $full_stars++;
                                    }
                                    for($i = $full_stars; $i < 5; $i++) {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <span style="color: #6b7280; font-size: 0.9rem; font-weight: 600;">(<?php echo $rating; ?>)</span>
                            </div>
                            <!-- Optional Status Text -->
                            <?php if (rand(1, 3) === 1): // Only show for some products ?>
                                <div style="margin-bottom: 12px;">
                                    <span style="background: #16a34a; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">In Stock</span>
                                </div>
                            <?php endif; ?>
                            <!-- Pricing -->
                            <div style="margin-bottom: 25px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span style="color: #4f46e5; font-size: 1.75rem; font-weight: 800;">
                                        GH₵<?php echo number_format($product['product_price'], 0); ?>
                                    </span>
                                    <span style="color: #9ca3af; font-size: 1.2rem; text-decoration: line-through;">
                                        GH₵<?php echo number_format($original_price, 0); ?>
                                    </span>
                                </div>
                                <div style="color: #6b7280; font-size: 0.85rem; margin-top: 4px;">
                                    Limited time offer - While supplies last
                                </div>
                            </div>
                            <!-- View Details Button -->
                            <button onclick="viewProductDetails(<?php echo $product['product_id']; ?>)"
                                    style="width: 100%; background: #4f46e5; color: white; border: none; padding: 15px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fas fa-eye"></i>
                                View Details
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
                            <i class="fas fa-chevron-left"></i> Previous
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
                            Next <i class="fas fa-chevron-right"></i>
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

        function initPhotoFilters() {
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
                if (applyBtn) applyBtn.style.display = 'flex';
            }
        }

        // Clear filters function
        document.getElementById('clearAllFilters')?.addEventListener('click', function() {
            window.location.href = 'photography_video.php';
        });

        // Search input functionality
        document.getElementById('searchInput')?.addEventListener('input', function() {
            showApplyButton();
        });

        // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            initPhotoFilters();
        });
    </script>
    <script>
        function viewProduct(productId) {
            window.location.href = 'single_product.php?id=' + productId;
        }

        function viewProductDetails(productId) {
            window.location.href = 'single_product.php?pid=' + productId;
        }

        function toggleWishlist(productId) {
            // Check if user is logged in
            <?php if (!$is_logged_in): ?>
            window.location.href = 'login/login.php';
            return;
            <?php endif; ?>

            const heartIcon = event.target;
            const isWishlisted = heartIcon.classList.contains('fas');

            // Optimistic UI update
            if (isWishlisted) {
                heartIcon.className = 'far fa-heart';
                heartIcon.style.color = '#6b7280';
            } else {
                heartIcon.className = 'fas fa-heart';
                heartIcon.style.color = '#ef4444';
            }

            // Make API call
            fetch('actions/wishlist_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${isWishlisted ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert the UI change if the API call failed
                    if (isWishlisted) {
                        heartIcon.className = 'fas fa-heart';
                        heartIcon.style.color = '#ef4444';
                    } else {
                        heartIcon.className = 'far fa-heart';
                        heartIcon.style.color = '#6b7280';
                    }
                    console.error('Failed to update wishlist');
                }
            })
            .catch(error => {
                // Revert the UI change if there was an error
                if (isWishlisted) {
                    heartIcon.className = 'fas fa-heart';
                    heartIcon.style.color = '#ef4444';
                } else {
                    heartIcon.className = 'far fa-heart';
                    heartIcon.style.color = '#6b7280';
                }
                console.error('Error:', error);
            });
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

            window.location.href = 'photography_video.php?' + params.toString();
        }

        function clearFilters() {
            window.location.href = 'photography_video.php';
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
                Swal.fire({title: 'Feature Coming Soon', text: 'Profile picture modal not implemented yet', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
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
                Swal.fire({title: 'Feature Coming Soon', text: 'Language change to ' + lang + ' not implemented yet', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
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
                Swal.fire({title: 'Feature Coming Soon', text: 'Theme toggle not implemented yet', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
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
</body>
</html>