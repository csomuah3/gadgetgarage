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

    <!-- Category Distribution -->
    <div class="col-lg-6">
        <div class="admin-card chart-container">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-pie me-2"></i>Category Distribution</h5>
            </div>
            <div class="card-body-custom text-center">
                <canvas id="categoryChart" width="300" height="300"></canvas>
                <div class="chart-legend mt-3">
                    <div class="legend-items">
                        <!-- Legend will be generated by JavaScript -->
                    </div>
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
                                                 class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
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

/* Ensure chart canvas is stable */
#categoryChart {
    position: relative !important;
    animation: none !important;
    transform: none !important;
}

/* Override any sliding animations for charts */
.admin-card.chart-container {
    animation: none !important;
    animation-delay: 0s !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

// Category chart
const categoryData = <?= json_encode($category_stats) ?>;
const ctx = document.getElementById('categoryChart').getContext('2d');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: categoryData.map(cat => cat.name),
        datasets: [{
            data: categoryData.map(cat => cat.count),
            backgroundColor: [
                '#e74c3c',  // Red
                '#f39c12',  // Orange
                '#9b59b6',  // Purple
                '#3498db',  // Blue
                '#2ecc71',  // Green
                '#34495e'   // Dark Gray
            ],
            borderWidth: 0,
            hoverBorderWidth: 0,
            hoverBorderColor: 'transparent'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            animateRotate: false,
            animateScale: false,
            duration: 0
        },
        hover: {
            mode: null,
            animationDuration: 0
        },
        responsiveAnimationDuration: 0,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: false
            }
        },
        elements: {
            arc: {
                borderWidth: 0
            }
        },
        interaction: {
            intersect: false
        }
    }
});

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

    fetch('../actions/add_product_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
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
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while adding the product: ' + error.message
            });
        } else {
            alert('An error occurred while adding the product: ' + error.message);
        }
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

}); // End DOMContentLoaded

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

            fetch('../actions/delete_product_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
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
                        text: data.message
                    });
                }
            });
        }
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
</script>

<?php include 'includes/admin_footer.php'; ?>