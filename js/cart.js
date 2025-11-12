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
    // Convert to integer and enforce limits
    quantity = parseInt(quantity);

    if (quantity < 1) {
        quantity = 1; // Don't allow less than 1
        document.getElementById(`qty-${productId}`).value = 1;
    }

    if (quantity > 99) {
        quantity = 99;
        showNotification('Maximum quantity is 99', 'warning');
        document.getElementById(`qty-${productId}`).value = 99;
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

            // Update the subtotal for this item - more comprehensive approach
            const cartItem = document.querySelector(`[data-product-id="${productId}"]`) ||
                           document.querySelector(`tr[data-product-id="${productId}"]`) ||
                           document.querySelector(`div[data-product-id="${productId}"]`);

            if (cartItem) {
                // Find the unit price (try multiple approaches)
                let unitPrice = 0;
                const possibleUnitPriceElements = [
                    cartItem.querySelector('.unit-price'),
                    cartItem.querySelector('[data-unit-price]'),
                    cartItem.querySelector('.fw-bold.text-primary.fs-5'),
                    cartItem.querySelector('.product-price'),
                    cartItem.querySelector('.price-per-unit')
                ];

                for (let element of possibleUnitPriceElements) {
                    if (element) {
                        const priceText = element.textContent || element.getAttribute('data-unit-price') || '';
                        const extractedPrice = parseFloat(priceText.replace(/[^\d.]/g, ''));
                        if (!isNaN(extractedPrice) && extractedPrice > 0) {
                            unitPrice = extractedPrice;
                            break;
                        }
                    }
                }

                // Find the total price element to update
                const totalPriceElements = [
                    cartItem.querySelector('.item-total'),
                    cartItem.querySelector('.fw-bold.fs-5.text-success'),
                    cartItem.querySelector('.total-price'),
                    cartItem.querySelector('.item-price-total'),
                    cartItem.querySelector('[class*="total"]')
                ];

                let totalPriceElement = null;
                for (let element of totalPriceElements) {
                    if (element) {
                        totalPriceElement = element;
                        break;
                    }
                }

                if (unitPrice > 0 && totalPriceElement) {
                    const newSubtotal = (unitPrice * quantity).toFixed(2);
                    totalPriceElement.textContent = `GHS ${newSubtotal}`;

                    // Add visual feedback
                    totalPriceElement.style.fontWeight = 'bold';
                    totalPriceElement.style.color = '#059669';
                } else {
                    // If we can't find price elements, reload page for updated totals
                    console.log('Could not find price elements, reloading...');
                    setTimeout(() => location.reload(), 1000);
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

// Increment quantity
function incrementQuantity(productId) {
    const quantityInput = document.getElementById(`qty-${productId}`);
    if (quantityInput) {
        let currentQuantity = parseInt(quantityInput.value) || 1;
        let newQuantity = currentQuantity + 1;

        if (newQuantity > 99) {
            showNotification('Maximum quantity is 99', 'warning');
            return;
        }

        quantityInput.value = newQuantity;
        updateQuantity(productId, newQuantity);
    }
}

// Decrement quantity
function decrementQuantity(productId) {
    const quantityInput = document.getElementById(`qty-${productId}`);
    if (quantityInput) {
        let currentQuantity = parseInt(quantityInput.value) || 1;
        let newQuantity = currentQuantity - 1;

        if (newQuantity < 1) {
            newQuantity = 1;
            showNotification('Minimum quantity is 1', 'info');
        }

        quantityInput.value = newQuantity;
        updateQuantity(productId, newQuantity);
    }
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

// Enhanced Add to Cart Modal
function showAddToCartModal(productId, productName, productPrice, productImage) {
    // Remove existing modal
    const existingModal = document.getElementById('addToCartModal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'addToCartModal';
    modal.className = 'cart-modal-overlay';
    modal.innerHTML = `
        <div class="cart-modal">
            <div class="cart-modal-header">
                <h3><i class="fas fa-shopping-cart"></i> Add to Cart</h3>
                <button class="cart-modal-close" onclick="closeAddToCartModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="cart-modal-body">
                <div class="product-preview">
                    <img src="${productImage || 'https://via.placeholder.com/80x80/8b5fbf/ffffff?text=Product'}" alt="${productName}" class="product-image" onerror="this.src='https://via.placeholder.com/80x80/8b5fbf/ffffff?text=Product'">
                    <div class="product-info">
                        <h4>${productName}</h4>
                        <div class="price-display">
                            <span class="current-price">GHS <span id="modalPrice">${parseFloat(productPrice).toFixed(2)}</span></span>
                        </div>
                    </div>
                </div>
                <div class="quantity-controls">
                    <label>Quantity:</label>
                    <div class="quantity-input-group">
                        <button class="quantity-btn minus" onclick="updateModalQuantity(-1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="modalQuantity" value="1" min="1" max="99" readonly>
                        <button class="quantity-btn plus" onclick="updateModalQuantity(1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="total-price">
                    <strong>Total: GHS <span id="modalTotal">${parseFloat(productPrice).toFixed(2)}</span></strong>
                </div>
            </div>
            <div class="cart-modal-footer">
                <button class="btn btn-secondary" onclick="closeAddToCartModal()">Cancel</button>
                <button class="btn btn-primary" onclick="confirmAddToCart(${productId})" id="confirmAddBtn">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Store modal data
    window.modalData = {
        productId: productId,
        productName: productName,
        unitPrice: parseFloat(productPrice),
        quantity: 1
    };

    // Show modal with animation
    setTimeout(() => modal.classList.add('show'), 10);
}

function updateModalQuantity(change) {
    const quantityInput = document.getElementById('modalQuantity');
    const priceElement = document.getElementById('modalPrice');
    const totalElement = document.getElementById('modalTotal');

    if (!quantityInput || !window.modalData) return;

    let newQuantity = window.modalData.quantity + change;

    // Enforce minimum of 1 and maximum of 99
    if (newQuantity < 1) newQuantity = 1;
    if (newQuantity > 99) newQuantity = 99;

    window.modalData.quantity = newQuantity;
    quantityInput.value = newQuantity;

    // Update total price
    const total = (window.modalData.unitPrice * newQuantity).toFixed(2);
    totalElement.textContent = total;

    // Add visual feedback
    quantityInput.style.transform = 'scale(1.1)';
    setTimeout(() => quantityInput.style.transform = 'scale(1)', 200);
}

function confirmAddToCart(productId) {
    if (!window.modalData) return;

    const confirmBtn = document.getElementById('confirmAddBtn');
    const originalText = confirmBtn.innerHTML;

    // Show loading state
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    confirmBtn.disabled = true;

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', window.modalData.quantity);

    fetch('actions/add_to_cart_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success animation
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Added!';
            confirmBtn.classList.add('btn-success');
            confirmBtn.classList.remove('btn-primary');

            updateCartBadge(data.cart_count);

            // Show success notification
            showNotification(`Added ${window.modalData.quantity} item(s) to cart successfully!`, 'success');

            // Close modal after delay
            setTimeout(() => {
                closeAddToCartModal();
            }, 1500);
        } else {
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
            showNotification(data.message || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function closeAddToCartModal() {
    const modal = document.getElementById('addToCartModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
    window.modalData = null;
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

// Add CSS for animations and modal styles
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

    /* Enhanced Add to Cart Modal Styles */
    .cart-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .cart-modal-overlay.show {
        opacity: 1;
    }

    .cart-modal {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        transform: scale(0.8);
        transition: transform 0.3s ease;
        overflow: hidden;
    }

    .cart-modal-overlay.show .cart-modal {
        transform: scale(1);
    }

    .cart-modal-header {
        background: #4f46e5;
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .cart-modal-header h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cart-modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: background 0.2s ease;
    }

    .cart-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .cart-modal-body {
        padding: 24px;
    }

    .product-preview {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .product-preview .product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid #f3f4f6;
    }

    .product-info h4 {
        margin: 0 0 8px 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        line-height: 1.3;
    }

    .price-display .current-price {
        font-size: 1.3rem;
        font-weight: 700;
        color: #059669;
    }

    .quantity-controls {
        margin-bottom: 20px;
    }

    .quantity-controls label {
        display: block;
        margin-bottom: 12px;
        font-weight: 600;
        color: #374151;
    }

    .quantity-input-group {
        display: flex;
        align-items: center;
        gap: 0;
        width: fit-content;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
    }

    .quantity-btn {
        background: #f9fafb;
        border: none;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.9rem;
        color: #6b7280;
    }

    .quantity-btn:hover {
        background: #e5e7eb;
        color: #374151;
    }

    .quantity-btn:active {
        transform: scale(0.95);
    }

    .quantity-btn.minus {
        border-right: 1px solid #e5e7eb;
    }

    .quantity-btn.plus {
        border-left: 1px solid #e5e7eb;
    }

    #modalQuantity {
        border: none;
        width: 60px;
        height: 44px;
        text-align: center;
        font-size: 1.1rem;
        font-weight: 600;
        background: white;
        outline: none;
        transition: transform 0.2s ease;
    }

    .total-price {
        background: #f0f9ff;
        padding: 16px;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 20px;
        border: 2px solid #bae6fd;
    }

    .total-price strong {
        font-size: 1.4rem;
        color: #0c4a6e;
    }

    .cart-modal-footer {
        padding: 20px 24px;
        background: #f9fafb;
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .cart-modal-footer .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cart-modal-footer .btn-secondary {
        background: #e5e7eb;
        color: #6b7280;
    }

    .cart-modal-footer .btn-secondary:hover {
        background: #d1d5db;
        color: #374151;
    }

    .cart-modal-footer .btn-primary {
        background: #4f46e5;
        color: white;
    }

    .cart-modal-footer .btn-primary:hover {
        background: #4338ca;
    }

    .cart-modal-footer .btn-success {
        background: #059669;
        color: white;
    }

    @media (max-width: 576px) {
        .cart-modal {
            width: 95%;
            margin: 0 10px;
        }

        .cart-modal-header {
            padding: 16px 20px;
        }

        .cart-modal-body {
            padding: 20px;
        }

        .product-preview {
            flex-direction: column;
            text-align: center;
        }

        .cart-modal-footer {
            padding: 16px 20px;
            flex-direction: column;
        }
    }
`;
document.head.appendChild(style);