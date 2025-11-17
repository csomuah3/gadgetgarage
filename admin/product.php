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
        $product_cat = intval($_POST['product_cat']);
        $product_brand = intval($_POST['product_brand']);
        $stock_quantity = intval($_POST['stock_quantity']);

        if (!empty($product_title) && $product_price > 0) {
            if (add_product_ctr($product_title, $product_price, $product_desc, $product_cat, $product_brand, '', $stock_quantity)) {
                $success_message = "Product added successfully!";
            } else {
                $error_message = "Failed to add product.";
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
    <div class="col-lg-4">
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
                        <div class="col-md-6">
                            <label for="product_price" class="form-label-modern">Price (GH₵)</label>
                            <input type="number" class="form-control-modern" id="product_price" name="product_price" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="stock_quantity" class="form-label-modern">Stock</label>
                            <input type="number" class="form-control-modern" id="stock_quantity" name="stock_quantity" required>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="product_desc" class="form-label-modern">Description</label>
                        <textarea class="form-control-modern" id="product_desc" name="product_desc" rows="3"></textarea>
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

                    <div class="form-group mb-4">
                        <label for="product_brand" class="form-label-modern">Brand</label>
                        <select class="form-control-modern" id="product_brand" name="product_brand" required>
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
    <div class="col-lg-8">
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
    alert(`Edit product #${productId} - Advanced edit form coming soon!`);
}

function deleteProduct(productId, productName) {
    if (confirm(`Are you sure you want to delete "${productName}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_product" value="1">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
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

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart
    setTimeout(initializeCategoryChart, 500);

    // Start counter animations
    setTimeout(animateCounters, 300);

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