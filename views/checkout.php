<?php
try {
	require_once(__DIR__ . '/../settings/core.php');
	require_once(__DIR__ . '/../controllers/cart_controller.php');
	require_once(__DIR__ . '/../helpers/image_helper.php');

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
		require_once(__DIR__ . '/../controllers/category_controller.php');
		$categories = get_all_categories_ctr();
	} catch (Exception $e) {
		error_log("Failed to load categories: " . $e->getMessage());
	}

	try {
		require_once(__DIR__ . '/../controllers/brand_controller.php');
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
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<link href="../includes/chatbot-styles.css" rel="stylesheet">
	<link href="../css/dark-mode.css" rel="stylesheet">
	<link href="../includes/header.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<style>
		/* Import Google Fonts */
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

		/* Reset and Base Styles */
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: "Times New Roman", Times, serif;
			background-color: #ffffff;
			color: #1a1a1a;
			overflow-x: hidden;
		}

		body::after {
			content: '';
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: url('http://169.239.251.102:442/~chelsea.somuah/uploads/ChatGPTImageNov19202511_50_42PM.png');
			background-size: cover;
			background-position: center;
			background-attachment: fixed;
			opacity: 0.45;
			z-index: -1;
			pointer-events: none;
		}



		/* Page Title */
		.page-title {
			text-align: center;
			padding: 20px 0;
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			margin: 0;
		}

		.checkout-header {
			background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
			color: #1f2937;
			padding: 1.5rem 0;
			margin-bottom: 1rem;
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
			padding: 1.5rem;
			margin-bottom: 1rem;
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
			box-shadow: 0 8px 25px rgba(0, 128, 96, 0.3);
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
			position: relative;
			z-index: 1;
			user-select: none;
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


		/* Dark Mode Promotional Banner Styles */
		@media (prefers-color-scheme: dark) {
			
		}

		/* Footer Styles */
		.main-footer {
			background: #ffffff;
			border-top: 1px solid #e5e7eb;
			padding: 60px 0 20px;
			margin-top: 0;
		}

		.footer-brand {
			margin-bottom: 30px;
		}

		.footer-logo {
			font-size: 1.8rem;
			font-weight: 700;
			color: #1f2937;
			margin-bottom: 16px;
		}

		.footer-logo img {
			height: 50px !important;
			width: auto !important;
			object-fit: contain !important;
		}

		.footer-logo .garage {
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
			color: white;
			padding: 4px 8px;
			border-radius: 6px;
			font-size: 1rem;
			font-weight: 600;
		}

		.footer-description {
			color: #6b7280;
			font-size: 1.1rem;
			margin-bottom: 24px;
			line-height: 1.7;
		}

		.social-links {
			display: flex;
			gap: 12px;
		}

		.social-link {
			width: 48px;
			height: 48px;
			background: #f3f4f6;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #6b7280;
			text-decoration: none;
			transition: all 0.3s ease;
			font-size: 1.2rem;
		}

		.social-link:hover {
			background: #2563EB;
			color: white;
			transform: translateY(-2px);
		}

		.footer-title {
			font-size: 1.3rem;
			font-weight: 600;
			color: #1f2937;
			margin-bottom: 24px;
		}

		.footer-links {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.footer-links li {
			margin-bottom: 14px;
		}

		.footer-links li a {
			color: #6b7280;
			text-decoration: none;
			font-size: 1rem;
			transition: all 0.3s ease;
		}

		.footer-links li a:hover {
			color: #2563EB;
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
			font-size: 1rem;
			margin: 0;
		}

		/* Newsletter Signup Section */
		.newsletter-signup-section {
			background: transparent;
			padding: 0;
			text-align: left;
			max-width: 100%;
			height: fit-content;
		}

		.newsletter-title {
			color: #1f2937;
			font-size: 1.3rem;
			font-weight: 600;
			margin-bottom: 24px;
		}

		.newsletter-form {
			display: flex;
			width: 100%;
			margin: 0 0 15px 0;
			gap: 0;
			border-radius: 50px;
			overflow: hidden;
			background: #e5e7eb;
		}

		.newsletter-input {
			flex: 1;
			padding: 14px 20px;
			border: none;
			outline: none;
			font-size: 1rem;
			color: #1a1a1a;
			background: #e5e7eb;
		}

		.newsletter-input::placeholder {
			color: #6b7280;
		}

		.newsletter-submit-btn {
			width: 45px;
			height: 45px;
			min-width: 45px;
			border: none;
			background: #9ca3af;
			color: #ffffff;
			border-radius: 50%;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.3s ease;
			font-size: 1.2rem;
			padding: 0;
		}

		.newsletter-submit-btn:hover {
			background: #6b7280;
			transform: scale(1.05);
		}

		.newsletter-disclaimer {
			color: #6b7280;
			font-size: 0.85rem;
			line-height: 1.6;
			margin: 8px 0 0 0;
			text-align: left;
		}

		.newsletter-disclaimer a {
			color: #2563EB;
			text-decoration: underline;
			transition: color 0.3s ease;
		}

		.newsletter-disclaimer a:hover {
			color: #1d4ed8;
		}

		@media (max-width: 991px) {
			.newsletter-signup-section {
				margin-top: 20px;
			}
		}
	</style>
</head>

<body>
	<?php include '../includes/header.php'; ?>

	<!-- Floating Bubbles Background -->
	<div class="floating-bubbles" id="floatingBubbles"></div>

	<!-- Page Title -->
	<h1 class="page-title">Checkout</h1>

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

	<div class="container py-2">
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
											class="product-image-small"
											onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4='; this.onerror=null;">
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
						<div class="payment-option" data-method="paystack-mobile">
							<i class="fas fa-mobile-alt"></i>
							<div class="fw-bold">Mobile Money</div>
							<small class="text-muted">MTN MoMo, Vodafone Cash, AirtelTigo Money via PayStack</small>
						</div>

						<div class="payment-option" data-method="paystack-card">
							<i class="fas fa-credit-card"></i>
							<div class="fw-bold">Credit/Debit Card</div>
							<small class="text-muted">Visa, Mastercard via PayStack</small>
						</div>

						<div class="payment-option" data-method="paystack">
							<i class="fas fa-wallet"></i>
							<div class="fw-bold">PayStack</div>
							<small class="text-muted">Secure online payment gateway</small>
						</div>

						<div class="payment-option" data-method="paystack-bank">
							<i class="fas fa-university"></i>
							<div class="fw-bold">Bank Transfer</div>
							<small class="text-muted">Direct bank transfer via PayStack</small>
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
						Complete Order - GH₵ <?php echo number_format($cart_total, 2); ?>
					</button>
				</div>
			</div>

			<div class="col-lg-4">
				<div class="order-summary">
					<h4 class="mb-4">Order Summary</h4>

					<div class="summary-row">
						<span>Subtotal (<?php echo $cart_count; ?> items):</span>
						<span class="ms-auto" id="subtotal">GH₵ <?php echo number_format($cart_total, 2); ?></span>
					</div>

					<div class="summary-row">
						<span>Shipping:</span>
						<span class="ms-auto text-success">FREE</span>
					</div>

					<div class="summary-row">
						<span>Tax:</span>
						<span class="ms-auto">GH₵ 0.00</span>
					</div>

					<!-- Discount Row (hidden by default) -->
					<div class="summary-row discount-row" id="discountRow" style="display: none;">
						<span class="text-success" id="discountLabel">
							<i class="fas fa-tag me-1"></i>
							Discount (<span id="discountPercent">20</span>%):
						</span>
						<span class="ms-auto text-success" id="discountAmount">-GH₵ 0.00</span>
					</div>

					<div class="summary-row total">
						<span>Total:</span>
						<span class="ms-auto" id="finalTotal">GH₵ <?php echo number_format($cart_total, 2); ?></span>
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
						<i class="fas fa-credit-card payment-icon"></i>
						<h4 class="mb-0">Secure Payment via PayStack</h4>
						<p class="mb-0 opacity-90">🔒 Powered by PayStack - Ghana's trusted payment gateway</p>
					</div>
				</div>
				<div class="modal-body text-center">
					<div class="mb-4">
						<div class="fs-2 fw-bold text-primary mb-2" id="checkoutTotalDisplay">
							GH₵ <?php echo number_format($cart_total, 2); ?>
						</div>
						<p class="text-muted">
							You'll be redirected to PayStack's secure payment page where you can choose from Mobile Money, Cards, or Bank Transfer options.
						</p>
						<div class="alert alert-info mb-3">
							<i class="fas fa-info-circle me-2"></i>
							<small><strong>All payment methods</strong> (Mobile Money, Cards, Bank Transfer) are processed securely through PayStack</small>
						</div>
					</div>

					<div class="d-grid gap-3">
						<button type="button" class="btn btn-primary btn-lg" id="confirmPaymentBtn">
							<i class="fas fa-credit-card me-2"></i>
							💳 Pay Now
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
	<script src="js/dark-mode.js"></script>
	<script src="js/checkout.js"></script>
