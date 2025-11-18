<?php
// This file should be included after setting up the necessary variables:
// $is_logged_in, $is_admin, $cart_count, $brands, $categories

// Include language configuration
require_once __DIR__ . '/language_config.php';

// Load brands and categories if not already loaded
if (!isset($brands) || empty($brands)) {
    try {
        require_once __DIR__ . '/../controllers/brand_controller.php';
        $brands = get_all_brands_ctr() ?: [];
    } catch (Exception $e) {
        $brands = [];
        error_log("Failed to load brands in header: " . $e->getMessage());
    }
}

if (!isset($categories) || empty($categories)) {
    try {
        require_once __DIR__ . '/../controllers/category_controller.php';
        $categories = get_all_categories_ctr() ?: [];
    } catch (Exception $e) {
        $categories = [];
        error_log("Failed to load categories in header: " . $e->getMessage());
    }
}

// Initialize user state variables if not set
if (!isset($is_logged_in)) {
    require_once __DIR__ . '/../settings/core.php';
    $is_logged_in = check_login();
    $is_admin = $is_logged_in ? check_admin() : false;
}

// Get cart count for logged in users
if (!isset($cart_count)) {
    $cart_count = 0;
    if ($is_logged_in && !$is_admin) {
        try {
            require_once __DIR__ . '/../controllers/cart_controller.php';
            $cart_count = get_cart_count_ctr($_SESSION['user_id']) ?: 0;
        } catch (Exception $e) {
            error_log("Failed to get cart count in header: " . $e->getMessage());
        }
    }
}
?>

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
                    <input type="text" name="query" class="search-input" placeholder="<?= t('search_placeholder') ?>" required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <!-- Tech Revival Section -->
                <div class="tech-revival-section">
                    <i class="fas fa-recycle tech-revival-icon"></i>
                    <div>
                        <p class="tech-revival-text"><?= t('tech_revival') ?></p>
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
                                    <span><?= t('language') ?></span>
                                    <select class="form-select form-select-sm" style="border: none; background: transparent; font-size: 0.8rem;" onchange="changeLanguage(this.value)">
                                        <?php foreach ($available_languages as $lang_code => $lang_info): ?>
                                            <option value="<?= $lang_code ?>" <?= ($current_language === $lang_code) ? 'selected' : '' ?>>
                                                <?= $lang_info['flag'] ?> <?= $lang_info['code'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="dropdown-item-custom">
                                <i class="fas fa-moon"></i>
                                <div class="theme-toggle">
                                    <span><?= t('dark_mode') ?></span>
                                    <div class="toggle-switch" id="themeToggle" onclick="toggleTheme()">
                                        <div class="toggle-slider"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider-custom"></div>
                            <a href="my_orders.php" class="dropdown-item-custom">
                                <i class="fas fa-box"></i>
                                <span><?= t('my_orders') ?></span>
                            </a>
                            <div class="dropdown-divider-custom"></div>
                            <a href="wishlist.php" class="dropdown-item-custom">
                                <i class="fas fa-heart"></i>
                                <span><?= t('wishlist') ?></span>
                            </a>
                            <div class="dropdown-divider-custom"></div>
                            <a href="notifications.php" class="dropdown-item-custom" onclick="showNotifications(); return false;">
                                <i class="fas fa-bell"></i>
                                <span><?= t('notifications') ?></span>
                                <?php
                                if ($is_logged_in && !$is_admin) {
                                    require_once __DIR__ . '/../controllers/support_controller.php';
                                    $unread_count = get_unread_notification_count_ctr($_SESSION['user_id']);
                                    if ($unread_count > 0) {
                                        echo '<span class="notification-badge">' . $unread_count . '</span>';
                                    }
                                }
                                ?>
                            </a>
                            <?php if ($is_admin): ?>
                                <div class="dropdown-divider-custom"></div>
                                <a href="admin/category.php" class="dropdown-item-custom">
                                    <i class="fas fa-cog"></i>
                                    <span><?= t('admin_panel') ?></span>
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider-custom"></div>
                            <a href="login/logout.php" class="dropdown-item-custom">
                                <i class="fas fa-sign-out-alt"></i>
                                <span><?= t('logout') ?></span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Not logged in: Register | Login -->
                    <a href="login/register.php" class="login-btn me-2"><?= t('register') ?></a>
                    <a href="login/login.php" class="login-btn"><?= t('login') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Main Navigation -->
<nav class="main-nav">
    <div class="container">
        <div class="nav-menu">
            <!-- Shop by Brands Button -->
            <div class="shop-categories-btn" onmouseenter="showDropdown()" onmouseleave="hideDropdown()">
                <button class="categories-button">
                    <i class="fas fa-tags"></i>
                    <?= t('shop_by_brands') ?>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="brands-dropdown" id="shopDropdown">
                    <h4><?= t('all_brands') ?></h4>
                    <ul>
                        <?php if (!empty($brands) && count($brands) > 0): ?>
                            <?php foreach ($brands as $brand): ?>
                                <li><a href="all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                            <?php endforeach; ?>
                            <li class="divider"></li>
                            <li><a href="all_product.php"><i class="fas fa-th-large"></i> <?= t('all_products') ?></a></li>
                        <?php else: ?>
                            <li><a href="all_product.php"><i class="fas fa-th-large"></i> <?= t('all_products') ?></a></li>
                            <li class="no-brands"><small>No brands available</small></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <a href="index.php" class="nav-item"><?= t('home') ?></a>

            <!-- Shop Dropdown -->
            <div class="nav-dropdown" onmouseenter="showShopDropdown()" onmouseleave="hideShopDropdown()">
                <a href="#" class="nav-item">
                    <?= t('shop') ?>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="mega-dropdown" id="shopCategoryDropdown">
                    <div class="dropdown-content">
                        <div class="dropdown-column">
                            <h4>
                                <a href="mobile_devices.php" style="text-decoration: none; color: inherit;">
                                    <?= t('mobile_devices') ?>
                                </a>
                            </h4>
                            <ul>
                                <li><a href="all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <?= t('smartphones') ?></a></li>
                                <li><a href="all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <?= t('ipads') ?></a></li>
                            </ul>
                        </div>
                        <div class="dropdown-column">
                            <h4>
                                <a href="computing.php" style="text-decoration: none; color: inherit;">
                                    <?= t('computing') ?>
                                </a>
                            </h4>
                            <ul>
                                <li><a href="all_product.php?category=laptops"><i class="fas fa-laptop"></i> <?= t('laptops') ?></a></li>
                                <li><a href="all_product.php?category=desktops"><i class="fas fa-desktop"></i> <?= t('desktops') ?></a></li>
                            </ul>
                        </div>
                        <div class="dropdown-column">
                            <h4>
                                <a href="photography_video.php" style="text-decoration: none; color: inherit;">
                                    <?= t('photography_video') ?>
                                </a>
                            </h4>
                            <ul>
                                <li><a href="all_product.php?category=cameras"><i class="fas fa-camera"></i> <?= t('cameras') ?></a></li>
                                <li><a href="all_product.php?category=video_equipment"><i class="fas fa-video"></i> <?= t('video_equipment') ?></a></li>
                            </ul>
                        </div>
                        <div class="dropdown-column featured">
                            <h4><?= t('shop_all') ?></h4>
                            <div class="featured-item">
                                <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="<?= t('new_arrivals') ?>">
                                <div class="featured-text">
                                    <strong><?= t('new_arrivals') ?></strong>
                                    <p><?= t('latest_tech_gadgets') ?></p>
                                    <a href="all_product.php" class="shop-now-btn"><?= t('shop_now') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <a href="repair_services.php" class="nav-item"><?= t('repair_studio') ?></a>
            <a href="device_drop.php" class="nav-item"><?= t('device_drop') ?></a>

            <!-- More Dropdown -->
            <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                <a href="#" class="nav-item">
                    <?= t('more') ?>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="simple-dropdown" id="moreDropdown">
                    <ul>
                        <li><a href="contact.php"><i class="fas fa-phone"></i> <?= t('contact') ?></a></li>
                        <li><a href="terms_conditions.php"><i class="fas fa-file-contract"></i> <?= t('terms_conditions') ?></a></li>
                    </ul>
                </div>
            </div>

            <!-- Flash Deal positioned at far right -->
            <a href="#" class="nav-item flash-deal"><?= t('flash_deal') ?></a>
        </div>
    </div>
</nav>