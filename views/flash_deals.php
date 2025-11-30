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

// Get real products from database
$all_products = get_all_products_ctr();

// Get real categories and brands from database
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

// Filter products to ONLY show Flash Deals category
$flash_deals_category = null;
foreach ($categories as $cat) {
    if (stripos($cat['cat_name'], 'flash') !== false || stripos($cat['cat_name'], 'deal') !== false) {
        $flash_deals_category = $cat['cat_name'];
        break;
    }
}

// If no "Flash Deals" category found, try common variations
if (!$flash_deals_category) {
    $possible_names = ['Flash Deals', 'flash_deals', 'FlashDeals', 'flash deals', 'Flash Deal'];
    foreach ($possible_names as $name) {
        foreach ($categories as $cat) {
            if (strtolower(trim($cat['cat_name'])) === strtolower(trim($name))) {
                $flash_deals_category = $cat['cat_name'];
                break 2;
            }
        }
    }
}

// Filter products to ONLY show Flash Deals category products
$filtered_products = [];
if ($flash_deals_category) {
    $filtered_products = array_filter($all_products, function ($product) use ($flash_deals_category) {
        return $product['cat_name'] === $flash_deals_category;
    });
} else {
    // If category not found, show all products (fallback)
    $filtered_products = $all_products;
}

// Additional filters based on URL parameters
$category_filter = $_GET['category'] ?? '';
$brand_filter = $_GET['brand'] ?? '';
$search_query = $_GET['search'] ?? '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 50000;
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : '';

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
$category_id = null;
$joint_category_ids = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Flash Deals - Gadget Garage</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        /* Flash Deals Hero Section */
        .flash-deals-hero {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            padding: 60px 20px;
            margin: 20px 0 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .flash-deals-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .flash-hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        .flash-hero-text {
            text-align: center;
        }

        .flash-main-title {
            font-size: 4rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 4px 20px rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .flash-main-title i {
            color: #ffd700;
            animation: flash 2s ease-in-out infinite;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8));
        }

        @keyframes flash {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        .flash-subtitle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            letter-spacing: 1px;
        }

        /* Large Countdown Timer */
        .flash-countdown-large {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 50px 80px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), inset 0 0 30px rgba(255, 255, 255, 0.1);
        }

        .countdown-label-large {
            text-align: center;
            color: #ffffff;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 30px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .countdown-display-large {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
        }

        .countdown-item-large {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 30px 40px;
            min-width: 140px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .countdown-item-large:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        .countdown-number-large {
            font-size: 4.5rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            font-family: 'Inter', sans-serif;
        }

        .countdown-text-large {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .countdown-separator {
            font-size: 3rem;
            font-weight: 700;
            color: #ffd700;
            margin: 0 5px;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
            animation: blink 1s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        @media (max-width: 992px) {
            .flash-main-title {
                font-size: 2.5rem;
            }
            .flash-countdown-large {
                padding: 40px 30px;
            }
            .countdown-display-large {
                gap: 15px;
            }
            .countdown-item-large {
                padding: 20px 25px;
                min-width: 100px;
            }
            .countdown-number-large {
                font-size: 3rem;
            }
            .countdown-separator {
                font-size: 2rem;
                margin: 0 3px;
            }
        }

        @media (max-width: 768px) {
            .flash-deals-hero {
                padding: 40px 15px;
            }
            .flash-main-title {
                font-size: 2rem;
                flex-direction: column;
                gap: 10px;
            }
            .flash-subtitle {
                font-size: 1.1rem;
            }
            .flash-countdown-large {
                padding: 30px 20px;
            }
            .countdown-display-large {
                flex-wrap: wrap;
                gap: 10px;
            }
            .countdown-item-large {
                padding: 15px 20px;
                min-width: 80px;
            }
            .countdown-number-large {
                font-size: 2.5rem;
            }
            .countdown-text-large {
                font-size: 0.7rem;
            }
            .countdown-separator {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="page-background">
    <?php include '../includes/header.php'; ?>

    <!-- Flash Deals Hero Section with Large Timer -->
    <div class="container-fluid">
        <div class="flash-deals-hero">
            <div class="flash-hero-content">
                <div class="flash-hero-text">
                    <h1 class="flash-main-title">
                        <i class="fas fa-bolt"></i>
                        FLASH DEALS
                        <i class="fas fa-bolt"></i>
                    </h1>
                    <p class="flash-subtitle">Unbeatable prices for a limited time only!</p>
                </div>

                <div class="flash-countdown-large">
                    <div class="countdown-label-large">Deals End In</div>
                    <div class="countdown-display-large">
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="days">12</div>
                            <div class="countdown-text-large">Days</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="hours">00</div>
                            <div class="countdown-text-large">Hours</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="minutes">00</div>
                            <div class="countdown-text-large">Minutes</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="countdown-item-large">
                            <div class="countdown-number-large" id="seconds">00</div>
                            <div class="countdown-text-large">Seconds</div>
                        </div>
                    </div>
                </div>
            </div>
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
        // Flash Deals 12-Day Countdown Timer
        function startCountdown() {
            const now = new Date().getTime();
            const endDate = new Date(now + (12 * 24 * 60 * 60 * 1000)).getTime(); // 12 days from now

            const timer = setInterval(function() {
                const now = new Date().getTime();
                const distance = endDate - now;

                if (distance < 0) {
                    clearInterval(timer);
                    document.getElementById("days").innerHTML = "00";
                    document.getElementById("hours").innerHTML = "00";
                    document.getElementById("minutes").innerHTML = "00";
                    document.getElementById("seconds").innerHTML = "00";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("days").innerHTML = String(days).padStart(2, '0');
                document.getElementById("hours").innerHTML = String(hours).padStart(2, '0');
                document.getElementById("minutes").innerHTML = String(minutes).padStart(2, '0');
                document.getElementById("seconds").innerHTML = String(seconds).padStart(2, '0');
            }, 1000);
        }

        // Start countdown on page load
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown();
        });

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
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
