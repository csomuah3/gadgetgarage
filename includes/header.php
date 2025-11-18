<?php
// This file should be included after setting up the necessary variables:
// $is_logged_in, $is_admin, $cart_count, $brands, $categories
?>

<!-- Main Header -->
<header class="main-header animate__animated animate__fadeInDown">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between w-100 header-container">
            <!-- Logo -->
            <a href="index.php" class="logo">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                     alt="Gadget Garage"
                     style="height: 40px; width: auto; object-fit: contain;">
            </a>

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

            <!-- User Actions -->
            <div class="user-actions">
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
                            <div class="dropdown-divider-custom"></div>
                            <a href="notifications.php" class="dropdown-item-custom" onclick="showNotifications(); return false;">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
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
    <div class="container">
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
                        <li><a href="contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                        <li><a href="terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                    </ul>
                </div>
            </div>

            <!-- Flash Deal positioned at far right -->
            <a href="#" class="nav-item flash-deal">⚡ FLASH DEAL</a>
        </div>
    </div>
</nav>