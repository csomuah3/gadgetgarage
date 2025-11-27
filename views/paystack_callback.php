<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';

// Check if user is logged in - if not, we'll handle this in JavaScript after verification attempt
$user_logged_in = check_login();
$user_id = $user_logged_in ? $_SESSION['user_id'] : null;

// Get reference from URL
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : null;

if (!$reference) {
    // Payment cancelled or reference missing
    header('Location: checkout.php?error=cancelled');
    exit();
}

log_paystack_activity('info', 'PayStack callback accessed', [
    'reference' => $reference,
    'user_id' => $user_id,
    'session_active' => $user_logged_in ? 'yes' : 'no'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .payment-container {
            max-width: 500px;
            width: 90%;
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .spinner {
            display: inline-block;
            width: 60px;
            height: 60px;
            border: 5px solid #f3f4f6;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 30px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo {
            max-height: 60px;
            margin-bottom: 20px;
        }

        .reference {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 25px 0;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #495057;
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
    <div class="payment-container">
        <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
             alt="Gadget Garage" class="logo">

        <div class="spinner" id="spinner"></div>

        <h2 class="mb-3" id="statusTitle">Verifying Payment</h2>
        <p class="text-muted mb-4" id="statusMessage">Please wait while we verify your payment with PayStack...</p>

        <div class="reference">
            <i class="fas fa-receipt me-2"></i>
            Payment Reference: <strong><?= htmlspecialchars($reference) ?></strong>
        </div>

        <div class="alert alert-danger d-none" id="errorBox" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error:</strong> <span id="errorMessage"></span>
        </div>

        <div class="alert alert-success d-none" id="successBox" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong> Payment verified successfully. Redirecting...
        </div>

        <div class="text-muted small mt-4">
            <i class="fas fa-shield-alt me-1"></i>
            Secured by PayStack
        </div>
    </div>

    <script>
        /**
         * Verify PayStack payment with proper error handling
         */
        async function verifyPayment() {
            const reference = '<?= htmlspecialchars($reference) ?>';
            const isUserLoggedIn = <?= json_encode($user_logged_in) ?>;

            try {
                console.log('Verifying PayStack payment with reference:', reference);
                console.log('User logged in status:', isUserLoggedIn);

                if (!isUserLoggedIn) {
                    throw new Error('Session expired. Please login again to complete your order.');
                }

                // Use the proper PayStack verification endpoint that creates real orders
                const verifyUrl = '../actions/paystack_verify_payment.php';
                console.log('Verifying payment, URL:', verifyUrl);

                const response = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        reference: reference
                    })
                });

                console.log('Verification response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('Non-JSON response:', textResponse);
                    throw new Error(`Server returned non-JSON response. Content: ${textResponse.substring(0, 200)}...`);
                }

                const data = await response.json();
                console.log('PayStack verification response:', data);

                if (data.status === 'success' && data.verified === true) {
                    // Show success state
                    document.getElementById('spinner').style.display = 'none';
                    document.getElementById('statusTitle').textContent = 'Payment Verified ✓';
                    document.getElementById('statusTitle').style.color = '#28a745';
                    document.getElementById('statusMessage').textContent = 'Your payment has been verified successfully! Redirecting...';
                    document.getElementById('successBox').classList.remove('d-none');

                    // Store order details in sessionStorage for checkout page
                    if (data.order_id) {
                        sessionStorage.setItem('orderData', JSON.stringify({
                            order_id: data.order_id,
                            order_reference: data.order_reference,
                            total_amount: data.total_amount,
                            payment_reference: data.payment_reference,
                            payment_method: data.payment_method,
                            currency: data.currency
                        }));
                    }

                    // Clear promo code from localStorage
                    localStorage.removeItem('appliedPromo');

                    // Redirect to index page for order confirmation and rating
                    setTimeout(() => {
                        window.location.replace('../index.php?payment=success&order=' + encodeURIComponent(data.order_id) + '&ref=' + encodeURIComponent(reference));
                    }, 2000);

                } else {
                    // Payment verification failed
                    document.getElementById('spinner').style.display = 'none';
                    const errorMsg = data.message || 'Payment verification failed';
                    console.error('Verification failed:', errorMsg);
                    showError(errorMsg);

                    // Redirect to checkout page with error after 4 seconds
                    setTimeout(() => {
                        window.location.replace('checkout.php?payment=failed&reason=' + encodeURIComponent(errorMsg));
                    }, 4000);
                }

            } catch (error) {
                console.error('Payment verification error:', error);
                document.getElementById('spinner').style.display = 'none';

                let errorMessage;
                if (error.message.includes('Session expired')) {
                    errorMessage = 'Your session has expired. Please login again to complete your order.';
                    // Redirect to login page
                    setTimeout(() => {
                        window.location.replace('../login/user_login.php?redirect=' + encodeURIComponent('cart.php') + '&message=' + encodeURIComponent('Session expired during payment'));
                    }, 3000);
                } else if (error.message.includes('HTTP 404')) {
                    errorMessage = 'Payment verification service not found. Please contact support.';
                    setTimeout(() => {
                        window.location.replace('checkout.php?payment=failed&reason=' + encodeURIComponent('verification_service_error'));
                    }, 4000);
                } else if (error.message.includes('HTTP 500')) {
                    errorMessage = 'Server error during verification. Please try again or contact support.';
                    setTimeout(() => {
                        window.location.replace('checkout.php?payment=failed&reason=' + encodeURIComponent('server_error'));
                    }, 4000);
                } else {
                    errorMessage = 'Connection error. Please refresh the page or contact support.';
                    setTimeout(() => {
                        window.location.replace('checkout.php?payment=failed&reason=' + encodeURIComponent('connection_error'));
                    }, 4000);
                }

                showError(errorMessage);
                addRetryButton();
            }
        }

        /**
         * Show error message
         */
        function showError(message) {
            document.getElementById('errorBox').classList.remove('d-none');
            document.getElementById('errorMessage').textContent = message;
        }

        // Add retry functionality
        let retryCount = 0;
        const maxRetries = 2;

        async function retryVerification() {
            if (retryCount < maxRetries) {
                retryCount++;
                console.log(`Retrying verification (attempt ${retryCount}/${maxRetries})`);
                document.getElementById('statusMessage').textContent = `Retrying verification (attempt ${retryCount})...`;
                document.getElementById('spinner').style.display = 'inline-block';
                document.getElementById('errorBox').classList.add('d-none');

                setTimeout(() => {
                    verifyPayment();
                }, 2000);
            } else {
                document.getElementById('statusMessage').textContent = 'Maximum retry attempts reached. Redirecting to checkout...';
                setTimeout(() => {
                    window.location.replace('checkout.php?payment=failed&reason=' + encodeURIComponent('verification_timeout'));
                }, 3000);
            }
        }

        // Start verification when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user is still logged in before attempting verification
            if (!<?= json_encode($user_logged_in) ?>) {
                showError('Session expired. Redirecting to login...');
                setTimeout(() => {
                    window.location.replace('../login/user_login.php?redirect=' + encodeURIComponent('cart.php'));
                }, 2000);
                return;
            }

            // Add a small delay to show the processing state
            setTimeout(verifyPayment, 1000);
        });

        // Add retry button functionality
        function addRetryButton() {
            const errorBox = document.getElementById('errorBox');
            if (!document.getElementById('retryBtn')) {
                const retryButton = document.createElement('button');
                retryButton.id = 'retryBtn';
                retryButton.className = 'btn btn-primary mt-3';
                retryButton.innerHTML = '<i class="fas fa-redo me-2"></i>Retry Verification';
                retryButton.onclick = retryVerification;
                errorBox.appendChild(retryButton);
            }
        }
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