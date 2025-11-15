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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="includes/header-styles.css" rel="stylesheet">
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

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 128, 96, 0.1);
            margin-bottom: 30px;
        }

        .filter-title {
            color: #008060;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .filter-select:focus {
            outline: none;
            border-color: #008060;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
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
    <div class="floating-bubbles"></div>

    <!-- Main Header -->
    <header class="main-header animate__animated animate__fadeInDown">
        <div class="container-fluid" style="padding: 0 120px 0 95px;">
            <div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
                <!-- Logo - Far Left -->
                <a href="index.php" class="logo">
                    Gadget<span class="garage">Garage</span>
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
                                <a href="my_orders.php" class="dropdown-item-custom">
                                    <i class="fas fa-box"></i>
                                    <span>My Orders</span>
                                </a>
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
    <div class="container">
        <h1 class="page-title">Mobile Devices</h1>

        <!-- Results Info -->
        <?php if (!empty($filtered_products)): ?>
            <div class="results-info">
                <strong><?php echo $total_products; ?> Mobile Device<?php echo $total_products != 1 ? 's' : ''; ?> Found</strong>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <h5 class="filter-title">Filter Mobile Devices</h5>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="filter-select" id="categoryFilter" onchange="applyFilters()">
                        <option value="all">All Mobile Devices</option>
                        <?php foreach ($mobile_cats as $cat): ?>
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
                        <?php foreach ($mobile_brands as $brand): ?>
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
                <i class="fas fa-mobile-alt fa-4x mb-3" style="color: #cbd5e0;"></i>
                <h3>No Mobile Devices Found</h3>
                <p>Try adjusting your filters or search terms.</p>
                <a href="mobile_devices.php" class="btn btn-primary mt-3">
                    <i class="fas fa-refresh"></i> View All Mobile Devices
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products_to_display as $product): ?>
                    <div class="product-card" onclick="viewProduct(<?php echo $product['product_id']; ?>)">
                        <img src=""
                             alt="<?php echo htmlspecialchars($product['product_title']); ?>"
                             class="product-image"
                             data-product-id="<?php echo $product['product_id']; ?>"
                             data-product-image="<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>"
                             data-product-title="<?php echo htmlspecialchars($product['product_title']); ?>">
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
                                <i class="fas fa-shopping-cart"></i> Add to Cart
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        // Image Loading System
        function loadProductImages() {
            document.querySelectorAll('.product-image').forEach(img => {
                const productId = img.getAttribute('data-product-id');
                const productTitle = img.getAttribute('data-product-title');

                fetch(`actions/upload_product_image_action.php?action=get_image_url&product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.url) {
                            img.src = data.url;
                        } else {
                            img.src = generatePlaceholderUrl(productTitle);
                        }
                    })
                    .catch(error => {
                        console.log('Image load error for product', productId, '- using placeholder');
                        img.src = generatePlaceholderUrl(productTitle);
                    });
            });
        }

        function generatePlaceholderUrl(text, size = '320x240') {
            const encodedText = encodeURIComponent(text);
            return `https://via.placeholder.com/${size}/008060/ffffff?text=${encodedText}`;
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
            alert('Profile picture modal not implemented yet');
        }

        function changeLanguage(lang) {
            alert('Language change to ' + lang + ' not implemented yet');
        }

        function toggleTheme() {
            alert('Theme toggle not implemented yet');
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
            loadProductImages();
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