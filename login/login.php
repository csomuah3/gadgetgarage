<?php
session_start();
require_once '../settings/db_class.php';

// Handle login
$login_error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = 'Please enter a valid email address.';
    } else {
        $db = new db_connection();

        $email_escaped = mysqli_real_escape_string($db->db_conn(), $email);
        $sql = "SELECT * FROM customer WHERE customer_email = '$email_escaped'";

        $user = $db->db_fetch_one($sql);

        if ($user && password_verify($password, $user['customer_pass'])) {
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['customer_name'];
            $_SESSION['user_email'] = $user['customer_email'];
            $_SESSION['email'] = $user['customer_email'];
            $_SESSION['role'] = $user['user_role'];
            $_SESSION['name'] = $user['customer_name'];

            $login_success = true;
        } else {
            $login_error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Login - Gadget Garage</title>
	<meta name="description" content="Log in to your Gadget Garage account to access premium tech devices and exclusive deals.">

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<link href="../includes/header-styles.css" rel="stylesheet">

	<style>
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

		body {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
			color: #1a1a1a;
			overflow-x: hidden;
			margin: 0;
			padding: 0;
		}

		/* Promotional Banner Styles */
		.promo-banner {
			background: #001f3f !important;
			color: white;
			padding: 6px 15px;
			text-align: center;
			font-size: 1.4rem;
			font-weight: 700;
			position: sticky;
			top: 0;
			z-index: 1001;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
			height: 38px;
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
			font-size: 1.5rem;
		}

		.promo-banner .promo-text {
			font-size: 1.65rem;
			font-weight: 700;
			letter-spacing: 0.5px;
		}

		.promo-timer {
			background: transparent;
			padding: 0;
			border-radius: 0;
			font-size: 1.65rem;
			font-weight: 700;
			color: #FFD700;
			letter-spacing: 1px;
		}

		.promo-shop-link {
			color: #FFD700;
			text-decoration: none;
			font-weight: 700;
			font-size: 1.4rem;
			transition: color 0.3s ease;
		}

		.promo-shop-link:hover {
			color: white;
		}

		/* Header Styles */
		.main-header {
			background: #ffffff;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
			position: sticky;
			top: 38px;
			z-index: 1000;
			padding: 16px 0;
			border-bottom: 1px solid #e5e7eb;
		}

		.logo img {
			height: 45px;
			width: auto;
			object-fit: contain;
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
			box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
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
			font-size: 2.8rem;
			color: #008060;
			transition: transform 0.3s ease;
		}

		.tech-revival-icon:hover {
			transform: rotate(15deg) scale(1.1);
		}

		.tech-revival-text {
			font-size: 1.9rem;
			font-weight: 800;
			color: #1f2937;
			margin: 0;
			letter-spacing: 0.5px;
			line-height: 1.3;
		}

		.ghana-flag {
			font-size: 2.2rem;
			margin-left: 10px;
			margin-right: 6px;
			vertical-align: middle;
			display: inline-block;
			animation: wave 2s ease-in-out infinite;
		}

		@keyframes wave {
			0%, 100% {
				transform: rotate(0deg);
			}
			25% {
				transform: rotate(-5deg);
			}
			75% {
				transform: rotate(5deg);
			}
		}

		.contact-number {
			font-size: 1rem;
			font-weight: 600;
			color: #008060;
			margin: 0;
			margin-top: 4px;
		}

		.user-actions {
			display: flex;
			align-items: center;
			gap: 12px;
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
		}

		.nav-item:hover {
			color: #008060;
			background: rgba(0, 128, 96, 0.1);
		}

		.shop-categories-btn {
			position: relative;
			margin-right: 20px;
		}

		.categories-button {
			background: #008060;
			color: white;
			border: none;
			padding: 12px 20px;
			border-radius: 8px;
			font-weight: 500;
			display: flex;
			align-items: center;
			gap: 8px;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.categories-button:hover {
			background: #006b4e;
		}

		.flash-deal {
			color: #dc2626 !important;
			font-weight: 700;
			margin-left: auto;
		}

		.flash-deal:hover {
			color: #991b1b !important;
		}

		/* Hide header and navigation for clean login page */
		.promo-banner, .main-header, .main-nav {
			display: none;
		}

		/* Full screen login section */
		.login-section {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
			position: relative;
			background: #667eea;
		}

		/* Circuit Board Background */
		.login-section::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background:
				/* Main circuit lines */
				linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px),
				linear-gradient(0deg, rgba(255,255,255,0.1) 1px, transparent 1px),
				/* Secondary lines */
				linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px),
				linear-gradient(0deg, rgba(255,255,255,0.05) 1px, transparent 1px),
				/* Circuit nodes */
				radial-gradient(circle at 25% 25%, rgba(255,255,255,0.3) 2px, transparent 2px),
				radial-gradient(circle at 75% 75%, rgba(255,255,255,0.3) 2px, transparent 2px),
				radial-gradient(circle at 25% 75%, rgba(255,255,255,0.2) 1.5px, transparent 1.5px),
				radial-gradient(circle at 75% 25%, rgba(255,255,255,0.2) 1.5px, transparent 1.5px),
				/* Connecting circuits */
				linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.08) 50%, transparent 60%),
				linear-gradient(-45deg, transparent 40%, rgba(255,255,255,0.08) 50%, transparent 60%);
			background-size:
				60px 60px, 60px 60px,
				20px 20px, 20px 20px,
				120px 120px, 120px 120px, 120px 120px, 120px 120px,
				200px 200px, 200px 200px;
			background-position:
				0 0, 0 0,
				30px 30px, 30px 30px,
				0 0, 60px 60px, 0 60px, 60px 0,
				0 0, 100px 100px;
			animation: circuitFlow 20s linear infinite;
			opacity: 0.7;
		}

		@keyframes circuitFlow {
			0% {
				background-position:
					0 0, 0 0,
					30px 30px, 30px 30px,
					0 0, 60px 60px, 0 60px, 60px 0,
					0 0, 100px 100px;
			}
			100% {
				background-position:
					60px 60px, 60px 60px,
					50px 50px, 50px 50px,
					120px 120px, 180px 180px, 120px 180px, 180px 120px,
					200px 200px, 300px 300px;
			}
		}

		/* Main Login Container */
		.login-container {
			background: rgba(255, 255, 255, 0.98);
			backdrop-filter: blur(20px);
			border-radius: 24px;
			padding: 60px 50px;
			box-shadow:
				0 32px 64px rgba(0, 0, 0, 0.2),
				0 0 0 1px rgba(255, 255, 255, 0.1),
				inset 0 1px 0 rgba(255, 255, 255, 0.9);
			max-width: 480px;
			width: 100%;
			position: relative;
			z-index: 10;
			border: 1px solid rgba(255, 255, 255, 0.2);
		}

		/* Logo Section */
		.logo-section {
			text-align: center;
			margin-bottom: 40px;
		}

		.logo-section img {
			height: 80px;
			width: auto;
			margin-bottom: 20px;
			filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
		}

		.welcome-text {
			font-size: 28px;
			font-weight: 700;
			color: #2d3748;
			margin-bottom: 8px;
			letter-spacing: -0.5px;
		}

		.subtitle-text {
			font-size: 16px;
			color: #718096;
			margin-bottom: 0;
		}

		/* Form Styles */
		.form-container {
			margin-top: 32px;
		}

		.form-group {
			margin-bottom: 24px;
		}

		.form-input {
			width: 100%;
			height: 56px;
			border: 2px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px 20px;
			font-size: 16px;
			font-weight: 500;
			color: #2d3748;
			background: #ffffff;
			transition: all 0.3s ease;
			outline: none;
		}

		.form-input::placeholder {
			color: #a0aec0;
			font-weight: 400;
		}

		.form-input:focus {
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}

		.login-button {
			width: 100%;
			height: 56px;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			border: none;
			border-radius: 12px;
			color: white;
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			margin-top: 8px;
			position: relative;
			overflow: hidden;
		}

		.login-button::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
			transition: left 0.6s ease;
		}

		.login-button:hover {
			transform: translateY(-2px);
			box-shadow: 0 12px 24px rgba(102, 126, 234, 0.3);
		}

		.login-button:hover::before {
			left: 100%;
		}

		.login-button:active {
			transform: translateY(0);
		}

		/* Links */
		.forgot-link {
			text-align: right;
			margin-top: 8px;
		}

		.forgot-link a {
			color: #667eea;
			text-decoration: none;
			font-size: 14px;
			font-weight: 500;
		}

		.forgot-link a:hover {
			text-decoration: underline;
		}

		.signup-link {
			text-align: center;
			margin-top: 32px;
			padding-top: 24px;
			border-top: 1px solid #e2e8f0;
		}

		.signup-link a {
			color: #667eea;
			text-decoration: none;
			font-weight: 600;
		}

		.signup-link a:hover {
			text-decoration: underline;
		}

		/* Alerts */
		.alert {
			border-radius: 12px;
			margin-bottom: 24px;
			border: none;
		}

		.alert-danger {
			background: #fed7d7;
			color: #9b2c2c;
		}

		.alert-success {
			background: #c6f6d5;
			color: #276749;
		}

		/* Responsive Design */
		@media (max-width: 768px) {
			.login-container {
				padding: 40px 30px;
				margin: 20px;
			}

			.logo-section img {
				height: 60px;
			}

			.welcome-text {
				font-size: 24px;
			}
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
			<span class="promo-text">BLACK FRIDAY DEALS! ON ORDERS OVER GH₵2,000!</span>
			<span class="promo-timer" id="promoTimer">12d:00h:00m:00s</span>
		</div>
		<a href="#flash-deals" class="promo-shop-link">Shop Now</a>
	</div>

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
							<p class="tech-revival-text">Bring Retired Tech <span class="ghana-flag">🇬🇭</span> Ghana Store</p>
							<p class="contact-number">055-138-7578</p>
						</div>
					</div>
				</div>

				<!-- User Actions - Far Right -->
				<div class="user-actions" style="display: flex; align-items: center; gap: 12px;">
					<span style="color: #ddd;">|</span>
					<a href="../register.php" class="login-btn me-2">Register</a>
					<span style="color: #008060; font-weight: 600;">Login</span>
				</div>
			</div>
		</div>
	</header>

	<!-- Main Navigation -->
	<nav class="main-nav">
		<div class="container-fluid px-0">
			<div class="nav-menu">
				<!-- Shop by Brands Button -->
				<div class="shop-categories-btn">
					<button class="categories-button">
						<i class="fas fa-tags"></i>
						<span>SHOP BY BRANDS</span>
						<i class="fas fa-chevron-down"></i>
					</button>
				</div>

				<a href="../index.php" class="nav-item"><span>HOME</span></a>
				<a href="../all_product.php" class="nav-item"><span>SHOP</span></a>
				<a href="../repair_services.php" class="nav-item"><span>REPAIR STUDIO</span></a>
				<a href="../device_drop.php" class="nav-item"><span>DEVICE DROP</span></a>
				<a href="../contact.php" class="nav-item"><span>MORE</span></a>

				<!-- Flash Deal positioned at far right -->
				<a href="../flash_deals.php" class="nav-item flash-deal">⚡ <span>FLASH DEAL</span></a>
			</div>
		</div>
	</nav>

	<!-- Login Form Section -->
	<section class="login-section">
		<div class="login-container" id="loginContainer">
			<!-- Logo Section -->
			<div class="logo-section">
				<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
				     alt="Gadget Garage">
				<h1 class="welcome-text">Welcome Back</h1>
				<p class="subtitle-text">Sign in to your account to continue</p>
			</div>

			<!-- Alert Messages -->
			<?php if (!empty($login_error)): ?>
				<div class="alert alert-danger" role="alert">
					<i class="fas fa-exclamation-triangle me-2"></i><?php echo $login_error; ?>
				</div>
			<?php endif; ?>

			<?php if ($login_success): ?>
				<div class="alert alert-success" role="alert">
					<i class="fas fa-check-circle me-2"></i>Login successful! Redirecting...
				</div>
				<script>
					setTimeout(function() {
						window.location.href = '../index.php';
					}, 1500);
				</script>
			<?php else: ?>

			<!-- Form Container -->
			<div class="form-container">
				<form method="POST" id="loginForm">
					<div class="form-group">
						<input type="email"
						       class="form-input"
						       name="email"
						       placeholder="Email address"
						       required
						       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
					</div>

					<div class="form-group">
						<input type="password"
						       class="form-input"
						       name="password"
						       placeholder="Password"
						       required>
						<div class="forgot-link">
							<a href="#">Forgot your password?</a>
						</div>
					</div>

					<button type="submit" class="login-button" id="loginBtn">
						Sign In
					</button>
				</form>

				<div class="signup-link">
					Don't have an account? <a href="../register.php">Sign up</a>
				</div>
			</div>

			<?php endif; ?>
		</div>
	</section>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="../js/header.js"></script>

	<script>
		// Handle form submission with enhanced animations
		document.getElementById('loginForm').addEventListener('submit', function(e) {
			const submitBtn = document.getElementById('loginBtn');
			const btnText = submitBtn.querySelector('.btn-text');
			const btnLoading = submitBtn.querySelector('.btn-loading');

			// Add loading state with animation
			submitBtn.classList.add('loading');
			submitBtn.disabled = true;

			// Smooth transition to loading state
			btnText.style.opacity = '0';
			btnText.style.transform = 'translateY(-10px)';

			setTimeout(() => {
				btnText.style.display = 'none';
				btnLoading.style.display = 'inline-flex';
				btnLoading.style.opacity = '0';
				btnLoading.style.transform = 'translateY(10px)';

				// Animate loading text in
				setTimeout(() => {
					btnLoading.style.opacity = '1';
					btnLoading.style.transform = 'translateY(0)';
				}, 50);
			}, 200);
		});
	</script>
</body>
</html>