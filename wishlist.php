<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/cart_controller.php');

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Wishlist - Gadget Garage</title>
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
    </style>
</head>

<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo -->
                <a href="index.php" class="logo">
                    Gadget<span class="garage">Garage</span>
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

            <div class="empty-wishlist">
                <i class="fas fa-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Start adding products to your wishlist to save them for later!</p>
                <a href="index.php" class="shop-btn">Start Shopping</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>