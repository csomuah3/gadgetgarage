<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

$page_title = "Category Management";

// Include controllers
require_once __DIR__ . '/../controllers/category_controller.php';

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $cat_name = trim($_POST['cat_name']);

        if (!empty($cat_name)) {
            if (add_category_ctr($cat_name)) {
                $success_message = "Category added successfully!";
            } else {
                $error_message = "Failed to add category.";
            }
        } else {
            $error_message = "Category name cannot be empty.";
        }
    }

    if (isset($_POST['update_category'])) {
        $cat_id = intval($_POST['cat_id']);
        $cat_name = trim($_POST['cat_name']);

        if (!empty($cat_name)) {
            if (update_category_ctr($cat_id, $cat_name)) {
                $success_message = "Category updated successfully!";
            } else {
                $error_message = "Failed to update category.";
            }
        } else {
            $error_message = "Category name cannot be empty.";
        }
    }

    if (isset($_POST['delete_category'])) {
        $cat_id = intval($_POST['cat_id']);
        if (delete_category_ctr($cat_id)) {
            $success_message = "Category deleted successfully!";
        } else {
            $error_message = "Failed to delete category.";
        }
    }
}

// Get all categories
try {
    $categories = get_all_categories_ctr();
    if (!$categories) $categories = [];

    // Get category analytics
    $category_analytics = [];
    foreach ($categories as $category) {
        $product_count = count_products_by_category_ctr($category['cat_id']);
        $category_analytics[$category['cat_id']] = $product_count;
    }
} catch (Exception $e) {
    $categories = [];
    $category_analytics = [];
    $error_message = "Unable to load categories: " . $e->getMessage();
}
?>

<?php include 'includes/admin_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Category Management</h1>
    <p class="page-subtitle">Organize your product categories and track performance</p>
    <nav class="breadcrumb-custom">
        <span>Home > Categories</span>
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
                <h3 class="counter text-primary" data-target="<?= count($categories) ?>">0</h3>
                <p class="text-muted mb-0">Total Categories</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.2s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-success mb-3">
                    <i class="fas fa-box fa-3x"></i>
                </div>
                <h3 class="counter text-success" data-target="<?= array_sum($category_analytics) ?>">0</h3>
                <p class="text-muted mb-0">Total Products</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.3s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-warning mb-3">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
                <h3 class="counter text-warning" data-target="<?= count(array_filter($category_analytics, function($count) { return $count > 0; })) ?>">0</h3>
                <p class="text-muted mb-0">Active Categories</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.4s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-info mb-3">
                    <i class="fas fa-percentage fa-3x"></i>
                </div>
                <h3 class="counter text-info" data-target="<?= count($category_analytics) > 0 ? round((count(array_filter($category_analytics, function($count) { return $count > 0; })) / count($category_analytics)) * 100) : 0 ?>">0</h3>
                <p class="text-muted mb-0">Utilization %</p>
            </div>
        </div>
    </div>
</div>

<!-- Categories Management -->
<div class="row g-4">
    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="admin-card" style="animation-delay: 0.5s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-plus me-2"></i>Add New Category</h5>
            </div>
            <div class="card-body-custom">
                <form method="POST" class="modern-form">
                    <div class="form-group mb-4">
                        <label for="cat_name" class="form-label-modern">Category Name</label>
                        <input type="text"
                               class="form-control-modern"
                               id="cat_name"
                               name="cat_name"
                               placeholder="Enter category name..."
                               required>
                    </div>

                    <button type="submit" name="add_category" class="btn-primary-custom w-100">
                        <i class="fas fa-plus me-2"></i>
                        Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="admin-card" style="animation-delay: 0.6s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-list me-2"></i>All Categories</h5>
            </div>
            <div class="card-body-custom p-0">
                <?php if (!empty($categories)): ?>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr style="animation-delay: <?= 0.7 + ($index * 0.1) ?>s;">
                                        <td><strong>#<?= htmlspecialchars($category['cat_id']) ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="category-avatar me-3">
                                                    <i class="fas fa-tag"></i>
                                                </div>
                                                <strong><?= htmlspecialchars($category['cat_name']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $category_analytics[$category['cat_id']] ?? 0 ?> products
                                            </span>
                                        </td>
                                        <td>
                                            <?php $product_count = $category_analytics[$category['cat_id']] ?? 0; ?>
                                            <span class="status-badge <?= $product_count > 0 ? 'status-active' : 'status-inactive' ?>">
                                                <?= $product_count > 0 ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary"
                                                        onclick="editCategory(<?= $category['cat_id'] ?>, '<?= htmlspecialchars($category['cat_name']) ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteCategory(<?= $category['cat_id'] ?>, '<?= htmlspecialchars($category['cat_name']) ?>')">
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
                        <h3>No Categories Found</h3>
                        <p class="text-muted">Start by adding your first product category.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="cat_id" id="editCatId">
                    <div class="form-group">
                        <label for="editCatName" class="form-label-modern">Category Name</label>
                        <input type="text" class="form-control-modern" id="editCatName" name="cat_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_category" class="btn-primary-custom">
                        <i class="fas fa-save me-2"></i>Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Additional styles for category management */
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

.category-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
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

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
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

// Edit category function
function editCategory(id, name) {
    document.getElementById('editCatId').value = id;
    document.getElementById('editCatName').value = name;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Delete category function
function deleteCategory(id, name) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Category',
            text: `Are you sure you want to delete the category "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performDeleteCategory(id);
            }
        });
    }
}

function performDeleteCategory(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="delete_category" value="1">
        <input type="hidden" name="cat_id" value="${id}">
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