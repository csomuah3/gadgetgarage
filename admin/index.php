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

    // Total Customers (customer table has no role column)
    $total_customers_query = "SELECT COUNT(*) as total_customers FROM customer";
    $total_customers_result = $db->db_fetch_one($total_customers_query);
    $total_customers = $total_customers_result ? $total_customers_result['total_customers'] : 0;

    // Scheduled Appointments
    $scheduled_appointments_query = "SELECT COUNT(*) as scheduled_appointments FROM repair_appointments WHERE status = 'scheduled'";
    $scheduled_appointments_result = $db->db_fetch_one($scheduled_appointments_query);
    $scheduled_appointments = $scheduled_appointments_result ? $scheduled_appointments_result['scheduled_appointments'] : 0;

    // Calculate appointment growth (this month vs last month)
    $current_month_appointments_query = "SELECT COUNT(*) as current_appointments FROM repair_appointments WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
    $current_month_result = $db->db_fetch_one($current_month_appointments_query);
    $current_month_appointments = $current_month_result ? $current_month_result['current_appointments'] : 0;

    $last_month_appointments_query = "SELECT COUNT(*) as last_appointments FROM repair_appointments WHERE MONTH(created_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(NOW() - INTERVAL 1 MONTH)";
    $last_month_result = $db->db_fetch_one($last_month_appointments_query);
    $last_month_appointments = $last_month_result ? $last_month_result['last_appointments'] : 1;

    $appointment_growth = $last_month_appointments > 0 ? (($current_month_appointments - $last_month_appointments) / $last_month_appointments) * 100 : 0;

    // Additional analytics for bottom cards
    // Total Products
    $total_products_query = "SELECT COUNT(*) as total_products FROM products";
    $total_products_result = $db->db_fetch_one($total_products_query);
    $total_products = $total_products_result ? $total_products_result['total_products'] : 0;

    // Pending Orders
    $pending_orders_query = "SELECT COUNT(*) as pending_orders FROM orders WHERE order_status = 'pending'";
    $pending_orders_result = $db->db_fetch_one($pending_orders_query);
    $pending_orders = $pending_orders_result ? $pending_orders_result['pending_orders'] : 0;

    // Completed Appointments
    $completed_appointments_query = "SELECT COUNT(*) as completed_appointments FROM repair_appointments WHERE status = 'completed'";
    $completed_appointments_result = $db->db_fetch_one($completed_appointments_query);
    $completed_appointments = $completed_appointments_result ? $completed_appointments_result['completed_appointments'] : 0;

    // Total Categories
    $total_categories_query = "SELECT COUNT(*) as total_categories FROM categories";
    $total_categories_result = $db->db_fetch_one($total_categories_query);
    $total_categories = $total_categories_result ? $total_categories_result['total_categories'] : 0;

    // Support Messages
    $support_messages_query = "SELECT COUNT(*) as support_messages FROM support_messages WHERE status = 'open'";
    $support_messages_result = $db->db_fetch_one($support_messages_query);
    $support_messages = $support_messages_result ? $support_messages_result['support_messages'] : 0;

    // Revenue this month
    $current_month_revenue_query = "SELECT COALESCE(SUM(amt), 0) as current_month_revenue FROM payment WHERE MONTH(payment_date) = MONTH(NOW()) AND YEAR(payment_date) = YEAR(NOW())";
    $current_month_revenue_result = $db->db_fetch_one($current_month_revenue_query);
    $current_month_revenue = $current_month_revenue_result ? $current_month_revenue_result['current_month_revenue'] : 0;

    // Calendar appointments for upcoming week
    $calendar_appointments_query = "
        SELECT
            ra.appointment_date,
            ra.appointment_time,
            ra.issue_description,
            c.customer_name,
            COALESCE(s.specialist_name, 'Unassigned') as specialist_name
        FROM repair_appointments ra
        LEFT JOIN customer c ON ra.customer_id = c.customer_id
        LEFT JOIN specialists s ON ra.specialist_id = s.specialist_id
        WHERE ra.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND ra.status IN ('scheduled', 'confirmed')
        ORDER BY ra.appointment_date ASC, ra.appointment_time ASC
    ";
    $calendar_appointments = $db->db_fetch_all($calendar_appointments_query);
    if (!$calendar_appointments) $calendar_appointments = [];

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

    // Top selling categories - check if data exists first
    $categories_count_query = "SELECT COUNT(*) as count FROM categories";
    $categories_count_result = $db->db_fetch_one($categories_count_query);
    $has_categories = $categories_count_result && $categories_count_result['count'] > 0;

    $products_count_query = "SELECT COUNT(*) as count FROM products";
    $products_count_result = $db->db_fetch_one($products_count_query);
    $has_products = $products_count_result && $products_count_result['count'] > 0;

    if ($has_categories && $has_products) {
        $top_categories_query = "
            SELECT
                c.cat_name,
                SUM(od.qty) as total_sold,
                SUM(od.qty * p.product_price) as revenue
            FROM orderdetails od
            JOIN products p ON od.product_id = p.product_id
            JOIN categories c ON p.product_cat = c.cat_id
            GROUP BY c.cat_id
            ORDER BY revenue DESC
            LIMIT 5
        ";
        $top_categories = $db->db_fetch_all($top_categories_query);
    } else {
        // Show placeholder data when no products/categories exist
        $top_categories = [
            ['cat_name' => 'No categories yet', 'total_sold' => 0, 'revenue' => 0],
            ['cat_name' => 'Add products to see data', 'total_sold' => 0, 'revenue' => 0]
        ];
    }

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
    $scheduled_appointments = 0;
    $appointment_growth = 0;
    $recent_orders = 0;
    $sales_growth = 0;
    $total_products = 0;
    $pending_orders = 0;
    $completed_appointments = 0;
    $total_categories = 0;
    $support_messages = 0;
    $current_month_revenue = 0;
    $calendar_appointments = [];
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
                    <h6 class="text-muted mb-2">Scheduled Appointments</h6>
                    <h3 class="mb-0 text-warning"><?php echo number_format($scheduled_appointments); ?></h3>
                    <small class="text-<?php echo $appointment_growth >= 0 ? 'success' : 'danger'; ?>">
                        <i class="fas fa-arrow-<?php echo $appointment_growth >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo $appointment_growth >= 0 ? '+' : ''; ?><?php echo number_format($appointment_growth, 2); ?>% vs last month
                    </small>
                </div>
                <div class="text-warning">
                    <i class="fas fa-calendar-check fa-2x"></i>
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

    <!-- Appointments Calendar -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments</h5>
            </div>
            <div class="card-body-custom">
                <div class="calendar-header mb-3">
                    <h6 class="text-center mb-3" id="currentWeek"></h6>
                    <div class="row text-center calendar-days">
                        <div class="col calendar-day" data-day="0"><small class="text-muted">Mon</small></div>
                        <div class="col calendar-day" data-day="1"><small class="text-muted">Tue</small></div>
                        <div class="col calendar-day" data-day="2"><small class="text-muted">Wed</small></div>
                        <div class="col calendar-day" data-day="3"><small class="text-muted">Thu</small></div>
                        <div class="col calendar-day" data-day="4"><small class="text-muted">Fri</small></div>
                        <div class="col calendar-day" data-day="5"><small class="text-muted">Sat</small></div>
                        <div class="col calendar-day" data-day="6"><small class="text-muted">Sun</small></div>
                    </div>
                </div>

                <div class="appointments-list" style="max-height: 300px; overflow-y: auto;">
                    <?php if (!empty($calendar_appointments)): ?>
                        <?php
                        $current_date = '';
                        foreach ($calendar_appointments as $index => $appointment):
                            $appointment_date = date('Y-m-d', strtotime($appointment['appointment_date']));
                            if ($appointment_date != $current_date):
                                $current_date = $appointment_date;
                        ?>
                            <div class="date-separator">
                                <small class="text-muted fw-bold">
                                    <?= date('M j, Y', strtotime($appointment['appointment_date'])) ?>
                                </small>
                            </div>
                        <?php endif; ?>

                        <div class="appointment-item" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="d-flex align-items-center">
                                <div class="time-badge me-3">
                                    <?= date('g:i A', strtotime($appointment['appointment_time'])) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="appointment-client fw-bold">
                                        <?= htmlspecialchars($appointment['customer_name']) ?>
                                    </div>
                                    <div class="appointment-specialist text-muted small">
                                        with <?= htmlspecialchars($appointment['specialist_name']) ?>
                                    </div>
                                    <div class="appointment-issue text-muted small">
                                        <?= htmlspecialchars(substr($appointment['issue_description'], 0, 40)) ?>...
                                    </div>
                                </div>
                                <div class="appointment-status">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No appointments scheduled</p>
                            <small class="text-muted">for the next 7 days</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="calendar-footer mt-3 pt-3 border-top">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">This Week</small>
                            <strong class="text-primary"><?= count($calendar_appointments) ?> appointments</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Specialists</small>
                            <strong class="text-success">
                                <?= count(array_unique(array_column($calendar_appointments, 'specialist_name'))) ?> active
                            </strong>
                        </div>
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

<!-- Additional Analytics Cards -->
<div class="row g-4 mt-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-bar me-2"></i>Additional Analytics</h5>
            </div>
            <div class="card-body-custom">
                <div class="row g-4">
                    <!-- Total Products -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-primary mb-2">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                            <h4 class="text-primary mb-1"><?php echo number_format($total_products); ?></h4>
                            <small class="text-muted">Total Products</small>
                        </div>
                    </div>

                    <!-- Pending Orders -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-warning mb-2">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h4 class="text-warning mb-1"><?php echo number_format($pending_orders); ?></h4>
                            <small class="text-muted">Pending Orders</small>
                        </div>
                    </div>

                    <!-- Completed Repairs -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-success mb-2">
                                <i class="fas fa-tools fa-2x"></i>
                            </div>
                            <h4 class="text-success mb-1"><?php echo number_format($completed_appointments); ?></h4>
                            <small class="text-muted">Completed Repairs</small>
                        </div>
                    </div>

                    <!-- Total Categories -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-info mb-2">
                                <i class="fas fa-tags fa-2x"></i>
                            </div>
                            <h4 class="text-info mb-1"><?php echo number_format($total_categories); ?></h4>
                            <small class="text-muted">Categories</small>
                        </div>
                    </div>

                    <!-- Support Messages -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-danger mb-2">
                                <i class="fas fa-headset fa-2x"></i>
                            </div>
                            <h4 class="text-danger mb-1"><?php echo number_format($support_messages); ?></h4>
                            <small class="text-muted">Open Tickets</small>
                        </div>
                    </div>

                    <!-- Monthly Revenue -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-success mb-2">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                            <h4 class="text-success mb-1">GH₵<?php echo number_format($current_month_revenue, 0); ?></h4>
                            <small class="text-muted">This Month</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar and Animation Styles -->
<style>
/* Calendar Styles */
.calendar-days {
    margin-bottom: 1rem;
}

.calendar-day {
    padding: 0.5rem 0.25rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.calendar-day:hover {
    background: rgba(59, 130, 246, 0.1);
}

.calendar-day.today {
    background: var(--gradient-primary);
    color: white;
}

.calendar-day.has-appointments::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    background: var(--accent-orange);
    border-radius: 50%;
}

.appointment-item {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-left: 3px solid var(--electric-blue);
    transition: all 0.3s ease;
    animation: slideInRight 0.6s ease forwards;
    opacity: 0;
    transform: translateX(20px);
}

.appointment-item:hover {
    background: rgba(255, 255, 255, 1);
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

@keyframes slideInRight {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.time-badge {
    background: var(--gradient-accent);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 60px;
    text-align: center;
}

.appointment-client {
    font-size: 0.9rem;
    color: var(--primary-navy);
}

.appointment-specialist {
    font-size: 0.8rem;
}

.appointment-issue {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.date-separator {
    text-align: center;
    margin: 1rem 0 0.5rem;
    position: relative;
}

.date-separator::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
    z-index: 0;
}

.date-separator small {
    background: white;
    padding: 0 1rem;
    position: relative;
    z-index: 1;
}

/* Animated Charts */
.chart-container {
    position: relative;
    animation: chartSlideIn 0.8s ease forwards;
    opacity: 0;
}

@keyframes chartSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Counter animations for stats */
.stats-counter {
    transition: all 0.3s ease;
}

.stats-counter:hover {
    transform: scale(1.05);
}

/* Pulse animation for important metrics */
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
    100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}

.pulse-animation {
    animation: pulse 2s infinite;
}
</style>

<script>
// Calendar initialization and animations
function initializeCalendar() {
    const now = new Date();
    const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay() + 1));
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    // Set current week display
    const currentWeekEl = document.getElementById('currentWeek');
    if (currentWeekEl) {
        currentWeekEl.textContent = `${startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
    }

    // Highlight today and days with appointments
    const calendarDays = document.querySelectorAll('.calendar-day');
    const today = new Date();

    calendarDays.forEach((day, index) => {
        const dayDate = new Date(startOfWeek);
        dayDate.setDate(startOfWeek.getDate() + index);

        // Add day number
        const dayNumber = document.createElement('div');
        dayNumber.className = 'fw-bold';
        dayNumber.textContent = dayDate.getDate();
        day.appendChild(dayNumber);

        // Highlight today
        if (dayDate.toDateString() === today.toDateString()) {
            day.classList.add('today');
        }

        // Check for appointments
        const hasAppointments = <?= json_encode(array_map(function($apt) { return date('Y-m-d', strtotime($apt['appointment_date'])); }, $calendar_appointments)) ?>;
        const dayStr = dayDate.toISOString().split('T')[0];
        if (hasAppointments.includes(dayStr)) {
            day.classList.add('has-appointments');
        }
    });

    // Animate appointment items
    setTimeout(() => {
        document.querySelectorAll('.appointment-item').forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, index * 100);
        });
    }, 500);
}

// Counter animation for stats
function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-counter'));
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

// Initialize everything when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar
    initializeCalendar();

    // Start counter animations
    setTimeout(animateCounters, 300);

    // Add chart animation
    const chartContainer = document.querySelector('#revenueChart').parentElement;
    if (chartContainer) {
        chartContainer.classList.add('chart-container');
    }

    // Add pulse animation to important metrics
    setTimeout(() => {
        const salesCard = document.querySelector('.admin-card');
        if (salesCard) {
            salesCard.classList.add('pulse-animation');
        }
    }, 2000);
});
</script>

<?php include 'includes/admin_footer.php'; ?>