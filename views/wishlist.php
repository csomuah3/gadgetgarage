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
    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo -->
                <a href="index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                         alt="Gadget Garage"
                         style="height: 40px; width: auto; object-fit: contain;">
                </a>

                <!-- Navigation -->
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
                    <?php if ($is_logged_in): ?>
                        <a href="cart.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="badge bg-success"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
    </script>
</body>

</html>