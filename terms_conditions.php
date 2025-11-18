<?php
session_start();
require_once __DIR__ . '/settings/core.php';

// Initialize variables
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = false;
$cart_count = 0;

if ($is_logged_in) {
    $is_admin = check_admin();

    // Get cart count for logged in users
    if (!$is_admin) {
        require_once __DIR__ . '/controllers/cart_controller.php';
        $cart_count = get_cart_count_ctr($_SESSION['user_id']);
    }
}

// Get brands and categories for navigation
require_once __DIR__ . '/controllers/brand_controller.php';
require_once __DIR__ . '/controllers/category_controller.php';

$brands = get_all_brands_ctr() ?: [];
$categories = get_all_categories_ctr() ?: [];

$page_title = "Terms & Conditions - GadgetGarage";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="includes/header-styles.css">

    <style>
        .terms-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .terms-content {
            padding: 60px 0;
            background: #f8f9fa;
        }

        .terms-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .terms-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .terms-section h3 {
            color: #374151;
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .terms-section p, .terms-section li {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .terms-section ul {
            padding-left: 20px;
        }

        .last-updated {
            background: #e0e7ff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: #4338ca;
            font-weight: 500;
        }

        .contact-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
        }

        .contact-info h3 {
            color: white !important;
            margin-bottom: 20px;
        }

        .contact-info a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 600;
        }

        .contact-info a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Terms Hero Section -->
    <section class="terms-hero">
        <div class="container">
            <h1><i class="fas fa-file-contract me-3"></i>Terms & Conditions</h1>
            <p class="lead">Please read these terms and conditions carefully before using our service</p>
        </div>
    </section>

    <!-- Terms Content -->
    <section class="terms-content">
        <div class="container">
            <div class="last-updated">
                <i class="fas fa-calendar-alt me-2"></i>
                <strong>Last Updated:</strong> November 2024
            </div>

            <!-- Introduction -->
            <div class="terms-section">
                <h2>1. Introduction</h2>
                <p>Welcome to GadgetGarage. These Terms and Conditions ("Terms", "Terms and Conditions") govern your relationship with GadgetGarage website (the "Service") operated by GadgetGarage ("us", "we", or "our").</p>
                <p>Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms. These Terms apply to all visitors, users and others who access or use the Service.</p>
                <p>By accessing or using our Service you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Service.</p>
            </div>

            <!-- Accounts -->
            <div class="terms-section">
                <h2>2. User Accounts</h2>
                <h3>Account Creation</h3>
                <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for any activities that occur under your account.</p>

                <h3>Account Security</h3>
                <ul>
                    <li>You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account</li>
                    <li>We will not be liable for any loss or damage arising from your failure to comply with this security obligation</li>
                    <li>You must not use another user's account without permission</li>
                </ul>
            </div>

            <!-- Products and Services -->
            <div class="terms-section">
                <h2>3. Products and Services</h2>
                <h3>Product Information</h3>
                <p>We strive to display our products as accurately as possible. However, we cannot guarantee that your device's display of colors or product details will be accurate.</p>

                <h3>Pricing</h3>
                <ul>
                    <li>All prices are listed in Ghana Cedis (GH₵) and are subject to change without notice</li>
                    <li>We reserve the right to modify prices at any time</li>
                    <li>The price charged will be the price displayed at the time of purchase</li>
                </ul>

                <h3>Availability</h3>
                <p>All products are subject to availability. We reserve the right to discontinue any product at any time.</p>
            </div>

            <!-- Orders and Payments -->
            <div class="terms-section">
                <h2>4. Orders and Payments</h2>
                <h3>Order Process</h3>
                <p>When you place an order, you will receive an email confirmation. This confirmation does not constitute our acceptance of your order.</p>

                <h3>Payment Terms</h3>
                <ul>
                    <li>Payment must be made at the time of order</li>
                    <li>We accept various payment methods as displayed during checkout</li>
                    <li>All transactions are processed securely</li>
                </ul>

                <h3>Order Cancellation</h3>
                <p>We reserve the right to refuse or cancel any order for any reason, including but not limited to product availability, errors in product information, or problems with your account.</p>
            </div>

            <!-- Shipping and Returns -->
            <div class="terms-section">
                <h2>5. Shipping and Returns</h2>
                <h3>Shipping Policy</h3>
                <p>We ship within Ghana. Delivery times are estimates and not guaranteed. Shipping costs will be calculated at checkout.</p>

                <h3>Return Policy</h3>
                <ul>
                    <li>Items may be returned within 14 days of delivery</li>
                    <li>Items must be in original condition and packaging</li>
                    <li>Customer is responsible for return shipping costs unless the item is defective</li>
                    <li>Refunds will be processed within 5-7 business days after receiving the returned item</li>
                </ul>
            </div>

            <!-- User Conduct -->
            <div class="terms-section">
                <h2>6. User Conduct</h2>
                <p>You agree not to:</p>
                <ul>
                    <li>Use the Service for any unlawful purpose or to solicit others to perform such acts</li>
                    <li>Violate any local, state, national, or international law</li>
                    <li>Infringe upon or violate our intellectual property rights or the intellectual property rights of others</li>
                    <li>Harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate</li>
                    <li>Submit false or misleading information</li>
                    <li>Upload viruses or any other type of malicious code</li>
                </ul>
            </div>

            <!-- Privacy -->
            <div class="terms-section">
                <h2>7. Privacy Policy</h2>
                <p>Your privacy is important to us. Our Privacy Policy explains how we collect, use, and protect your information when you use our Service. By using our Service, you agree to the collection and use of information in accordance with our Privacy Policy.</p>
            </div>

            <!-- Limitation of Liability -->
            <div class="terms-section">
                <h2>8. Limitation of Liability</h2>
                <p>In no event shall GadgetGarage, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your use of the Service.</p>
            </div>

            <!-- Governing Law -->
            <div class="terms-section">
                <h2>9. Governing Law</h2>
                <p>These Terms shall be interpreted and governed by the laws of Ghana, without regard to its conflict of law provisions. Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights.</p>
            </div>

            <!-- Changes to Terms -->
            <div class="terms-section">
                <h2>10. Changes to Terms</h2>
                <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days notice prior to any new terms taking effect.</p>
                <p>Your continued use of the Service after we post any modifications to the Terms on this page will constitute your acknowledgment of the modifications and your consent to abide and be bound by the modified Terms.</p>
            </div>

            <!-- Contact Information -->
            <div class="contact-info">
                <h3>Contact Information</h3>
                <p>If you have any questions about these Terms & Conditions, please contact us:</p>
                <p>
                    <i class="fas fa-envelope me-2"></i>
                    Email: <a href="mailto:support@gadgetgarage.com">support@gadgetgarage.com</a>
                </p>
                <p>
                    <i class="fas fa-phone me-2"></i>
                    Phone: <a href="tel:+233551387578">+233 55 138 7578</a>
                </p>
                <p>
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Address: Ghana, West Africa
                </p>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Header JavaScript -->
    <script src="js/header.js"></script>
</body>
</html>