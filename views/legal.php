<?php
require_once(__DIR__ . '/../settings/core.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count for logged in users
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
require_once(__DIR__ . '/../controllers/cart_controller.php');
$cart_count = get_cart_count_ctr($customer_id, $ip_address);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Legal - Terms & Privacy - Gadget Garage</title>
    <meta name="description" content="Terms and Conditions and Privacy Policy for Gadget Garage services including purchases, repairs, device drop, and support.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a202c;
            line-height: 1.6;
        }

        /* Header Styles */
        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
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

        /* Legal Page Container */
        .legal-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: 80vh;
        }

        .legal-header {
            text-align: center;
            margin-bottom: 60px;
            color: white;
        }

        .legal-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .legal-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            font-weight: 400;
        }

        /* Tab Navigation */
        .legal-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            gap: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 8px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 40px;
        }

        .tab-button {
            flex: 1;
            padding: 15px 30px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-button:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .tab-button.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .tab-button.active::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 20%;
            right: 20%;
            height: 3px;
            background: white;
            border-radius: 2px;
        }

        /* Content Areas */
        .legal-content {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .legal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .content-section {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 30px;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        /* Sidebar Navigation */
        .content-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            align-items: start;
        }

        .sidebar {
            background: #f8fafc;
            border-radius: 15px;
            padding: 30px 0;
            position: sticky;
            top: 120px;
            border: 1px solid #e2e8f0;
        }

        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            padding: 0 25px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 0;
        }

        .sidebar-nav a {
            display: block;
            padding: 12px 25px;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            border-left-color: #667eea;
        }

        .main-content {
            background: white;
        }

        /* Content Styling */
        .content-subsection {
            margin-bottom: 40px;
        }

        .subsection-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 15px;
            scroll-margin-top: 120px;
        }

        .content-text {
            color: #4a5568;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .content-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .content-list li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #4a5568;
            line-height: 1.6;
        }

        .content-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
            font-size: 1.2em;
        }

        .highlight-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-left: 4px solid #667eea;
            padding: 25px;
            margin: 25px 0;
            border-radius: 0 10px 10px 0;
        }

        .highlight-box h4 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .highlight-box p {
            margin: 0;
            color: #4a5568;
        }

        /* Contact Info Box */
        .contact-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 40px 0;
        }

        .contact-box h3 {
            font-weight: 700;
            margin-bottom: 15px;
        }

        .contact-box p {
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .contact-button {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .contact-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: #667eea;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #667eea;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-to-top:hover {
            background: #764ba2;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Footer */
        .legal-footer {
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 30px 0;
            margin-top: 60px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .content-wrapper {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .sidebar {
                position: relative;
                top: auto;
                order: 2;
            }

            .main-content {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .legal-container {
                padding: 20px 15px;
            }

            .legal-title {
                font-size: 2rem;
            }

            .legal-content {
                padding: 30px 25px;
            }

            .legal-tabs {
                flex-direction: column;
                max-width: 300px;
            }

            .tab-button {
                padding: 12px 20px;
            }

            .sidebar {
                padding: 20px 0;
            }
        }

        /* Dark Mode Adjustments */
        [data-theme="dark"] .legal-content {
            background: var(--card-bg);
            color: var(--text-primary);
        }

        [data-theme="dark"] .sidebar {
            background: var(--bg-secondary);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .subsection-title {
            color: var(--text-primary);
        }

        [data-theme="dark"] .sidebar-title {
            color: var(--text-primary);
        }

        [data-theme="dark"] .content-text {
            color: var(--text-secondary);
        }

        [data-theme="dark"] .content-list li {
            color: var(--text-secondary);
        }

        [data-theme="dark"] .highlight-box {
            background: rgba(102, 126, 234, 0.1);
        }

        [data-theme="dark"] .highlight-box p {
            color: var(--text-secondary);
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
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3">
                    <a href="index.php" class="logo">
                        <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                             alt="Gadget Garage"
                             style="height: 40px; width: auto; object-fit: contain;">
                    </a>
                </div>
                <div class="col-lg-6 text-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Legal</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-3 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <!-- Dark Mode Toggle -->
                        <button class="btn btn-outline-secondary me-2" onclick="toggleTheme()" id="darkModeToggleBtn" title="Toggle Dark Mode">
                            <i class="fas fa-moon" id="darkModeIcon"></i>
                        </button>

                        <!-- Cart Icon -->
                        <a href="cart.php" class="cart-icon position-relative">
                            <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: #008060;"></i>
                            <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartBadge" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>">
                                <?php echo $cart_count; ?>
                            </span>
                        </a>

                        <?php if ($is_logged_in): ?>
                            <a href="login/logout.php" class="btn btn-outline-danger">Logout</a>
                        <?php else: ?>
                            <a href="login/login.php" class="btn btn-outline-primary">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <!-- Legal Page Content -->
    <div class="legal-container">
        <!-- Header Section -->
        <div class="legal-header">
            <h1 class="legal-title animate__animated animate__fadeInDown">Legal Information</h1>
            <p class="legal-subtitle animate__animated animate__fadeInUp animate__delay-1s">
                Comprehensive terms, conditions, and privacy policies for all Gadget Garage services
            </p>
        </div>

        <!-- Tab Navigation -->
        <div class="legal-tabs animate__animated animate__fadeInUp animate__delay-2s">
            <button class="tab-button active" onclick="showTab('terms')" id="terms-tab">
                Terms & Conditions
            </button>
            <button class="tab-button" onclick="showTab('privacy')" id="privacy-tab">
                Privacy Policy
            </button>
        </div>

        <!-- Legal Content -->
        <div class="legal-content animate__animated animate__fadeInUp animate__delay-3s">
            <!-- Terms and Conditions Section -->
            <div id="terms-content" class="content-section active">
                <div class="content-wrapper">
                    <aside class="sidebar">
                        <h3 class="sidebar-title">Contents</h3>
                        <ul class="sidebar-nav">
                            <li><a href="#terms-general" onclick="scrollToSection('terms-general')">General Terms</a></li>
                            <li><a href="#terms-purchasing" onclick="scrollToSection('terms-purchasing')">Product Purchasing</a></li>
                            <li><a href="#terms-device-drop" onclick="scrollToSection('terms-device-drop')">Device Drop Service</a></li>
                            <li><a href="#terms-repair" onclick="scrollToSection('terms-repair')">Repair Studio</a></li>
                            <li><a href="#terms-returns" onclick="scrollToSection('terms-returns')">Returns & Refunds</a></li>
                            <li><a href="#terms-warranty" onclick="scrollToSection('terms-warranty')">Warranty Terms</a></li>
                            <li><a href="#terms-limitation" onclick="scrollToSection('terms-limitation')">Limitation of Liability</a></li>
                            <li><a href="#terms-governing" onclick="scrollToSection('terms-governing')">Governing Law</a></li>
                        </ul>
                    </aside>

                    <main class="main-content">
                        <h2 class="section-title">Terms and Conditions</h2>
                        <p class="content-text">Last updated: <?php echo date('F j, Y'); ?></p>

                        <div class="content-subsection" id="terms-general">
                            <h3 class="subsection-title">1. General Terms</h3>
                            <p class="content-text">
                                Welcome to Gadget Garage. These terms and conditions ("Terms") govern your use of our website, products, and services. By accessing our website or using our services, you agree to be bound by these Terms.
                            </p>
                            <p class="content-text">
                                Gadget Garage is a premium technology retailer and service provider specializing in refurbished devices, repairs, and device trade-in services. We are committed to providing quality products and exceptional customer service.
                            </p>
                            <ul class="content-list">
                                <li>You must be at least 18 years old to make purchases</li>
                                <li>All information provided must be accurate and complete</li>
                                <li>You are responsible for maintaining account security</li>
                                <li>We reserve the right to refuse service to anyone</li>
                            </ul>
                        </div>

                        <div class="content-subsection" id="terms-purchasing">
                            <h3 class="subsection-title">2. Product Purchasing</h3>
                            <p class="content-text">
                                All products sold by Gadget Garage are subject to availability. Prices are listed in Ghana Cedis (GH₵) and include applicable taxes unless otherwise specified.
                            </p>

                            <div class="highlight-box">
                                <h4>Product Conditions</h4>
                                <p>We offer devices in three conditions with corresponding prices:</p>
                            </div>

                            <ul class="content-list">
                                <li><strong>Excellent Condition:</strong> Like new, no visible wear or damage</li>
                                <li><strong>Good Condition:</strong> Minor scratches or wear, fully functional</li>
                                <li><strong>Fair Condition:</strong> Visible wear but works perfectly</li>
                            </ul>
                            <p class="content-text">
                                All orders are subject to acceptance by Gadget Garage. We reserve the right to cancel orders for any reason including but not limited to product unavailability, pricing errors, or suspected fraudulent activity.
                            </p>
                        </div>

                        <div class="content-subsection" id="terms-device-drop">
                            <h3 class="subsection-title">3. Device Drop Service</h3>
                            <p class="content-text">
                                Our Device Drop service allows customers to trade in their old devices for store credit or cash. The following terms apply to this service:
                            </p>
                            <ul class="content-list">
                                <li>Devices must be factory reset before drop-off</li>
                                <li>You must provide proof of ownership</li>
                                <li>Final valuation may differ from initial quote based on physical inspection</li>
                                <li>Gadget Garage is not responsible for data left on devices</li>
                                <li>Payment is processed within 24-48 hours of final evaluation</li>
                                <li>Devices that cannot be accepted will be returned to you</li>
                            </ul>
                            <p class="content-text">
                                Trade-in values are based on current market conditions and device condition. Values may change without notice.
                            </p>
                        </div>

                        <div class="content-subsection" id="terms-repair">
                            <h3 class="subsection-title">4. Repair Studio Services</h3>
                            <p class="content-text">
                                Our Repair Studio offers professional device repair services with certified technicians. The following terms apply:
                            </p>
                            <ul class="content-list">
                                <li>Free diagnostic evaluation for all devices</li>
                                <li>Written estimates provided before any work begins</li>
                                <li>Customer authorization required for all repairs</li>
                                <li>90-day warranty on all repairs and parts used</li>
                                <li>We are not liable for data loss during repair</li>
                                <li>Unclaimed devices after 30 days may be disposed of</li>
                            </ul>

                            <div class="highlight-box">
                                <h4>Repair Warranty</h4>
                                <p>All repairs come with a 90-day warranty covering the specific repair performed. This warranty does not cover water damage, physical abuse, or normal wear and tear.</p>
                            </div>
                        </div>

                        <div class="content-subsection" id="terms-returns">
                            <h3 class="subsection-title">5. Returns and Refunds</h3>
                            <p class="content-text">
                                We want you to be completely satisfied with your purchase. Our return policy is designed to be fair and customer-friendly:
                            </p>
                            <ul class="content-list">
                                <li><strong>30-Day Return Window:</strong> Items can be returned within 30 days of purchase</li>
                                <li><strong>Original Condition:</strong> Items must be in original condition with all accessories</li>
                                <li><strong>Return Authorization:</strong> Contact customer service for return authorization</li>
                                <li><strong>Restocking Fee:</strong> 15% restocking fee may apply to opened items</li>
                                <li><strong>Shipping Costs:</strong> Customer responsible for return shipping unless item is defective</li>
                            </ul>
                            <p class="content-text">
                                Refunds are processed to the original payment method within 5-7 business days after we receive the returned item.
                            </p>
                        </div>

                        <div class="content-subsection" id="terms-warranty">
                            <h3 class="subsection-title">6. Warranty Terms</h3>
                            <p class="content-text">
                                Gadget Garage provides comprehensive warranty coverage for all products and services:
                            </p>
                            <ul class="content-list">
                                <li><strong>Product Warranty:</strong> 6-month warranty on all refurbished devices</li>
                                <li><strong>Repair Warranty:</strong> 90-day warranty on all repair services</li>
                                <li><strong>Parts Warranty:</strong> All replacement parts covered for 90 days</li>
                                <li><strong>Manufacturer Warranty:</strong> New items include original manufacturer warranty</li>
                            </ul>
                            <p class="content-text">
                                Warranty does not cover damage from misuse, accidents, water damage, or normal wear and tear. Warranty claims require proof of purchase and may require device inspection.
                            </p>
                        </div>

                        <div class="content-subsection" id="terms-limitation">
                            <h3 class="subsection-title">7. Limitation of Liability</h3>
                            <p class="content-text">
                                To the maximum extent permitted by law, Gadget Garage's liability is limited to the purchase price of the product or service in question. We are not liable for:
                            </p>
                            <ul class="content-list">
                                <li>Indirect, incidental, or consequential damages</li>
                                <li>Data loss or corruption</li>
                                <li>Business interruption or loss of profits</li>
                                <li>Compatibility issues with other devices</li>
                                <li>Damage caused by misuse or accidents</li>
                            </ul>
                        </div>

                        <div class="content-subsection" id="terms-governing">
                            <h3 class="subsection-title">8. Governing Law</h3>
                            <p class="content-text">
                                These Terms are governed by the laws of Ghana. Any disputes arising from these Terms or your use of our services will be resolved in the courts of Ghana, specifically in Accra.
                            </p>
                            <p class="content-text">
                                If any provision of these Terms is found to be invalid or unenforceable, the remaining provisions will continue to be valid and enforceable.
                            </p>
                        </div>

                        <div class="contact-box">
                            <h3>Questions About Our Terms?</h3>
                            <p>Our customer service team is here to help clarify any questions about our terms and conditions.</p>
                            <a href="contact.php" class="contact-button">Contact Support</a>
                        </div>
                    </main>
                </div>
            </div>

            <!-- Privacy Policy Section -->
            <div id="privacy-content" class="content-section">
                <div class="content-wrapper">
                    <aside class="sidebar">
                        <h3 class="sidebar-title">Contents</h3>
                        <ul class="sidebar-nav">
                            <li><a href="#privacy-overview" onclick="scrollToSection('privacy-overview')">Privacy Overview</a></li>
                            <li><a href="#privacy-collection" onclick="scrollToSection('privacy-collection')">Information We Collect</a></li>
                            <li><a href="#privacy-use" onclick="scrollToSection('privacy-use')">How We Use Information</a></li>
                            <li><a href="#privacy-sharing" onclick="scrollToSection('privacy-sharing')">Information Sharing</a></li>
                            <li><a href="#privacy-security" onclick="scrollToSection('privacy-security')">Data Security</a></li>
                            <li><a href="#privacy-retention" onclick="scrollToSection('privacy-retention')">Data Retention</a></li>
                            <li><a href="#privacy-rights" onclick="scrollToSection('privacy-rights')">Your Rights</a></li>
                            <li><a href="#privacy-cookies" onclick="scrollToSection('privacy-cookies')">Cookies & Tracking</a></li>
                            <li><a href="#privacy-contact" onclick="scrollToSection('privacy-contact')">Contact Us</a></li>
                        </ul>
                    </aside>

                    <main class="main-content">
                        <h2 class="section-title">Privacy Policy</h2>
                        <p class="content-text">Last updated: <?php echo date('F j, Y'); ?></p>

                        <div class="content-subsection" id="privacy-overview">
                            <h3 class="subsection-title">1. Privacy Overview</h3>
                            <p class="content-text">
                                At Gadget Garage, we respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, and safeguard your information when you use our website and services.
                            </p>
                            <p class="content-text">
                                We only collect information that is necessary to provide you with excellent service and improve your experience with our products and services.
                            </p>

                            <div class="highlight-box">
                                <h4>Our Privacy Commitment</h4>
                                <p>We will never sell your personal information to third parties and we use industry-standard security measures to protect your data.</p>
                            </div>
                        </div>

                        <div class="content-subsection" id="privacy-collection">
                            <h3 class="subsection-title">2. Information We Collect</h3>
                            <p class="content-text">
                                We collect information in several ways to provide and improve our services:
                            </p>

                            <h4 style="font-weight: 600; margin: 20px 0 10px;">Information You Provide</h4>
                            <ul class="content-list">
                                <li>Name, email address, and phone number when creating an account</li>
                                <li>Billing and shipping addresses for orders</li>
                                <li>Payment information (processed securely through our payment partners)</li>
                                <li>Device information for repair and trade-in services</li>
                                <li>Communications with customer support</li>
                            </ul>

                            <h4 style="font-weight: 600; margin: 20px 0 10px;">Information We Automatically Collect</h4>
                            <ul class="content-list">
                                <li>IP address and device information</li>
                                <li>Browser type and operating system</li>
                                <li>Pages visited and time spent on our website</li>
                                <li>Referring websites and search terms</li>
                                <li>Shopping cart contents and purchase history</li>
                            </ul>
                        </div>

                        <div class="content-subsection" id="privacy-use">
                            <h3 class="subsection-title">3. How We Use Your Information</h3>
                            <p class="content-text">
                                We use your information to provide excellent service and improve your experience:
                            </p>
                            <ul class="content-list">
                                <li><strong>Order Processing:</strong> To process orders, arrange shipping, and provide customer support</li>
                                <li><strong>Account Management:</strong> To create and manage your account, track order history</li>
                                <li><strong>Communication:</strong> To send order confirmations, shipping updates, and service notifications</li>
                                <li><strong>Service Improvement:</strong> To analyze website usage and improve our services</li>
                                <li><strong>Marketing:</strong> To send promotional offers (with your consent)</li>
                                <li><strong>Legal Compliance:</strong> To comply with legal obligations and resolve disputes</li>
                            </ul>
                        </div>

                        <div class="content-subsection" id="privacy-sharing">
                            <h3 class="subsection-title">4. Information Sharing</h3>
                            <p class="content-text">
                                We do not sell your personal information. We may share your information only in these limited circumstances:
                            </p>
                            <ul class="content-list">
                                <li><strong>Service Providers:</strong> With trusted partners who help us operate our business (shipping, payment processing, customer support)</li>
                                <li><strong>Legal Requirements:</strong> When required by law, court order, or government regulation</li>
                                <li><strong>Business Protection:</strong> To protect our rights, property, or safety, or that of our customers</li>
                                <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
                            </ul>

                            <div class="highlight-box">
                                <h4>Third-Party Services</h4>
                                <p>We work with reputable service providers who are contractually obligated to protect your information and use it only for the services they provide to us.</p>
                            </div>
                        </div>

                        <div class="content-subsection" id="privacy-security">
                            <h3 class="subsection-title">5. Data Security</h3>
                            <p class="content-text">
                                We implement comprehensive security measures to protect your personal information:
                            </p>
                            <ul class="content-list">
                                <li><strong>Encryption:</strong> All sensitive data is encrypted in transit and at rest</li>
                                <li><strong>Access Controls:</strong> Strict access controls limit who can view your information</li>
                                <li><strong>Secure Servers:</strong> Data is stored on secure servers with regular security updates</li>
                                <li><strong>Regular Monitoring:</strong> We continuously monitor for security threats and vulnerabilities</li>
                                <li><strong>Employee Training:</strong> All staff receive regular privacy and security training</li>
                            </ul>
                            <p class="content-text">
                                While we implement strong security measures, no method of transmission over the internet is 100% secure. We cannot guarantee absolute security but we use industry best practices.
                            </p>
                        </div>

                        <div class="content-subsection" id="privacy-retention">
                            <h3 class="subsection-title">6. Data Retention</h3>
                            <p class="content-text">
                                We retain your information for as long as necessary to provide our services and comply with legal obligations:
                            </p>
                            <ul class="content-list">
                                <li><strong>Account Information:</strong> Retained while your account is active or as needed for services</li>
                                <li><strong>Purchase Records:</strong> Kept for 7 years for warranty and tax purposes</li>
                                <li><strong>Support Communications:</strong> Retained for 3 years for quality assurance</li>
                                <li><strong>Website Analytics:</strong> Anonymized data may be retained indefinitely</li>
                            </ul>
                        </div>

                        <div class="content-subsection" id="privacy-rights">
                            <h3 class="subsection-title">7. Your Privacy Rights</h3>
                            <p class="content-text">
                                You have several rights regarding your personal information:
                            </p>
                            <ul class="content-list">
                                <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
                                <li><strong>Correction:</strong> Request correction of inaccurate or incomplete information</li>
                                <li><strong>Deletion:</strong> Request deletion of your personal information (subject to legal requirements)</li>
                                <li><strong>Portability:</strong> Request transfer of your information to another service</li>
                                <li><strong>Marketing Opt-out:</strong> Unsubscribe from marketing communications at any time</li>
                                <li><strong>Account Closure:</strong> Request closure of your account and data deletion</li>
                            </ul>
                            <p class="content-text">
                                To exercise these rights, please contact our privacy team at privacy@gadgetgarage.gh or use our contact form.
                            </p>
                        </div>

                        <div class="content-subsection" id="privacy-cookies">
                            <h3 class="subsection-title">8. Cookies and Tracking</h3>
                            <p class="content-text">
                                We use cookies and similar technologies to improve your experience on our website:
                            </p>
                            <ul class="content-list">
                                <li><strong>Essential Cookies:</strong> Required for website functionality (shopping cart, login)</li>
                                <li><strong>Analytics Cookies:</strong> Help us understand how visitors use our website</li>
                                <li><strong>Preference Cookies:</strong> Remember your settings and preferences</li>
                                <li><strong>Marketing Cookies:</strong> Used to show relevant advertisements (with your consent)</li>
                            </ul>
                            <p class="content-text">
                                You can control cookie settings through your browser preferences. However, disabling certain cookies may limit website functionality.
                            </p>
                        </div>

                        <div class="content-subsection" id="privacy-contact">
                            <h3 class="subsection-title">9. Contact Us About Privacy</h3>
                            <p class="content-text">
                                If you have any questions about this Privacy Policy or our data practices, please contact us:
                            </p>
                            <ul class="content-list">
                                <li><strong>Email:</strong> privacy@gadgetgarage.gh</li>
                                <li><strong>Phone:</strong> 055-138-7578</li>
                                <li><strong>Mail:</strong> Gadget Garage Plaza, Oxford Street, Osu, Accra, Ghana</li>
                                <li><strong>Contact Form:</strong> <a href="contact.php">contact.php</a></li>
                            </ul>
                        </div>

                        <div class="contact-box">
                            <h3>Privacy Questions?</h3>
                            <p>We're committed to transparency about how we handle your data. Contact us with any privacy concerns.</p>
                            <a href="contact.php" class="contact-button">Contact Privacy Team</a>
                        </div>
                    </main>
                </div>
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

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dark-mode.js"></script>

    <script>
        // Tab Switching
        function showTab(tabName) {
            // Hide all content sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected content and activate tab
            document.getElementById(tabName + '-content').classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');

            // Scroll to top of content
            document.querySelector('.legal-content').scrollIntoView({ behavior: 'smooth' });
        }

        // Smooth scrolling to sections
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Update active nav item
                document.querySelectorAll('.sidebar-nav a').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelector(`a[href="#${sectionId}"]`).classList.add('active');
            }
        }

        // Back to top button
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Show/hide back to top button
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('backToTop');
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        });

        // Update dark mode button icon
        function updateDarkModeIcon() {
            const icon = document.getElementById('darkModeIcon');
            const isDark = window.isDarkMode && window.isDarkMode();

            if (icon) {
                icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateDarkModeIcon();

            // Update sidebar navigation on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        document.querySelectorAll('.sidebar-nav a').forEach(link => {
                            link.classList.remove('active');
                        });
                        const activeLink = document.querySelector(`a[href="#${id}"]`);
                        if (activeLink) {
                            activeLink.classList.add('active');
                        }
                    }
                });
            }, {
                rootMargin: '-20% 0px -70% 0px'
            });

            document.querySelectorAll('.content-subsection').forEach(section => {
                observer.observe(section);
            });
        });

        // Listen for theme changes to update icon
        if (window.darkModeManager) {
            window.darkModeManager.onThemeChange(updateDarkModeIcon);
        }
    </script>
</body>

</html>