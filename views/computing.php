<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/product_controller.php');
require_once(__DIR__ . '/../controllers/category_controller.php');
require_once(__DIR__ . '/../controllers/brand_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');
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

// Get categories to find laptop and desktop category IDs
$categories = get_all_categories_ctr();
$laptop_category_id = null;
$desktop_category_id = null;

// Find laptop and desktop categories by name (case-insensitive)
foreach ($categories as $cat) {
    $cat_name_lower = strtolower(trim($cat['cat_name']));
    if (strpos($cat_name_lower, 'laptop') !== false || strpos($cat_name_lower, 'notebook') !== false) {
        $laptop_category_id = $cat['cat_id'];
    }
    if (strpos($cat_name_lower, 'desktop') !== false || strpos($cat_name_lower, 'computer') !== false) {
        $desktop_category_id = $cat['cat_id'];
    }
}

// Get all products first
$all_products = get_all_products_ctr();

// Try to get products by category IDs
$computing_products = [];
if ($laptop_category_id) {
    $laptop_products = get_products_by_category_ctr($laptop_category_id);
    if (!empty($laptop_products)) {
        $computing_products = array_merge($computing_products, $laptop_products);
    }
}
if ($desktop_category_id) {
    $desktop_products = get_products_by_category_ctr($desktop_category_id);
    if (!empty($desktop_products)) {
        $computing_products = array_merge($computing_products, $desktop_products);
    }
}

// If no products found by ID, or IDs not found, filter by category name
if (empty($computing_products)) {
    $computing_products = array_filter($all_products, function ($product) {
        $cat_name = isset($product['cat_name']) ? strtolower($product['cat_name']) : '';
        $product_title = isset($product['product_title']) ? strtolower($product['product_title']) : '';

        return (strpos($cat_name, 'laptop') !== false ||
            strpos($cat_name, 'desktop') !== false ||
            strpos($cat_name, 'notebook') !== false ||
            strpos($cat_name, 'computer') !== false ||
            strpos($product_title, 'laptop') !== false ||
            strpos($product_title, 'desktop') !== false ||
            strpos($product_title, 'notebook') !== false ||
            strpos($product_title, 'pc') !== false);
    });
}

// Remove duplicates and re-index
$computing_products = array_values(array_unique($computing_products, SORT_REGULAR));

// Get brands
try {
    $brands = get_all_brands_ctr();
} catch (Exception $e) {
    $brands = [];
}

// Filter products based on URL parameters
$category_filter = $_GET['category'] ?? '';
$brand_filter = $_GET['brand'] ?? '';
$search_query = $_GET['search'] ?? '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 50000;
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : '';

$filtered_products = $computing_products;

// Apply filters
if (!empty($category_filter) && $category_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function ($product) use ($category_filter) {
        return isset($product['cat_id']) && $product['cat_id'] == $category_filter;
    });
}

if (!empty($brand_filter) && $brand_filter !== 'all') {
    $filtered_products = array_filter($filtered_products, function ($product) use ($brand_filter) {
        return isset($product['brand_id']) && $product['brand_id'] == $brand_filter;
    });
}

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function ($product) use ($search_query) {
        return stripos($product['product_title'], $search_query) !== false ||
            (isset($product['product_desc']) && stripos($product['product_desc'], $search_query) !== false);
    });
}

// Price filter
$filtered_products = array_filter($filtered_products, function ($product) use ($min_price, $max_price) {
    $price = isset($product['product_price']) ? floatval($product['product_price']) : 0;
    return $price >= $min_price && $price <= $max_price;
});

// Pagination
$products_per_page = 12;
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $products_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;
$products_to_display = array_slice($filtered_products, $offset, $products_per_page);

// Template parameters - JOINT CATEGORY PAGE
$is_joint_category = true; // Joint category page
$category_id = null;
$joint_category_ids = [];
if ($laptop_category_id) $joint_category_ids[] = $laptop_category_id;
if ($desktop_category_id) $joint_category_ids[] = $desktop_category_id;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Computing - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="page-background">
    <?php include '../includes/header.php'; ?>

    <!-- Page Title -->
    <div class="container-fluid">
        <div class="text-center py-3">
            <h1 style="color: #1f2937; font-weight: 700; margin: 0;">Computing</h1>
        </div>
    </div>

    <!-- Category Product Layout Template -->
    <?php include '../includes/category_product_layout.php'; ?>

    <!-- Footer -->
    <footer class="main-footer" style="background: #ffffff; border-top: 1px solid #e5e7eb; padding: 60px 0 20px; margin-top: 150px;">
        <div class="container">
            <div class="footer-content">
                <div class="row align-items-start">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-brand">
                            <div class="footer-logo" style="margin-bottom: 20px;">
                                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png" alt="Gadget Garage">
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

    <?php include '../includes/cart_sidebar.php'; ?>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cart.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/chatbot.js"></script>
    <script src="../js/compare.js"></script>

    <script>
        // Global toggleWishlist function
        window.toggleWishlist = function(productId, button) {
            <?php if (!$is_logged_in): ?>
                window.location.href = '../login/login.php';
                return;
            <?php endif; ?>

            const icon = button.querySelector('i');
            const isActive = button.classList.contains('active');

            if (isActive) {
                button.classList.remove('active');
                icon.className = 'far fa-heart';
                icon.style.color = '#6b7280';

                fetch('../actions/remove_from_wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'product_id=' + productId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const wishlistBadge = document.getElementById('wishlistBadge');
                            if (wishlistBadge) {
                                let count = parseInt(wishlistBadge.textContent) || 0;
                                count = Math.max(0, count - 1);
                                wishlistBadge.textContent = count;
                                wishlistBadge.style.display = count > 0 ? 'flex' : 'none';
                            }
                        } else {
                            button.classList.add('active');
                            icon.className = 'fas fa-heart';
                            icon.style.color = '#ef4444';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        button.classList.add('active');
                        icon.className = 'fas fa-heart';
                        icon.style.color = '#ef4444';
                    });
            } else {
                button.classList.add('active');
                icon.className = 'fas fa-heart';
                icon.style.color = '#ef4444';

                fetch('../actions/add_to_wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'product_id=' + productId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const wishlistBadge = document.getElementById('wishlistBadge');
                            if (wishlistBadge) {
                                let count = parseInt(wishlistBadge.textContent) || 0;
                                count++;
                                wishlistBadge.textContent = count;
                                wishlistBadge.style.display = 'flex';
                            }
                        } else {
                            button.classList.remove('active');
                            icon.className = 'far fa-heart';
                            icon.style.color = '#6b7280';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        button.classList.remove('active');
                        icon.className = 'far fa-heart';
                        icon.style.color = '#6b7280';
                    });
            }
        };

        // Scroll to Top Button
        document.addEventListener('DOMContentLoaded', function() {
            const scrollToTopBtn = document.getElementById('scrollToTopBtn');
            if (scrollToTopBtn) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollToTopBtn.classList.add('show');
                    } else {
                        scrollToTopBtn.classList.remove('show');
                    }
                });

                scrollToTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>
</body>

</html>