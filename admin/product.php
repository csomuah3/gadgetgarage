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

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $product_title = trim($_POST['product_title']);
        $product_price = floatval($_POST['product_price']);
        $product_desc = trim($_POST['product_desc']);
        $product_keywords = trim($_POST['product_keywords']);
        $product_color = trim($_POST['product_color']);
        $product_cat = intval($_POST['product_cat']);
        $product_brand = intval($_POST['product_brand']);
        $stock_quantity = intval($_POST['stock_quantity']);

        if (!empty($product_title) && $product_price > 0) {
            $result = add_product_ctr($product_title, $product_price, $product_desc, '', $product_keywords, $product_color, $product_cat, $product_brand, $stock_quantity);
            if ($result['status'] === 'success') {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = "Please fill in all required fields.";
        }
    }

    if (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        if (delete_product_ctr($product_id)) {
            $success_message = "Product deleted successfully!";
        } else {
            $error_message = "Failed to delete product.";
        }
    }
}

// Get data for analytics and display
try {
    $products = get_all_products_ctr();
    $categories = get_all_categories_ctr();
    $brands = get_all_brands_ctr();

    if (!$products) $products = [];
    if (!$categories) $categories = [];
    if (!$brands) $brands = [];

    // Product analytics
    $total_products = count($products);
    $total_inventory_value = array_sum(array_map(function($p) {
        return ($p['product_price'] ?? 0) * ($p['stock_quantity'] ?? 0);
    }, $products));
    $low_stock_products = array_filter($products, function($p) {
        return ($p['stock_quantity'] ?? 0) < 10;
    });
    $out_of_stock = array_filter($products, function($p) {
        return ($p['stock_quantity'] ?? 0) == 0;
    });

    // Category distribution
    $category_distribution = [];
    foreach ($products as $product) {
        $cat_id = $product['product_cat'];
        $category_distribution[$cat_id] = ($category_distribution[$cat_id] ?? 0) + 1;
    }

} catch (Exception $e) {
    $products = [];
    $categories = [];
    $brands = [];
    $total_products = 0;
    $total_inventory_value = 0;
    $low_stock_products = [];
    $out_of_stock = [];
    $category_distribution = [];
    $error_message = "Unable to load products: " . $e->getMessage();
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
    <div class="col-lg-8 mx-auto">
        <div class="admin-card" style="animation-delay: 0.5s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-plus me-2"></i>Add New Product</h5>
            </div>
            <div class="card-body-custom">
                <form method="POST" class="modern-form">
                    <div class="form-group mb-3">
                        <label for="product_title" class="form-label-modern">Product Title</label>
                        <input type="text" class="form-control-modern" id="product_title" name="product_title" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-11">
                            <label for="product_price" class="form-label-modern">Excellent Condition Price (GH₵)</label>
                            <input type="number" class="form-control-modern" id="product_price" name="product_price" step="0.01" required>
                        </div>
                        <div class="col-md-1">
                            <label for="stock_quantity" class="form-label-modern">Stock</label>
                            <input type="number" class="form-control-modern" id="stock_quantity" name="stock_quantity" required>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="product_desc" class="form-label-modern">Description</label>
                        <textarea class="form-control-modern" id="product_desc" name="product_desc" rows="3"></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="product_keywords" class="form-label-modern">Product Keywords</label>
                        <input type="text" class="form-control-modern" id="product_keywords" name="product_keywords" placeholder="e.g., laptop, gaming, portable, wireless">
                        <small class="text-muted">Separate keywords with commas for better search results</small>
                    </div>

                    <div class="form-group mb-3">
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

                    <div class="form-group mb-3">
                        <label for="product_cat" class="form-label-modern">Category</label>
                        <div class="custom-dropdown">
                            <div class="dropdown-selected" id="category-selected">
                                <span class="dropdown-text">Select Category</span>
                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                            </div>
                            <div class="dropdown-options" id="category-options">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Search categories..." class="dropdown-search-input">
                                </div>
                                <?php foreach ($categories as $category): ?>
                                    <div class="dropdown-option" data-value="<?= $category['cat_id'] ?>">
                                        <i class="fas fa-tags me-2"></i>
                                        <?= htmlspecialchars($category['cat_name']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="product_cat" name="product_cat" required>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="product_brand" class="form-label-modern">Brand</label>
                        <div class="custom-dropdown">
                            <div class="dropdown-selected" id="brand-selected">
                                <span class="dropdown-text">Select Brand</span>
                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                            </div>
                            <div class="dropdown-options" id="brand-options">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Search brands..." class="dropdown-search-input">
                                </div>
                                <?php foreach ($brands as $brand): ?>
                                    <div class="dropdown-option" data-value="<?= $brand['brand_id'] ?>">
                                        <i class="fas fa-trademark me-2"></i>
                                        <?= htmlspecialchars($brand['brand_name']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="product_brand" name="product_brand" required>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label-modern">Product Images</label>
                        <div class="image-upload-container">
                            <!-- Single Image Upload -->
                            <div class="upload-section">
                                <div class="upload-area" id="single-upload-area">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-text">
                                        <h6>Upload Main Image</h6>
                                        <p>Drag & drop or click to select</p>
                                    </div>
                                    <input type="file" id="single-image-input" accept="image/*" hidden>
                                </div>
                                <div class="image-preview" id="single-image-preview" style="display: none;">
                                    <img src="" alt="Preview" class="preview-image">
                                    <div class="image-overlay">
                                        <button type="button" class="btn btn-danger btn-sm remove-image">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Multiple Images Upload -->
                            <div class="upload-section mt-3">
                                <div class="upload-area" id="multiple-upload-area">
                                    <div class="upload-icon">
                                        <i class="fas fa-images"></i>
                                    </div>
                                    <div class="upload-text">
                                        <h6>Upload Additional Images</h6>
                                        <p>Multiple images for gallery</p>
                                    </div>
                                    <input type="file" id="multiple-images-input" accept="image/*" multiple hidden>
                                </div>
                                <div class="images-preview-grid" id="multiple-images-preview"></div>
                            </div>
                        </div>
                    </div>


                    <button type="submit" name="add_product" class="btn-primary-custom w-100">
                        <i class="fas fa-plus me-2"></i>
                        Add Product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Inventory Chart -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-bar me-2"></i>Category Distribution</h5>
            </div>
            <div class="card-body-custom">
                <canvas id="categoryChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="admin-card" style="animation-delay: 0.7s;">
    <div class="card-header-custom">
        <h5><i class="fas fa-list me-2"></i>Product Inventory</h5>
        <div class="ms-auto">
            <button class="btn btn-light btn-sm" onclick="filterLowStock()">
                <i class="fas fa-filter me-1"></i> Low Stock
            </button>
            <button class="btn btn-light btn-sm" onclick="refreshProducts()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>
    <div class="card-body-custom p-0">
        <?php if (!empty($products)): ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($products, 0, 15) as $index => $product): ?>
                            <?php
                            $stock = $product['stock_quantity'] ?? 0;
                            $status_class = $stock == 0 ? 'danger' : ($stock < 10 ? 'warning' : 'success');
                            $status_text = $stock == 0 ? 'Out of Stock' : ($stock < 10 ? 'Low Stock' : 'In Stock');
                            ?>
                            <tr class="product-row" style="animation-delay: <?= 0.8 + ($index * 0.05) ?>s;">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="product-avatar me-3">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($product['product_title']) ?></strong><br>
                                            <small class="text-muted">ID: #<?= $product['product_id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $category_name = 'Unknown';
                                    foreach ($categories as $cat) {
                                        if ($cat['cat_id'] == $product['product_cat']) {
                                            $category_name = $cat['cat_name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($category_name) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $brand_name = 'Unknown';
                                    foreach ($brands as $brand) {
                                        if ($brand['brand_id'] == $product['product_brand']) {
                                            $brand_name = $brand['brand_name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($brand_name) ?></span>
                                </td>
                                <td><strong class="text-success">GH₵<?= number_format($product['product_price'], 2) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $status_class ?> rounded-pill">
                                        <?= $stock ?> units
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $status_text)) ?>">
                                        <?= $status_text ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="editProduct(<?= $product['product_id'] ?>)"
                                                title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteProduct(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_title']) ?>')"
                                                title="Delete Product">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($products) > 15): ?>
                <div class="text-center p-3 border-top">
                    <small class="text-muted">Showing latest 15 products of <?= count($products) ?> total</small>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-cubes fa-4x text-muted mb-3"></i>
                <h3>No Products Found</h3>
                <p class="text-muted">Start by adding your first product.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Styles -->
<style>
.analytics-card {
    transition: all 0.3s ease;
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
}

.analytics-card:hover {
    transform: translateY(-10px);
}

.analytics-icon {
    transition: all 0.3s ease;
}

.analytics-card:hover .analytics-icon {
    transform: scale(1.1) rotate(5deg);
}

.counter {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
}

.form-label-modern {
    font-weight: 600;
    color: var(--primary-navy);
    margin-bottom: 0.5rem;
    display: block;
}

.form-control-modern {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
}

.form-control-modern:focus {
    outline: none;
    border-color: var(--electric-blue);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Force wider form container */
.admin-card {
    width: 100% !important;
    max-width: none !important;
}

.card-body-custom {
    width: 100% !important;
    max-width: none !important;
}

.modern-form {
    width: 100% !important;
    max-width: none !important;
}

/* Force wider inputs */
#product_price {
    width: 100% !important;
    min-width: 100% !important;
}

.col-md-11 .form-control-modern {
    width: 100% !important;
    min-width: 100% !important;
}

/* Custom Dropdown Styles */
.custom-dropdown {
    position: relative;
    width: 100%;
}

.dropdown-selected {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.dropdown-selected:hover {
    border-color: #cbd5e1;
}

.dropdown-selected.open {
    border-color: var(--electric-blue);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.dropdown-text {
    color: #1a202c;
    font-weight: 500;
}

.dropdown-arrow {
    color: #64748b;
    transition: transform 0.3s ease;
}

.dropdown-selected.open .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-options {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
    display: none;
}

.dropdown-options.show {
    display: block;
}

.dropdown-search {
    padding: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.dropdown-search-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    outline: none;
    font-size: 0.9rem;
}

.dropdown-option {
    padding: 0.75rem 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
    color: #1a202c;
}

.dropdown-option:hover {
    background: #f1f5f9;
    color: var(--electric-blue);
}

.dropdown-option.selected {
    background: var(--electric-blue);
    color: white;
}

/* Image Upload Styles */
.image-upload-container {
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    background: rgba(248, 250, 252, 0.5);
}

.upload-section {
    margin-bottom: 1rem;
}

.upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.upload-area:hover {
    border-color: var(--electric-blue);
    background: rgba(59, 130, 246, 0.05);
}

.upload-area.dragover {
    border-color: var(--electric-blue);
    background: rgba(59, 130, 246, 0.1);
}

.upload-icon {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.upload-area:hover .upload-icon {
    color: var(--electric-blue);
}

.upload-text h6 {
    margin: 0;
    color: #1a202c;
    font-weight: 600;
}

.upload-text p {
    margin: 0;
    color: #64748b;
    font-size: 0.9rem;
}

.image-preview {
    position: relative;
    width: 100%;
    max-width: 200px;
    margin: 1rem auto;
}

.preview-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
}

.image-overlay {
    position: absolute;
    top: 0;
    right: 0;
    padding: 0.5rem;
}

.images-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.multiple-image-preview {
    position: relative;
    width: 100%;
    height: 100px;
}

.multiple-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
}

.multiple-image-preview .image-overlay {
    position: absolute;
    top: 0;
    right: 0;
    padding: 0.25rem;
}

.product-avatar {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.product-row {
    animation: slideInFromLeft 0.6s ease forwards;
    opacity: 0;
    transform: translateX(-20px);
}

@keyframes slideInFromLeft {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.product-row:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: translateX(5px);
}

.status-in-stock {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-low-stock {
    background: linear-gradient(135deg, #fef3c7, #fed7aa);
    color: #92400e;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-out-of-stock {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

/* Color Selection Styles */
.color-selector {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    background: rgba(248, 250, 252, 0.5);
}

.color-selection-container {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    background: rgba(248, 250, 252, 0.5);
}

.color-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 1rem;
}

.color-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    background: white;
}

.color-option:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.color-option.selected {
    border-color: var(--electric-blue);
    background: rgba(59, 130, 246, 0.1);
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
}

.color-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.color-option:hover .color-circle {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.color-option.selected .color-circle {
    transform: scale(1.2);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}

.color-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: #374151;
    text-align: center;
    text-transform: capitalize;
}

.color-option.selected .color-name {
    color: var(--electric-blue);
    font-weight: 700;
}

/* Animation for color selection */
@keyframes selectColor {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

.color-option.selected {
    animation: selectColor 0.3s ease;
}

/* Responsive design for colors */
@media (max-width: 768px) {
    .color-options {
        grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
        gap: 0.75rem;
    }

    .color-option {
        padding: 0.75rem 0.25rem;
    }

    .color-circle {
        width: 25px;
        height: 25px;
    }

    .color-name {
        font-size: 0.7rem;
    }
}

/* Edit Modal Styles */
.product-edit-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.product-edit-modal.show {
    opacity: 1;
    visibility: visible;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease;
}

.product-edit-modal.show .modal-content {
    transform: translate(-50%, -50%) scale(1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.btn-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.btn-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem 2rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 0 0 16px 16px;
}

.current-image-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 120px;
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    padding: 1rem;
}

.current-product-image {
    max-width: 200px;
    max-height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
}

.no-image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #9ca3af;
    font-size: 1rem;
}

.no-image-placeholder i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.edit-image-upload {
    margin-top: 1rem;
}

.edit-image-upload .upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.edit-image-upload .upload-area:hover {
    border-color: var(--electric-blue);
    background: rgba(59, 130, 246, 0.05);
}

.edit-image-upload .upload-area.dragover {
    border-color: var(--electric-blue);
    background: rgba(59, 130, 246, 0.1);
}

/* Edit modal responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        max-height: 95vh;
    }

    .modal-header, .modal-body, .modal-footer {
        padding: 1rem;
    }

    .modal-footer {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
// Initialize category chart
function initializeCategoryChart() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?= json_encode($category_distribution) ?>;
    const categories = <?= json_encode(array_column($categories, 'cat_name', 'cat_id')) ?>;

    const labels = Object.keys(categoryData).map(id => categories[id] || 'Unknown');
    const data = Object.values(categoryData);
    const colors = [
        'rgb(59, 130, 246)',
        'rgb(245, 158, 11)',
        'rgb(16, 185, 129)',
        'rgb(239, 68, 68)',
        'rgb(139, 92, 246)',
        'rgb(236, 72, 153)'
    ];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true }
                }
            },
            animation: {
                animateRotate: true,
                duration: 2000
            }
        }
    });
}

// Counter Animation
function animateCounters() {
    const counters = document.querySelectorAll('.counter');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target'));
                const increment = target / 50;
                let count = 0;

                const updateCounter = () => {
                    if (count < target) {
                        count += increment;
                        counter.textContent = Math.ceil(count);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };

                updateCounter();
                observer.unobserve(counter);
            }
        });
    });

    counters.forEach(counter => observer.observe(counter));
}

// Product management functions
function editProduct(productId) {
    // Fetch product data and show edit modal
    fetchProductData(productId).then(product => {
        showEditModal(product);
    }).catch(error => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'Failed to load product data: ' + error.message,
                icon: 'error',
                confirmButtonColor: '#D19C97',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Failed to load product data: ' + error.message);
        }
    });
}

// Fetch product data for editing
async function fetchProductData(productId) {
    try {
        const response = await fetch(`actions/get_product_action.php?id=${productId}`);
        const data = await response.json();
        if (data.status === 'success') {
            return data.product;
        } else {
            throw new Error(data.message || 'Failed to fetch product data');
        }
    } catch (error) {
        throw error;
    }
}

// Show edit modal with product data
function showEditModal(product) {
    const modal = document.createElement('div');
    modal.className = 'product-edit-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeEditModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit me-2"></i>Edit Product</h3>
                <button type="button" class="btn-close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_product_id" value="${product.product_id}">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="edit_product_title" class="form-label-modern">Product Title</label>
                            <input type="text" class="form-control-modern" id="edit_product_title" value="${product.product_title}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_product_price" class="form-label-modern">Price (GH₵)</label>
                            <input type="number" class="form-control-modern" id="edit_product_price" value="${product.product_price}" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_product_desc" class="form-label-modern">Description</label>
                        <textarea class="form-control-modern" id="edit_product_desc" rows="3">${product.product_desc || ''}</textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_product_keywords" class="form-label-modern">Keywords</label>
                        <input type="text" class="form-control-modern" id="edit_product_keywords" value="${product.product_keywords || ''}" placeholder="e.g., laptop, gaming, portable">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_product_cat" class="form-label-modern">Category</label>
                            <select class="form-control-modern" id="edit_product_cat" required>
                                <option value="">Select Category</option>
                                ${getCategoryOptions(product.product_cat)}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_product_brand" class="form-label-modern">Brand</label>
                            <select class="form-control-modern" id="edit_product_brand" required>
                                <option value="">Select Brand</option>
                                ${getBrandOptions(product.product_brand)}
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_product_color" class="form-label-modern">Color</label>
                        <div class="color-selection-container">
                            <div class="color-options" id="editColorOptions">
                                ${getColorOptions(product.product_color)}
                            </div>
                            <input type="hidden" id="edit_product_color" value="${product.product_color || ''}">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label-modern">Current Image</label>
                        <div class="current-image-container">
                            ${product.product_image ?
                                `<img src="uploads/products/${product.product_image}" alt="Current product image" class="current-product-image">` :
                                '<div class="no-image-placeholder"><i class="fas fa-image"></i><span>No image</span></div>'
                            }
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="edit_product_image" class="form-label-modern">Update Image</label>
                        <div class="edit-image-upload">
                            <div class="upload-area" id="editUploadArea">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">
                                    <h6>Upload New Image</h6>
                                    <p>Click to select or drag & drop</p>
                                </div>
                                <input type="file" id="edit_product_image" accept="image/*" hidden>
                            </div>
                            <div class="image-preview" id="editImagePreview" style="display: none;">
                                <img src="" alt="Preview" class="preview-image">
                                <div class="image-overlay">
                                    <button type="button" class="btn btn-danger btn-sm remove-image" onclick="removeEditImage()">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateProduct()">
                    <i class="fas fa-save me-2"></i>Update Product
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Initialize color selection for edit modal
    initializeEditColorSelection();

    // Initialize image upload for edit modal
    initializeEditImageUpload();

    // Show modal with animation
    setTimeout(() => modal.classList.add('show'), 10);
}

// Helper function to generate category options
function getCategoryOptions(selectedCategoryId) {
    const categories = <?= json_encode($categories) ?>;
    return categories.map(cat =>
        `<option value="${cat.cat_id}" ${cat.cat_id == selectedCategoryId ? 'selected' : ''}>
            ${cat.cat_name}
        </option>`
    ).join('');
}

// Helper function to generate brand options
function getBrandOptions(selectedBrandId) {
    const brands = <?= json_encode($brands) ?>;
    return brands.map(brand =>
        `<option value="${brand.brand_id}" ${brand.brand_id == selectedBrandId ? 'selected' : ''}>
            ${brand.brand_name}
        </option>`
    ).join('');
}

// Helper function to generate color options
function getColorOptions(selectedColor) {
    const colors = [
        { name: 'Black', value: 'black', hex: '#000000' },
        { name: 'White', value: 'white', hex: '#ffffff' },
        { name: 'Silver', value: 'silver', hex: '#c0c0c0' },
        { name: 'Gray', value: 'gray', hex: '#808080' },
        { name: 'Gold', value: 'gold', hex: '#ffd700' },
        { name: 'Rose Gold', value: 'rose-gold', hex: '#e8b4a0' },
        { name: 'Blue', value: 'blue', hex: '#007aff' },
        { name: 'Red', value: 'red', hex: '#ff3b30' },
        { name: 'Green', value: 'green', hex: '#34c759' },
        { name: 'Purple', value: 'purple', hex: '#af52de' },
        { name: 'Pink', value: 'pink', hex: '#ff2d92' },
        { name: 'Yellow', value: 'yellow', hex: '#ffcc00' }
    ];

    return colors.map(color =>
        `<div class="color-option ${color.value === selectedColor ? 'selected' : ''}" data-color="${color.value}">
            <div class="color-circle" style="background-color: ${color.hex}; ${color.value === 'white' ? 'border: 1px solid #e2e8f0;' : ''}"></div>
            <span class="color-name">${color.name}</span>
        </div>`
    ).join('');
}

// Close edit modal
function closeEditModal() {
    const modal = document.querySelector('.product-edit-modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

// Initialize color selection for edit modal
function initializeEditColorSelection() {
    const colorOptions = document.querySelectorAll('#editColorOptions .color-option');
    const colorInput = document.getElementById('edit_product_color');

    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            colorOptions.forEach(opt => opt.classList.remove('selected'));

            // Add selected class to clicked option
            this.classList.add('selected');

            // Set the hidden input value
            const selectedColor = this.getAttribute('data-color');
            colorInput.value = selectedColor;
        });
    });
}

// Initialize image upload for edit modal
function initializeEditImageUpload() {
    const uploadArea = document.getElementById('editUploadArea');
    const imageInput = document.getElementById('edit_product_image');
    const imagePreview = document.getElementById('editImagePreview');

    uploadArea.addEventListener('click', () => {
        imageInput.click();
    });

    imageInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            displayEditImagePreview(file);
        }
    });

    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function() {
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');

        const files = Array.from(e.dataTransfer.files).filter(file =>
            file.type.startsWith('image/')
        );

        if (files.length > 0) {
            imageInput.files = e.dataTransfer.files;
            displayEditImagePreview(files[0]);
        }
    });
}

// Display edit image preview
function displayEditImagePreview(file) {
    const uploadArea = document.getElementById('editUploadArea');
    const imagePreview = document.getElementById('editImagePreview');

    const reader = new FileReader();
    reader.onload = function(e) {
        imagePreview.querySelector('.preview-image').src = e.target.result;
        imagePreview.style.display = 'block';
        uploadArea.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

// Remove edit image
function removeEditImage() {
    const uploadArea = document.getElementById('editUploadArea');
    const imagePreview = document.getElementById('editImagePreview');
    const imageInput = document.getElementById('edit_product_image');

    imagePreview.style.display = 'none';
    uploadArea.style.display = 'block';
    imageInput.value = '';
}

// Update product
async function updateProduct() {
    const form = document.getElementById('editProductForm');
    const submitBtn = document.querySelector('.modal-footer .btn-primary');
    const originalBtnText = submitBtn.innerHTML;

    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
    submitBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('product_id', document.getElementById('edit_product_id').value);
        formData.append('product_title', document.getElementById('edit_product_title').value);
        formData.append('product_price', document.getElementById('edit_product_price').value);
        formData.append('product_desc', document.getElementById('edit_product_desc').value);
        formData.append('product_keywords', document.getElementById('edit_product_keywords').value);
        formData.append('category_id', document.getElementById('edit_product_cat').value);
        formData.append('brand_id', document.getElementById('edit_product_brand').value);
        formData.append('product_color', document.getElementById('edit_product_color').value);

        // Add image file if selected
        const imageInput = document.getElementById('edit_product_image');
        if (imageInput.files.length > 0) {
            formData.append('product_image', imageInput.files[0]);
        }

        const response = await fetch('actions/update_product_action.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            closeEditModal();
            // Refresh the page to show updated data
            window.location.reload();
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'Error updating product: ' + (data.message || 'Unknown error'),
                    icon: 'error',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Error updating product: ' + (data.message || 'Unknown error'));
            }
        }
    } catch (error) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'Error updating product: ' + error.message,
                icon: 'error',
                confirmButtonColor: '#D19C97',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Error updating product: ' + error.message);
        }
    } finally {
        // Restore button state
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

function deleteProduct(productId, productName) {
    if (typeof Swal !== 'undefined') {
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
                performDeleteProduct(productId);
            }
        });
    } else {
        if (confirm(`Are you sure you want to delete "${productName}"?`)) {
            performDeleteProduct(productId);
        }
    }
}

function performDeleteProduct(productId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="delete_product" value="1">
        <input type="hidden" name="product_id" value="${productId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function filterLowStock() {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        const stockBadge = row.querySelector('.badge');
        const stockText = stockBadge ? stockBadge.textContent : '';
        const stockNumber = parseInt(stockText.match(/\d+/));

        if (stockNumber < 10) {
            row.style.display = '';
            row.style.background = 'rgba(245, 158, 11, 0.1)';
        } else {
            row.style.display = 'none';
        }
    });
}

function refreshProducts() {
    window.location.reload();
}

// Custom Dropdown Functionality
function initializeDropdowns() {
    // Category Dropdown
    const categorySelected = document.getElementById('category-selected');
    const categoryOptions = document.getElementById('category-options');
    const categoryInput = document.getElementById('product_cat');
    const categorySearchInput = categoryOptions.querySelector('.dropdown-search-input');

    categorySelected.addEventListener('click', function() {
        categoryOptions.classList.toggle('show');
        categorySelected.classList.toggle('open');
    });

    categoryOptions.querySelectorAll('.dropdown-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const text = this.textContent.trim();

            categorySelected.querySelector('.dropdown-text').textContent = text;
            categoryInput.value = value;
            categoryOptions.classList.remove('show');
            categorySelected.classList.remove('open');

            // Remove previous selection
            categoryOptions.querySelectorAll('.dropdown-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // Search functionality for categories
    categorySearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        categoryOptions.querySelectorAll('.dropdown-option').forEach(option => {
            const text = option.textContent.toLowerCase();
            option.style.display = text.includes(searchTerm) ? 'flex' : 'none';
        });
    });

    // Brand Dropdown
    const brandSelected = document.getElementById('brand-selected');
    const brandOptions = document.getElementById('brand-options');
    const brandInput = document.getElementById('product_brand');
    const brandSearchInput = brandOptions.querySelector('.dropdown-search-input');

    brandSelected.addEventListener('click', function() {
        brandOptions.classList.toggle('show');
        brandSelected.classList.toggle('open');
    });

    brandOptions.querySelectorAll('.dropdown-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const text = this.textContent.trim();

            brandSelected.querySelector('.dropdown-text').textContent = text;
            brandInput.value = value;
            brandOptions.classList.remove('show');
            brandSelected.classList.remove('open');

            // Remove previous selection
            brandOptions.querySelectorAll('.dropdown-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // Search functionality for brands
    brandSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        brandOptions.querySelectorAll('.dropdown-option').forEach(option => {
            const text = option.textContent.toLowerCase();
            option.style.display = text.includes(searchTerm) ? 'flex' : 'none';
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            categoryOptions.classList.remove('show');
            categorySelected.classList.remove('open');
            brandOptions.classList.remove('show');
            brandSelected.classList.remove('open');
        }
    });

    // Color Picker Functionality
    const colorOptions = document.querySelectorAll('.color-option');
    const colorInput = document.getElementById('product_color');

    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            colorOptions.forEach(opt => opt.classList.remove('selected'));

            // Add selected class to clicked option
            this.classList.add('selected');

            // Set the value in hidden input
            const colorValue = this.getAttribute('data-color');
            colorInput.value = colorValue;
        });
    });
}

// Image Upload Functionality
function initializeImageUpload() {
    const singleUploadArea = document.getElementById('single-upload-area');
    const singleImageInput = document.getElementById('single-image-input');
    const singleImagePreview = document.getElementById('single-image-preview');

    const multipleUploadArea = document.getElementById('multiple-upload-area');
    const multipleImagesInput = document.getElementById('multiple-images-input');
    const multipleImagesPreview = document.getElementById('multiple-images-preview');

    let selectedImages = [];

    // Single image upload
    singleUploadArea.addEventListener('click', () => {
        singleImageInput.click();
    });

    singleImageInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            displaySingleImagePreview(file);
        }
    });

    // Multiple images upload
    multipleUploadArea.addEventListener('click', () => {
        multipleImagesInput.click();
    });

    multipleImagesInput.addEventListener('change', function() {
        Array.from(this.files).forEach(file => {
            selectedImages.push(file);
        });
        displayMultipleImagesPreview();
    });

    // Drag and drop functionality
    [singleUploadArea, multipleUploadArea].forEach(area => {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        area.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            const files = Array.from(e.dataTransfer.files).filter(file =>
                file.type.startsWith('image/')
            );

            if (this === singleUploadArea && files.length > 0) {
                displaySingleImagePreview(files[0]);
            } else if (this === multipleUploadArea) {
                files.forEach(file => selectedImages.push(file));
                displayMultipleImagesPreview();
            }
        });
    });

    function displaySingleImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            singleImagePreview.querySelector('.preview-image').src = e.target.result;
            singleImagePreview.style.display = 'block';
            singleUploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);

        // Add remove functionality
        singleImagePreview.querySelector('.remove-image').onclick = function() {
            singleImagePreview.style.display = 'none';
            singleUploadArea.style.display = 'block';
            singleImageInput.value = '';
        };
    }

    function displayMultipleImagesPreview() {
        multipleImagesPreview.innerHTML = '';
        selectedImages.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'multiple-image-preview';
                previewDiv.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <div class="image-overlay">
                        <button type="button" class="btn btn-danger btn-sm remove-multiple-image" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                multipleImagesPreview.appendChild(previewDiv);
            };
            reader.readAsDataURL(file);
        });

        // Add remove functionality for multiple images
        setTimeout(() => {
            document.querySelectorAll('.remove-multiple-image').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    selectedImages.splice(index, 1);
                    displayMultipleImagesPreview();
                });
            });
        }, 100);
    }
}

// Color Selection Functionality
function initializeColorSelection() {
    const colorOptions = document.querySelectorAll('.color-option');
    const colorInput = document.getElementById('product_color');

    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            colorOptions.forEach(opt => opt.classList.remove('selected'));

            // Add selected class to clicked option
            this.classList.add('selected');

            // Set the hidden input value
            const selectedColor = this.getAttribute('data-color');
            colorInput.value = selectedColor;

            // Optional: Show confirmation
            console.log('Selected color:', selectedColor);
        });
    });

    // Set default selection (first color - black)
    if (colorOptions.length > 0) {
        colorOptions[0].classList.add('selected');
        colorInput.value = colorOptions[0].getAttribute('data-color');
    }
}

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart
    setTimeout(initializeCategoryChart, 500);

    // Start counter animations
    setTimeout(animateCounters, 300);

    // Initialize custom dropdowns
    initializeDropdowns();

    // Initialize image upload
    initializeImageUpload();

    // Initialize color selection
    initializeColorSelection();

    // Animate cards
    const cards = document.querySelectorAll('.admin-card, .analytics-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Animate table rows
    setTimeout(() => {
        document.querySelectorAll('.product-row').forEach((row, index) => {
            setTimeout(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateX(0)';
            }, index * 50);
        });
    }, 800);
});
</script>

<?php include 'includes/admin_footer.php'; ?>