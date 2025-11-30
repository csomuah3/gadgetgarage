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
	<link href="../includes/page-background.css" rel="stylesheet">
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
			font-size: 1.1rem;
			font-weight: 600;
			background-color: #ffffff;
			color: #1a1a1a;
			overflow-x: hidden;
		}

		/* Background moved to page-background.css - using class instead */



		/* Page Title */
		.page-title {
			text-align: center;
			padding: 5px 0;
			font-size: 2.8rem;
			font-weight: 800;
			color: #1f2937;
			margin: 0;
		}

		.checkout-header {
			background: transparent;
			color: #1f2937;
			padding: 0.3rem 0;
			margin-bottom: 0.3rem;
		}

		.checkout-steps {
			display: flex;
			justify-content: center;
			margin-bottom: 0.5rem;
		}

		.step {
			display: flex;
			align-items: center;
			color: #000000;
			font-weight: 500;
		}

		.step.active {
			color: #000000;
		}

		.step-number {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: #e5e7eb;
			border: 2px solid #9ca3af;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 10px;
			font-weight: 600;
			color: #000000;
		}

		.step.active .step-number {
			background: #000000;
			border-color: #000000;
			color: #ffffff;
		}

		.step-divider {
			width: 60px;
			height: 2px;
			background: #9ca3af;
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
			justify-content: space-between;
			align-items: center;
			margin-bottom: 0.5rem;
		}
		
		.summary-row > span:first-child {
			display: inline-flex;
			align-items: center;
			flex-wrap: nowrap;
			flex: 1;
		}
		
		.summary-row .ms-auto {
			margin-left: auto;
			flex-shrink: 0;
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

<body class="page-background">
	<?php include '../includes/header.php'; ?>

	<!-- Floating Bubbles Background -->
	<div class="floating-bubbles" id="floatingBubbles" style="display: none;"></div>

	<div class="checkout-header" style="margin-top: 0; padding-top: 10px;">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-12">
					<div class="checkout-steps" style="margin-bottom: 0.3rem;">
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
					<h1 class="text-center" style="margin-bottom: 5px; font-size: 2rem; font-weight: 800; color: #000000;">
						<i class="fas fa-credit-card me-3" style="color: #000000;"></i>
						Secure Checkout
					</h1>
					<p class="text-center mb-0 fs-5" style="margin-bottom: 5px; color: #000000;">
						Review your order and complete your purchase
					</p>
				</div>
			</div>
		</div>
	</div>

	<div class="container" style="padding-top: 10px; padding-bottom: 20px;">
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
										<small class="text-muted">
											Quantity: <?php echo $item['qty']; ?>
											<?php if (isset($item['condition_type']) && !empty($item['condition_type'])): ?>
												<span class="ms-2">| Condition: <strong><?php echo htmlspecialchars(ucfirst($item['condition_type'])); ?></strong></span>
											<?php elseif (isset($item['product_condition']) && !empty($item['product_condition'])): ?>
												<span class="ms-2">| Condition: <strong><?php echo htmlspecialchars(ucfirst($item['product_condition'])); ?></strong></span>
											<?php endif; ?>
										</small>
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
						<span class="ms-auto" id="taxAmount">GH₵ 150.00</span>
					</div>

					<!-- Discount Row (hidden by default) -->
					<div class="summary-row discount-row" id="discountRow" style="display: none;">
						<span class="text-success" id="discountLabel">
							<i class="fas fa-tag me-1"></i>
							<span>Discount (<span id="discountPercent">20</span>%):</span>
							<span class="badge bg-success ms-2" id="discountBadge" style="font-size: 0.7rem; padding: 2px 6px; white-space: nowrap; display: inline-block;">
								<i class="fas fa-check-circle me-1"></i>Applied from Cart
							</span>
						</span>
						<span class="ms-auto text-success" id="discountAmount">-GH₵ 0.00</span>
					</div>

					<!-- Store Credit Row (hidden by default) -->
					<div class="summary-row store-credits-row" id="storeCreditsRow" style="display: none;">
						<span class="text-primary" id="storeCreditsLabel">
							<i class="fas fa-credit-card me-1"></i>
							Store Credits Applied:
							<span class="badge bg-primary ms-2" id="storeCreditsBadge" style="font-size: 0.7rem; padding: 2px 6px;">
								<i class="fas fa-check-circle me-1"></i>Applied from Cart
							</span>
						</span>
						<span class="ms-auto text-primary" id="storeCreditsAmount">-GH₵ 0.00</span>
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
		.scroll-to-top {
			position: fixed;
			bottom: 30px;
			left: 50%;
			transform: translateX(-50%);
			width: 50px;
			height: 50px;
			background: linear-gradient(135deg, #1E3A5F, #2563EB);
			color: white;
			border: none;
			border-radius: 50%;
			cursor: pointer;
			display: none;
			align-items: center;
			justify-content: center;
			font-size: 20px;
			box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
			z-index: 1000;
			transition: all 0.3s ease;
			opacity: 0;
			visibility: hidden;
		}
		.scroll-to-top.show {
			display: flex;
			opacity: 1;
			visibility: visible;
		}
		.scroll-to-top:hover {
			background: linear-gradient(135deg, #2563EB, #1E3A5F);
			transform: translateX(-50%) translateY(-3px);
			box-shadow: 0 6px 16px rgba(30, 58, 95, 0.4);
		}
		@media (max-width: 768px) {
			.scroll-to-top {
				bottom: 20px;
				width: 45px;
				height: 45px;
				font-size: 18px;
			}
		}
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

	<!-- Footer -->
	<footer class="main-footer">
		<div class="container">
			<div class="footer-content">
				<div class="row align-items-start">
					<!-- First Column: Logo and Social -->
					<div class="col-lg-3 col-md-6 mb-4">
						<div class="footer-brand">
							<div class="footer-logo" style="margin-bottom: 20px;">
								<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
									alt="Gadget Garage">
							</div>
							<p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</p>
							<div class="social-links">
								<a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
								<a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
								<a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
								<a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
							</div>
						</div>
					</div>
					<!-- Navigation Links -->
					<div class="col-lg-5 col-md-12">
						<div class="row">
							<div class="col-lg-4 col-md-6 mb-4">
								<h5 class="footer-title">Get Help</h5>
								<ul class="footer-links">
									<li><a href="contact.php">Help Center</a></li>
									<li><a href="contact.php">Track Order</a></li>
									<li><a href="terms_conditions.php">Shipping Info</a></li>
									<li><a href="terms_conditions.php">Returns</a></li>
									<li><a href="contact.php">Contact Us</a></li>
								</ul>
							</div>
							<div class="col-lg-4 col-md-6 mb-4">
								<h5 class="footer-title">Company</h5>
								<ul class="footer-links">
									<li><a href="contact.php">Careers</a></li>
									<li><a href="contact.php">About</a></li>
									<li><a href="contact.php">Stores</a></li>
									<li><a href="contact.php">Want to Collab?</a></li>
								</ul>
							</div>
							<div class="col-lg-4 col-md-6 mb-4">
								<h5 class="footer-title">Quick Links</h5>
								<ul class="footer-links">
									<li><a href="contact.php">Size Guide</a></li>
									<li><a href="contact.php">Sitemap</a></li>
									<li><a href="contact.php">Gift Cards</a></li>
									<li><a href="contact.php">Check Gift Card Balance</a></li>
								</ul>
							</div>
						</div>
					</div>
					<!-- Right Side: Email Signup Form -->
					<div class="col-lg-4 col-md-12 mb-4">
						<div class="newsletter-signup-section">
							<h3 class="newsletter-title">SIGN UP FOR DISCOUNTS + UPDATES</h3>
							<form class="newsletter-form" id="newsletterForm">
								<input type="text" class="newsletter-input" placeholder="Phone Number or Email" required>
								<button type="submit" class="newsletter-submit-btn">
									<i class="fas fa-arrow-right"></i>
								</button>
							</form>
							<p class="newsletter-disclaimer">
								By signing up for email, you agree to Gadget Garage's <a href="terms_conditions.php">Terms of Service</a> and <a href="legal.php">Privacy Policy</a>.
							</p>
							<p class="newsletter-disclaimer">
								By submitting your phone number, you agree to receive recurring automated promotional and personalized marketing text messages (e.g. cart reminders) from Gadget Garage at the cell number used when signing up. Consent is not a condition of any purchase. Reply HELP for help and STOP to cancel. Msg frequency varies. Msg & data rates may apply. <a href="terms_conditions.php">View Terms</a> & <a href="legal.php">Privacy</a>.
							</p>
						</div>
					</div>
				</div>
				<hr class="footer-divider">
				<div class="footer-bottom">
					<div class="row align-items-center">
						<div class="col-md-12 text-center">
							<p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>

	<script>
		// Scroll to Top Button Functionality
		document.addEventListener('DOMContentLoaded', function() {
			const scrollToTopBtn = document.getElementById('scrollToTopBtn');
			
			if (scrollToTopBtn) {
				// Show/hide button based on scroll position
				window.addEventListener('scroll', function() {
					if (window.pageYOffset > 300) {
						scrollToTopBtn.classList.add('show');
					} else {
						scrollToTopBtn.classList.remove('show');
					}
				});

				// Scroll to top when button is clicked
				scrollToTopBtn.addEventListener('click', function() {
					window.scrollTo({
						top: 0,
						behavior: 'smooth'
					});
				});
			}
		});
	</script>

	<!-- Scroll to Top Button -->
	<button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
		<i class="fas fa-arrow-up"></i>
	</button>

	<script>
		// Check for payment success or error messages on page load
		function checkForPaymentStatus() {
			const urlParams = new URLSearchParams(window.location.search);
			const paymentStatus = urlParams.get('payment');
			const orderId = urlParams.get('order');
			const error = urlParams.get('error');
			const reason = urlParams.get('reason');

			// Check for payment success
			if (paymentStatus === 'success' && orderId) {
				const orderData = sessionStorage.getItem('orderData');
				let orderInfo = null;
				
				if (orderData) {
					try {
						orderInfo = JSON.parse(orderData);
					} catch (e) {
						console.error('Error parsing order data:', e);
					}
				}

				Swal.fire({
					title: 'Order Placed Successfully! 🎉',
					html: `
						<div style="text-align: left;">
							<p style="font-size: 16px; margin-bottom: 15px;">
								<strong>Your order has been confirmed!</strong>
							</p>
							<p style="margin-bottom: 10px;">
								<strong>Order Number:</strong> ${orderInfo?.order_reference || orderId}
							</p>
							<p style="margin-bottom: 10px;">
								<strong>Total Amount:</strong> GH₵ ${orderInfo?.total_amount || '0.00'}
							</p>
							<p style="margin-bottom: 10px;">
								<strong>Payment Method:</strong> ${orderInfo?.payment_method || 'PayStack'}
							</p>
							<p style="margin-top: 15px; color: #28a745; font-weight: 600;">
								✓ Your order will be delivered within 3-5 business days
							</p>
							<p style="margin-top: 10px; font-size: 14px; color: #6c757d;">
								You will receive an SMS confirmation shortly.
							</p>
						</div>
					`,
					icon: 'success',
					confirmButtonText: 'Continue Shopping',
					confirmButtonColor: '#28a745',
					allowOutsideClick: false,
					allowEscapeKey: false
				}).then((result) => {
					sessionStorage.removeItem('orderData');
					const newUrl = window.location.pathname;
					history.replaceState(null, null, newUrl);
					window.location.replace('../index.php');
				});
				return;
			}

			// Check for payment errors
			if (error || (paymentStatus === 'failed')) {
				let title = 'Payment Error';
				let message = reason ? decodeURIComponent(reason) : 'There was an issue with your payment.';

				if (error) {
					switch(error) {
						case 'cancelled':
							title = 'Payment Cancelled';
							message = 'Your payment was cancelled. You can try again when ready.';
							break;
						case 'verification_failed':
							title = 'Payment Verification Failed';
							message = 'We could not verify your payment. Please try again or contact support.';
							break;
						case 'connection_error':
							title = 'Connection Error';
							message = 'There was a connection error while processing your payment. Please try again.';
							break;
						default:
							message = decodeURIComponent(error);
					}
				}

				Swal.fire({
					title: title,
					text: message,
					icon: 'error',
					confirmButtonText: 'Try Again',
					confirmButtonColor: '#dc3545'
				}).then(() => {
					const newUrl = window.location.pathname;
					history.replaceState(null, null, newUrl);
				});
			}
		}

		const BASE_PATH = '<?php echo dirname($_SERVER['PHP_SELF']); ?>';
		const ACTIONS_PATH = BASE_PATH.replace('/views', '') + '/actions/';

		// Make function globally accessible for promo-code.js - MUST BE DEFINED BEFORE USE
		window.checkAndApplyPromoFromCart = function() {
			const VAT_RATE = 0.125; // 12.5% VAT
			
			// Read discount data from localStorage
			const appliedPromo = localStorage.getItem('appliedPromo');
			// Read store credit data from sessionStorage
			const appliedStoreCredits = sessionStorage.getItem('appliedStoreCredits');
			
			// Try to get values from sessionStorage (set from cart page)
			const storedSubtotal = sessionStorage.getItem('cartSubtotal');
			const storedVAT = sessionStorage.getItem('vatAmount');
			const storedFinalTotal = sessionStorage.getItem('finalTotal');
			const storedDiscount = sessionStorage.getItem('discountAmount');
			
			// Get original cart total from PHP (fallback)
			const originalCartTotal = <?php echo $cart_total; ?>;
			
			// Use stored values if available, otherwise calculate
			let subtotal = storedSubtotal ? parseFloat(storedSubtotal) : originalCartTotal;
			let vatAmount = storedVAT ? parseFloat(storedVAT) : (subtotal * VAT_RATE);
			let finalTotal = storedFinalTotal ? parseFloat(storedFinalTotal) : (subtotal + vatAmount);
			let discountAmount = storedDiscount ? parseFloat(storedDiscount) : 0;
			let storeCreditsAmount = 0;
			let activeMethod = null; // 'discount' or 'store_credit'
			
			console.log('Checkout initialization:', {
				subtotal: subtotal,
				vatAmount: vatAmount,
				finalTotal: finalTotal,
				storedValues: {
					subtotal: storedSubtotal,
					vat: storedVAT,
					finalTotal: storedFinalTotal,
					discount: storedDiscount
				}
			});
			
			// Process discount if exists
			if (appliedPromo && !storedFinalTotal) {
				// Only recalculate if we don't have stored final total from cart
				try {
					const promoData = JSON.parse(appliedPromo);
					console.log('Promo data parsed successfully:', promoData);
					
					discountAmount = promoData.discount_amount || 0;
					// Recalculate: subtotal - discount, then add VAT
					const subtotalAfterDiscount = Math.max(0, originalCartTotal - discountAmount);
					vatAmount = subtotalAfterDiscount * VAT_RATE;
					finalTotal = subtotalAfterDiscount + vatAmount;
					activeMethod = 'discount';
					
					// Show discount row
					const discountRow = document.getElementById('discountRow');
					if (discountRow) {
						discountRow.style.display = 'flex';
						
						const discountLabel = document.getElementById('discountLabel');
						if (discountLabel) {
							let discountText = '';
							if (promoData.discount_type === 'fixed') {
								discountText = 'Discount (' + (promoData.promo_code || 'Discount') + '):';
							} else {
								discountText = 'Discount (' + promoData.discount_value + '%):';
							}
							discountLabel.innerHTML = '<i class="fas fa-tag me-1"></i><span>' + discountText + '</span><span class="badge bg-success ms-2" style="font-size: 0.7rem; padding: 2px 6px; white-space: nowrap; display: inline-block;"><i class="fas fa-check-circle me-1"></i>Applied from Cart</span>';
						}
						
						const discountAmountElement = document.getElementById('discountAmount');
						if (discountAmountElement) {
							discountAmountElement.textContent = '-GH₵ ' + discountAmount.toFixed(2);
						}
					}
					
					// Hide store credit row if discount is active
					const storeCreditsRow = document.getElementById('storeCreditsRow');
					if (storeCreditsRow) {
						storeCreditsRow.style.display = 'none';
					}
					
					// Store in window for later use
					if (typeof window !== 'undefined') {
						window.discountedTotal = finalTotal;
						window.originalTotal = promoData.original_total || originalCartTotal;
						window.discountAmount = discountAmount;
						window.appliedPromoCode = promoData.promo_code;
					}
					
					console.log('Discount applied from cart. Final total:', finalTotal);
				} catch (error) {
					console.error('Error applying promo from cart:', error);
					localStorage.removeItem('appliedPromo');
				}
			}
			
			// Process store credits if exists (only if discount is not active)
			if (appliedStoreCredits && !activeMethod && !storedFinalTotal) {
				// Only recalculate if we don't have stored final total from cart
				try {
					storeCreditsAmount = parseFloat(appliedStoreCredits) || 0;
					if (storeCreditsAmount > 0) {
						// Recalculate: subtotal - credits, then add VAT
						const subtotalAfterCredits = Math.max(0, originalCartTotal - storeCreditsAmount);
						vatAmount = subtotalAfterCredits * VAT_RATE;
						finalTotal = subtotalAfterCredits + vatAmount;
						activeMethod = 'store_credit';
						
						// Show store credit row
						const storeCreditsRow = document.getElementById('storeCreditsRow');
						if (storeCreditsRow) {
							storeCreditsRow.style.display = 'flex';
							
							const storeCreditsLabel = document.getElementById('storeCreditsLabel');
							if (storeCreditsLabel) {
								storeCreditsLabel.innerHTML = '<i class="fas fa-credit-card me-1"></i><span>Store Credits Applied:</span><span class="badge bg-primary ms-2" style="font-size: 0.7rem; padding: 2px 6px; white-space: nowrap; display: inline-block;"><i class="fas fa-check-circle me-1"></i>Applied from Cart</span>';
							}
							
							const storeCreditsAmountElement = document.getElementById('storeCreditsAmount');
							if (storeCreditsAmountElement) {
								storeCreditsAmountElement.textContent = '-GH₵ ' + storeCreditsAmount.toFixed(2);
							}
						}
						
						// Hide discount row if store credit is active
						const discountRow = document.getElementById('discountRow');
						if (discountRow) {
							discountRow.style.display = 'none';
						}
						
						// Store in window for later use
						if (typeof window !== 'undefined') {
							window.storeCreditsApplied = storeCreditsAmount;
							window.originalTotal = originalCartTotal;
						}
						
						console.log('Store credits applied from cart. Final total:', finalTotal);
					}
				} catch (error) {
					console.error('Error applying store credits from cart:', error);
					sessionStorage.removeItem('appliedStoreCredits');
				}
			}
			
			// Update subtotal display (show original cart subtotal)
			const subtotalElement = document.getElementById('subtotal');
			if (subtotalElement) {
				subtotalElement.textContent = 'GH₵ ' + subtotal.toFixed(2);
			}
			
			// Update VAT display (12.5% of subtotal after discount/credits)
			const taxAmountElement = document.getElementById('taxAmount');
			if (taxAmountElement) {
				taxAmountElement.textContent = 'GH₵ ' + vatAmount.toFixed(2);
			}
			
			// Update final total display (subtotal - discount/credits + VAT)
			const finalTotalElement = document.getElementById('finalTotal');
			if (finalTotalElement) {
				finalTotalElement.textContent = 'GH₵ ' + finalTotal.toFixed(2);
			}
			
			// Update payment button with final total
			const completeOrderBtn = document.getElementById('simulatePaymentBtn');
			if (completeOrderBtn) {
				completeOrderBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Complete Order - GH₵ ' + finalTotal.toFixed(2);
			}
			
			// Store final total in window for payment processing
			if (typeof window !== 'undefined') {
				window.checkoutFinalTotal = finalTotal;
				window.checkoutVAT = vatAmount;
				window.checkoutSubtotal = subtotal;
			}
			
			console.log('Checkout totals updated:', {
				subtotal: subtotal,
				vatAmount: vatAmount,
				finalTotal: finalTotal,
				discountAmount: discountAmount,
				storeCreditsAmount: storeCreditsAmount,
				activeMethod: activeMethod
			});
			
			// Update large total display at top
			const checkoutTotalDisplay = document.getElementById('checkoutTotalDisplay');
			if (checkoutTotalDisplay) {
				checkoutTotalDisplay.textContent = 'GH₵ ' + finalTotal.toFixed(2);
			}
			
			// Store final total for payment processing
			if (typeof window !== 'undefined') {
				window.cartPageFinalTotal = finalTotal;
				window.cartPageOriginalTotal = originalCartTotal;
				window.activeMethod = activeMethod;
			}
			
		};

		// Wait for DOM to be fully loaded before calling
		function initCheckoutPage() {
			checkForPaymentStatus();

			const savedLanguage = localStorage.getItem('selectedLanguage');
			if (savedLanguage) {
				const languageSelect = document.querySelector('.language-selector select');
				if (languageSelect) {
					languageSelect.value = savedLanguage;
				}
			}

			const isDarkMode = localStorage.getItem('darkMode') === 'true';
			if (isDarkMode) {
				document.body.classList.add('dark-mode');
				const toggleSwitch = document.getElementById('themeToggle');
				if (toggleSwitch) {
					toggleSwitch.classList.add('active');
				}
			}

			createFloatingBubbles();
			// Call after a small delay to ensure all elements are rendered
			setTimeout(function() {
				window.checkAndApplyPromoFromCart();
			}, 100);
		}

		// Call function when DOM is ready
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initCheckoutPage);
		} else {
			// DOM is already ready
			initCheckoutPage();
		}

		function createFloatingBubbles() {
			const bubblesContainer = document.getElementById('floatingBubbles');
			const bubbleCount = 50;

			for (let i = 0; i < bubbleCount; i++) {
				const bubble = document.createElement('div');
				bubble.className = 'bubble';

				let size;
				const sizeCategory = Math.random();
				if (sizeCategory < 0.5) {
					size = Math.random() * 20 + 15;
					bubble.classList.add('bubble-small');
				} else if (sizeCategory < 0.8) {
					size = Math.random() * 25 + 35;
					bubble.classList.add('bubble-medium');
				} else {
					size = Math.random() * 30 + 60;
					bubble.classList.add('bubble-large');
				}

				bubble.style.width = size + 'px';
				bubble.style.height = size + 'px';
				bubble.style.left = Math.random() * 100 + '%';

				let duration;
				if (size < 35) {
					duration = Math.random() * 8 + 12;
				} else if (size < 60) {
					duration = Math.random() * 6 + 15;
				} else {
					duration = Math.random() * 4 + 18;
				}
				bubble.style.animationDuration = duration + 's';

				const delay = Math.random() * 15;
				bubble.style.animationDelay = delay + 's';

				let opacity;
				if (size < 35) {
					opacity = Math.random() * 0.3 + 0.4;
				} else if (size < 60) {
					opacity = Math.random() * 0.3 + 0.5;
				} else {
					opacity = Math.random() * 0.2 + 0.6;
				}
				bubble.style.opacity = opacity;

				bubblesContainer.appendChild(bubble);
			}
		}

		let selectedPaymentMethod = 'paystack-mobile';

		document.addEventListener('DOMContentLoaded', function() {
			console.log('Checkout page loaded, setting up payment options...');

			const paymentOptions = document.querySelectorAll('.payment-option');
			console.log('Found payment options:', paymentOptions.length);

			if (paymentOptions.length > 0) {
				paymentOptions[0].classList.add('selected');
				selectedPaymentMethod = paymentOptions[0].getAttribute('data-method');
				console.log('Default payment method set to:', selectedPaymentMethod);
			}

			paymentOptions.forEach((option, index) => {
				console.log('Setting up payment option:', index, option.getAttribute('data-method'));
				option.addEventListener('click', function(e) {
					e.preventDefault();
					console.log('Payment option clicked:', this.getAttribute('data-method'));

					paymentOptions.forEach(opt => opt.classList.remove('selected'));
					this.classList.add('selected');
					selectedPaymentMethod = this.getAttribute('data-method');
					console.log('Selected payment method:', selectedPaymentMethod);
				});
			});

			const checkoutButton = document.getElementById('simulatePaymentBtn');
			console.log('Found checkout button:', !!checkoutButton);
			if (checkoutButton) {
				checkoutButton.addEventListener('click', function(e) {
					e.preventDefault();
					console.log('Checkout button clicked, processing payment...');
					processCheckout();
				});
			}
		});

		function processCheckout() {
			console.log('Session data check:');
			console.log('User ID: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'; ?>');
			console.log('Email: <?php echo isset($_SESSION['email']) ? $_SESSION['email'] : 'NOT SET'; ?>');
			console.log('Name: <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'NOT SET'; ?>');

			const customerEmail = '<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>';
			console.log('Customer email for payment:', customerEmail);

			if (!customerEmail) {
				Swal.fire({
					icon: 'error',
					title: 'Login Required',
					text: 'Please login to complete your purchase.',
					showCancelButton: true,
					confirmButtonText: 'Login Now',
					cancelButtonText: 'Cancel'
				}).then((result) => {
					if (result.isConfirmed) {
						window.location.href = 'login/login.php';
					}
				});
				return;
			}

			// Use the total from cart page (already calculated and stored)
			const taxAmount = 150.00; // Standard tax
			let totalAmount = <?php echo $cart_total; ?> + taxAmount;
			
			// Check if we have a final total from cart page
			if (typeof window.cartPageFinalTotal !== 'undefined' && window.cartPageFinalTotal !== null) {
				totalAmount = window.cartPageFinalTotal;
				console.log('Using cart page final total:', totalAmount);
			} else {
				// Fallback: check localStorage/sessionStorage
				const appliedPromo = localStorage.getItem('appliedPromo');
				const appliedStoreCredits = sessionStorage.getItem('appliedStoreCredits');
				
				if (appliedPromo) {
					try {
						const promoData = JSON.parse(appliedPromo);
						totalAmount = promoData.new_total + taxAmount;
					} catch (error) {
						console.error('Error parsing promo data:', error);
					}
				} else if (appliedStoreCredits) {
					const creditsAmount = parseFloat(appliedStoreCredits) || 0;
					// Calculate: (original subtotal - credits) + tax
					const originalSubtotal = <?php echo $cart_total; ?>;
					totalAmount = Math.max(0, originalSubtotal - creditsAmount) + taxAmount;
				}
			}

			Swal.fire({
				title: 'Processing Payment...',
				html: `
					<div class="text-center">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading...</span>
						</div>
						<p class="mt-3">Redirecting to PayStack...</p>
					</div>
				`,
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false
			});

			// Get store credits applied (if any)
			let storeCreditsApplied = 0;
			if (typeof window.storeCreditsApplied !== 'undefined') {
				storeCreditsApplied = window.storeCreditsApplied;
			} else {
				const appliedStoreCredits = sessionStorage.getItem('appliedStoreCredits');
				if (appliedStoreCredits) {
					storeCreditsApplied = parseFloat(appliedStoreCredits) || 0;
				}
			}
			
			fetch('../actions/paystack_init_transaction.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					email: customerEmail,
					total_amount: totalAmount,
					payment_method: selectedPaymentMethod,
					store_credits_applied: storeCreditsApplied > 0 ? storeCreditsApplied : 0
				})
			})
			.then(response => response.json())
			.then(data => {
				console.log('Payment initialization response:', data);

				if (data.status === 'success') {
					Swal.close();
					localStorage.removeItem('appliedPromo');
					window.location.href = data.authorization_url;
				} else {
					console.error('Payment initialization failed:', data);

					let errorText = data.message || 'Failed to initialize payment';
					if (data.debug) {
						console.log('Debug info:', data.debug);
						errorText += '\n\nDebug Info:\n' + JSON.stringify(data.debug, null, 2);
					}

					Swal.fire({
						icon: 'error',
						title: 'Payment Error',
						text: errorText,
						footer: 'Check browser console for more details'
					});
				}
			})
			.catch(error => {
				console.error('Payment initialization error:', error);
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Failed to initialize payment. Please try again.'
				});
			});
		}
	</script>

</body>
</html>
