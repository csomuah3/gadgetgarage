<?php
session_start();
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../settings/db_class.php');

$page_title = "Admin Dashboard";

// Get database connection
$db = new db_connection();
$db->db_connect();

// Analytics queries
try {
    // Total Sales (sum of all payment amounts)
    $total_sales_query = "SELECT COALESCE(SUM(amt), 0) as total_sales FROM payment";
    $total_sales_result = $db->db_fetch_one($total_sales_query);
    $total_sales = $total_sales_result ? $total_sales_result['total_sales'] : 0;

    // Total Orders
    $total_orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $total_orders_result = $db->db_fetch_one($total_orders_query);
    $total_orders = $total_orders_result ? $total_orders_result['total_orders'] : 0;

    // Total Customers
    $total_customers_query = "SELECT COUNT(*) as total_customers FROM customer WHERE role = 1";
    $total_customers_result = $db->db_fetch_one($total_customers_query);
    $total_customers = $total_customers_result ? $total_customers_result['total_customers'] : 0;

    // Recent Orders (last 7 days)
    $recent_orders_query = "SELECT COUNT(*) as recent_orders FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $recent_orders_result = $db->db_fetch_one($recent_orders_query);
    $recent_orders = $recent_orders_result ? $recent_orders_result['recent_orders'] : 0;

    // Revenue last 30 days for chart
    $revenue_chart_query = "
        SELECT
            DATE(payment_date) as date,
            SUM(amt) as revenue
        FROM payment
        WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(payment_date)
        ORDER BY date ASC
    ";
    $revenue_chart_data = $db->db_fetch_all($revenue_chart_query);

    // Top selling categories
    $top_categories_query = "
        SELECT
            c.cat_name,
            SUM(od.qty) as total_sold,
            SUM(od.qty * p.product_price) as revenue
        FROM order_details od
        JOIN products p ON od.product_id = p.product_id
        JOIN categories c ON p.product_cat = c.cat_id
        GROUP BY c.cat_id
        ORDER BY revenue DESC
        LIMIT 5
    ";
    $top_categories = $db->db_fetch_all($top_categories_query);

    // Order status breakdown
    $order_status_query = "
        SELECT
            order_status,
            COUNT(*) as count
        FROM orders
        GROUP BY order_status
    ";
    $order_status_data = $db->db_fetch_all($order_status_query);

    // Calculate growth percentages (comparing to previous period)
    $prev_sales_query = "SELECT COALESCE(SUM(amt), 0) as prev_sales FROM payment WHERE payment_date < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $prev_sales_result = $db->db_fetch_one($prev_sales_query);
    $prev_sales = $prev_sales_result ? $prev_sales_result['prev_sales'] : 1;
    $sales_growth = $prev_sales > 0 ? (($total_sales - $prev_sales) / $prev_sales) * 100 : 0;

} catch (Exception $e) {
    $total_sales = 0;
    $total_orders = 0;
    $total_customers = 0;
    $recent_orders = 0;
    $sales_growth = 0;
    $revenue_chart_data = [];
    $top_categories = [];
    $order_status_data = [];
}
?>

<?php include 'includes/admin_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Analytics Overview</p>
    <nav class="breadcrumb-custom">
        <span>Home > Dashboard</span>
    </nav>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Total Sales</h6>
                    <h3 class="mb-0 text-success">GH₵ <?php echo number_format($total_sales, 0); ?></h3>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo number_format($sales_growth, 1); ?>% vs last month
                    </small>
                </div>
                <div class="text-success">
                    <i class="fas fa-dollar-sign fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Total Orders</h6>
                    <h3 class="mb-0 text-primary"><?php echo number_format($total_orders); ?></h3>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i>
                        <?php echo $recent_orders; ?> this week
                    </small>
                </div>
                <div class="text-primary">
                    <i class="fas fa-shopping-cart fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Total Customers</h6>
                    <h3 class="mb-0 text-info"><?php echo number_format($total_customers); ?></h3>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i>
                        +8.02% vs last month
                    </small>
                </div>
                <div class="text-info">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card">
            <div class="card-body-custom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Active Users</h6>
                    <h3 class="mb-0 text-warning"><?php echo number_format($total_customers * 0.4); ?></h3>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i>
                        +6.02% vs last month
                    </small>
                </div>
                <div class="text-warning">
                    <i class="fas fa-eye fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-chart-line me-2"></i>Revenue Analytics</h5>
                <span class="badge bg-light text-dark">Last 30 Days</span>
            </div>
            <div class="card-body-custom">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Target -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-bullseye me-2"></i>Monthly Target</h5>
            </div>
            <div class="card-body-custom text-center">
                <div class="mb-4">
                    <div style="position: relative; width: 200px; height: 200px; margin: 0 auto;">
                        <svg width="200" height="200" style="transform: rotate(-90deg);">
                            <circle cx="100" cy="100" r="80" fill="none" stroke="#e5f3f0" stroke-width="12"></circle>
                            <circle cx="100" cy="100" r="80" fill="none" stroke="#008060" stroke-width="12"
                                stroke-dasharray="<?php echo 2 * 3.14159 * 80; ?>"
                                stroke-dashoffset="<?php echo 2 * 3.14159 * 80 * (1 - 0.85); ?>"
                                stroke-linecap="round"></circle>
                        </svg>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <h2 class="text-success mb-0">85%</h2>
                            <small class="text-muted">Complete</small>
                        </div>
                    </div>
                </div>
                <div class="text-success mb-3">
                    <strong>Great Progress! 🎉</strong>
                </div>
                <p class="text-muted small mb-3">
                    Our achievement increased by GH₵ <?php echo number_format($total_sales * 0.1); ?>;
                    let's reach 100% next month.
                </p>
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted d-block">Target</small>
                        <strong>GH₵ <?php echo number_format($total_sales * 1.2); ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Revenue</small>
                        <strong>GH₵ <?php echo number_format($total_sales); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="row g-4">
    <!-- Top Categories -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-chart-pie me-2"></i>Top Categories</h5>
                <a href="category.php" class="btn btn-sm btn-light">See All</a>
            </div>
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Items Sold</th>
                                <th>Revenue</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_revenue = array_sum(array_column($top_categories, 'revenue'));
                            $colors = ['#008060', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];
                            foreach (array_slice($top_categories, 0, 5) as $index => $category):
                                $percentage = $total_revenue > 0 ? ($category['revenue'] / $total_revenue) * 100 : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div style="width: 12px; height: 12px; background: <?php echo $colors[$index]; ?>; border-radius: 50%; margin-right: 12px;"></div>
                                        <?php echo htmlspecialchars($category['cat_name'] ?? 'Electronics'); ?>
                                    </div>
                                </td>
                                <td><?php echo number_format($category['total_sold'] ?? 0); ?></td>
                                <td><strong>GH₵ <?php echo number_format($category['revenue'] ?? 0); ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div style="width: 100px; height: 8px; background: #e5f3f0; border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?php echo $percentage; ?>%; height: 100%; background: <?php echo $colors[$index]; ?>; border-radius: 4px;"></div>
                                        </div>
                                        <span class="ms-2 small"><?php echo number_format($percentage, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list-alt me-2"></i>Order Status</h5>
                <span class="badge bg-light text-dark">This Week</span>
            </div>
            <div class="card-body-custom">
                <?php foreach ($order_status_data as $status): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="status-badge status-<?php echo strtolower($status['order_status']); ?>">
                            <?php echo ucfirst($status['order_status']); ?>
                        </span>
                        <div class="text-muted small mt-1"><?php echo $status['count']; ?> orders</div>
                    </div>
                    <h5 class="mb-0"><?php echo $status['count']; ?></h5>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mt-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-lightning-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body-custom">
                <div class="row g-3">
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="product.php" class="btn btn-primary-custom w-100">
                            <i class="fas fa-plus-circle me-2"></i>
                            Add Product
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="orders.php" class="btn btn-primary-custom w-100">
                            <i class="fas fa-eye me-2"></i>
                            View Orders
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="category.php" class="btn btn-primary-custom w-100">
                            <i class="fas fa-tags me-2"></i>
                            Categories
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="brand.php" class="btn btn-primary-custom w-100">
                            <i class="fas fa-trademark me-2"></i>
                            Brands
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="support_messages.php" class="btn btn-primary-custom w-100">
                            <i class="fas fa-headset me-2"></i>
                            Support
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="appointments.php" class="btn btn-primary-custom w-100">
                            <i class="fas fa-calendar me-2"></i>
                            Appointments
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');

// Prepare chart data from PHP
const chartLabels = [
    <?php
    if (!empty($revenue_chart_data)) {
        foreach ($revenue_chart_data as $data) {
            echo "'" . date('M d', strtotime($data['date'])) . "',";
        }
    } else {
        // Default labels for demo
        for ($i = 29; $i >= 0; $i--) {
            echo "'" . date('M d', strtotime("-$i days")) . "',";
        }
    }
    ?>
];

const revenueData = [
    <?php
    if (!empty($revenue_chart_data)) {
        foreach ($revenue_chart_data as $data) {
            echo $data['revenue'] . ",";
        }
    } else {
        // Default data for demo
        echo "8000, 12000, 9000, 15000, 11000, 13000, 16000, 14000, 18000, 12000, 15000, 17000, 13000, 19000, 16000, 14000, 12000, 18000, 15000, 13000, 17000, 19000, 16000, 14000, 18000, 15000, 17000, 19000, 18000, 20000";
    }
    ?>
];

new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Revenue',
                data: revenueData,
                borderColor: '#008060',
                backgroundColor: 'rgba(0, 128, 96, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#008060',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                display: true,
                grid: {
                    display: false
                }
            },
            y: {
                display: true,
                grid: {
                    color: '#f0fdf9'
                },
                ticks: {
                    callback: function(value) {
                        return 'GH₵' + value.toLocaleString();
                    }
                }
            }
        },
        elements: {
            point: {
                hoverRadius: 8
            }
        }
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>