// Checkout and Payment Simulation JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Checkout.js loaded');
    initializePaymentMethods();
    initializePaymentModal();
});

// Initialize payment method selection
function initializePaymentMethods() {
    const paymentOptions = document.querySelectorAll('.payment-option');

    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            paymentOptions.forEach(opt => opt.classList.remove('selected'));

            // Add selected class to clicked option
            this.classList.add('selected');

            // Update payment method for processing
            const selectedMethod = this.getAttribute('data-method');
            console.log('Selected payment method:', selectedMethod);
        });
    });
}

// Initialize payment simulation modal
function initializePaymentModal() {
    const simulatePaymentBtn = document.getElementById('simulatePaymentBtn');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');

    if (simulatePaymentBtn) {
        simulatePaymentBtn.addEventListener('click', showPaymentModal);
    }

    if (confirmPaymentBtn) {
        confirmPaymentBtn.addEventListener('click', processCheckout);
    }
}

// Show payment simulation modal
function showPaymentModal() {
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
}

// Process checkout and handle payment simulation
function processCheckout() {
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    const originalText = confirmBtn.innerHTML;

    // Show loading state
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    confirmBtn.disabled = true;

    // Simulate processing delay
    setTimeout(() => {
        fetch('actions/process_checkout_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide payment modal
                const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                paymentModal.hide();

                // Show success modal with order details
                showSuccessModal(data);
            } else {
                showNotification(data.message || 'Payment failed. Please try again.', 'error');
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred during payment processing.', 'error');
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        });
    }, 2000); // 2 second delay to simulate payment processing
}

// Show success modal with order details
function showSuccessModal(orderData) {
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const orderDetailsContainer = document.getElementById('orderSuccessDetails');

    // Populate order details
    orderDetailsContainer.innerHTML = `
        <div class="mb-4">
            <div class="fw-bold text-success fs-4 mb-2">
                Order #${orderData.order_reference || orderData.order_id}
            </div>
            <div class="text-muted mb-2">
                Order ID: ${orderData.order_id}
            </div>
            <div class="fw-bold fs-5 text-primary">
                Total Paid: GHS ${orderData.total_amount}
            </div>
        </div>

        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            Your order has been successfully processed and will be shipped shortly.
        </div>

        <div class="text-muted small">
            <p>You will receive an email confirmation with your order details.</p>
            <p>Estimated delivery: 3-5 business days</p>
        </div>
    `;

    successModal.show();

    // Update page to show order completion
    setTimeout(() => {
        // Clear cart badge
        updateCartBadge(0);

        // Optionally redirect after showing success
        // window.location.href = 'index.php';
    }, 1000);
}

// Update cart badge (shared with cart.js)
function updateCartBadge(count) {
    const cartBadge = document.querySelector('.cart-badge');
    const cartLink = document.querySelector('[href="cart.php"]');

    if (cartBadge) {
        if (count > 0) {
            cartBadge.textContent = count;
            cartBadge.style.display = 'flex';
        } else {
            cartBadge.style.display = 'none';
        }
    }

    if (cartLink && cartLink.textContent.includes('Cart')) {
        cartLink.innerHTML = `<i class="fas fa-shopping-cart"></i> Cart (${count})`;
    }
}

// Show notification (shared with cart.js)
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast alert alert-${getBootstrapAlertClass(type)} position-fixed`;
    notification.style.cssText = `
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border-radius: 10px;
        animation: slideInRight 0.3s ease;
    `;

    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${getNotificationIcon(type)} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Helper function to get Bootstrap alert class
function getBootstrapAlertClass(type) {
    switch (type) {
        case 'success': return 'success';
        case 'error': return 'danger';
        case 'warning': return 'warning';
        default: return 'info';
    }
}

// Helper function to get notification icon
function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}

// Handle payment modal events
document.addEventListener('DOMContentLoaded', function() {
    const paymentModal = document.getElementById('paymentModal');

    if (paymentModal) {
        paymentModal.addEventListener('hidden.bs.modal', function () {
            // Reset the confirm button when modal is closed
            const confirmBtn = document.getElementById('confirmPaymentBtn');
            if (confirmBtn) {
                confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Complete Payment';
                confirmBtn.disabled = false;
            }
        });
    }
});

// Add CSS for animations if not already added
if (!document.querySelector('#checkout-animations')) {
    const style = document.createElement('style');
    style.id = 'checkout-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .payment-option {
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.15);
        }

        .modal.payment-modal .modal-dialog {
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}