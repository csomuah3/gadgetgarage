<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

$page_title = "Support Messages";

// Include controllers
require_once(__DIR__ . '/../controllers/support_controller.php');

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $message_id = intval($_POST['message_id']);
        $new_status = $_POST['status'];
        $response = trim($_POST['response'] ?? '');

        // Update status
        if (update_support_message_status_ctr($message_id, $new_status, null)) {
            // If response is provided, save it as admin response
            if (!empty($response)) {
                $admin_id = $_SESSION['user_id'] ?? null;
                add_admin_response_ctr($message_id, $response, $admin_id);
            }
            $success_message = "Status updated successfully.";
        } else {
            $error_message = "Failed to update status.";
        }
    }

    if (isset($_POST['add_response'])) {
        $message_id = intval($_POST['message_id']);
        $response = trim($_POST['response']);
        $admin_id = $_SESSION['user_id'] ?? null;

        if (add_admin_response_ctr($message_id, $response, $admin_id)) {
            $success_message = "Response added successfully.";
        } else {
            $error_message = "Failed to add response.";
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$limit = $_GET['limit'] ?? 50;

// Get all support messages with enhanced analytics
try {
    // Check if functions exist and are working
    if (function_exists('get_all_support_messages_ctr')) {
        $messages = get_all_support_messages_ctr($status_filter ?: null, $limit);
    } else {
        $messages = [];
        $error_message = "Support controller function not found.";
    }

    if (function_exists('get_support_statistics_ctr')) {
        $stats = get_support_statistics_ctr();
    } else {
        $stats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
        if (empty($error_message)) {
            $error_message = "Support statistics function not found.";
        }
    }

    // Initialize with defaults if functions returned false
    if (!$messages || $messages === false) $messages = [];
    if (!$stats || $stats === false) $stats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];

    // Calculate additional analytics
    $total_messages = $stats['total'] ?? 0;
    $new_messages = $stats['new'] ?? 0;
    $in_progress_messages = $stats['in_progress'] ?? 0;
    $resolved_messages = $stats['resolved'] ?? 0;
    $closed_messages = $stats['closed'] ?? 0;

    // Calculate response rate
    $response_rate = $total_messages > 0 ? round((($resolved_messages + $closed_messages) / $total_messages) * 100) : 0;

    // Calculate urgent messages - with safety checks
    $urgent_count = 0;
    if (is_array($messages)) {
        $urgent_messages = array_filter($messages, function($msg) {
            return isset($msg['priority']) && $msg['priority'] === 'urgent';
        });
        $urgent_count = count($urgent_messages);
    }

    // Today's messages - with safety checks
    $today_count = 0;
    if (is_array($messages)) {
        $today = date('Y-m-d');
        $today_messages = array_filter($messages, function($msg) use ($today) {
            return isset($msg['created_at']) && date('Y-m-d', strtotime($msg['created_at'])) === $today;
        });
        $today_count = count($today_messages);
    }

} catch (Exception $e) {
    $messages = [];
    $stats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
    $total_messages = 0;
    $new_messages = 0;
    $in_progress_messages = 0;
    $resolved_messages = 0;
    $closed_messages = 0;
    $response_rate = 0;
    $urgent_count = 0;
    $today_count = 0;
    $error_message = "Unable to load support messages: " . $e->getMessage();
}
?>

<?php include 'includes/admin_header.php'; ?>
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Support Center</h1>
    <p class="page-subtitle">Manage customer support tickets with advanced analytics and response tracking</p>
    <nav class="breadcrumb-custom">
        <span>Home > Support</span>
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
                    <i class="fas fa-ticket-alt fa-3x"></i>
                </div>
                <h3 class="counter text-primary" data-target="<?= $total_messages ?>">0</h3>
                <p class="text-muted mb-0">Total Tickets</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.2s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-danger mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                </div>
                <h3 class="counter text-danger" data-target="<?= $urgent_count ?>">0</h3>
                <p class="text-muted mb-0">Urgent Tickets</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.3s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-success mb-3">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
                <h3 class="counter text-success" data-target="<?= $response_rate ?>">0</h3>
                <p class="text-muted mb-0">Response Rate (%)</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.4s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-info mb-3">
                    <i class="fas fa-calendar-day fa-3x"></i>
                </div>
                <h3 class="counter text-info" data-target="<?= $today_count ?>">0</h3>
                <p class="text-muted mb-0">Today's Tickets</p>
            </div>
        </div>
    </div>
</div>

<!-- Status Overview Chart -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="admin-card" style="animation-delay: 0.5s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-doughnut me-2"></i>Ticket Status Distribution</h5>
            </div>
            <div class="card-body-custom">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="ticketStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="admin-card" style="animation-delay: 0.6s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-filter me-2"></i>Quick Filters</h5>
            </div>
            <div class="card-body-custom">
                <div class="d-grid gap-2">
                    <select class="form-control-modern" id="statusFilter" onchange="filterByStatus(this.value)">
                        <option value="">All Messages</option>
                        <option value="new" <?= $status_filter === 'new' ? 'selected' : ''; ?>>New Tickets</option>
                        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?= $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>

                    <div class="quick-stats mt-3">
                        <div class="stat-item d-flex justify-content-between">
                            <span class="text-muted">New</span>
                            <span class="badge bg-danger"><?= $new_messages ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between mt-2">
                            <span class="text-muted">In Progress</span>
                            <span class="badge bg-warning"><?= $in_progress_messages ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between mt-2">
                            <span class="text-muted">Resolved</span>
                            <span class="badge bg-success"><?= $resolved_messages ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between mt-2">
                            <span class="text-muted">Closed</span>
                            <span class="badge bg-secondary"><?= $closed_messages ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Support Messages -->
<div class="row g-4">
    <div class="col-12">
        <div class="admin-card" style="animation-delay: 0.7s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-list me-2"></i>Support Tickets</h5>
                <div class="ms-auto">
                    <button class="btn btn-light btn-sm" onclick="refreshMessages()" id="refreshBtn">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body-custom p-0">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h3>No Support Tickets Found</h3>
                        <p class="text-muted">There are no support messages matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="support-messages">
                        <?php foreach ($messages as $index => $message): ?>
                            <div class="support-message-item priority-<?= $message['priority'] ?? 'normal' ?>" style="animation-delay: <?= 0.8 + ($index * 0.1) ?>s;">
                                <div class="message-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="subject-tag subject-<?= $message['subject'] ?? 'general' ?>">
                                                        <?php
                                                        $subject_labels = [
                                                            'order' => 'Order',
                                                            'device_quality' => 'Device Issue',
                                                            'repair' => 'Repair',
                                                            'device_drop' => 'Device Drop',
                                                            'tech_revival' => 'Tech Revival',
                                                            'billing' => 'Billing',
                                                            'account' => 'Account',
                                                            'general' => 'General'
                                                        ];
                                                        echo $subject_labels[$message['subject'] ?? 'general'] ?? ($message['subject'] ?? 'General');
                                                        ?>
                                                    </span>
                                                    <strong><?= htmlspecialchars($message['customer_name'] ?? $message['full_name'] ?? 'Unknown Customer') ?></strong>
                                                </div>
                                                <?php if (!empty($message['customer_email'] ?? $message['email'] ?? '')): ?>
                                                    <small class="text-muted"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($message['customer_email'] ?? $message['email'] ?? '') ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="status-badge status-<?= $message['status'] ?? 'new' ?>">
                                                <?php
                                                $status = $message['status'] ?? 'new';
                                                $status_labels = [
                                                    'reached_out_via_mail' => 'Reached Out via Mail',
                                                    'reached_out_via_call' => 'Reached Out via Call',
                                                    'reached_out_via_text' => 'Reached Out via Text',
                                                    'new' => 'New',
                                                    'in_progress' => 'In Progress',
                                                    'resolved' => 'Resolved',
                                                    'closed' => 'Closed'
                                                ];
                                                echo $status_labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
                                                ?>
                                            </span>
                                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="message-body mt-3">
                                    <p class="message-text"><?= nl2br(htmlspecialchars($message['message'])) ?></p>

                                    <?php if (isset($message['admin_response']) && $message['admin_response']): ?>
                                        <div class="admin-response">
                                            <div class="response-header">
                                                <i class="fas fa-reply me-2"></i>
                                                <strong>Admin Response</strong>
                                            </div>
                                            <div class="response-content">
                                                <?= nl2br(htmlspecialchars($message['admin_response'])) ?>
                                            </div>
                                            <small class="text-muted">
                                                Responded on <?= date('M j, Y g:i A', strtotime($message['response_date'])) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="message-actions mt-3">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#responseModal<?= $message['message_id'] ?>">
                                                <i class="fas fa-reply me-1"></i>Response
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#statusModal<?= $message['message_id'] ?>">
                                                <i class="fas fa-edit me-1"></i>Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Response Modal -->
                            <div class="modal fade" id="responseModal<?= $message['message_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content modern-modal">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-reply me-2"></i>Add Response</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="message_id" value="<?= $message['message_id'] ?>">
                                                <div class="form-group">
                                                    <label for="response" class="form-label-modern">Your Response</label>
                                                    <textarea class="form-control-modern" name="response" rows="5"
                                                              placeholder="Summary of what you told the customer..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="add_response" class="btn-primary-custom">
                                                    <i class="fas fa-paper-plane me-2"></i>Send Response
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Update Modal -->
                            <div class="modal fade" id="statusModal<?= $message['message_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content modern-modal">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="message_id" value="<?= $message['message_id'] ?>">
                                                <div class="form-group mb-3">
                                                    <label for="status" class="form-label-modern">Status</label>
                                                    <select class="form-control-modern" name="status" required>
                                                        <option value="reached_out_via_mail" <?= ($message['status'] ?? '') === 'reached_out_via_mail' ? 'selected' : '' ?>>Reached Out via Mail</option>
                                                        <option value="reached_out_via_call" <?= ($message['status'] ?? '') === 'reached_out_via_call' ? 'selected' : '' ?>>Reached Out via Call</option>
                                                        <option value="reached_out_via_text" <?= ($message['status'] ?? '') === 'reached_out_via_text' ? 'selected' : '' ?>>Reached Out via Text</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="response" class="form-label-modern">Response</label>
                                                    <textarea class="form-control-modern" name="response" rows="4"
                                                              placeholder="Summary of what you told the customer..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_status" class="btn-primary-custom">
                                                    <i class="fas fa-save me-2"></i>Update Status
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles for support messages */
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

.support-messages {
    max-height: 600px;
    overflow-y: auto;
}

.support-message-item {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
}

.support-message-item:last-child {
    border-bottom: none;
}

.support-message-item:hover {
    background: rgba(59, 130, 246, 0.05);
    border-radius: 8px;
    margin: 2px;
    padding: 18px;
}

.priority-urgent {
    border-left: 4px solid #ef4444;
}

.priority-high {
    border-left: 4px solid #f59e0b;
}

.priority-normal {
    border-left: 4px solid #3b82f6;
}

.priority-low {
    border-left: 4px solid #6b7280;
}

.customer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.subject-tag {
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 12px;
    margin-right: 8px;
    font-weight: 600;
    text-transform: uppercase;
}

.subject-order {
    background: #3b82f6;
    color: white;
}

.subject-device_quality {
    background: #ef4444;
    color: white;
}

.subject-repair {
    background: #f59e0b;
    color: white;
}

.subject-device_drop {
    background: #10b981;
    color: white;
}

.subject-tech_revival {
    background: #8b5cf6;
    color: white;
}

.subject-billing {
    background: #06b6d4;
    color: white;
}

.subject-account {
    background: #84cc16;
    color: white;
}

.subject-general {
    background: #6b7280;
    color: white;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-new {
    background: #fee2e2;
    color: #991b1b;
}

.status-in_progress {
    background: #fef3c7;
    color: #92400e;
}

.status-resolved {
    background: #d1fae5;
    color: #065f46;
}

.status-closed {
    background: #f3f4f6;
    color: #374151;
}

.status-reached_out_via_mail {
    background: #dbeafe;
    color: #1e40af;
}

.status-reached_out_via_call {
    background: #dcfce7;
    color: #166534;
}

.status-reached_out_via_text {
    background: #fef3c7;
    color: #92400e;
}

.message-text {
    color: #374151;
    line-height: 1.6;
}

.admin-response {
    background: rgba(59, 130, 246, 0.1);
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    border-left: 3px solid #3b82f6;
}

.response-header {
    color: #3b82f6;
    font-weight: 600;
    margin-bottom: 8px;
}

.response-content {
    color: #374151;
    margin-bottom: 8px;
}

.quick-stats .stat-item {
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.quick-stats .stat-item:last-child {
    border-bottom: none;
}

.chart-container {
    position: relative;
    width: 100%;
    height: 300px;
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
    resize: vertical;
}

.form-control-modern:focus {
    outline: none;
    border-color: var(--electric-blue);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

// Ticket Status Chart
function initTicketStatusChart() {
    const ctx = document.getElementById('ticketStatusChart').getContext('2d');

    const chartData = {
        labels: ['New', 'In Progress', 'Resolved', 'Closed'],
        datasets: [{
            data: [<?= $new_messages ?>, <?= $in_progress_messages ?>, <?= $resolved_messages ?>, <?= $closed_messages ?>],
            backgroundColor: [
                '#ef4444',
                '#f59e0b',
                '#10b981',
                '#6b7280'
            ],
            borderWidth: 0,
            borderRadius: 5,
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 14,
                            weight: '600'
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
}

// Filter functionality
function filterByStatus(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
}

// Initialize animations and charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Start counter animations
    setTimeout(animateCounters, 500);

    // Initialize ticket status chart
    setTimeout(initTicketStatusChart, 800);

    // Add stagger animation to cards
    const cards = document.querySelectorAll('.admin-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Animate support message items
    const items = document.querySelectorAll('.support-message-item');
    items.forEach((item, index) => {
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 1000 + (index * 100));
    });
});

// Manual refresh function
function refreshMessages() {
    const refreshBtn = document.getElementById('refreshBtn');
    const originalContent = refreshBtn.innerHTML;

    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...';
    refreshBtn.disabled = true;

    // Reload the page after a short delay
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Auto-refresh every 60 seconds (increased from 30)
setInterval(function() {
    // Only auto-refresh if no modals are open
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 60000);

// Remove purchase notifications on admin pages
(function() {
    // Remove any existing purchase notifications immediately
    function removePurchaseNotifications() {
        const notifications = document.querySelectorAll('.purchase-notification');
        notifications.forEach(notification => {
            notification.style.display = 'none';
            notification.remove();
        });
        
        // Stop purchase notification intervals if they exist
        if (window.purchaseNotifications) {
            window.purchaseNotifications.pause();
            window.purchaseNotifications.removeAll();
        }
    }
    
    // Remove on page load
    removePurchaseNotifications();
    
    // Remove when modals are shown
    document.addEventListener('show.bs.modal', function() {
        removePurchaseNotifications();
    });
    
    // Remove periodically to catch any that might appear
    setInterval(removePurchaseNotifications, 1000);
    
    // Override the purchase notification function if it exists
    if (window.purchaseNotifications) {
        const originalShow = window.purchaseNotifications.showNextNotification;
        if (originalShow) {
            window.purchaseNotifications.showNextNotification = function() {
                // Do nothing on admin pages
                return;
            };
        }
    }
})();
</script>

<?php include 'includes/admin_footer.php'; ?>