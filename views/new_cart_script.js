// Clean cart functionality with SweetAlert
class CartManager {
    constructor() {
        // Determine the base URL dynamically based on current page location
        const currentPath = window.location.pathname;
        if (currentPath.includes('/views/')) {
            this.baseUrl = '../actions/';
        } else {
            this.baseUrl = 'actions/';
        }
        console.log('Cart Manager base URL:', this.baseUrl);
        this.init();
    }

    init() {
        // Initialize any event listeners if needed
        console.log('Cart Manager initialized');
    }

    // Update quantity function
    updateQuantity(productId, newQuantity, cartItemId) {
        console.log('updateQuantity called:', productId, newQuantity, cartItemId);

        // Validate quantity
        if (newQuantity < 1) {
            this.showError('Minimum quantity is 1');
            return;
        }
        if (newQuantity > 99) {
            this.showError('Maximum quantity is 99');
            return;
        }

        // Show loading
        this.showLoading('Updating quantity...');

        // Prepare form data
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', newQuantity);

        console.log('Sending request to:', this.baseUrl + 'update_cart_quantity.php');
        console.log('FormData:', Array.from(formData.entries()));

        // Send request
        fetch(this.baseUrl + 'update_cart_quantity.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed response:', data);

                if (data.success) {
                    this.showSuccess(data.message);
                    this.updateCartDisplay(data);
                    this.updateItemQuantity(productId, newQuantity, data.item_total, cartItemId);
                } else {
                    this.showError(data.message || 'Failed to update quantity');
                    this.resetQuantityInput(productId, cartItemId);
                }
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Raw response was:', text);
                this.showError('Server returned invalid response');
                this.resetQuantityInput(productId, cartItemId);
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            this.showError('Failed to update quantity. Please try again.');
            this.resetQuantityInput(productId, cartItemId);
        });
    }

    // Remove item function
    removeItem(productId, cartItemId) {
        Swal.fire({
            title: 'Remove Item?',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Yes, remove it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                this.performRemoveItem(productId, cartItemId);
            }
        });
    }

    performRemoveItem(productId, cartItemId) {
        // Show loading
        this.showLoading('Removing item...');

        // Prepare form data
        const formData = new FormData();
        formData.append('product_id', productId);

        // Send request
        fetch(this.baseUrl + 'remove_cart_item.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess(data.message);
                this.updateCartDisplay(data);
                this.removeItemFromDOM(cartItemId);
            } else {
                this.showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showError('Failed to remove item. Please try again.');
        });
    }

    // Empty cart function
    emptyCart() {
        Swal.fire({
            title: 'Empty Cart?',
            text: 'Are you sure you want to remove all items from your cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt"></i> Yes, empty cart!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                this.performEmptyCart();
            }
        });
    }

    performEmptyCart() {
        // Show loading
        this.showLoading('Emptying cart...');

        // Send request
        fetch(this.baseUrl + 'empty_cart.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess(data.message);
                this.updateCartDisplay(data);
                this.showEmptyCartMessage();
            } else {
                this.showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showError('Failed to empty cart. Please try again.');
        });
    }

    // Increment quantity
    incrementQuantity(productId, cartItemId) {
        const input = document.getElementById(`qty-${cartItemId}`);
        if (input) {
            const currentValue = parseInt(input.value) || 1;
            const newValue = Math.min(currentValue + 1, 99);
            input.value = newValue;
            this.updateQuantity(productId, newValue, cartItemId);
        }
    }

    // Decrement quantity
    decrementQuantity(productId, cartItemId) {
        const input = document.getElementById(`qty-${cartItemId}`);
        if (input) {
            const currentValue = parseInt(input.value) || 1;
            const newValue = Math.max(currentValue - 1, 1);
            input.value = newValue;
            this.updateQuantity(productId, newValue, cartItemId);
        }
    }

    // Helper functions
    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }

    showLoading(message) {
        Swal.fire({
            title: 'Please wait...',
            text: message,
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
    }

    updateCartDisplay(data) {
        // Update cart total
        const cartTotal = document.getElementById('cartTotal');
        const cartSubtotal = document.getElementById('cartSubtotal');

        if (cartTotal) {
            cartTotal.textContent = `GH₵ ${data.cart_total}`;
        }
        if (cartSubtotal) {
            cartSubtotal.textContent = `GH₵ ${data.cart_total}`;
        }

        // Update cart badge
        const cartBadge = document.getElementById('cartBadge');
        if (cartBadge) {
            if (data.cart_count > 0) {
                cartBadge.textContent = data.cart_count;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }
        }

        // Update cart count text
        const cartCountText = document.querySelector('.cart-header p');
        if (cartCountText) {
            if (data.cart_count > 0) {
                cartCountText.textContent = `You have ${data.cart_count} item${data.cart_count > 1 ? 's' : ''} in your cart`;
            } else {
                cartCountText.textContent = 'Your cart is currently empty';
            }
        }
    }

    updateItemQuantity(productId, quantity, itemTotal, cartItemId) {
        // Update the quantity input using the specific cart item ID
        const input = document.getElementById(`qty-${cartItemId}`);
        if (input) {
            input.value = quantity;
        }

        // Update item total price using the specific cart item ID
        const totalPriceElement = document.getElementById(`total-price-${cartItemId}`);
        if (totalPriceElement) {
            totalPriceElement.textContent = `GH₵ ${itemTotal}`;
        }
    }

    resetQuantityInput(productId, cartItemId) {
        const input = document.getElementById(`qty-${cartItemId}`);
        if (input && input.defaultValue) {
            input.value = input.defaultValue;
        }
    }

    removeItemFromDOM(cartItemId) {
        const cartItem = document.querySelector(`[data-cart-item-id="${cartItemId}"]`);
        if (cartItem) {
            cartItem.style.transition = 'all 0.3s ease';
            cartItem.style.opacity = '0';
            cartItem.style.transform = 'translateX(-100%)';

            setTimeout(() => {
                cartItem.remove();
                this.checkEmptyCart();
            }, 300);
        }
    }

    showEmptyCartMessage() {
        const cartContainer = document.getElementById('cartItemsContainer');
        if (cartContainer) {
            cartContainer.innerHTML = '';
        }

        // Reload page to show empty cart message
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    checkEmptyCart() {
        const remainingItems = document.querySelectorAll('[data-cart-item-id]');
        if (remainingItems.length === 0) {
            this.showEmptyCartMessage();
        }
    }
}

// Initialize cart manager
const cartManager = new CartManager();

// Global functions for cart operations
function updateQuantity(productId, quantity, cartItemId) {
    cartManager.updateQuantity(productId, quantity, cartItemId);
}

function incrementQuantity(productId, cartItemId) {
    cartManager.incrementQuantity(productId, cartItemId);
}

function decrementQuantity(productId, cartItemId) {
    cartManager.decrementQuantity(productId, cartItemId);
}

function removeItem(productId, cartItemId) {
    cartManager.removeItem(productId, cartItemId);
}

function emptyCart() {
    cartManager.emptyCart();
}

// NEW: Cart ID-based functions for multiple conditions support
function incrementQuantityByCartId(cartItemId, productId) {
    cartManager.incrementQuantity(productId, cartItemId.replace('qty-', ''));
}

function decrementQuantityByCartId(cartItemId, productId) {
    cartManager.decrementQuantity(productId, cartItemId.replace('qty-', ''));
}

function updateQuantityByCartId(cartItemId, productId, quantity) {
    cartManager.updateQuantity(productId, quantity, cartItemId.replace('qty-', ''));
}

function removeFromCartByCartId(cartItemId, productId) {
    cartManager.removeItem(productId, cartItemId);
}

// NEW: Update product condition in cart with dynamic price
function updateCondition(selectElement) {
    const cartItemId = selectElement.getAttribute('data-cart-item-id');
    const productId = selectElement.getAttribute('data-product-id');
    const newCondition = selectElement.value;

    console.log('Condition change:', { cartItemId, productId, newCondition });

    // Get the unit price element
    const unitPriceElement = document.getElementById('unit-price-' + cartItemId);
    if (!unitPriceElement) {
        console.error('Unit price element not found:', cartItemId);
        return;
    }

    // Get current price
    let currentPriceText = unitPriceElement.textContent.replace('GH₵', '').replace(/,/g, '').trim();
    let currentPrice = parseFloat(currentPriceText);
    
    // Calculate multiplier based on condition
    let multiplier = 1.0;
    switch (newCondition) {
        case 'excellent':
            multiplier = 1.0; // 100%
            break;
        case 'good':
            multiplier = 0.9; // 90%
            break;
        case 'fair':
            multiplier = 0.8; // 80%
            break;
    }

    // Calculate new price
    let newPrice = currentPrice * multiplier;

    // Update the unit price display
    unitPriceElement.textContent = `GH₵ ${newPrice.toFixed(2)}`;

    // Update total price for this item
    const quantityInput = document.getElementById(`qty-${cartItemId}`);
    if (quantityInput) {
        const quantity = parseInt(quantityInput.value) || 1;
        const totalPriceElement = document.getElementById('total-price-' + cartItemId);
        if (totalPriceElement) {
            const newTotal = newPrice * quantity;
            totalPriceElement.textContent = `GH₵ ${newTotal.toFixed(2)}`;
        }
    }

    // Recalculate cart totals
    updateCartTotals();

    // Show notification
    Swal.fire({
        icon: 'success',
        title: 'Condition Updated!',
        text: `Changed to ${newCondition.charAt(0).toUpperCase() + newCondition.slice(1)}`,
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// Calculate cart totals from all items
function updateCartTotals() {
    let total = 0;
    const cartItems = document.querySelectorAll('.cart-item');

    cartItems.forEach(item => {
        const totalPriceElement = item.querySelector('[id^="total-price-"]');
        if (totalPriceElement) {
            let priceText = totalPriceElement.textContent.replace('GH₵', '').replace(/,/g, '').trim();
            const itemTotal = parseFloat(priceText);
            if (!isNaN(itemTotal)) {
                total += itemTotal;
            }
        }
    });

    // Update cart total displays
    const cartTotal = document.getElementById('cartTotal');
    const cartSubtotal = document.getElementById('cartSubtotal');

    if (cartTotal) {
        cartTotal.textContent = `GH₵ ${total.toFixed(2)}`;
    }
    if (cartSubtotal) {
        cartSubtotal.textContent = `GH₵ ${total.toFixed(2)}`;
    }
}