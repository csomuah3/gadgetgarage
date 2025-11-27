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

<style>
            border-radius: 12px 12px 0 0 !important;
            padding: 1.5rem;
        }

        .btn {
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-warning {
            background: var(--warning-color);
            border-color: var(--warning-color);
        }

        .btn-danger {
            background: var(--danger-color);
            border-color: var(--danger-color);
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background: var(--dark-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
        }

        .status-active {
            background: var(--success-color);
            color: white;
        }

        .status-inactive {
            background: var(--secondary-color);
            color: white;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px 12px 0 0;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-card .icon {
            font-size: 2rem;
            opacity: 0.8;
        }

        .stats-card .number {
            font-size: 2rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-buttons .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                Gadget Garage Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>
                    Back to Store
                </a>
                <a class="nav-link" href="../login/login.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-percent me-3"></i>
                        Discount Codes Management
                    </h1>
                    <p class="page-subtitle">Create, manage, and track discount codes for your store</p>
                </div>
                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addDiscountModal">
                    <i class="fas fa-plus me-2"></i>
                    Add New Discount
                </button>
            </div>
        </div>

        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?php echo count($all_discounts); ?></div>
                            <div>Total Codes</div>
                        </div>
                        <div class="icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number">
                                <?php
                                $active_count = 0;
                                foreach ($all_discounts as $discount) {
                                    if ($discount['is_active']) $active_count++;
                                }
                                echo $active_count;
                                ?>
                            </div>
                            <div>Active Codes</div>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number">
                                <?php
                                $total_used = 0;
                                foreach ($all_discounts as $discount) {
                                    $total_used += $discount['used_count'];
                                }
                                echo $total_used;
                                ?>
                            </div>
                            <div>Total Used</div>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number">
                                <?php
                                $expired_count = 0;
                                $now = date('Y-m-d H:i:s');
                                foreach ($all_discounts as $discount) {
                                    if ($discount['end_date'] && $discount['end_date'] < $now) $expired_count++;
                                }
                                echo $expired_count;
                                ?>
                            </div>
                            <div>Expired</div>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discount Codes Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    All Discount Codes
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="discountTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min Order</th>
                                <th>Max Discount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_discounts)): ?>
                                <?php foreach ($all_discounts as $discount): ?>
                                    <tr>
                                        <td><?php echo $discount['promo_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($discount['promo_code']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($discount['promo_description']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $discount['discount_type'] === 'percentage' ? 'bg-info' : 'bg-warning'; ?>">
                                                <?php echo ucfirst($discount['discount_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if ($discount['discount_type'] === 'percentage') {
                                                echo $discount['discount_value'] . '%';
                                            } else {
                                                echo 'GH¢' . number_format($discount['discount_value'], 2);
                                            }
                                            ?>
                                        </td>
                                        <td>GH¢<?php echo number_format($discount['min_order_amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            echo $discount['max_discount_amount'] > 0
                                                ? 'GH¢' . number_format($discount['max_discount_amount'], 2)
                                                : 'No Limit';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            echo $discount['start_date']
                                                ? date('M j, Y', strtotime($discount['start_date']))
                                                : 'Not Set';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            echo $discount['end_date']
                                                ? date('M j, Y', strtotime($discount['end_date']))
                                                : 'No Expiry';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $used = $discount['used_count'];
                                            $limit = $discount['usage_limit'];
                                            if ($limit > 0) {
                                                echo "$used / $limit";
                                            } else {
                                                echo "$used / Unlimited";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $discount['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $discount['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-warning btn-sm"
                                                        onclick="editDiscount(<?php echo htmlspecialchars(json_encode($discount)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn <?php echo $discount['is_active'] ? 'btn-secondary' : 'btn-success'; ?> btn-sm"
                                                        onclick="toggleDiscountStatus(<?php echo $discount['promo_id']; ?>, '<?php echo htmlspecialchars($discount['promo_code']); ?>')">
                                                    <i class="fas <?php echo $discount['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="deleteDiscount(<?php echo $discount['promo_id']; ?>, '<?php echo htmlspecialchars($discount['promo_code']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        No discount codes found. Create your first discount code!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Discount Modal -->
    <div class="modal fade" id="addDiscountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Add New Discount Code
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
                        <i class="fas fa-save me-2"></i>
                        Create Discount Code
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
                        <i class="fas fa-edit me-2"></i>
                        Edit Discount Code
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
                        <i class="fas fa-save me-2"></i>
                        Update Discount Code
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            $('#discountTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                columnDefs: [
                    { targets: -1, orderable: false } // Disable ordering on action column
                ]
            });

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
        function editDiscount(discount) {
            console.log('Editing discount:', discount);

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
        async function toggleDiscountStatus(promoId, promoCode) {
            const result = await Swal.fire({
                title: 'Toggle Status',
                text: `Are you sure you want to toggle the status of "${promoCode}"?`,
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
</body>
</html>