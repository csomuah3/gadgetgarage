<?php
try {
    require_once(__DIR__ . '/settings/core.php');
    require_once(__DIR__ . '/controllers/cart_controller.php');
    require_once(__DIR__ . '/helpers/image_helper.php');

    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Get cart items for both logged-in and guest users
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
    <title>Checkout - Gadget Garage</title>
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

        .checkout-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1f2937;
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
            color: #000000;
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
            background: #000000;
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
            border-color: #000000;
            background: #f8f9ff;
        }

        .payment-option i {
            font-size: 2rem;
            color: #000000;
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
            color: #000000;
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
            color: #000000;
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
            background: #000000;
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
        }

        .payment-modal .modal-body {
            padding: 3rem 2rem;
        }

        .payment-icon {
            font-size: 4rem;
            color: #000000;
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
                    Gadget<span class="garage">Garage</span>
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
                        <?php if ($is_logged_in): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Account'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="login/customer_profile.php">
                                    <i class="fas fa-user"></i> Profile
                                </a></li>
                                <li><a class="dropdown-item" href="my_orders.php">
                                    <i class="fas fa-box"></i> My Orders
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="login/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login/user_login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login/user_register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                        <?php endif; ?>
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

                <!-- Login Status / Guest Checkout -->
                <?php if (!$is_logged_in): ?>
                <div class="checkout-card">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Checkout Options:</strong> You can continue as a guest or
                                <a href="login/user_login.php" class="alert-link">login</a> to your account for a faster checkout experience.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="guestCheckout" checked>
                                <label class="form-check-label fw-bold" for="guestCheckout">
                                    Continue as Guest
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contact Information -->
                <div class="checkout-card">
                    <h4 class="mb-4">
                        <i class="fas fa-user me-2"></i>
                        Contact Information
                        <?php if ($is_logged_in): ?>
                            <small class="text-success ms-2">
                                <i class="fas fa-check-circle"></i> Logged in as <?php echo htmlspecialchars($_SESSION['user_email'] ?? 'User'); ?>
                            </small>
                        <?php endif; ?>
                    </h4>
                    <form id="contactForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name"
                                       value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['customer_name'] ?? '') : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email"
                                       value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>"
                                       required>
                                <?php if ($is_logged_in): ?>
                                    <small class="text-muted">You can update your email address if needed</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="phone"
                                       value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['customer_contact'] ?? '') : ''; ?>"
                                       placeholder="+233 XX XXX XXXX" required>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Shipping Address -->
                <div class="checkout-card">
                    <h4 class="mb-4">
                        <i class="fas fa-shipping-fast me-2"></i>
                        Shipping Address
                    </h4>
                    <form id="shippingForm">
                        <div class="mb-3">
                            <label class="form-label">Street Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="street_address" placeholder="House number and street name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apartment, suite, etc. (optional)</label>
                            <input type="text" class="form-control" name="apartment" placeholder="Apartment, suite, unit, building, floor, etc.">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Region <span class="text-danger">*</span></label>
                                <select class="form-control" name="region" required>
                                    <option value="">Select Region</option>
                                    <option value="Greater Accra">Greater Accra</option>
                                    <option value="Ashanti">Ashanti</option>
                                    <option value="Western">Western</option>
                                    <option value="Central">Central</option>
                                    <option value="Eastern">Eastern</option>
                                    <option value="Volta">Volta</option>
                                    <option value="Northern">Northern</option>
                                    <option value="Upper East">Upper East</option>
                                    <option value="Upper West">Upper West</option>
                                    <option value="Brong Ahafo">Brong Ahafo</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Billing Address -->
                <div class="checkout-card">
                    <h4 class="mb-4">
                        <i class="fas fa-receipt me-2"></i>
                        Billing Address
                    </h4>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sameBillingAddress" checked>
                            <label class="form-check-label" for="sameBillingAddress">
                                Billing address is the same as shipping address
                            </label>
                        </div>
                    </div>
                    <div id="billingAddressForm" style="display: none;">
                        <form id="billingForm">
                            <div class="mb-3">
                                <label class="form-label">Street Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="billing_street_address">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Apartment, suite, etc. (optional)</label>
                                <input type="text" class="form-control" name="billing_apartment">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="billing_city">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Region <span class="text-danger">*</span></label>
                                    <select class="form-control" name="billing_region">
                                        <option value="">Select Region</option>
                                        <option value="Greater Accra">Greater Accra</option>
                                        <option value="Ashanti">Ashanti</option>
                                        <option value="Western">Western</option>
                                        <option value="Central">Central</option>
                                        <option value="Eastern">Eastern</option>
                                        <option value="Volta">Volta</option>
                                        <option value="Northern">Northern</option>
                                        <option value="Upper East">Upper East</option>
                                        <option value="Upper West">Upper West</option>
                                        <option value="Brong Ahafo">Brong Ahafo</option>
                                    </select>
                                </div>
                            </div>
                        </form>
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

    <style>
        /* Footer and Chat Styles */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 80px;
        }
        .footer-logo { font-size: 1.8rem; font-weight: 700; color: #1f2937; margin-bottom: 16px; }
        .footer-logo .garage { background: linear-gradient(135deg, #000000, #333333); color: white; padding: 4px 8px; border-radius: 6px; font-size: 1rem; font-weight: 600; }
        .footer-description { color: #6b7280; font-size: 0.95rem; margin-bottom: 24px; line-height: 1.6; }
        .social-links { display: flex; gap: 12px; }
        .social-link { width: 40px; height: 40px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #6b7280; text-decoration: none; transition: all 0.3s ease; }
        .social-link:hover { background: #000000; color: white; transform: translateY(-2px); }
        .footer-title { font-size: 1.1rem; font-weight: 600; color: #1f2937; margin-bottom: 20px; }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links li a { color: #6b7280; text-decoration: none; font-size: 0.9rem; transition: all 0.3s ease; }
        .footer-links li a:hover { color: #000000; transform: translateX(4px); }
        .footer-divider { border: none; height: 1px; background: linear-gradient(90deg, transparent, #e5e7eb, transparent); margin: 40px 0 20px; }
        .footer-bottom { padding-top: 20px; }
        .copyright { color: #6b7280; font-size: 0.9rem; margin: 0; }
        .payment-methods { display: flex; gap: 8px; justify-content: end; align-items: center; }
        .payment-methods img { height: 25px; border-radius: 4px; opacity: 0.8; transition: opacity 0.3s ease; }
        .payment-methods img:hover { opacity: 1; }
        .live-chat-widget { position: fixed; bottom: 20px; left: 20px; z-index: 1000; }
        .chat-trigger { width: 60px; height: 60px; background: #000000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); transition: all 0.3s ease; }
        .chat-trigger:hover { background: #374151; transform: scale(1.1); }
        .chat-panel { position: absolute; bottom: 80px; left: 0; width: 350px; height: 450px; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); border: 1px solid #e5e7eb; display: none; flex-direction: column; }
        .chat-panel.active { display: flex; }
        .chat-header { padding: 16px 20px; background: #000000; color: white; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .chat-header h4 { margin: 0; font-size: 1.1rem; font-weight: 600; }
        .chat-close { background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; padding: 0; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; }
        .chat-message { margin-bottom: 16px; }
        .chat-message.bot p { background: #f3f4f6; padding: 12px 16px; border-radius: 18px; margin: 0; color: #374151; font-size: 0.9rem; }
        .chat-footer { padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 12px; }
        .chat-input { flex: 1; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 25px; outline: none; font-size: 0.9rem; }
        .chat-input:focus { border-color: #000000; }
        .chat-send { width: 40px; height: 40px; background: #000000; color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.3s ease; }
        .chat-send:hover { background: #374151; }
    </style>

    <script>
        function toggleLiveChat() {
            document.getElementById('chatPanel').classList.toggle('active');
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
                    botMessage.innerHTML = `<p>I can help you complete your order! Any questions about payment or shipping?</p>`;
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
                    if (e.key === 'Enter') sendChatMessage();
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
                    <p>Ready to complete your order? I'm here to help with any checkout questions!</p>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" class="chat-input" placeholder="Need help with checkout?">
                <button class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

</body>
</html>