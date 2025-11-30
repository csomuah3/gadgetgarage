<?php
// Header PHP - Reusable header component
// This file contains the promo banner, main header, and navigation bar

// Determine base path for links (works from both root and views directories)
// Check the current script path to determine if we're in views/ directory
$base_path = '';
$current_script = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($current_script, '/views/') !== false || strpos($current_script, 'views/') !== false) {
    // If we're in a views page, go up one level
    $base_path = '../';
}

// Get cart count and wishlist count if user is logged in
if (!isset($cart_count)) {
    $cart_count = 0;
    if (isset($_SESSION['user_id'])) {
        try {
            require_once(__DIR__ . '/../controllers/cart_controller.php');
            $customer_id = $_SESSION['user_id'];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;
        } catch (Exception $e) {
            error_log("Failed to load cart count: " . $e->getMessage());
        }
    }
}

// Get wishlist count if not already set
if (!isset($wishlist_count)) {
    $wishlist_count = 0;
    if (isset($_SESSION['user_id'])) {
    try {
            require_once(__DIR__ . '/../controllers/wishlist_controller.php');
            $customer_id = $_SESSION['user_id'];
            $wishlist_count = get_wishlist_count_ctr($customer_id) ?: 0;
            error_log("Wishlist count for customer $customer_id: $wishlist_count");
    } catch (Exception $e) {
            error_log("Failed to load wishlist count: " . $e->getMessage());
        }
    }
}

// Get compare count if not already set
if (!isset($compare_count)) {
    $compare_count = 0;
    if (isset($_SESSION['user_id'])) {
        try {
            require_once(__DIR__ . '/../controllers/compare_controller.php');
            $customer_id = $_SESSION['user_id'];
            $compare_count = get_compare_count_ctr($customer_id) ?: 0;
        } catch (Exception $e) {
            error_log("Failed to load compare count: " . $e->getMessage());
        }
    }
}

// Get categories for navigation
if (!isset($categories)) {
    $categories = [];
    try {
        require_once(__DIR__ . '/../controllers/category_controller.php');
        $categories = get_all_categories_ctr();
    } catch (Exception $e) {
        error_log("Failed to load categories: " . $e->getMessage());
    }
}

// Get brands for navigation
if (!isset($brands)) {
    $brands = [];
    try {
        require_once(__DIR__ . '/../controllers/brand_controller.php');
        $brands = get_all_brands_ctr();
    } catch (Exception $e) {
        error_log("Failed to load brands: " . $e->getMessage());
    }
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!-- Promotional Banner -->
<div class="promo-banner2">
    <div class="promo-banner-left">
        <i class="fas fa-bolt"></i>
    </div>
    <div class="promo-banner-center">
        <span class="promo-text" data-translate="black_friday_deals">BLACK FRIDAY DEALS STOREWIDE! SHOP AMAZING DISCOUNTS! </span>
        <span class="promo-timer" id="promoTimer">12d:00h:00m:00s</span>
    </div>
    <a href="<?php echo $base_path; ?>index.php#flash-deals" class="promo-shop-link" data-translate="shop_now">Shop Now</a>
</div>

<!-- Main Header -->
<header class="main-header animate__animated animate__fadeInDown">
    <div class="container-fluid" style="padding: 0 120px 0 95px;">
        <div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
            <!-- Logo - Far Left -->
            <a href="<?php echo $base_path; ?>index.php" class="logo">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                    alt="Gadget Garage">
            </a>

            <!-- Center Content -->
            <div class="d-flex align-items-center" style="flex: 1; justify-content: center; gap: 60px;">
                <!-- Search Bar -->
                <form class="search-container" method="GET" action="<?php echo $base_path; ?>product_search_result.php">
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
                <?php if ($is_logged_in): ?>
                    <!-- Wishlist Icon -->
                    <div class="header-icon">
                        <a href="<?php echo $base_path; ?>views/wishlist.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-heart"></i>
                            <span class="wishlist-badge" id="wishlistBadge" style="display: <?php echo ($wishlist_count > 0) ? 'flex' : 'none'; ?>;">
                                <?php echo $wishlist_count; ?>
                            </span>
                        </a>
                    </div>

                    <!-- Compare Icon -->
                    <div class="header-icon">
                        <a href="<?php echo $base_path; ?>views/compare.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;" title="Compare Products">
                            <i class="fas fa-balance-scale"></i>
                            <?php if ($compare_count > 0): ?>
                                <span class="compare-badge" id="compareBadge"><?php echo $compare_count; ?></span>
                            <?php else: ?>
                                <span class="compare-badge" id="compareBadge" style="display: none;">0</span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Cart Icon -->
                    <div class="header-icon">
                        <a href="<?php echo $base_path; ?>views/cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
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
                            <a href="<?php echo $base_path; ?>views/account.php" class="dropdown-item-custom">
                                <i class="fas fa-user"></i>
                                <span data-translate="account">Account</span>
                            </a>
                            <a href="<?php echo $base_path; ?>views/my_orders.php" class="dropdown-item-custom">
                                <i class="fas fa-shopping-bag"></i>
                                <span data-translate="my_orders">My Orders</span>
                            </a>
                            <a href="<?php echo $base_path; ?>track_order.php" class="dropdown-item-custom">
                                <i class="fas fa-truck"></i>
                                <span data-translate="track_orders">Track Orders</span>
                            </a>
                            <a href="<?php echo $base_path; ?>views/notifications.php" class="dropdown-item-custom">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
                            </a>
                            <button class="dropdown-item-custom" onclick="openProfilePictureModal()">
                                <i class="fas fa-camera"></i>
                                <span>Profile Picture</span>
                            </button>
                            <div class="dropdown-divider-custom"></div>
                            <div class="dropdown-item-custom">
                                <i class="fas fa-globe"></i>
                                <div class="language-selector">
                                    <span>Language</span>
                                    <?php
                                    // Get current language from session
                                    require_once(__DIR__ . '/language_config.php');
                                    $current_lang = $_SESSION['language'] ?? 'en';
                                    $current_lang_data = $available_languages[$current_lang] ?? $available_languages['en'];
                                    ?>
                                    <div class="custom-language-dropdown" id="languageDropdown">
                                        <div class="language-dropdown-selected" id="language-selected">
                                            <span class="language-flag"><?php echo $current_lang_data['flag']; ?></span>
                                            <span class="language-code"><?php echo $current_lang_data['code']; ?></span>
                                            <i class="fas fa-chevron-down language-dropdown-arrow"></i>
                                        </div>
                                        <div class="language-dropdown-options" id="language-options">
                                            <?php foreach ($available_languages as $lang_code => $lang_data): ?>
                                                <div class="language-dropdown-option <?php echo $lang_code === $current_lang ? 'selected' : ''; ?>" 
                                                     data-value="<?php echo $lang_code; ?>">
                                                    <span class="language-flag"><?php echo $lang_data['flag']; ?></span>
                                                    <span class="language-name"><?php echo $lang_data['name']; ?> (<?php echo $lang_data['code']; ?>)</span>
                                                    <?php if ($lang_code === $current_lang): ?>
                                                        <i class="fas fa-check language-check"></i>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
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
                            <a href="<?php echo $base_path; ?>login/logout.php" class="dropdown-item-custom">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login Button -->
                    <a href="<?php echo $base_path; ?>login/login.php" class="login-btn">
                        <i class="fas fa-user"></i>
                        Login
                    </a>
                    <!-- Register Button -->
                    <a href="<?php echo $base_path; ?>login/register.php" class="login-btn" style="margin-left: 10px;">
                        <i class="fas fa-user-plus"></i>
                        Register
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
                                <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=Apple"><i class="fas fa-tag"></i> Apple</a></li>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=Samsung"><i class="fas fa-tag"></i> Samsung</a></li>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=HP"><i class="fas fa-tag"></i> HP</a></li>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=Dell"><i class="fas fa-tag"></i> Dell</a></li>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=Sony"><i class="fas fa-tag"></i> Sony</a></li>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=Canon"><i class="fas fa-tag"></i> Canon</a></li>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=Nikon"><i class="fas fa-tag"></i> Nikon</a></li>
                            <li><a href="<?php echo $base_path; ?>views/all_product.php?brand=Microsoft"><i class="fas fa-tag"></i> Microsoft</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <a href="<?php echo $base_path; ?>index.php" class="nav-item"><span data-translate="home">HOME</span></a>

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
                                <a href="<?php echo $base_path; ?>views/mobile_devices.php" style="text-decoration: none; color: inherit;">
                                    <span data-translate="mobile_devices">Mobile Devices</span>
                                </a>
                            </h4>
                            <ul>
                                <li><a href="<?php echo $base_path; ?>views/all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
                                <li><a href="<?php echo $base_path; ?>views/all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
                            </ul>
                        </div>
                        <div class="dropdown-column">
                            <h4>
                                <a href="<?php echo $base_path; ?>views/computing.php" style="text-decoration: none; color: inherit;">
                                    <span data-translate="computing">Computing</span>
                                </a>
                            </h4>
                            <ul>
                                <li><a href="<?php echo $base_path; ?>views/all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
                                <li><a href="<?php echo $base_path; ?>views/all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
                            </ul>
                        </div>
                        <div class="dropdown-column">
                            <h4>
                                <a href="<?php echo $base_path; ?>views/photography_video.php" style="text-decoration: none; color: inherit;">
                                    <span data-translate="photography_video">Photography & Video</span>
                                </a>
                            </h4>
                            <ul>
                                <li><a href="<?php echo $base_path; ?>views/all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
                                <li><a href="<?php echo $base_path; ?>views/all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
                            </ul>
                        </div>
                        <div class="dropdown-column featured">
                            <h4>Shop All</h4>
                            <div class="featured-item">
                                <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
                                <div class="featured-text">
                                    <strong>New Arrivals</strong>
                                    <p>Latest tech gadgets</p>
                                    <a href="<?php echo $base_path; ?>views/all_product.php" class="shop-now-btn">Shop </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <a href="<?php echo $base_path; ?>views/repair_services.php" class="nav-item"><span data-translate="repair_studio">REPAIR STUDIO</span></a>
            <a href="<?php echo $base_path; ?>views/device_drop.php" class="nav-item"><span data-translate="device_drop">DEVICE DROP</span></a>

            <!-- More Dropdown -->
            <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                <a href="#" class="nav-item">
                    <span data-translate="more">MORE</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="simple-dropdown" id="moreDropdown">
                    <ul>
                        <li><a href="<?php echo $base_path; ?>views/contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                        <li><a href="<?php echo $base_path; ?>views/terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                    </ul>
                </div>
            </div>

            <!-- Flash Deal positioned at far right -->
            <a href="<?php echo $base_path; ?>views/flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
        </div>
    </div>
</nav>

<script>
// Header JavaScript functionality - DROPDOWN FUNCTIONS MUST BE GLOBAL
// Dropdown navigation functions with timeout delays
let dropdownTimeout;
let shopDropdownTimeout;
let moreDropdownTimeout;
let userDropdownTimeout;

// Initialize dropdown immediately if DOM is already loaded
(function() {
    if (document.readyState === 'loading') {
        // DOM is still loading, will initialize in DOMContentLoaded
        return;
    }
    // DOM is already loaded, initialize now
    const shopCategoryDropdown = document.getElementById('shopCategoryDropdown');
    if (shopCategoryDropdown) {
        // Ensure dropdown is properly styled
        shopCategoryDropdown.style.display = 'block';
    }
})();

window.toggleUserDropdown = function() {
    const dropdown = document.getElementById('userDropdownMenu');
    if (dropdown) dropdown.classList.toggle('show');
};

window.showDropdown = function() {
    console.log('showDropdown called');
    const dropdown = document.getElementById('shopDropdown');
    console.log('shopDropdown element:', dropdown);
    if (dropdown) {
        clearTimeout(dropdownTimeout);
        dropdown.classList.add('show');
        console.log('Added show class, classes:', dropdown.className);
    } else {
        console.error('shopDropdown element not found!');
    }
};

window.hideDropdown = function() {
    const dropdown = document.getElementById('shopDropdown');
    if (dropdown) {
        clearTimeout(dropdownTimeout);
        dropdownTimeout = setTimeout(() => {
            dropdown.classList.remove('show');
        }, 300);
    }
};

window.showShopDropdown = function() {
    const dropdown = document.getElementById('shopCategoryDropdown');
    if (dropdown) {
        clearTimeout(shopDropdownTimeout);
        dropdown.classList.add('show');
        dropdown.style.display = 'block';
        dropdown.style.opacity = '1';
        dropdown.style.visibility = 'visible';
    }
};

window.hideShopDropdown = function() {
    const dropdown = document.getElementById('shopCategoryDropdown');
    if (dropdown) {
        clearTimeout(shopDropdownTimeout);
        shopDropdownTimeout = setTimeout(() => {
            dropdown.classList.remove('show');
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
        }, 300);
    }
};

window.showMoreDropdown = function() {
    console.log('showMoreDropdown called');
    const dropdown = document.getElementById('moreDropdown');
    console.log('moreDropdown element:', dropdown);
    if (dropdown) {
        clearTimeout(moreDropdownTimeout);
        dropdown.classList.add('show');
        console.log('Added show class, classes:', dropdown.className);
    } else {
        console.error('moreDropdown element not found!');
    }
};

window.hideMoreDropdown = function() {
    const dropdown = document.getElementById('moreDropdown');
    if (dropdown) {
        clearTimeout(moreDropdownTimeout);
        moreDropdownTimeout = setTimeout(() => {
            dropdown.classList.remove('show');
        }, 300);
    }
};

window.showUserDropdown = function() {
    const dropdown = document.getElementById('userDropdownMenu');
    if (dropdown) {
        clearTimeout(userDropdownTimeout);
        dropdown.classList.add('show');
    }
};

window.hideUserDropdown = function() {
    const dropdown = document.getElementById('userDropdownMenu');
    if (dropdown) {
        clearTimeout(userDropdownTimeout);
        userDropdownTimeout = setTimeout(() => {
            dropdown.classList.remove('show');
        }, 300);
    }
};

document.addEventListener('DOMContentLoaded', function() {

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdownMenu');
        const avatar = document.querySelector('.user-avatar');
        if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Enhanced dropdown behavior - Brands Dropdown
    const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
    const brandsDropdown = document.getElementById('shopDropdown');

    if (shopCategoriesBtn && brandsDropdown) {
        shopCategoriesBtn.addEventListener('mouseenter', showDropdown);
        shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);
        brandsDropdown.addEventListener('mouseenter', function() {
            clearTimeout(dropdownTimeout);
        });
        brandsDropdown.addEventListener('mouseleave', hideDropdown);
        console.log('Brands dropdown listeners attached');
    } else {
        console.error('Brands dropdown elements not found:', {shopCategoriesBtn, brandsDropdown});
    }

    // Shop Category Dropdown - Fixed selector to work on all browsers
    const shopCategoryDropdown = document.getElementById('shopCategoryDropdown');
    // Find the parent nav-dropdown that contains the shop dropdown
    let shopNavDropdown = null;
    if (shopCategoryDropdown) {
        shopNavDropdown = shopCategoryDropdown.closest('.nav-dropdown');
        // If closest doesn't work, try finding by parent
        if (!shopNavDropdown) {
            let parent = shopCategoryDropdown.parentElement;
            while (parent && !parent.classList.contains('nav-dropdown')) {
                parent = parent.parentElement;
            }
            shopNavDropdown = parent;
        }
    }

    if (shopNavDropdown && shopCategoryDropdown) {
        // Add event listeners (inline handlers will also work as fallback)
        shopNavDropdown.addEventListener('mouseenter', showShopDropdown);
        shopNavDropdown.addEventListener('mouseleave', hideShopDropdown);
        shopCategoryDropdown.addEventListener('mouseenter', function() {
            clearTimeout(shopDropdownTimeout);
        });
        shopCategoryDropdown.addEventListener('mouseleave', hideShopDropdown);
        console.log('Shop category dropdown listeners attached');
    } else {
        console.error('Shop category dropdown elements not found:', {shopNavDropdown, shopCategoryDropdown});
        // Fallback: try to find by querySelector
        const fallbackNav = document.querySelector('.nav-dropdown');
        const fallbackDropdown = document.getElementById('shopCategoryDropdown');
        if (fallbackNav && fallbackDropdown && fallbackNav.contains(fallbackDropdown)) {
            fallbackNav.addEventListener('mouseenter', showShopDropdown);
            fallbackNav.addEventListener('mouseleave', hideShopDropdown);
            console.log('Shop category dropdown fallback listeners attached');
        }
    }

    // More Dropdown
    const moreNavDropdown = document.querySelector('.nav-dropdown:has(#moreDropdown)');
    const moreDropdown = document.getElementById('moreDropdown');

    if (moreNavDropdown && moreDropdown) {
        moreNavDropdown.addEventListener('mouseenter', showMoreDropdown);
        moreNavDropdown.addEventListener('mouseleave', hideMoreDropdown);
        moreDropdown.addEventListener('mouseenter', function() {
            clearTimeout(moreDropdownTimeout);
        });
        moreDropdown.addEventListener('mouseleave', hideMoreDropdown);
        console.log('More dropdown listeners attached');
    } else {
        console.error('More dropdown elements not found:', {moreNavDropdown, moreDropdown});
    }

    // User Avatar Dropdown
    const userAvatar = document.querySelector('.user-avatar');
    const userDropdown = document.getElementById('userDropdownMenu');

    if (userAvatar && userDropdown) {
        userAvatar.addEventListener('mouseenter', showUserDropdown);
        userAvatar.addEventListener('mouseleave', hideUserDropdown);
        userDropdown.addEventListener('mouseenter', function() {
            clearTimeout(userDropdownTimeout);
        });
        userDropdown.addEventListener('mouseleave', hideUserDropdown);
        console.log('User dropdown listeners attached');
    }
});
</script>

<!-- Wishlist Badge Update Script -->
<!-- SweetAlert2 for modern alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo $base_path; ?>js/wishlist.js"></script>

<!-- Purchase Notifications -->
<link href="<?php echo $base_path; ?>css/purchase_notifications.css" rel="stylesheet">
<script src="<?php echo $base_path; ?>js/purchase_notifications.js" defer></script>
