<?php
require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/product_controller.php');
require_once(__DIR__ . '/controllers/cart_controller.php');
require_once(__DIR__ . '/helpers/image_helper.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: all_product.php');
    exit();
}

// Get product details
$product = view_single_product_ctr($product_id);

if (!$product) {
    header('Location: all_product.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($product['product_title']); ?> - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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

        .product-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin: 30px 0;
        }

        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background: #f8fafc;
        }

        .product-details {
            padding: 40px;
        }

        .product-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 20px;
        }

        .product-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-weight: 500;
        }

        .meta-item i {
            color: #000000;
            font-size: 1.1rem;
        }

        .product-description {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #4a5568;
            margin-bottom: 25px;
        }

        .product-keywords {
            margin-bottom: 30px;
        }

        .keyword-tag {
            display: inline-block;
            background: #000000;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .add-to-cart-btn {
            background: #000000;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-to-cart-btn:hover {
            background: #374151;
            transform: scale(1.05);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #e2e8f0;
            color: #4a5568;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-btn:hover {
            background: #cbd5e0;
            color: #2d3748;
        }

        /* Main Navigation */
        .main-nav {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 0;
            position: sticky;
            top: 85px;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-item {
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            padding: 12px 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item:hover {
            color: #008060;
        }

        .shop-categories-btn {
            position: relative;
        }

        .categories-button {
            background: #4f63d2;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .categories-button:hover {
            background: #3d4fd1;
        }

        .nav-item.flash-deal {
            color: #ef4444;
            font-weight: 600;
        }

        .nav-item.flash-deal:hover {
            color: #dc2626;
        }


        .side-card {
            display: grid;
            grid-template-columns: 1fr 80px;
            gap: 16px;
            padding: 24px;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .side-card.yellow {
            background: #fbbf24;
            color: #1f2937;
        }

        .side-card.green {
            background: #22c55e;
            color: white;
        }

        .side-copy {
            display: grid;
            align-content: center;
            gap: 8px;
        }

        .side-title {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .side-price {
            font-size: 12px;
            margin: 0;
            opacity: 0.9;
        }

        .side-price .price {
            font-weight: 700;
            font-size: 14px;
        }

        .side-media {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .side-banners {
                grid-template-rows: none;
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .main-banner {
                grid-template-columns: 1fr;
                padding: 28px;
            }

            .banner-media {
                order: -1;
            }

            .side-banners {
                grid-template-columns: 1fr;
            }
        }

        .hero-actions .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            border-width: 2px;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-item {
            color: #64748b;
        }

        .breadcrumb-item.active {
            color: #000000;
            font-weight: 600;
        }

        .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
            color: #cbd5e0;
        }

        .product-id {
            background: #f8fafc;
            color: #64748b;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .share-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .share-btn.facebook {
            background: #1877f2;
        }

        .share-btn.twitter {
            background: #1da1f2;
        }

        .share-btn.whatsapp {
            background: #25d366;
        }

        .share-btn:hover {
            transform: scale(1.1);
        }

        /* Condition Selection Styles */
        .condition-selection {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }

        .condition-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .condition-option {
            margin-bottom: 0 !important;
        }

        .condition-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 25px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            width: 100%;
        }

        .condition-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .condition-label span {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 4px;
            color: #1a202c;
        }

        .condition-label small {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.2;
        }

        .condition-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a202c;
        }

        .condition-discount {
            font-size: 0.9rem;
            color: #dc2626;
            margin-top: 4px;
        }

        .excellent-label i {
            color: #22c55e;
        }

        .good-label i {
            color: #3b82f6;
        }

        .fair-label i {
            color: #f59e0b;
        }

        .condition-option input[type="radio"]:checked + .condition-label {
            border-color: #000000;
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .condition-option input[type="radio"] {
            display: none;
        }

        /* Price Section Styles */
        .price-section {
            margin-bottom: 25px;
        }

        .price-breakdown {
            margin-top: 10px;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 8px;
            border: 1px solid #bae6fd;
        }

        .original-price {
            color: #64748b;
            text-decoration: line-through;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .discount-amount {
            color: #dc2626;
            font-weight: 600;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .product-details {
                padding: 20px;
            }

            .product-title {
                font-size: 1.8rem;
            }

            .product-price {
                font-size: 2rem;
            }

            .product-meta {
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .add-to-cart-btn {
                justify-content: center;
            }

            .condition-options {
                flex-direction: column;
            }

            .condition-label {
                min-width: auto;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3">
                    <a href="index.php" class="logo">
                        Gadget<span class="garage">Garage</span>
                    </a>
                </div>
                <div class="col-lg-6 text-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="all_product.php">Products</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['product_title']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-3 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <!-- Cart Icon -->
                        <a href="cart.php" class="cart-icon position-relative">
                            <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: #008060;"></i>
                            <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartBadge" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>">
                                <?php echo $cart_count; ?>
                            </span>
                        </a>

                        <?php if ($is_logged_in): ?>
                            <a href="my_orders.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-box"></i> My Orders
                            </a>
                            <a href="login/logout.php" class="btn btn-outline-danger">Logout</a>
                        <?php else: ?>
                            <a href="login/login.php" class="btn btn-outline-primary">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="container">
            <div class="nav-menu">
                <!-- Blue Shop by Categories Button -->
                <div class="shop-categories-btn">
                    <button class="categories-button">
                        <i class="fas fa-bars"></i>
                        SHOP BY CATEGORIES
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <a href="index.php" class="nav-item">Home</a>
                <a href="all_product.php" class="nav-item">All Products</a>
                <a href="all_product.php?category=phones" class="nav-item">Smartphones</a>
                <a href="all_product.php?category=laptops" class="nav-item">Laptops</a>
                <a href="all_product.php?category=ipads" class="nav-item">Tablets</a>
                <a href="all_product.php?category=cameras" class="nav-item">Cameras</a>
                <a href="#" class="nav-item flash-deal">⚡ FLASH DEAL</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4" id="product-details">

        <div class="product-container">
            <div class="row g-0">
                <div class="col-lg-6">
                    <img src=""
                        alt="<?php echo htmlspecialchars($product['product_title']); ?>"
                        class="product-image"
                        data-product-id="<?php echo $product['product_id']; ?>"
                        data-product-image="<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>"
                        data-product-title="<?php echo htmlspecialchars($product['product_title']); ?>">
                </div>
                <div class="col-lg-6">
                    <div class="product-details" style="padding: 40px; background: #4f46e5; color: white; height: 100%;">
                        <!-- Special Offer Header -->
                        <div style="margin-bottom: 20px;">
                            <span style="background: white; color: #4f46e5; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">SPECIAL OFFER</span>
                        </div>

                        <!-- Product Title -->
                        <h1 style="color: white; font-size: 2.2rem; font-weight: 800; margin-bottom: 15px; line-height: 1.2;"><?php echo htmlspecialchars($product['product_title']); ?></h1>

                        <!-- Product Description -->
                        <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; margin-bottom: 25px; line-height: 1.6;">
                            <?php
                            $description = $product['product_desc'] ?? 'The ultimate professional device with advanced features. Perfect for intensive workflows and high-performance tasks.';
                            echo htmlspecialchars(strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description);
                            ?>
                        </p>

                        <!-- Key Features -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: white; margin-bottom: 15px; font-weight: 600;">Key Features</h5>
                            <?php
                            // Generate features based on product category and brand
                            $category = $product['cat_name'] ?? 'Electronic';
                            $brand = $product['brand_name'] ?? 'Premium';
                            $features = [
                                '• ' . ucfirst($brand) . ' brand with premium quality',
                                '• ' . ucfirst($category) . ' device specifications',
                                '• High-performance components',
                                '• Professional-grade reliability',
                                '• Advanced connectivity options'
                            ];
                            foreach($features as $feature): ?>
                                <div style="color: rgba(255,255,255,0.95); margin-bottom: 8px; display: flex; align-items: center;">
                                    <i class="fas fa-check" style="color: #10b981; margin-right: 12px; font-size: 0.9rem;"></i>
                                    <?php echo $feature; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Select Condition -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: white; margin-bottom: 20px; font-weight: 600;">Select Condition</h5>

                            <!-- Excellent Condition -->
                            <div style="background: rgba(255,255,255,0.15); border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" onclick="selectCondition('excellent', <?php echo $product['product_price']; ?>)" id="excellent-option">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">Excellent Condition</div>
                                        <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Like new, no visible wear</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.1rem; font-weight: 700; color: white;">GH₵<?php echo number_format($product['product_price'], 0); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Good Condition -->
                            <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" onclick="selectCondition('good', <?php echo $product['product_price'] - 100; ?>)" id="good-option">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">Good Condition</div>
                                        <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Minor scratches, fully functional</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.1rem; font-weight: 700; color: white;">GH₵<?php echo number_format($product['product_price'] - 100, 0); ?></div>
                                        <div style="color: #10b981; font-size: 0.85rem;">-GH₵100</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fair Condition -->
                            <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" onclick="selectCondition('fair', <?php echo $product['product_price'] - 200; ?>)" id="fair-option">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">Fair Condition</div>
                                        <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Visible wear, works perfectly</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.1rem; font-weight: 700; color: white;">GH₵<?php echo number_format($product['product_price'] - 200, 0); ?></div>
                                        <div style="color: #10b981; font-size: 0.85rem;">-GH₵200</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Display -->
                        <div style="margin-bottom: 30px;">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 8px;">
                                <span id="currentPrice" style="color: white; font-size: 2.5rem; font-weight: 800;">GH₵<?php echo number_format($product['product_price'], 0); ?></span>
                                <span id="originalPrice" style="color: rgba(255,255,255,0.6); font-size: 1.5rem; text-decoration: line-through; display: none;">GH₵<?php echo number_format($product['product_price'] * 1.13, 0); ?></span>
                                <span id="discountBadge" style="background: #ef4444; color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; display: none;">13% off</span>
                            </div>
                            <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Limited time offer - While supplies last</div>
                        </div>

                        <!-- Add to Cart Button -->
                        <button onclick="addToCartWithCondition(<?php echo $product['product_id']; ?>)" id="addToCartBtn"
                                style="width: 100%; background: white; color: #4f46e5; border: none; padding: 18px; border-radius: 12px; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 20px;">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart - GH₵<span id="cartButtonPrice"><?php echo number_format($product['product_price'], 0); ?></span>
                        </button>

                        <div style="color: rgba(255,255,255,0.7); font-size: 0.85rem; text-align: center;">
                            <i class="fas fa-shield-alt" style="margin-right: 5px;"></i>
                            Secure checkout • Free delivery • 30-day return policy
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="all_product.php" class="btn btn-outline-primary me-3">
                    <i class="fas fa-grid-3x3"></i> View All Products
                </a>
                <a href="product_search_result.php?query=<?php echo urlencode($product['cat_name']); ?>" class="btn btn-outline-success">
                    <i class="fas fa-search"></i> Similar Products
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cart.js"></script>
    <script>
        function addToCart(productId) {
            // Get selected condition
            const selectedCondition = document.querySelector('input[name="condition"]:checked').value;
            const priceData = calculatePrice(selectedCondition);

            // Show loading state
            const btn = event.target.closest('.add-to-cart-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('condition', selectedCondition);
            formData.append('final_price', priceData.finalPrice);

            fetch('actions/add_to_cart_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-success');
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-primary');
                            btn.disabled = false;
                        }, 2000);
                        updateCartBadge(data.cart_count);
                        showNotification(`${selectedCondition.charAt(0).toUpperCase() + selectedCondition.slice(1)} condition product added to cart!`, 'success');
                    } else {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        showNotification(data.message || 'Failed to add product to cart', 'error');
                    }
                })
                .catch(error => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    showNotification('An error occurred. Please try again.', 'error');
                });
        }

        function showCart() {
            window.location.href = 'cart.php';
        }

        // Update cart badge (shared with cart.js)
        function updateCartBadge(count) {
            const cartBadge = document.getElementById('cartCount');
            if (cartBadge) {
                if (count > 0) {
                    cartBadge.textContent = count;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }

        // Show notification (simple toast)
        function showNotification(message, type = 'info') {
            const existing = document.querySelector('.notification-toast');
            if (existing) existing.remove();
            const notification = document.createElement('div');
            notification.className = `notification-toast alert alert-${type} position-fixed`;
            notification.style.cssText = `
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 2000);
        }

        function shareProduct(platform) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);

            let shareUrl = '';

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${title}%20${url}`;
                    break;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }

        // Image Loading System
        function loadProductImage() {
            const img = document.querySelector('.product-image');
            const heroImg = document.querySelector('.product-hero-image');
            const productId = img.getAttribute('data-product-id');
            const productTitle = img.getAttribute('data-product-title');

            fetch(`actions/upload_product_image_action.php?action=get_image_url&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.url) {
                        img.src = data.url;
                        if (heroImg) heroImg.src = data.url;
                    } else {
                        // Use placeholder
                        const placeholderUrl = generatePlaceholderUrl(productTitle, '600x400');
                        img.src = placeholderUrl;
                        if (heroImg) heroImg.src = placeholderUrl;
                    }
                })
                .catch(error => {
                    console.log('Image load error - using placeholder');
                    const placeholderUrl = generatePlaceholderUrl(productTitle, '600x400');
                    img.src = placeholderUrl;
                    if (heroImg) heroImg.src = placeholderUrl;
                });
        }

        function generatePlaceholderUrl(text, size = '600x400') {
            const encodedText = encodeURIComponent(text);
            return `https://via.placeholder.com/${size}/8b5fbf/ffffff?text=${encodedText}`;
        }

        // Condition-based pricing configuration
        const categoryPricing = {
            'Tablets': { // iPads
                'excellent': 0,
                'good': 300,
                'fair': 400
            },
            'Laptops': {
                'excellent': 0,
                'good': 500,
                'fair': 700
            },
            'Desktops': {
                'excellent': 0,
                'good': 700,
                'fair': 1000
            },
            'Cameras': { // Camera and Camera Equipment
                'excellent': 0,
                'good': 500,
                'fair': 750
            },
            'Camera Equipment': {
                'excellent': 0,
                'good': 500,
                'fair': 750
            },
            'default': { // Default for any other category
                'excellent': 0,
                'good': 300,
                'fair': 400
            }
        };

        // Get product data
        const productCategory = '<?php echo addslashes($product['cat_name']); ?>';
        const originalPrice = <?php echo $product['product_price']; ?>;

        // Price calculation function
        function calculatePrice(condition) {
            const categoryKey = categoryPricing[productCategory] ? productCategory : 'default';
            const discount = categoryPricing[categoryKey][condition];
            const finalPrice = originalPrice - discount;

            return {
                finalPrice: Math.max(finalPrice, 0), // Ensure price doesn't go negative
                discount: discount
            };
        }

        // Update price display
        function updatePriceDisplay(condition) {
            const priceData = calculatePrice(condition);
            const displayPrice = document.getElementById('displayPrice');
            const priceBreakdown = document.getElementById('priceBreakdown');
            const originalPriceSpan = document.getElementById('originalPrice');
            const discountAmount = document.getElementById('discountAmount');

            displayPrice.textContent = `GHS ${priceData.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            if (priceData.discount > 0) {
                priceBreakdown.style.display = 'block';
                originalPriceSpan.textContent = `GHS ${originalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                discountAmount.textContent = `Discount: -GHS ${priceData.discount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            } else {
                priceBreakdown.style.display = 'none';
            }
        }

        // Add event listeners to condition radio buttons
        function initializeConditionSelection() {
            const conditionInputs = document.querySelectorAll('input[name="condition"]');
            conditionInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.checked) {
                        updatePriceDisplay(this.value);
                    }
                });
            });

            // Initialize with default selection
            const defaultCondition = document.querySelector('input[name="condition"]:checked').value;
            updatePriceDisplay(defaultCondition);
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Load product image
            loadProductImage();

            // Initialize condition-based pricing
            initializeConditionSelection();

            // Animate product details on load
            const productDetails = document.querySelector('.product-details');
            productDetails.style.opacity = '0';
            productDetails.style.transform = 'translateY(20px)';

            setTimeout(() => {
                productDetails.style.transition = 'all 0.6s ease';
                productDetails.style.opacity = '1';
                productDetails.style.transform = 'translateY(0)';
            }, 200);
        });

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
                    botMessage.innerHTML = `<p>Thank you! Let me help you with this product. Our team will assist you shortly.</p>`;
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
                    <p>Hi! Interested in this product? I'd be happy to help you with any questions!</p>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" class="chat-input" placeholder="Ask about this product...">
                <button class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

</body>

</html>