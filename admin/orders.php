<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

// Include controllers
require_once __DIR__ . '/../controllers/order_controller.php';

$page_title = "Manage Orders";

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];

    if (update_order_status_ctr($order_id, $new_status)) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Failed to update order status.";
    }
}

// Get all orders
try {
    $orders = get_all_orders_ctr();
    if (!$orders) $orders = [];
} catch (Exception $e) {
    $orders = [];
    $error_message = "Unable to load orders: " . $e->getMessage();
}
?>

<?php include 'includes/admin_header.php'; ?>
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Order Management</h1>
    <p class="page-subtitle">Monitor and manage customer orders</p>
    <nav class="breadcrumb-custom">
        <span>Home > Orders</span>
    </nav>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="admin-card">
    <?php if (!empty($orders)): ?>
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($order['order_id']) ?></strong></td>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                                </div>
                            </td>
                            <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                            <td><?= htmlspecialchars($order['item_count'] ?? '0') ?> items</td>
                            <td><strong>GH₵<?= number_format($order['total_amount'] ?? 0, 2) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                                    <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-primary-custom btn-sm" onclick="updateStatus(<?= $order['order_id'] ?>)">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                    <button class="btn btn-primary-custom btn-sm" onclick="viewOrder(<?= $order['order_id'] ?>)">
                                        <i class="fas fa-eye"></i> View
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
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>No Orders Found</h3>
            <p class="text-muted">When customers place orders, they will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function updateStatus(orderId) {
    const newStatus = prompt('Enter new status (pending, processing, completed, cancelled):');
    if (newStatus && ['pending', 'processing', 'completed', 'cancelled'].includes(newStatus.toLowerCase())) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="order_id" value="${orderId}">
            <input type="hidden" name="status" value="${newStatus.toLowerCase()}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewOrder(orderId) {
    alert('Order details for Order #' + orderId + ' - Feature coming soon!');
}
</script>

<?php include 'includes/admin_footer.php'; ?>