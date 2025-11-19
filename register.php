<?php
session_start();
require_once 'settings/db_class.php';

// Handle registration
$registration_error = '';
$registration_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $registration_error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $registration_error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $registration_error = 'Passwords do not match.';
    } else {
        $db = new db_connection();

        // Check if user already exists
        $email_escaped = mysqli_real_escape_string($db->db_conn(), $email);
        $check_sql = "SELECT customer_id FROM customer WHERE customer_email = '$email_escaped'";
        $existing_user = $db->db_fetch_one($check_sql);

        if ($existing_user) {
            $registration_error = 'An account with this email already exists.';
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $first_name_escaped = mysqli_real_escape_string($db->db_conn(), $first_name);
            $last_name_escaped = mysqli_real_escape_string($db->db_conn(), $last_name);
            $full_name = $first_name . ' ' . $last_name;
            $full_name_escaped = mysqli_real_escape_string($db->db_conn(), $full_name);

            $insert_sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_contact, user_role, customer_country, customer_city, customer_address, date_created)
                          VALUES ('$full_name_escaped', '$email_escaped', '$hashed_password', '', 1, 'Ghana', 'Accra', '', NOW())";

            if ($db->db_write_query($insert_sql)) {
                // Auto-login the user
                $user_id = $db->last_insert_id();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 1;
                $_SESSION['name'] = $full_name;

                $registration_success = true;
            } else {
                $registration_error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Register - Gadget Garage</title>
	<meta name="description" content="Create your Gadget Garage account to access premium tech devices and exclusive deals.">

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link rel="shortcut icon" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<link href="includes/header-styles.css" rel="stylesheet">

	<style>
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

		body {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
			background-color: #ffffff;
			color: #1a1a1a;
			overflow-x: hidden;
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

		/* Registration Form Section */
		.register-section {
			min-height: calc(100vh - 200px);
			background: #f0f2f5;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 40px 20px;
		}

		/* Registration Card */
		.register-card {
			background: #87ceeb;
			background-image:
				linear-gradient(90deg, rgba(255,255,255,0.15) 1px, transparent 1px),
				linear-gradient(rgba(255,255,255,0.15) 1px, transparent 1px),
				radial-gradient(circle at 20% 20%, rgba(255,255,255,0.3) 2px, transparent 2px),
				radial-gradient(circle at 80% 80%, rgba(255,255,255,0.3) 2px, transparent 2px);
			background-size: 40px 40px, 40px 40px, 80px 80px, 80px 80px;
			background-position: 0 0, 0 0, 0 0, 40px 40px;
			animation: circuitFlow 15s linear infinite;
			border-radius: 15px;
			padding: 40px;
			box-shadow: 0 15px 35px rgba(0,0,0,0.1);
			max-width: 500px;
			width: 100%;
			position: relative;
			overflow: hidden;
		}

		.register-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background:
				linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.1) 50%, transparent 60%),
				radial-gradient(circle at 30% 70%, rgba(255,255,255,0.2) 0%, transparent 50%);
			animation: circuitPulse 8s ease-in-out infinite alternate;
			pointer-events: none;
		}

		.register-card .form-container {
			background: rgba(255, 255, 255, 0.95);
			border-radius: 10px;
			padding: 30px;
			position: relative;
			z-index: 2;
			backdrop-filter: blur(10px);
		}

		@keyframes circuitFlow {
			0% { background-position: 0 0, 0 0, 0 0, 40px 40px; }
			100% { background-position: 40px 40px, 40px 40px, 40px 40px, 80px 80px; }
		}

		@keyframes circuitPulse {
			0% { opacity: 0.4; }
			100% { opacity: 0.8; }
		}

		.register-card h2 {
			color: #2c3e50;
			font-weight: 700;
			margin-bottom: 8px;
			text-align: center;
			font-size: 1.8rem;
		}

		.register-card .subtitle {
			color: #6c757d;
			text-align: center;
			margin-bottom: 25px;
			font-size: 0.95rem;
		}

		.form-group {
			margin-bottom: 20px;
		}

		.form-control {
			height: 50px;
			border: 2px solid #e9ecef;
			border-radius: 10px;
			padding: 15px;
			font-size: 16px;
			transition: all 0.3s ease;
			background: rgba(248, 249, 250, 0.8);
		}

		.form-control:focus {
			border-color: #87ceeb;
			box-shadow: 0 0 0 0.2rem rgba(135, 206, 235, 0.25);
			background: white;
		}

		.btn-register {
			width: 100%;
			height: 55px;
			background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
			color: white;
			border: none;
			border-radius: 15px;
			font-size: 16px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
			position: relative;
			overflow: hidden;
			cursor: pointer;
			box-shadow: 0 8px 32px rgba(240, 147, 251, 0.3);
		}

		.btn-register::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
			transition: left 0.6s ease;
		}

		.btn-register:hover::before {
			left: 100%;
		}

		.btn-register:hover {
			background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
			transform: translateY(-3px) scale(1.02);
			box-shadow: 0 15px 40px rgba(240, 147, 251, 0.4);
		}

		.btn-register:active {
			transform: translateY(-1px) scale(0.98);
			box-shadow: 0 5px 20px rgba(240, 147, 251, 0.3);
		}

		.btn-register.loading {
			background: linear-gradient(135deg, #6c757d, #495057);
			cursor: not-allowed;
			transform: none;
			animation: pulse 2s infinite;
		}

		.btn-register.loading::before {
			display: none;
		}

		@keyframes pulse {
			0% {
				box-shadow: 0 8px 32px rgba(108, 117, 125, 0.3);
			}
			50% {
				box-shadow: 0 8px 32px rgba(108, 117, 125, 0.5);
			}
			100% {
				box-shadow: 0 8px 32px rgba(108, 117, 125, 0.3);
			}
		}

		.btn-register.success {
			background: linear-gradient(135deg, #56ab2f, #a8e6cf);
			animation: successPulse 0.6s ease;
		}

		@keyframes successPulse {
			0% { transform: scale(1); }
			50% { transform: scale(1.05); }
			100% { transform: scale(1); }
		}

		.login-link {
			text-align: center;
			margin-top: 20px;
			color: #6c757d;
		}

		.login-link a {
			color: #4682b4;
			text-decoration: none;
			font-weight: 500;
		}

		.login-link a:hover {
			text-decoration: underline;
		}

		.alert {
			border-radius: 10px;
			margin-bottom: 20px;
		}

		/* Fly-up animation */
		@keyframes flyUp {
			0% {
				transform: translateY(0) scale(1);
				opacity: 1;
			}
			100% {
				transform: translateY(-100px) scale(0.9);
				opacity: 0;
			}
		}

		.fly-up {
			animation: flyUp 0.8s ease-in-out forwards;
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
				<a href="index.php" class="logo">
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
					     alt="Gadget Garage">
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
							<p class="tech-revival-text">Bring Retired Tech <span class="ghana-flag">🇬🇭</span> Ghana Store</p>
							<p class="contact-number">055-138-7578</p>
						</div>
					</div>
				</div>

				<!-- User Actions - Far Right -->
				<div class="user-actions" style="display: flex; align-items: center; gap: 12px;">
					<span style="color: #ddd;">|</span>
					<span style="color: #008060; font-weight: 600;">Register</span>
					<a href="login/login.php" class="login-btn me-2">Login</a>
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

				<a href="index.php" class="nav-item"><span>HOME</span></a>
				<a href="all_product.php" class="nav-item"><span>SHOP</span></a>
				<a href="repair_services.php" class="nav-item"><span>REPAIR STUDIO</span></a>
				<a href="device_drop.php" class="nav-item"><span>DEVICE DROP</span></a>
				<a href="contact.php" class="nav-item"><span>MORE</span></a>

				<!-- Flash Deal positioned at far right -->
				<a href="flash_deals.php" class="nav-item flash-deal">⚡ <span>FLASH DEAL</span></a>
			</div>
		</div>
	</nav>

	<!-- Registration Form Section -->
	<section class="register-section">
		<div class="register-card" id="registerCard">
			<div class="form-container">
				<!-- Logo -->
				<div class="text-center mb-4">
					<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png"
					     alt="Gadget Garage" style="height: 60px; width: auto;">
				</div>

				<h2>Create Account</h2>
				<p class="subtitle">Join Gadget Garage today and start shopping!</p>

				<?php if (!empty($registration_error)): ?>
					<div class="alert alert-danger" role="alert">
						<i class="fas fa-exclamation-triangle me-2"></i><?php echo $registration_error; ?>
					</div>
				<?php endif; ?>

				<?php if ($registration_success): ?>
					<div class="alert alert-success" role="alert">
						<i class="fas fa-check-circle me-2"></i>Account created successfully! Redirecting...
					</div>
					<script>
						const registerBtn = document.getElementById('registerBtn');
						if (registerBtn) {
							registerBtn.classList.remove('loading');
							registerBtn.classList.add('success');
							registerBtn.innerHTML = '<i class="fas fa-check me-2"></i>Account Created Successfully!';
						}

						setTimeout(function() {
							document.getElementById('registerCard').classList.add('fly-up');
							setTimeout(function() {
								window.location.href = 'index.php';
							}, 800);
						}, 1000);
					</script>
				<?php else: ?>

				<form method="POST" id="registerForm">
					<div class="form-group">
						<input type="text" class="form-control" name="first_name" placeholder="First Name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
					</div>

					<div class="form-group">
						<input type="text" class="form-control" name="last_name" placeholder="Last Name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
					</div>

					<div class="form-group">
						<input type="email" class="form-control" name="email" placeholder="Email Address" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
					</div>

					<div class="form-group">
						<input type="password" class="form-control" name="password" placeholder="Password" required>
					</div>

					<div class="form-group">
						<input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
					</div>

					<button type="submit" class="btn-register" id="registerBtn">
						<span class="btn-text">
							<i class="fas fa-user-plus me-2"></i>
							Create Account
						</span>
						<span class="btn-loading" style="display: none;">
							Creating Account...
						</span>
					</button>
				</form>

				<div class="login-link">
					Already have an account? <a href="login/login.php">Login here</a>
				</div>

				<?php endif; ?>
			</div>
		</div>
	</section>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="js/header.js"></script>

	<script>
		// Handle form submission with enhanced animations
		document.getElementById('registerForm').addEventListener('submit', function(e) {
			const submitBtn = document.getElementById('registerBtn');
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

		// Password confirmation validation
		document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
			const password = document.querySelector('input[name="password"]').value;
			const confirmPassword = this.value;

			if (password !== confirmPassword && confirmPassword.length > 0) {
				this.setCustomValidity('Passwords do not match');
			} else {
				this.setCustomValidity('');
			}
		});
	</script>
</body>
</html>