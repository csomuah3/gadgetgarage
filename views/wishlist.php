<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');
require_once(__DIR__ . '/../helpers/image_helper.php');

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

// Get user's name for sidebar
$user_name = $_SESSION['name'] ?? 'User';
$first_name = explode(' ', $user_name)[0];
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
    
    <!-- Reusable Header CSS -->
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">
    <link href="../includes/account_sidebar.css" rel="stylesheet">
    
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
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
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
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }
        }
        /* Footer Styles */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 0;
        }

        .footer-brand {
            margin-bottom: 30px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo img {
            height: 50px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .footer-logo .garage {
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .footer-description {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social-link:hover {
            background: #2563EB;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 24px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 14px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #2563EB;
            transform: translateX(4px);
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 40px 0 20px;
        }

        .footer-bottom {
            padding-top: 20px;
        }

        .copyright {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }

        /* Newsletter Signup Section */
        .newsletter-signup-section {
            background: transparent;
            padding: 0;
            text-align: left;
            max-width: 100%;
            height: fit-content;
        }

        .newsletter-title {
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .newsletter-form {
            display: flex;
            width: 100%;
            margin: 0 0 15px 0;
            gap: 0;
            border-radius: 50px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .newsletter-input {
            flex: 1;
            padding: 14px 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1a1a1a;
            background: #e5e7eb;
        }

        .newsletter-input::placeholder {
            color: #6b7280;
        }

        .newsletter-submit-btn {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border: none;
            background: #9ca3af;
            color: #ffffff;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            padding: 0;
        }

        .newsletter-submit-btn:hover {
            background: #6b7280;
            transform: scale(1.05);
        }

        .newsletter-disclaimer {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 8px 0 0 0;
            text-align: left;
        }

        .newsletter-disclaimer a {
            color: #2563EB;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .newsletter-disclaimer a:hover {
            color: #1d4ed8;
        }

        @media (max-width: 991px) {
            .newsletter-signup-section {
                margin-top: 20px;
            }
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }

        .scroll-to-top.show {
            display: flex;
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            background: linear-gradient(135deg, #2563EB, #1E3A5F);
            transform: translateX(-50%) translateY(-3px);
            box-shadow: 0 6px 16px rgba(30, 58, 95, 0.4);
        }

        .scroll-to-top:active {
            transform: translateX(-50%) translateY(-1px);
        }

        @media (max-width: 768px) {
            .scroll-to-top {
                bottom: 20px;
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }
    </style>
</head>

<body class="page-background">
    <!-- Reusable Header Component -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Account Sidebar -->
        <?php include '../includes/account_sidebar.php'; ?>

        <!-- Content Area -->
        <main class="content-area">
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
                                <img src="<?php echo get_product_image_url($item['product_image'] ?? '', $item['product_title'] ?? 'Product'); ?>"
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
        </main>
    </div>
    <!-- End Main Content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/dark-mode.js"></script>

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

        // Remove from Wishlist Function (Make globally available)
        window.removeFromWishlist = function(productId, button) {
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

        // Add to Cart from Wishlist Function (Make globally available)
        window.addToCartFromWishlist = function(productId) {
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

        // View Product Function (Make globally available)
        window.viewProduct = function(productId) {
            window.location.href = `single_product.php?pid=${productId}`;
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

    <!-- AI Recommendations Section -->
    <?php include '../includes/ai_recommendations_section.php'; ?>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row align-items-start">
                    <!-- First Column: Logo and Social -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-brand">
                            <div class="footer-logo" style="margin-bottom: 20px;">
                                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                                    alt="Gadget Garage">
                            </div>
                            <p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- Navigation Links -->
                    <div class="col-lg-5 col-md-12">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Get Help</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Help Center</a></li>
                                    <li><a href="contact.php">Track Order</a></li>
                                    <li><a href="terms_conditions.php">Shipping Info</a></li>
                                    <li><a href="terms_conditions.php">Returns</a></li>
                                    <li><a href="contact.php">Contact Us</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Company</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Careers</a></li>
                                    <li><a href="contact.php">About</a></li>
                                    <li><a href="contact.php">Stores</a></li>
                                    <li><a href="contact.php">Want to Collab?</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Quick Links</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Size Guide</a></li>
                                    <li><a href="contact.php">Sitemap</a></li>
                                    <li><a href="contact.php">Gift Cards</a></li>
                                    <li><a href="contact.php">Check Gift Card Balance</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Right Side: Email Signup Form -->
                    <div class="col-lg-4 col-md-12 mb-4">
                        <div class="newsletter-signup-section">
                            <h3 class="newsletter-title">SIGN UP FOR DISCOUNTS + UPDATES</h3>
                            <form class="newsletter-form" id="newsletterForm">
                                <input type="text" class="newsletter-input" placeholder="Phone Number or Email" required>
                                <button type="submit" class="newsletter-submit-btn">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                            <p class="newsletter-disclaimer">
                                By signing up for email, you agree to Gadget Garage's <a href="terms_conditions.php">Terms of Service</a> and <a href="legal.php">Privacy Policy</a>.
                            </p>
                            <p class="newsletter-disclaimer">
                                By submitting your phone number, you agree to receive recurring automated promotional and personalized marketing text messages (e.g. cart reminders) from Gadget Garage at the cell number used when signing up. Consent is not a condition of any purchase. Reply HELP for help and STOP to cancel. Msg frequency varies. Msg & data rates may apply. <a href="terms_conditions.php">View Terms</a> & <a href="legal.php">Privacy</a>.
                            </p>
                        </div>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-12 text-center">
                            <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Scroll to Top Button Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const scrollToTopBtn = document.getElementById('scrollToTopBtn');
            
            if (scrollToTopBtn) {
                // Show/hide button based on scroll position
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollToTopBtn.classList.add('show');
                    } else {
                        scrollToTopBtn.classList.remove('show');
                    }
                });

                // Scroll to top when button is clicked
                scrollToTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>

</html>