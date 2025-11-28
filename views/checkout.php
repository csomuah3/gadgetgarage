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

		/* Checkout Page Specific Styles */

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
