<?php
try {
    require_once(__DIR__ . '/settings/core.php');
    require_once(__DIR__ . '/controllers/cart_controller.php');
    require_once(__DIR__ . '/helpers/image_helper.php');

    $is_logged_in = check_login();

    if (!$is_logged_in) {
        header("Location: login/user_login.php");
        exit;
    }

    $customer_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    $cart_total = get_cart_total_ctr($customer_id, $ip_address);
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);

    if (empty($cart_items)) {
        header("Location: cart.php");
        exit;
    }

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
    <title>Checkout - FlavorHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        .logo .co {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .checkout-header {
            background: linear-gradient(135deg, #8b5fbf 0%, #f093fb 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .checkout-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
        }

        .step.active {
            color: white;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: 600;
        }

        .step.active .step-number {
            background: white;
            color: #8b5fbf;
        }

        .step-divider {
            width: 60px;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            margin: 0 20px;
        }

        .checkout-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .order-item {
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem 0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .product-image-small {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border: none;
            border-radius: 25px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #7c4dff, #e91e63);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            border-radius: 25px;
            padding: 15px 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .payment-option {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .payment-option:hover,
        .payment-option.selected {
            border-color: #8b5fbf;
            background: #f8f9ff;
        }

        .payment-option i {
            font-size: 2rem;
            color: #8b5fbf;
            margin-bottom: 0.5rem;
        }

        .order-summary {
            background: #f8f9ff;
            border-radius: 15px;
            padding: 2rem;
            position: sticky;
            top: 120px;
        }

        .summary-row {
            display: flex;
            justify-content: between;
            margin-bottom: 0.5rem;
        }

        .summary-row.total {
            border-top: 2px solid #e2e8f0;
            padding-top: 1rem;
            font-weight: 700;
            font-size: 1.2rem;
            color: #8b5fbf;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #059669;
            background: #d1fae5;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .navbar-nav .nav-link {
            color: #4a5568 !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #8b5fbf !important;
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
            background: #f8f9ff;
            color: #8b5fbf;
        }

        /* Payment Modal Styles */
        .payment-modal {
            backdrop-filter: blur(10px);
        }

        .payment-modal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .payment-modal .modal-header {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
        }

        .payment-modal .modal-body {
            padding: 3rem 2rem;
        }

        .payment-icon {
            font-size: 4rem;
            color: #8b5fbf;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .checkout-steps {
                display: none;
            }

            .checkout-header {
                padding: 2rem 0;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .step-divider {
                display: none;
            }
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a class="logo navbar-brand" href="index.php">
                    Flavor<span class="co">Hub</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="all_product.php">All Products</a>
                        </li>
                    </ul>

                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Cart (<?php echo $cart_count; ?>)
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> Account
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="login/customer_profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="login/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <div class="checkout-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="checkout-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <span>Cart</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step active">
                            <div class="step-number">2</div>
                            <span>Checkout</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <span>Confirmation</span>
                        </div>
                    </div>
                    <h1 class="text-center mb-2">
                        <i class="fas fa-credit-card me-3"></i>
                        Secure Checkout
                    </h1>
                    <p class="text-center mb-0 fs-5 opacity-90">
                        Review your order and complete your purchase
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="checkout-card">
                    <h4 class="mb-4">
                        <i class="fas fa-list-check me-2"></i>
                        Order Review
                    </h4>

                    <div id="orderItems">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <img src="<?php echo get_product_image_url($item['product_image']); ?>"
                                             alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                             class="product-image-small">
                                    </div>
                                    <div class="col">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h6>
                                        <small class="text-muted">Quantity: <?php echo $item['qty']; ?></small>
                                    </div>
                                    <div class="col-auto">
                                        <div class="fw-bold text-primary">
                                            GHS <?php echo number_format($item['product_price'] * $item['qty'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="checkout-card">
                    <h4 class="mb-4">
                        <i class="fas fa-credit-card me-2"></i>
                        Payment Method
                    </h4>

                    <div class="payment-methods">
                        <div class="payment-option selected" data-method="mobile-money">
                            <i class="fas fa-mobile-alt"></i>
                            <div class="fw-bold">Mobile Money</div>
                            <small class="text-muted">MTN MoMo, Vodafone Cash, AirtelTigo Money</small>
                        </div>

                        <div class="payment-option" data-method="credit-card">
                            <i class="fas fa-credit-card"></i>
                            <div class="fw-bold">Credit/Debit Card</div>
                            <small class="text-muted">Visa, Mastercard</small>
                        </div>

                        <div class="payment-option" data-method="paystack">
                            <i class="fas fa-wallet"></i>
                            <div class="fw-bold">Paystack</div>
                            <small class="text-muted">Secure online payment</small>
                        </div>

                        <div class="payment-option" data-method="bank-transfer">
                            <i class="fas fa-university"></i>
                            <div class="fw-bold">Bank Transfer</div>
                            <small class="text-muted">Direct bank transfer</small>
                        </div>
                    </div>

                    <div class="secure-badge">
                        <i class="fas fa-lock"></i>
                        <span>Your payment information is secure and encrypted</span>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <a href="cart.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Cart
                    </a>
                    <button type="button" class="btn btn-primary flex-fill" id="simulatePaymentBtn">
                        <i class="fas fa-lock me-2"></i>
                        Complete Order - GHS <?php echo number_format($cart_total, 2); ?>
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="order-summary">
                    <h4 class="mb-4">Order Summary</h4>

                    <div class="summary-row">
                        <span>Subtotal (<?php echo $cart_count; ?> items):</span>
                        <span class="ms-auto">GHS <?php echo number_format($cart_total, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span class="ms-auto text-success">FREE</span>
                    </div>

                    <div class="summary-row">
                        <span>Tax:</span>
                        <span class="ms-auto">$0.00</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total:</span>
                        <span class="ms-auto">GHS <?php echo number_format($cart_total, 2); ?></span>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center gap-2 text-muted small">
                            <i class="fas fa-shield-alt"></i>
                            <span>30-day money-back guarantee</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                            <i class="fas fa-shipping-fast"></i>
                            <span>Free shipping on all orders</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                            <i class="fas fa-headset"></i>
                            <span>24/7 customer support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Simulation Modal -->
    <div class="modal fade payment-modal" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-center border-0">
                    <div class="w-100">
                        <i class="fas fa-mobile-alt payment-icon"></i>
                        <h4 class="mb-0">Simulate Payment</h4>
                        <p class="mb-0 opacity-90">This is a simulation - no real payment will be processed</p>
                    </div>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <div class="fs-2 fw-bold text-primary mb-2">
                            GHS <?php echo number_format($cart_total, 2); ?>
                        </div>
                        <p class="text-muted">
                            Choose your preferred payment method and proceed with this simulated payment.
                        </p>
                    </div>

                    <div class="d-grid gap-3">
                        <button type="button" class="btn btn-primary btn-lg" id="confirmPaymentBtn">
                            <i class="fas fa-check me-2"></i>
                            Complete Payment
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade payment-modal" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-center border-0">
                    <div class="w-100">
                        <i class="fas fa-check-circle payment-icon text-success"></i>
                        <h4 class="mb-0 text-success">Payment Successful!</h4>
                        <p class="mb-0 text-muted">Your order has been processed</p>
                    </div>
                </div>
                <div class="modal-body text-center">
                    <div id="orderSuccessDetails">
                        <!-- Order details will be populated here -->
                    </div>

                    <div class="d-grid gap-3">
                        <button type="button" class="btn btn-primary btn-lg" onclick="window.location.href='index.php'">
                            <i class="fas fa-home me-2"></i>
                            Continue Shopping
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/checkout.js"></script>
</body>
</html>