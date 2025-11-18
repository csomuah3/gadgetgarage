<?php
// Universal Header for All Customer Pages
// Initialize required variables and functions
if (!isset($is_logged_in)) {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');

    $is_logged_in = check_login();
    $is_admin = false;

    if ($is_logged_in) {
        $is_admin = check_admin();
    }

    // Get cart count
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);

    // Initialize arrays for navigation
    $categories = [];
    $brands = [];

    // Try to load categories and brands safely
    try {
        require_once(__DIR__ . '/../controllers/category_controller.php');
        $categories = get_all_categories_ctr();
    } catch (Exception $e) {
        error_log("Failed to load categories: " . $e->getMessage());
    }

    try {
        require_once(__DIR__ . '/../controllers/brand_controller.php');
        $brands = get_all_brands_ctr();
    } catch (Exception $e) {
        error_log("Failed to load brands: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Gadget Garage - Premium Refurbrished Tech Devices & Repair Services</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

    <!-- External Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.all.min.js"></script>

    <!-- Local Styles -->
    <link href="<?php echo isset($css_path_prefix) ? $css_path_prefix : ''; ?>includes/chatbot-styles.css" rel="stylesheet">
    <link href="<?php echo isset($css_path_prefix) ? $css_path_prefix : ''; ?>css/dark-mode.css" rel="stylesheet">

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

        /* Modern Header Styles - Based on image provided */
        .main-header {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Logo Styles */
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #1f2937;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        /* Navigation Styles */
        .main-nav {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex: 1;
            justify-content: center;
            position: relative;
        }

        .nav-dropdown {
            position: relative;
        }

        .nav-item {
            position: relative;
            padding: 8px 16px;
            text-decoration: none;
            color: #374151;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-item:hover,
        .nav-item.active {
            color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
        }

        .nav-item.shop-by-brands {
            background: #6366f1;
            color: white;
            padding: 10px 20px;
        }

        .nav-item.shop-by-brands:hover {
            background: #5b5fe8;
            color: white;
        }

        .nav-item.flash-deal {
            color: #ef4444;
            font-weight: 600;
        }

        .nav-item.flash-deal:hover {
            color: #dc2626;
            background: rgba(239, 68, 68, 0.1);
        }

        /* Dropdown Content */
        .dropdown-content {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 16px;
            min-width: 320px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .dropdown-content.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        .dropdown-section h6 {
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 0 8px 0;
            letter-spacing: 0.05em;
        }

        .dropdown-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .dropdown-link:hover {
            background: #f3f4f6;
            color: #6366f1;
        }

        .dropdown-link i {
            width: 16px;
            text-align: center;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .dropdown-link:hover i {
            color: #6366f1;
        }

        /* Special dropdown positioning for brands */
        #brandsDropdown {
            min-width: 250px;
        }

        #brandsDropdown .dropdown-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }

        /* Special dropdown positioning for MORE */
        #moreDropdown {
            right: 0;
            left: auto;
            min-width: 400px;
        }

        /* Search Bar */
        .search-container {
            position: relative;
            max-width: 400px;
            flex: 1;
            margin: 0 2rem;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .search-input:focus {
            outline: none;
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1rem;
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #6366f1;
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: #5b5fe8;
            transform: translateY(-50%) scale(1.05);
        }

        /* Right Side Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        /* Bring Retired Tech Section */
        .tech-revival-section {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #059669;
            font-size: 0.9rem;
        }

        .tech-revival-icon {
            font-size: 1.2rem;
            color: #059669;
        }

        .tech-revival-text {
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }

        .contact-number {
            font-weight: 500;
            margin: 0;
            line-height: 1.2;
            color: #374151;
        }

        /* User Actions */
        .user-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-icon {
            position: relative;
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6b7280;
        }

        .header-icon:hover {
            background: #e5e7eb;
            color: #374151;
            transform: scale(1.05);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* User Avatar */
        .user-dropdown {
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
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
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* Dropdown Menu */
        .dropdown-menu-custom {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 8px 0;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
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
            padding: 12px 16px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 0.95rem;
        }

        .dropdown-item-custom:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .dropdown-item-custom i {
            width: 16px;
            text-align: center;
            color: #6b7280;
        }

        .dropdown-divider-custom {
            height: 1px;
            background: #e5e7eb;
            margin: 4px 0;
        }

        /* Login Buttons */
        .login-btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .login-btn:first-child {
            color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
        }

        .login-btn:first-child:hover {
            background: rgba(99, 102, 241, 0.2);
            color: #5b5fe8;
        }

        .login-btn:last-child {
            color: white;
            background: #6366f1;
        }

        .login-btn:last-child:hover {
            background: #5b5fe8;
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .search-container {
                max-width: 300px;
                margin: 0 1rem;
            }
            .main-nav {
                gap: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .tech-revival-section {
                display: none;
            }
            .search-container {
                max-width: 250px;
            }
            .main-nav {
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 15px;
            }
            .main-nav {
                display: none;
            }
            .search-container {
                margin: 0 0.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="header-container">
            <!-- Logo -->
            <a href="<?php echo isset($logo_link) ? $logo_link : 'index.php'; ?>" class="logo">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                     alt="Gadget Garage"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span style="display:none;">GADGET <span class="garage">GARAGE</span></span>
            </a>

            <!-- Main Navigation -->
            <nav class="main-nav">
                <!-- Shop by Brands (with dropdown) -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-item shop-by-brands" onclick="toggleDropdown('brandsDropdown', event)">
                        <i class="fas fa-tag"></i> SHOP BY BRANDS
                    </a>
                    <div class="dropdown-content" id="brandsDropdown">
                        <div class="dropdown-grid">
                            <?php if (!empty($brands)): ?>
                                <?php foreach ($brands as $brand): ?>
                                    <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>all_product.php?brand=<?php echo $brand['brand_id']; ?>" class="dropdown-link">
                                        <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($brand['brand_name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <a href="#" class="dropdown-link"><i class="fas fa-mobile-alt"></i> Apple</a>
                                <a href="#" class="dropdown-link"><i class="fas fa-laptop"></i> Samsung</a>
                                <a href="#" class="dropdown-link"><i class="fas fa-tablet-alt"></i> Lenovo</a>
                                <a href="#" class="dropdown-link"><i class="fas fa-desktop"></i> HP</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Home -->
                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>index.php"
                   class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    HOME
                </a>

                <!-- Shop (with dropdown) -->
                <div class="nav-dropdown">
                    <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>all_product.php"
                       class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'all_product.php' ? 'active' : ''; ?>"
                       onclick="toggleDropdown('shopDropdown', event)">
                        SHOP <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content" id="shopDropdown">
                        <div class="dropdown-grid">
                            <div class="dropdown-section">
                                <h6>Categories</h6>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>all_product.php?category=<?php echo $category['cat_id']; ?>" class="dropdown-link">
                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category['cat_name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>mobile_devices.php" class="dropdown-link">
                                        <i class="fas fa-mobile-alt"></i> Mobile Devices
                                    </a>
                                    <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>computing.php" class="dropdown-link">
                                        <i class="fas fa-laptop"></i> Computing
                                    </a>
                                    <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>photography_video.php" class="dropdown-link">
                                        <i class="fas fa-camera"></i> Photography & Video
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-section">
                                <h6>Quick Links</h6>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>all_product.php" class="dropdown-link">
                                    <i class="fas fa-th"></i> All Products
                                </a>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>all_product.php?featured=1" class="dropdown-link">
                                    <i class="fas fa-star"></i> Featured Products
                                </a>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>all_product.php?sale=1" class="dropdown-link">
                                    <i class="fas fa-percentage"></i> On Sale
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Repair Studio -->
                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>repair_services.php"
                   class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'repair_services.php' ? 'active' : ''; ?>">
                    Repair Studio
                </a>

                <!-- Device Drop -->
                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>device_drop.php"
                   class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'device_drop.php' ? 'active' : ''; ?>">
                    Device Drop
                </a>

                <!-- More (with dropdown) -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-item" onclick="toggleDropdown('moreDropdown', event)">
                        MORE <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content" id="moreDropdown">
                        <div class="dropdown-grid">
                            <div class="dropdown-section">
                                <h6>Services</h6>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>repair_schedule.php" class="dropdown-link">
                                    <i class="fas fa-calendar-alt"></i> Schedule Repair
                                </a>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>repair_specialist.php" class="dropdown-link">
                                    <i class="fas fa-user-cog"></i> Repair Specialist
                                </a>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>device_drop.php" class="dropdown-link">
                                    <i class="fas fa-recycle"></i> Tech Revival
                                </a>
                            </div>
                            <div class="dropdown-section">
                                <h6>Support</h6>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>support_message.php" class="dropdown-link">
                                    <i class="fas fa-headset"></i> Contact Support
                                </a>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>contact.php" class="dropdown-link">
                                    <i class="fas fa-envelope"></i> Contact Us
                                </a>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>terms_conditions.php" class="dropdown-link">
                                    <i class="fas fa-file-contract"></i> Terms & Conditions
                                </a>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>legal.php" class="dropdown-link">
                                    <i class="fas fa-balance-scale"></i> Legal Info
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flash Deal -->
                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>all_product.php?flash=1" class="nav-item flash-deal">
                    ⚡ Flash Deal
                </a>
            </nav>

            <!-- Search Bar -->
            <div class="search-container">
                <form method="GET" action="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>product_search_result.php">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                           name="query"
                           class="search-input"
                           placeholder="Search phones, laptops, cameras..."
                           value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>"
                           required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Right Side Actions -->
            <div class="header-actions">
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
                            <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>wishlist.php" style="color: inherit; text-decoration: none;">
                                <i class="fas fa-heart"></i>
                            </a>
                        </div>

                        <!-- Cart Icon -->
                        <div class="header-icon">
                            <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>cart.php" style="color: inherit; text-decoration: none;">
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
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>my_orders.php" class="dropdown-item-custom">
                                    <i class="fas fa-box"></i>
                                    <span>My Orders</span>
                                </a>
                                <div class="dropdown-divider-custom"></div>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>wishlist.php" class="dropdown-item-custom">
                                    <i class="fas fa-heart"></i>
                                    <span>Wishlist</span>
                                </a>
                                <?php if ($is_admin): ?>
                                    <div class="dropdown-divider-custom"></div>
                                    <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>admin/index.php" class="dropdown-item-custom">
                                        <i class="fas fa-cog"></i>
                                        <span>Admin Panel</span>
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider-custom"></div>
                                <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>login/logout.php" class="dropdown-item-custom">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Not logged in: Register | Login -->
                        <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>login/register.php" class="login-btn">Register</a>
                        <a href="<?php echo isset($nav_path_prefix) ? $nav_path_prefix : ''; ?>login/login.php" class="login-btn">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- JavaScript for header functionality -->
    <script>
        // Header dropdown functions
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            dropdown.classList.toggle('show');
        }

        function toggleDropdown(dropdownId, event) {
            if (event) event.preventDefault();

            // Close all other dropdowns first
            document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                if (dropdown.id !== dropdownId) {
                    dropdown.classList.remove('show');
                }
            });

            // Toggle the selected dropdown
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        }

        function openProfilePictureModal() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Profile Picture',
                    text: 'Profile picture modal not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#6366f1',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Profile picture modal not implemented yet');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const target = event.target;
            const isDropdownButton = target.closest('.user-avatar') || target.closest('.nav-item');
            const isDropdownContent = target.closest('.dropdown-menu-custom') || target.closest('.dropdown-content');

            if (!isDropdownButton && !isDropdownContent) {
                document.querySelectorAll('.dropdown-menu-custom, .dropdown-content').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });

        // Update cart badge
        function updateCartBadge(count) {
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                if (count > 0) {
                    cartBadge.textContent = count;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }
    </script>