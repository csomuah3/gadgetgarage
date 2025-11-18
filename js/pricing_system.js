/**
 * Dynamic Pricing System for GadgetGarage
 * Calculates prices based on product condition and category
 */

// Pricing configuration by category
const CATEGORY_PRICING_CONFIG = {
    // Smartphones category
    'smartphones': {
        'excellent': 0,    // Base price (no deduction)
        'good': -2000,     // -2000 GH₵
        'fair': -3500      // -3500 GH₵
    },
    'mobile_devices': {
        'excellent': 0,    // Base price (no deduction)
        'good': -2000,     // -2000 GH₵
        'fair': -3500      // -3500 GH₵
    },
    // iPads/Tablets category
    'tablets': {
        'excellent': 0,    // Base price (no deduction)
        'good': -1800,     // -1800 GH₵
        'fair': -2500      // -2500 GH₵
    },
    'ipads': {
        'excellent': 0,    // Base price (no deduction)
        'good': -1800,     // -1800 GH₵
        'fair': -2500      // -2500 GH₵
    },
    // Laptops category
    'laptops': {
        'excellent': 0,    // Base price (no deduction)
        'good': -3000,     // -3000 GH₵
        'fair': -3400      // -3400 GH₵
    },
    'computing': {
        'excellent': 0,    // Base price (no deduction)
        'good': -3000,     // -3000 GH₵
        'fair': -3400      // -3400 GH₵
    },
    // Desktops category
    'desktops': {
        'excellent': 0,    // Base price (no deduction)
        'good': -2000,     // -2000 GH₵
        'fair': -2300      // -2300 GH₵
    },
    // Cameras category
    'cameras': {
        'excellent': 0,    // Base price (no deduction)
        'good': -1000,     // -1000 GH₵
        'fair': -2000      // -2000 GH₵
    },
    'photography_video': {
        'excellent': 0,    // Base price (no deduction)
        'good': -1000,     // -1000 GH₵
        'fair': -2000      // -2000 GH₵
    },
    // Video Equipment category
    'video_equipment': {
        'excellent': 0,    // Base price (no deduction)
        'good': -1500,     // -1500 GH₵
        'fair': -3000      // -3000 GH₵
    }
};

// Default pricing for unknown categories
const DEFAULT_PRICING = {
    'excellent': 0,
    'good': -1000,
    'fair': -2000
};

/**
 * Calculate price based on category and condition
 * @param {number} excellentPrice - The excellent condition price (base price)
 * @param {string} categoryName - The product category name
 * @param {string} condition - The product condition (excellent, good, fair)
 * @returns {number} - Calculated price
 */
function calculatePrice(excellentPrice, categoryName, condition = 'excellent') {
    // Normalize category name (lowercase, remove spaces/special chars)
    const normalizedCategory = categoryName.toLowerCase()
        .replace(/[^a-z0-9]/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_|_$/g, '');

    // Get pricing config for this category or use default
    const categoryConfig = CATEGORY_PRICING_CONFIG[normalizedCategory] || DEFAULT_PRICING;

    // Get the price adjustment for the condition
    const priceAdjustment = categoryConfig[condition.toLowerCase()] || 0;

    // Calculate final price (ensure it doesn't go below 0)
    const finalPrice = Math.max(0, excellentPrice + priceAdjustment);

    return finalPrice;
}

/**
 * Get all available conditions and prices for a product
 * @param {number} excellentPrice - The excellent condition price (base price)
 * @param {string} categoryName - The product category name
 * @returns {Object} - Object with all condition prices
 */
function getAllConditionPrices(excellentPrice, categoryName) {
    return {
        excellent: calculatePrice(excellentPrice, categoryName, 'excellent'),
        good: calculatePrice(excellentPrice, categoryName, 'good'),
        fair: calculatePrice(excellentPrice, categoryName, 'fair')
    };
}

/**
 * Format price with currency symbol
 * @param {number} price - Price to format
 * @param {string} currency - Currency symbol (default: GH₵)
 * @returns {string} - Formatted price string
 */
function formatPrice(price, currency = 'GH₵') {
    return `${currency}${parseFloat(price).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

/**
 * Create condition selector HTML
 * @param {number} excellentPrice - The excellent condition price (base price)
 * @param {string} categoryName - The product category name
 * @param {string} selectedCondition - Currently selected condition
 * @returns {string} - HTML for condition selector
 */
function createConditionSelector(excellentPrice, categoryName, selectedCondition = 'excellent') {
    const prices = getAllConditionPrices(excellentPrice, categoryName);

    return `
        <div class="condition-selector">
            <label class="condition-label">Select Condition:</label>
            <div class="condition-options">
                <div class="condition-option ${selectedCondition === 'excellent' ? 'selected' : ''}"
                     data-condition="excellent" data-price="${prices.excellent}">
                    <div class="condition-name">Excellent</div>
                    <div class="condition-price">${formatPrice(prices.excellent)}</div>
                    <div class="condition-desc">Like new condition</div>
                </div>
                <div class="condition-option ${selectedCondition === 'good' ? 'selected' : ''}"
                     data-condition="good" data-price="${prices.good}">
                    <div class="condition-name">Good</div>
                    <div class="condition-price">${formatPrice(prices.good)}</div>
                    <div class="condition-desc">Minor wear, fully functional</div>
                </div>
                <div class="condition-option ${selectedCondition === 'fair' ? 'selected' : ''}"
                     data-condition="fair" data-price="${prices.fair}">
                    <div class="condition-name">Fair</div>
                    <div class="condition-price">${formatPrice(prices.fair)}</div>
                    <div class="condition-desc">Visible wear, works perfectly</div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Initialize condition selector functionality
 * @param {string} containerId - ID of container element
 * @param {function} onConditionChange - Callback when condition changes
 */
function initializeConditionSelector(containerId, onConditionChange) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const conditionOptions = container.querySelectorAll('.condition-option');

    conditionOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            conditionOptions.forEach(opt => opt.classList.remove('selected'));

            // Add selected class to clicked option
            this.classList.add('selected');

            // Get selected condition and price
            const condition = this.getAttribute('data-condition');
            const price = parseFloat(this.getAttribute('data-price'));

            // Call callback if provided
            if (onConditionChange && typeof onConditionChange === 'function') {
                onConditionChange(condition, price);
            }
        });
    });
}

/**
 * Update product display price based on condition
 * @param {string} priceElementId - ID of price display element
 * @param {number} newPrice - New price to display
 */
function updatePriceDisplay(priceElementId, newPrice) {
    const priceElement = document.getElementById(priceElementId);
    if (priceElement) {
        priceElement.textContent = formatPrice(newPrice);
    }
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        calculatePrice,
        getAllConditionPrices,
        formatPrice,
        createConditionSelector,
        initializeConditionSelector,
        updatePriceDisplay,
        CATEGORY_PRICING_CONFIG
    };
}

// Make functions globally available
window.GadgetGaragePricing = {
    calculatePrice,
    getAllConditionPrices,
    formatPrice,
    createConditionSelector,
    initializeConditionSelector,
    updatePriceDisplay,
    CATEGORY_PRICING_CONFIG
};