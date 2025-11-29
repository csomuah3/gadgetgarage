<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

require_once __DIR__ . '/../settings/db_class.php';

$page_title = "Refunds & Ratings";

// Connect to database
$db = new db_connection();
$db->db_connect();
$conn = $db->db_conn();

// Helper to safely fetch single value
function rr_safe_value($row, $key, $default = 0) {
    return isset($row[$key]) ? $row[$key] : $default;
}

// ------------------------------
// REFUND ANALYTICS
// ------------------------------
try {
    // Pending refunds (count + amount)
    $pending_refunds_query = "
        SELECT 
            COUNT(*) AS pending_count,
            COALESCE(SUM(refund_amount), 0) AS pending_amount
        FROM refund_requests
        WHERE status = 'pending'
    ";
    $pending_refunds_row = $db->db_fetch_one($pending_refunds_query) ?: [];
    $pending_refunds_count = rr_safe_value($pending_refunds_row, 'pending_count', 0);
    $pending_refunds_amount = rr_safe_value($pending_refunds_row, 'pending_amount', 0.0);

    // Total refunds (all statuses)
    $total_refunds_query = "
        SELECT 
            COUNT(*) AS total_count,
            COALESCE(SUM(refund_amount), 0) AS total_amount
        FROM refund_requests
    ";
    $total_refunds_row = $db->db_fetch_one($total_refunds_query) ?: [];
    $total_refunds_count = rr_safe_value($total_refunds_row, 'total_count', 0);
    $total_refunds_amount = rr_safe_value($total_refunds_row, 'total_amount', 0.0);

    // Refunds by status (for chart)
    $refund_status_query = "
        SELECT 
            status,
            COUNT(*) AS count,
            COALESCE(SUM(refund_amount), 0) AS amount
        FROM refund_requests
        GROUP BY status
    ";
    $refund_status_rows = $db->db_fetch_all($refund_status_query) ?: [];

    // Refunds trend (last 30 days)
    $refund_trend_query = "
        SELECT 
            DATE(request_date) AS date,
            COUNT(*) AS count,
            COALESCE(SUM(refund_amount), 0) AS amount
        FROM refund_requests
        WHERE request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(request_date)
        ORDER BY date ASC
    ";
    $refund_trend_rows = $db->db_fetch_all($refund_trend_query) ?: [];

    // Pending refund list
    $pending_list_query = "
        SELECT 
            refund_id,
            order_id,
            customer_id,
            first_name,
            last_name,
            email,
            phone,
            refund_amount,
            reason_for_refund,
            status,
            request_date
        FROM refund_requests
        WHERE status = 'pending'
        ORDER BY request_date ASC
        LIMIT 50
    ";
    $pending_refunds_list = $db->db_fetch_all($pending_list_query) ?: [];
} catch (Exception $e) {
    $pending_refunds_count = 0;
    $pending_refunds_amount = 0;
    $total_refunds_count = 0;
    $total_refunds_amount = 0;
    $refund_status_rows = [];
    $refund_trend_rows = [];
    $pending_refunds_list = [];
    $refund_error_message = "Unable to load refund analytics: " . $e->getMessage();
}

// ------------------------------
// RATINGS ANALYTICS (product_ratings)
// ------------------------------
try {
    // Overall ratings summary
    $ratings_summary_query = "
        SELECT 
            COALESCE(AVG(rating), 0) AS avg_rating,
            COUNT(*) AS total_ratings
        FROM product_ratings
    ";
    $ratings_summary_row = $db->db_fetch_one($ratings_summary_query) ?: [];
    $avg_rating = round(rr_safe_value($ratings_summary_row, 'avg_rating', 0), 2);
    $total_ratings = rr_safe_value($ratings_summary_row, 'total_ratings', 0);

    // Rating distribution (1-5 stars)
    $rating_distribution_query = "
        SELECT 
            rating,
            COUNT(*) AS count
        FROM product_ratings
        GROUP BY rating
        ORDER BY rating DESC
    ";
    $rating_distribution_rows = $db->db_fetch_all($rating_distribution_query) ?: [];

    // Recent product ratings with product & customer info
    $recent_ratings_query = "
        SELECT 
            pr.rating_id,
            pr.order_id,
            pr.product_id,
            pr.customer_id,
            pr.rating,
            pr.comment,
            pr.product_condition,
            pr.product_price,
            pr.created_at,
            p.product_title,
            c.customer_name
        FROM product_ratings pr
        LEFT JOIN products p ON pr.product_id = p.product_id
        LEFT JOIN customer c ON pr.customer_id = c.customer_id
        ORDER BY pr.created_at DESC
        LIMIT 30
    ";
    $recent_ratings = $db->db_fetch_all($recent_ratings_query) ?: [];
} catch (Exception $e) {
    $avg_rating = 0;
    $total_ratings = 0;
    $rating_distribution_rows = [];
    $recent_ratings = [];
    $ratings_error_message = "Unable to load ratings analytics: " . $e->getMessage();
}

// Prepare data for charts (in PHP arrays)
$refundStatusLabels = [];
$refundStatusCounts = [];
foreach ($refund_status_rows as $row) {
    $refundStatusLabels[] = $row['status'];
    $refundStatusCounts[] = (int)$row['count'];
}

$ratingLabels = [];
$ratingCounts = [];
foreach ($rating_distribution_rows as $row) {
    $ratingLabels[] = (string)$row['rating'] . "★";
    $ratingCounts[] = (int)$row['count'];
}

$refundTrendLabels = [];
$refundTrendCounts = [];
foreach ($refund_trend_rows as $row) {
    $refundTrendLabels[] = $row['date'];
    $refundTrendCounts[] = (int)$row['count'];
}

?>

<?php include 'includes/admin_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Refunds &amp; Ratings</h1>
    <p class="page-subtitle">Monitor refund requests and customer feedback on products</p>
    <nav class="breadcrumb-custom">
        <span>Home &gt; Refunds &amp; Ratings</span>
    </nav>
</div>

<?php if (isset($refund_error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($refund_error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($ratings_error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($ratings_error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Top Analytics Cards -->
<div class="row g-4 mb-4">
    <!-- Total Ratings -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.1s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-warning mb-3">
                    <i class="fas fa-star fa-3x"></i>
                </div>
                <h3 class="counter text-warning" data-target="<?= (int)$total_ratings ?>">0</h3>
                <p class="text-muted mb-0">Total Product Ratings</p>
                <small class="text-warning">
                    <i class="fas fa-star-half-alt me-1"></i>
                    Avg: <?= number_format($avg_rating, 2) ?> / 5
                </small>
            </div>
        </div>
    </div>

    <!-- Pending Refunds -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.2s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-danger mb-3">
                    <i class="fas fa-money-bill-wave fa-3x"></i>
                </div>
                <h3 class="counter text-danger" data-target="<?= (int)$pending_refunds_count ?>">0</h3>
                <p class="text-muted mb-0">Pending Refunds</p>
                <small class="text-danger">
                    <i class="fas fa-coins me-1"></i>
                    GH₵<?= number_format($pending_refunds_amount, 2) ?> pending
                </small>
            </div>
        </div>
    </div>

    <!-- Total Refunds -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.3s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-primary mb-3">
                    <i class="fas fa-undo-alt fa-3x"></i>
                </div>
                <h3 class="counter text-primary" data-target="<?= (int)$total_refunds_count ?>">0</h3>
                <p class="text-muted mb-0">Total Refund Requests</p>
                <small class="text-primary">
                    <i class="fas fa-money-check-alt me-1"></i>
                    GH₵<?= number_format($total_refunds_amount, 2) ?> requested
                </small>
            </div>
        </div>
    </div>

    <!-- Ratings Health -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.4s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-success mb-3">
                    <i class="fas fa-heart fa-3x"></i>
                </div>
                <h3 class="text-success">
                    <?= number_format($avg_rating, 2) ?> / 5
                </h3>
                <p class="text-muted mb-0">Customer Satisfaction</p>
                <small class="text-success">
                    <i class="fas fa-users me-1"></i>
                    Based on <?= (int)$total_ratings ?> ratings
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Rating Distribution -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-bar me-2"></i>Rating Distribution</h5>
            </div>
            <div class="card-body-custom">
                <canvas id="ratingDistributionChart" height="260"></canvas>
            </div>
        </div>
    </div>

    <!-- Refund Status Overview -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-pie me-2"></i>Refund Status Overview</h5>
            </div>
            <div class="card-body-custom">
                <canvas id="refundStatusChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row g-4 mb-4">
    <!-- Pending Refunds Table -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Pending Refund Requests</h5>
            </div>
            <div class="card-body-custom">
                <?php if (empty($pending_refunds_list)): ?>
                    <p class="text-muted mb-0">No pending refund requests at the moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Refund ID</th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount (GH₵)</th>
                                    <th>Requested</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_refunds_list as $refund): ?>
                                    <tr>
                                        <td>#<?= (int)$refund['refund_id'] ?></td>
                                        <td><?= htmlspecialchars($refund['order_id']) ?></td>
                                        <td><?= htmlspecialchars(trim($refund['first_name'] . ' ' . $refund['last_name'])) ?></td>
                                        <td><?= number_format((float)$refund['refund_amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($refund['request_date']) ?></td>
                                        <td>
                                            <span class="status-badge status-pending">Pending</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Ratings -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-star-half-alt me-2"></i>Recent Product Ratings</h5>
            </div>
            <div class="card-body-custom" style="max-height: 460px; overflow-y: auto;">
                <?php if (empty($recent_ratings)): ?>
                    <p class="text-muted mb-0">No ratings have been submitted yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_ratings as $rating): ?>
                        <div class="mb-4 pb-3 border-bottom" style="border-color: #e5f3f0 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?= htmlspecialchars($rating['product_title'] ?? 'Unknown Product') ?></strong>
                                <span class="badge bg-light text-dark">
                                    <?php
                                    $stars = (int)$rating['rating'];
                                    for ($i = 0; $i < 5; $i++) {
                                        if ($i < $stars) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-muted"></i>';
                                        }
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    By <?= htmlspecialchars($rating['customer_name'] ?? 'Customer') ?>
                                    · <?= htmlspecialchars($rating['created_at']) ?>
                                </small>
                                <?php if (!empty($rating['product_condition'])): ?>
                                    <small class="text-muted">
                                        Condition: <?= htmlspecialchars($rating['product_condition']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($rating['comment'])): ?>
                                <p class="mb-0" style="font-size: 0.9rem; color: #4b5563;">
                                    <?= nl2br(htmlspecialchars($rating['comment'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Rating Distribution Chart
        const ratingCtx = document.getElementById('ratingDistributionChart');
        if (ratingCtx && typeof Chart !== 'undefined') {
            const ratingLabels = <?= json_encode($ratingLabels) ?>;
            const ratingCounts = <?= json_encode($ratingCounts) ?>;

            new Chart(ratingCtx, {
                type: 'bar',
                data: {
                    labels: ratingLabels,
                    datasets: [{
                        label: 'Number of Ratings',
                        data: ratingCounts,
                        backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#6366f1'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' ratings';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Refund Status Chart
        const refundStatusCtx = document.getElementById('refundStatusChart');
        if (refundStatusCtx && typeof Chart !== 'undefined') {
            const refundStatusLabels = <?= json_encode($refundStatusLabels) ?>;
            const refundStatusCounts = <?= json_encode($refundStatusCounts) ?>;

            new Chart(refundStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: refundStatusLabels,
                    datasets: [{
                        data: refundStatusCounts,
                        backgroundColor: ['#f59e0b', '#22c55e', '#ef4444', '#3b82f6'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' refunds';
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    });
</script>


