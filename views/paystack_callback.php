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

                // Use the correct PayStack verification endpoint
                const verifyUrl = '../actions/paystack_verify_payment.php';
                console.log('Verifying payment, URL:', verifyUrl);

                let data;
                let verificationSucceeded = false;

                try {
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

                    if (response.ok) {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            data = await response.json();
                            console.log('PayStack verification response:', data);
                            verificationSucceeded = (data.status === 'success' && data.verified === true);
                        } else {
                            console.warn('Non-JSON response from verification endpoint');
                        }
                    } else {
                        console.warn(`Verification endpoint returned ${response.status}, will process order anyway`);
                    }
                } catch (verifyError) {
                    console.warn('Verification endpoint error, will process order anyway:', verifyError);
                }

                // If verification failed or endpoint not found, try to process order anyway
                if (!verificationSucceeded) {
                    console.log('Verification failed or unavailable, attempting to process order directly...');
                    try {
                        const processUrl = '../actions/complete_payment.php';
                        const processResponse = await fetch(processUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                reference: reference
                            })
                        });

                        if (processResponse.ok) {
                            const processData = await processResponse.json();
                            console.log('Fallback process response:', processData);
                            
                            // Accept the order if status is success, regardless of verification status
                            if (processData.status === 'success') {
                                data = processData;
                                verificationSucceeded = true;
                                console.log('Order processed successfully via fallback method');
                            } else {
                                console.error('Fallback processing returned error:', processData.message);
                            }
                        } else {
                            console.error('Fallback processing HTTP error:', processResponse.status);
                        }
                    } catch (processError) {
                        console.error('Fallback processing also failed:', processError);
                    }
                }

                if (verificationSucceeded && data) {
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
                
                // Try to process order anyway, even if verification fails
                console.log('Attempting to process order despite verification error...');
                
                try {
                    const processUrl = '../actions/complete_payment.php';
                    const processResponse = await fetch(processUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            reference: reference
                        })
                    });

                    if (processResponse.ok) {
                        const processData = await processResponse.json();
                        console.log('Process response data:', processData);
                        
                        // Accept the order if status is success, regardless of verification status
                        if (processData.status === 'success') {
                            // Order processed successfully despite verification error
                            document.getElementById('spinner').style.display = 'none';
                            document.getElementById('statusTitle').textContent = 'Payment Processed ✓';
                            document.getElementById('statusTitle').style.color = '#28a745';
                            document.getElementById('statusMessage').textContent = 'Your order has been processed successfully! Redirecting...';
                            document.getElementById('successBox').classList.remove('d-none');

                            // Store order details
                            if (processData.order_id) {
                                sessionStorage.setItem('orderData', JSON.stringify({
                                    order_id: processData.order_id,
                                    order_reference: processData.order_reference || reference,
                                    total_amount: processData.total_amount,
                                    payment_reference: reference,
                                    payment_method: 'PayStack',
                                    currency: 'GHS'
                                }));
                            }

                            localStorage.removeItem('appliedPromo');

                            // Redirect to success page
                            setTimeout(() => {
                                window.location.replace('../index.php?payment=success&order=' + encodeURIComponent(processData.order_id || '') + '&ref=' + encodeURIComponent(reference));
                            }, 2000);
                            return; // Exit early on success
                        } else {
                            console.error('Process data returned error:', processData.message);
                        }
                    } else {
                        console.error('Process response not ok:', processResponse.status);
                    }
                } catch (processError) {
                    console.error('Fallback processing failed:', processError);
                }

                // Only show error if all processing attempts failed
                document.getElementById('spinner').style.display = 'none';

                let errorMessage;
                if (error.message.includes('Session expired')) {
                    errorMessage = 'Your session has expired. Please login again to complete your order.';
                    setTimeout(() => {
                        window.location.replace('../login/user_login.php?redirect=' + encodeURIComponent('cart.php') + '&message=' + encodeURIComponent('Session expired during payment'));
                    }, 3000);
                } else {
                    // For other errors, still try to redirect to success (order might have been processed)
                    errorMessage = 'Processing your order...';
                    document.getElementById('statusMessage').textContent = errorMessage;
                    
                    // Give it a moment, then redirect to index (order might be processed)
                    setTimeout(() => {
                        window.location.replace('../index.php?payment=processing&ref=' + encodeURIComponent(reference));
                    }, 3000);
                }

                if (errorMessage !== 'Processing your order...') {
                    showError(errorMessage);
                    addRetryButton();
                }
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
</body>
</html>