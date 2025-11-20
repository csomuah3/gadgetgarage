<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

$page_title = "Brand Management";

// Include controllers
require_once __DIR__ . '/../controllers/brand_controller.php';
require_once __DIR__ . '/../controllers/category_controller.php';

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_brand'])) {
        $brand_name = trim($_POST['brand_name']);
        $cat_id = intval($_POST['cat_id']);

        if (!empty($brand_name) && $cat_id > 0) {
            $result = add_brand_ctr($brand_name, $cat_id, $_SESSION['user_id'] ?? null);
            if (is_array($result)) {
                if ($result['status'] === 'success') {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
            } else if ($result) {
                $success_message = "Brand added successfully!";
            } else {
                $error_message = "Failed to add brand or brand already exists.";
            }
        } else {
            $error_message = "Brand name and category are required.";
        }
    }

    if (isset($_POST['update_brand'])) {
        $brand_id = intval($_POST['brand_id']);
        $brand_name = trim($_POST['brand_name']);
        $cat_id = intval($_POST['cat_id']);

        if (!empty($brand_name) && $cat_id > 0) {
            $result = update_brand_ctr($brand_id, $brand_name, $cat_id, $_SESSION['user_id'] ?? null);
            if (is_array($result)) {
                if ($result['status'] === 'success') {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
            } else if ($result) {
                $success_message = "Brand updated successfully!";
            } else {
                $error_message = "Failed to update brand.";
            }
        } else {
            $error_message = "Brand name and category are required.";
        }
    }

    if (isset($_POST['delete_brand'])) {
        $brand_id = intval($_POST['brand_id']);
        $result = delete_brand_ctr($brand_id);
        if (is_array($result)) {
            if ($result['status'] === 'success') {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        } else if ($result) {
            $success_message = "Brand deleted successfully!";
        } else {
            $error_message = "Failed to delete brand. It may be linked to products.";
        }
    }
}

// Get all brands with categories
try {
    $brands = get_all_brands_ctr();
    if (!$brands) $brands = [];

    $categories = get_all_categories_ctr();
    if (!$categories) $categories = [];

    // Calculate brand analytics
    $total_brands = count($brands);
    $total_categories = count($categories);

    // Count brands per category
    $category_brand_count = [];
    foreach ($brands as $brand) {
        if (isset($brand['cat_id'])) {
            $cat_id = $brand['cat_id'];
            $category_brand_count[$cat_id] = ($category_brand_count[$cat_id] ?? 0) + 1;
        }
    }

    // Most popular category (by brand count)
    $popular_category = '';
    $max_brands = 0;
    foreach ($categories as $category) {
        if (isset($category['cat_id']) && isset($category['cat_name'])) {
            $brand_count = $category_brand_count[$category['cat_id']] ?? 0;
            if ($brand_count > $max_brands) {
                $max_brands = $brand_count;
                $popular_category = $category['cat_name'];
            }
        }
    }

    if (empty($popular_category)) {
        $popular_category = 'None';
    }

} catch (Exception $e) {
    $brands = [];
    $categories = [];
    $total_brands = 0;
    $total_categories = 0;
    $category_brand_count = [];
    $popular_category = 'None';
    $error_message = "Unable to load brands: " . $e->getMessage();
}
?>

<?php include 'includes/admin_header.php'; ?>
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Brand Management</h1>
    <p class="page-subtitle">Manage product brands and track brand performance across categories</p>
    <nav class="breadcrumb-custom">
        <span>Home > Brands</span>
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

<!-- Analytics Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.1s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-primary mb-3">
                    <i class="fas fa-tags fa-3x"></i>
                </div>
                <h3 class="counter text-primary" data-target="<?= $total_brands ?>">0</h3>
                <p class="text-muted mb-0">Total Brands</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.2s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-success mb-3">
                    <i class="fas fa-layer-group fa-3x"></i>
                </div>
                <h3 class="counter text-success" data-target="<?= $total_categories ?>">0</h3>
                <p class="text-muted mb-0">Categories Covered</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.3s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-warning mb-3">
                    <i class="fas fa-crown fa-3x"></i>
                </div>
                <h3 class="text-warning brand-name"><?= htmlspecialchars($popular_category) ?></h3>
                <p class="text-muted mb-0">Top Category</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.4s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-info mb-3">
                    <i class="fas fa-chart-bar fa-3x"></i>
                </div>
                <h3 class="counter text-info" data-target="<?= $total_categories > 0 ? round(($total_brands / $total_categories), 1) : 0 ?>">0</h3>
                <p class="text-muted mb-0">Brands per Category</p>
            </div>
        </div>
    </div>
</div>

<!-- Brand Management -->
<div class="row g-4">
    <!-- Add Brand Form -->
    <div class="col-lg-4">
        <div class="admin-card" style="animation-delay: 0.5s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-plus me-2"></i>Add New Brand</h5>
            </div>
            <div class="card-body-custom">
                <form method="POST" class="modern-form">
                    <div class="form-group mb-4">
                        <label for="brand_name" class="form-label-modern">Brand Name</label>
                        <input type="text"
                               class="form-control-modern"
                               id="brand_name"
                               name="brand_name"
                               placeholder="Enter brand name..."
                               required>
                    </div>

                    <div class="form-group mb-4">
                        <label for="cat_id" class="form-label-modern">Category</label>
                        <select class="form-control-modern" id="cat_id" name="cat_id" required>
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['cat_id'] ?>">
                                    <?= htmlspecialchars($category['cat_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="add_brand" class="btn-primary-custom w-100">
                        <i class="fas fa-plus me-2"></i>
                        Add Brand
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Brands List -->
    <div class="col-lg-8">
        <div class="admin-card" style="animation-delay: 0.6s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-list me-2"></i>All Brands</h5>
            </div>
            <div class="card-body-custom p-0">
                <?php if (!empty($brands)): ?>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Brand Name</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($brands as $index => $brand): ?>
                                    <tr style="animation-delay: <?= 0.7 + ($index * 0.1) ?>s;">
                                        <td><strong>#<?= htmlspecialchars($brand['brand_id']) ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="brand-avatar me-3">
                                                    <i class="fas fa-tag"></i>
                                                </div>
                                                <strong><?= htmlspecialchars($brand['brand_name']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="category-badge">
                                                <?= htmlspecialchars($brand['cat_name'] ?? 'No Category') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active">
                                                Active
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary"
                                                        onclick="editBrand(<?= $brand['brand_id'] ?>, '<?= htmlspecialchars($brand['brand_name']) ?>', <?= isset($brand['cat_id']) ? $brand['cat_id'] : 0 ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteBrand(<?= $brand['brand_id'] ?>, '<?= htmlspecialchars($brand['brand_name']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                        <h3>No Brands Found</h3>
                        <p class="text-muted">Start by adding your first product brand.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="brand_id" id="editBrandId">

                    <div class="form-group mb-3">
                        <label for="editBrandName" class="form-label-modern">Brand Name</label>
                        <input type="text" class="form-control-modern" id="editBrandName" name="brand_name" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="editCatId" class="form-label-modern">Category</label>
                        <select class="form-control-modern" id="editCatId" name="cat_id" required>
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['cat_id'] ?>">
                                    <?= htmlspecialchars($category['cat_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_brand" class="btn-primary-custom">
                        <i class="fas fa-save me-2"></i>Update Brand
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Additional styles for brand management */
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
    transform: scale(1.1);
}

.counter {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
}

.brand-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    text-transform: capitalize;
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

.brand-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.category-badge {
    background: #e0f2fe;
    color: #0277bd;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.modern-modal .modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
    backdrop-filter: blur(20px);
}

.modern-modal .modal-header {
    background: var(--gradient-primary);
    color: white;
    border-radius: 20px 20px 0 0;
}

/* Counter Animation */
@keyframes countUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.counter-animate {
    animation: countUp 0.6s ease forwards;
}
</style>

<script>
// Counter animation
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

// Edit brand function
function editBrand(id, name, catId) {
    document.getElementById('editBrandId').value = id;
    document.getElementById('editBrandName').value = name;
    document.getElementById('editCatId').value = catId;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Delete brand function
function deleteBrand(id, name) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Brand',
            text: `Are you sure you want to delete the brand "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performDeleteBrand(id);
            }
        });
    }
}

function performDeleteBrand(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="delete_brand" value="1">
        <input type="hidden" name="brand_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Initialize animations when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Start counter animations
    setTimeout(animateCounters, 500);

    // Add stagger animation to cards
    const cards = document.querySelectorAll('.admin-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?>