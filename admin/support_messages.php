<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';

// Check admin access for proper error handling
if (!check_admin()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // If it's an AJAX/form request, return JSON error
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Access denied. Admin privileges required.'
        ]);
        exit();
    } else {
        // If it's a page request, redirect to login
        redirect('/login/login.php');
    }
}

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

        // Validate response requirement for "customer_needs_to_come_in" status
        if ($new_status === 'customer_needs_to_come_in' && empty($response)) {
            $error_message = "Response is required when customer needs to come in.";
        } else {
            // Update status
            if (update_support_message_status_ctr($message_id, $new_status, null)) {
                // If response is provided, save it as admin response
                if (!empty($response)) {
                    $admin_id = $_SESSION['user_id'] ?? null;
                    add_admin_response_ctr($message_id, $response, $admin_id);
                }
                // If status is "spoke_to_ai", auto-add a default response
                if ($new_status === 'spoke_to_ai' && empty($response)) {
                    $admin_id = $_SESSION['user_id'] ?? null;
                    add_admin_response_ctr($message_id, 'Spoke to AI - Issue handled automatically.', $admin_id);
                }
                $success_message = "Status updated successfully.";
                // Redirect to prevent form resubmission and clear modal state
                header("Location: " . $_SERVER['REQUEST_URI'] . "?success=1");
                exit();
            } else {
                $error_message = "Failed to update status.";
            }
        }
    }

    if (isset($_POST['add_response'])) {
        $message_id = intval($_POST['message_id']);
        $response = trim($_POST['response']);
        $admin_id = $_SESSION['user_id'] ?? null;

        if (add_admin_response_ctr($message_id, $response, $admin_id)) {
            $success_message = "Response added successfully.";
            // Redirect to prevent form resubmission and clear modal state
            header("Location: " . $_SERVER['REQUEST_URI'] . "?success=2");
            exit();
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
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Support Tickets</h5>
                <button class="btn btn-light btn-sm" onclick="refreshMessages()" id="refreshBtn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
            <div class="card-body-custom">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5">
                        <div class="glass-card-empty">
                            <i class="fas fa-inbox fa-4x mb-3" style="color: rgba(100, 116, 139, 0.5);"></i>
                            <h3 style="color: var(--primary-navy);">No Support Tickets Found</h3>
                            <p style="color: #64748b;">There are no support messages matching your criteria.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="support-messages-grid">
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
                        $status_labels = [
                            'spoke_to_ai' => 'Spoke to AI',
                            'reached_out_via_mail' => 'Reached Out via Mail',
                            'reached_out_via_call' => 'Reached Out via Call',
                            'reached_out_via_text' => 'Reached Out via Text',
                            'customer_needs_to_come_in' => 'Customer Needs to Come In',
                            'new' => 'New',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Resolved',
                            'closed' => 'Closed'
                        ];
                        foreach ($messages as $index => $message): 
                            $status = $message['status'] ?? 'new';
                            $current_status = ($status === 'new' || empty($status)) ? 'spoke_to_ai' : $status;
                        ?>
                            <div class="glass-message-card" style="animation-delay: <?= 0.8 + ($index * 0.1) ?>s;">
                                <div class="message-card-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="customer-avatar-glass">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="subject-badge subject-<?= $message['subject'] ?? 'general' ?>">
                                                    <?= $subject_labels[$message['subject'] ?? 'general'] ?? 'General' ?>
                                                </span>
                                                <strong class="customer-name"><?= htmlspecialchars($message['customer_name'] ?? $message['full_name'] ?? 'Unknown Customer') ?></strong>
                                            </div>
                                            <?php if (!empty($message['customer_email'] ?? $message['email'] ?? '')): ?>
                                                <small class="customer-email">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?= htmlspecialchars($message['customer_email'] ?? $message['email'] ?? '') ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-meta">
                                            <span class="status-badge-glass status-<?= $status ?>">
                                                <?= $status_labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) ?>
                                            </span>
                                            <small class="message-date"><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="message-card-body">
                                    <div class="message-content">
                                        <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                    </div>

                                    <?php if (isset($message['admin_response']) && $message['admin_response']): ?>
                                        <div class="admin-response-glass">
                                            <div class="response-header-glass">
                                                <i class="fas fa-reply me-2"></i>
                                                <strong>Admin Response</strong>
                                            </div>
                                            <div class="response-content-glass">
                                                <?= nl2br(htmlspecialchars($message['admin_response'])) ?>
                                            </div>
                                            <small class="response-date">
                                                <?= date('M j, Y g:i A', strtotime($message['response_date'] ?? $message['updated_at'] ?? 'now')) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="message-actions-glass">
                                        <button class="btn-update-glass" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateModal<?= $message['message_id'] ?>">
                                            <i class="fas fa-edit me-2"></i>Update
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Modal -->
                            <div class="modal fade" id="updateModal<?= $message['message_id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content glass-modal">
                                        <div class="modal-header glass-modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-edit me-2"></i>Update Support Ticket
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" id="updateForm<?= $message['message_id'] ?>" onsubmit="return handleFormSubmit(this)">
                                            <div class="modal-body glass-modal-body">
                                                <input type="hidden" name="message_id" value="<?= $message['message_id'] ?>">
                                                
                                                <div class="form-group-glass mb-4">
                                                    <label for="updateStatus<?= $message['message_id'] ?>" class="form-label-glass">
                                                        <i class="fas fa-tag me-2"></i>Update
                                                    </label>
                                                    <select class="form-control-glass" 
                                                            name="status" 
                                                            id="updateStatus<?= $message['message_id'] ?>" 
                                                            required 
                                                            onchange="handleStatusChange(<?= $message['message_id'] ?>)">
                                                        <option value="spoke_to_ai" <?= $current_status === 'spoke_to_ai' ? 'selected' : '' ?>>Spoke to AI</option>
                                                        <option value="reached_out_via_mail" <?= $current_status === 'reached_out_via_mail' ? 'selected' : '' ?>>Reached Out via Mail</option>
                                                        <option value="reached_out_via_call" <?= $current_status === 'reached_out_via_call' ? 'selected' : '' ?>>Reached Out via Call</option>
                                                        <option value="reached_out_via_text" <?= $current_status === 'reached_out_via_text' ? 'selected' : '' ?>>Reached Out via Text</option>
                                                        <option value="customer_needs_to_come_in" <?= $current_status === 'customer_needs_to_come_in' ? 'selected' : '' ?>>Customer Needs to Come In</option>
                                                        <option value="resolved" <?= $current_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                        <option value="closed" <?= $current_status === 'closed' ? 'selected' : '' ?>>Closed</option>
                                                    </select>
                                                </div>

                                                <div class="form-group-glass" id="responseGroup<?= $message['message_id'] ?>" style="display: <?= $current_status === 'customer_needs_to_come_in' ? 'block' : 'none'; ?>;">
                                                    <label for="updateResponse<?= $message['message_id'] ?>" class="form-label-glass">
                                                        <i class="fas fa-comment me-2"></i>Response 
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <textarea class="form-control-glass" 
                                                              name="response" 
                                                              id="updateResponse<?= $message['message_id'] ?>" 
                                                              rows="5"
                                                              placeholder="Enter details about what the customer needs to know if they have to come in..."
                                                              <?= $current_status === 'customer_needs_to_come_in' ? 'required' : '' ?>></textarea>
                                                    <small class="form-help-text">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        This response will be sent to the customer.
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="modal-footer glass-modal-footer">
                                                <button type="button" class="btn-cancel-glass" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-1"></i>Cancel
                                                </button>
                                                <button type="submit" name="update_status" class="btn-submit-glass">
                                                    <i class="fas fa-save me-1"></i>Update Status
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
/* Analytics Cards */
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

/* Glassmorphic Support Messages */
.support-messages-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    max-height: 70vh;
    overflow-y: auto;
    padding: 0.5rem;
}

.glass-message-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
    position: relative;
    overflow: hidden;
}

.glass-message-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.glass-message-card:hover::before {
    left: 100%;
}

.glass-message-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    border-color: rgba(59, 130, 246, 0.3);
}

.message-card-header {
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(226, 232, 240, 0.5);
}

.customer-avatar-glass {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.customer-name {
    color: var(--primary-navy);
    font-size: 1.1rem;
    font-weight: 700;
}

.customer-email {
    color: #64748b;
    font-size: 0.9rem;
}

.message-meta {
    text-align: right;
}

.message-date {
    display: block;
    color: #94a3b8;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.subject-badge {
    font-size: 0.7rem;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.subject-order { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
.subject-device_quality { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.subject-repair { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.subject-device_drop { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.subject-tech_revival { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
.subject-billing { background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; }
.subject-account { background: linear-gradient(135deg, #84cc16, #65a30d); color: white; }
.subject-general { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }

.status-badge-glass {
    padding: 0.4rem 1rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.status-new { background: rgba(254, 226, 226, 0.8); color: #991b1b; }
.status-in_progress { background: rgba(254, 243, 199, 0.8); color: #92400e; }
.status-resolved { background: rgba(209, 250, 229, 0.8); color: #065f46; }
.status-closed { background: rgba(243, 244, 246, 0.8); color: #374151; }
.status-reached_out_via_mail { background: rgba(219, 234, 254, 0.8); color: #1e40af; }
.status-reached_out_via_call { background: rgba(220, 252, 231, 0.8); color: #166534; }
.status-reached_out_via_text { background: rgba(254, 243, 199, 0.8); color: #92400e; }
.status-spoke_to_ai { background: rgba(224, 231, 255, 0.8); color: #3730a3; }
.status-customer_needs_to_come_in { background: rgba(254, 243, 199, 0.8); color: #92400e; }

.message-card-body {
    margin-top: 1rem;
}

.message-content {
    color: #374151;
    line-height: 1.7;
    margin-bottom: 1.25rem;
    font-size: 0.95rem;
}

.message-content p {
    margin: 0;
}

.admin-response-glass {
    background: rgba(59, 130, 246, 0.1);
    backdrop-filter: blur(10px);
    padding: 1.25rem;
    border-radius: 15px;
    margin: 1.25rem 0;
    border-left: 4px solid var(--electric-blue);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.response-header-glass {
    color: var(--electric-blue);
    font-weight: 700;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    font-size: 0.95rem;
}

.response-content-glass {
    color: #374151;
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.response-date {
    color: #94a3b8;
    font-size: 0.85rem;
}

.message-actions-glass {
    margin-top: 1.5rem;
    display: flex;
    justify-content: flex-end;
}

.btn-update-glass {
    background: var(--gradient-primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    display: inline-flex;
    align-items: center;
}

.btn-update-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    color: white;
}

.glass-card-empty {
    padding: 3rem;
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* Glass Modal */
.glass-modal {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.glass-modal-header {
    background: var(--gradient-primary);
    color: white;
    border-radius: 24px 24px 0 0;
    padding: 1.5rem;
    border-bottom: none;
}

.glass-modal-body {
    padding: 2rem;
    background: rgba(255, 255, 255, 0.5);
}

.glass-modal-footer {
    padding: 1.5rem 2rem;
    background: rgba(248, 250, 252, 0.8);
    border-top: 1px solid rgba(226, 232, 240, 0.5);
    border-radius: 0 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.form-group-glass {
    margin-bottom: 1.5rem;
}

.form-label-glass {
    font-weight: 700;
    color: var(--primary-navy);
    margin-bottom: 0.75rem;
    display: block;
    font-size: 0.95rem;
}

.form-control-glass {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid rgba(226, 232, 240, 0.6);
    border-radius: 15px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    resize: vertical;
    color: var(--primary-navy);
}

.form-control-glass:focus {
    outline: none;
    border-color: var(--electric-blue);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    background: rgba(255, 255, 255, 0.95);
}

.form-help-text {
    display: block;
    margin-top: 0.5rem;
    color: #64748b;
    font-size: 0.85rem;
}

.btn-cancel-glass {
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
    border: 2px solid rgba(100, 116, 139, 0.2);
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
}

.btn-cancel-glass:hover {
    background: rgba(100, 116, 139, 0.2);
    color: #475569;
    border-color: rgba(100, 116, 139, 0.3);
}

.btn-submit-glass {
    background: var(--gradient-primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    display: inline-flex;
    align-items: center;
}

.btn-submit-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    color: white;
}

.quick-stats .stat-item {
    padding: 8px 0;
    border-bottom: 1px solid rgba(226, 232, 240, 0.5);
}

.quick-stats .stat-item:last-child {
    border-bottom: none;
}

.chart-container {
    position: relative;
    width: 100%;
    height: 300px;
}

/* Counter Animation */
@keyframes countUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.counter-animate {
    animation: countUp 0.6s ease forwards;
}

/* Scrollbar Styling */
.support-messages-grid::-webkit-scrollbar {
    width: 8px;
}

.support-messages-grid::-webkit-scrollbar-track {
    background: rgba(241, 245, 249, 0.5);
    border-radius: 10px;
}

.support-messages-grid::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.3);
    border-radius: 10px;
}

.support-messages-grid::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.5);
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

// Handle status change - show/hide response field
function handleStatusChange(messageId) {
    const statusSelect = document.getElementById('updateStatus' + messageId);
    const responseGroup = document.getElementById('responseGroup' + messageId);
    const responseTextarea = document.getElementById('updateResponse' + messageId);
    
    if (!statusSelect || !responseGroup || !responseTextarea) return;
    
    const selectedStatus = statusSelect.value;
    
    // Show response field only if customer needs to come in
    if (selectedStatus === 'customer_needs_to_come_in') {
        responseGroup.style.display = 'block';
        responseTextarea.setAttribute('required', 'required');
    } else {
        responseGroup.style.display = 'none';
        responseTextarea.removeAttribute('required');
        responseTextarea.value = '';
    }
}

// Check for success messages from URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');

    if (success === '1') {
        // Show success message for status update
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            Status updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        const container = document.querySelector('.container-fluid');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
        }

        // Clean the URL
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=\d+/, '').replace(/^&/, '?');
        window.history.replaceState({}, '', newUrl === window.location.pathname + '?' ? window.location.pathname : newUrl);
    } else if (success === '2') {
        // Show success message for response added
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            Response added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        const container = document.querySelector('.container-fluid');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
        }

        // Clean the URL
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=\d+/, '').replace(/^&/, '?');
        window.history.replaceState({}, '', newUrl === window.location.pathname + '?' ? window.location.pathname : newUrl);
    }

    // Initialize modals when they open
    const updateModals = document.querySelectorAll('[id^="updateModal"]');
    updateModals.forEach(function(modal) {
        modal.addEventListener('show.bs.modal', function() {
            const messageId = modal.id.replace('updateModal', '');
            setTimeout(() => handleStatusChange(messageId), 100);
        });
    });

    // Initialize animations and charts when page loads
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
    const items = document.querySelectorAll('.glass-message-card');
    items.forEach((item, index) => {
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 1000 + (index * 100));
    });
});

// Handle form submission to prevent blue page issue
function handleFormSubmit(form) {
    // Disable the submit button to prevent double submission
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Updating...';
        submitBtn.disabled = true;

        // Re-enable after a delay if the form submission fails
        setTimeout(() => {
            if (submitBtn.disabled) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }, 5000);
    }

    // Validate required fields
    const status = form.querySelector('select[name="status"]').value;
    const response = form.querySelector('textarea[name="response"]');

    if (status === 'customer_needs_to_come_in' && response && !response.value.trim()) {
        alert('Response is required when customer needs to come in.');
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
        return false;
    }

    // Close the modal before submission to prevent blue page
    const modal = form.closest('.modal');
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    }

    return true; // Allow form submission
}

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