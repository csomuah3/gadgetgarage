<?php

/**
 * Product Filters Sidebar
 * Reusable filter component for all product pages
 * 
 * Configuration via $filter_config array:
 * - show_category_filter: boolean - Show/hide category filter
 * - show_brand_filter: boolean - Show/hide brand filter  
 * - show_price_filter: boolean - Show/hide price filter
 * - fixed_category_id: int - Fixed category ID (for individual category pages)
 * - allowed_categories: array - Array of allowed category IDs (for joint pages)
 */

// Default configuration
$default_config = [
    'show_category_filter' => true,
    'show_brand_filter' => true,
    'show_price_filter' => true,
    'show_rating_filter' => true,
    'fixed_category_id' => null,
    'allowed_categories' => []
];

// Merge with provided config
$filter_config = isset($filter_config) ? array_merge($default_config, $filter_config) : $default_config;

// Get categories and brands if not already loaded
if (!isset($categories) || empty($categories)) {
    require_once(__DIR__ . '/../controllers/category_controller.php');
    $categories = get_all_categories_ctr();
}

if (!isset($brands) || empty($brands)) {
    require_once(__DIR__ . '/../controllers/brand_controller.php');
    $brands = get_all_brands_ctr();
}

// Filter categories if allowed_categories is set
if (!empty($filter_config['allowed_categories'])) {
    $categories = array_filter($categories, function ($cat) use ($filter_config) {
        return in_array($cat['cat_id'], $filter_config['allowed_categories']);
    });
}

// ✅ CENTRALIZED CSS - Include filter stylesheet
// Only include once per page (check if already included)
if (!defined('PRODUCT_FILTERS_CSS_INCLUDED')) {
    define('PRODUCT_FILTERS_CSS_INCLUDED', true);
    echo '<link rel="stylesheet" href="../css/product-filters.css">' . "\n";
}
?>

<!-- Filter Toggle Button -->
<button class="filter-toggle-btn" id="filterToggleBtn">
    <i class="fas fa-times"></i>
    Hide Filters
</button>

<!-- Filters Overlay (Mobile) -->
<div class="filters-overlay"></div>

<!-- Filters Sidebar -->
<div class="filters-sidebar">
    <!-- Mobile Close Button -->
    <button class="mobile-filter-close" aria-label="Close filters">
        <i class="fas fa-times"></i>
    </button>

    <!-- Filters Header -->
    <div class="filters-header">
        <h3 class="filters-title">
            <i class="fas fa-sliders-h"></i>
            Filters
        </h3>
    </div>

    <!-- Search Filter -->
    <div class="filter-search">
        <input
            type="text"
            id="filterSearchInput"
            placeholder="Search products, keywords..."
            autocomplete="off">
        <i class="fas fa-search filter-search-icon"></i>

        <!-- Search Suggestions -->
        <div class="search-suggestions" id="searchSuggestions"></div>
    </div>

    <!-- Rating Filter -->
    <?php if ($filter_config['show_rating_filter']): ?>
        <div class="filter-group">
            <h6 class="filter-subtitle">
                <i class="fas fa-star"></i>
                Rating
            </h6>
            <div class="rating-options">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <label class="rating-option">
                        <input type="radio" name="rating_filter" value="<?php echo $i; ?>">
                        <div class="rating-stars">
                            <?php for ($j = 1; $j <= 5; $j++): ?>
                                <i class="fas fa-star <?php echo $j > $i ? 'empty' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text"><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?> & Up</span>
                    </label>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Price Range Filter -->
    <?php if ($filter_config['show_price_filter']): ?>
        <div class="filter-group">
            <h6 class="filter-subtitle">
                <i class="fas fa-tags"></i>
                Price Range
            </h6>
            <div class="price-slider-container">
                <div class="price-slider-track">
                    <div class="price-slider-range" id="priceRange"></div>
                    <input
                        type="range"
                        class="price-slider"
                        id="minPriceSlider"
                        min="0"
                        max="50000"
                        value="0"
                        step="100">
                    <input
                        type="range"
                        class="price-slider"
                        id="maxPriceSlider"
                        min="0"
                        max="50000"
                        value="50000"
                        step="100">
                </div>
                <div class="price-display">
                    <span class="price-min" id="priceMinDisplay">GH₵ 0</span>
                    <span class="price-separator">-</span>
                    <span class="price-max" id="priceMaxDisplay">GH₵ 50,000</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Category Filter -->
    <?php if ($filter_config['show_category_filter'] && !$filter_config['fixed_category_id']): ?>
        <div class="filter-group">
            <h6 class="filter-subtitle">
                <i class="fas fa-th-large"></i>
                Category
            </h6>
            <div class="tag-filters" id="categoryTags">
                <button class="tag-btn active" data-category="">All</button>
                <?php foreach ($categories as $category): ?>
                    <button class="tag-btn" data-category="<?php echo $category['cat_id']; ?>">
                        <?php echo htmlspecialchars($category['cat_name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Brand Filter -->
    <?php if ($filter_config['show_brand_filter']): ?>
        <div class="filter-group">
            <h6 class="filter-subtitle">
                <i class="fas fa-trademark"></i>
                Brand
            </h6>
            <div class="tag-filters" id="brandTags">
                <button class="tag-btn active" data-brand="">All</button>
                <?php foreach ($brands as $brand): ?>
                    <button class="tag-btn" data-brand="<?php echo $brand['brand_id']; ?>">
                        <?php echo htmlspecialchars($brand['brand_name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter Actions -->
    <div class="filter-actions">
        <button class="apply-filters-btn" id="applyFiltersBtn">
            <i class="fas fa-filter"></i>
            Apply Filters
        </button>
        <button class="clear-filters-btn" id="clearFiltersBtn">
            <i class="fas fa-times"></i>
            Clear All
        </button>
    </div>
</div>

<!-- Pass config to JavaScript -->
<script>
    window.productFilterConfig = <?php echo json_encode([
                                        'showCategoryFilter' => $filter_config['show_category_filter'],
                                        'showBrandFilter' => $filter_config['show_brand_filter'],
                                        'showPriceFilter' => $filter_config['show_price_filter'],
                                        'fixedCategoryId' => $filter_config['fixed_category_id'],
                                        'allowedCategories' => $filter_config['allowed_categories']
                                    ]); ?>;

    // Load all products for search suggestions (if available)
    <?php if (isset($all_products) && !empty($all_products)): ?>
        window.allProducts = <?php echo json_encode($all_products); ?>;
    <?php endif; ?>
</script>