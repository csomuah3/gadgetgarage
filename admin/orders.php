<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

// Include controllers
require_once __DIR__ . '/../controllers/order_controller.php';

$page_title = "Manage Orders";

// Handle tracking update
if (isset($_POST['update_tracking'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $updated_by = $_SESSION['user_id'];

    if (update_order_tracking_ctr($order_id, $new_status, $notes, $location, $updated_by)) {
        $success_message = "Order tracking updated successfully!";

        // Send SMS notification if enabled
        try {
            require_once __DIR__ . '/../helpers/sms_helper.php';
            if (defined('SMS_ENABLED') && SMS_ENABLED) {
                send_order_status_update_sms($order_id, $new_status);
            }
        } catch (Exception $sms_error) {
            // Log SMS error but don't fail the update
            error_log('SMS notification error: ' . $sms_error->getMessage());
        }
    } else {
        $error_message = "Failed to update order tracking.";
    }
}

// Get all orders and analytics
try {
    $orders = get_all_orders_ctr();
    if (!$orders) $orders = [];

    // Order analytics
    $total_orders = count($orders);
    $total_revenue = array_sum(array_column($orders, 'total_amount'));

    // Orders by status
    $order_status_counts = [];
    foreach ($orders as $order) {
        $status = $order['order_status'];
        $order_status_counts[$status] = ($order_status_counts[$status] ?? 0) + 1;
    }

    // Recent orders (last 30 days)
    $recent_orders = array_filter($orders, function($order) {
        return strtotime($order['order_date']) >= strtotime('-30 days');
    });

    // Average order value
    $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

    // Monthly revenue trend (last 6 months)
    $monthly_revenue = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-{$i} months"));
        $month_revenue = 0;
        foreach ($orders as $order) {
            if (date('Y-m', strtotime($order['order_date'])) === $month) {
                $month_revenue += $order['total_amount'];
            }
        }
        $monthly_revenue[$month] = $month_revenue;
    }

} catch (Exception $e) {
    $orders = [];
    $total_orders = 0;
    $total_revenue = 0;
    $order_status_counts = [];
    $recent_orders = [];
    $avg_order_value = 0;
    $monthly_revenue = [];
    $error_message = "Unable to load orders: " . $e->getMessage();
}
?>

<?php include 'includes/admin_header.php'; ?>
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Order Management</h1>
    <p class="page-subtitle">Monitor sales performance and manage customer orders</p>
    <nav class="breadcrumb-custom">
        <span>Home > Orders</span>
    </nav>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
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
                    <i class="fas fa-shopping-cart fa-3x"></i>
                </div>
                <h3 class="counter text-primary" data-target="<?= $total_orders ?>">0</h3>
                <p class="text-muted mb-0">Total Orders</p>
                <small class="text-success">
                    <i class="fas fa-arrow-up me-1"></i>
                    <?= count($recent_orders) ?> this month
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
                <h3 class="counter text-success" data-target="<?= round($total_revenue) ?>">0</h3>
                <p class="text-muted mb-0">Total Revenue (GH₵)</p>
                <small class="text-success">
                    <i class="fas fa-chart-line me-1"></i>
                    All time sales
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.3s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-warning mb-3">
                    <i class="fas fa-chart-bar fa-3x"></i>
                </div>
                <h3 class="counter text-warning" data-target="<?= round($avg_order_value) ?>">0</h3>
                <p class="text-muted mb-0">Avg Order Value (GH₵)</p>
                <small class="text-info">
                    <i class="fas fa-calculator me-1"></i>
                    Per transaction
                </small>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.4s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-info mb-3">
                    <i class="fas fa-clock fa-3x"></i>
                </div>
                <h3 class="counter text-info" data-target="<?= $order_status_counts['pending'] ?? 0 ?>">0</h3>
                <p class="text-muted mb-0">Pending Orders</p>
                <small class="text-warning">
                    <i class="fas fa-hourglass-half me-1"></i>
                    Need attention
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Revenue Trend Chart -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-line me-2"></i>Revenue Trend</h5>
            </div>
            <div class="card-body-custom">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Order Status Pie Chart -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-pie me-2"></i>Order Status</h5>
            </div>
            <div class="card-body-custom">
                <canvas id="statusChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="admin-card" style="animation-delay: 0.6s;">
    <div class="card-header-custom">
        <h5><i class="fas fa-list me-2"></i>Recent Orders</h5>
        <div class="ms-auto">
            <button class="btn btn-light btn-sm" onclick="refreshOrders()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>
    <div class="card-body-custom p-0">
        <?php if (!empty($orders)): ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Tracking #</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($orders, 0, 20) as $index => $order): ?>
                            <tr class="order-row" style="animation-delay: <?= 0.7 + ($index * 0.05) ?>s;">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="order-avatar me-3">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <strong>#<?= htmlspecialchars($order['order_id']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?= date('M j, Y', strtotime($order['order_date'])) ?><br>
                                        <small class="text-muted"><?= date('g:i A', strtotime($order['order_date'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <code class="tracking-code"><?= htmlspecialchars($order['tracking_number'] ?? 'N/A') ?></code><br>
                                        <small class="text-muted">
                                            <a href="../track_order.php?order=<?= urlencode($order['tracking_number'] ?? $order['order_id']) ?>"
                                               target="_blank" class="text-decoration-none">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">
                                        <?= htmlspecialchars($order['item_count'] ?? '0') ?> items
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success">GH₵<?= number_format($order['total_amount'] ?? 0, 2) ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                                        <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="updateTracking(<?= $order['order_id'] ?>)"
                                                title="Update Tracking">
                                            <i class="fas fa-truck"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info"
                                                onclick="viewOrder(<?= $order['order_id'] ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($orders) > 20): ?>
                <div class="text-center p-3 border-top">
                    <small class="text-muted">Showing latest 20 orders of <?= count($orders) ?> total</small>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                <h3>No Orders Found</h3>
                <p class="text-muted">When customers place orders, they will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Styles for Orders Page -->
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

.order-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.order-row {
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

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tracking-code {
    background: #f8fafc;
    color: #1f2937;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid #e5e7eb;
}

.status-pending {
    background: linear-gradient(135deg, #fef3c7, #fed7aa);
    color: #92400e;
}

.status-processing {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
}

.status-completed {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
}

.status-cancelled {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
}

.order-row:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: translateX(5px);
}
</style>

<script>
// Enhanced Charts
function initializeCharts() {
    // Revenue Trend Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const monthlyData = <?= json_encode(array_values($monthly_revenue)) ?>;
    const monthlyLabels = <?= json_encode(array_keys($monthly_revenue)) ?>;

    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels.map(month => {
                const date = new Date(month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
            }),
            datasets: [{
                label: 'Revenue (GH₵)',
                data: monthlyData,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#6b7280' }
                },
                y: {
                    grid: { color: 'rgba(59, 130, 246, 0.1)' },
                    ticks: {
                        color: '#6b7280',
                        callback: function(value) {
                            return 'GH₵' + value.toLocaleString();
                        }
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });

    // Order Status Pie Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = <?= json_encode($order_status_counts) ?>;
    const statusLabels = Object.keys(statusData);
    const statusValues = Object.values(statusData);
    const colors = [
        'rgb(59, 130, 246)',   // pending - blue
        'rgb(245, 158, 11)',   // processing - orange
        'rgb(16, 185, 129)',   // completed - green
        'rgb(239, 68, 68)'     // cancelled - red
    ];

    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
            datasets: [{
                data: statusValues,
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

// Order Management Functions
function updateTracking(orderId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Update Order Tracking',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select id="tracking-status" class="form-select">
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="out_for_delivery">Out for Delivery</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location</label>
                        <input type="text" id="tracking-location" class="form-control" placeholder="e.g., Accra Distribution Center">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea id="tracking-notes" class="form-control" rows="3" placeholder="Additional tracking information..."></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Update Tracking',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const status = document.getElementById('tracking-status').value;
                const location = document.getElementById('tracking-location').value;
                const notes = document.getElementById('tracking-notes').value;

                if (!status) {
                    Swal.showValidationMessage('Please select a status');
                    return false;
                }

                return { status, location, notes };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                performTrackingUpdate(orderId, result.value);
            }
        });
    } else {
        const newStatus = prompt('Enter new status (pending/processing/shipped/out_for_delivery/delivered/cancelled):');
        if (newStatus && ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'].includes(newStatus.toLowerCase())) {
            const location = prompt('Enter location (optional):') || '';
            const notes = prompt('Enter notes (optional):') || '';
            performTrackingUpdate(orderId, { status: newStatus.toLowerCase(), location, notes });
        }
    }
}

function performTrackingUpdate(orderId, trackingData) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="update_tracking" value="1">
        <input type="hidden" name="order_id" value="${orderId}">
        <input type="hidden" name="status" value="${trackingData.status}">
        <input type="hidden" name="location" value="${trackingData.location || ''}">
        <input type="hidden" name="notes" value="${trackingData.notes || ''}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function viewOrder(orderId) {
    // Create modal for order details
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Order Details',
            text: `Order #${orderId} details - Advanced order view coming soon!`,
            icon: 'info',
            confirmButtonColor: '#D19C97',
            confirmButtonText: 'OK'
        });
    } else {
        Swal.fire({
            title: 'Order Details',
            text: `Order #${orderId} details - Advanced order view coming soon!`,
            icon: 'info',
            confirmButtonColor: '#007bff',
            confirmButtonText: 'OK'
        });
    }
}

function refreshOrders() {
    window.location.reload();
}

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    setTimeout(initializeCharts, 500);

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
        document.querySelectorAll('.order-row').forEach((row, index) => {
            setTimeout(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateX(0)';
            }, index * 50);
        });
    }, 800);
});
</script>

<?php include 'includes/admin_footer.php'; ?>