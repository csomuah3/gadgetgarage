<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

$page_title = "Discount Codes Management";

// Include controllers
require_once __DIR__ . '/../controllers/discount_controller.php';

// Get all discount codes
try {
    $all_discounts = get_all_discounts_ctr();
    error_log("Discount codes fetched: " . count($all_discounts));
    // Debug: Print the actual data
    error_log("Discount codes data: " . print_r($all_discounts, true));
} catch (Exception $e) {
    error_log("Error fetching discount codes: " . $e->getMessage());
    $all_discounts = [];
}

// Calculate statistics
$total_codes = count($all_discounts);
$active_codes = 0;
$inactive_codes = 0;
$total_usage = 0;

foreach ($all_discounts as $discount) {
    if ($discount['is_active']) {
        $active_codes++;
    } else {
        $inactive_codes++;
    }
    $total_usage += $discount['times_used'] ?? 0;
}
?>

<?php include 'includes/admin_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Discount Codes Management</h1>
    <p class="page-subtitle">Create, manage, and track discount codes and promotional offers</p>
    <nav class="breadcrumb-custom">
        <span>Home > Discounts</span>
    </nav>
</div>

<!-- Statistics Dashboard -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-primary mb-3">
                    <i class="fas fa-percentage fa-3x"></i>
                </div>
                <h3 class="counter text-primary" data-target="<?= $total_codes ?>">0</h3>
                <p class="text-muted mb-0">Total Codes</p>
                <small class="text-info">
                    <i class="fas fa-tag me-1"></i>
                    Available
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-success mb-3">
                    <i class="fas fa-check-circle fa-3x"></i>
                </div>
                <h3 class="counter text-success" data-target="<?= $active_codes ?>">0</h3>
                <p class="text-muted mb-0">Active Codes</p>
                <small class="text-success">
                    <i class="fas fa-arrow-up me-1"></i>
                    Currently active
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-warning mb-3">
                    <i class="fas fa-pause-circle fa-3x"></i>
                </div>
                <h3 class="counter text-warning" data-target="<?= $inactive_codes ?>">0</h3>
                <p class="text-muted mb-0">Inactive Codes</p>
                <small class="text-warning">
                    <i class="fas fa-minus-circle me-1"></i>
                    Not active
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-info mb-3">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
                <h3 class="counter text-info" data-target="<?= $total_usage ?>">0</h3>
                <p class="text-muted mb-0">Total Usage</p>
                <small class="text-info">
                    <i class="fas fa-users me-1"></i>
                    Times used
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Discount Codes Management -->
<div class="admin-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-percentage me-2"></i>Discount Codes</h5>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addDiscountModal">
            <i class="fas fa-plus me-2"></i>Add New Code
        </button>
    </div>
    <div class="card-body-custom p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0" id="discountTable">
                <thead>
                    <tr>
                        <th>CODE</th>
                        <th>DESCRIPTION</th>
                        <th>TYPE</th>
                        <th>VALUE</th>
                        <th>MIN ORDER</th>
                        <th>USAGE</th>
                        <th>STATUS</th>
                        <th>EXPIRES</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_discounts)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="fas fa-percentage fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Discount Codes Found</h5>
                                    <p class="text-muted">Create your first discount code to get started.</p>
                                    <?php if (ini_get('display_errors')): ?>
                                        <small class="text-info">Debug: Discounts array count = <?= count($all_discounts) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_discounts as $discount): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?= htmlspecialchars($discount['promo_code']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($discount['promo_description'] ?? 'No description') ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= strtoupper($discount['discount_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>
                                        <?php if ($discount['discount_type'] == 'percentage'): ?>
                                            <?= $discount['discount_value'] ?>%
                                        <?php else: ?>
                                            GH₵<?= number_format($discount['discount_value'], 2) ?>
                                        <?php endif; ?>
                                    </strong>
                                </td>
                                <td>GH₵<?= number_format($discount['min_order_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= $discount['times_used'] ?? 0 ?> / <?= $discount['usage_limit'] ?? '∞' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $discount['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $discount['is_active'] ? 'ACTIVE' : 'INACTIVE' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($discount['end_date']): ?>
                                        <?= date('M d, Y', strtotime($discount['end_date'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No expiry</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editDiscount(<?= $discount['promo_id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-<?= $discount['is_active'] ? 'warning' : 'success' ?>"
                                                onclick="toggleDiscountStatus(<?= $discount['promo_id'] ?>)">
                                            <i class="fas fa-<?= $discount['is_active'] ? 'pause' : 'play' ?>"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteDiscount(<?= $discount['promo_id'] ?>, '<?= htmlspecialchars($discount['promo_code']) ?>')">
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

<!-- Add Discount Modal -->
<div class="modal fade" id="addDiscountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Discount Code
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addDiscountForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="promo_code" class="form-label">Promo Code</label>
                                <input type="text" class="form-control" id="promo_code" name="promo_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discount_type" class="form-label">Discount Type</label>
                                <select class="form-control" id="discount_type" name="discount_type" required>
                                    <option value="">Select Type</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="promo_description" class="form-label">Description</label>
                        <textarea class="form-control" id="promo_description" name="promo_description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="discount_value" class="form-label">Discount Value</label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="min_order_amount" class="form-label">Min Order Amount</label>
                                <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_discount_amount" class="form-label">Max Discount Amount</label>
                                <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="usage_limit" class="form-label">Usage Limit</label>
                                <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addDiscountForm" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create Discount Code
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Discount Modal -->
<div class="modal fade" id="editDiscountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Discount Code
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editDiscountForm">
                    <input type="hidden" id="edit_promo_id" name="promo_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_promo_code" class="form-label">Promo Code</label>
                                <input type="text" class="form-control" id="edit_promo_code" name="promo_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_discount_type" class="form-label">Discount Type</label>
                                <select class="form-control" id="edit_discount_type" name="discount_type" required>
                                    <option value="">Select Type</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_promo_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_promo_description" name="promo_description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_discount_value" class="form-label">Discount Value</label>
                                <input type="number" class="form-control" id="edit_discount_value" name="discount_value" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_min_order_amount" class="form-label">Min Order Amount</label>
                                <input type="number" class="form-control" id="edit_min_order_amount" name="min_order_amount" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_max_discount_amount" class="form-label">Max Discount Amount</label>
                                <input type="number" class="form-control" id="edit_max_discount_amount" name="max_discount_amount" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" id="edit_start_date" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" id="edit_end_date" name="end_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_usage_limit" class="form-label">Usage Limit</label>
                                <input type="number" class="form-control" id="edit_usage_limit" name="usage_limit" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editDiscountForm" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Discount Code
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Counter animation function
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

document.addEventListener('DOMContentLoaded', function() {
    // Start counter animations
    animateCounters();

    // Add discount form submission
    document.getElementById('addDiscountForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.querySelector('#addDiscountModal .btn-primary');
        const originalText = submitBtn.innerHTML;

        // Show loading
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('../actions/add_discount_action.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while creating the discount code.'
            });
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    // Edit discount form submission
    document.getElementById('editDiscountForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.querySelector('#editDiscountModal .btn-primary');
        const originalText = submitBtn.innerHTML;

        // Show loading
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('../actions/update_discount_action.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while updating the discount code.'
            });
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
});

// Edit discount function
async function editDiscount(promoId) {
    try {
        const response = await fetch(`../actions/get_discount_action.php?id=${promoId}`);
        const result = await response.json();

        if (result.status === 'success') {
            const discount = result.discount;

            // Populate form fields
            document.getElementById('edit_promo_id').value = discount.promo_id;
            document.getElementById('edit_promo_code').value = discount.promo_code;
            document.getElementById('edit_promo_description').value = discount.promo_description || '';
            document.getElementById('edit_discount_type').value = discount.discount_type;
            document.getElementById('edit_discount_value').value = discount.discount_value;
            document.getElementById('edit_min_order_amount').value = discount.min_order_amount;
            document.getElementById('edit_max_discount_amount').value = discount.max_discount_amount;
            document.getElementById('edit_usage_limit').value = discount.usage_limit;
            document.getElementById('edit_is_active').checked = discount.is_active == 1;

            // Format dates for datetime-local input
            if (discount.start_date) {
                const startDate = new Date(discount.start_date);
                document.getElementById('edit_start_date').value = formatDateForInput(startDate);
            }

            if (discount.end_date) {
                const endDate = new Date(discount.end_date);
                document.getElementById('edit_end_date').value = formatDateForInput(endDate);
            }

            // Show modal
            new bootstrap.Modal(document.getElementById('editDiscountModal')).show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: result.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while fetching discount data.'
        });
    }
}

// Helper function to format date for datetime-local input
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Toggle discount status
async function toggleDiscountStatus(promoId) {
    const result = await Swal.fire({
        title: 'Toggle Status',
        text: 'Are you sure you want to toggle the status of this discount?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, toggle it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('promo_id', promoId);

            const response = await fetch('../actions/toggle_discount_status_action.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while toggling the discount status.'
            });
        }
    }
}

// Delete discount
async function deleteDiscount(promoId, promoCode) {
    const result = await Swal.fire({
        title: 'Delete Discount Code',
        text: `Are you sure you want to delete "${promoCode}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('promo_id', promoId);

            const response = await fetch('../actions/delete_discount_action.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while deleting the discount code.'
            });
        }
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>