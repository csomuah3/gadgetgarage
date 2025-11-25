// Checkout and Payment Simulation JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Checkout.js loaded');
    initializePaymentMethods();
    initializePaymentModal();
    initializeBillingAddressToggle();
    initializeFormValidation();
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

            // Update payment method for processing - All methods go through PayStack
            const selectedMethod = this.getAttribute('data-method');
            console.log('Selected payment method:', selectedMethod, '- Will process via PayStack');
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

// Process checkout with PayStack payment
function processCheckout() {
    // Validate forms first
    if (!validateCheckoutForms()) {
        return;
    }

    // Prompt for customer email
    promptForEmailAndPay();
}

// Prompt for customer email before payment
async function promptForEmailAndPay() {
    const { value: email } = await Swal.fire({
        title: 'Complete Your Payment',
        input: 'email',
        inputLabel: 'Email Address',
        inputPlaceholder: 'Enter your email for payment confirmation',
        inputValidator: (value) => {
            if (!value) {
                return 'Email is required for payment processing!';
            }
            if (!isValidEmail(value)) {
                return 'Please enter a valid email address!';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Proceed to Payment',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'payment-email-modal'
        }
    });

    if (email) {
        // Initialize PayStack payment
        initializePayStackPayment(email);
    }
}

// Validate email format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Initialize PayStack payment
function initializePayStackPayment(email) {
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    const originalText = confirmBtn.innerHTML;

    // Show loading state
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Initializing Payment...';
    confirmBtn.disabled = true;

    // Get final total (including any applied discounts)
    const finalTotalElement = document.getElementById('finalTotal');
    let finalTotal = null;
    if (finalTotalElement) {
        const totalText = finalTotalElement.textContent.replace(/[^\d.]/g, '');
        finalTotal = parseFloat(totalText);
    }

    // Prepare request data
    const requestData = { email: email };
    if (finalTotal && finalTotal > 0) {
        requestData.total_amount = finalTotal;
    }

    // Call backend to initialize PayStack transaction
    // Use ACTIONS_PATH if defined (from checkout.php), otherwise use relative path
    const initUrl = (typeof ACTIONS_PATH !== 'undefined' ? ACTIONS_PATH : '../actions/') + 'paystack_init_transaction.php';
    
    console.log('Initializing PayStack payment, URL:', initUrl);
    
    fetch(initUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('PayStack initialization response:', data);

        if (data.status === 'success') {
            // Hide payment modal
            const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            paymentModal.hide();

            // Show redirecting message with SweetAlert
            Swal.fire({
                title: 'Redirecting to PayStack',
                text: 'Please wait while we redirect you to the secure payment gateway...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                timer: 2000,
                timerProgressBar: true,
                didClose: () => {
                    window.location.href = data.authorization_url;
                }
            });
        } else {
            Swal.fire({
                title: 'Payment Error',
                text: data.message || 'Failed to initialize payment. Please try again.',
                icon: 'error',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#dc3545'
            });
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error initializing PayStack payment:', error);
        Swal.fire({
            title: 'Connection Error',
            text: 'An error occurred while initializing payment. Please check your connection and try again.',
            icon: 'error',
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#dc3545'
        });
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

// Show success modal with order details
function showSuccessModal(orderData) {
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const orderDetailsContainer = document.getElementById('orderSuccessDetails');

    // Generate tracking number
    const trackingNumber = generateTrackingNumber();

    // Populate order details
    orderDetailsContainer.innerHTML = `
        <div class="mb-4">
            <div class="fw-bold text-success fs-4 mb-2">
                Order #${orderData.order_reference || orderData.order_id}
            </div>
            <div class="text-muted mb-2">
                Order ID: ${orderData.order_id}
            </div>
            <div class="text-muted mb-2">
                <strong>Tracking Number:</strong> <span class="text-primary">${trackingNumber}</span>
            </div>
            <div class="fw-bold fs-5 text-primary">
                Total Paid: GH₵ ${orderData.total_amount}
            </div>
        </div>

        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            Your order has been successfully processed and will be shipped shortly.
        </div>

        <div class="alert alert-info">
            <i class="fas fa-envelope me-2"></i>
            <strong>Email Confirmation:</strong> Sent to your registered email address<br>
            <i class="fas fa-sms me-2"></i>
            <strong>SMS Confirmation:</strong> Sent to your phone number with tracking details
        </div>

        <div class="text-muted small">
            <p><strong>Tracking Number:</strong> ${trackingNumber}</p>
            <p>Use this number to track your package on our website or mobile app.</p>
            <p><strong>Estimated delivery:</strong> 3-5 business days</p>
        </div>
    `;

    successModal.show();

    // Simulate sending confirmations
    sendOrderConfirmations(orderData, trackingNumber);

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

// Initialize billing address toggle
function initializeBillingAddressToggle() {
    const sameBillingCheckbox = document.getElementById('sameBillingAddress');
    const billingForm = document.getElementById('billingAddressForm');

    if (sameBillingCheckbox && billingForm) {
        sameBillingCheckbox.addEventListener('change', function() {
            if (this.checked) {
                billingForm.style.display = 'none';
            } else {
                billingForm.style.display = 'block';
            }
        });
    }
}

// Initialize form validation
function initializeFormValidation() {
    // Add real-time validation feedback
    const forms = ['contactForm', 'shippingForm', 'billingForm'];

    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            const inputs = form.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });
        }
    });
}

// Validate individual field
function validateField(field) {
    if (field.value.trim() === '') {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    } else {
        field.classList.add('is-valid');
        field.classList.remove('is-invalid');
    }
}

// Validate all checkout forms before payment
function validateCheckoutForms() {
    const requiredForms = ['contactForm', 'shippingForm'];
    let isValid = true;

    // Check if billing form needs validation
    const sameBillingCheckbox = document.getElementById('sameBillingAddress');
    if (sameBillingCheckbox && !sameBillingCheckbox.checked) {
        requiredForms.push('billingForm');
    }

    requiredForms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            const requiredFields = form.querySelectorAll('input[required], select[required]');

            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
        }
    });

    if (!isValid) {
        Swal.fire({
            title: 'Form Incomplete',
            text: 'Please fill in all required fields before proceeding.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ffc107'
        }).then(() => {
            // Scroll to first invalid field
            const firstInvalid = document.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        });
    }

    return isValid;
}

// Generate tracking number
function generateTrackingNumber() {
    const prefix = 'FLV';
    const date = new Date().toISOString().slice(2, 10).replace(/-/g, ''); // YYMMDD
    const random = Math.floor(Math.random() * 900000) + 100000; // 6 digit random number
    return `${prefix}${date}${random}`;
}

// Send order confirmations (simulated)
function sendOrderConfirmations(orderData, trackingNumber) {
    // Simulate email sending
    setTimeout(() => {
        showNotification('✉️ Email confirmation sent successfully!', 'success');
        console.log('Email sent with details:', {
            orderId: orderData.order_id,
            trackingNumber: trackingNumber,
            amount: orderData.total_amount
        });
    }, 3000);

    // Simulate SMS sending
    setTimeout(() => {
        showNotification('📱 SMS confirmation sent with tracking details!', 'success');
        console.log('SMS sent with tracking number:', trackingNumber);
    }, 4000);

    // Log confirmation details for demo purposes
    console.log('=== ORDER CONFIRMATION SENT ===');
    console.log('Email Content:');
    console.log(`Subject: Order Confirmation - FlavorHub Order #${orderData.order_id}`);
    console.log(`Dear Customer,

Thank you for your order with FlavorHub!

Order Details:
- Order ID: ${orderData.order_id}
- Tracking Number: ${trackingNumber}
- Total Amount: GH₵ ${orderData.total_amount}
- Estimated Delivery: 3-5 business days

Track your order: Use tracking number ${trackingNumber} on our website.

Best regards,
FlavorHub Team`);

    console.log('\nSMS Content:');
    console.log(`FlavorHub: Your order #${orderData.order_id} is confirmed! Tracking: ${trackingNumber}. Total: GH₵ ${orderData.total_amount}. Delivery: 3-5 days. Track at flavorhub.com`);
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