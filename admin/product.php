<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

$page_title = "Product Management";

// Include controllers
require_once __DIR__ . '/../controllers/product_controller.php';
require_once __DIR__ . '/../controllers/category_controller.php';
require_once __DIR__ . '/../controllers/brand_controller.php';
require_once __DIR__ . '/../settings/db_class.php';

// Check and fix table structure first
try {
    $db = new db_connection();
    $connection = $db->db_connect();

    // Check if stock_quantity column exists
    $sql = "SHOW COLUMNS FROM products LIKE 'stock_quantity'";
    $result = $db->db_fetch_one($sql);

    if (!$result) {
        error_log("Adding missing stock_quantity column to products table");
        $alter_sql = "ALTER TABLE products ADD COLUMN stock_quantity INT(11) NOT NULL DEFAULT 0";
        $db->db_write_query($alter_sql);
    }

    // Check if product_color column exists
    $sql = "SHOW COLUMNS FROM products LIKE 'product_color'";
    $result = $db->db_fetch_one($sql);

    if (!$result) {
        error_log("Adding missing product_color column to products table");
        $alter_sql = "ALTER TABLE products ADD COLUMN product_color VARCHAR(100)";
        $db->db_write_query($alter_sql);
    }


} catch (Exception $e) {
    error_log("Error checking/fixing table structure: " . $e->getMessage());
}

// Get data for dashboard
try {
    $all_products = get_all_products_ctr();
    error_log("Products fetched: " . count($all_products));
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $all_products = [];
}

try {
    $categories = get_all_categories_ctr();
    error_log("Categories fetched: " . count($categories));
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

try {
    $brands = get_all_brands_ctr();
    error_log("Brands fetched: " . count($brands));
} catch (Exception $e) {
    error_log("Error fetching brands: " . $e->getMessage());
    $brands = [];
}

// Calculate statistics
$total_products = count($all_products);
$total_inventory_value = 0;
$low_stock_products = [];
$out_of_stock = [];

foreach ($all_products as $product) {
    $total_inventory_value += $product['product_price'] * $product['stock_quantity'];
    if ($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0) {
        $low_stock_products[] = $product;
    }
    if ($product['stock_quantity'] == 0) {
        $out_of_stock[] = $product;
    }
}

// Category distribution for pie chart
$category_stats = [];
foreach ($categories as $category) {
    $cat_products = get_products_by_category_ctr($category['cat_id']);
    $category_stats[] = [
        'name' => $category['cat_name'],
        'count' => count($cat_products)
    ];
}

// Handle messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<?php include 'includes/admin_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Product Management</h1>
    <p class="page-subtitle">Manage inventory, track stock levels, and analyze product performance</p>
    <nav class="breadcrumb-custom">
        <span>Home > Products</span>
    </nav>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Analytics Dashboard -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.1s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-primary mb-3">
                    <i class="fas fa-cubes fa-3x"></i>
                </div>
                <h3 class="counter text-primary" data-target="<?= $total_products ?>">0</h3>
                <p class="text-muted mb-0">Total Products</p>
                <small class="text-info">
                    <i class="fas fa-box me-1"></i>
                    In inventory
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.2s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-success mb-3">
                    <i class="fas fa-dollar-sign fa-3x"></i>
                </div>
                <h3 class="counter text-success" data-target="<?= round($total_inventory_value) ?>">0</h3>
                <p class="text-muted mb-0">Inventory Value (GH₵)</p>
                <small class="text-success">
                    <i class="fas fa-chart-line me-1"></i>
                    Total worth
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.3s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-warning mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                </div>
                <h3 class="counter text-warning" data-target="<?= count($low_stock_products) ?>">0</h3>
                <p class="text-muted mb-0">Low Stock Items</p>
                <small class="text-warning">
                    <i class="fas fa-arrow-down me-1"></i>
                    Below 10 units
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.4s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-danger mb-3">
                    <i class="fas fa-times-circle fa-3x"></i>
                </div>
                <h3 class="counter text-danger" data-target="<?= count($out_of_stock) ?>">0</h3>
                <p class="text-muted mb-0">Out of Stock</p>
                <small class="text-danger">
                    <i class="fas fa-ban me-1"></i>
                    Zero inventory
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Product Management -->
<div class="row g-4 mb-4">
    <!-- Add Product Form -->
    <div class="col-lg-6">
        <div class="admin-card" style="animation-delay: 0.5s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-plus me-2"></i>Add New Product</h5>
            </div>
            <div class="card-body-custom">
                <style>
                    .form-group {
                        margin-bottom: 20px;
                    }

                    .form-label-modern {
                        font-weight: 600;
                        color: #333;
                        margin-bottom: 8px;
                        display: block;
                        font-size: 14px;
                    }

                    .form-control-modern {
                        width: 100%;
                        padding: 12px 16px;
                        border: 1px solid #e0e0e0;
                        border-radius: 8px;
                        font-size: 14px;
                        background: #fff;
                        transition: all 0.3s ease;
                        box-sizing: border-box;
                    }

                    .form-control-modern:focus {
                        outline: none;
                        border-color: #4285f4;
                        box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
                    }

                    .custom-dropdown {
                        position: relative;
                        width: 100%;
                    }

                    .dropdown-selected {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 12px 16px;
                        border: 1px solid #e0e0e0;
                        border-radius: 8px;
                        background: #fff;
                        cursor: pointer;
                        font-size: 14px;
                        transition: all 0.3s ease;
                    }

                    .dropdown-selected:hover {
                        border-color: #4285f4;
                    }

                    .dropdown-selected.active {
                        border-color: #4285f4;
                        border-bottom-left-radius: 0;
                        border-bottom-right-radius: 0;
                    }

                    .dropdown-text {
                        color: #666;
                    }

                    .dropdown-text.selected {
                        color: #333;
                    }

                    .dropdown-arrow {
                        transition: transform 0.3s ease;
                        color: #666;
                    }

                    .dropdown-selected.active .dropdown-arrow {
                        transform: rotate(180deg);
                    }

                    .dropdown-options {
                        position: absolute;
                        top: 100%;
                        left: 0;
                        right: 0;
                        background: #fff;
                        border: 1px solid #4285f4;
                        border-top: none;
                        border-radius: 0 0 8px 8px;
                        max-height: 200px;
                        overflow-y: auto;
                        z-index: 1000;
                        display: none;
                    }

                    .dropdown-search {
                        padding: 12px;
                        border-bottom: 1px solid #e0e0e0;
                    }

                    .dropdown-search-input {
                        width: 100%;
                        padding: 8px 12px;
                        border: 1px solid #e0e0e0;
                        border-radius: 4px;
                        font-size: 14px;
                        outline: none;
                    }

                    .dropdown-search-input:focus {
                        border-color: #4285f4;
                    }

                    .dropdown-option {
                        padding: 12px 16px;
                        cursor: pointer;
                        font-size: 14px;
                        transition: background 0.2s ease;
                    }

                    .dropdown-option:hover {
                        background: #f8f9fa;
                    }

                    .dropdown-option.selected {
                        background: #e3f2fd;
                        color: #4285f4;
                        font-weight: 500;
                    }

                    .color-selector {
                        margin-top: 8px;
                    }

                    .color-options {
                        display: grid;
                        grid-template-columns: repeat(6, 1fr);
                        gap: 12px;
                    }

                    .color-option {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        cursor: pointer;
                        padding: 8px;
                        border-radius: 8px;
                        transition: all 0.3s ease;
                    }

                    .color-option:hover {
                        background: #f8f9fa;
                    }

                    .color-option.selected {
                        background: #e3f2fd;
                        border: 2px solid #4285f4;
                    }

                    .color-circle {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        margin-bottom: 4px;
                        border: 2px solid #e0e0e0;
                    }

                    .color-name {
                        font-size: 12px;
                        color: #666;
                        text-align: center;
                    }

                    .upload-area {
                        border: 2px dashed #e0e0e0;
                        border-radius: 12px;
                        padding: 40px 20px;
                        text-align: center;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        background: #fafafa;
                        position: relative;
                    }

                    .upload-area:hover {
                        border-color: #4285f4;
                        background: #f8f9ff;
                    }

                    .upload-area.drag-over {
                        border-color: #4285f4;
                        background: #f0f8ff;
                    }

                    .upload-content h6 {
                        margin: 12px 0 8px 0;
                        color: #333;
                        font-weight: 600;
                    }

                    .upload-content p {
                        color: #666;
                        margin: 0;
                        font-size: 14px;
                    }

                    .upload-icon {
                        font-size: 32px;
                        color: #4285f4;
                        margin-bottom: 8px;
                    }

                    .file-input {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        opacity: 0;
                        cursor: pointer;
                    }

                    .image-preview {
                        margin-top: 12px;
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                        justify-content: center;
                    }
                </style>

                <form id="addProductForm" class="modern-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="product_title" class="form-label-modern">Product Title</label>
                        <input type="text" class="form-control-modern" id="product_title" name="product_title" required>
                    </div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="product_price" class="form-label-modern">Excellent Condition Price (GH₵)</label>
                                <input type="number" class="form-control-modern" id="product_price" name="product_price" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="stock_quantity" class="form-label-modern">Stock</label>
                                <input type="number" class="form-control-modern" id="stock_quantity" name="stock_quantity" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="product_desc" class="form-label-modern">Description</label>
                        <textarea class="form-control-modern" id="product_desc" name="product_desc" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="product_keywords" class="form-label-modern">Product Keywords</label>
                        <input type="text" class="form-control-modern" id="product_keywords" name="product_keywords" placeholder="e.g., laptop, gaming, portable, wireless">
                        <small class="text-muted">Separate keywords with commas for better search results</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Product Color</label>
                        <div class="color-selector">
                            <div class="color-options">
                                <div class="color-option" data-color="Black">
                                    <div class="color-circle" style="background-color: #000000;"></div>
                                    <span class="color-name">Black</span>
                                </div>
                                <div class="color-option" data-color="White">
                                    <div class="color-circle" style="background-color: #FFFFFF; border: 2px solid #e2e8f0;"></div>
                                    <span class="color-name">White</span>
                                </div>
                                <div class="color-option" data-color="Silver">
                                    <div class="color-circle" style="background-color: #C0C0C0;"></div>
                                    <span class="color-name">Silver</span>
                                </div>
                                <div class="color-option" data-color="Gray">
                                    <div class="color-circle" style="background-color: #808080;"></div>
                                    <span class="color-name">Gray</span>
                                </div>
                                <div class="color-option" data-color="Gold">
                                    <div class="color-circle" style="background-color: #FFD700;"></div>
                                    <span class="color-name">Gold</span>
                                </div>
                                <div class="color-option" data-color="Rose Gold">
                                    <div class="color-circle" style="background-color: #E8B4B8;"></div>
                                    <span class="color-name">Rose Gold</span>
                                </div>
                                <div class="color-option" data-color="Blue">
                                    <div class="color-circle" style="background-color: #007AFF;"></div>
                                    <span class="color-name">Blue</span>
                                </div>
                                <div class="color-option" data-color="Red">
                                    <div class="color-circle" style="background-color: #FF3B30;"></div>
                                    <span class="color-name">Red</span>
                                </div>
                                <div class="color-option" data-color="Green">
                                    <div class="color-circle" style="background-color: #34C759;"></div>
                                    <span class="color-name">Green</span>
                                </div>
                                <div class="color-option" data-color="Purple">
                                    <div class="color-circle" style="background-color: #AF52DE;"></div>
                                    <span class="color-name">Purple</span>
                                </div>
                                <div class="color-option" data-color="Pink">
                                    <div class="color-circle" style="background-color: #FF2D92;"></div>
                                    <span class="color-name">Pink</span>
                                </div>
                                <div class="color-option" data-color="Yellow">
                                    <div class="color-circle" style="background-color: #FFCC00;"></div>
                                    <span class="color-name">Yellow</span>
                                </div>
                            </div>
                            <input type="hidden" id="product_color" name="product_color" value="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="product_cat" class="form-label-modern">Category</label>
                        <div class="custom-dropdown" id="categoryDropdown">
                            <div class="dropdown-selected" id="category-selected">
                                <span class="dropdown-text">Laptops</span>
                                <i class="fas fa-chevron-up dropdown-arrow"></i>
                            </div>
                            <div class="dropdown-options" id="category-options">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Search categories..." class="dropdown-search-input" id="categorySearch">
                                </div>
                                <div class="dropdown-option selected" data-value="1">Laptops</div>
                                <?php foreach ($categories as $category): ?>
                                    <div class="dropdown-option" data-value="<?= $category['cat_id'] ?>"><?= htmlspecialchars($category['cat_name']) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" id="product_cat" name="product_cat" value="1">
                    </div>

                    <div class="form-group">
                        <label for="product_brand" class="form-label-modern">Brand</label>
                        <div class="custom-dropdown" id="brandDropdown">
                            <div class="dropdown-selected" id="brand-selected">
                                <span class="dropdown-text">Lenovo Laptop</span>
                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                            </div>
                            <div class="dropdown-options" id="brand-options">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Search brands..." class="dropdown-search-input" id="brandSearch">
                                </div>
                                <div class="dropdown-option selected" data-value="1">Lenovo Laptop</div>
                                <?php foreach ($brands as $brand): ?>
                                    <div class="dropdown-option" data-value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" id="product_brand" name="product_brand" value="1">
                    </div>

                    <div class="form-group">
                        <label class="form-label-modern">Product Images</label>
                        <div class="image-upload-section">
                            <div class="upload-area" id="mainImageUpload">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                    <h6>Upload Main Image</h6>
                                    <p>Drag & drop or click to select</p>
                                </div>
                                <input type="file" id="product_image" name="product_image" accept="image/*" class="file-input">
                                <div class="image-preview" id="mainImagePreview"></div>
                            </div>

                            <div class="upload-area mt-3" id="additionalImagesUpload">
                                <div class="upload-content">
                                    <i class="fas fa-images upload-icon"></i>
                                    <h6>Upload Additional Images</h6>
                                    <p>Multiple images for gallery</p>
                                </div>
                                <input type="file" id="product_images" name="product_images[]" accept="image/*" multiple class="file-input">
                                <div class="image-preview" id="additionalImagesPreview"></div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Distribution Pie Chart -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-pie me-2"></i>Category Distribution</h5>
            </div>
            <div class="card-body-custom">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="category-legend mt-3">
                    <?php foreach ($category_stats as $index => $stat): ?>
                        <div class="legend-item d-flex align-items-center mb-2">
                            <div class="legend-color" style="width: 16px; height: 16px; border-radius: 50%; margin-right: 8px; background-color: <?php
                                $colors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'];
                                echo $colors[$index % count($colors)];
                            ?>;"></div>
                            <span class="legend-text"><?= htmlspecialchars($stat['name']) ?> (<?= $stat['count'] ?> products)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Inventory -->
<div class="admin-card" style="animation-delay: 0.7s;">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-box me-2"></i>Product Inventory</h5>
        <div class="header-actions">
            <button class="btn btn-outline-primary btn-sm me-2">
                <i class="fas fa-download me-1"></i>Export
            </button>
            <button class="btn btn-primary btn-sm">
                <i class="fas fa-sync me-1"></i>Refresh
            </button>
        </div>
    </div>
    <div class="card-body-custom p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>PRODUCT</th>
                        <th>CATEGORY</th>
                        <th>BRAND</th>
                        <th>PRICE</th>
                        <th>STOCK</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_products)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Products Found</h5>
                                    <p class="text-muted">Add your first product to get started.</p>
                                    <?php if (ini_get('display_errors')): ?>
                                        <small class="text-info">Debug: Products array count = <?= count($all_products) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($all_products as $product): ?>
                        <?php
                        $status_class = 'success';
                        $status_text = 'IN STOCK';
                        if ($product['stock_quantity'] == 0) {
                            $status_class = 'danger';
                            $status_text = 'OUT OF STOCK';
                        } elseif ($product['stock_quantity'] < 10) {
                            $status_class = 'warning';
                            $status_text = 'LOW STOCK';
                        }

                        // Get category and brand names
                        $category_name = '';
                        foreach ($categories as $cat) {
                            if ($cat['cat_id'] == $product['product_cat']) {
                                $category_name = $cat['cat_name'];
                                break;
                            }
                        }

                        $brand_name = '';
                        foreach ($brands as $brand) {
                            if ($brand['brand_id'] == $product['product_brand']) {
                                $brand_name = $brand['brand_name'];
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="product-image me-3">
                                        <?php if (!empty($product['product_image'])): ?>
                                            <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/<?= htmlspecialchars($product['product_image']) ?>"
                                                 alt="<?= htmlspecialchars($product['product_title']) ?>"
                                                 class="rounded" style="width: 50px; height: 50px; object-fit: cover;"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNSAyMEwzNSAzNUgxNVYyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPGNpcmNsZSBjeD0iMjIiIGN5PSIyMiIgcj0iMyIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4='; this.onerror=null;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($product['product_title']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($product['product_id']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($category_name) ?></span></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($brand_name) ?></span></td>
                            <td><strong>GH₵<?= number_format($product['product_price'], 2) ?></strong></td>
                            <td>
                                <span class="badge bg-<?= $status_class === 'warning' ? 'warning' : 'light' ?> text-dark">
                                    <?= $product['stock_quantity'] ?> units
                                </span>
                            </td>
                            <td><span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editProduct(<?= $product['product_id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteProduct(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_title']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Fix chart animation issue */
.chart-container {
    animation: none !important;
    opacity: 1 !important;
    transform: none !important;
}

.chart-container .card-body-custom {
    animation: none !important;
}

/* Category overview styles */
.category-stats {
    max-height: 300px;
    overflow-y: auto;
}

.category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    margin-bottom: 8px;
    background: rgba(59, 130, 246, 0.05);
    border-left: 4px solid #3b82f6;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.category-item:hover {
    background: rgba(59, 130, 246, 0.1);
    border-left-color: #2563eb;
}

.category-name {
    font-weight: 600;
    color: #374151;
    flex: 1;
}

.category-count {
    font-size: 0.9rem;
    color: #6b7280;
    background: rgba(59, 130, 246, 0.1);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
}

/* Dark mode category styles */
body.dark-mode .category-item {
    background: rgba(96, 165, 250, 0.1);
    border-left-color: #60a5fa;
}

body.dark-mode .category-item:hover {
    background: rgba(96, 165, 250, 0.15);
}

body.dark-mode .category-name {
    color: #e5e7eb;
}

body.dark-mode .category-count {
    color: #9ca3af;
    background: rgba(96, 165, 250, 0.15);
}

/* Category chart legend styles */
.category-legend {
    max-height: 150px;
    overflow-y: auto;
    padding: 0 5px;
}

.legend-item {
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.legend-item:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: translateX(5px);
}

.legend-text {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.legend-color {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.legend-item:hover .legend-color {
    transform: scale(1.1);
}

/* Chart container styling */
.chart-container {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Dark mode legend styles */
body.dark-mode .legend-text {
    color: #e5e7eb;
}

body.dark-mode .legend-item:hover {
    background: rgba(96, 165, 250, 0.1);
}
</style>

<!-- Chart.js script removed -->
<script>
// Custom dropdown functionality
function setupDropdown(dropdownId, optionsId, hiddenInputId, searchId) {
    const dropdown = document.getElementById(dropdownId);
    const selected = dropdown.querySelector('.dropdown-selected');
    const options = document.getElementById(optionsId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const searchInput = document.getElementById(searchId);

    // Toggle dropdown
    selected.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const isActive = selected.classList.contains('active');

        // Close all OTHER dropdowns first (not this one)
        document.querySelectorAll('.dropdown-selected.active').forEach(sel => {
            if (sel !== selected) {
                sel.classList.remove('active');
            }
        });
        document.querySelectorAll('.dropdown-options').forEach(opts => {
            if (opts !== options) {
                opts.style.display = 'none';
            }
        });

        // Toggle current dropdown
        if (isActive) {
            selected.classList.remove('active');
            options.style.display = 'none';
        } else {
            selected.classList.add('active');
            options.style.display = 'block';
            searchInput.focus();
        }
    });

    // Handle option selection
    options.addEventListener('click', (e) => {
        if (e.target.classList.contains('dropdown-option')) {
            const value = e.target.getAttribute('data-value');
            const text = e.target.textContent;

            // Update selected text and value
            selected.querySelector('.dropdown-text').textContent = text;
            selected.querySelector('.dropdown-text').classList.add('selected');
            hiddenInput.value = value;

            // Update selected option
            options.querySelectorAll('.dropdown-option').forEach(opt => opt.classList.remove('selected'));
            e.target.classList.add('selected');

            // Close dropdown
            selected.classList.remove('active');
            options.style.display = 'none';
        }
    });

    // Search functionality
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        options.querySelectorAll('.dropdown-option').forEach(option => {
            const text = option.textContent.toLowerCase();
            option.style.display = text.includes(query) ? 'block' : 'none';
        });
    });

    // Store dropdown elements for global click handler
    dropdown._selected = selected;
    dropdown._options = options;
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing form functionality');

    // Initialize dropdowns
    setupDropdown('categoryDropdown', 'category-options', 'product_cat', 'categorySearch');
    setupDropdown('brandDropdown', 'brand-options', 'product_brand', 'brandSearch');

    // Global click handler to close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const allDropdowns = document.querySelectorAll('.custom-dropdown');
        allDropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                const selected = dropdown._selected;
                const options = dropdown._options;
                if (selected && options) {
                    selected.classList.remove('active');
                    options.style.display = 'none';
                }
            }
        });
    });

// Color selector functionality
document.querySelectorAll('.color-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('product_color').value = this.getAttribute('data-color');
    });
});

// Image upload functionality
function setupImageUpload(uploadAreaId, inputId, previewId) {
    const uploadArea = document.getElementById(uploadAreaId);
    const fileInput = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('drag-over');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(fileInput, preview);
    });

    fileInput.addEventListener('change', () => {
        handleFileSelect(fileInput, preview);
    });
}

function handleFileSelect(input, preview) {
    const files = input.files;
    preview.innerHTML = '';

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width: 60px; height: 60px; object-fit: cover; margin: 5px; border-radius: 5px;';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    }
}

// Setup image uploads
setupImageUpload('mainImageUpload', 'product_image', 'mainImagePreview');
setupImageUpload('additionalImagesUpload', 'product_images', 'additionalImagesPreview');

// Category chart JavaScript removed to prevent movement issues

// Form submission
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Form submission started');

    const formData = new FormData(this);

    // Debug: Log form data
    console.log('Form data contents:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }

    // Validate required fields
    const requiredFields = ['product_title', 'product_price', 'product_cat', 'product_brand', 'stock_quantity'];
    let isValid = true;

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        console.log(`Checking field ${field}:`, input ? input.value : 'NOT FOUND');
        if (!input || !input.value.trim()) {
            if (input) input.classList.add('is-invalid');
            isValid = false;
        } else {
            if (input) input.classList.remove('is-invalid');
        }
    });

    console.log('Validation result:', isValid);

    if (!isValid) {
        console.log('Showing validation error');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fill in all required fields'
            });
        } else {
            alert('Please fill in all required fields');
        }
        return;
    }

    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding Product...';
    submitBtn.disabled = true;

    console.log('Sending request to add_product_action.php');

    // Calculate correct path - admin/product.php needs to go up one level to actions/
    const addProductUrl = '../actions/add_product_action.php';
    console.log('Fetching from URL:', addProductUrl);

    fetch(addProductUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response. Check server logs for PHP errors.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // Instead of reloading, refresh the product list dynamically
                setTimeout(() => {
                    location.reload();
                }, 500);
            });
        } else {
            // Handle authentication/authorization errors with redirects
            if (data.action === 'redirect') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = data.redirect;
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message
                });
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);

        let errorMessage = 'An error occurred while adding the product: ' + error.message;

        // Handle specific HTTP errors
        if (error.message.includes('401')) {
            errorMessage = 'Your session has expired. Please log in again to continue.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Session Expired',
                    text: errorMessage,
                    confirmButtonText: 'Go to Login'
                }).then(() => {
                    window.location.href = '../login/login.php';
                });
            } else {
                alert(errorMessage);
                window.location.href = '../login/login.php';
            }
            return;
        } else if (error.message.includes('403')) {
            errorMessage = 'Access denied. You do not have admin privileges.';
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMessage
            });
        } else {
            alert(errorMessage);
        }
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

}); // End DOMContentLoaded

// Function to check session status
async function checkSessionStatus() {
    try {
        const response = await fetch('../actions/debug_session.php');
        const sessionInfo = await response.json();
        console.log('Session Status:', sessionInfo);

        if (!sessionInfo.check_login) {
            console.warn('User is not logged in');
            // Could show a warning banner here
        } else if (!sessionInfo.check_admin) {
            console.warn('User is not admin');
            // Could redirect to main site
        }

        return sessionInfo;
    } catch (error) {
        console.error('Failed to check session status:', error);
        return null;
    }
}

// Check session on page load
document.addEventListener('DOMContentLoaded', function() {
    checkSessionStatus();
});

// Delete product function
function deleteProduct(productId, productName) {
    Swal.fire({
        title: 'Delete Product',
        text: `Are you sure you want to delete "${productName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('product_id', productId);

            const deleteUrl = '../actions/delete_product_action.php';
            console.log('Deleting product, URL:', deleteUrl);
            
            fetch(deleteUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Delete response:', data);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to delete product'
                    });
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while deleting the product: ' + error.message
                });
            });
        }
    });
}

// Enhanced Edit product function with better UI and stock management
function editProduct(productId) {
    // Show loading state
    Swal.fire({
        title: 'Loading Product...',
        html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
        showConfirmButton: false,
        allowOutsideClick: false
    });

    // Fetch all required data in parallel
    Promise.all([
        fetch('../actions/get_product_action.php?id=' + productId).then(r => r.json()),
        fetch('../actions/get_categories_action.php').then(r => r.json()),
        fetch('../actions/get_brands_action.php').then(r => r.json())
    ])
    .then(([productData, categories, brands]) => {
        if (productData.status === 'error') {
            throw new Error(productData.message);
        }

        const product = productData.product;
        console.log('Product data:', product);
        console.log('Categories:', categories);
        console.log('Brands:', brands);

        // Build category options
        let categoryOptions = '<option value="">Select Category</option>';
        if (Array.isArray(categories) && categories.length > 0) {
            categories.forEach(category => {
                const selected = (category.cat_id == product.product_cat || category.category_id == product.product_cat) ? 'selected' : '';
                const catId = category.cat_id || category.category_id;
                const catName = category.cat_name || category.category_name;
                categoryOptions += `<option value="${catId}" ${selected}>${catName}</option>`;
            });
        }

        // Build brand options
        let brandOptions = '<option value="">Select Brand</option>';
        if (Array.isArray(brands) && brands.length > 0) {
            brands.forEach(brand => {
                const selected = (brand.brand_id == product.product_brand) ? 'selected' : '';
                brandOptions += `<option value="${brand.brand_id}" ${selected}>${brand.brand_name}</option>`;
            });
        }

        // Create enhanced edit form HTML
        const editFormHTML = `
            <div class="edit-product-form-container">
                <style>
                    .edit-product-form-container {
                        max-width: 800px;
                        margin: 0 auto;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    }
                    .form-section {
                        background: #f8f9fa;
                        border-radius: 12px;
                        padding: 20px;
                        margin-bottom: 20px;
                        border: 1px solid #e9ecef;
                    }
                    .form-section h6 {
                        color: #495057;
                        font-weight: 600;
                        margin-bottom: 15px;
                        font-size: 14px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    .form-row-custom {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 15px;
                        margin-bottom: 15px;
                    }
                    .form-row-full {
                        margin-bottom: 15px;
                    }
                    .form-group-custom {
                        display: flex;
                        flex-direction: column;
                    }
                    .form-group-custom label {
                        font-weight: 500;
                        color: #495057;
                        margin-bottom: 5px;
                        font-size: 13px;
                    }
                    .form-control-custom {
                        padding: 10px 12px;
                        border: 2px solid #e9ecef;
                        border-radius: 8px;
                        font-size: 14px;
                        transition: all 0.3s ease;
                        background: white;
                    }
                    .form-control-custom:focus {
                        border-color: #007bff;
                        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
                        outline: none;
                    }
                    .textarea-custom {
                        min-height: 100px;
                        resize: vertical;
                    }
                    .stock-indicator {
                        display: inline-block;
                        padding: 4px 8px;
                        border-radius: 12px;
                        font-size: 11px;
                        font-weight: 600;
                        margin-left: 8px;
                    }
                    .stock-low { background: #fff3cd; color: #856404; }
                    .stock-out { background: #f8d7da; color: #721c24; }
                    .stock-good { background: #d4edda; color: #155724; }
                    @media (max-width: 768px) {
                        .form-row-custom { grid-template-columns: 1fr; }
                    }
                    .custom-edit-popup {
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
                    }
                    .swal2-title {
                        font-size: 1.5rem !important;
                        font-weight: 600 !important;
                        margin-bottom: 1rem !important;
                    }
                    .swal2-actions {
                        gap: 15px !important;
                        margin-top: 2rem !important;
                    }
                </style>

                <!-- Basic Information Section -->
                <div class="form-section">
                    <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>

                    <div class="form-row-full">
                        <div class="form-group-custom">
                            <label for="edit-title">Product Title *</label>
                            <input type="text" id="edit-title" class="form-control-custom"
                                   value="${product.product_title || ''}"
                                   placeholder="Enter product title">
                        </div>
                    </div>

                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label for="edit-price">Price (GH₵) *</label>
                            <input type="number" id="edit-price" class="form-control-custom"
                                   value="${product.product_price || ''}"
                                   placeholder="0.00" min="0" step="0.01">
                        </div>
                        <div class="form-group-custom">
                            <label for="edit-stock">Stock Quantity *
                                ${product.stock_quantity <= 0 ? '<span class="stock-indicator stock-out">Out of Stock</span>' :
                                  product.stock_quantity < 10 ? '<span class="stock-indicator stock-low">Low Stock</span>' :
                                  '<span class="stock-indicator stock-good">In Stock</span>'}
                            </label>
                            <input type="number" id="edit-stock" class="form-control-custom"
                                   value="${product.stock_quantity || 0}"
                                   placeholder="0" min="0">
                        </div>
                    </div>

                    <div class="form-row-full">
                        <div class="form-group-custom">
                            <label for="edit-description">Description *</label>
                            <textarea id="edit-description" class="form-control-custom textarea-custom"
                                      placeholder="Enter detailed product description">${product.product_desc || ''}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Classification Section -->
                <div class="form-section">
                    <h6><i class="fas fa-tags me-2"></i>Classification</h6>

                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label for="edit-category">Category *</label>
                            <select id="edit-category" class="form-control-custom">
                                ${categoryOptions}
                            </select>
                        </div>
                        <div class="form-group-custom">
                            <label for="edit-brand">Brand *</label>
                            <select id="edit-brand" class="form-control-custom">
                                ${brandOptions}
                            </select>
                        </div>
                    </div>

                    <div class="form-row-full">
                        <div class="form-group-custom">
                            <label for="edit-keywords">Keywords</label>
                            <input type="text" id="edit-keywords" class="form-control-custom"
                                   value="${product.product_keywords || ''}"
                                   placeholder="smartphone, tech, gadget (comma separated)">
                        </div>
                    </div>
                </div>

                <!-- Additional Details Section -->
                <div class="form-section">
                    <h6><i class="fas fa-palette me-2"></i>Additional Details</h6>

                    <div class="form-row-full">
                        <div class="form-group-custom">
                            <label for="edit-color">Color</label>
                            <input type="text" id="edit-color" class="form-control-custom"
                                   value="${product.product_color || ''}"
                                   placeholder="Black, White, etc.">
                        </div>
                    </div>
                </div>
            </div>
        `;

        Swal.fire({
            title: '<i class="fas fa-edit text-primary me-2"></i>Edit Product',
            html: editFormHTML,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save me-2"></i>Update Product',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
            width: '900px',
            customClass: {
                popup: 'custom-edit-popup',
                confirmButton: 'btn btn-primary btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            },
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            },
            preConfirm: () => {
                // Get all form values
                const title = document.getElementById('edit-title').value.trim();
                const price = document.getElementById('edit-price').value.trim();
                const stock = document.getElementById('edit-stock').value.trim();
                const description = document.getElementById('edit-description').value.trim();
                const keywords = document.getElementById('edit-keywords').value.trim();
                const categoryId = document.getElementById('edit-category').value;
                const brandId = document.getElementById('edit-brand').value;
                const color = document.getElementById('edit-color').value.trim();

                // Enhanced validation with specific error messages
                if (!title) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Product title is required');
                    return false;
                }
                if (title.length < 3) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Product title must be at least 3 characters long');
                    return false;
                }
                if (!price || parseFloat(price) <= 0) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Please enter a valid price greater than 0');
                    return false;
                }
                if (parseFloat(price) > 1000000) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Price cannot exceed GH₵ 1,000,000');
                    return false;
                }
                if (!stock || parseInt(stock) < 0) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Stock quantity must be 0 or greater');
                    return false;
                }
                if (!description) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Product description is required');
                    return false;
                }
                if (description.length < 10) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Product description must be at least 10 characters long');
                    return false;
                }
                if (!categoryId) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Please select a category');
                    return false;
                }
                if (!brandId) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle me-2"></i>Please select a brand');
                    return false;
                }

                return {
                    product_id: productId,
                    title: title,
                    price: parseFloat(price),
                    stock_quantity: parseInt(stock),
                    description: description,
                    keywords: keywords,
                    category_id: parseInt(categoryId),
                    brand_id: parseInt(brandId),
                    color: color
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                // Show loading state
                Swal.fire({
                    title: 'Updating Product...',
                    html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Updating...</span></div>',
                    showConfirmButton: false,
                    allowOutsideClick: false
                });

                // Submit the update with all fields including stock
                const formData = new FormData();
                formData.append('product_id', result.value.product_id);
                formData.append('product_title', result.value.title);
                formData.append('product_price', result.value.price);
                formData.append('stock_quantity', result.value.stock_quantity);
                formData.append('product_desc', result.value.description);
                formData.append('product_keywords', result.value.keywords);
                formData.append('category_id', result.value.category_id);
                formData.append('brand_id', result.value.brand_id);
                formData.append('product_color', result.value.color);

                const editUrl = '../actions/edit_product_action.php';
                console.log('Updating product with data:', result.value);

                fetch(editUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Update response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Update response:', data);
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Product Updated Successfully!',
                            html: `
                                <div class="text-center">
                                    <p class="mb-2"><strong>${result.value.title}</strong> has been updated.</p>
                                    <div class="alert alert-success d-inline-block">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Stock: ${result.value.stock_quantity} units
                                    </div>
                                </div>
                            `,
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed!',
                            text: data.message || 'Failed to update product',
                            confirmButtonText: 'Try Again'
                        });
                    }
                })
                .catch(error => {
                    console.error('Update error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Error!',
                        html: `
                            <p>An error occurred while updating the product:</p>
                            <div class="alert alert-danger mt-3">
                                <small>${error.message}</small>
                            </div>
                        `,
                        confirmButtonText: 'OK'
                    });
                });
            }
        });

    })
    .catch(error => {
        console.error('Error loading product data:', error);
        Swal.fire({
            icon: 'error',
            title: 'Failed to Load Product!',
            html: `
                <p>Unable to load product data for editing:</p>
                <div class="alert alert-danger mt-3">
                    <small>${error.message}</small>
                </div>
            `,
            confirmButtonText: 'OK'
        });
    });
}


// Counter animation
function animateCounters() {
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                counter.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    });
}

// Start counter animation when page loads
document.addEventListener('DOMContentLoaded', animateCounters);

// Category Distribution Pie Chart
document.addEventListener('DOMContentLoaded', function() {
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        // Prepare data for pie chart
        const categoryData = {
            labels: [
                <?php foreach ($category_stats as $stat): ?>
                    '<?= htmlspecialchars($stat['name']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($category_stats as $stat): ?>
                        <?= $stat['count'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    '#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#8b5cf6',
                    '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 10
            }]
        };

        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: categoryData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // We'll use custom legend below
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} products (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1500,
                    easing: 'easeOutQuart'
                },
                cutout: '50%', // Makes it a doughnut chart
                elements: {
                    arc: {
                        hoverBorderWidth: 3
                    }
                }
            }
        });

        // Add animation on hover
        categoryCtx.addEventListener('mousemove', function() {
            categoryChart.update('none');
        });
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>