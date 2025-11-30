<?php

/**
 * Reusable Category Product Layout Template
 * 
 * This template includes:
 * - Filter sidebar with conditional category filter
 * - Product grid with product cards
 * - All CSS styles
 * - All JavaScript functions
 * - AI recommendations section
 * 
 * Required Parameters (should be set before including this file):
 * @param array $products_to_display - Products to display
 * @param int $total_products - Total number of products
 * @param array $categories - All categories
 * @param array $brands - All brands
 * @param bool $is_joint_category - Whether this is a joint category page
 * @param int|null $category_id - Single category ID (for single category pages)
 * @param array $joint_category_ids - Array of category IDs (for joint category pages)
 * @param string $search_query - Search query from URL
 * @param int $min_price - Minimum price filter
 * @param int $max_price - Maximum price filter
 * @param string $category_filter - Selected category filter
 * @param string $brand_filter - Selected brand filter
 * @param int $rating_filter - Selected rating filter
 * @param bool $is_logged_in - Whether user is logged in
 * @param int|null $customer_id - Customer ID if logged in
 * @param int $current_page - Current page number for pagination
 * @param int $total_pages - Total pages for pagination
 */

// Ensure required variables exist
if (!isset($products_to_display)) $products_to_display = [];
if (!isset($total_products)) $total_products = 0;
if (!isset($categories)) $categories = [];
if (!isset($brands)) $brands = [];
if (!isset($is_joint_category)) $is_joint_category = false;
if (!isset($category_id)) $category_id = null;
if (!isset($joint_category_ids)) $joint_category_ids = [];
if (!isset($search_query)) $search_query = '';
if (!isset($min_price)) $min_price = 0;
if (!isset($max_price)) $max_price = 50000;
if (!isset($category_filter)) $category_filter = '';
if (!isset($brand_filter)) $brand_filter = '';
if (!isset($rating_filter)) $rating_filter = '';
if (!isset($is_logged_in)) $is_logged_in = false;
if (!isset($customer_id)) $customer_id = null;
if (!isset($current_page)) $current_page = 1;
if (!isset($total_pages)) $total_pages = 1;

// Helper function for product highlights
if (!function_exists('generate_product_highlights')) {
    function generate_product_highlights($product)
    {
        $brand = isset($product['brand_name']) && $product['brand_name'] ? $product['brand_name'] : 'GadgetGarage';
        $category = isset($product['cat_name']) && $product['cat_name'] ? strtolower($product['cat_name']) : 'tech';
        $price = isset($product['product_price']) ? number_format($product['product_price'], 0) : null;
        $title = isset($product['product_title']) ? $product['product_title'] : 'this device';

        $highlight_pool = [
            "$brand reliability engineered for everyday confidence",
            "Optimized for $category power-users who need speed",
            "Smart sensors inside $title keep performance seamless",
            "Energy-efficient design helps $title stay cool and quiet",
            "Premium materials built to handle busy workdays",
            $price ? "Flexible plans make GH₵{$price} easier to own" : "Flexible installment options available"
        ];

        shuffle($highlight_pool);
        return array_slice(array_unique($highlight_pool), 0, 2);
    }
}

// Filter categories for joint category pages
$filtered_categories = $categories;
if ($is_joint_category && !empty($joint_category_ids)) {
    $filtered_categories = array_filter($categories, function ($cat) use ($joint_category_ids) {
        return in_array($cat['cat_id'], $joint_category_ids);
    });
}
?>

<style>
    /* All CSS styles from all_product.php - consolidated here */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    /* Color Scheme Variables */
    :root {
        --pale-blue: #E8F0F5;
        --navy-blue: #1E3A5F;
        --royal-blue: #2563EB;
        --pure-white: #FFFFFF;
        --text-dark: #1F2937;
        --text-body: #374151;
        --text-light: #6B7280;
        --border-light: #E5E7EB;
        --bg-subtle: #F8FAFC;
        --shadow: rgba(30, 58, 95, 0.08);
        --shadow-hover: rgba(30, 58, 95, 0.12);
        --gradient-primary: linear-gradient(135deg, var(--navy-blue) 0%, var(--royal-blue) 100%);
        --gradient-subtle: linear-gradient(135deg, var(--pale-blue) 0%, var(--pure-white) 100%);
    }

    /* Sidebar Layout Styles */
    .filters-sidebar {
        background: var(--pure-white);
        padding: 35px;
        border-radius: 16px;
        box-shadow: 0 4px 12px var(--shadow);
        border: 1px solid var(--border-light);
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        margin-right: 30px;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e5e7eb;
    }

    .filter-title {
        color: var(--text-dark);
        font-weight: 800;
        font-size: 1.6rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-close {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border: none;
        color: white;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 10px 16px;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
    }

    .filter-close:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        color: white;
    }

    .filter-subtitle {
        color: var(--text-dark);
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 18px;
        display: block;
    }

    .filter-group {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .filter-group:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    /* Search Input Styles */
    .search-container {
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 14px 45px 14px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1.05rem;
        font-weight: 500;
        transition: all 0.3s ease;
        background: rgba(248, 250, 252, 0.8);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--royal-blue);
        background: var(--pure-white);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .search-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #000000;
        font-size: 0.9rem;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        cursor: pointer;
        font-size: 1.05rem;
        font-weight: 600;
        color: #374151;
        transition: all 0.3s ease;
        margin: 0;
    }

    .checkbox-item:hover {
        color: #000000;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 5px;
        padding-left: 5px;
    }

    .filter-actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid rgba(37, 99, 235, 0.1);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .apply-filters-btn {
        background: var(--gradient-primary);
        color: var(--pure-white);
        border: none;
        padding: 16px 24px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 1.05rem;
        box-shadow: 0 4px 12px var(--shadow);
        position: relative;
        width: 100%;
    }

    .apply-filters-btn:hover {
        background: linear-gradient(135deg, var(--royal-blue) 0%, var(--navy-blue) 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px var(--shadow-hover);
    }

    .clear-filters-btn {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        padding: 14px 24px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        justify-content: center;
        font-size: 1.05rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .clear-filters-btn:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    }

    .show-filters-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 14px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .show-filters-btn:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
    }

    /* Rating Filter Styles */
    .rating-filter {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .rating-option {
        display: flex;
        align-items: center;
    }

    .rating-option input[type="radio"] {
        display: none;
    }

    .rating-option label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 8px;
        border-radius: 5px;
        transition: all 0.3s ease;
        width: 100%;
        font-size: 1.05rem;
        font-weight: 600;
    }

    .rating-option label:hover {
        background: rgba(37, 99, 235, 0.08);
    }

    .rating-option input[type="radio"]:checked+label {
        background: rgba(37, 99, 235, 0.12);
        color: var(--navy-blue);
    }

    .stars {
        display: flex;
        gap: 2px;
    }

    .stars i {
        color: #ffd700;
        font-size: 16px;
    }

    .rating-text {
        font-size: 1.05rem;
        font-weight: 600;
        color: #666;
    }

    /* Price Range Slider Styles */
    .price-slider-container {
        padding: 10px 0;
    }

    .price-slider-track {
        position: relative;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        margin: 10px 0 20px 0;
    }

    .price-slider-range {
        position: absolute;
        height: 6px;
        background: #000000;
        border-radius: 3px;
        left: 0%;
        right: 0%;
    }

    .price-slider {
        position: absolute;
        top: -2px;
        width: 100%;
        height: 10px;
        background: transparent;
        outline: none;
        pointer-events: none;
        -webkit-appearance: none;
        appearance: none;
    }

    .price-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        background: var(--royal-blue);
        border-radius: 50%;
        cursor: pointer;
        pointer-events: auto;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
    }

    .price-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        background: var(--royal-blue);
        border-radius: 50%;
        cursor: pointer;
        pointer-events: auto;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
    }

    .price-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-dark);
    }

    .price-min,
    .price-max {
        font-weight: 700;
        font-size: 1.1rem;
    }

    /* Tag Filter Styles */
    .tag-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .tag-btn {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 10px 16px;
        border-radius: 20px;
        font-size: 1rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tag-btn:hover {
        background: rgba(37, 99, 235, 0.1);
        border-color: var(--royal-blue);
        color: var(--royal-blue);
    }

    .tag-btn.active {
        background: var(--gradient-primary);
        border-color: var(--navy-blue);
        color: var(--pure-white);
    }

    /* Color Filter Styles */
    .color-filters {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .color-btn {
        background: none;
        border: 2px solid transparent;
        padding: 5px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .color-btn:hover {
        border-color: var(--royal-blue);
        transform: scale(1.1);
    }

    .color-btn.active {
        border-color: var(--royal-blue);
        background: rgba(37, 99, 235, 0.1);
    }

    .color-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid rgba(0, 0, 0, 0.1);
    }

    .color-circle.all-colors {
        background: conic-gradient(from 0deg,
                #ff0000 0deg 60deg,
                #ffff00 60deg 120deg,
                #00ff00 120deg 180deg,
                #00ffff 180deg 240deg,
                #0000ff 240deg 300deg,
                #ff00ff 300deg 360deg);
    }

    .stats-bar {
        background: var(--pure-white);
        backdrop-filter: blur(20px);
        padding: 20px 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px var(--shadow);
        border: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .product-count {
        font-weight: 600;
        color: #008060;
        font-size: 1.1rem;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(420px, 1fr));
        gap: 20px;
        margin: 0;
        margin-bottom: 60px;
        width: 100%;
        padding: 0;
    }

    .modern-product-card {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        border-radius: 30px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        transition: transform 0.35s ease, box-shadow 0.35s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .modern-product-card:hover {
        transform: translateY(-12px) scale(1.01);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.15);
    }

    .modern-product-card .product-image-container {
        padding: 28px;
        text-align: center;
        height: 320px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f5f7ff;
        position: relative;
    }

    .product-card-body {
        padding: 26px 30px 32px;
        display: flex;
        flex-direction: column;
        gap: 18px;
        flex: 1;
        height: 100%;
    }

    .product-card-top {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .product-card-bottom {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .product-highlights {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .product-highlights li {
        display: flex;
        gap: 8px;
        font-size: 0.92rem;
        color: #4b5563;
        line-height: 1.35;
    }

    .product-highlights li::before {
        content: '•';
        color: #22c55e;
        font-weight: bold;
    }

    .customer-activity-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(99, 102, 241, 0.12);
        color: #4338ca;
        padding: 10px 16px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.9rem;
        animation: pillPulse 6s ease-in-out infinite;
    }

    @keyframes pillPulse {
        0% {
            transform: translateY(0);
            opacity: 0.85;
        }

        50% {
            transform: translateY(-4px);
            opacity: 1;
        }

        100% {
            transform: translateY(0);
            opacity: 0.85;
        }
    }

    .product-layout-row {
        --bs-gutter-x: 0;
        margin: 0;
    }

    #productContent {
        padding-left: 0;
        padding-right: 0;
    }

    @media (max-width: 1600px) {
        .product-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }
    }

    @media (max-width: 1200px) {
        .product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
    }

    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: 1fr;
            gap: 18px;
        }

        #filterSidebar {
            position: fixed;
            left: -100%;
            top: 0;
            width: 320px;
            height: 100vh;
            z-index: 9999;
            transition: all 0.3s ease;
            background: white;
        }

        #filterSidebar.show {
            left: 0;
        }

        .filters-sidebar {
            height: 100vh;
            max-height: none;
            border-radius: 0;
            position: static;
            top: auto;
            margin-right: 0;
        }
    }

    .no-products {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
    }

    .no-products-icon {
        font-size: 4rem;
        margin-bottom: 20px;
    }

    .no-products h3 {
        color: var(--text-dark);
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .no-products p {
        color: var(--text-light);
        font-size: 1.1rem;
    }
</style>

<div class="container-fluid mt-4">
    <div class="row product-layout-row">
        <!-- Left Sidebar - Filters -->
        <div class="col-lg-3 col-md-4" id="filterSidebar">
            <div class="filters-sidebar">
                <div class="filter-header">
                    <h3 class="filter-title">
                        <i class="fas fa-sliders-h"></i>
                        Filter Products
                    </h3>
                    <button class="filter-close" id="hideFiltersBtn" onclick="hideFilters()">
                        <i class="fas fa-eye-slash"></i>
                        <span>Hide</span>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="filter-group">
                    <div class="search-container">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search products..." autocomplete="off" value="<?php echo htmlspecialchars($search_query); ?>">
                        <i class="fas fa-search search-icon"></i>
                        <div id="searchSuggestions" class="search-suggestions" style="display: none;"></div>
                    </div>
                </div>

                <!-- Rating Filter -->
                <div class="filter-group">
                    <h6 class="filter-subtitle">Rating</h6>
                    <div class="rating-filter">
                        <div class="rating-option" data-rating="5">
                            <input type="radio" id="rating_5" name="rating_filter" value="5" <?php echo ($rating_filter == 5) ? 'checked' : ''; ?>>
                            <label for="rating_5">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <span class="rating-text">5 Star</span>
                            </label>
                        </div>
                        <div class="rating-option" data-rating="4">
                            <input type="radio" id="rating_4" name="rating_filter" value="4" <?php echo ($rating_filter == 4) ? 'checked' : ''; ?>>
                            <label for="rating_4">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span class="rating-text">4 Star</span>
                            </label>
                        </div>
                        <div class="rating-option" data-rating="3">
                            <input type="radio" id="rating_3" name="rating_filter" value="3" <?php echo ($rating_filter == 3) ? 'checked' : ''; ?>>
                            <label for="rating_3">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span class="rating-text">3 Star</span>
                            </label>
                        </div>
                        <div class="rating-option" data-rating="2">
                            <input type="radio" id="rating_2" name="rating_filter" value="2" <?php echo ($rating_filter == 2) ? 'checked' : ''; ?>>
                            <label for="rating_2">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span class="rating-text">2 Star</span>
                            </label>
                        </div>
                        <div class="rating-option" data-rating="1">
                            <input type="radio" id="rating_1" name="rating_filter" value="1" <?php echo ($rating_filter == 1) ? 'checked' : ''; ?>>
                            <label for="rating_1">
                                <div class="stars">
                                    <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <span class="rating-text">1 Star</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <h6 class="filter-subtitle">Price Range</h6>
                    <div class="price-slider-container">
                        <div class="price-slider-track">
                            <div class="price-slider-range" id="priceRange"></div>
                            <input type="range" class="price-slider" id="minPriceSlider" min="0" max="50000" value="<?php echo $min_price; ?>" step="100">
                            <input type="range" class="price-slider" id="maxPriceSlider" min="0" max="50000" value="<?php echo $max_price; ?>" step="100">
                        </div>
                        <div class="price-display">
                            <span class="price-min" id="priceMinDisplay">GH₵ <?php echo number_format($min_price); ?></span>
                            <span class="price-separator">-</span>
                            <span class="price-max" id="priceMaxDisplay">GH₵ <?php echo number_format($max_price); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Filter by Category (Conditional - only show for joint category pages) -->
                <?php if ($is_joint_category): ?>
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Category</h6>
                        <div class="tag-filters" id="categoryTags">
                            <button class="tag-btn <?php echo ($category_filter === 'all' || $category_filter === '') ? 'active' : ''; ?>" data-category="" id="category_all_btn">All</button>
                            <?php foreach ($filtered_categories as $category): ?>
                                <button class="tag-btn <?php echo ($category_filter == $category['cat_id']) ? 'active' : ''; ?>" data-category="<?php echo $category['cat_id']; ?>" id="category_btn_<?php echo $category['cat_id']; ?>">
                                    <?php echo htmlspecialchars($category['cat_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filter by Brand -->
                <div class="filter-group">
                    <h6 class="filter-subtitle">Filter By Brand</h6>
                    <div class="tag-filters" id="brandTags">
                        <button class="tag-btn <?php echo ($brand_filter === 'all' || $brand_filter === '') ? 'active' : ''; ?>" data-brand="" id="brand_all_btn">All</button>
                        <?php foreach ($brands as $brand): ?>
                            <button class="tag-btn <?php echo ($brand_filter == $brand['brand_id']) ? 'active' : ''; ?>" data-brand="<?php echo $brand['brand_id']; ?>" id="brand_btn_<?php echo $brand['brand_id']; ?>">
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Filter by Color -->
                <div class="filter-group">
                    <h6 class="filter-subtitle">Filter By Color</h6>
                    <div class="color-filters">
                        <button class="color-btn active" data-color="" title="All Colors">
                            <span class="color-circle all-colors"></span>
                        </button>
                        <button class="color-btn" data-color="blue" title="Blue">
                            <span class="color-circle" style="background-color: #0066cc;"></span>
                        </button>
                        <button class="color-btn" data-color="gray" title="Gray">
                            <span class="color-circle" style="background-color: #808080;"></span>
                        </button>
                        <button class="color-btn" data-color="green" title="Green">
                            <span class="color-circle" style="background-color: #00aa00;"></span>
                        </button>
                        <button class="color-btn" data-color="red" title="Red">
                            <span class="color-circle" style="background-color: #dd0000;"></span>
                        </button>
                        <button class="color-btn" data-color="yellow" title="Yellow">
                            <span class="color-circle" style="background-color: #ffdd00;"></span>
                        </button>
                    </div>
                </div>

                <!-- Apply/Clear Filters Buttons -->
                <div class="filter-actions">
                    <button class="apply-filters-btn" id="applyFilters">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                    <button class="clear-filters-btn" id="clearFilters">
                        <i class="fas fa-times"></i>
                        Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Content - Products -->
        <div class="col-lg-9 col-md-8" id="productContent">
            <!-- Show Filters Button (shown when filters are hidden) -->
            <button class="show-filters-btn" id="showFiltersBtn" onclick="showFilters()" style="display: none;">
                <i class="fas fa-filter"></i>
                <span>Show Filters</span>
            </button>
            <div class="stats-bar">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="product-count">
                        <i class="fas fa-box" style="margin-right: 8px;"></i>
                        Showing <?php echo count($products_to_display); ?> of <?php echo $total_products; ?> products
                    </div>
                    <!-- Sort Dropdown -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: #6b7280; font-size: 0.9rem; font-weight: 500;">Sort by:</span>
                        <select id="sortSelect" onchange="sortProducts()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; font-size: 0.9rem; cursor: pointer;">
                            <option value="alphabetically-az">Alphabetically, A-Z</option>
                            <option value="alphabetically-za">Alphabetically, Z-A</option>
                            <option value="price-low-high">Price, low to high</option>
                            <option value="price-high-low">Price, high to low</option>
                            <option value="rating-high-low">Rating, high to low</option>
                            <option value="newest">Date, new to old</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="color: #6b7280; font-size: 0.9rem; font-weight: 500;"><?php echo $total_products; ?> Products</span>
                    <div class="view-toggle" style="display: flex; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;">
                        <button class="view-btn active" onclick="toggleView('grid')" title="Grid View" style="padding: 8px 12px; border: none; background: #2563eb; color: white; cursor: pointer;">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" onclick="toggleView('list')" title="List View" style="padding: 8px 12px; border: none; background: white; color: #6b7280; cursor: pointer;">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="productsContainer">
                <?php if (empty($products_to_display)): ?>
                    <div class="no-products">
                        <div class="no-products-icon">📦</div>
                        <h3>No Products Found</h3>
                        <p>There are no products available at the moment.</p>
                    </div>
                <?php else: ?>
                    <div class="product-grid" id="productGrid">
                        <?php foreach ($products_to_display as $product):
                            $discount_percentage = rand(10, 25);
                            $original_price = $product['product_price'] * (1 + $discount_percentage / 100);
                            $rating = round(rand(40, 50) / 10, 1);
                            $highlights = generate_product_highlights($product);
                            $show_customer_activity = rand(1, 3) === 1;
                            $activity_message = '';
                            if ($show_customer_activity) {
                                $activities = [
                                    rand(2, 8) . ' people have this on their wishlist',
                                    rand(1, 5) . ' shoppers added this today',
                                    rand(3, 12) . ' customers watching this deal',
                                    rand(2, 6) . ' people comparing this model',
                                    rand(1, 4) . ' orders confirmed this hour'
                                ];
                                $activity_message = $activities[array_rand($activities)];
                            }
                        ?>
                            <div class="modern-product-card">
                                <!-- Discount Badge -->
                                <?php if ($discount_percentage > 0): ?>
                                    <div style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; z-index: 10;">
                                        -<?php echo $discount_percentage; ?>%
                                    </div>
                                <?php endif; ?>

                                <!-- Wishlist Heart & Compare Button -->
                                <div style="position: absolute; top: 12px; right: 12px; z-index: 10; display: flex; gap: 8px;">
                                    <!-- Compare Button -->
                                    <button onclick="event.stopPropagation(); addToCompare(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_title']); ?>')"
                                        class="compare-btn"
                                        style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
                                        onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';"
                                        title="Add to Compare">
                                        <i class="fas fa-balance-scale" style="color: #2563eb; font-size: 18px;"></i>
                                    </button>

                                    <!-- Wishlist Heart -->
                                    <?php
                                    $is_in_wishlist = false;
                                    if ($is_logged_in) {
                                        $is_in_wishlist = check_wishlist_item_ctr($product['product_id'], $customer_id);
                                    }
                                    $heart_class = $is_in_wishlist ? 'fas fa-heart' : 'far fa-heart';
                                    $btn_class = $is_in_wishlist ? 'wishlist-btn active' : 'wishlist-btn';
                                    ?>
                                    <button onclick="event.stopPropagation(); toggleWishlist(<?php echo $product['product_id']; ?>, this)"
                                        class="<?php echo $btn_class; ?>"
                                        style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='rgba(255,255,255,1)'; this.style.transform='scale(1.1)';"
                                        onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.transform='scale(1)';">
                                        <i class="<?php echo $heart_class; ?>" style="color: <?php echo $is_in_wishlist ? '#ef4444' : '#6b7280'; ?>; font-size: 20px;"></i>
                                    </button>
                                </div>

                                <!-- Product Image -->
                                <div class="product-image-container">
                                    <?php
                                    $image_url = get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? 'Product');
                                    $fallback_url = generate_placeholder_url($product['product_title'] ?? 'Product', '400x300');
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_url); ?>"
                                        alt="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>"
                                        class="product-image"
                                        style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.3s ease;"
                                        onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($fallback_url); ?>';">
                                </div>

                                <!-- Product Content -->
                                <div class="product-card-body">
                                    <div class="product-card-top">
                                        <h3 style="color: #1f2937; font-size: 1.3rem; font-weight: 700; line-height: 1.4; cursor: pointer;" onclick="viewProductDetails(<?php echo $product['product_id']; ?>)">
                                            <?php echo htmlspecialchars($product['product_title']); ?>
                                        </h3>

                                        <div style="display: flex; align-items: center;">
                                            <div style="color: #fbbf24; margin-right: 8px;">
                                                <?php
                                                $full_stars = floor($rating);
                                                $half_star = $rating - $full_stars >= 0.5;

                                                for ($i = 0; $i < $full_stars; $i++) {
                                                    echo '<i class="fas fa-star"></i>';
                                                }
                                                if ($half_star) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                    $full_stars++;
                                                }
                                                for ($i = $full_stars; $i < 5; $i++) {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                                ?>
                                            </div>
                                            <span style="color: #6b7280; font-size: 0.9rem; font-weight: 600;">(<?php echo $rating; ?>)</span>
                                        </div>

                                        <?php if (!empty($highlights)): ?>
                                            <ul class="product-highlights">
                                                <?php foreach ($highlights as $highlight): ?>
                                                    <li><?php echo htmlspecialchars($highlight); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-card-bottom">
                                        <?php
                                        $stock_quantity = isset($product['stock_quantity']) ? intval($product['stock_quantity']) : 10;
                                        if ($stock_quantity <= 0):
                                        ?>
                                            <div>
                                                <span style="background: #ef4444; color: white; padding: 6px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                                    <i class="fas fa-times-circle" style="margin-right: 4px;"></i>Out of Stock
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($show_customer_activity): ?>
                                            <div class="customer-activity-pill">
                                                <i class="fas fa-eye"></i>
                                                <span><?php echo htmlspecialchars($activity_message); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <div style="display: flex; align-items: baseline; gap: 12px;">
                                                <span style="color: #4f46e5; font-size: 1.75rem; font-weight: 900;">
                                                    GH₵<?php echo number_format($product['product_price'], 0); ?>
                                                </span>
                                                <span style="color: #9ca3af; font-size: 1.2rem; text-decoration: line-through; font-weight: 600;">
                                                    GH₵<?php echo number_format($original_price, 0); ?>
                                                </span>
                                            </div>
                                            <div style="color: #6b7280; font-size: 0.85rem; margin-top: 4px; line-height: 1.4;">
                                                Limited time offer - While supplies last
                                            </div>
                                        </div>

                                        <?php if ($stock_quantity > 0): ?>
                                            <button onclick="viewProductDetails(<?php echo isset($product['product_id']) ? $product['product_id'] : 0; ?>)"
                                                data-product-id="<?php echo isset($product['product_id']) ? $product['product_id'] : 0; ?>"
                                                style="width: 100%; background: #4f46e5; color: white; border: none; padding: 15px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </button>
                                        <?php else: ?>
                                            <button onclick="showOutOfStockAlert()"
                                                disabled
                                                style="width: 100%; background: #94a3b8; color: white; border: none; padding: 15px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: not-allowed; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; opacity: 0.6;">
                                                <i class="fas fa-times-circle"></i>
                                                Out of Stock
                                            </button>
                                        <?php endif; ?>

                                        <div style="text-align: center;">
                                            <p style="font-size: 0.75rem; color: #6b7280; margin: 4px 0; line-height: 1.3;">
                                                Pay in installment, with only your Ghana Card
                                            </p>
                                            <p style="font-size: 0.7rem; color: #9ca3af; margin: 4px 0; line-height: 1.3;">
                                                Contact us to Enroll in GadgetGarage's installment Plans
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 40px; flex-wrap: wrap;">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?>" class="page-btn" style="padding: 10px 20px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>"
                                    class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>"
                                    style="padding: 10px 20px; background: <?php echo $i == $current_page ? '#1d4ed8' : 'white'; ?>; color: <?php echo $i == $current_page ? 'white' : '#374151'; ?>; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?>" class="page-btn" style="padding: 10px 20px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- AI Recommendations Section -->
<?php include __DIR__ . '/ai_recommendations_section.php'; ?>

<script>
    // Hide/Show Filters Functionality
    function hideFilters() {
        const filterSidebar = document.getElementById('filterSidebar');
        const showFiltersBtn = document.getElementById('showFiltersBtn');
        const productContent = document.getElementById('productContent');

        if (filterSidebar) {
            filterSidebar.style.display = 'none';
            if (showFiltersBtn) {
                showFiltersBtn.style.display = 'flex';
            }
            if (productContent) {
                productContent.classList.remove('col-lg-9');
                productContent.classList.add('col-lg-12');
            }
        }
    }

    function showFilters() {
        const filterSidebar = document.getElementById('filterSidebar');
        const showFiltersBtn = document.getElementById('showFiltersBtn');
        const productContent = document.getElementById('productContent');

        if (filterSidebar) {
            filterSidebar.style.display = 'block';
            if (showFiltersBtn) {
                showFiltersBtn.style.display = 'none';
            }
            if (productContent) {
                productContent.classList.remove('col-lg-12');
                productContent.classList.add('col-lg-9');
            }
        }
    }

    // Make functions globally available
    window.hideFilters = hideFilters;
    window.showFilters = showFilters;

    // View Product Details
    function viewProductDetails(productId) {
        if (!productId || productId === 0) {
            console.error('Invalid product ID:', productId);
            alert('Invalid product ID');
            return;
        }
        window.location.href = 'single_product.php?pid=' + productId;
    }

    window.viewProductDetails = viewProductDetails;

    // Show Out of Stock Alert
    window.showOutOfStockAlert = function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Out of Stock!',
                text: 'This product is currently out of stock. Please check back later or browse our other available products.',
                icon: 'warning',
                iconColor: '#f59e0b',
                confirmButtonText: 'Browse Other Products',
                confirmButtonColor: '#4f46e5',
                showCancelButton: true,
                cancelButtonText: 'OK',
                cancelButtonColor: '#6b7280',
                background: '#ffffff',
                color: '#1f2937'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        } else {
            alert('This product is currently out of stock.');
        }
    };

    // Product Sorting
    function sortProducts() {
        const sortValue = document.getElementById('sortSelect').value;
        const productGrid = document.getElementById('productGrid');
        if (!productGrid) return;

        const products = Array.from(productGrid.children);

        products.sort((a, b) => {
            switch (sortValue) {
                case 'alphabetically-az':
                    return a.querySelector('h3').textContent.localeCompare(b.querySelector('h3').textContent);
                case 'alphabetically-za':
                    return b.querySelector('h3').textContent.localeCompare(a.querySelector('h3').textContent);
                case 'price-low-high':
                    const priceA = parseFloat(a.querySelector('[style*="color: #4f46e5"]').textContent.replace(/[^0-9.]/g, ''));
                    const priceB = parseFloat(b.querySelector('[style*="color: #4f46e5"]').textContent.replace(/[^0-9.]/g, ''));
                    return priceA - priceB;
                case 'price-high-low':
                    const priceA2 = parseFloat(a.querySelector('[style*="color: #4f46e5"]').textContent.replace(/[^0-9.]/g, ''));
                    const priceB2 = parseFloat(b.querySelector('[style*="color: #4f46e5"]').textContent.replace(/[^0-9.]/g, ''));
                    return priceB2 - priceA2;
                case 'rating-high-low':
                    const ratingA = a.querySelectorAll('.fas.fa-star').length;
                    const ratingB = b.querySelectorAll('.fas.fa-star').length;
                    return ratingB - ratingA;
                default:
                    return 0;
            }
        });

        productGrid.innerHTML = '';
        products.forEach(product => productGrid.appendChild(product));
    }

    // View Toggle
    function toggleView(view) {
        const productGrid = document.getElementById('productGrid');
        if (!productGrid) return;

        if (view === 'list') {
            productGrid.classList.add('list-view');
        } else {
            productGrid.classList.remove('list-view');
        }

        // Update button states
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.style.background = 'white';
            btn.style.color = '#6b7280';
        });

        event.target.closest('.view-btn').classList.add('active');
        event.target.closest('.view-btn').style.background = '#2563eb';
        event.target.closest('.view-btn').style.color = 'white';
    }

    // Price Slider Functionality
    function initPriceSlider() {
        const minSlider = document.getElementById('minPriceSlider');
        const maxSlider = document.getElementById('maxPriceSlider');
        const minDisplay = document.getElementById('priceMinDisplay');
        const maxDisplay = document.getElementById('priceMaxDisplay');
        const rangeDisplay = document.getElementById('priceRange');

        if (!minSlider || !maxSlider || !minDisplay || !maxDisplay || !rangeDisplay) return;

        function updatePriceDisplay() {
            const minVal = parseInt(minSlider.value);
            const maxVal = parseInt(maxSlider.value);

            if (minVal > maxVal - 100) {
                minSlider.value = maxVal - 100;
            }
            if (maxVal < minVal + 100) {
                maxSlider.value = minVal + 100;
            }

            const finalMin = parseInt(minSlider.value);
            const finalMax = parseInt(maxSlider.value);

            minDisplay.textContent = `GH₵ ${finalMin.toLocaleString()}`;
            maxDisplay.textContent = `GH₵ ${finalMax.toLocaleString()}`;

            const minPercent = (finalMin / parseInt(minSlider.max)) * 100;
            const maxPercent = (finalMax / parseInt(maxSlider.max)) * 100;

            rangeDisplay.style.left = `${minPercent}%`;
            rangeDisplay.style.right = `${100 - maxPercent}%`;
        }

        minSlider.addEventListener('input', updatePriceDisplay);
        maxSlider.addEventListener('input', updatePriceDisplay);
        updatePriceDisplay();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initPriceSlider();

        // Filter button handlers
        const applyFiltersBtn = document.getElementById('applyFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function() {
                // Get current page filename
                const currentPage = window.location.pathname.split('/').pop();
                const params = new URLSearchParams();

                const searchInput = document.getElementById('searchInput');
                const categoryBtn = document.querySelector('#categoryTags .tag-btn.active');
                const brandBtn = document.querySelector('#brandTags .tag-btn.active');
                const selectedRating = document.querySelector('input[name="rating_filter"]:checked');
                const minPrice = document.getElementById('minPriceSlider').value;
                const maxPrice = document.getElementById('maxPriceSlider').value;

                if (searchInput && searchInput.value) {
                    params.append('search', searchInput.value);
                }
                if (categoryBtn && categoryBtn.dataset.category) {
                    params.append('category', categoryBtn.dataset.category);
                }
                if (brandBtn && brandBtn.dataset.brand) {
                    params.append('brand', brandBtn.dataset.brand);
                }
                if (selectedRating && selectedRating.value) {
                    params.append('rating', selectedRating.value);
                }
                if (minPrice !== '0') {
                    params.append('min_price', minPrice);
                }
                if (maxPrice !== '50000') {
                    params.append('max_price', maxPrice);
                }

no                window.location.href = currentPage + '?' + params.toString();
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                const currentPage = window.location.pathname.split('/').pop();
                window.location.href = currentPage;
            });
        }
    });
</script>