/**
 * AI-Powered Abandoned Cart Notification System
 * Tracks cart abandonment and uses OpenAI to generate personalized messages
 */

// Configuration
const ABANDONED_CART_CONFIG = {
    // Timing delays (in milliseconds)
    FIRST_REMINDER_DELAY: 30 * 60 * 1000,      // 30 minutes
    SECOND_REMINDER_DELAY: 24 * 60 * 60 * 1000, // 24 hours
    THIRD_REMINDER_DELAY: 48 * 60 * 60 * 1000,  // 48 hours
    
    // Storage keys
    CART_STORAGE_KEY: 'gg_cart_snapshot',
    ABANDONMENT_KEY: 'gg_cart_abandoned',
    NOTIFICATION_SHOWN_KEY: 'gg_notification_shown',
    
    // OpenAI API endpoint (you'll need to create this)
    OPENAI_API_ENDPOINT: 'actions/generate_abandoned_cart_message.php'
};

/**
 * Initialize abandoned cart tracking
 */
function initAbandonedCartTracking() {
    // Track cart state when items are added
    trackCartState();
    
    // Detect abandonment when user leaves
    detectAbandonment();
    
    // Check for abandoned cart on page load
    checkAbandonedCart();
}

/**
 * Track current cart state
 */
function trackCartState() {
    // Fetch current cart data
    fetch('actions/get_cart_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items && data.items.length > 0) {
                const cartSnapshot = {
                    items: data.items.map(item => ({
                        name: item.name,
                        quantity: item.quantity,
                        price: item.price
                    })),
                    total: data.total,
                    count: data.count,
                    timestamp: Date.now()
                };
                
                // Save to localStorage
                localStorage.setItem(ABANDONED_CART_CONFIG.CART_STORAGE_KEY, JSON.stringify(cartSnapshot));
                
                // Clear abandonment flag if cart is active
                localStorage.removeItem(ABANDONED_CART_CONFIG.ABANDONMENT_KEY);
                localStorage.removeItem(ABANDONED_CART_CONFIG.NOTIFICATION_SHOWN_KEY);
            } else {
                // Cart is empty, clear tracking
                localStorage.removeItem(ABANDONED_CART_CONFIG.CART_STORAGE_KEY);
                localStorage.removeItem(ABANDONED_CART_CONFIG.ABANDONMENT_KEY);
            }
        })
        .catch(error => {
            console.error('Error tracking cart state:', error);
        });
}

/**
 * Detect when user abandons cart
 */
function detectAbandonment() {
    window.addEventListener('beforeunload', function() {
        const cartSnapshot = localStorage.getItem(ABANDONED_CART_CONFIG.CART_STORAGE_KEY);
        
        if (cartSnapshot) {
            const cart = JSON.parse(cartSnapshot);
            
            // Mark as abandoned with timestamp
            const abandonmentData = {
                timestamp: Date.now(),
                cart: cart
            };
            
            localStorage.setItem(ABANDONED_CART_CONFIG.ABANDONMENT_KEY, JSON.stringify(abandonmentData));
        }
    });
    
    // Also track visibility changes (tab switching)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // User switched tabs or minimized window
            const cartSnapshot = localStorage.getItem(ABANDONED_CART_CONFIG.CART_STORAGE_KEY);
            if (cartSnapshot) {
                const cart = JSON.parse(cartSnapshot);
                const abandonmentData = {
                    timestamp: Date.now(),
                    cart: cart
                };
                localStorage.setItem(ABANDONED_CART_CONFIG.ABANDONMENT_KEY, JSON.stringify(abandonmentData));
            }
        }
    });
}

/**
 * Check for abandoned cart on page load
 */
function checkAbandonedCart() {
    const abandonmentData = localStorage.getItem(ABANDONED_CART_CONFIG.ABANDONMENT_KEY);
    const notificationShown = localStorage.getItem(ABANDONED_CART_CONFIG.NOTIFICATION_SHOWN_KEY);
    
    if (!abandonmentData) return;
    
    const abandonment = JSON.parse(abandonmentData);
    const timeSinceAbandonment = Date.now() - abandonment.timestamp;
    
    // Check if enough time has passed for first reminder
    if (timeSinceAbandonment >= ABANDONED_CART_CONFIG.FIRST_REMINDER_DELAY) {
        // Check if we've already shown notification for this abandonment
        const lastShown = notificationShown ? JSON.parse(notificationShown) : null;
        
        if (!lastShown || lastShown.timestamp !== abandonment.timestamp) {
            // Show notification with AI-generated message
            showAbandonedCartNotification(abandonment.cart);
            
            // Mark as shown
            localStorage.setItem(ABANDONED_CART_CONFIG.NOTIFICATION_SHOWN_KEY, JSON.stringify({
                timestamp: abandonment.timestamp,
                shownAt: Date.now()
            }));
        }
    }
}

/**
 * Generate AI message using OpenAI API
 */
async function generateAICartMessage(cartItems) {
    try {
        const response = await fetch(ABANDONED_CART_CONFIG.OPENAI_API_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                items: cartItems
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.message) {
            return data.message;
        } else {
            // Fallback message if AI fails
            return generateFallbackMessage(cartItems);
        }
    } catch (error) {
        console.error('Error generating AI message:', error);
        return generateFallbackMessage(cartItems);
    }
}

/**
 * Generate fallback message if AI is unavailable
 */
function generateFallbackMessage(cartItems) {
    const itemNames = cartItems.map(item => item.name).join(', ');
    const itemCount = cartItems.length;
    
    if (itemCount === 1) {
        return `Don't forget! Your ${itemNames} is still waiting in your cart. Complete your purchase now!`;
    } else {
        return `You have ${itemCount} items in your cart: ${itemNames}. Don't miss out - complete your purchase!`;
    }
}

/**
 * Show abandoned cart notification
 */
async function showAbandonedCartNotification(cart) {
    // Remove any existing notification
    const existingNotification = document.getElementById('abandonedCartNotification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Generate AI message
    const message = await generateAICartMessage(cart.items);
    
    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'abandonedCartNotification';
    notification.className = 'abandoned-cart-notification';
    notification.innerHTML = `
        <div class="abandoned-cart-content">
            <div class="abandoned-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="abandoned-cart-text">
                <h4>Your Cart is Waiting!</h4>
                <p>${message}</p>
            </div>
            <div class="abandoned-cart-actions">
                <a href="views/cart.php" class="abandoned-cart-btn view-cart-btn">
                    <i class="fas fa-shopping-bag"></i> View Cart
                </a>
                <button class="abandoned-cart-btn dismiss-btn" onclick="dismissAbandonedCartNotification()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
}

/**
 * Dismiss notification
 */
function dismissAbandonedCartNotification() {
    const notification = document.getElementById('abandonedCartNotification');
    if (notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

/**
 * Track cart updates (call this when cart changes)
 */
function updateCartTracking() {
    trackCartState();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAbandonedCartTracking);
} else {
    initAbandonedCartTracking();
}

// Re-track cart every 30 seconds to keep it updated
setInterval(trackCartState, 30000);

// Export functions for global use
window.dismissAbandonedCartNotification = dismissAbandonedCartNotification;
window.updateCartTracking = updateCartTracking;

