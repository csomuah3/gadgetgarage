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

// Get categories to find camera category ID
$categories = get_all_categories_ctr();
$camera_category_id = null;

// Find camera category by name (case-insensitive)
foreach ($categories as $cat) {
    $cat_name_lower = strtolower(trim($cat['cat_name']));
    if (
        strpos($cat_name_lower, 'camera') !== false ||
        strpos($cat_name_lower, 'photo') !== false ||
        $cat_name_lower === 'cameras' ||
        $cat_name_lower === 'camera'
    ) {
        $camera_category_id = $cat['cat_id'];
        break;
    }
}

// Get all products first
$all_products = get_all_products_ctr();

// Try to get products by category ID if found
$camera_products = [];
if ($camera_category_id) {
    $products_by_id = get_products_by_category_ctr($camera_category_id);
    if (!empty($products_by_id)) {
        $camera_products = $products_by_id;
    }
}

// If no products found by ID, or ID not found, filter by category name
if (empty($camera_products)) {
    $camera_products = array_filter($all_products, function ($product) {
        $cat_name = isset($product['cat_name']) ? strtolower($product['cat_name']) : '';
        $product_title = isset($product['product_title']) ? strtolower($product['product_title']) : '';

        // Include camera/photo products
        return (strpos($cat_name, 'camera') !== false ||
            strpos($cat_name, 'photo') !== false ||
            strpos($product_title, 'camera') !== false ||
            strpos($product_title, 'dslr') !== false ||
            strpos($product_title, 'mirrorless') !== false);
    });
}

// Ensure array is re-indexed
$camera_products = array_values($camera_products);

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

$filtered_products = $camera_products;

// Apply filters
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

// Template parameters
$is_joint_category = false; // Single category page
$category_id = $camera_category_id;
$joint_category_ids = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cameras - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">
    <link href="../includes/footer.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="page-background">
    <?php include '../includes/header.php'; ?>

    <!-- Page Title -->
    <div class="container-fluid">
        <div class="text-center py-3">
            <h1 style="color: #1f2937; font-weight: 700; margin: 0;">Cameras</h1>
        </div>
    </div>

    <!-- Category Product Layout Template -->
    <?php include '../includes/category_product_layout.php'; ?>

        <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

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