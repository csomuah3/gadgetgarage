// Cart Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cart.js loaded');
});

// Add item to cart
function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    // Show loading state
    const addBtn = document.querySelector(`[onclick*="addToCart(${productId})"]`);
    if (addBtn) {
        const originalText = addBtn.innerHTML;
        addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        addBtn.disabled = true;
    }

    fetch('actions/add_to_cart_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart successfully!', 'success');
            updateCartBadge(data.cart_count);

            // Update button text temporarily
            if (addBtn) {
                addBtn.innerHTML = '<i class="fas fa-check"></i> Added!';
                addBtn.classList.remove('btn-primary');
                addBtn.classList.add('btn-success');

                setTimeout(() => {
                    addBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    addBtn.classList.remove('btn-success');
                    addBtn.classList.add('btn-primary');
                    addBtn.disabled = false;
                }, 2000);
            }
        } else {
            showNotification(data.message || 'Failed to add product to cart', 'error');
            if (addBtn) {
                addBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                addBtn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        if (addBtn) {
            addBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
            addBtn.disabled = false;
        }
    });
}

// Remove item from cart
function removeFromCart(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('actions/remove_from_cart_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Item removed from cart', 'success');

                // Remove the cart item from the DOM
                const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
                if (cartItem) {
                    cartItem.style.transition = 'all 0.3s ease';
                    cartItem.style.transform = 'translateX(-100%)';
                    cartItem.style.opacity = '0';

                    setTimeout(() => {
                        cartItem.remove();
                        checkEmptyCart();
                    }, 300);
                }

                updateCartBadge(data.cart_count);
                updateCartTotals(data.cart_total);
            } else {
                showNotification(data.message || 'Failed to remove item from cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
}

// Update quantity
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }

    if (quantity > 99) {
        showNotification('Maximum quantity is 99', 'warning');
        document.getElementById(`qty-${productId}`).value = 99;
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('actions/update_quantity_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Quantity updated', 'success');
            updateCartBadge(data.cart_count);
            updateCartTotals(data.cart_total);

            // Update the subtotal for this item
            const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
            if (cartItem) {
                const priceElement = cartItem.querySelector('.fw-bold.fs-5.text-success');
                const unitPrice = parseFloat(cartItem.querySelector('.fw-bold.text-primary.fs-5').textContent.replace('GHS ', ''));
                const newSubtotal = (unitPrice * quantity).toFixed(2);
                if (priceElement) {
                    priceElement.textContent = `GHS ${newSubtotal}`;
                }
            }
        } else {
            showNotification(data.message || 'Failed to update quantity', 'error');
            // Reset the input to previous value
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Empty cart
function emptyCart() {
    if (confirm('Are you sure you want to empty your cart? This action cannot be undone.')) {
        fetch('actions/empty_cart_action.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Cart emptied successfully', 'success');
                updateCartBadge(0);
                updateCartTotals('0.00');

                // Hide cart items and show empty state
                const cartContainer = document.getElementById('cartItemsContainer');
                if (cartContainer) {
                    cartContainer.style.transition = 'all 0.5s ease';
                    cartContainer.style.opacity = '0';

                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            } else {
                showNotification(data.message || 'Failed to empty cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
}

// Proceed to checkout
function proceedToCheckout() {
    window.location.href = 'checkout.php';
}

// Update cart badge
function updateCartBadge(count) {
    const cartBadge = document.getElementById('cartBadge');
    if (cartBadge) {
        if (count > 0) {
            cartBadge.textContent = count;
            cartBadge.style.display = 'flex';
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

// Update cart totals
function updateCartTotals(total) {
    const cartSubtotal = document.getElementById('cartSubtotal');
    const cartTotal = document.getElementById('cartTotal');

    if (cartSubtotal) {
        cartSubtotal.textContent = `GHS ${total}`;
    }

    if (cartTotal) {
        cartTotal.textContent = `GHS ${total}`;
    }
}

// Check if cart is empty and show appropriate content
function checkEmptyCart() {
    const cartContainer = document.getElementById('cartItemsContainer');
    if (cartContainer && cartContainer.children.length === 0) {
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
}

// Show notification
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

// Add CSS for animations
const style = document.createElement('style');
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
`;
document.head.appendChild(style);