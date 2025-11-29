/**
 * Purchase Notification Pop-ups
 * Displays recent purchase notifications in the bottom-right corner
 */

(function() {
    'use strict';

    let purchaseQueue = [];
    let currentNotification = null;
    let notificationInterval = null;
    let isPaused = false;

    /**
     * Fetch recent purchases from server
     */
    async function fetchRecentPurchases() {
        try {
            // Determine base path dynamically based on current page location
            const currentPath = window.location.pathname;
            let basePath = '';
            
            // If we're in views directory, go up one level
            if (currentPath.includes('/views/') || currentPath.match(/\/views\//)) {
                basePath = '../';
            }
            
            const response = await fetch(basePath + 'actions/get_recent_purchases.php');
            
            // Check if response is ok
            if (!response.ok) {
                console.warn('Failed to fetch recent purchases: HTTP ' + response.status);
                return false;
            }
            
            const data = await response.json();
            
            if (data.success && data.purchases && data.purchases.length > 0) {
                purchaseQueue = data.purchases;
                return true;
            }
            return false;
        } catch (error) {
            // Silently fail - don't break the page
            console.warn('Error fetching recent purchases:', error);
            return false;
        }
    }

    /**
     * Create and display a purchase notification
     */
    function showPurchaseNotification(purchase) {
        // Remove any existing notification
        const existing = document.querySelector('.purchase-notification');
        if (existing) {
            existing.remove();
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'purchase-notification';
        notification.setAttribute('data-product-id', purchase.product_id);

        // Format product title (extract main parts)
        const title = purchase.product_title || 'Product';
        
        // Determine base path for images
        const currentPath = window.location.pathname;
        const imageBasePath = currentPath.includes('/views/') ? '../' : '';
        const placeholderImage = imageBasePath + 'images/placeholder.jpg';
        
        // Create notification HTML
        notification.innerHTML = `
            <button class="purchase-notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
            <div class="purchase-notification-content">
                <div class="purchase-notification-image">
                    <img src="${purchase.product_image || placeholderImage}" 
                         alt="${title}" 
                         onerror="this.src='${placeholderImage}'">
                </div>
                <div class="purchase-notification-details">
                    <div class="purchase-notification-label">Someone Purchased</div>
                    <div class="purchase-notification-title">${escapeHtml(title)}</div>
                    ${purchase.product_color ? `<div class="purchase-notification-subtitle">- ${escapeHtml(purchase.product_color)}</div>` : ''}
                    ${purchase.brand_name ? `<div class="purchase-notification-location">(${escapeHtml(purchase.brand_name)})</div>` : ''}
                    <div class="purchase-notification-time">${purchase.time_ago}</div>
                </div>
            </div>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);

        currentNotification = notification;
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show next purchase notification from queue
     */
    function showNextNotification() {
        if (isPaused || purchaseQueue.length === 0) {
            return;
        }

        // Get random purchase from queue
        const randomIndex = Math.floor(Math.random() * purchaseQueue.length);
        const purchase = purchaseQueue[randomIndex];
        
        // Remove from queue to avoid showing same one too often
        purchaseQueue.splice(randomIndex, 1);

        showPurchaseNotification(purchase);
    }

    /**
     * Start the notification system
     */
    async function startPurchaseNotifications() {
        // Initial fetch
        await fetchRecentPurchases();

        // Show first notification after 3 seconds
        setTimeout(() => {
            if (purchaseQueue.length > 0) {
                showNextNotification();
            }
        }, 3000);

        // Set interval to show notifications every 5 minutes
        notificationInterval = setInterval(() => {
            if (purchaseQueue.length === 0) {
                // Refetch if queue is empty
                fetchRecentPurchases().then(() => {
                    if (purchaseQueue.length > 0) {
                        showNextNotification();
                    }
                });
            } else {
                showNextNotification();
            }
        }, 300000); // Show every 5 minutes (300000 milliseconds)

        // Refetch purchases every 10 minutes to keep queue fresh
        setInterval(async () => {
            await fetchRecentPurchases();
        }, 600000);
    }

    /**
     * Pause notifications (when user is on checkout/cart pages)
     */
    function pauseNotifications() {
        isPaused = true;
    }

    /**
     * Resume notifications
     */
    function resumeNotifications() {
        isPaused = false;
    }

    // Check if we should show notifications on this page
    function shouldShowNotifications() {
        const currentPage = window.location.pathname.toLowerCase();
        const excludedPages = [
            'admin',
            'checkout',
            'cart',
            'login',
            'register',
            'payment'
        ];
        
        // Don't show on excluded pages
        for (let page of excludedPages) {
            if (currentPage.includes(page)) {
                return false;
            }
        }
        
        return true;
    }

    // Initialize when DOM is ready - wrap in try-catch to prevent breaking other scripts
    try {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                try {
                    if (shouldShowNotifications()) {
                        startPurchaseNotifications();
                    }
                } catch (error) {
                    console.warn('Error initializing purchase notifications:', error);
                }
            });
        } else {
            try {
                if (shouldShowNotifications()) {
                    startPurchaseNotifications();
                }
            } catch (error) {
                console.warn('Error initializing purchase notifications:', error);
            }
        }
    } catch (error) {
        console.warn('Error setting up purchase notifications:', error);
    }

    // Export functions for external use
    window.purchaseNotifications = {
        pause: pauseNotifications,
        resume: resumeNotifications,
        refresh: fetchRecentPurchases
    };

})();

