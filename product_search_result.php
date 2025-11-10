<?php
require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/product_controller.php');
require_once(__DIR__ . '/controllers/category_controller.php');
require_once(__DIR__ . '/controllers/brand_controller.php');
require_once(__DIR__ . '/helpers/image_helper.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get search parameters
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$category_filter = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
$brand_filter = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;

// Get all categories and brands for filters
$categories = get_all_categories_ctr();
$brands = get_all_brands_ctr();

// Perform search
$products = [];
if (!empty($search_query)) {
    $products = search_products_ctr($search_query);

    // Apply additional filters if specified
    if ($category_filter > 0) {
        $products = array_filter($products, function($product) use ($category_filter) {
            return $product['product_cat'] == $category_filter;
        });
    }

    if ($brand_filter > 0) {
        $products = array_filter($products, function($product) use ($brand_filter) {
            return $product['product_brand'] == $brand_filter;
        });
    }

    $products = array_values($products); // Re-index array
}

// Pagination settings
$products_per_page = 10;
$total_products = count($products);
$total_pages = ceil($total_products / $products_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;
$products_to_display = array_slice($products, $offset, $products_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search Results<?php echo !empty($search_query) ? ' for "' . htmlspecialchars($search_query) . '"' : ''; ?> - FlavorHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
            color: #1a202c;
        }

        .main-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            box-shadow: 0 2px 10px rgba(139, 95, 191, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #8b5fbf;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(139, 95, 191, 0.1);
            margin-bottom: 30px;
        }

        .search-input-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .search-input:focus {
            outline: none;
            border-color: #8b5fbf;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b5fbf;
            font-size: 1.2rem;
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: translateY(-50%) scale(1.05);
        }

        .search-results-info {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }

        .results-count {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .search-term {
            font-size: 1rem;
            opacity: 0.9;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(139, 95, 191, 0.1);
            margin-bottom: 30px;
        }

        .filter-title {
            color: #8b5fbf;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .filter-select:focus {
            outline: none;
            border-color: #8b5fbf;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(139, 95, 191, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.2);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8fafc;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #8b5fbf;
            margin-bottom: 10px;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #64748b;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: scale(1.02);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 40px 0;
        }

        .page-btn {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .page-btn:hover, .page-btn.active {
            background: #8b5fbf;
            color: white;
            border-color: #8b5fbf;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            color: white;
            transform: scale(1.02);
        }

        .hero-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-actions .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            border-width: 2px;
        }

        .clear-filters-btn {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-filters-btn:hover {
            background: #cbd5e0;
            color: #2d3748;
        }

        .highlight {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-2">
                    <a href="index.php" class="logo">
                        <i class="fas fa-utensils"></i>
                        <span>FlavorHub</span>
                    </a>
                </div>
                <div class="col-lg-8 text-center">
                    <h1 class="mb-0" style="color: #8b5fbf; font-weight: 700;">Search Results</h1>
                </div>
                <div class="col-lg-2 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <!-- Cart Icon -->
                        <a href="#" class="cart-icon position-relative" onclick="showCart()">
                            <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: #8b5fbf;"></i>
                            <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
                                0
                            </span>
                        </a>

                        <?php if ($is_logged_in): ?>
                            <a href="login/logout.php" class="btn btn-outline-danger">Logout</a>
                        <?php else: ?>
                            <a href="login/login.php" class="btn btn-outline-primary">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mt-4">
        <!-- Hero Bar -->
        <div class="hero-bar">
            <div class="d-flex align-items-center justify-content-between">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
                <div class="hero-title">
                    <h2 class="mb-0 text-muted" style="font-size: 1.2rem; font-weight: 600;">
                        Search Results for "<?php echo htmlspecialchars($search_query); ?>"
                    </h2>
                </div>
                <div class="hero-actions">
                    <button class="btn btn-outline-primary" onclick="document.getElementById('query').focus()">
                        <i class="fas fa-search"></i>
                        New Search
                    </button>
                </div>
            </div>
        </div>

        <div class="search-header">
            <form method="GET" action="product_search_result.php">
                <div class="search-input-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="query" class="search-input"
                           placeholder="Search for products..."
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($search_query)): ?>
            <div class="search-results-info">
                <div class="results-count">
                    <?php echo $total_products; ?> Product<?php echo $total_products != 1 ? 's' : ''; ?> Found
                </div>
                <div class="search-term">
                    for "<?php echo htmlspecialchars($search_query); ?>"
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($search_query)): ?>
            <div class="filters-section">
                <h5 class="filter-title">Narrow Your Search</h5>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select class="filter-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['cat_id']; ?>"
                                        <?php echo $category_filter == $category['cat_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['cat_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <select class="filter-select" id="brandFilter">
                            <option value="">All Brands</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['brand_id']; ?>"
                                        <?php echo $brand_filter == $brand['brand_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['brand_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="clear-filters-btn w-100" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div id="resultsContainer">
            <?php if (empty($search_query)): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-4x mb-3" style="color: #cbd5e0;"></i>
                    <h3>Search for Products</h3>
                    <p>Enter a search term above to find products.</p>
                    <a href="all_product.php" class="btn btn-primary mt-3">
                        <i class="fas fa-grid-3x3"></i> Browse All Products
                    </a>
                </div>
            <?php elseif (empty($products_to_display)): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-4x mb-3" style="color: #cbd5e0;"></i>
                    <h3>No Results Found</h3>
                    <p>We couldn't find any products matching "<?php echo htmlspecialchars($search_query); ?>"</p>
                    <div class="mt-3">
                        <a href="all_product.php" class="btn btn-primary me-2">
                            <i class="fas fa-grid-3x3"></i> Browse All Products
                        </a>
                        <button class="btn btn-outline-secondary" onclick="document.querySelector('.search-input').focus()">
                            <i class="fas fa-search"></i> Try Another Search
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="product-grid" id="productGrid">
                    <?php foreach ($products_to_display as $product): ?>
                        <div class="product-card" onclick="viewProduct(<?php echo $product['product_id']; ?>)">
                            <img src=""
                                 alt="<?php echo htmlspecialchars($product['product_title']); ?>"
                                 class="product-image"
                                 data-product-id="<?php echo $product['product_id']; ?>"
                                 data-product-image="<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>"
                                 data-product-title="<?php echo htmlspecialchars($product['product_title']); ?>">
                            <div class="product-content">
                                <h5 class="product-title">
                                    <?php
                                    $title = htmlspecialchars($product['product_title']);
                                    if (!empty($search_query)) {
                                        $title = preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<span class="highlight">$1</span>', $title);
                                    }
                                    echo $title;
                                    ?>
                                </h5>
                                <div class="product-price">$<?php echo number_format($product['product_price'], 2); ?></div>
                                <div class="product-meta">
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['cat_name'] ?? 'N/A'); ?></span>
                                    <span><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></span>
                                </div>
                                <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(<?php echo $product['product_id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?query=<?php echo urlencode($search_query); ?>&cat_id=<?php echo $category_filter; ?>&brand_id=<?php echo $brand_filter; ?>&page=<?php echo $current_page - 1; ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?query=<?php echo urlencode($search_query); ?>&cat_id=<?php echo $category_filter; ?>&brand_id=<?php echo $brand_filter; ?>&page=<?php echo $i; ?>"
                               class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?query=<?php echo urlencode($search_query); ?>&cat_id=<?php echo $category_filter; ?>&brand_id=<?php echo $brand_filter; ?>&page=<?php echo $current_page + 1; ?>" class="page-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewProduct(productId) {
            window.location.href = 'single_product.php?id=' + productId;
        }

        function addToCart(productId) {
            // Add visual feedback
            const btn = event.target.closest('.add-to-cart-btn');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-check"></i> Added!';
            btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = 'linear-gradient(135deg, #8b5fbf, #f093fb)';
            }, 1500);

            // Here you would normally send AJAX request to add to cart
            console.log('Add to cart functionality - Product ID: ' + productId);

            // Update cart count
            updateCartCount();
        }

        function showCart() {
            alert('Cart functionality will be implemented soon!\nThis will show your cart items.');
        }

        function updateCartCount() {
            // This would normally get the actual cart count from storage/database
            const cartCountElement = document.getElementById('cartCount');
            let currentCount = parseInt(cartCountElement.textContent);
            cartCountElement.textContent = currentCount + 1;
        }

        function clearFilters() {
            const searchQuery = '<?php echo addslashes($search_query); ?>';
            window.location.href = 'product_search_result.php?query=' + encodeURIComponent(searchQuery);
        }

        // Image Loading System
        function loadProductImages() {
            document.querySelectorAll('.product-image').forEach(img => {
                const productId = img.getAttribute('data-product-id');
                const productTitle = img.getAttribute('data-product-title');

                fetch(`actions/upload_product_image_action.php?action=get_image_url&product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.url) {
                            img.src = data.url;
                        } else {
                            img.src = generatePlaceholderUrl(productTitle);
                        }
                    })
                    .catch(error => {
                        console.log('Image load error for product', productId, '- using placeholder');
                        img.src = generatePlaceholderUrl(productTitle);
                    });
            });
        }

        function generatePlaceholderUrl(text, size = '320x240') {
            const encodedText = encodeURIComponent(text);
            return `https://via.placeholder.com/${size}/8b5fbf/ffffff?text=${encodedText}`;
        }

        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Load product images
            loadProductImages();
            const categoryFilter = document.getElementById('categoryFilter');
            const brandFilter = document.getElementById('brandFilter');

            function applyFilters() {
                const categoryId = categoryFilter.value;
                const brandId = brandFilter.value;
                const searchQuery = '<?php echo addslashes($search_query); ?>';

                const params = new URLSearchParams();
                if (searchQuery) params.append('query', searchQuery);
                if (categoryId) params.append('cat_id', categoryId);
                if (brandId) params.append('brand_id', brandId);

                window.location.href = 'product_search_result.php?' + params.toString();
            }

            if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
            if (brandFilter) brandFilter.addEventListener('change', applyFilters);

            // Auto-focus search input if no query
            <?php if (empty($search_query)): ?>
            document.querySelector('.search-input').focus();
            <?php endif; ?>
        });
    </script>
</body>
</html>