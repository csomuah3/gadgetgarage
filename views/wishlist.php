<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');

// Check login status
$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Get wishlist items if user is logged in
$wishlist_items = [];
$wishlist_count = 0;
if ($is_logged_in) {
    $wishlist_items = get_wishlist_items_ctr($customer_id);
    $wishlist_count = get_wishlist_count_ctr($customer_id);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Wishlist - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
        }

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
            margin-right: 40px;
        }

        .logo .garage {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .wishlist-container {
            padding: 40px 0;
            min-height: 80vh;
        }

        .wishlist-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 30px;
            text-align: center;
        }

        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-wishlist i {
            font-size: 4rem;
            color: #e5e7eb;
            margin-bottom: 20px;
        }

        .empty-wishlist h3 {
            font-size: 1.5rem;
            color: #6b7280;
            margin-bottom: 15px;
        }

        .empty-wishlist p {
            color: #9ca3af;
            margin-bottom: 30px;
        }

        .shop-btn {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .shop-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            color: white;
            transform: translateY(-2px);
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .wishlist-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .wishlist-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .product-image-container {
            position: relative;
            margin-bottom: 15px;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .remove-wishlist-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(239, 68, 68, 0.9);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .remove-wishlist-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #008060;
            margin-bottom: 15px;
        }

        .product-condition {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .add-to-cart-btn {
            flex: 1;
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-1px);
        }

        .view-details-btn {
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background: #e5e7eb;
            color: #1f2937;
        }

        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Promo Banner -->
    <div class="promo-banner2">
        <div class="container">
            <div class="promo-text">
                <i class="fas fa-bolt"></i>
                <span>LIMITED TIME OFFER</span>
            </div>
            <div class="countdown-timer" id="promoCountdown">
                <span>Ends in:</span>
                <div class="time-unit" id="hours">00</div>
                <span>:</span>
                <div class="time-unit" id="minutes">00</div>
                <span>:</span>
                <div class="time-unit" id="seconds">00</div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-container">
            <div class="header-top">
                <!-- Logo -->
                <a href="../index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png" alt="Gadget Garage">
                </a>

                <!-- Search Bar -->
                <div class="search-container">
                    <form class="search-form" action="../product_search_result.php" method="GET">
                        <input type="text" name="search" class="search-input" placeholder="Search for gadgets, phones, laptops..." required>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Tech Revival -->
                <a href="../repair_services.php" class="tech-revival">
                    <i class="fas fa-tools"></i>
                    Tech Revival
                </a>

                <!-- User Actions -->
                <div class="user-actions">
                    <?php if ($is_logged_in): ?>
                        <!-- Wishlist -->
                        <a href="../views/wishlist.php" class="action-item">
                            <i class="fas fa-heart"></i>
                            <span>Wishlist</span>
                            <?php if ($wishlist_count > 0): ?>
                                <span class="badge"><?php echo $wishlist_count; ?></span>
                            <?php endif; ?>
                        </a>

                        <!-- Cart -->
                        <a href="../views/cart.php" class="action-item">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart</span>
                            <?php if ($cart_count > 0): ?>
                                <span class="badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>

                        <!-- Account Dropdown -->
                        <div class="dropdown">
                            <a href="#" class="action-item">
                                <i class="fas fa-user"></i>
                                <span>Account</span>
                            </a>
                            <div class="dropdown-menu">
                                <a href="../views/account.php" class="dropdown-item">My Profile</a>
                                <a href="../views/my_orders.php" class="dropdown-item">My Orders</a>
                                <a href="../views/notifications.php" class="dropdown-item">Notifications</a>
                                <?php if ($is_admin): ?>
                                    <a href="../admin/dashboard.php" class="dropdown-item">Admin Panel</a>
                                <?php endif; ?>
                                <hr style="margin: 5px 0; border: none; border-top: 1px solid #e5e7eb;">
                                <a href="../login/logout.php" class="dropdown-item">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login/Register -->
                        <a href="../login/login.php" class="action-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="../views/register.php" class="action-item">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    <?php endif; ?>

                    <!-- Dark Mode Toggle -->
                    <button class="dark-mode-toggle" onclick="toggleDarkMode()" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="main-nav">
            <div class="container nav-container">
                <ul class="nav-links">
                    <!-- Shop Categories -->
                    <li class="nav-item">
                        <a href="#" class="nav-link has-dropdown">
                            <i class="fas fa-th-large"></i>
                            Shop Categories
                        </a>
                        <div class="mega-menu">
                            <div class="mega-menu-grid">
                                <div class="mega-menu-category">
                                    <h4><i class="fas fa-mobile-alt"></i> Mobile Devices</h4>
                                    <ul>
                                        <li><a href="../views/mobile_devices.php?category=smartphones">Smartphones</a></li>
                                        <li><a href="../views/mobile_devices.php?category=tablets">Tablets</a></li>
                                        <li><a href="../views/mobile_devices.php?category=smartwatches">Smartwatches</a></li>
                                        <li><a href="../views/mobile_devices.php?category=accessories">Phone Accessories</a></li>
                                    </ul>
                                </div>
                                <div class="mega-menu-category">
                                    <h4><i class="fas fa-laptop"></i> Computing</h4>
                                    <ul>
                                        <li><a href="../views/computing.php?category=laptops">Laptops</a></li>
                                        <li><a href="../views/computing.php?category=desktops">Desktops</a></li>
                                        <li><a href="../views/computing.php?category=components">Components</a></li>
                                        <li><a href="../views/computing.php?category=peripherals">Peripherals</a></li>
                                    </ul>
                                </div>
                                <div class="mega-menu-category">
                                    <h4><i class="fas fa-camera"></i> Photography</h4>
                                    <ul>
                                        <li><a href="../views/photography_video.php?category=cameras">Cameras</a></li>
                                        <li><a href="../views/photography_video.php?category=lenses">Lenses</a></li>
                                        <li><a href="../views/photography_video.php?category=tripods">Tripods</a></li>
                                        <li><a href="../views/photography_video.php?category=accessories">Photo Accessories</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Brands -->
                    <li class="nav-item">
                        <a href="#" class="nav-link has-dropdown">
                            <i class="fas fa-tags"></i>
                            Brands
                        </a>
                        <div class="brands-dropdown">
                            <div class="brands-grid">
                                <a href="../views/all_product.php?brand=apple" class="brand-item">
                                    <i class="fab fa-apple"></i> Apple
                                </a>
                                <a href="../views/all_product.php?brand=samsung" class="brand-item">
                                    <i class="fas fa-mobile"></i> Samsung
                                </a>
                                <a href="../views/all_product.php?brand=sony" class="brand-item">
                                    <i class="fas fa-tv"></i> Sony
                                </a>
                                <a href="../views/all_product.php?brand=canon" class="brand-item">
                                    <i class="fas fa-camera"></i> Canon
                                </a>
                                <a href="../views/all_product.php?brand=hp" class="brand-item">
                                    <i class="fas fa-laptop"></i> HP
                                </a>
                                <a href="../views/all_product.php?brand=dell" class="brand-item">
                                    <i class="fas fa-desktop"></i> Dell
                                </a>
                            </div>
                        </div>
                    </li>

                    <!-- Regular Navigation Links -->
                    <li class="nav-item">
                        <a href="../views/all_product.php" class="nav-link">
                            <i class="fas fa-shopping-bag"></i>
                            All Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../views/device_drop.php" class="nav-link">
                            <i class="fas fa-recycle"></i>
                            Device Drop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../views/contact.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            Contact
                        </a>
                    </li>
                </ul>

                <!-- Flash Deals -->
                <a href="../views/flash_deals.php" class="flash-deals">
                    <i class="fas fa-bolt"></i>
                    Flash Deals
                </a>
            </div>
        </nav>
    </header>

    <!-- Wishlist Content -->
    <div class="wishlist-container">
        <div class="container">
            <h1 class="wishlist-title">My Wishlist</h1>

            <?php if (!$is_logged_in): ?>
                <div class="empty-wishlist">
                    <i class="fas fa-user-lock" style="font-size: 4rem; color: #e5e7eb; margin-bottom: 20px;"></i>
                    <h3>Please log in to view your wishlist</h3>
                    <p>You need to be logged in to access your saved items.</p>
                    <a href="login.php" class="shop-btn">Log In</a>
                </div>
            <?php elseif (empty($wishlist_items)): ?>
                <div class="empty-wishlist">
                    <i class="fas fa-heart"></i>
                    <h3>Your wishlist is empty</h3>
                    <p>Start adding products to your wishlist to save them for later!</p>
                    <a href="all_product.php" class="shop-btn">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="wishlist-item">
                            <div class="product-image-container">
                                <img src="<?php echo htmlspecialchars($item['product_image'] ?: '../uploads/default-product.png'); ?>"
                                     alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                     class="product-image">
                                <button class="remove-wishlist-btn"
                                        onclick="removeFromWishlist(<?php echo $item['product_id']; ?>, this)"
                                        title="Remove from wishlist">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <h3 class="product-title"><?php echo htmlspecialchars($item['product_title']); ?></h3>
                            <div class="product-price">GH₵<?php echo number_format($item['product_price'], 2); ?></div>

                            <?php if (!empty($item['product_condition'])): ?>
                                <span class="product-condition">Condition: <?php echo htmlspecialchars($item['product_condition']); ?></span>
                            <?php endif; ?>

                            <div class="action-buttons">
                                <button class="add-to-cart-btn" onclick="addToCartFromWishlist(<?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <button class="view-details-btn" onclick="viewProduct(<?php echo $item['product_id']; ?>)">
                                    View
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Promo Banner Countdown Timer
        function updateCountdown() {
            const now = new Date().getTime();
            const endOfDay = new Date();
            endOfDay.setHours(23, 59, 59, 999);
            const distance = endOfDay.getTime() - now;

            if (distance > 0) {
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            }
        }

        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call

        // Dark Mode Toggle
        function toggleDarkMode() {
            const body = document.body;
            const isDarkMode = body.getAttribute('data-theme') === 'dark';

            if (isDarkMode) {
                body.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.setAttribute('data-theme', 'dark');
            }
        });

        // Search functionality
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const searchInput = document.querySelector('.search-input');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                searchInput.focus();
            }
        });
        function removeFromWishlist(productId, button) {
            fetch('../actions/remove_from_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the wishlist item from the page
                    const wishlistItem = button.closest('.wishlist-item');
                    wishlistItem.style.transition = 'all 0.3s ease';
                    wishlistItem.style.opacity = '0';
                    wishlistItem.style.transform = 'scale(0.8)';

                    setTimeout(() => {
                        wishlistItem.remove();

                        // Check if there are no more items
                        const remainingItems = document.querySelectorAll('.wishlist-item');
                        if (remainingItems.length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    }, 300);
                } else {
                    alert(data.message || 'Failed to remove item from wishlist');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove item from wishlist');
            });
        }

        function addToCartFromWishlist(productId) {
            fetch('../actions/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&qty=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Item added to cart successfully!');

                    // Optional: Show cart sidebar if available
                    if (window.showCartSidebar) {
                        window.showCartSidebar();
                    }
                } else {
                    alert(data.message || 'Failed to add item to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add item to cart');
            });
        }

        function viewProduct(productId) {
            window.location.href = `single_product.php?product_id=${productId}`;
        }

        // Dropdown navigation functions with timeout delays
        let dropdownTimeout;
        let shopDropdownTimeout;
        let moreDropdownTimeout;
        let userDropdownTimeout;

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
                clearTimeout(dropdownTimeout);
                dropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

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

        function showUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                userDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        }

        // Enhanced dropdown behavior
        document.addEventListener('DOMContentLoaded', function() {
            const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
            const brandsDropdown = document.getElementById('shopDropdown');
            
            if (shopCategoriesBtn && brandsDropdown) {
                shopCategoriesBtn.addEventListener('mouseenter', showDropdown);
                shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);
                brandsDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(dropdownTimeout);
                });
                brandsDropdown.addEventListener('mouseleave', hideDropdown);
            }

            const userAvatar = document.querySelector('.user-avatar');
            const userDropdown = document.getElementById('userDropdownMenu');
            
            if (userAvatar && userDropdown) {
                userAvatar.addEventListener('mouseenter', showUserDropdown);
                userAvatar.addEventListener('mouseleave', hideUserDropdown);
                userDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(userDropdownTimeout);
                });
                userDropdown.addEventListener('mouseleave', hideUserDropdown);
            }
        });
    </script>
</body>

</html>