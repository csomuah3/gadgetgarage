/**
 * Product Filters JavaScript
 * Modular, reusable filter system for all product pages
 * Version 2.0
 */

(function() {
    'use strict';

    console.log('🎯 Product Filters JS Loaded - Version 2.0');

    // Configuration
    let filterConfig = {
        showCategoryFilter: true,
        showBrandFilter: true,
        showPriceFilter: true,
        fixedCategoryId: null,
        allowedCategories: [],
        ajaxUrl: '../actions/product_actions.php'
    };

    // State
    let filtersVisible = true;
    let initialState = null;

    // Elements
    let elements = {};

    /**
     * Initialize the filter system
     */
    function init() {
        console.log('🚀 Initializing Product Filters...');

        // Get configuration from page if exists
        if (window.productFilterConfig) {
            filterConfig = { ...filterConfig, ...window.productFilterConfig };
            console.log('📋 Filter Config:', filterConfig);
        }

        // Cache DOM elements
        cacheElements();

        // Set up event listeners
        setupEventListeners();

        // Initialize components
        initPriceSlider();
        initSearch();

        // Load saved filter state
        loadSavedState();

        console.log('✅ Product Filters Initialized Successfully');
    }

    /**
     * Cache DOM elements
     */
    function cacheElements() {
        elements = {
            toggleBtn: document.getElementById('filterToggleBtn'),
            sidebar: document.querySelector('.filters-sidebar'),
            overlay: document.querySelector('.filters-overlay'),
            productGrid: document.querySelector('.product-grid-container'),
            searchInput: document.getElementById('filterSearchInput'),
            searchSuggestions: document.getElementById('searchSuggestions'),
            minPriceSlider: document.getElementById('minPriceSlider'),
            maxPriceSlider: document.getElementById('maxPriceSlider'),
            priceMinDisplay: document.getElementById('priceMinDisplay'),
            priceMaxDisplay: document.getElementById('priceMaxDisplay'),
            priceRange: document.getElementById('priceRange'),
            categoryBtns: document.querySelectorAll('#categoryTags .tag-btn'),
            brandBtns: document.querySelectorAll('#brandTags .tag-btn'),
            ratingInputs: document.querySelectorAll('input[name="rating_filter"]'),
            applyBtn: document.getElementById('applyFiltersBtn'),
            clearBtn: document.getElementById('clearFiltersBtn'),
            mobileCloseBtn: document.querySelector('.mobile-filter-close')
        };

        console.log('📦 Elements cached:', {
            toggleBtn: !!elements.toggleBtn,
            sidebar: !!elements.sidebar,
            applyBtn: !!elements.applyBtn,
            searchInput: !!elements.searchInput
        });
    }

    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Toggle button
        if (elements.toggleBtn) {
            elements.toggleBtn.addEventListener('click', toggleFilters);
        }

        // Mobile close button
        if (elements.mobileCloseBtn) {
            elements.mobileCloseBtn.addEventListener('click', closeFilters);
        }

        // Overlay click
        if (elements.overlay) {
            elements.overlay.addEventListener('click', closeFilters);
        }

        // Apply filters button
        if (elements.applyBtn) {
            elements.applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🎯 Apply Filters Clicked');
                applyFilters();
            });
        }

        // Clear filters button
        if (elements.clearBtn) {
            elements.clearBtn.addEventListener('click', clearFilters);
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
                console.log('Rating selected:', this.value);
            });
        });

        // Search input
        if (elements.searchInput) {
            let searchTimeout;
            elements.searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => showSearchSuggestions(query), 300);
                } else {
                    hideSearchSuggestions();
                }
            });
        }

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.filter-search')) {
                hideSearchSuggestions();
            }
        });
    }

    /**
     * Toggle filters visibility
     */
    function toggleFilters() {
        console.log('🔄 Toggling filters');
        
        if (window.innerWidth < 992) {
            // Mobile: Show/hide overlay
            if (elements.sidebar) {
                elements.sidebar.classList.toggle('show');
            }
            if (elements.overlay) {
                elements.overlay.classList.toggle('show');
            }
        } else {
            // Desktop: Slide sidebar
            if (elements.sidebar) {
                elements.sidebar.classList.toggle('hidden');
            }
            if (elements.productGrid) {
                elements.productGrid.classList.toggle('full-width');
            }
        }

        // Update button
        if (elements.toggleBtn) {
            elements.toggleBtn.classList.toggle('active');
            const isHidden = elements.sidebar.classList.contains('hidden');
            elements.toggleBtn.innerHTML = isHidden 
                ? '<i class="fas fa-filter"></i> Show Filters'
                : '<i class="fas fa-times"></i> Hide Filters';
        }

        // Save state
        filtersVisible = !elements.sidebar.classList.contains('hidden');
        localStorage.setItem('filtersVisible', filtersVisible);
    }

    /**
     * Close filters (mobile)
     */
    function closeFilters() {
        if (elements.sidebar) {
            elements.sidebar.classList.remove('show');
        }
        if (elements.overlay) {
            elements.overlay.classList.remove('show');
        }
    }

    /**
     * Initialize price slider
     */
    function initPriceSlider() {
        if (!elements.minPriceSlider || !elements.maxPriceSlider) {
            console.warn('⚠️ Price sliders not found');
            return;
        }

        console.log('🎚️ Initializing price slider');

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

        // Event listeners
        elements.minPriceSlider.addEventListener('input', updatePriceDisplay);
        elements.maxPriceSlider.addEventListener('input', updatePriceDisplay);

        // Initialize display
        updatePriceDisplay();
    }

    /**
     * Initialize search
     */
    function initSearch() {
        if (!elements.searchInput) {
            console.warn('⚠️ Search input not found');
            return;
        }

        console.log('🔍 Search initialized');
    }

    /**
     * Select tag (category or brand)
     */
    function selectTag(buttons, selectedBtn) {
        // Remove active from all
        buttons.forEach(btn => btn.classList.remove('active'));
        // Add active to selected
        selectedBtn.classList.add('active');
        console.log('✅ Tag selected:', selectedBtn.textContent.trim());
    }

    /**
     * Show search suggestions
     */
    function showSearchSuggestions(query) {
        if (!elements.searchSuggestions) return;

        console.log('🔍 Searching for:', query);

        // Get all products from window if available
        if (!window.allProducts || window.allProducts.length === 0) {
            return;
        }

        // Filter products
        const matches = window.allProducts.filter(product => {
            const title = (product.product_title || '').toLowerCase();
            const desc = (product.product_desc || '').toLowerCase();
            const keywords = (product.product_keywords || '').toLowerCase();
            const searchLower = query.toLowerCase();

            return title.includes(searchLower) || 
                   desc.includes(searchLower) || 
                   keywords.includes(searchLower);
        }).slice(0, 5); // Limit to 5 suggestions

        if (matches.length === 0) {
            elements.searchSuggestions.innerHTML = '<div class="suggestion-item">No products found</div>';
        } else {
            elements.searchSuggestions.innerHTML = matches.map(product => `
                <div class="suggestion-item" onclick="window.productFilters.selectSuggestion('${product.product_title.replace(/'/g, "\\'")}')">
                    ${highlightMatch(product.product_title, query)}
                </div>
            `).join('');
        }

        elements.searchSuggestions.classList.add('show');
    }

    /**
     * Hide search suggestions
     */
    function hideSearchSuggestions() {
        if (elements.searchSuggestions) {
            elements.searchSuggestions.classList.remove('show');
        }
    }

    /**
     * Select suggestion
     */
    function selectSuggestion(productTitle) {
        if (elements.searchInput) {
            elements.searchInput.value = productTitle;
        }
        hideSearchSuggestions();
        applyFilters();
    }

    /**
     * Highlight matching text
     */
    function highlightMatch(text, query) {
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }

    /**
     * Escape regex special characters
     */
    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Apply filters
     */
    function applyFilters() {
        console.log('🚀 Applying filters...');

        // Get filter values
        const searchQuery = elements.searchInput ? elements.searchInput.value.trim() : '';
        
        const activeCategory = document.querySelector('#categoryTags .tag-btn.active');
        const categoryId = activeCategory ? activeCategory.getAttribute('data-category') : '';
        
        const activeBrand = document.querySelector('#brandTags .tag-btn.active');
        const brandId = activeBrand ? activeBrand.getAttribute('data-brand') : '';
        
        const minPrice = elements.minPriceSlider ? parseInt(elements.minPriceSlider.value) : 0;
        const maxPrice = elements.maxPriceSlider ? parseInt(elements.maxPriceSlider.value) : 50000;
        
        const selectedRating = document.querySelector('input[name="rating_filter"]:checked');
        const rating = selectedRating ? selectedRating.value : '';

        // Build params
        const params = new URLSearchParams();
        params.append('action', 'combined_filter');
        
        if (searchQuery) params.append('query', searchQuery);
        if (categoryId) params.append('cat_ids[]', categoryId);
        if (brandId) params.append('brand_ids[]', brandId);
        if (minPrice > 0) params.append('min_price', minPrice);
        if (maxPrice < 50000) params.append('max_price', maxPrice);
        if (rating) params.append('rating', rating);

        // Use fixed category if set
        if (filterConfig.fixedCategoryId) {
            params.set('cat_ids[]', filterConfig.fixedCategoryId);
        }

        const fetchUrl = `${filterConfig.ajaxUrl}?${params.toString()}`;
        
        console.log('📡 Fetch URL:', fetchUrl);
        console.log('📊 Filter params:', {
            searchQuery, categoryId, brandId, minPrice, maxPrice, rating
        });

        // Show loading
        if (elements.applyBtn) {
            elements.applyBtn.disabled = true;
            elements.applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
        }

        // Make AJAX request
        fetch(fetchUrl)
            .then(response => {
                console.log('📥 Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Filter response:', data);
                console.log('📦 Products found:', Array.isArray(data) ? data.length : 0);
                
                // Update product grid
                updateProductGrid(data);
                
                // Close filters on mobile after applying
                if (window.innerWidth < 992) {
                    closeFilters();
                }
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
     * Clear filters
     */
    function clearFilters() {
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

        // Hide suggestions
        hideSearchSuggestions();

        // Apply filters (will show all products)
        applyFilters();
    }

    /**
     * Update product grid
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
            productGrid.innerHTML = products.map(product => createProductCard(product)).join('');
        }

        console.log('✅ Product grid updated with', products.length, 'products');
    }

    /**
     * Create product card HTML
     */
    function createProductCard(product) {
        const imageUrl = product.image_url || 'http://169.239.251.102:442/~chelsea.somuah/uploads/' + (product.product_image || '');
        const title = product.product_title || 'Unknown Product';
        const price = parseFloat(product.product_price || 0).toFixed(2);
        const category = product.cat_name || 'N/A';
        const brand = product.brand_name || 'N/A';
        const productId = product.product_id || 0;

        return `
            <div class="product-card" onclick="viewProduct(${productId})">
                <div class="product-image-container">
                    <img src="${imageUrl}" 
                         alt="${title}" 
                         class="product-image"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4=';">
                </div>
                <div class="product-content">
                    <h5 class="product-title">${title}</h5>
                    <div class="product-price">GH₵ ${price}</div>
                    <div class="product-meta">
                        <span class="meta-tag"><i class="fas fa-tag"></i> ${category}</span>
                        <span class="meta-tag"><i class="fas fa-store"></i> ${brand}</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Load saved state
     */
    function loadSavedState() {
        const saved = localStorage.getItem('filtersVisible');
        if (saved === 'false' && window.innerWidth >= 992) {
            // Desktop: hide filters if previously hidden
            toggleFilters();
        }
    }

    /**
     * Public API
     */
    window.productFilters = {
        init: init,
        applyFilters: applyFilters,
        clearFilters: clearFilters,
        toggleFilters: toggleFilters,
        selectSuggestion: selectSuggestion
    };

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

