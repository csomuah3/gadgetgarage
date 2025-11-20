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

// Get data for dashboard
$all_products = get_all_products_ctr();
$categories = get_all_categories_ctr();
$brands = get_all_brands_ctr();

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
                <form id="addProductForm" class="modern-form" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label for="product_title" class="form-label-modern">Product Title</label>
                        <input type="text" class="form-control-modern" id="product_title" name="product_title" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label for="product_price" class="form-label-modern">Excellent Condition Price (GH₵)</label>
                            <input type="number" class="form-control-modern" id="product_price" name="product_price" step="0.01" required>
                        </div>
                        <div class="col-md-5">
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
                        <select class="form-control-modern" id="product_cat" name="product_cat" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['cat_id'] ?>"><?= htmlspecialchars($category['cat_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="product_brand" class="form-label-modern">Brand</label>
                        <select class="form-control-modern" id="product_brand" name="product_brand" required>
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
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
        <div class="admin-card" style="animation-delay: 0.6s;">
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
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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
                '#4285f4',
                '#34a853',
                '#fbbc04',
                '#ea4335',
                '#ff6d01',
                '#9c27b0'
            ],
            borderWidth: 0,
            hoverBorderWidth: 3,
            hoverBorderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Form submission
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Validate required fields
    const requiredFields = ['product_title', 'product_price', 'product_cat', 'product_brand', 'stock_quantity'];
    let isValid = true;

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields'
        });
        return;
    }

    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding Product...';
    submitBtn.disabled = true;

    fetch('../actions/add_product_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
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
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while adding the product'
        });
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
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