<?php
// Cart Sidebar Component
?>

<!-- Cart Sidebar Overlay -->
<div id="cartSidebarOverlay" class="cart-sidebar-overlay" style="display: none;">
    <div id="cartSidebar" class="cart-sidebar">
        <!-- Cart Header -->
        <div class="cart-header">
            <h3>Shopping Cart</h3>
            <button id="closeCartSidebar" class="close-cart-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Cart Content -->
        <div class="cart-content">
            <!-- Subtotal Section -->
            <div class="cart-subtotal">
                <div class="subtotal-line">
                    <span>Subtotal:</span>
                    <span id="cartSubtotal" class="subtotal-amount">GH₵0.00</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="cart-actions">
                <button id="viewCartBtn" class="cart-btn view-cart-btn">
                    <span>View bag (<span id="cartItemCount">0</span>)</span>
                </button>
                <button id="proceedCheckoutBtn" class="cart-btn checkout-btn">
                    Proceed to Checkout
                </button>
            </div>

            <!-- Free Shipping Message -->
            <div class="free-shipping-message">
                <i class="fas fa-shipping-fast"></i>
                <span>You earned Free Standard Shipping!</span>
            </div>

            <!-- Success Message -->
            <div id="cartSuccessMessage" class="cart-success-message">
                <span>Sync was successful!</span>
            </div>

            <!-- Cart Items List -->
            <div id="cartItemsList" class="cart-items-list">
                <!-- Cart items will be dynamically loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Cart Sidebar Styles */
.cart-sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.cart-sidebar-overlay.show {
    opacity: 1;
    visibility: visible;
}

.cart-sidebar {
    position: fixed;
    top: 80px; /* Position below nav bar */
    right: 0;
    width: 400px;
    max-width: 90vw;
    height: calc(100% - 80px); /* Account for nav bar height */
    background: white;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    transform: translateX(100%);
    transition: transform 0.3s ease;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    border-top-left-radius: 15px;
    border-bottom-left-radius: 15px;
}

.cart-sidebar-overlay.show .cart-sidebar {
    transform: translateX(0);
}

/* Cart Header */
.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e5e5;
    background: white;
    position: sticky;
    top: 0;
    z-index: 1001;
}

.cart-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
}

.close-cart-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.close-cart-btn:hover {
    background: #f5f5f5;
    color: #333;
}

/* Cart Content */
.cart-content {
    flex: 1;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Subtotal */
.cart-subtotal {
    padding: 15px 0;
}

.subtotal-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.subtotal-amount {
    font-size: 1.25rem;
    color: #000;
}

/* Action Buttons */
.cart-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.cart-btn {
    padding: 15px 20px;
    border: none;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-cart-btn {
    background: transparent;
    border: 2px solid #333;
    color: #333;
}

.view-cart-btn:hover {
    background: #333;
    color: white;
}

.checkout-btn {
    background: #000;
    color: white;
}

.checkout-btn:hover {
    background: #333;
    transform: translateY(-1px);
}

/* Free Shipping Message */
.free-shipping-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #e8f5e8;
    border-radius: 10px;
    color: #2d5a2d;
    font-size: 0.9rem;
    font-weight: 500;
}

.free-shipping-message i {
    color: #4caf50;
    font-size: 1rem;
}

/* Success Message */
.cart-success-message {
    display: none;
    padding: 12px 15px;
    background: #e8f5e8;
    border-radius: 10px;
    color: #2d5a2d;
    font-size: 0.9rem;
    font-weight: 500;
    text-align: center;
}

.cart-success-message.show {
    display: block;
}

/* Cart Items */
.cart-items-list {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.cart-item {
    display: flex;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #e5e5e5;
}

.cart-item-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.cart-item-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
    line-height: 1.3;
}

.cart-item-condition {
    font-size: 0.8rem;
    color: #666;
}

.cart-item-price {
    font-weight: 600;
    color: #000;
    margin-top: auto;
}

.cart-item-original-price {
    text-decoration: line-through;
    color: #999;
    font-size: 0.85rem;
    margin-right: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .cart-sidebar {
        width: 100%;
        max-width: none;
    }

    .cart-header {
        padding: 15px;
    }

    .cart-content {
        padding: 15px;
    }
}

/* Loading State */
.cart-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    font-size: 0.9rem;
    color: #666;
}

.cart-loading i {
    margin-right: 10px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Empty Cart State */
.cart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #666;
    font-size: 0.9rem;
}

.cart-empty i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 15px;
}
</style>

<script>
// Cart Sidebar JavaScript
class CartSidebar {
    constructor() {
        this.overlay = document.getElementById('cartSidebarOverlay');
        this.sidebar = document.getElementById('cartSidebar');
        this.closeBtn = document.getElementById('closeCartSidebar');
        this.viewCartBtn = document.getElementById('viewCartBtn');
        this.checkoutBtn = document.getElementById('proceedCheckoutBtn');
        this.itemsList = document.getElementById('cartItemsList');
        this.subtotalElement = document.getElementById('cartSubtotal');
        this.itemCountElement = document.getElementById('cartItemCount');
        this.successMessage = document.getElementById('cartSuccessMessage');

        this.init();
    }

    init() {
        // Close sidebar events
        this.closeBtn.addEventListener('click', () => this.hide());
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) this.hide();
        });

        // Button events
        this.viewCartBtn.addEventListener('click', () => this.goToCart());
        this.checkoutBtn.addEventListener('click', () => this.goToCheckout());

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.overlay.classList.contains('show')) {
                this.hide();
            }
        });
    }

    show() {
        document.body.style.overflow = 'hidden';
        this.overlay.classList.add('show');
        this.loadCartData();
        this.showSuccessMessage();
    }

    hide() {
        document.body.style.overflow = '';
        this.overlay.classList.remove('show');
    }

    showSuccessMessage() {
        this.successMessage.classList.add('show');
        setTimeout(() => {
            this.successMessage.classList.remove('show');
        }, 3000);
    }

    async loadCartData() {
        try {
            // Show loading state
            this.itemsList.innerHTML = '<div class="cart-loading"><i class="fas fa-spinner"></i> Loading cart...</div>';

            // Fetch cart data
            const response = await fetch('../actions/get_cart_data.php');
            const data = await response.json();

            if (data.success) {
                this.updateCartDisplay(data);
            } else {
                throw new Error(data.message || 'Failed to load cart');
            }
        } catch (error) {
            console.error('Failed to load cart:', error);
            this.itemsList.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Failed to load cart items</p></div>';
        }
    }

    updateCartDisplay(cartData) {
        // Update subtotal and count
        this.subtotalElement.textContent = `GH₵${cartData.total || '0.00'}`;
        this.itemCountElement.textContent = cartData.count || '0';

        // Update items list
        if (cartData.items && cartData.items.length > 0) {
            this.itemsList.innerHTML = cartData.items.map(item => this.createCartItemHTML(item)).join('');
        } else {
            this.itemsList.innerHTML = `
                <div class="cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                </div>`;
        }
    }

    createCartItemHTML(item) {
        const originalPrice = item.original_price ? `<span class="cart-item-original-price">GH₵${item.original_price}</span>` : '';

        return `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-condition">Condition: ${item.condition || 'New'}</div>
                    <div class="cart-item-price">
                        ${originalPrice}
                        GH₵${item.price}
                    </div>
                </div>
            </div>`;
    }

    goToCart() {
        window.location.href = '../views/cart.php';
    }

    goToCheckout() {
        window.location.href = '../views/checkout.php';
    }
}

// Initialize cart sidebar when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.cartSidebar = new CartSidebar();
});

// Global function to show cart sidebar (called by Add to Cart buttons)
window.showCartSidebar = function() {
    if (window.cartSidebar) {
        window.cartSidebar.show();
    }
};
</script>