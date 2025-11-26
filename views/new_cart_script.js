// Clean cart functionality with SweetAlert
class CartManager {
    constructor() {
        this.baseUrl = '../actions/';
        this.init();
    }

    init() {
        // Initialize any event listeners if needed
        console.log('Cart Manager initialized');
    }

    // Update quantity function
    updateQuantity(productId, newQuantity) {
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

        // Send request
        fetch(this.baseUrl + 'update_cart_quantity.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess(data.message);
                this.updateCartDisplay(data);
                this.updateItemQuantity(productId, newQuantity, data.item_total);
            } else {
                this.showError(data.message);
                this.resetQuantityInput(productId);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showError('Failed to update quantity. Please try again.');
            this.resetQuantityInput(productId);
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
    incrementQuantity(productId) {
        const input = document.querySelector(`input[data-product-id="${productId}"]`);
        if (input) {
            const currentValue = parseInt(input.value) || 1;
            const newValue = Math.min(currentValue + 1, 99);
            input.value = newValue;
            this.updateQuantity(productId, newValue);
        }
    }

    // Decrement quantity
    decrementQuantity(productId) {
        const input = document.querySelector(`input[data-product-id="${productId}"]`);
        if (input) {
            const currentValue = parseInt(input.value) || 1;
            const newValue = Math.max(currentValue - 1, 1);
            input.value = newValue;
            this.updateQuantity(productId, newValue);
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

    updateItemQuantity(productId, quantity, itemTotal) {
        // Update the quantity input
        const input = document.querySelector(`input[data-product-id="${productId}"]`);
        if (input) {
            input.value = quantity;
        }

        // Update item total price
        const totalPriceElement = document.querySelector(`[id*="total-price"][id*="${productId}"]`);
        if (totalPriceElement) {
            totalPriceElement.textContent = `GH₵ ${itemTotal}`;
        }
    }

    resetQuantityInput(productId) {
        const input = document.querySelector(`input[data-product-id="${productId}"]`);
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
function updateQuantity(productId, quantity) {
    cartManager.updateQuantity(productId, quantity);
}

function incrementQuantity(productId) {
    cartManager.incrementQuantity(productId);
}

function decrementQuantity(productId) {
    cartManager.decrementQuantity(productId);
}

function removeItem(productId, cartItemId) {
    cartManager.removeItem(productId, cartItemId);
}

function emptyCart() {
    cartManager.emptyCart();
}