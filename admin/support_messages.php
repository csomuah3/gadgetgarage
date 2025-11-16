<?php
// Start session and include required files
session_start();
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/support_controller.php');

// Check if user is logged in and is admin
if (!check_login() || !check_admin()) {
    header("Location: ../login/login.php");
    exit();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['update_status'])) {
        $message_id = intval($_POST['message_id']);
        $new_status = $_POST['status'];
        $assigned_to = $_POST['assigned_to'] ?: null;

        if (update_support_message_status_ctr($message_id, $new_status, $assigned_to)) {
            $success_message = "Message status updated successfully.";
        } else {
            $error_message = "Failed to update message status.";
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

// Get all support messages
$messages = get_all_support_messages_ctr($status_filter ?: null, $limit);
$stats = get_support_statistics_ctr();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Messages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            color: white;
        }

        .sidebar .nav-link {
            color: #bdc3c7;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }

        .stats-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .message-card {
            border: none;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .priority-urgent {
            border-left: 4px solid #e74c3c;
        }

        .priority-high {
            border-left: 4px solid #f39c12;
        }

        .priority-normal {
            border-left: 4px solid #3498db;
        }

        .priority-low {
            border-left: 4px solid #95a5a6;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }

        .status-new {
            background: #e74c3c;
        }

        .status-in_progress {
            background: #f39c12;
        }

        .status-resolved {
            background: #27ae60;
        }

        .status-closed {
            background: #95a5a6;
        }

        .subject-tag {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-right: 5px;
        }

        .subject-order {
            background: #3498db;
            color: white;
        }

        .subject-device_quality {
            background: #e74c3c;
            color: white;
        }

        .subject-repair {
            background: #f39c12;
            color: white;
        }

        .subject-device_drop {
            background: #27ae60;
            color: white;
        }

        .subject-tech_revival {
            background: #9b59b6;
            color: white;
        }

        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <div class="d-flex align-items-center mb-4">
                    <h4 class="mb-0">Gadget<span style="background: #008060; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">Garage</span></h4>
                </div>

                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="../admin/category.php" class="nav-link">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link active">
                            <i class="fas fa-headset me-2"></i>
                            Support Messages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/products.php" class="nav-link">
                            <i class="fas fa-box me-2"></i>
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/category.php" class="nav-link">
                            <i class="fas fa-tags me-2"></i>
                            Categories
                        </a>
                    </li>
                </ul>

                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown">
                        <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                        <li><a class="dropdown-item" href="../login/logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-4">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-0"><i class="fas fa-headset text-primary me-2"></i>Customer Support Messages</h2>
                            <small class="text-muted">Manage and respond to customer inquiries</small>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <select class="form-select" id="statusFilter" onchange="filterByStatus(this.value)">
                                    <option value="">All Messages</option>
                                    <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card border-primary">
                            <div class="card-body text-center">
                                <div class="text-primary">
                                    <i class="fas fa-envelope fa-2x mb-2"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['total'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Total Messages</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card border-danger">
                            <div class="card-body text-center">
                                <div class="text-danger">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['new'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">New Messages</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card border-warning">
                            <div class="card-body text-center">
                                <div class="text-warning">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['in_progress'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card border-success">
                            <div class="card-body text-center">
                                <div class="text-success">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['resolved'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Resolved</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Messages -->
                <div class="row">
                    <?php if (empty($messages)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No messages found</h5>
                                    <p class="text-muted">There are no support messages matching your criteria.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="col-12">
                                <div class="card message-card priority-<?php echo $message['priority']; ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="subject-tag subject-<?php echo $message['subject']; ?>">
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
                                                echo $subject_labels[$message['subject']] ?? $message['subject'];
                                                ?>
                                            </span>
                                            <strong><?php echo htmlspecialchars($message['customer_name']); ?></strong>
                                            <small class="text-muted ms-2"><?php echo htmlspecialchars($message['customer_email']); ?></small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge status-badge status-<?php echo $message['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $message['status'])); ?>
                                            </span>
                                            <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>

                                        <!-- Admin Response -->
                                        <?php if ($message['admin_response']): ?>
                                            <div class="alert alert-light">
                                                <strong><i class="fas fa-reply me-1"></i>Admin Response:</strong>
                                                <p class="mb-1 mt-2"><?php echo nl2br(htmlspecialchars($message['admin_response'])); ?></p>
                                                <small class="text-muted">
                                                    Responded on <?php echo date('M j, Y g:i A', strtotime($message['response_date'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Action Buttons -->
                                        <div class="row g-2 mt-2">
                                            <div class="col-md-6">
                                                <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $message['message_id']; ?>">
                                                    <i class="fas fa-reply me-1"></i>Add Response
                                                </button>
                                            </div>
                                            <div class="col-md-6">
                                                <button class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $message['message_id']; ?>">
                                                    <i class="fas fa-edit me-1"></i>Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Response Modal -->
                                <div class="modal fade" id="responseModal<?php echo $message['message_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Add Response</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="response" class="form-label">Your Response:</label>
                                                        <textarea class="form-control" name="response" rows="5" required placeholder="Type your response to the customer..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="add_response" class="btn btn-primary">Send Response</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Update Modal -->
                                <div class="modal fade" id="statusModal<?php echo $message['message_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="status" class="form-label">Status:</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="new" <?php echo $message['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                            <option value="in_progress" <?php echo $message['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                            <option value="resolved" <?php echo $message['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                            <option value="closed" <?php echo $message['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="assigned_to" class="form-label">Assign To (Optional):</label>
                                                        <input type="number" class="form-control" name="assigned_to" placeholder="Admin ID" value="<?php echo $message['assigned_to'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByStatus(status) {
            const url = new URL(window.location.href);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        }

        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>