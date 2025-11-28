<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

// Check user authentication
$is_logged_in = check_login();
$is_admin = $is_logged_in ? check_admin() : false;

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

// Get repair issue types
try {
    $db = new db_connection();
    $db->db_connect();
    $issue_types = $db->db_fetch_all("SELECT * FROM repair_issue_types ORDER BY issue_name");
} catch (Exception $e) {
    $issue_types = [];
    $error_message = "Unable to load repair services. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Device Repair Services - Gadget Garage</title>
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
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

		/* Promotional Banner Styles - Same as login */
		.promo-banner,
        .promo-banner2 {
			background: #001f3f !important;
			color: white;
			padding: 6px 15px;
			text-align: center;
			font-size: 1rem;
			font-weight: 400;
			position: sticky;
			top: 0;
			z-index: 1001;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
			height: 32px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 15px;
			max-width: 100%;
		}

		.promo-banner-left,
        .promo-banner2 .promo-banner-left {
			display: flex;
			align-items: center;
			gap: 15px;
			flex: 0 0 auto;
		}

		.promo-banner-center,
        .promo-banner2 .promo-banner-center {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 20px;
			flex: 1;
		}

		.promo-banner i,
        .promo-banner2 i {
			font-size: 1rem;
		}

		.promo-banner .promo-text,
        .promo-banner2 .promo-text {
			font-size: 1rem;
			font-weight: 400;
			letter-spacing: 0.5px;
		}

		.promo-timer {
			background: transparent;
			padding: 0;
			border-radius: 0;
			font-size: 1.3rem;
			font-weight: 500;
			margin: 0;
			border: none;
		}

		.promo-shop-link {
			color: white;
			text-decoration: underline;
			font-weight: 700;
			cursor: pointer;
			transition: opacity 0.3s ease;
			font-size: 1.2rem;
			flex: 0 0 auto;
		}

		.promo-shop-link:hover {
			opacity: 0.8;
		}

		/* Header Styles - Same as login */
		.main-header {
			background: #ffffff;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
			position: sticky;
			top: 38px;
			z-index: 1000;
			padding: 20px 0;
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
			gap: 11px;
		}

		.header-icon {
			position: relative;
			width: 48px;
			height: 48px;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #374151;
			font-size: 1.3rem;
			transition: all 0.3s ease;
			border-radius: 50%;
		}

		.header-icon:hover {
			background: rgba(139, 95, 191, 0.1);
			transform: scale(1.1);
		}

		.wishlist-badge,
		.cart-badge {
			position: absolute;
			top: -2px;
			right: -2px;
			background: #ef4444;
			color: white;
			border-radius: 50%;
			width: 20px;
			height: 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 0.7rem;
			font-weight: 600;
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

		.user-dropdown {
			position: relative;
		}

		.user-avatar {
			width: 48px;
			height: 48px;
			background: linear-gradient(135deg, #008060, #006b4e);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-weight: 600;
			font-size: 1.3rem;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.user-avatar:hover {
			transform: scale(1.15);
			box-shadow: 0 5px 15px rgba(0, 128, 96, 0.5);
		}

		.dropdown-menu-custom {
			position: absolute;
			top: 100%;
			right: 0;
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(20px);
			border: 1px solid rgba(139, 95, 191, 0.2);
			border-radius: 15px;
			box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
			padding: 15px 0;
			min-width: 220px;
			opacity: 0;
			visibility: hidden;
			transform: translateY(-10px);
			transition: all 0.3s ease;
			z-index: 1000;
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
			background: rgba(139, 95, 191, 0.1);
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
			background: linear-gradient(90deg, transparent, rgba(139, 95, 191, 0.2), transparent);
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

		.language-selector,
		.theme-toggle {
			display: flex;
			align-items: center;
			justify-content: space-between;
			width: 100%;
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

		.nav-dropdown {
			position: relative;
			display: inline-block;
		}

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

		/* Page Title */
		.page-title {
			text-align: center;
			padding: 40px 0;
			font-size: 2.5rem;
			font-weight: 700;
			color: #1f2937;
			margin: 0;
		}

		/* Floating Bubbles Animation */
		.floating-bubbles {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			pointer-events: none;
			z-index: -1;
			overflow: hidden;
		}

		.bubble {
			position: absolute;
			border-radius: 50%;
			background: linear-gradient(135deg, rgba(0, 128, 96, 0.1), rgba(0, 107, 78, 0.05));
			animation: floatUp linear infinite;
			opacity: 0.8;
		}

		.bubble:nth-child(odd) {
			background: linear-gradient(135deg, rgba(0, 107, 78, 0.1), rgba(0, 128, 96, 0.05));
		}

		.bubble:nth-child(3n) {
			background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(0, 128, 96, 0.05));
		}

		.bubble:nth-child(5n) {
			background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(0, 107, 78, 0.05));
		}

		@keyframes floatUp {
			from {
				transform: translateY(100vh) rotate(0deg);
				opacity: 0;
			}

			10% {
				opacity: 0.8;
			}

			90% {
				opacity: 0.8;
			}

			to {
				transform: translateY(-100px) rotate(360deg);
				opacity: 0;
			}
		}

		/* Repair Services Specific Styles */
		.hero-section {
			padding: 4rem 0 2rem;
			text-align: center;
			position: relative;
			z-index: 10;
		}

		.hero-title {
			font-size: 3rem;
			font-weight: 700;
			color: #047857;
			margin-bottom: 1rem;
		}

		.hero-subtitle {
			font-size: 1.2rem;
			color: #065f46;
			margin-bottom: 0.5rem;
		}

		.hero-description {
			color: #6b7280;
			font-size: 1.1rem;
			max-width: 600px;
			margin: 0 auto;
		}

		.progress-steps {
			display: flex;
			justify-content: center;
			align-items: center;
			gap: 2rem;
			margin: 3rem 0;
		}

		.step {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			color: #6b7280;
			font-weight: 500;
		}

		.step-number {
			width: 30px;
			height: 30px;
			border-radius: 50%;
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 600;
		}

		.step.active .step-number {
			background: linear-gradient(135deg, #047857, #059669);
		}

		.step-separator {
			width: 3rem;
			height: 2px;
			background: #e5e7eb;
		}

		.issues-section {
			padding: 2rem 0;
			position: relative;
			z-index: 10;
		}

		.section-title {
			text-align: center;
			font-size: 2.5rem;
			font-weight: 700;
			color: #047857;
			margin-bottom: 3rem;
		}

		.issues-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
			gap: 2rem;
			max-width: 1200px;
			margin: 0 auto;
		}

		.issue-card {
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(20px);
			border-radius: 20px;
			padding: 2rem;
			border: 1px solid rgba(0, 128, 96, 0.1);
			box-shadow: 0 4px 20px rgba(0, 128, 96, 0.05);
			transition: all 0.3s ease;
			cursor: pointer;
			position: relative;
			overflow: hidden;
		}

		.issue-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: linear-gradient(135deg, #008060, #006b4e);
			transform: scaleX(0);
			transition: transform 0.3s ease;
		}

		.issue-card:hover {
			transform: translateY(-8px);
			box-shadow: 0 8px 30px rgba(0, 128, 96, 0.15);
		}

		.issue-card:hover::before {
			transform: scaleX(1);
		}

		.issue-icon {
			width: 80px;
			height: 80px;
			border-radius: 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 1.5rem;
			font-size: 2rem;
			color: white;
			background: linear-gradient(135deg, #008060, #006b4e);
		}

		.issue-title {
			font-size: 1.5rem;
			font-weight: 700;
			color: #047857;
			margin-bottom: 1rem;
			text-align: center;
		}

		.issue-description {
			color: #6b7280;
			text-align: center;
			margin-bottom: 1.5rem;
			line-height: 1.6;
		}

		.issue-price {
			text-align: center;
			margin-top: auto;
		}

		.price-range {
			font-size: 1.1rem;
			font-weight: 600;
			color: #059669;
		}

		.price-label {
			font-size: 0.9rem;
			color: #6b7280;
			margin-top: 0.25rem;
		}

		.continue-btn {
			background: linear-gradient(135deg, #008060, #006b4e);
			color: white;
			border: none;
			padding: 15px 40px;
			border-radius: 50px;
			font-size: 1.1rem;
			font-weight: 600;
			position: fixed;
			bottom: 30px;
			right: 30px;
			box-shadow: 0 4px 20px rgba(0, 128, 96, 0.3);
			transition: all 0.3s ease;
			z-index: 1000;
			display: none;
		}

		.continue-btn:hover {
			background: linear-gradient(135deg, #059669, #006b4e);
			transform: translateY(-2px);
			box-shadow: 0 6px 25px rgba(0, 128, 96, 0.4);
		}

		.continue-btn.show {
			display: block;
			animation: slideUp 0.3s ease;
		}

		@keyframes slideUp {
			from {
				opacity: 0;
				transform: translateY(20px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		@media (max-width: 768px) {
			.hero-title {
				font-size: 2rem;
			}

			.progress-steps {
				flex-direction: column;
				gap: 1rem;
			}

			.step-separator {
				display: none;
			}

			.issues-grid {
				grid-template-columns: 1fr;
				padding: 0 1rem;
			}

			.continue-btn {
				bottom: 20px;
				right: 20px;
				left: 20px;
				width: auto;
			}
		}

    /* Dark Mode Promotional Banner Styles */
    @media (prefers-color-scheme: dark) {
        .promo-banner,
        .promo-banner2 {
            background: linear-gradient(90deg, #1a202c, #2d3748);
            color: #f7fafc;
        }
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
    <!-- Promotional Banner -->
    <div class="promo-banner2">
        <div class="promo-banner-left">
            <i class="fas fa-bolt"></i>
        </div>
        <div class="promo-banner-center">
            <span class="promo-text" data-translate="black_friday_deals">BLACK FRIDAY DEALS STOREWIDE! SHOP AMAZING DISCOUNTS! </span>
            <span class="promo-timer" id="promoTimer">12d:00h:00m:00s</span>
        </div>
        <a href="../index.php#flash-deals" class="promo-shop-link" data-translate="shop_now">Shop Now</a>
    </div>

	<!-- Floating Bubbles Background -->
	<div class="floating-bubbles" id="floatingBubbles"></div>

    <!-- Main Header -->
    <header class="main-header animate__animated animate__fadeInDown">
        <div class="container-fluid" style="padding: 0 120px 0 95px;">
            <div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
                <!-- Logo - Far Left -->
                <a href="../index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                        alt="Gadget Garage">
                </a>

                <!-- Center Content -->
                <div class="d-flex align-items-center" style="flex: 1; justify-content: center; gap: 60px;">
                    <!-- Search Bar -->
                    <form class="search-container" method="GET" action="../product_search_result.php">
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
                            <p class="tech-revival-text">Bring Retired Devices</p>
                            <p class="contact-number">055-138-7578</p>
                        </div>
                    </div>
                </div>

                <!-- User Actions - Far Right -->
                <div class="user-actions" style="display: flex; align-items: center; gap: 18px;">
                    <span style="color: #ddd; font-size: 1.5rem; margin: 0 5px;">|</span>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Wishlist Icon -->
                        <div class="header-icon">
                            <a href="../views/wishlist.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-heart"></i>
                                <span class="wishlist-badge" id="wishlistBadge" style="display: none;">0</span>
                            </a>
                        </div>

                        <!-- Cart Icon -->
                        <div class="header-icon">
                            <a href="cart.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                                <?php else: ?>
                                    <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                                <?php endif; ?>
                            </a>
                        </div>

                        <!-- User Avatar Dropdown -->
                        <div class="user-dropdown">
                            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>" onclick="toggleUserDropdown()">
                                <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="dropdown-menu-custom" id="userDropdownMenu">
                                <a href="account.php" class="dropdown-item-custom">
                                    <i class="fas fa-user"></i>
                                    <span data-translate="account">Account</span>
                                </a>
                                <a href="my_orders.php" class="dropdown-item-custom">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span data-translate="my_orders">My Orders</span>
                                </a>
                                <a href="../track_order.php" class="dropdown-item-custom">
                                    <i class="fas fa-truck"></i>
                                    <span data-translate="track_orders">Track Orders</span>
                                </a>
                                <a href="notifications.php" class="dropdown-item-custom">
                                    <i class="fas fa-bell"></i>
                                    <span>Notifications</span>
                                </a>
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
                                <a href="../login/logout.php" class="dropdown-item-custom">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login Button -->
                        <a href="../login/login.php" class="login-btn">
                            <i class="fas fa-user"></i>
                            Login
                        </a>
                        <!-- Register Button -->
                        <a href="../login/register.php" class="login-btn" style="margin-left: 10px;">
                            <i class="fas fa-user-plus"></i>
                            Register
                        </a>
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
                        <span data-translate="shop_by_brands">SHOP BY BRANDS</span>
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
                                <li><a href="all_product.php?brand=Apple"><i class="fas fa-tag"></i> Apple</a></li>
                                <li><a href="all_product.php?brand=Samsung"><i class="fas fa-tag"></i> Samsung</a></li>
                                <li><a href="all_product.php?brand=HP"><i class="fas fa-tag"></i> HP</a></li>
                                <li><a href="all_product.php?brand=Dell"><i class="fas fa-tag"></i> Dell</a></li>
                                <li><a href="all_product.php?brand=Sony"><i class="fas fa-tag"></i> Sony</a></li>
                                <li><a href="all_product.php?brand=Canon"><i class="fas fa-tag"></i> Canon</a></li>
                                <li><a href="all_product.php?brand=Nikon"><i class="fas fa-tag"></i> Nikon</a></li>
                                <li><a href="all_product.php?brand=Microsoft"><i class="fas fa-tag"></i> Microsoft</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <a href="../index.php" class="nav-item"><span data-translate="home">HOME</span></a>

                <!-- Shop Dropdown -->
                <div class="nav-dropdown" onmouseenter="showShopDropdown()" onmouseleave="hideShopDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="shop">SHOP</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="mega-dropdown" id="shopCategoryDropdown">
                        <div class="dropdown-content">
                            <div class="dropdown-column">
                                <h4>
                                    <a href="mobile_devices.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="mobile_devices">Mobile Devices</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
                                    <li><a href="all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="computing.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="computing">Computing</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
                                    <li><a href="all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="photography_video.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="photography_video">Photography & Video</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
                                    <li><a href="all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column featured">
                                <h4>Shop All</h4>
                                <div class="featured-item">
                                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
                                    <div class="featured-text">
                                        <strong>New Arrivals</strong>
                                        <p>Latest tech gadgets</p>
                                        <a href="all_product.php" class="shop-now-btn">Shop </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="repair_services.php" class="nav-item"><span data-translate="repair_studio">REPAIR STUDIO</span></a>
                <a href="device_drop.php" class="nav-item"><span data-translate="device_drop">DEVICE DROP</span></a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="more">MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                            <li><a href="terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
            </div>
        </div>
    </nav>

	<!-- Page Title -->
	<h1 class="page-title">Device Repair Services</h1>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <i class="fas fa-tools me-2" style="color: #10b981; font-size: 1.5rem;"></i>
                <span style="color: #10b981; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Professional Repair Services</span>
            </div>

            <h1 class="hero-title">Device Repair Services</h1>
            <p class="hero-subtitle">Get your device repaired by certified experts. Schedule an appointment</p>
            <p class="hero-description">within 24 hours and receive expert care.</p>

            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <span>Issue Type</span>
                </div>
                <div class="step-separator"></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <span>Specialist</span>
                </div>
                <div class="step-separator"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <span>Schedule</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Issues Section -->
    <section class="issues-section">
        <div class="container">
            <h2 class="section-title">What's wrong with your device?</h2>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="issues-grid">
                <?php foreach ($issue_types as $issue): ?>
                    <div class="issue-card" onclick="goToSpecialist(<?php echo $issue['issue_id']; ?>, '<?php echo htmlspecialchars($issue['issue_name'], ENT_QUOTES); ?>')">
                        <div class="issue-icon">
                            <i class="<?php echo htmlspecialchars($issue['icon_class']); ?>"></i>
                        </div>
                        <h3 class="issue-title"><?php echo htmlspecialchars($issue['issue_name']); ?></h3>
                        <p class="issue-description"><?php echo htmlspecialchars($issue['issue_description']); ?></p>
                        <div class="issue-price">
                            <div class="price-range">
                                GH₵ <?php echo number_format($issue['estimated_cost_min'], 0); ?> -
                                <?php echo number_format($issue['estimated_cost_max'], 0); ?>
                            </div>
                            <div class="price-label">Estimated Cost</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Continue Button -->
    <button class="continue-btn" id="continueBtn" onclick="proceedToSpecialist()">
        Continue
        <i class="fas fa-arrow-right ms-2"></i>
    </button>

	<!-- Scripts -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="js/cart.js"></script>
	<script src="js/header.js"></script>
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
		// Dropdown navigation functions with timeout delays - MUST BE GLOBAL for inline handlers
		let dropdownTimeout;
		let shopDropdownTimeout;
		let moreDropdownTimeout;
		let userDropdownTimeout;

		window.showDropdown = function() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				clearTimeout(dropdownTimeout);
				dropdown.classList.add('show');
			}
		};

		window.hideDropdown = function() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				clearTimeout(dropdownTimeout);
				dropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		};

		window.showShopDropdown = function() {
			const dropdown = document.getElementById('shopCategoryDropdown');
			if (dropdown) {
				clearTimeout(shopDropdownTimeout);
				dropdown.classList.add('show');
			}
		};

		window.hideShopDropdown = function() {
			const dropdown = document.getElementById('shopCategoryDropdown');
			if (dropdown) {
				clearTimeout(shopDropdownTimeout);
				shopDropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		};

		window.showMoreDropdown = function() {
			const dropdown = document.getElementById('moreDropdown');
			if (dropdown) {
				clearTimeout(moreDropdownTimeout);
				dropdown.classList.add('show');
			}
		};

		window.hideMoreDropdown = function() {
			const dropdown = document.getElementById('moreDropdown');
			if (dropdown) {
				clearTimeout(moreDropdownTimeout);
				moreDropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		};

		window.showUserDropdown = function() {
			const dropdown = document.getElementById('userDropdownMenu');
			if (dropdown) {
				clearTimeout(userDropdownTimeout);
				dropdown.classList.add('show');
			}
		};

		window.hideUserDropdown = function() {
			const dropdown = document.getElementById('userDropdownMenu');
			if (dropdown) {
				clearTimeout(userDropdownTimeout);
				userDropdownTimeout = setTimeout(() => {
					dropdown.classList.remove('show');
				}, 300);
			}
		};

		window.toggleUserDropdown = function() {
			const dropdown = document.getElementById('userDropdownMenu');
			if (dropdown) {
				dropdown.classList.toggle('show');
			}
		};

		// Enhanced dropdown behavior
		document.addEventListener('DOMContentLoaded', function() {
			const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
			const brandsDropdown = document.getElementById('shopDropdown');

			if (shopCategoriesBtn && brandsDropdown) {
				shopCategoriesBtn.addEventListener('mouseenter', window.showDropdown);
				shopCategoriesBtn.addEventListener('mouseleave', window.hideDropdown);
				brandsDropdown.addEventListener('mouseenter', function() {
					clearTimeout(dropdownTimeout);
				});
				brandsDropdown.addEventListener('mouseleave', window.hideDropdown);
			}

			const userAvatar = document.querySelector('.user-avatar');
			const userDropdown = document.getElementById('userDropdownMenu');

			if (userAvatar && userDropdown) {
				userAvatar.addEventListener('mouseenter', window.showUserDropdown);
				userAvatar.addEventListener('mouseleave', window.hideUserDropdown);
				userDropdown.addEventListener('mouseenter', function() {
					clearTimeout(userDropdownTimeout);
				});
				userDropdown.addEventListener('mouseleave', window.hideUserDropdown);
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
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					title: 'Profile Picture',
					text: 'Profile picture upload functionality will be implemented',
					icon: 'info',
					confirmButtonColor: '#D19C97',
					confirmButtonText: 'OK'
				});
			} else {
				Swal.fire({title: 'Feature Coming Soon', text: 'Profile picture upload functionality will be implemented', icon: 'info', confirmButtonColor: '#007bff', confirmButtonText: 'OK'});
			}
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

		// Repair services specific functionality
		// Direct navigation function - goes straight to specialist page
		function goToSpecialist(issueId, issueName) {
			// Navigate directly to specialist selection page
			// Both files are in the same directory (views/), so use relative path
			const url = `repair_specialist.php?issue_id=${issueId}&issue_name=${encodeURIComponent(issueName)}`;
			console.log('Navigating to:', url);
			window.location.href = url;
		}

		// Keep the old functions for backward compatibility if needed
		let selectedIssue = null;
		let selectedIssueName = '';

		function selectIssue(issueId, issueName) {
			// Remove previous selection
			document.querySelectorAll('.issue-card').forEach(card => {
				card.classList.remove('selected');
				card.style.background = '';
				card.style.border = '';
			});

			// Select current issue
			if (event && event.currentTarget) {
				event.currentTarget.classList.add('selected');
				event.currentTarget.style.background = 'linear-gradient(135deg, #ecfdf5, #d1fae5)';
				event.currentTarget.style.border = '2px solid #008060';
			}

			selectedIssue = issueId;
			selectedIssueName = issueName;

			// Show continue button
			const continueBtn = document.getElementById('continueBtn');
			if (continueBtn) {
				continueBtn.classList.add('show');
			}
		}

		function proceedToSpecialist() {
			if (selectedIssue) {
				// Both files are in views/ directory, so relative path works
				const url = `repair_specialist.php?issue_id=${selectedIssue}&issue_name=${encodeURIComponent(selectedIssueName)}`;
				console.log('Navigating to:', url);
				window.location.href = url;
			} else {
				alert('Please select an issue first');
			}
		}

		// Timer functionality for promo banner
		function updateTimer() {
			const timerElement = document.getElementById('promoTimer');
			if (timerElement) {
				const now = new Date().getTime();
				const nextDay = new Date();
				nextDay.setDate(nextDay.getDate() + 1);
				nextDay.setHours(0, 0, 0, 0);

				const distance = nextDay.getTime() - now;

				const days = Math.floor(distance / (1000 * 60 * 60 * 24));
				const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
				const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
				const seconds = Math.floor((distance % (1000 * 60)) / 1000);

				timerElement.innerHTML = days + "d:" +
								         (hours < 10 ? "0" : "") + hours + "h:" +
								         (minutes < 10 ? "0" : "") + minutes + "m:" +
								         (seconds < 10 ? "0" : "") + seconds + "s";
			}
		}

		// Update timer every second
		setInterval(updateTimer, 1000);
		updateTimer(); // Initial call

		// Account page navigation function from login.php
		function goToAccount() {
			window.location.href = 'my_orders.php';
		}

		// Add hover effects for repair cards
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.issue-card').forEach(card => {
				card.addEventListener('mouseenter', function() {
					if (!this.classList.contains('selected')) {
						this.style.background = 'linear-gradient(135deg, #f8fafc, #f1f5f9)';
					}
				});

				card.addEventListener('mouseleave', function() {
					if (!this.classList.contains('selected')) {
						this.style.background = '';
					}
				});
			});
		});
	</script>

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
</body>
</html>