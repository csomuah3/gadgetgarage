/**
 * Product Filters - Redirect Mode
 * Centralized JavaScript for redirect-based filtering (no AJAX)
 * 
 * Usage:
 *   <script src="../js/product-filters-redirect.js"></script>
 *   <script>
 *       productFiltersRedirect.init('all_product.php'); // Pass your page name
 *   </script>
 */

(function() {
    'use strict';

    let currentPage = 'all_product.php';
    let elements = {};

    /**
     * Initialize the filter system
     * @param {string} pageName - Name of the current page (e.g., 'all_product.php', 'photography_video.php')
     */
    function init(pageName) {
        currentPage = pageName || 'all_product.php';
        console.log('🎯 Product Filters (Redirect Mode) Initialized for:', currentPage);

        cacheElements();
        setupEventListeners();
        initPriceSlider();
        loadFilterStateFromURL();
    }

    /**
     * Cache DOM elements
     */
    function cacheElements() {
        elements = {
            searchInput: document.getElementById('searchInput') || document.getElementById('filterSearchInput'),
            minPriceSlider: document.getElementById('minPriceSlider'),
            maxPriceSlider: document.getElementById('maxPriceSlider'),
            priceMinDisplay: document.getElementById('priceMinDisplay'),
            priceMaxDisplay: document.getElementById('priceMaxDisplay'),
            priceRange: document.getElementById('priceRange'),
            categoryBtns: document.querySelectorAll('#categoryTags .tag-btn'),
            brandBtns: document.querySelectorAll('#brandTags .tag-btn'),
            ratingInputs: document.querySelectorAll('input[name="rating_filter"]'),
            applyBtn: document.getElementById('applyFilters') || document.getElementById('applyFiltersBtn'),
            clearBtn: document.getElementById('clearFilters') || document.getElementById('clearFiltersBtn')
        };
    }

    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Apply filters button
        if (elements.applyBtn) {
            elements.applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                applyAllFilters();
            });
        }

        // Clear filters button
        if (elements.clearBtn) {
            elements.clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearAllFilters();
            });
        }

        // Category buttons
        elements.categoryBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                selectTag(elements.categoryBtns, this);
            });
        });

        // Brand buttons
        elements.brandBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                selectTag(elements.brandBtns, this);
            });
        });

        // Rating inputs
        elements.ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Optionally show apply button when rating changes
            });
        });
    }

    /**
     * Select tag (category or brand)
     */
    function selectTag(buttons, selectedBtn) {
        buttons.forEach(btn => btn.classList.remove('active'));
        selectedBtn.classList.add('active');
    }

    /**
     * Initialize price slider
     */
    function initPriceSlider() {
        if (!elements.minPriceSlider || !elements.maxPriceSlider) return;

        function updatePriceDisplay() {
            const minVal = parseInt(elements.minPriceSlider.value);
            const maxVal = parseInt(elements.maxPriceSlider.value);

            // Ensure min is not greater than max
            if (minVal > maxVal - 100) {
                elements.minPriceSlider.value = maxVal - 100;
            }
            if (maxVal < minVal + 100) {
                elements.maxPriceSlider.value = minVal + 100;
            }

            const finalMin = parseInt(elements.minPriceSlider.value);
            const finalMax = parseInt(elements.maxPriceSlider.value);

            // Update displays
            if (elements.priceMinDisplay) {
                elements.priceMinDisplay.textContent = `GH₵ ${finalMin.toLocaleString()}`;
            }
            if (elements.priceMaxDisplay) {
                elements.priceMaxDisplay.textContent = `GH₵ ${finalMax.toLocaleString()}`;
            }

            // Update range visual
            if (elements.priceRange) {
                const minPercent = (finalMin / parseInt(elements.minPriceSlider.max)) * 100;
                const maxPercent = (finalMax / parseInt(elements.maxPriceSlider.max)) * 100;
                elements.priceRange.style.left = `${minPercent}%`;
                elements.priceRange.style.right = `${100 - maxPercent}%`;
            }
        }

        elements.minPriceSlider.addEventListener('input', updatePriceDisplay);
        elements.maxPriceSlider.addEventListener('input', updatePriceDisplay);
        updatePriceDisplay();
    }

    /**
     * Load filter state from URL parameters
     */
    function loadFilterStateFromURL() {
        const urlParams = new URLSearchParams(window.location.search);

        // Load search
        if (elements.searchInput && urlParams.get('search')) {
            elements.searchInput.value = urlParams.get('search');
        }

        // Load price sliders
        if (urlParams.get('min_price')) {
            const minPrice = parseInt(urlParams.get('min_price'));
            if (elements.minPriceSlider) {
                elements.minPriceSlider.value = minPrice;
            }
        }
        if (urlParams.get('max_price')) {
            const maxPrice = parseInt(urlParams.get('max_price'));
            if (elements.maxPriceSlider) {
                elements.maxPriceSlider.value = maxPrice;
            }
        }

        // Load category
        const categoryId = urlParams.get('category');
        if (categoryId) {
            elements.categoryBtns.forEach(btn => {
                if (btn.getAttribute('data-category') == categoryId) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // Load brand
        const brandId = urlParams.get('brand');
        if (brandId) {
            elements.brandBtns.forEach(btn => {
                if (btn.getAttribute('data-brand') == brandId) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // Load rating
        const rating = urlParams.get('rating');
        if (rating) {
            elements.ratingInputs.forEach(input => {
                input.checked = (input.value == rating);
            });
        }

        // Update price display after loading values
        if (elements.minPriceSlider && elements.maxPriceSlider) {
            initPriceSlider();
        }
    }

    /**
     * Apply all filters (AJAX - stays on same page)
     */
    function applyAllFilters() {
        console.log('🚀 Applying filters via AJAX...');

        const categoryBtn = document.querySelector('#categoryTags .tag-btn.active');
        const brandBtn = document.querySelector('#brandTags .tag-btn.active');
        const searchInput = elements.searchInput;
        const minPrice = elements.minPriceSlider ? parseInt(elements.minPriceSlider.value) : 0;
        const maxPrice = elements.maxPriceSlider ? parseInt(elements.maxPriceSlider.value) : 50000;
        const selectedRating = document.querySelector('input[name="rating_filter"]:checked');

        // Build params for AJAX request
        const params = new URLSearchParams();
        params.append('action', 'combined_filter');
        
        if (categoryBtn && categoryBtn.dataset.category) {
            params.append('cat_ids[]', categoryBtn.dataset.category);
        }
        if (brandBtn && brandBtn.dataset.brand) {
            params.append('brand_ids[]', brandBtn.dataset.brand);
        }
        if (searchInput && searchInput.value) {
            params.append('query', searchInput.value);
        }
        if (minPrice > 0) {
            params.append('min_price', minPrice);
        }
        if (maxPrice < 50000) {
            params.append('max_price', maxPrice);
        }
        if (selectedRating && selectedRating.value) {
            params.append('rating', selectedRating.value);
        }

        const fetchUrl = '../actions/product_actions.php?' + params.toString();
        
        console.log('📡 Fetch URL:', fetchUrl);
        console.log('📊 Filter params:', params.toString());

        // Show loading state
        if (elements.applyBtn) {
            elements.applyBtn.disabled = true;
            elements.applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
        }

        // Make AJAX request
        fetch(fetchUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Filter response:', data);
                console.log('📦 Products found:', Array.isArray(data) ? data.length : 0);
                
                // Update product grid on same page
                updateProductGrid(data);
            })
            .catch(error => {
                console.error('❌ Filter error:', error);
                alert('Error applying filters. Please try again.\n\nError: ' + error.message);
            })
            .finally(() => {
                // Reset button
                if (elements.applyBtn) {
                    elements.applyBtn.disabled = false;
                    elements.applyBtn.innerHTML = '<i class="fas fa-filter"></i> Apply Filters';
                }
            });
    }

    /**
     * Update product grid with filtered products
     */
    function updateProductGrid(products) {
        const productGrid = document.getElementById('productGrid');
        const productCount = document.querySelector('.product-count');

        if (!productGrid) {
            console.error('❌ Product grid not found');
            return;
        }

        // Update count
        if (productCount) {
            productCount.innerHTML = `<i class="fas fa-box"></i> Showing ${products.length} products`;
        }

        // Update grid
        if (products.length === 0) {
            productGrid.innerHTML = `
                <div class="no-products" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                    <i class="fas fa-search" style="font-size: 4rem; color: #d1d5db; margin-bottom: 20px;"></i>
                    <h3 style="color: #6b7280; margin-bottom: 10px;">No Products Found</h3>
                    <p style="color: #9ca3af;">Try adjusting your filters or search terms.</p>
                </div>
            `;
        } else {
            // Use existing product card rendering if available, or create simple cards
            productGrid.innerHTML = products.map(product => createProductCard(product)).join('');
        }

        console.log('✅ Product grid updated with', products.length, 'products');
    }

    /**
     * Create product card HTML (matches existing structure)
     */
    function createProductCard(product) {
        const imageUrl = product.image_url || 'http://169.239.251.102:442/~chelsea.somuah/uploads/' + (product.product_image || '');
        const title = (product.product_title || 'Unknown Product').replace(/'/g, "&#39;").replace(/"/g, "&quot;");
        const price = parseFloat(product.product_price || 0).toFixed(2);
        const productId = product.product_id || 0;
        const discount = Math.floor(Math.random() * 15) + 10; // Random discount 10-25%
        const originalPrice = (parseFloat(price) * (1 + discount / 100)).toFixed(2);
        const rating = ((productId % 5) + 1).toFixed(1);

        return `
            <div class="modern-product-card" onclick="window.location.href='single_product.php?pid=${productId}'" style="cursor: pointer; background: white; border-radius: 16px; border: 1px solid #e5e7eb; overflow: visible; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative;">
                <!-- Discount Badge -->
                <div style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; z-index: 10;">
                    -${discount}%
                </div>
                
                <!-- Wishlist & Compare Buttons -->
                <div style="position: absolute; top: 12px; right: 12px; z-index: 10; display: flex; gap: 8px;">
                    <button onclick="event.stopPropagation(); addToCompare(${productId}, '${title}')" 
                        class="compare-btn" 
                        style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="fas fa-balance-scale" style="color: #2563eb; font-size: 14px;"></i>
                    </button>
                    <button onclick="event.stopPropagation(); toggleWishlist(${productId}, this)" 
                        class="wishlist-btn" 
                        style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="far fa-heart" style="color: #6b7280; font-size: 16px;"></i>
                    </button>
                </div>
                
                <!-- Product Image -->
                <div class="product-image-container" style="padding: 20px; text-align: center; height: 200px; display: flex; align-items: center; justify-content: center; background: #f9fafb; overflow: hidden;">
                    <img src="${imageUrl}" 
                         alt="${title}" 
                         style="max-width: 100%; max-height: 100%; object-fit: contain;"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4=';">
                </div>
                
                <!-- Product Info -->
                <div style="padding: 20px;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin-bottom: 10px; line-height: 1.4;">
                        ${title}
                    </h3>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <div style="display: flex; gap: 2px;">
                            ${Array.from({length: 5}, (_, i) => 
                                `<i class="fas fa-star" style="color: ${i < Math.floor(rating) ? '#fbbf24' : '#e5e7eb'}; font-size: 0.85rem;"></i>`
                            ).join('')}
                        </div>
                        <span style="font-size: 0.85rem; color: #6b7280;">${rating}</span>
                    </div>
                    <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 15px;">
                        <span style="font-size: 1.5rem; font-weight: 700; color: #2563eb;">GH₵ ${parseFloat(price).toLocaleString()}</span>
                        <span style="font-size: 1rem; color: #9ca3af; text-decoration: line-through;">GH₵ ${parseFloat(originalPrice).toLocaleString()}</span>
                    </div>
                    <button onclick="event.stopPropagation(); addToCart(${productId})" 
                        style="width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb, #1e40af); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                        Add to Cart
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Clear all filters (AJAX - stays on same page)
     */
    function clearAllFilters() {
        console.log('🧹 Clearing filters');

        // Reset search
        if (elements.searchInput) {
            elements.searchInput.value = '';
        }

        // Reset price sliders
        if (elements.minPriceSlider) elements.minPriceSlider.value = 0;
        if (elements.maxPriceSlider) elements.maxPriceSlider.value = 50000;
        if (elements.priceMinDisplay) elements.priceMinDisplay.textContent = 'GH₵ 0';
        if (elements.priceMaxDisplay) elements.priceMaxDisplay.textContent = 'GH₵ 50,000';
        if (elements.priceRange) {
            elements.priceRange.style.left = '0%';
            elements.priceRange.style.right = '0%';
        }

        // Reset categories
        elements.categoryBtns.forEach((btn, index) => {
            btn.classList.remove('active');
            if (index === 0) btn.classList.add('active'); // Activate "All"
        });

        // Reset brands
        elements.brandBtns.forEach((btn, index) => {
            btn.classList.remove('active');
            if (index === 0) btn.classList.add('active'); // Activate "All"
        });

        // Reset ratings
        elements.ratingInputs.forEach(input => {
            input.checked = false;
        });

        // Apply filters (will show all products)
        applyAllFilters();
    }

    /**
     * Public API
     */
    window.productFiltersRedirect = {
        init: init,
        applyAllFilters: applyAllFilters,
        clearAllFilters: clearAllFilters
    };

    // Auto-initialize if page name is provided in data attribute
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            const pageName = document.body.getAttribute('data-filter-page') || 
                           document.querySelector('[data-filter-page]')?.getAttribute('data-filter-page');
            if (pageName) {
                init(pageName);
            }
        });
    } else {
        const pageName = document.body.getAttribute('data-filter-page') || 
                        document.querySelector('[data-filter-page]')?.getAttribute('data-filter-page');
        if (pageName) {
            init(pageName);
        }
    }

})();

