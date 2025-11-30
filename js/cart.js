// Cart Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cart.js loaded');
});

function createCartPlaceholder(text = 'Product', size = '80x80', bgColor = '#8b5fbf', textColor = '#ffffff') {
    const [width, height] = size.split('x').map(Number);
    const safeText = (text || 'Gadget Garage').substring(0, 20).replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
        <rect width="100%" height="100%" rx="8" ry="8" fill="${bgColor}"/>
        <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="${Math.max(Math.floor(height * 0.25), 16)}" fill="${textColor}" text-anchor="middle" dominant-baseline="middle">${safeText}</text>
    </svg>`;
    return `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svg)))}`;
}

function handleCartImageError(event, text = 'Product', size = '80x80') {
    if (!event || !event.target) return;
    event.target.onerror = null;
    event.target.src = createCartPlaceholder(text, size);
}

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
            // Show enhanced cart popup instead of notification
            showAddedToCartPopup(data);
            updateCartBadge(data.cart_count);
            
            // Update abandoned cart tracking
            if (typeof updateCartTracking === 'function') {
                updateCartTracking();
            }

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
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Remove Item',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performRemoveFromCart(productId);
            }
        });
    }
}

function performRemoveFromCart(productId) {
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
            
            // Update abandoned cart tracking
            if (typeof updateCartTracking === 'function') {
                updateCartTracking();
            }
        } else {
            showNotification(data.message || 'Failed to remove item from cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Update price display immediately using cart item ID
function updateItemPriceDisplayByCartId(cartItemId, quantity) {
    const cartItem = document.querySelector(`[data-cart-item-id="${cartItemId}"]`);
    if (!cartItem) {
        console.log('Cart item not found for cart ID:', cartItemId);
        return;
    }

    // Find the unit price and total price using unique IDs - no more generic selectors!
    const unitPriceElement = document.getElementById('unit-price-' + cartItemId);
    const totalPriceElement = document.getElementById('total-price-' + cartItemId);

    if (unitPriceElement && totalPriceElement) {
        // Extract unit price number - remove GH₵, commas, and any other formatting
        let unitPriceText = unitPriceElement.textContent;
        unitPriceText = unitPriceText.replace('GH₵', '').replace(/,/g, '').replace(/\s/g, '');
        const unitPrice = parseFloat(unitPriceText);

        if (!isNaN(unitPrice) && unitPrice > 0) {
            // Calculate new total: unit price × quantity
            const newTotal = unitPrice * quantity;

            // Format the price properly with commas for thousands
            const formattedTotal = newTotal.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            totalPriceElement.textContent = `GH₵ ${formattedTotal}`;

            console.log(`Updated cart item ${cartItemId}: ${quantity} × GH₵ ${unitPrice} = GH₵ ${formattedTotal}`);

            // Update the cart total immediately based on all visible items
            setTimeout(() => {
                const clientTotal = calculateCartTotalClientSide();
                const formattedCartTotal = clientTotal.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                updateCartTotals(formattedCartTotal);
            }, 10);
        } else {
            console.log('Invalid unit price:', unitPriceText);
        }
    } else {
        console.log('Price elements not found for cart item:', cartItemId);
    }
}

// Legacy function for backward compatibility
function updateItemPriceDisplay(productId, quantity) {
    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
    if (!cartItem) {
        console.log('Cart item not found for product:', productId);
        return;
    }

    // Find the unit price (blue text) - more specific selector
    const unitPriceElement = cartItem.querySelector('.col-md-6 .fw-bold.text-primary.fs-5');
    // Find the total price (green text above remove button) - more specific selector
    const totalPriceElement = cartItem.querySelector('.col-md-3.text-end .fw-bold.fs-5.text-success');

    if (unitPriceElement && totalPriceElement) {
        // Extract unit price number - remove GH₵, commas, and any other formatting
        let unitPriceText = unitPriceElement.textContent;
        unitPriceText = unitPriceText.replace('GH₵', '').replace(/,/g, '').replace(/\s/g, '');
        const unitPrice = parseFloat(unitPriceText);

        if (!isNaN(unitPrice) && unitPrice > 0) {
            // Calculate new total: unit price × quantity
            const newTotal = unitPrice * quantity;

            // Format the price properly with commas for thousands
            const formattedTotal = newTotal.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            totalPriceElement.textContent = `GH₵ ${formattedTotal}`;

            console.log(`Updated: ${quantity} × GH₵ ${unitPrice} = GH₵ ${formattedTotal}`);

            // Update the cart total immediately based on all visible items
            setTimeout(() => {
                const clientTotal = calculateCartTotalClientSide();
                const formattedCartTotal = clientTotal.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                updateCartTotals(formattedCartTotal);
            }, 10);
        } else {
            console.log('Invalid unit price:', unitPriceText);
        }
    } else {
        console.log('Price elements not found for product:', productId);
    }
}

// Update quantity on server and update display
function updateQuantity(productId, quantity) {
    quantity = parseInt(quantity);

    if (quantity < 1) {
        quantity = 1;
        document.getElementById(`qty-${productId}`).value = 1;
    }

    if (quantity > 99) {
        quantity = 99;
        document.getElementById(`qty-${productId}`).value = 99;
        showNotification('Maximum quantity is 99', 'warning');
    }

    // Update display immediately
    updateItemPriceDisplay(productId, quantity);

    // Send to server
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
            updateCartBadge(data.cart_count);
            updateCartTotals(data.cart_total);
            showNotification('Cart updated', 'success');
        } else {
            showNotification(data.message || 'Failed to update cart', 'error');
            location.reload(); // Reload if server update failed
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating cart', 'error');
    });
}

// PLUS BUTTON - Add 1 to quantity and update price
function incrementQuantity(productId) {
    console.log('Plus button clicked for product:', productId);

    const quantityInput = document.getElementById(`qty-${productId}`);
    if (!quantityInput) {
        console.log('Quantity input not found for product:', productId);
        return;
    }

    // Get current quantity from input
    let currentQuantity = parseInt(quantityInput.value) || 1;
    let newQuantity = currentQuantity + 1;

    // Check maximum limit
    if (newQuantity > 99) {
        showNotification('Maximum quantity is 99', 'warning');
        return;
    }

    console.log(`Incrementing from ${currentQuantity} to ${newQuantity}`);

    // Update the input field immediately with visual feedback
    quantityInput.value = newQuantity;

    // Force visual update with slight delay to ensure DOM updates
    setTimeout(() => {
        quantityInput.value = newQuantity;
        quantityInput.setAttribute('value', newQuantity);

        // Trigger visual change event
        quantityInput.dispatchEvent(new Event('input'));
        quantityInput.dispatchEvent(new Event('change'));

        console.log(`Input field now shows: ${quantityInput.value}`);
    }, 10);

    // Update the price display immediately
    updateItemPriceDisplay(productId, newQuantity);

    // Update server in background
    updateQuantityOnServer(productId, newQuantity);
}

// MINUS BUTTON - Subtract 1 from quantity and update price
function decrementQuantity(productId) {
    console.log('Minus button clicked for product:', productId);

    const quantityInput = document.getElementById(`qty-${productId}`);
    if (!quantityInput) {
        console.log('Quantity input not found for product:', productId);
        return;
    }

    // Get current quantity from input
    let currentQuantity = parseInt(quantityInput.value) || 1;
    let newQuantity = currentQuantity - 1;

    // Check minimum limit - DON'T GO BELOW 1
    if (newQuantity < 1) {
        newQuantity = 1;
        showNotification('Minimum quantity is 1', 'info');
        quantityInput.value = 1; // Make sure input shows 1
        return; // Don't update anything if already at minimum
    }

    console.log(`Decrementing from ${currentQuantity} to ${newQuantity}`);

    // Update the input field immediately
    quantityInput.value = newQuantity;

    // Update the price display immediately
    updateItemPriceDisplay(productId, newQuantity);

    // Update server in background
    updateQuantityOnServer(productId, newQuantity);
}

// Server update function - updates cart on server
function updateQuantityOnServer(productId, quantity) {
    console.log(`Updating server: Product ${productId} to quantity ${quantity}`);

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('actions/update_quantity_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);

        if (data.success) {
            // Update cart badge and overall totals
            updateCartBadge(data.cart_count);
            updateCartTotals(data.cart_total);
            console.log('Server update successful');
            
            // Update abandoned cart tracking
            if (typeof updateCartTracking === 'function') {
                updateCartTracking();
            }
        } else {
            console.error('Server update failed:', data.message);
            showNotification(data.message || 'Failed to update cart', 'error');
            // Don't reload, keep the user's changes visible
        }
    })
    .catch(error => {
        console.error('Network error updating cart:', error);
        showNotification('Network error - changes may not be saved', 'warning');
        // Don't reload, keep the user's changes visible
    });
}

// NEW CART ITEM ID-BASED FUNCTIONS FOR MULTIPLE CONDITIONS

// UPDATE QUANTITY BY CART ID - for form input changes
function updateQuantityByCartId(cartItemId, productId, quantity) {
    quantity = parseInt(quantity);

    if (quantity < 1) {
        quantity = 1;
        document.getElementById(cartItemId).value = 1;
    }

    if (quantity > 99) {
        quantity = 99;
        document.getElementById(cartItemId).value = 99;
        showNotification('Maximum quantity is 99', 'warning');
    }

    // Update display immediately
    updateItemPriceDisplayByCartId(cartItemId, quantity);

    // Send to server
    updateQuantityOnServer(productId, quantity);
}

// INCREMENT QUANTITY BY CART ID - for plus button
function incrementQuantityByCartId(cartItemId, productId) {
    console.log('CART DEBUG: Plus button clicked for cart item:', cartItemId, 'product:', productId);

    const quantityInput = document.getElementById(cartItemId);
    if (!quantityInput) {
        console.log('Quantity input not found for cart item:', cartItemId);
        return;
    }

    // Get current quantity from input
    let currentQuantity = parseInt(quantityInput.value) || 1;
    let newQuantity = currentQuantity + 1;

    // Check maximum limit
    if (newQuantity > 99) {
        showNotification('Maximum quantity is 99', 'warning');
        return;
    }

    console.log(`Incrementing cart item ${cartItemId} from ${currentQuantity} to ${newQuantity}`);

    // Update the input field immediately
    quantityInput.value = newQuantity;

    // Update the price display immediately
    updateItemPriceDisplayByCartId(cartItemId, newQuantity);

    // Update server in background
    updateQuantityOnServer(productId, newQuantity);
}

// DECREMENT QUANTITY BY CART ID - for minus button
function decrementQuantityByCartId(cartItemId, productId) {
    console.log('CART DEBUG: Minus button clicked for cart item:', cartItemId, 'product:', productId);

    const quantityInput = document.getElementById(cartItemId);
    if (!quantityInput) {
        console.log('Quantity input not found for cart item:', cartItemId);
        return;
    }

    // Get current quantity from input
    let currentQuantity = parseInt(quantityInput.value) || 1;
    let newQuantity = currentQuantity - 1;

    // Check minimum limit - DON'T GO BELOW 1
    if (newQuantity < 1) {
        newQuantity = 1;
        showNotification('Minimum quantity is 1', 'info');
        quantityInput.value = 1; // Make sure input shows 1
        return; // Don't update anything if already at minimum
    }

    console.log(`Decrementing cart item ${cartItemId} from ${currentQuantity} to ${newQuantity}`);

    // Update the input field immediately
    quantityInput.value = newQuantity;

    // Update the price display immediately
    updateItemPriceDisplayByCartId(cartItemId, newQuantity);

    // Update server in background
    updateQuantityOnServer(productId, newQuantity);
}

// REMOVE FROM CART BY CART ID - for delete button
function removeFromCartByCartId(cartItemId, productId) {
    console.log('CART DEBUG: Delete button clicked for cart item:', cartItemId, 'product:', productId);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Remove Item',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performRemoveFromCartByCartId(cartItemId, productId);
            }
        });
    }
}

function performRemoveFromCartByCartId(cartItemId, productId) {
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

            // Remove the specific cart item from the DOM using the unique cart item ID
            const cartItem = document.querySelector(`[data-cart-item-id="${cartItemId}"]`);
            if (cartItem) {
                cartItem.style.transition = 'all 0.3s ease';
                cartItem.style.transform = 'translateX(-100%)';
                cartItem.style.opacity = '0';

                setTimeout(() => {
                    cartItem.remove();
                    checkEmptyCart();
                }, 300);
            } else {
                console.log('Cart item not found in DOM for cart ID:', cartItemId);
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

// Empty cart
function emptyCart() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Empty Cart',
            text: 'Are you sure you want to empty your cart? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Empty Cart',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performEmptyCart();
            }
        });
    }
}

function performEmptyCart() {
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

// Make updateCartBadge globally available
window.updateCartBadge = updateCartBadge;

// Update cart totals
function updateCartTotals(total) {
    const cartSubtotal = document.getElementById('cartSubtotal');
    const cartTotal = document.getElementById('cartTotal');

    if (cartSubtotal) {
        cartSubtotal.textContent = `GH₵ ${total}`;
    }

    if (cartTotal) {
        cartTotal.textContent = `GH₵ ${total}`;
    }
}

// Calculate cart total from individual item prices (client-side verification)
function calculateCartTotalClientSide() {
    let total = 0;
    const cartItems = document.querySelectorAll('.cart-item');

    cartItems.forEach(item => {
        const totalPriceElement = item.querySelector('.col-md-3.text-end .fw-bold.fs-5.text-success');
        if (totalPriceElement) {
            let priceText = totalPriceElement.textContent;
            priceText = priceText.replace('GH₵', '').replace(/,/g, '').replace(/\s/g, '');
            const itemTotal = parseFloat(priceText);
            if (!isNaN(itemTotal)) {
                total += itemTotal;
            }
        }
    });

    return total;
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

    const safeNameForAttr = (productName || 'Product').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
    const placeholderImage = createCartPlaceholder(productName, '80x80');
    const resolvedImage = (productImage && productImage.trim() !== '') ? productImage : placeholderImage;

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
                    <img src="${resolvedImage}" alt="${safeNameForAttr}" class="product-image" onerror="handleCartImageError(event, '${safeNameForAttr}', '80x80')">
                    <div class="product-info">
                        <h4>${productName}</h4>
                        <div class="price-display">
                            <span class="current-price">GH₵ <span id="modalPrice">${parseFloat(productPrice).toFixed(2)}</span></span>
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
                    <strong>Total: GH₵ <span id="modalTotal">${parseFloat(productPrice).toFixed(2)}</span></strong>
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

// Enhanced Add to Cart Popup
function showAddedToCartPopup(data) {
    // Remove existing popup
    const existingPopup = document.getElementById('addedToCartPopup');
    if (existingPopup) existingPopup.remove();

    const popupPlaceholder = createCartPlaceholder(data.product_name, '80x80', '#667eea', '#ffffff');
    const popupImage = (data.product_image && data.product_image.trim() !== '') ? data.product_image : popupPlaceholder;
    const safePopupName = (data.product_name || 'Product').replace(/"/g, '&quot;').replace(/'/g, '&apos;');

    const popup = document.createElement('div');
    popup.id = 'addedToCartPopup';
    popup.className = 'cart-popup-overlay';
    popup.innerHTML = `
        <div class="cart-popup">
            <div class="cart-popup-header">
                <h3><i class="fas fa-check-circle text-success"></i> Added to Cart!</h3>
                <button class="cart-popup-close" onclick="closeAddedToCartPopup()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="cart-popup-body">
                <div class="added-item">
                    <img src="${popupImage}" alt="${safePopupName}" class="item-image" onerror="handleCartImageError(event, '${safePopupName}', '80x80')">
                    <div class="item-details">
                        <h4>${data.product_name}</h4>
                        <div class="item-specs">
                            ${data.condition ? `<span class="condition-badge">${data.condition} Condition</span>` : ''}
                            <span class="quantity-badge">Qty: ${data.quantity || 1}</span>
                        </div>
                        <div class="item-price">GH₵${parseFloat(data.final_price || data.product_price).toLocaleString()}</div>
                    </div>
                </div>
                <div class="cart-summary">
                    <div class="cart-info">
                        <div class="cart-count">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Cart (${data.cart_count || 0})</span>
                        </div>
                        <div class="subtotal">
                            <span>Subtotal: <strong>GH₵${parseFloat(data.cart_total || '0').toLocaleString()}</strong></span>
                        </div>
                    </div>
                    ${parseFloat(data.cart_total || '0') > 200 ? '<div class="shipping-badge"><i class="fas fa-shipping-fast"></i> You earned Free Standard Shipping!</div>' : ''}
                </div>
            </div>
            <div class="cart-popup-footer">
                <button class="btn btn-outline" onclick="closeAddedToCartPopup()">Continue Shopping</button>
                <button class="btn btn-primary" onclick="viewCart()">
                    <i class="fas fa-shopping-cart"></i> View Cart (${data.cart_count || 0})
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(popup);

    // Show popup with animation
    setTimeout(() => popup.classList.add('show'), 10);

    // Auto close after 8 seconds
    setTimeout(() => {
        if (document.getElementById('addedToCartPopup')) {
            closeAddedToCartPopup();
        }
    }, 8000);
}

function closeAddedToCartPopup() {
    const popup = document.getElementById('addedToCartPopup');
    if (popup) {
        popup.classList.remove('show');
        setTimeout(() => popup.remove(), 300);
    }
}

function viewCart() {
    window.location.href = 'cart.php';
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

    /* Enhanced Added to Cart Popup Styles */
    .cart-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10001;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(3px);
    }

    .cart-popup-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .cart-popup {
        background: white;
        border-radius: 20px;
        width: 90%;
        max-width: 520px;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
        transform: scale(0.8) translateY(30px);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .cart-popup-overlay.show .cart-popup {
        transform: scale(1) translateY(0);
    }

    .cart-popup-header {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    .cart-popup-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
        pointer-events: none;
    }

    .cart-popup-header h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 1;
        position: relative;
    }

    .cart-popup-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s ease;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        position: relative;
    }

    .cart-popup-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .cart-popup-body {
        padding: 24px;
    }

    .added-item {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .added-item .item-image {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .item-details {
        flex: 1;
    }

    .item-details h4 {
        margin: 0 0 8px 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        line-height: 1.3;
    }

    .item-specs {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }

    .condition-badge {
        background: #dbeafe;
        color: #1e40af;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .quantity-badge {
        background: #f3e8ff;
        color: #7c3aed;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .item-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #059669;
    }

    .cart-summary {
        background: #fafafa;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }

    .cart-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .cart-count {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #374151;
    }

    .cart-count i {
        color: #6b7280;
    }

    .subtotal {
        font-size: 1.1rem;
        color: #374151;
    }

    .subtotal strong {
        color: #059669;
        font-size: 1.2rem;
    }

    .shipping-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #dcfdf7;
        color: #065f46;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        border: 1px solid #a7f3d0;
    }

    .shipping-badge i {
        color: #059669;
    }

    .cart-popup-footer {
        padding: 20px 24px;
        background: #f9fafb;
        display: flex;
        gap: 12px;
        border-top: 1px solid #e5e7eb;
    }

    .cart-popup-footer .btn {
        flex: 1;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .cart-popup-footer .btn-outline {
        background: white;
        color: #6b7280;
        border: 2px solid #e5e7eb;
    }

    .cart-popup-footer .btn-outline:hover {
        background: #f9fafb;
        border-color: #d1d5db;
        color: #374151;
        transform: translateY(-1px);
    }

    .cart-popup-footer .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #3b82f6);
        color: white;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
    }

    .cart-popup-footer .btn-primary:hover {
        background: linear-gradient(135deg, #4338ca, #2563eb);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
    }

    @media (max-width: 576px) {
        .cart-popup {
            width: 95%;
            margin: 0 10px;
        }

        .cart-popup-header {
            padding: 16px 20px;
        }

        .cart-popup-body {
            padding: 20px;
        }

        .added-item {
            flex-direction: column;
            text-align: center;
            align-items: center;
        }

        .cart-info {
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
        }

        .cart-popup-footer {
            padding: 16px 20px;
            flex-direction: column;
        }
    }
`;
document.head.appendChild(style);