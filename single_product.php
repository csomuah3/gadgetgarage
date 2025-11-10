<?php
require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/product_controller.php');
require_once(__DIR__ . '/helpers/image_helper.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

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
    <title><?php echo htmlspecialchars($product['product_title']); ?> - FlavorHub</title>
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
            background-color: #f8fafc;
            color: #1a202c;
        }

        .main-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            box-shadow: 0 2px 10px rgba(139, 95, 191, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #8b5fbf;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(139, 95, 191, 0.1);
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
            color: #8b5fbf;
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
            color: #8b5fbf;
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
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
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
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
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

        .hero-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
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
            color: #8b5fbf;
            font-weight: 600;
        }

        .breadcrumb-item + .breadcrumb-item::before {
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

        .share-btn.facebook { background: #1877f2; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.whatsapp { background: #25d366; }

        .share-btn:hover {
            transform: scale(1.1);
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
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3">
                    <a href="index.php" class="logo">
                        <i class="fas fa-utensils"></i>
                        <span>FlavorHub</span>
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
                        <a href="#" class="cart-icon position-relative" onclick="showCart()">
                            <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: #8b5fbf;"></i>
                            <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
                                0
                            </span>
                        </a>

                        <?php if ($is_logged_in): ?>
                            <a href="login/logout.php" class="btn btn-outline-danger">Logout</a>
                        <?php else: ?>
                            <a href="login/login.php" class="btn btn-outline-primary">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mt-4">
        <!-- Hero Bar -->
        <div class="hero-bar">
            <div class="d-flex align-items-center justify-content-between">
                <a href="all_product.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Products
                </a>
                <div class="hero-title">
                    <h2 class="mb-0 text-muted" style="font-size: 1.2rem; font-weight: 600;">Product Details</h2>
                </div>
                <div class="hero-actions">
                    <button class="btn btn-outline-success" onclick="shareProduct('whatsapp')">
                        <i class="fab fa-whatsapp"></i>
                        Share
                    </button>
                </div>
            </div>
        </div>

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
                    <div class="product-details">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="product-id">ID: <?php echo $product['product_id']; ?></span>
                            <div class="share-buttons">
                                <button class="share-btn facebook" onclick="shareProduct('facebook')" title="Share on Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </button>
                                <button class="share-btn twitter" onclick="shareProduct('twitter')" title="Share on Twitter">
                                    <i class="fab fa-twitter"></i>
                                </button>
                                <button class="share-btn whatsapp" onclick="shareProduct('whatsapp')" title="Share on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            </div>
                        </div>

                        <h1 class="product-title"><?php echo htmlspecialchars($product['product_title']); ?></h1>
                        <div class="product-price">$<?php echo number_format($product['product_price'], 2); ?></div>

                        <div class="product-meta">
                            <div class="meta-item">
                                <i class="fas fa-tag"></i>
                                <span><?php echo htmlspecialchars($product['cat_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-store"></i>
                                <span><?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-box"></i>
                                <span>In Stock</span>
                            </div>
                        </div>

                        <?php if (!empty($product['product_desc'])): ?>
                            <div class="product-description">
                                <h5 style="color: #8b5fbf; margin-bottom: 15px;">Description</h5>
                                <p><?php echo nl2br(htmlspecialchars($product['product_desc'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($product['product_keywords'])): ?>
                            <div class="product-keywords">
                                <h6 style="color: #8b5fbf; margin-bottom: 10px;">Tags</h6>
                                <?php
                                $keywords = explode(',', $product['product_keywords']);
                                foreach ($keywords as $keyword):
                                    $keyword = trim($keyword);
                                    if (!empty($keyword)):
                                ?>
                                    <span class="keyword-tag"><?php echo htmlspecialchars($keyword); ?></span>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                            <button class="btn btn-outline-secondary" onclick="addToWishlist(<?php echo $product['product_id']; ?>)">
                                <i class="fas fa-heart"></i>
                                Wishlist
                            </button>
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
    <script>
        function addToCart(productId) {
            // Add visual feedback
            const btn = event.target.closest('.add-to-cart-btn');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-check"></i> Added!';
            btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = 'linear-gradient(135deg, #8b5fbf, #f093fb)';
            }, 1500);

            // Here you would normally send AJAX request to add to cart
            console.log('Add to cart functionality - Product ID: ' + productId);

            // Update cart count
            updateCartCount();
        }

        function showCart() {
            alert('Cart functionality will be implemented soon!\nThis will show your cart items.');
        }

        function updateCartCount() {
            // This would normally get the actual cart count from storage/database
            const cartCountElement = document.getElementById('cartCount');
            let currentCount = parseInt(cartCountElement.textContent);
            cartCountElement.textContent = currentCount + 1;
        }

        function addToWishlist(productId) {
            alert('Add to wishlist functionality - Product ID: ' + productId);
        }

        function shareProduct(platform) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);

            let shareUrl = '';

            switch(platform) {
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
            const productId = img.getAttribute('data-product-id');
            const productTitle = img.getAttribute('data-product-title');

            fetch(`actions/upload_product_image_action.php?action=get_image_url&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.url) {
                        img.src = data.url;
                    } else {
                        // Use placeholder
                        img.src = generatePlaceholderUrl(productTitle, '600x400');
                    }
                })
                .catch(error => {
                    console.log('Image load error - using placeholder');
                    img.src = generatePlaceholderUrl(productTitle, '600x400');
                });
        }

        function generatePlaceholderUrl(text, size = '600x400') {
            const encodedText = encodeURIComponent(text);
            return `https://via.placeholder.com/${size}/8b5fbf/ffffff?text=${encodedText}`;
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Load product image
            loadProductImage();

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
    </script>
</body>
</html>