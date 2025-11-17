<?php
try {
    require_once(__DIR__ . '/settings/core.php');
    require_once(__DIR__ . '/controllers/cart_controller.php');
    require_once(__DIR__ . '/helpers/image_helper.php');

    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    $cart_total = get_cart_total_ctr($customer_id, $ip_address) ?: 0;
    $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

    $categories = [];
    $brands = [];

    try {
        require_once(__DIR__ . '/controllers/category_controller.php');
        $categories = get_all_categories_ctr();
    } catch (Exception $e) {
        error_log("Failed to load categories: " . $e->getMessage());
    }

    try {
        require_once(__DIR__ . '/controllers/brand_controller.php');
        $brands = get_all_brands_ctr();
    } catch (Exception $e) {
        error_log("Failed to load brands: " . $e->getMessage());
    }
} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopping Cart - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="includes/header-styles.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ffffff;
            color: #1a202c;
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
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .garage {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .cart-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1f2937;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .cart-item {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }

        .btn-primary {
            background: #000000;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #374151;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .btn-outline-danger {
            border: 2px solid #dc3545;
            color: #dc3545;
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: #000000;
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #374151;
            transform: scale(1.1);
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 5px;
        }

        .cart-summary {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            position: sticky;
            top: 120px;
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-cart-icon {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }

        .navbar-nav .nav-link {
            color: #4a5568 !important;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            color: #000000 !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            padding: 1rem 0;
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f3f4f6;
            color: #000000;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .product-image {
                width: 80px;
                height: 80px;
            }

            .cart-header {
                padding: 2rem 0;
            }

            .cart-item {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
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
    <div class="container-fluid">
        <div class="text-center py-3">
            <h1 style="color: #1f2937; font-weight: 700; margin: 0;">Shopping Cart</h1>
        </div>
    </div>

    <div class="cart-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-shopping-cart me-3"></i>
                        Your Shopping Cart
                    </h1>
                    <p class="mb-0 fs-5">
                        <?php if ($cart_count > 0): ?>
                            You have <?php echo $cart_count; ?> item<?php echo $cart_count > 1 ? 's' : ''; ?> in your cart
                        <?php else: ?>
                            Your cart is currently empty
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="fs-3 fw-bold">
                        GH₵ <?php echo number_format($cart_total, 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="text-muted mb-3">Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="all_product.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Cart Items</h4>
                        <button type="button" class="btn btn-outline-danger" onclick="emptyCart()">
                            <i class="fas fa-trash me-2"></i>
                            Empty Cart
                        </button>
                    </div>

                    <div id="cartItemsContainer">
                        <?php foreach ($cart_items as $item):
                            // Create unique cart item ID combining product ID and condition
                            $condition = $item['condition_type'] ?? 'default';
                            $price = isset($item['final_price']) && $item['final_price'] > 0 ? $item['final_price'] : $item['product_price'];
                            // Clean the ID: remove spaces, dots, and any special characters
                            $clean_condition = preg_replace('/[^a-z0-9]/', '', strtolower($condition));
                            $clean_price = preg_replace('/[^0-9]/', '', $price);
                            $cart_item_id = 'cart_' . $item['p_id'] . '_' . $clean_condition . '_' . $clean_price;
                        ?>
                            <!-- Debug: Cart Item ID is <?php echo $cart_item_id; ?> -->
                            <div class="cart-item" data-product-id="<?php echo $item['p_id']; ?>" data-cart-item-id="<?php echo $cart_item_id; ?>">
                                <div class="row g-0 align-items-center p-3">
                                    <div class="col-auto">
                                        <img src="<?php echo get_product_image_url($item['product_image']); ?>"
                                             alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                             class="product-image">
                                    </div>
                                    <div class="col ms-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h5>
                                                <p class="text-muted mb-2 small"><?php echo htmlspecialchars($item['product_desc'] ?? ''); ?></p>
                                                <?php if (isset($item['condition_type'])): ?>
                                                <div class="condition-badge mb-2">
                                                    <span class="badge bg-secondary">Condition: <?php echo ucfirst($item['condition_type']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="fw-bold text-primary fs-5" id="unit-price-<?php echo $cart_item_id; ?>">
                                                    <?php
                                                    $price = (isset($item['final_price']) && $item['final_price'] > 0)
                                                        ? $item['final_price']
                                                        : $item['product_price'];
                                                    echo 'GH₵ ' . number_format($price, 2);
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-btn" onclick="decrementQuantityByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="quantity-input" value="<?php echo $item['qty']; ?>"
                                                           min="1" max="99" id="<?php echo $cart_item_id; ?>"
                                                           onchange="updateQuantityByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>, this.value)">
                                                    <button type="button" class="quantity-btn" onclick="incrementQuantityByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <div class="fw-bold fs-5 text-success mb-2" id="total-price-<?php echo $cart_item_id; ?>">
                                                    <?php
                                                    $price = (isset($item['final_price']) && $item['final_price'] > 0)
                                                        ? $item['final_price']
                                                        : $item['product_price'];
                                                    echo 'GH₵ ' . number_format($price * $item['qty'], 2);
                                                    ?>
                                                </div>
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                        onclick="removeFromCartByCartId('<?php echo $cart_item_id; ?>', <?php echo $item['p_id']; ?>)">
                                                    <i class="fas fa-times"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4">
                        <a href="all_product.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="mb-4">Order Summary</h4>

                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal (<?php echo $cart_count; ?> items):</span>
                            <span class="fw-bold" id="cartSubtotal">GH₵ <?php echo number_format($cart_total, 2); ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping:</span>
                            <span class="text-success fw-bold">FREE</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Total:</span>
                            <span class="fs-5 fw-bold text-primary" id="cartTotal">GH₵ <?php echo number_format($cart_total, 2); ?></span>
                        </div>

                        <?php if ($is_logged_in): ?>
                            <button type="button" class="btn btn-primary w-100 btn-lg" onclick="proceedToCheckout()">
                                <i class="fas fa-credit-card me-2"></i>
                                Proceed to Checkout
                            </button>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please <a href="login/user_login.php">login</a> to proceed with checkout.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cart.js"></script>

    <style>
        /* Footer Styles */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 80px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo .garage {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .footer-description {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #000000;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #000000;
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
            font-size: 0.9rem;
            margin: 0;
        }

        .payment-methods {
            display: flex;
            gap: 8px;
            justify-content: end;
            align-items: center;
        }

        .payment-methods img {
            height: 25px;
            border-radius: 4px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .payment-methods img:hover {
            opacity: 1;
        }

        /* Live Chat Widget */
        .live-chat-widget {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
        }

        .chat-trigger {
            width: 60px;
            height: 60px;
            background: #000000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .chat-trigger:hover {
            background: #374151;
            transform: scale(1.1);
        }

        .chat-panel {
            position: absolute;
            bottom: 80px;
            left: 0;
            width: 350px;
            height: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            display: none;
            flex-direction: column;
        }

        .chat-panel.active {
            display: flex;
        }

        .chat-header {
            padding: 16px 20px;
            background: #000000;
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
        }

        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .chat-message {
            margin-bottom: 16px;
        }

        .chat-message.bot p {
            background: #f3f4f6;
            padding: 12px 16px;
            border-radius: 18px;
            margin: 0;
            color: #374151;
            font-size: 0.9rem;
        }

        .chat-footer {
            padding: 16px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 25px;
            outline: none;
            font-size: 0.9rem;
        }

        .chat-input:focus {
            border-color: #000000;
        }

        .chat-send {
            width: 40px;
            height: 40px;
            background: #000000;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .chat-send:hover {
            background: #374151;
        }

        @media (max-width: 768px) {
            .chat-panel {
                width: calc(100vw - 40px);
                height: 400px;
            }

            .live-chat-widget {
                bottom: 15px;
                left: 15px;
            }
        }
    </style>

    <script>
        // Live chat functionality
        function toggleLiveChat() {
            const chatPanel = document.getElementById('chatPanel');
            chatPanel.classList.toggle('active');
        }

        function sendChatMessage() {
            const chatInput = document.querySelector('.chat-input');
            const chatBody = document.querySelector('.chat-body');
            const message = chatInput.value.trim();

            if (message) {
                const userMessage = document.createElement('div');
                userMessage.className = 'chat-message user';
                userMessage.innerHTML = `<p style="background: #000000; color: white; padding: 12px 16px; border-radius: 18px; margin: 0; font-size: 0.9rem; text-align: right;">${message}</p>`;
                chatBody.appendChild(userMessage);

                chatInput.value = '';

                setTimeout(() => {
                    const botMessage = document.createElement('div');
                    botMessage.className = 'chat-message bot';
                    botMessage.innerHTML = `<p>I can help you with your cart! Need assistance with checkout or have questions about your items?</p>`;
                    chatBody.appendChild(botMessage);
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 1000);

                chatBody.scrollTop = chatBody.scrollHeight;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.querySelector('.chat-input');
            const chatSend = document.querySelector('.chat-send');

            if (chatInput && chatSend) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendChatMessage();
                    }
                });

                chatSend.addEventListener('click', sendChatMessage);
            }
        });
    </script>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="footer-brand">
                            <h3 class="footer-logo">Gadget<span class="garage">Garage</span></h3>
                            <p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Shop</h5>
                        <ul class="footer-links">
                            <li><a href="all_product.php?category=phones">Smartphones</a></li>
                            <li><a href="all_product.php?category=laptops">Laptops</a></li>
                            <li><a href="all_product.php?category=ipads">Tablets</a></li>
                            <li><a href="all_product.php?category=cameras">Cameras</a></li>
                            <li><a href="all_product.php?category=video">Video Equipment</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Services</h5>
                        <ul class="footer-links">
                            <li><a href="repair_services.php">Device Repair</a></li>
                            <li><a href="#">Tech Support</a></li>
                            <li><a href="#">Data Recovery</a></li>
                            <li><a href="#">Setup Services</a></li>
                            <li><a href="#">Warranty</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Company</h5>
                        <ul class="footer-links">
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Contact</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">Press</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="footer-title">Support</h5>
                        <ul class="footer-links">
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">Shipping Info</a></li>
                            <li><a href="#">Returns</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="payment-methods">
                                <img src="https://via.placeholder.com/40x25/cccccc/666666?text=VISA" alt="Visa">
                                <img src="https://via.placeholder.com/40x25/cccccc/666666?text=MC" alt="Mastercard">
                                <img src="https://via.placeholder.com/40x25/cccccc/666666?text=AMEX" alt="American Express">
                                <img src="https://via.placeholder.com/40x25/cccccc/666666?text=GPAY" alt="Google Pay">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Live Chat Widget -->
    <div class="live-chat-widget" id="liveChatWidget">
        <div class="chat-trigger" onclick="toggleLiveChat()">
            <i class="fas fa-comments"></i>
        </div>
        <div class="chat-panel" id="chatPanel">
            <div class="chat-header">
                <h4>Live Chat</h4>
                <button class="chat-close" onclick="toggleLiveChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chat-body">
                <div class="chat-message bot">
                    <p>Need help with your cart? I'm here to assist with your shopping experience!</p>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" class="chat-input" placeholder="Ask about your cart items...">
                <button class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

</body>
</html>