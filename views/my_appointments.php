<?php
try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../settings/db_class.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');

    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!$is_logged_in) {
        header("Location: ../login/user_login.php");
        exit;
    }

    // Get cart counts for header
    $cart_items = get_user_cart_ctr($customer_id, $ip_address);
    $cart_total_raw = get_cart_total_ctr($customer_id, $ip_address);
    $cart_total = $cart_total_raw ?: 0;
    $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

    // Get user's appointments
    $appointments = [];
    $error_message = '';
    
    try {
        $db = new db_connection();
        $db->db_connect();
        
        // Get appointments for logged-in user only
        $appointments_query = "SELECT
                                ra.appointment_id,
                                ra.customer_phone,
                                ra.device_info as device_type,
                                ra.issue_description,
                                ra.appointment_date as preferred_date,
                                ra.appointment_time as preferred_time,
                                ra.status,
                                ra.created_at,
                                COALESCE(s.specialist_name, 'Unassigned') as specialist_name,
                                COALESCE(rit.issue_name, 'General Issue') as issue_name,
                                COALESCE(ra.estimated_cost, 0) as base_price
                              FROM repair_appointments ra
                              LEFT JOIN specialists s ON ra.specialist_id = s.specialist_id
                              LEFT JOIN repair_issue_types rit ON ra.issue_id = rit.issue_id
                              WHERE ra.customer_id = $customer_id
                              ORDER BY ra.created_at DESC";
        
        $appointments = $db->db_fetch_all($appointments_query);
        if (!$appointments) $appointments = [];
        
    } catch (Exception $e) {
        error_log("Error fetching appointments: " . $e->getMessage());
        $error_message = "Unable to load appointments. Please try again later.";
        $appointments = [];
    }

    // Get categories and brands for header
    $categories = [];
    $brands = [];

    try {
        require_once(__DIR__ . '/../controllers/category_controller.php');
        $categories = get_all_categories_ctr();
    } catch (Exception $e) {
        error_log("Failed to load categories: " . $e->getMessage());
    }

    try {
        require_once(__DIR__ . '/../controllers/brand_controller.php');
        $brands = get_all_brands_ctr();
    } catch (Exception $e) {
        error_log("Failed to load brands: " . $e->getMessage());
    }

    // Get user's name
    $user_name = $_SESSION['name'] ?? 'User';
    $first_name = explode(' ', $user_name)[0];
} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Appointments - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">
    <link href="../includes/account_sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        .page-header {
            background: transparent;
            padding: 0 0 25px 0;
            margin-bottom: 25px;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title {
            font-size: 32px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            letter-spacing: -0.5px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .appointments-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .appointments-table table {
            margin: 0;
        }

        .appointments-table thead {
            background: #f8fafc;
        }

        .appointments-table th {
            padding: 16px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .appointments-table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }

        .appointments-table tbody tr:hover {
            background: #f9fafb;
        }

        .appointments-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-scheduled {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-in-progress {
            background: #fce7f3;
            color: #9f1239;
        }

        .view-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .view-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 30px;
        }

        .btn-book-appointment {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-book-appointment:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 128, 96, 0.3);
            color: white;
        }

        /* Appointment Details Modal Styles */
        .appointment-details-container {
            padding: 1rem 0;
        }

        .detail-section {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .detail-section-title {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-item-full {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 1rem;
        }

        .detail-value-text {
            color: #1f2937;
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }

        .detail-value-badge {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .detail-value-price {
            color: #10b981;
            font-weight: 700;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="page-background">
    <?php include '../includes/header.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Account Sidebar -->
        <?php include '../includes/account_sidebar.php'; ?>

        <!-- Content Area -->
        <main class="content-area">
            <div class="page-header">
                <h1 class="page-title">My Appointments</h1>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($appointments)): ?>
                <div class="appointments-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Device & Issue</th>
                                <th>Date & Time</th>
                                <th>Specialist</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($appointment['appointment_id']) ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($appointment['device_type']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($appointment['issue_name']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= date('M j, Y', strtotime($appointment['preferred_date'])) ?></strong><br>
                                            <small class="text-muted"><i class="fas fa-clock me-1"></i><?= htmlspecialchars($appointment['preferred_time']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($appointment['specialist_name'] !== 'Unassigned'): ?>
                                            <span><?= htmlspecialchars($appointment['specialist_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="view-btn" onclick="viewAppointment(<?= $appointment['appointment_id'] ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Appointments Found</h3>
                    <p>You haven't scheduled any repair appointments yet.</p>
                    <a href="repair_services.php" class="btn-book-appointment">
                        <i class="fas fa-calendar-plus me-2"></i>Book an Appointment
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Appointment Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <div class="appointment-details-container">
                        <!-- Appointment ID & Status -->
                        <div class="detail-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="detail-section-title"><i class="fas fa-hashtag me-2"></i>Appointment ID</h6>
                                <span id="detailAppointmentId" class="detail-value-badge">-</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="detail-section-title"><i class="fas fa-info-circle me-2"></i>Status</h6>
                                <span id="detailStatus" class="status-badge">-</span>
                            </div>
                        </div>

                        <!-- Device & Issue Information -->
                        <div class="detail-section">
                            <h6 class="detail-section-title"><i class="fas fa-mobile-alt me-2"></i>Device & Issue</h6>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Device Type:</span>
                                    <span id="detailDeviceType" class="detail-value">-</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Issue Type:</span>
                                    <span id="detailIssueName" class="detail-value">-</span>
                                </div>
                            </div>
                            <div class="detail-item-full mt-3">
                                <span class="detail-label">Issue Description:</span>
                                <p id="detailIssueDescription" class="detail-value-text">-</p>
                            </div>
                        </div>

                        <!-- Appointment Schedule -->
                        <div class="detail-section">
                            <h6 class="detail-section-title"><i class="fas fa-calendar-alt me-2"></i>Appointment Schedule</h6>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Date:</span>
                                    <span id="detailDate" class="detail-value">-</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time:</span>
                                    <span id="detailTime" class="detail-value">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Specialist & Pricing -->
                        <div class="detail-section">
                            <h6 class="detail-section-title"><i class="fas fa-user-cog me-2"></i>Service Details</h6>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Assigned Specialist:</span>
                                    <span id="detailSpecialist" class="detail-value">-</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Estimated Cost:</span>
                                    <span id="detailCost" class="detail-value-price">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Timestamps -->
                        <div class="detail-section">
                            <h6 class="detail-section-title"><i class="fas fa-clock me-2"></i>Timestamps</h6>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Created:</span>
                                    <span id="detailCreatedAt" class="detail-value">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Store appointments data for quick lookup
        const appointmentsData = <?= json_encode($appointments, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        // View appointment details
        function viewAppointment(appointmentId) {
            // Find the appointment in the stored data
            const appointment = appointmentsData.find(app => app.appointment_id == appointmentId);
            
            if (!appointment) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Appointment not found!',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Populate modal with appointment data
            document.getElementById('detailAppointmentId').textContent = '#' + appointment.appointment_id;
            
            // Status badge
            const statusBadge = document.getElementById('detailStatus');
            let statusText = appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1);
            // Handle 'in-progress' status
            if (appointment.status === 'in-progress') {
                statusText = 'In Progress';
            }
            statusBadge.textContent = statusText;
            statusBadge.className = 'status-badge status-' + appointment.status.toLowerCase().replace(/\s+/g, '-');
            
            // Device & Issue
            document.getElementById('detailDeviceType').textContent = appointment.device_type || 'N/A';
            document.getElementById('detailIssueName').textContent = appointment.issue_name || 'General Issue';
            document.getElementById('detailIssueDescription').textContent = appointment.issue_description || 'No description provided';
            
            // Schedule
            const appointmentDate = new Date(appointment.preferred_date);
            document.getElementById('detailDate').textContent = appointmentDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            document.getElementById('detailTime').textContent = appointment.preferred_time || 'N/A';
            
            // Specialist & Cost
            document.getElementById('detailSpecialist').textContent = appointment.specialist_name || 'Unassigned';
            const cost = parseFloat(appointment.base_price) || 0;
            document.getElementById('detailCost').textContent = cost > 0 ? 'GH₵' + cost.toFixed(2) : 'Not estimated';
            
            // Timestamps
            if (appointment.created_at) {
                const createdDate = new Date(appointment.created_at);
                document.getElementById('detailCreatedAt').textContent = createdDate.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } else {
                document.getElementById('detailCreatedAt').textContent = 'N/A';
            }
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('appointmentDetailsModal'));
            modal.show();
        }
    </script>
</body>

</html>

