<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

// Check if user is logged in
if (!check_login()) {
    header('Location: ../login/login.php');
    exit();
}

$customer_id = $_SESSION['user_id'];
$order_id = isset($_GET['order']) ? intval($_GET['order']) : null;
$reference = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '';

// Get order details
$order_details = null;
if ($order_id) {
    $order_details = get_order_by_id_ctr($order_id);
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .success-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }

        .success-box {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 3px solid #10b981;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.2);
        }

        .success-icon {
            font-size: 100px;
            color: #059669;
            margin-bottom: 20px;
            animation: bounce 1.5s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .success-title {
            font-size: 2.5rem;
            color: #065f46;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .success-subtitle {
            font-size: 1.2rem;
            color: #047857;
            margin-bottom: 40px;
        }

        .order-details {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .detail-value {
            color: #6b7280;
            font-family: monospace;
        }

        .btn-action {
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 15px;
            transition: all 0.3s ease;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 123, 255, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary-custom {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary-custom:hover {
            background: #f9fafb;
            color: #374151;
            text-decoration: none;
        }

        .confirmation-badge {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            padding: 20px;
            border-radius: 12px;
            color: #1e40af;
            margin-bottom: 30px;
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .logo-container img {
            max-height: 60px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-box">
            <div class="logo-container">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                     alt="Gadget Garage" class="img-fluid">
            </div>

            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-subtitle">Your order has been confirmed and will be processed shortly</p>

            <div class="confirmation-badge">
                <i class="fas fa-shield-check me-2"></i>
                <strong>Payment Confirmed</strong><br>
                Thank you for shopping with Gadget Garage! Your payment was processed securely via PayStack.
            </div>

            <div class="order-details">
                <h4 class="mb-3"><i class="fas fa-receipt me-2"></i>Order Details</h4>

                <?php if ($order_details): ?>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-hashtag me-2"></i>Order ID</span>
                        <span class="detail-value">#<?= htmlspecialchars($order_details['order_id'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-file-invoice me-2"></i>Invoice Number</span>
                        <span class="detail-value"><?= htmlspecialchars($order_details['invoice_no'] ?? 'N/A') ?></span>
                    </div>
                <?php endif; ?>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-credit-card me-2"></i>Payment Reference</span>
                    <span class="detail-value"><?= htmlspecialchars($reference) ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-calendar me-2"></i>Order Date</span>
                    <span class="detail-value"><?= date('F j, Y \a\t g:i A') ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-info-circle me-2"></i>Status</span>
                    <span class="detail-value">
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-check me-1"></i>Paid
                        </span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-wallet me-2"></i>Payment Method</span>
                    <span class="detail-value">PayStack</span>
                </div>
            </div>

            <div class="text-center">
                <a href="my_orders.php" class="btn-action btn-primary-custom">
                    <i class="fas fa-box me-2"></i>View My Orders
                </a>
                <a href="../index.php" class="btn-action btn-secondary-custom">
                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                </a>
            </div>

            <div class="mt-4 text-muted">
                <small>
                    <i class="fas fa-lock me-1"></i>
                    Payment secured by PayStack |
                    <i class="fas fa-truck me-1"></i>
                    Delivery within 3-5 business days
                </small>
            </div>
        </div>
    </div>

    <!-- Confetti Animation -->
    <script>
        // Create confetti animation
        function createConfetti() {
            const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'];
            const confettiCount = 60;

            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        width: 12px;
                        height: 12px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        left: ${Math.random() * 100}%;
                        top: -20px;
                        border-radius: 50%;
                        z-index: 9999;
                        pointer-events: none;
                    `;

                    document.body.appendChild(confetti);

                    const duration = 3000 + Math.random() * 1000;
                    const startTime = Date.now();

                    function animateConfetti() {
                        const elapsed = Date.now() - startTime;
                        const progress = elapsed / duration;

                        if (progress < 1) {
                            const top = progress * (window.innerHeight + 50);
                            const wobble = Math.sin(progress * 8) * 30;

                            confetti.style.top = top + 'px';
                            confetti.style.left = `calc(${confetti.style.left} + ${wobble}px)`;
                            confetti.style.opacity = 1 - progress;
                            confetti.style.transform = `rotate(${progress * 720}deg)`;

                            requestAnimationFrame(animateConfetti);
                        } else {
                            confetti.remove();
                        }
                    }

                    animateConfetti();
                }, i * 50);
            }
        }

        // Start confetti when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(createConfetti, 500);
        });

        // Optional: Store order data in localStorage for future reference
        if (typeof(Storage) !== "undefined") {
            const orderData = {
                order_id: '<?= $order_id ?>',
                reference: '<?= $reference ?>',
                date: '<?= date('Y-m-d H:i:s') ?>',
                status: 'completed'
            };
            localStorage.setItem('lastOrder', JSON.stringify(orderData));
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>