<?php
// No PHP includes or session management needed for register page
// This is a static registration form
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Register - Gadget Garage</title>
	<meta name="description" content="Create your Gadget Garage account to access premium tech devices and exclusive deals.">
	<link href="../includes/chatbot-styles.css" rel="stylesheet">
	<link href="../css/dark-mode.css" rel="stylesheet">

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

	<style>
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

		/* Color Scheme Variables - GadgetGarage Colors */
		:root {
			--gg-teal: #008060;
			--gg-teal-dark: #006b4e;
			--gg-teal-light: #00a67e;
			--gg-green: #10b981;
			--gg-green-dark: #059669;
			--off-white: #FAFAFA;
			--text-dark: #1F2937;
			--text-light: #6B7280;
			--shadow: rgba(0, 128, 96, 0.15);
			--gradient-primary: linear-gradient(135deg, var(--gg-teal-dark) 0%, var(--gg-teal) 50%, var(--gg-teal-light) 100%);
			--gradient-light: linear-gradient(135deg, rgba(0, 128, 96, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
		}

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
		
		/* Ensure all form elements use consistent font */
		input, textarea, select, button {
			font-family: "Times New Roman", Times, serif;
			font-size: 1rem;
		}
		
		input::placeholder, textarea::placeholder {
			font-family: "Times New Roman", Times, serif;
			font-size: 1rem;
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

		/* Promotional Banner Styles - Same as index */
		.promo-banner {
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

		.promo-banner-left {
			display: flex;
			align-items: center;
			gap: 15px;
			flex: 0 0 auto;
		}

		.promo-banner-center {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 20px;
			flex: 1;
		}

		.promo-banner i {
			font-size: 1rem;
		}

		.promo-banner .promo-text {
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

		/* Header Styles - Same as index */
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

		.logo img {
			height: 60px !important;
			width: auto !important;
			object-fit: contain;
			transition: transform 0.3s ease;
		}

		.logo:hover img {
			transform: scale(1.05);
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
			max-width: 600px;
			width: 100%;
			margin: 0 auto;
		}

		.search-input {
			width: 100%;
			padding: 15px 50px 15px 50px;
			border: 2px solid #e5e7eb;
			border-radius: 50px;
			background: #f8fafc;
			font-size: 1rem;
			transition: all 0.3s ease;
			outline: none;
		}

		.search-input:focus {
			border-color: #3b82f6;
			background: white;
			box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
		}

		.search-icon {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: #6b7280;
			font-size: 1.1rem;
		}

		.search-btn {
			position: absolute;
			right: 8px;
			top: 50%;
			transform: translateY(-50%);
			background: linear-gradient(135deg, #3b82f6, #1e40af);
			color: white;
			border: none;
			border-radius: 50%;
			width: 40px;
			height: 40px;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.3s ease;
		}

		.search-btn:hover {
			transform: translateY(-50%) scale(1.05);
			box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
		}

		.tech-revival-section {
			display: flex;
			align-items: center;
			gap: 12px;
			color: #1f2937;
		}

		.tech-revival-icon {
			font-size: 2.5rem;
			color: #10b981;
		}

		.tech-revival-text {
			font-size: 1.1rem;
			font-weight: 600;
			margin: 0;
			line-height: 1.2;
		}

		.contact-number {
			font-size: 1rem;
			font-weight: 500;
			color: #6b7280;
			margin: 0;
			line-height: 1.2;
		}

		/* Main Navigation - Copied from index.php */
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
			padding-left: 280px;
		}

		.nav-item {
			color: #1f2937;
			text-decoration: none;
			font-weight: 600;
			padding: 16px 20px;
			font-size: 1.3rem;
			display: flex;
			align-items: center;
			gap: 6px;
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
			padding-right: 600px;
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
			font-size: 1rem;
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
			visibility: hidden;
			transform: translateY(-10px);
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
			font-size: 1.2rem;
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
			font-size: 1rem;
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
			width: 20px;
		}

		.dropdown-column.featured {
			border-left: 2px solid #f3f4f6;
			padding-left: 24px;
		}

		.featured-item {
			display: flex;
			gap: 12px;
			align-items: center;
		}

		.featured-item img {
			width: 60px;
			height: 60px;
			border-radius: 8px;
			object-fit: cover;
		}

		.featured-text {
			display: flex;
			flex-direction: column;
			gap: 4px;
		}

		.featured-text strong {
			color: #1f2937;
			font-size: 1rem;
		}

		.featured-text p {
			color: #6b7280;
			font-size: 0.9rem;
			margin: 0;
		}

		.shop-now-btn {
			background: #008060;
			color: white;
			text-decoration: none;
			padding: 6px 12px;
			border-radius: 4px;
			font-size: 0.8rem;
			font-weight: 500;
			margin-top: 4px;
			display: inline-block;
			transition: background 0.3s ease;
		}

		.shop-now-btn:hover {
			background: #006b4e;
			color: white;
		}

		/* Simple Dropdown */
		.simple-dropdown {
			position: absolute;
			top: 100%;
			left: 0;
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
			padding: 8px 0;
			min-width: 200px;
			opacity: 0;
			visibility: hidden;
			transform: translateY(-10px);
			transition: all 0.3s ease;
			z-index: 1000;
		}

		.simple-dropdown.show {
			opacity: 1;
			visibility: visible;
			transform: translateY(0);
		}

		.simple-dropdown ul {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.simple-dropdown ul li a {
			color: #6b7280;
			text-decoration: none;
			padding: 8px 16px;
			display: flex;
			align-items: center;
			gap: 8px;
			transition: all 0.3s ease;
		}

		.simple-dropdown ul li a:hover {
			background: #f9fafb;
			color: #008060;
		}

		/* Dropdown Positioning */
		.nav-dropdown {
			position: relative;
		}

		.brands-dropdown {
			position: absolute;
			top: 100%;
			left: 0;
			background: white;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
			padding: 20px;
			min-width: 300px;
			opacity: 0;
			visibility: hidden;
			transform: translateY(-10px);
			transition: all 0.3s ease;
			z-index: 1000;
		}

		.shop-categories-btn:hover .brands-dropdown {
			opacity: 1;
			visibility: visible;
			transform: translateY(0);
		}

		.brands-dropdown h4 {
			margin-bottom: 15px;
			color: #1f2937;
			font-size: 1.1rem;
			font-weight: 600;
		}

		.brands-dropdown ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		.brands-dropdown li {
			margin-bottom: 8px;
		}

		.brands-dropdown a {
			color: #6b7280;
			text-decoration: none;
			padding: 8px 12px;
			border-radius: 6px;
			display: flex;
			align-items: center;
			gap: 8px;
			transition: all 0.3s ease;
		}

		.brands-dropdown a:hover {
			background: #f3f4f6;
			color: #3b82f6;
		}

		.brands-dropdown h4 {
			margin-bottom: 15px;
			color: #1f2937;
			font-size: 1.1rem;
			font-weight: 600;
		}

		.brands-dropdown ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		.brands-dropdown li {
			margin-bottom: 8px;
		}

		.brands-dropdown a {
			color: #6b7280;
			text-decoration: none;
			padding: 8px 12px;
			border-radius: 6px;
			display: flex;
			align-items: center;
			gap: 8px;
			transition: all 0.3s ease;
		}

		.brands-dropdown a:hover {
			background: #f3f4f6;
			color: #3b82f6;
		}

		/* Login Form Container */
		.login-page-container {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 40px 20px;
			min-height: calc(100vh - 200px);
			background: transparent;
		}

		.auth-container {
			width: 100%;
			max-width: 1200px;
			height: 750px;
			position: relative;
			border-radius: 25px;
			overflow: hidden;
			box-shadow: 0 25px 80px var(--shadow);
			backdrop-filter: blur(15px);
			border: 1px solid rgba(255, 255, 255, 0.2);
			display: flex;
		}

		.auth-panels {
			display: flex;
			height: 100%;
			width: 100%;
			position: relative;
		}

		/* Welcome Panel - GadgetGarage Teal/Green Gradient - LEFT SIDE */
		.welcome-panel {
			flex: 0 0 50%;
			background: var(--gradient-primary);
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			color: white;
			padding: 80px 60px;
			text-align: center;
			position: relative;
			overflow: hidden;
			border-top-right-radius: 50px;
			border-bottom-right-radius: 50px;
		}

		.welcome-panel::before {
			content: '';
			position: absolute;
			top: -50%;
			left: -50%;
			width: 200%;
			height: 200%;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
			animation: float 6s ease-in-out infinite;
		}

		@keyframes float {

			0%,
			100% {
				transform: translateY(0) rotate(0deg);
			}

			50% {
				transform: translateY(-20px) rotate(180deg);
			}
		}

		.brand-logo {
			width: 140px;
			height: auto;
			margin-bottom: 40px;
			filter: brightness(0) invert(1);
			z-index: 2;
			position: relative;
		}

		.welcome-title {
			font-size: 2.8rem;
			font-weight: 700;
			margin-bottom: 20px;
			z-index: 2;
			position: relative;
		}

		.welcome-message {
			font-size: 1.2rem;
			line-height: 1.6;
			opacity: 0.95;
			max-width: 350px;
			margin-bottom: 30px;
			z-index: 2;
			position: relative;
		}

		.welcome-signup-btn,
		.welcome-signin-btn {
			background: transparent;
			border: 2px solid white;
			color: white;
			text-decoration: none;
			display: inline-block;
			padding: 16px 40px;
			border-radius: 12px;
			font-size: 1.1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			z-index: 2;
			position: relative;
			text-transform: uppercase;
			margin-bottom: 20px;
		}

		.welcome-signup-btn:hover,
		.welcome-signin-btn:hover {
			background: white;
			color: var(--gg-teal);
			transform: translateY(-2px);
			box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
		}

		.welcome-button-message {
			font-size: 0.95rem;
			line-height: 1.5;
			opacity: 0.9;
			max-width: 320px;
			z-index: 2;
			position: relative;
			margin-top: 10px;
		}

		/* Form Panel - RIGHT SIDE */
		.form-panel {
			flex: 0 0 50%;
			background: rgba(255, 255, 255, 0.98);
			backdrop-filter: blur(20px);
			display: flex;
			flex-direction: column;
			justify-content: flex-start;
			padding: 40px 50px;
			position: relative;
			overflow-y: auto;
			max-height: 100%;
		}

		.form-panel::-webkit-scrollbar {
			width: 8px;
		}

		.form-panel::-webkit-scrollbar-track {
			background: rgba(0, 0, 0, 0.05);
		}

		.form-panel::-webkit-scrollbar-thumb {
			background: var(--gg-teal);
			border-radius: 4px;
		}

		.form-container {
			width: 100%;
			max-width: 420px;
			margin: 0 auto;
			padding-top: 20px;
		}

		.form-header {
			text-align: center;
			margin-bottom: 30px;
		}

		.form-title {
			font-size: 2.5rem;
			font-weight: 700;
			font-family: "Times New Roman", Times, serif;
			color: var(--text-dark);
			margin-bottom: 10px;
			text-align: center;
		}

		.signup-socials-text {
			text-align: center;
			margin-top: 10px;
			margin-bottom: 20px;
			color: var(--text-light);
			font-size: 1rem;
		}

		.form-subtitle {
			color: var(--text-light);
			font-size: 1rem;
			text-align: center;
			margin-bottom: 30px;
		}

		/* Social Login Buttons */
		.social-login {
			margin-bottom: 30px;
		}

		.social-buttons {
			display: flex;
			gap: 15px;
			justify-content: center;
			margin-bottom: 25px;
		}

		.social-btn {
			width: 50px;
			height: 50px;
			border-radius: 12px;
			border: 2px solid #e5e7eb;
			background: white;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: all 0.3s ease;
			font-size: 1.3rem;
		}

		.social-btn.google {
			color: #ea4335;
		}

		.social-btn.facebook {
			color: #1877f2;
		}

		.social-btn.pinterest {
			color: #bd081c;
		}

		.social-btn.linkedin {
			color: #0077b5;
		}

		.social-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
		}

		.divider {
			text-align: center;
			position: relative;
			margin: 25px 0;
		}

		.divider::before {
			content: '';
			position: absolute;
			top: 50%;
			left: 0;
			right: 0;
			height: 1px;
			background: #e5e7eb;
		}

		.divider span {
			background: rgba(255, 255, 255, 0.98);
			padding: 0 20px;
			color: var(--text-light);
			font-size: 1.1rem;
			font-weight: 500;
		}

		.form-group {
			margin-bottom: 20px;
		}

		.form-label {
			display: block;
			font-weight: 600;
			color: var(--text-dark);
			margin-bottom: 10px;
			font-size: 1rem;
			font-family: "Times New Roman", Times, serif;
		}

		.form-control {
			width: 100%;
			padding: 16px 20px 16px 50px;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			background: #f8fafc;
			color: var(--text-dark);
			font-size: 1rem;
			font-family: "Times New Roman", Times, serif;
			transition: all 0.3s ease;
			outline: none;
		}
		
		.form-control::placeholder {
			font-family: "Times New Roman", Times, serif;
			font-size: 1rem;
		}

		.form-control:focus {
			border-color: var(--gg-teal);
			background: white;
			box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
		}

		.input-group {
			position: relative;
		}

		.input-icon {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: var(--gg-teal);
			font-size: 1.1rem;
			z-index: 2;
		}

		.ghana-flag {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			width: 24px;
			height: 16px;
			z-index: 2;
		}

		.form-control.with-icon {
			padding-left: 55px;
		}

		.form-control.with-flag {
			padding-left: 55px;
		}

		.submit-btn {
			width: 100%;
			background: var(--gradient-primary);
			color: white;
			border: none;
			padding: 18px;
			border-radius: 12px;
			font-size: 1rem;
			font-family: "Times New Roman", Times, serif;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
			margin-top: 10px;
		}

		.submit-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 12px 30px rgba(0, 128, 96, 0.4);
		}

		.submit-btn:active {
			transform: translateY(0);
		}

		.form-content {
			display: none;
		}

		.form-content.active {
			display: block;
		}

		.alert {
			border-radius: 12px;
			margin-bottom: 20px;
			border: none;
			padding: 15px 18px;
		}

		.alert-danger {
			background: #fee2e2;
			color: #dc2626;
		}

		.alert-success {
			background: #d1fae5;
			color: #059669;
			padding: 0 40px 30px;
			overflow-y: auto !important;
			overflow-x: hidden;
			flex: 1;
			min-height: 0;
			scroll-behavior: smooth;
			position: relative;
			-webkit-overflow-scrolling: touch;
		}

		.register-form-body::-webkit-scrollbar {
			width: 12px;
			background: rgba(0, 0, 0, 0.05);
		}

		.register-form-body::-webkit-scrollbar-track {
			background: rgba(0, 0, 0, 0.05);
			border-radius: 10px;
			margin: 5px;
		}

		.register-form-body::-webkit-scrollbar-thumb {
			background: linear-gradient(135deg, #3b82f6, #1e40af);
			border-radius: 10px;
			border: 2px solid rgba(255, 255, 255, 0.5);
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.register-form-body::-webkit-scrollbar-thumb:hover {
			background: linear-gradient(135deg, #1e40af, #3b82f6);
			box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
		}

		.register-form-body::-webkit-scrollbar-thumb:active {
			background: #1e40af;
		}

		/* Firefox scrollbar */
		.register-form-body {
			scrollbar-width: thin;
			scrollbar-color: #3b82f6 rgba(0, 0, 0, 0.05);
		}

		.form-row {
			display: flex;
			gap: 20px;
			margin-bottom: 20px;
		}

		.form-group {
			margin-bottom: 20px;
			flex: 1;
		}

		.form-group.full-width {
			flex: 1 1 100%;
		}

		.checkbox-group {
			display: flex;
			align-items: center;
			gap: 12px;
			margin-bottom: 25px;
			padding: 15px;
			background: rgba(59, 130, 246, 0.05);
			border: 1px solid rgba(59, 130, 246, 0.1);
			border-radius: 12px;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.checkbox-group:hover {
			background: rgba(59, 130, 246, 0.1);
			border-color: rgba(59, 130, 246, 0.2);
		}

		.custom-checkbox {
			width: 20px;
			height: 20px;
			border: 2px solid #d1d5db;
			border-radius: 4px;
			background: white;
			position: relative;
			transition: all 0.3s ease;
			cursor: pointer;
			flex-shrink: 0;
		}

		.custom-checkbox input {
			opacity: 0;
			position: absolute;
			width: 100%;
			height: 100%;
			cursor: pointer;
		}

		.custom-checkbox input:checked+.checkbox-mark {
			opacity: 1;
			transform: scale(1);
		}

		.custom-checkbox input:checked~.custom-checkbox {
			border-color: #3b82f6;
			background: #3b82f6;
		}

		.checkbox-mark {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%) scale(0);
			opacity: 0;
			color: white;
			font-size: 12px;
			transition: all 0.2s ease;
			pointer-events: none;
		}

		.checkbox-group input:checked~.custom-checkbox {
			border-color: #3b82f6;
			background: #3b82f6;
		}

		.checkbox-label {
			color: #374151;
			font-size: 1rem;
			font-weight: 500;
			cursor: pointer;
			user-select: none;
		}

		/* Enhanced Verification Section */
		.verification-section {
			margin-bottom: 25px;
			padding: 20px;
			background: linear-gradient(135deg, rgba(59, 130, 246, 0.03), rgba(16, 185, 129, 0.03));
			border: 1px solid rgba(59, 130, 246, 0.1);
			border-radius: 16px;
			transition: all 0.3s ease;
		}

		.verification-section:hover {
			border-color: rgba(59, 130, 246, 0.2);
			box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
		}

		.verification-header {
			display: flex;
			align-items: center;
			font-weight: 600;
			color: #374151;
			margin-bottom: 15px;
			font-size: 1.1rem;
		}

		/* Dark mode styles for verification */
		body.dark-mode .verification-section {
			background: linear-gradient(135deg, rgba(96, 165, 250, 0.05), rgba(34, 197, 94, 0.05));
			border-color: rgba(96, 165, 250, 0.1);
		}

		body.dark-mode .verification-header {
			color: #e5e7eb;
		}

		.form-label {
			display: block;
			font-weight: 600;
			color: #374151;
			margin-bottom: 8px;
			font-size: 1rem;
			font-family: "Times New Roman", Times, serif;
		}

		.form-control {
			width: 100%;
			padding: 18px 20px;
			border: 2px solid #e5e7eb;
			border-radius: 12px;
			font-size: 1rem;
			font-family: "Times New Roman", Times, serif;
			transition: all 0.3s ease;
			background: #f8fafc;
		}
		
		.form-control::placeholder {
			font-family: "Times New Roman", Times, serif;
			font-size: 1rem;
		}

		.form-control:focus {
			outline: none;
			border-color: #3b82f6;
			background: white;
			box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
		}

		.input-group {
			position: relative;
		}

		.input-icon {
			position: absolute;
			left: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: #9ca3af;
			font-size: 1.1rem;
		}

		.password-toggle {
			position: absolute;
			right: 18px;
			top: 50%;
			transform: translateY(-50%);
			color: #6b7280;
			font-size: 1.1rem;
			cursor: pointer;
			z-index: 2;
			transition: color 0.3s ease;
		}

		.password-toggle:hover {
			color: var(--gg-teal);
		}

		.form-control.with-icon {
			padding-left: 55px;
		}

		.register-btn {
			width: 100%;
			background: linear-gradient(135deg, #3b82f6, #1e40af);
			color: white;
			border: none;
			padding: 18px;
			border-radius: 12px;
			font-size: 1.2rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.register-btn:disabled {
			background: linear-gradient(135deg, #9ca3af, #6b7280);
			cursor: not-allowed;
			transform: none;
			box-shadow: none;
		}

		.register-btn:hover:not(:disabled) {
			transform: translateY(-2px);
			box-shadow: 0 12px 30px rgba(59, 130, 246, 0.4);
		}

		.register-btn:active:not(:disabled) {
			transform: translateY(0);
		}

		.form-links {
			display: flex;
			justify-content: center;
			align-items: center;
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #e5e7eb;
		}

		.login-link {
			color: #3b82f6;
			text-decoration: none;
			font-weight: 600;
		}

		.login-link:hover {
			text-decoration: underline;
		}

		.alert {
			border-radius: 12px;
			margin-bottom: 20px;
			border: none;
			padding: 15px 18px;
		}

		.alert-danger {
			background: #fee2e2;
			color: #dc2626;
		}

		.alert-success {
			background: #d1fae5;
			color: #059669;
		}

		/* Mobile Responsive */
		@media (max-width: 768px) {
			.main-header .container-fluid {
				padding: 0 20px !important;
			}

			.search-container {
				max-width: 300px;
			}

			.tech-revival-section {
				display: none;
			}

			.auth-container {
				height: auto;
				min-height: 600px;
				margin: 20px;
				flex-direction: column;
			}

			.auth-panels {
				width: 100%;
				height: auto;
				min-height: 600px;
				flex-direction: column;
			}

			.welcome-panel,
			.form-panel {
				flex: 0 0 100%;
				min-height: 500px;
			}

			.welcome-panel {
				border-top-left-radius: 25px;
				border-top-right-radius: 0;
				border-bottom-left-radius: 25px;
				border-bottom-right-radius: 25px;
				padding: 50px 30px;
			}

			.form-panel {
				padding: 40px 30px;
			}

			.welcome-title {
				font-size: 2rem;
			}

			.form-title {
				font-size: 1.7rem;
			}

			.nav-items {
				display: none;
			}

			.nav-menu {
				justify-content: flex-start;
				padding: 0 20px;
			}
		}

		/* User Interface Styles - Same as index */
		.user-actions {
			display: flex;
			align-items: center;
			gap: 11px;
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

		/* Language and Theme Toggle Styles */
		.language-selector,
		.theme-toggle {
			display: flex;
			align-items: center;
			justify-content: space-between;
			width: 100%;
		}

		.toggle-switch {
			position: relative;
			width: 40px;
			height: 20px;
			background: #cbd5e0;
			border-radius: 10px;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.toggle-switch.active {
			background: #008060;
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
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
		}

		.toggle-switch.active .toggle-slider {
			transform: translateX(20px);
		}

		/* Dark Mode Styles */
		body.dark-mode {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
			color: #e2e8f0;
		}

		body.dark-mode .promo-banner {
			background: #0f1419 !important;
		}

		body.dark-mode .main-header {
			background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
			border-bottom-color: #4a5568;
		}

		body.dark-mode .main-nav {
			background: #2d3748;
			border-bottom-color: #4a5568;
		}

		body.dark-mode .logo,
		body.dark-mode .tech-revival-text,
		body.dark-mode .contact-number {
			color: #e2e8f0;
		}

		body.dark-mode .search-input {
			background: #374151;
			border-color: #4a5568;
			color: #e2e8f0;
		}

		body.dark-mode .search-input::placeholder {
			color: #9ca3af;
		}

		body.dark-mode .search-input:focus {
			background: #4a5568;
			border-color: #60a5fa;
		}

		body.dark-mode .categories-button {
			background: linear-gradient(135deg, #374151, #1f2937);
		}

		body.dark-mode .categories-button:hover {
			background: linear-gradient(135deg, #4a5568, #374151);
		}

		body.dark-mode .nav-item a {
			color: #e2e8f0;
		}

		body.dark-mode .nav-item a:hover {
			color: #60a5fa;
		}

		body.dark-mode .brands-dropdown {
			background: rgba(45, 55, 72, 0.95);
			border-color: rgba(74, 85, 104, 0.5);
		}

		body.dark-mode .brands-dropdown h4 {
			color: #e2e8f0;
		}

		body.dark-mode .brands-dropdown a {
			color: #cbd5e0;
		}

		body.dark-mode .brands-dropdown a:hover {
			background: rgba(74, 85, 104, 0.3);
			color: #60a5fa;
		}

		body.dark-mode .header-icon {
			color: #e2e8f0;
		}

		body.dark-mode .header-icon:hover {
			background: rgba(74, 85, 104, 0.3);
		}

		body.dark-mode .dropdown-menu-custom {
			background: rgba(45, 55, 72, 0.95);
			border-color: rgba(74, 85, 104, 0.5);
		}

		body.dark-mode .dropdown-item-custom {
			color: #cbd5e0;
		}

		body.dark-mode .dropdown-item-custom:hover {
			background: rgba(74, 85, 104, 0.3);
			color: #60a5fa;
		}

		/* Dark Mode Form Styles */
		body.dark-mode .login-page-container {
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
		}

		body.dark-mode .form-label {
			color: #e2e8f0;
		}

		body.dark-mode .form-control {
			background: #374151;
			border-color: #4a5568;
			color: #e2e8f0;
		}

		body.dark-mode .form-control::placeholder {
			color: #9ca3af;
		}

		body.dark-mode .form-control:focus {
			background: #4a5568;
			border-color: #60a5fa;
			box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
		}

		body.dark-mode .input-icon {
			color: #cbd5e0;
		}

		body.dark-mode .form-label {
			color: #e2e8f0;
		}

		body.dark-mode .form-control {
			background: #374151;
			border-color: #4a5568;
			color: #e2e8f0;
		}

		body.dark-mode .form-control::placeholder {
			color: #9ca3af;
		}

		body.dark-mode .form-control:focus {
			background: #4a5568;
			border-color: #60a5fa;
			box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
		}

		body.dark-mode .input-icon {
			color: #cbd5e0;
		}

		body.dark-mode .alert-danger {
			background: #374151;
			border: 1px solid #ef4444;
			color: #fca5a5;
		}

		body.dark-mode .alert-success {
			background: #374151;
			border: 1px solid #10b981;
			color: #86efac;
		}

		/* Dark Mode Checkbox Styles */
		body.dark-mode .checkbox-group {
			background: rgba(96, 165, 250, 0.05);
			border-color: rgba(96, 165, 250, 0.1);
		}

		body.dark-mode .checkbox-group:hover {
			background: rgba(96, 165, 250, 0.1);
			border-color: rgba(96, 165, 250, 0.2);
		}

		body.dark-mode .custom-checkbox {
			border-color: #4a5568;
			background: #374151;
		}

		body.dark-mode .checkbox-label {
			color: #e2e8f0;
		}
	</style>
</head>

<body>
	<!-- Promotional Banner -->
	<div class="promo-banner">
		<div class="promo-banner-left">
			<i class="fas fa-bolt"></i>
		</div>
		<div class="promo-banner-center">
			<span class="promo-text">BLACK FRIDAY DEALS STOREWIDE! SHOP AMAZING DISCOUNTS!</span>
			<span class="promo-timer" id="promoTimer">12d:00h:00m:00s</span>
		</div>
		<a href="../index.php#flash-deals" class="promo-shop-link">Shop Now</a>
	</div>

	<!-- Main Header -->
	<header class="main-header animate__animated animate__fadeInDown">
		<div class="container-fluid" style="padding: 0 40px;">
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

				<!-- Simple Actions for Register Page -->
				<div class="user-actions" style="display: flex; align-items: center; gap: 18px;">
					<span style="color: #ddd; font-size: 1.5rem; margin: 0 5px;">|</span>
					<div style="display: flex; align-items: center; gap: 15px;">
						<a href="login.php" style="color: #f8fafc; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px;">
							<i class="fas fa-sign-in-alt"></i>
							<span>Login</span>
						</a>
						<a href="../index.php" style="color: #f8fafc; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px;">
							<i class="fas fa-home"></i>
							<span>Home</span>
						</a>
					</div>
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
						<span>SHOP BY BRANDS</span>
						<i class="fas fa-chevron-down"></i>
					</button>
					<div class="brands-dropdown" id="shopDropdown">
						<h4>All Brands</h4>
						<ul>
							<li><a href="../views/all_product.php?brand=Apple"><i class="fas fa-tag"></i> Apple</a></li>
							<li><a href="../views/all_product.php?brand=Samsung"><i class="fas fa-tag"></i> Samsung</a></li>
							<li><a href="../views/all_product.php?brand=HP"><i class="fas fa-tag"></i> HP</a></li>
							<li><a href="../views/all_product.php?brand=Dell"><i class="fas fa-tag"></i> Dell</a></li>
							<li><a href="../views/all_product.php?brand=Sony"><i class="fas fa-tag"></i> Sony</a></li>
							<li><a href="../views/all_product.php?brand=Canon"><i class="fas fa-tag"></i> Canon</a></li>
							<li><a href="../views/all_product.php?brand=Nikon"><i class="fas fa-tag"></i> Nikon</a></li>
							<li><a href="#"><i class="fas fa-tag"></i> Microsoft</a></li>
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
									<a href="../views/mobile_devices.php" style="text-decoration: none; color: inherit;">
										<span data-translate="mobile_devices">Mobile Devices</span>
									</a>
								</h4>
								<ul>
									<li><a href="../all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
									<li><a href="../all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="../views/computing.php" style="text-decoration: none; color: inherit;">
										<span data-translate="computing">Computing</span>
									</a>
								</h4>
								<ul>
									<li><a href="../all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
									<li><a href="../all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column">
								<h4>
									<a href="../views/photography_video.php" style="text-decoration: none; color: inherit;">
										<span data-translate="photography_video">Photography & Video</span>
									</a>
								</h4>
								<ul>
									<li><a href="../all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
									<li><a href="../all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
								</ul>
							</div>
							<div class="dropdown-column featured">
								<h4>Shop All</h4>
								<div class="featured-item">
									<img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=120&h=80&fit=crop&crop=center" alt="New Arrivals">
									<div class="featured-text">
										<strong>New Arrivals</strong>
										<p>Latest tech gadgets</p>
										<a href="../views/all_product.php" class="shop-now-btn">Shop </a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<a href="../views/repair_services.php" class="nav-item"><span data-translate="repair_studio">REPAIR STUDIO</span></a>
				<a href="../views/device_drop.php" class="nav-item"><span data-translate="device_drop">DEVICE DROP</span></a>

				<!-- More Dropdown -->
				<div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
					<a href="#" class="nav-item">
						<span data-translate="more">MORE</span>
						<i class="fas fa-chevron-down"></i>
					</a>
					<div class="simple-dropdown" id="moreDropdown">
						<ul>
							<li><a href="../views/contact.php"><i class="fas fa-phone"></i> Contact</a></li>
							<li><a href="../views/terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
						</ul>
					</div>
				</div>

				<!-- Flash Deal positioned at far right -->
				<a href="../views/flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
			</div>
		</div>
	</nav>

	<!-- Register Form Section -->
	<div class="login-page-container">
		<div class="auth-container">
			<div class="auth-panels" id="authPanels">

				<!-- Welcome Panel (Teal/Green) - LEFT SIDE -->
				<div class="welcome-panel">
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/ChatGPT_Image_Nov_19__2025__11_50_42_PM-removebg-preview.png"
						alt="Gadget Garage Logo" class="brand-logo">
					<h1 class="welcome-title" id="welcomeTitle">Hello!</h1>
					<p class="welcome-message" id="welcomeMessage">Unlock exclusive deals and tech discoveries!</p>
					<a href="login.php" class="welcome-signin-btn" id="welcomeSigninBtn">Log In</a>
					<p class="welcome-button-message" id="welcomeButtonMessage">Log in to your existing account.</p>
				</div>

				<!-- Form Panel (White) - RIGHT SIDE -->
				<div class="form-panel">
					<div class="form-container">
						<div class="form-header">
							<h2 class="form-title" id="formTitle">Join Gadget Garage Today!</h2>
						</div>

						<p style="text-align: center; color: var(--text-light); font-size: 1rem; margin-bottom: 20px;">Sign up with socials</p>

						<!-- Social Login Buttons -->
						<div class="social-login">
							<div class="social-buttons">
								<div class="social-btn google">
									<span style="font-weight: 700; color: #ea4335;">G</span>
								</div>
								<div class="social-btn facebook">
									<span style="font-weight: 700; color: #1877f2;">f</span>
								</div>
								<div class="social-btn pinterest">
									<span style="font-weight: 700; color: #bd081c;">P</span>
								</div>
								<div class="social-btn linkedin">
									<span style="font-weight: 700; color: #0077b5;">in</span>
								</div>
							</div>
							<div class="divider">
								<span>OR</span>
							</div>
						</div>

						<!-- Signup Form -->
						<div id="signupForm" class="form-content" style="display: block;">
							<form method="POST" id="registerForm">
								<div class="form-group">
									<label for="name" class="form-label">Full Name</label>
									<div class="input-group">
										<i class="fas fa-user input-icon"></i>
										<input type="text"
											id="name"
											name="name"
											class="form-control with-icon"
											placeholder="       Enter your full name"
											value=""
											required>
									</div>
								</div>

								<div class="form-group">
									<label for="email" class="form-label">Email</label>
									<div class="input-group">
										<i class="fas fa-envelope input-icon"></i>
										<input type="email"
											id="email"
											name="email"
											class="form-control with-icon"
											placeholder="Enter your email"
											value=""
											required>
									</div>
								</div>

								<div class="form-group">
									<label for="phone_number" class="form-label">Phone Number</label>
									<div class="input-group">
										<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3Crect width='300' height='67' fill='%23CE1126'/%3E%3Crect y='67' width='300' height='67' fill='%23FCD116'/%3E%3Crect y='133' width='300' height='67' fill='%23006B3F'/%3E%3Cpolygon points='150,80 160,110 190,110 170,130 180,160 150,140 120,160 130,130 110,110 140,110' fill='%23000'/%3E%3C/svg%3E" alt="Ghana Flag" class="ghana-flag">
										<input type="tel"
											id="phone_number"
											name="phone_number"
											class="form-control with-flag"
											placeholder="          Phone number"
											value=""
											required>
									</div>
								</div>

								<div class="form-group">
									<label for="country" class="form-label">Country</label>
									<div class="input-group">
										<i class="fas fa-globe input-icon"></i>
										<select id="country" name="country" class="form-control with-icon" required>
											<option value="">Select Country</option>
											<option value="Ghana" selected>Ghana</option>
											<option value="Nigeria">Nigeria</option>
											<option value="USA">United States</option>
											<option value="UK">United Kingdom</option>
											<option value="Canada">Canada</option>
											<option value="Australia">Australia</option>
										</select>
									</div>
								</div>

								<div class="form-group">
									<label for="city" class="form-label">City</label>
									<div class="input-group">
										<i class="fas fa-map-marker-alt input-icon"></i>
										<input type="text"
											id="city"
											name="city"
											class="form-control with-icon"
											placeholder="       Enter your city"
											value=""
											required>
									</div>
								</div>

								<input type="hidden" name="role" value="1">

								<div class="form-group">
									<label for="password" class="form-label">Password</label>
									<div class="input-group">
										<i class="fas fa-lock input-icon"></i>
										<input type="password"
											id="password"
											name="password"
											class="form-control with-icon"
											placeholder="Create a password"
											required>
										<i class="fas fa-eye password-toggle" id="passwordToggle" onclick="togglePassword()"></i>
									</div>
								</div>

								<button type="submit" class="submit-btn">
									SIGN UP
								</button>
							</form>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Footer spacer -->
	<div style="height: 100px;"></div>

	<!-- Scripts -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
	<script>
		// Dropdown functions
		let dropdownTimeout;

		function showDropdown() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				clearTimeout(dropdownTimeout);
				dropdown.style.opacity = '1';
				dropdown.style.visibility = 'visible';
				dropdown.style.transform = 'translateY(0)';
			}
		}

		function hideDropdown() {
			const dropdown = document.getElementById('shopDropdown');
			if (dropdown) {
				clearTimeout(dropdownTimeout);
				dropdownTimeout = setTimeout(() => {
					dropdown.style.opacity = '0';
					dropdown.style.visibility = 'hidden';
					dropdown.style.transform = 'translateY(-10px)';
				}, 300);
			}
		}

		// Timer functionality
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

		// User dropdown functionality
		function toggleUserDropdown() {
			const dropdown = document.getElementById('userDropdownMenu');
			dropdown.classList.toggle('show');
		}

		// Close dropdown when clicking outside
		document.addEventListener('click', function(event) {
			const dropdown = document.getElementById('userDropdownMenu');
			const avatar = document.querySelector('.user-avatar');

			if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
				dropdown.classList.remove('show');
			}
		});

		// Account page navigation
		function goToAccount() {
			window.location.href = '../views/my_orders.php';
		}

		// Language change functionality
		function changeLanguage(lang) {
			// Language change functionality can be implemented here
			console.log('Language changed to:', lang);
		}

		// Theme toggle functionality
		function toggleTheme() {
			const toggleSwitch = document.getElementById('themeToggle');
			const body = document.body;

			body.classList.toggle('dark-mode');
			toggleSwitch.classList.toggle('active');

			// Save theme preference to localStorage
			const isDarkMode = body.classList.contains('dark-mode');
			localStorage.setItem('darkMode', isDarkMode);
		}

		// Load theme preference on page load
		document.addEventListener('DOMContentLoaded', function() {
			const isDarkMode = localStorage.getItem('darkMode') === 'true';
			const toggleSwitch = document.getElementById('themeToggle');

			if (isDarkMode) {
				document.body.classList.add('dark-mode');
				if (toggleSwitch) {
					toggleSwitch.classList.add('active');
				}
			}
		});

		// Shop Dropdown Functions
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

		// Password toggle functionality
		function togglePassword() {
			const passwordField = document.getElementById('password');
			const toggleIcon = document.getElementById('passwordToggle');

			if (passwordField.type === 'password') {
				passwordField.type = 'text';
				toggleIcon.classList.remove('fa-eye');
				toggleIcon.classList.add('fa-eye-slash');
			} else {
				passwordField.type = 'password';
				toggleIcon.classList.remove('fa-eye-slash');
				toggleIcon.classList.add('fa-eye');
			}
		}

		// Handle signup form submission
		document.addEventListener('DOMContentLoaded', function() {
			console.log('DOM Content Loaded');
			const signupForm = document.getElementById('registerForm');
			console.log('Signup form element:', signupForm);

			if (signupForm) {
				console.log('Signup form found, attaching event listener');

				// Also add click handler to submit button as fallback
				const submitBtn = signupForm.querySelector('.submit-btn');
				if (submitBtn) {
					console.log('Submit button found:', submitBtn);
					submitBtn.addEventListener('click', function(e) {
						console.log('Submit button clicked');
						if (submitBtn.type !== 'submit') {
							e.preventDefault();
							signupForm.dispatchEvent(new Event('submit'));
						}
					});
				} else {
					console.log('ERROR: Submit button not found!');
				}

				signupForm.addEventListener('submit', async function(e) {
					e.preventDefault();
					console.log('Form submit event triggered');

					// Remove any existing error/success messages
					const existingAlerts = signupForm.parentNode.querySelectorAll('.alert');
					existingAlerts.forEach(alert => alert.remove());

					const formData = new FormData(signupForm);
					const submitBtn = signupForm.querySelector('.submit-btn');
					const originalBtnText = submitBtn.textContent;

					// Debug: Log form data
					console.log('Form data being sent:');
					for (let [key, value] of formData.entries()) {
						console.log(key, value);
					}

					// Disable button
					submitBtn.disabled = true;
					submitBtn.textContent = 'Signing Up...';

					try {
						console.log('Sending fetch request to: ../actions/register_user_action.php');
						const response = await fetch('../actions/register_user_action.php', {
							method: 'POST',
							body: formData
						});

						console.log('Response status:', response.status);
						console.log('Response headers:', response.headers);

						const responseText = await response.text();
						console.log('Raw response:', responseText);

						let result;
						try {
							result = JSON.parse(responseText);
						} catch (parseError) {
							console.error('JSON parse error:', parseError);
							throw new Error('Invalid JSON response: ' + responseText);
						}

						if (result.status === 'success') {
							// Show success message
							const alertDiv = document.createElement('div');
							alertDiv.className = 'alert alert-success animate__animated animate__fadeInUp';
							alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + result.message;
							signupForm.parentNode.insertBefore(alertDiv, signupForm);

							// Redirect to login after 2 seconds
							setTimeout(() => {
								window.location.href = 'login.php';
							}, 2000);
						} else {
							// Show error message (only one)
							const alertDiv = document.createElement('div');
							alertDiv.className = 'alert alert-danger';
							alertDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (result.message || 'Registration failed. Please try again.');
							signupForm.parentNode.insertBefore(alertDiv, signupForm);
							submitBtn.disabled = false;
							submitBtn.textContent = originalBtnText;
						}
					} catch (error) {
						console.error('Signup error:', error);
						const alertDiv = document.createElement('div');
						alertDiv.className = 'alert alert-danger';
						alertDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error: ' + error.message;
						signupForm.parentNode.insertBefore(alertDiv, signupForm);
						submitBtn.disabled = false;
						submitBtn.textContent = originalBtnText;
					}
				});
			} else {
				console.log('ERROR: Signup form not found!');
			}
		});
	</script>
</body>

</html>