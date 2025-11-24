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

        <h2 class="mb-3">Verifying Payment</h2>
        <p class="text-muted mb-4">Please wait while we verify your payment with PayStack...</p>

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
         * Verify payment with backend
         */
        async function verifyPayment() {
            const reference = '<?= htmlspecialchars($reference) ?>';

            try {
                console.log('Verifying payment with reference:', reference);

                // First, try the payment completion endpoint which handles expired sessions
                const completionResponse = await fetch('../actions/complete_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        reference: reference
                    })
                });

                const completionData = await completionResponse.json();
                console.log('Payment completion response:', completionData);

                if (completionData.status === 'success' && completionData.already_processed) {
                    // Payment already processed successfully
                    document.getElementById('spinner').style.display = 'none';
                    document.getElementById('successBox').classList.remove('d-none');

                    setTimeout(() => {
                        window.location.replace('../index.php?payment=success&order=' + encodeURIComponent(completionData.order_id));
                    }, 1500);
                    return;
                }

                if (completionData.requires_login) {
                    // Session expired, redirect to login
                    document.getElementById('spinner').style.display = 'none';
                    showError('Your session has expired. Redirecting to login...');
                    setTimeout(() => {
                        window.location.replace('../login/login.php?return_url=' + encodeURIComponent('../index.php?payment=failed&reason=session_expired'));
                    }, 2000);
                    return;
                }

                if (completionData.redirect_to_verify || (completionData.status === 'success' && completionData.verified)) {
                    // Payment verified by PayStack, now process the full order
                    const response = await fetch('../actions/paystack_verify_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            reference: reference
                        })
                    });

                    const data = await response.json();
                    console.log('Full verification response:', data);

                    // Hide spinner
                    document.getElementById('spinner').style.display = 'none';

                    if (data.status === 'success' && data.verified) {
                        // Payment verified successfully
                        document.getElementById('successBox').classList.remove('d-none');

                        // Store order details in sessionStorage for success page
                        sessionStorage.setItem('orderData', JSON.stringify(data));

                        // Redirect to home page
                        setTimeout(() => {
                            window.location.replace('../index.php?payment=success&order=' + encodeURIComponent(data.order_id));
                        }, 1500);

                    } else {
                        // Payment verification failed
                        const errorMsg = data.message || 'Payment verification failed';
                        showError(errorMsg);

                        // Redirect to home page with error message after 3 seconds
                        setTimeout(() => {
                            window.location.replace('../index.php?payment=failed&reason=' + encodeURIComponent(errorMsg));
                        }, 3000);
                    }
                } else {
                    // Initial payment completion failed
                    document.getElementById('spinner').style.display = 'none';
                    const errorMsg = completionData.message || 'Payment verification failed';
                    showError(errorMsg);

                    setTimeout(() => {
                        window.location.replace('../index.php?payment=failed&reason=' + encodeURIComponent(errorMsg));
                    }, 3000);
                }

            } catch (error) {
                console.error('Verification error:', error);
                document.getElementById('spinner').style.display = 'none';
                showError('Connection error. Please refresh the page or contact support.');

                // Redirect to home page after 3 seconds
                setTimeout(() => {
                    window.location.replace('../index.php?payment=failed&reason=' + encodeURIComponent('connection_error'));
                }, 3000);
            }
        }

        /**
         * Show error message
         */
        function showError(message) {
            document.getElementById('errorBox').classList.remove('d-none');
            document.getElementById('errorMessage').textContent = message;
        }

        // Start verification when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add a small delay to show the processing state
            setTimeout(verifyPayment, 1000);
        });
    </script>
</body>
</html>