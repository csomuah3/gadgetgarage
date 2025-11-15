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
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
			background-color: #ffffff;
			color: #1a1a1a;
			overflow-x: hidden;
		}

		/* Header Styles */
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
		}

		.logo .garage {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			padding: 4px 8px;
			border-radius: 6px;
			font-size: 1rem;
			font-weight: 600;
		}

		.search-container {
			position: relative;
			flex: 1;
			max-width: 500px;
			margin: 0 40px;
		}

		.search-input {
			width: 100%;
			padding: 12px 20px 12px 50px;
			border: 2px solid #e2e8f0;
			border-radius: 25px;
			font-size: 1rem;
			transition: all 0.3s ease;
			background: #f8fafc;
		}

		.search-input:focus {
			outline: none;
			border-color: #008060;
			background: white;
			box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
		}

		.search-icon {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: #008060;
			font-size: 1.1rem;
		}

		.search-btn {
			position: absolute;
			right: 6px;
			top: 50%;
			transform: translateY(-50%);
			background: linear-gradient(135deg, #008060, #006b4e);
			border: none;
			padding: 8px 16px;
			border-radius: 20px;
			color: white;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.search-btn:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			transform: translateY(-50%) scale(1.05);
		}

		.header-actions {
			display: flex;
			align-items: center;
			gap: 20px;
		}

		.vertical-separator {
			width: 1px;
			height: 40px;
			background: #e5e7eb;
			margin: 0 15px;
		}

		.tech-revival-section {
			display: flex;
			align-items: center;
			gap: 10px;
			text-align: center;
			margin: 0 60px;
		}

		.tech-revival-icon {
			font-size: 1.5rem;
			color: #008060;
		}

		.tech-revival-text {
			font-size: 0.9rem;
			font-weight: 600;
			color: #1f2937;
			margin: 0;
		}

		.contact-number {
			font-size: 0.8rem;
			color: #6b7280;
			margin: 0;
		}

		.user-actions {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		/* Navigation Separator Styles */
		.nav-separator {
			color: #e5e7eb;
			font-weight: 300;
			margin: 0 8px;
			font-size: 1.2rem;
		}

		.nav-dropdown {
			position: relative;
			display: inline-block;
		}

		.simple-dropdown {
			position: absolute;
			top: 100%;
			left: 0;
			background: white;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			min-width: 160px;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
			z-index: 1000;
			opacity: 0;
			visibility: hidden;
			transform: translateY(-10px);
			transition: all 0.3s ease;
		}

		.simple-dropdown.show {
			opacity: 1;
			visibility: visible;
			transform: translateY(0);
		}

		.simple-dropdown ul {
			list-style: none;
			padding: 8px 0;
			margin: 0;
		}

		.simple-dropdown li {
			padding: 0;
		}

		.simple-dropdown a {
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 12px 16px;
			color: #4b5563;
			text-decoration: none;
			font-size: 0.9rem;
			transition: all 0.3s ease;
		}

		.simple-dropdown a:hover {
			background: #f3f4f6;
			color: #008060;
		}

		.flash-deal-spacer {
			flex: 1;
			min-width: 100px;
		}

		/* Brands Dropdown Styles */
		.brands-dropdown {
			position: absolute;
			top: 100%;
			left: 0;
			background: white;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			padding: 12px;
			width: 280px;
			max-height: 350px;
			overflow-y: auto;
			box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
			z-index: 1000;
			opacity: 0;
			visibility: hidden;
			transform: translateY(-10px);
			transition: all 0.3s ease;
		}

		.brands-dropdown.show {
			opacity: 1;
			visibility: visible;
			transform: translateY(0);
		}

		.brands-dropdown h4 {
			font-size: 1rem;
			font-weight: 600;
			color: #1f2937;
			margin-bottom: 12px;
			padding-bottom: 8px;
			border-bottom: 1px solid #e5e7eb;
		}

		.brands-dropdown ul {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.brands-dropdown li {
			padding: 0;
		}

		.brands-dropdown a {
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 8px 12px;
			color: #4b5563;
			text-decoration: none;
			font-size: 0.9rem;
			border-radius: 6px;
			transition: all 0.2s ease;
		}

		.brands-dropdown a:hover {
			background: #f3f4f6;
			color: #008060;
		}

		/* Scrollbar styling for brands dropdown */
		.brands-dropdown::-webkit-scrollbar {
			width: 6px;
		}

		.brands-dropdown::-webkit-scrollbar-track {
			background: #f1f1f1;
			border-radius: 3px;
		}

		.brands-dropdown::-webkit-scrollbar-thumb {
			background: #c1c1c1;
			border-radius: 3px;
		}

		.brands-dropdown::-webkit-scrollbar-thumb:hover {
			background: #a8a8a8;
		}

		.header-icon {
			position: relative;
			padding: 8px;
			border-radius: 8px;
			transition: all 0.3s ease;
			color: #4b5563;
			cursor: pointer;
		}

		.header-icon:hover {
			background: rgba(0, 128, 96, 0.1);
			color: #008060;
		}

		.cart-badge {
			position: absolute;
			top: -2px;
			right: -2px;
			background: linear-gradient(135deg, #006b4e, #008060);
			color: white;
			font-size: 0.75rem;
			padding: 2px 6px;
			border-radius: 10px;
			min-width: 18px;
			text-align: center;
		}

		.user-menu {
			position: relative;
		}

		.login-btn {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 20px;
			font-weight: 500;
			text-decoration: none;
			transition: all 0.3s ease;
			display: inline-block;
		}

		.login-btn:hover {
			background: linear-gradient(135deg, #006b4e, #008060);
			transform: translateY(-1px);
			color: white;
		}

		.logout-btn {
			background: linear-gradient(135deg, #ef4444, #dc2626);
			color: white;
			border: none;
			padding: 8px 16px;
			border-radius: 16px;
			font-weight: 500;
			text-decoration: none;
			transition: all 0.3s ease;
			display: inline-block;
			font-size: 0.875rem;
		}

		.logout-btn:hover {
			background: linear-gradient(135deg, #dc2626, #b91c1c);
			transform: translateY(-1px);
			color: white;
		}

		.user-dropdown {
			position: relative;
		}

		.user-avatar {
			width: 36px;
			height: 36px;
			background: linear-gradient(135deg, #008060, #006b4e);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.user-avatar:hover {
			transform: scale(1.1);
			box-shadow: 0 4px 12px rgba(0, 128, 96, 0.3);
		}

		.dropdown-menu-custom {
			position: absolute;
			top: 100%;
			right: 0;
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(20px);
			border: 1px solid rgba(0, 128, 96, 0.2);
			border-radius: 15px;
			box-shadow: 0 8px 32px rgba(0, 128, 96, 0.15);
			padding: 15px 0;
			min-width: 220px;
			z-index: 1000;
			opacity: 0;
			visibility: hidden;
			transform: translateY(-10px);
			transition: all 0.3s ease;
		}

		.dropdown-menu-custom.show {
			opacity: 1;
			visibility: visible;
			transform: translateY(0);
		}

		.dropdown-item-custom {
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 12px 20px;
			color: #4a5568;
			text-decoration: none;
			transition: all 0.3s ease;
			border: none;
			background: none;
			width: 100%;
			cursor: pointer;
			font-size: 0.9rem;
		}

		.dropdown-item-custom:hover {
			background: rgba(0, 128, 96, 0.1);
			color: #008060;
			transform: translateX(3px);
		}

		.dropdown-item-custom i {
			font-size: 1rem;
			width: 18px;
			text-align: center;
		}

		.dropdown-divider-custom {
			height: 1px;
			background: linear-gradient(90deg, transparent, rgba(0, 128, 96, 0.2), transparent);
			margin: 8px 0;
		}

		.theme-toggle {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.toggle-switch {
			position: relative;
			width: 40px;
			height: 20px;
			background: #e2e8f0;
			border-radius: 10px;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.toggle-switch.active {
			background: linear-gradient(135deg, #008060, #006b4e);
		}

		.toggle-slider {
			position: absolute;
			top: 2px;
			left: 2px;
			width: 16px;
			height: 16px;
			background: white;
			border-radius: 50%;
			transition: all 0.3s ease;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.toggle-switch.active .toggle-slider {
			transform: translateX(20px);
		}

		.language-selector {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.language-flag {
			width: 20px;
			height: 15px;
			border-radius: 3px;
			background: linear-gradient(45deg, #4f46e5, #7c3aed);
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-size: 10px;
			font-weight: bold;
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
			width: 100%;
			padding-left: 260px;
		}

		.nav-item {
			color: #1f2937;
			text-decoration: none;
			font-weight: 500;
			padding: 16px 20px;
			display: flex;
			align-items: center;
			gap: 5px;
			transition: all 0.3s ease;
			border-radius: 8px;
			white-space: nowrap;
		}

		.nav-item:hover {
			background: rgba(0, 128, 96, 0.1);
			color: #008060;
			transform: translateY(-2px);
		}

		.nav-item.flash-deal {
			color: #ef4444;
			font-weight: 700;
			margin-left: auto;
			padding-right: 470px;
		}

		.nav-item.flash-deal:hover {
			color: #dc2626;
		}

		/* Blue Shop by Categories Button */
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

		.categories-button i:last-child {
			font-size: 0.8rem;
			transition: transform 0.3s ease;
		}

		.shop-categories-btn:hover .categories-button i:last-child {
			transform: rotate(180deg);
		}


		.nav-item.dropdown {
			position: relative;
		}

		.nav-link {
			color: #1f2937;
			text-decoration: none;
			font-weight: 500;
			font-size: 1rem;
			padding: 12px 0;
			display: flex;
			align-items: center;
			gap: 6px;
			transition: all 0.3s ease;
		}

		.nav-link:hover {
			color: #008060;
		}

		.nav-link i {
			font-size: 0.8rem;
			transition: transform 0.3s ease;
		}

		.dropdown:hover .nav-link i {
			transform: rotate(180deg);
		}

		/* Mega Dropdown */
		.mega-dropdown {
			position: absolute;
			top: 100%;
			left: 0;
			width: 800px;
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
			padding: 32px;
			opacity: 0;
			visibility: visible;
			transform: translateY(0px);
			transition: all 0.3s ease;
			z-index: 1000;
		}

		.mega-dropdown.show {
			opacity: 1;
			visibility: visible;
			transform: translateY(0);
		}

		.dropdown-content {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 40px;
		}

		.dropdown-column h4 {
			color: #1f2937;
			font-size: 1.1rem;
			font-weight: 600;
			margin-bottom: 16px;
			border-bottom: 2px solid #f3f4f6;
			padding-bottom: 8px;
		}

		.dropdown-column ul {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.dropdown-column ul li {
			margin-bottom: 8px;
		}

		.dropdown-column ul li a {
			color: #6b7280;
			text-decoration: none;
			font-size: 0.95rem;
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 8px 0;
			transition: all 0.3s ease;
		}

		.dropdown-column ul li a:hover {
			color: #008060;
			transform: translateX(4px);
		}

		.dropdown-column ul li a i {
			color: #9ca3af;
			width: 16px;
		}

		.dropdown-column.featured {
			border-left: 2px solid #f3f4f6;
			padding-left: 24px;
		}

		.featured-item {
			display: flex;
			gap: 12px;
			align-items: center;
			padding: 16px;
			border-radius: 12px;
			background: #f8fafc;
			border: 1px solid #e5e7eb;
			transition: all 0.3s ease;
		}

		.featured-item:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
		}

		.featured-item img {
			width: 60px;
			height: 40px;
			object-fit: cover;
			border-radius: 6px;
		}

		.featured-text strong {
			color: #1f2937;
			font-size: 0.9rem;
			font-weight: 600;
		}

		.featured-text p {
			color: #6b7280;
			font-size: 0.8rem;
			margin: 4px 0;
		}

		.shop-now-btn {
			background: #008060;
			color: white;
			padding: 4px 12px;
			border-radius: 6px;
			text-decoration: none;
			font-size: 0.8rem;
			font-weight: 500;
			transition: all 0.3s ease;
		}

		.shop-now-btn:hover {
			background: #374151;
			color: white;
		}

		/* Page Title */
		.page-title {
			text-align: center;
			padding: 40px 0;
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			margin: 0;
		}

		.checkout-header {
			background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
			color: #1f2937;
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
	<!-- Floating Bubbles Background -->
	<div class="floating-bubbles" id="floatingBubbles"></div>

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
								<?php if ($is_admin): ?>
									<div class="dropdown-divider-custom"></div>
									<a href="admin/category.php" class="dropdown-item-custom">
										<i class="fas fa-cog"></i>
										<span>Admin Panel</span>
									</a>
								<?php endif; ?>
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

	<!-- Scripts -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="js/cart.js"></script>
	<script>
		// Search functionality
		document.querySelector('.search-input').addEventListener('keypress', function(e) {
			if (e.key === 'Enter') {
				performSearch();
			}
		});

		document.querySelector('.search-btn').addEventListener('click', performSearch);

		function performSearch() {
			const query = document.querySelector('.search-input').value.trim();
			if (query) {
				// Redirect to search results page
				window.location.href = 'product_search_result.php?query=' + encodeURIComponent(query);
			}
		}

		// Dropdown navigation functions
		let dropdownTimeout;

		function showDropdown() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				clearTimeout(dropdownTimeout);
				dropdown.classList.add('show');
			}
		}

		function hideDropdown() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				// Clear any existing timeout
				clearTimeout(dropdownTimeout);
				// Set a delay before hiding to allow moving to dropdown
				dropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		}

		// Shop Category Dropdown Functions
		function showShopDropdown() {
			const dropdown = document.getElementById('shopCategoryDropdown');
			if (dropdown) {
				clearTimeout(shopDropdownTimeout);
				dropdown.classList.add('show');
			}
		}

		function hideShopDropdown() {
			const dropdown = document.getElementById('shopCategoryDropdown');
			if (dropdown) {
				clearTimeout(shopDropdownTimeout);
				shopDropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		}

		// More Dropdown Functions
		function showMoreDropdown() {
			const dropdown = document.getElementById('moreDropdown');
			if (dropdown) {
				clearTimeout(moreDropdownTimeout);
				dropdown.classList.add('show');
			}
		}

		function hideMoreDropdown() {
			const dropdown = document.getElementById('moreDropdown');
			if (dropdown) {
				clearTimeout(moreDropdownTimeout);
				moreDropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		}

		// Timeout variables
		let shopDropdownTimeout;
		let moreDropdownTimeout;

		// Enhanced dropdown behavior
		document.addEventListener('DOMContentLoaded', function() {
			const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
			const dropdown = document.getElementById('shopDropdown');

			if (shopCategoriesBtn && dropdown) {
				// Show dropdown on button hover
				shopCategoriesBtn.addEventListener('mouseenter', showDropdown);

				// Hide dropdown when leaving button (with delay)
				shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);

				// Keep dropdown open when hovering over it
				dropdown.addEventListener('mouseenter', function() {
					clearTimeout(dropdownTimeout);
				});

				// Hide dropdown when leaving dropdown area
				dropdown.addEventListener('mouseleave', hideDropdown);
			}
		});

		// Smooth scrolling for internal links
		document.querySelectorAll('a[href^="#"]').forEach(anchor => {
			anchor.addEventListener('click', function(e) {
				e.preventDefault();
				const target = document.querySelector(this.getAttribute('href'));
				if (target) {
					target.scrollIntoView({
						behavior: 'smooth'
					});
				}
			});
		});

		// Add animation classes on scroll
		const observerOptions = {
			threshold: 0.1,
			rootMargin: '0px 0px -50px 0px'
		};

		const observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					entry.target.style.opacity = '1';
					entry.target.style.transform = 'translateY(0)';
				}
			});
		}, observerOptions);

		// Observe elements for animation
		document.querySelectorAll('.promo-card, .hero-content').forEach(el => {
			observer.observe(el);
		});

		// User dropdown functionality
		function toggleUserDropdown() {
			const dropdown = document.getElementById('userDropdownMenu');
			dropdown.classList.toggle('show');
		}

		// Close dropdown when clicking outside
		document.addEventListener('click', function(event) {
			const dropdown = document.getElementById('userDropdownMenu');
			const avatar = document.querySelector('.user-avatar');

			if (!dropdown.contains(event.target) && !avatar.contains(event.target)) {
				dropdown.classList.remove('show');
			}
		});

		// Profile picture modal functionality
		function openProfilePictureModal() {
			// For now, show alert - will be replaced with actual modal
			alert('Profile picture upload functionality will be implemented');
		}

		// Language change functionality
		function changeLanguage(language) {
			// Store language preference
			localStorage.setItem('selectedLanguage', language);
			console.log('Language changed to:', language);
			// Here you would implement actual language switching
			// Language change is silent now - no notification
		}

		// Theme toggle functionality
		function toggleTheme() {
			const toggle = document.getElementById('themeToggle');
			const body = document.body;

			toggle.classList.toggle('active');
			body.classList.toggle('dark-mode');

			// Store theme preference
			const isDark = body.classList.contains('dark-mode');
			localStorage.setItem('darkMode', isDark);
		}

		// Load saved preferences on page load
		document.addEventListener('DOMContentLoaded', function() {
			// Load saved language
			const savedLanguage = localStorage.getItem('selectedLanguage');
			if (savedLanguage) {
				const languageSelect = document.querySelector('.language-selector select');
				if (languageSelect) {
					languageSelect.value = savedLanguage;
				}
			}

			// Load saved theme
			const isDarkMode = localStorage.getItem('darkMode') === 'true';
			if (isDarkMode) {
				document.body.classList.add('dark-mode');
				document.getElementById('themeToggle').classList.add('active');
			}

			// Create floating bubbles
			createFloatingBubbles();
		});

		// Create 40+ floating bubbles with different sizes and animations
		function createFloatingBubbles() {
			const bubblesContainer = document.getElementById('floatingBubbles');
			const bubbleCount = 50; // Create 50 bubbles

			for (let i = 0; i < bubbleCount; i++) {
				const bubble = document.createElement('div');
				bubble.className = 'bubble';

				// Create distinct size categories: small, medium, large
				let size;
				const sizeCategory = Math.random();
				if (sizeCategory < 0.5) {
					// 50% small bubbles (15-35px)
					size = Math.random() * 20 + 15;
					bubble.classList.add('bubble-small');
				} else if (sizeCategory < 0.8) {
					// 30% medium bubbles (35-60px)
					size = Math.random() * 25 + 35;
					bubble.classList.add('bubble-medium');
				} else {
					// 20% large bubbles (60-90px)
					size = Math.random() * 30 + 60;
					bubble.classList.add('bubble-large');
				}

				bubble.style.width = size + 'px';
				bubble.style.height = size + 'px';

				// Random horizontal position
				bubble.style.left = Math.random() * 100 + '%';

				// Animation duration based on size (larger bubbles float slower)
				let duration;
				if (size < 35) {
					duration = Math.random() * 8 + 12; // Small: 12-20s
				} else if (size < 60) {
					duration = Math.random() * 6 + 15; // Medium: 15-21s
				} else {
					duration = Math.random() * 4 + 18; // Large: 18-22s
				}
				bubble.style.animationDuration = duration + 's';

				// Random delay between 0s and 15s
				const delay = Math.random() * 15;
				bubble.style.animationDelay = delay + 's';

				// Opacity based on size (larger bubbles slightly more visible)
				let opacity;
				if (size < 35) {
					opacity = Math.random() * 0.3 + 0.4; // Small: 0.4-0.7
				} else if (size < 60) {
					opacity = Math.random() * 0.3 + 0.5; // Medium: 0.5-0.8
				} else {
					opacity = Math.random() * 0.2 + 0.6; // Large: 0.6-0.8
				}
				bubble.style.opacity = opacity;

				bubblesContainer.appendChild(bubble);
			}
		}
	</script>
</body>
</html>