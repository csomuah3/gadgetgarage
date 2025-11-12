<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

// Include database connection
require_once __DIR__ . '/../settings/db_class.php';

// Handle appointment status update
if (isset($_POST['update_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['status'];

    $db = new db_connection();
    $db->db_connect();
    $sql = "UPDATE repair_appointments SET status = '$new_status' WHERE appointment_id = $appointment_id";
    $result = $db->db_write_query($sql);

    if ($result) {
        $success_message = "Appointment status updated successfully!";
    } else {
        $error_message = "Failed to update appointment status.";
    }
}

// Get all appointments
try {
    $db = new db_connection();
    $db->db_connect();
    $appointments_query = "SELECT
                            ra.appointment_id,
                            ra.customer_name,
                            ra.customer_email,
                            ra.customer_phone,
                            ra.device_type,
                            ra.issue_description,
                            ra.preferred_date,
                            ra.preferred_time,
                            ra.status,
                            ra.created_at,
                            s.name as specialist_name,
                            rit.issue_name,
                            rit.base_price
                          FROM repair_appointments ra
                          LEFT JOIN specialists s ON ra.specialist_id = s.specialist_id
                          LEFT JOIN repair_issue_types rit ON ra.issue_type_id = rit.issue_type_id
                          ORDER BY ra.created_at DESC";

    $appointments = $db->db_fetch_all($appointments_query);
    if (!$appointments) $appointments = [];

} catch (Exception $e) {
    $appointments = [];
    $error_message = "Unable to load appointments: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Manage Appointments - Gadget Garage Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Import Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
            color: #065f46;
            padding: 0 !important;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Animated Background Circles */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .bg-circle-1 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
            top: 10%;
            left: 80%;
            animation: float1 8s ease-in-out infinite;
        }

        .bg-circle-2 {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.08), rgba(16, 185, 129, 0.08));
            bottom: 20%;
            right: 85%;
            animation: float2 10s ease-in-out infinite reverse;
        }

        .bg-circle-3 {
            width: 180px;
            height: 180px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.09), rgba(52, 211, 153, 0.09));
            top: 60%;
            left: 5%;
            animation: float3 12s ease-in-out infinite;
        }

        /* Animation Keyframes */
        @keyframes float1 {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            25% { transform: translateY(-20px) translateX(10px); }
            50% { transform: translateY(0px) translateX(20px); }
            75% { transform: translateY(20px) translateX(10px); }
        }

        @keyframes float2 {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            33% { transform: translateY(15px) translateX(-15px); }
            66% { transform: translateY(-10px) translateX(15px); }
        }

        @keyframes float3 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-25px) rotate(5deg); }
        }

        /* Header Styles */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            height: 80px;
        }

        .header-container {
            height: 80px;
            display: flex;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #10b981;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo:hover {
            color: #059669;
            transform: scale(1.05);
        }

        .co {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            margin-left: 4px;
        }

        /* Left Sidebar - Starts after header */
        .sidebar {
            position: fixed;
            left: 0;
            top: 80px;
            width: 320px;
            height: calc(100vh - 80px);
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            z-index: 999;
            padding: 20px;
            box-shadow: 4px 0 15px rgba(16, 185, 129, 0.2);
            transition: transform 0.3s ease;
        }

        .sidebar.sidebar-hidden {
            transform: translateX(-100%);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Main Content - Adjusted for wider sidebar */
        .main-content {
            margin-left: 320px;
            position: relative;
            z-index: 10;
            transition: margin-left 0.3s ease;
        }

        .main-content.sidebar-hidden {
            margin-left: 0;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggle {
            position: fixed;
            top: 90px;
            left: 20px;
            z-index: 1001;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .sidebar-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .sidebar-toggle.sidebar-hidden {
            left: 20px;
        }

        /* Content Container */
        .content-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            margin: 100px 30px 30px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .content-header {
            margin-bottom: 30px;
        }

        .content-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 10px;
        }

        .content-subtitle {
            font-size: 1.2rem;
            color: #6b7280;
        }

        /* Appointments Table */
        .appointments-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
        }

        .table tbody tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.875rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.sidebar-visible {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                left: 20px;
            }

            .content-container {
                margin: 100px 15px 15px;
                padding: 20px;
            }

            .content-title {
                font-size: 2rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background Circles -->
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>
    <div class="bg-circle bg-circle-3"></div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar - Always Visible -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
            <p>Manage appointments</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="category.php"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="brand.php"><i class="fas fa-tags"></i> Brands</a></li>
            <li><a href="product.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="appointments.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between header-container">
                <!-- Logo -->
                <a href="../index.php" class="logo">
                    Gadget<span class="co">Garage</span>
                </a>

                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- User Avatar with Dropdown -->
                    <div class="user-dropdown">
                        <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>">
                            <?= strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="dropdown-menu-custom">
                            <a href="../login/logout.php" class="dropdown-item-custom">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-container">
            <div class="content-header">
                <h1 class="content-title">Appointment Management</h1>
                <p class="content-subtitle">Monitor and manage repair appointments</p>
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

            <div class="appointments-table">
                <?php if (!empty($appointments)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Device & Issue</th>
                                    <th>Specialist</th>
                                    <th>Date & Time</th>
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
                                                <strong><?= htmlspecialchars($appointment['customer_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($appointment['customer_email']) ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($appointment['customer_phone']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($appointment['device_type']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($appointment['issue_name'] ?? 'General Issue') ?></small><br>
                                                <small class="text-info">GH₵<?= number_format($appointment['base_price'] ?? 0, 2) ?></small>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($appointment['specialist_name'] ?? 'Unassigned') ?></td>
                                        <td>
                                            <div>
                                                <strong><?= date('M j, Y', strtotime($appointment['preferred_date'])) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($appointment['preferred_time']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                                                <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-primary btn-sm" onclick="updateStatus(<?= $appointment['appointment_id'] ?>)">
                                                    <i class="fas fa-edit"></i> Update
                                                </button>
                                                <button class="btn btn-info btn-sm" onclick="viewAppointment(<?= $appointment['appointment_id'] ?>)">
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
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <h3>No Appointments Found</h3>
                        <p>When customers book repair appointments, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar functionality
        let sidebarHidden = false;

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const toggleIcon = toggleBtn.querySelector('i');

            if (window.innerWidth <= 768) {
                // Mobile behavior
                sidebar.classList.toggle('sidebar-visible');
                if (sidebar.classList.contains('sidebar-visible')) {
                    toggleIcon.classList.remove('fa-bars');
                    toggleIcon.classList.add('fa-times');
                } else {
                    toggleIcon.classList.remove('fa-times');
                    toggleIcon.classList.add('fa-bars');
                }
            } else {
                // Desktop behavior
                sidebarHidden = !sidebarHidden;
                if (sidebarHidden) {
                    sidebar.classList.add('sidebar-hidden');
                    mainContent.classList.add('sidebar-hidden');
                    toggleBtn.classList.add('sidebar-hidden');
                    toggleIcon.classList.remove('fa-bars');
                    toggleIcon.classList.add('fa-chevron-right');
                } else {
                    sidebar.classList.remove('sidebar-hidden');
                    mainContent.classList.remove('sidebar-hidden');
                    toggleBtn.classList.remove('sidebar-hidden');
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-bars');
                }
            }
        }

        // Appointment management functions
        function updateStatus(appointmentId) {
            const newStatus = prompt('Enter new status (pending, confirmed, completed, cancelled):');
            if (newStatus && ['pending', 'confirmed', 'completed', 'cancelled'].includes(newStatus.toLowerCase())) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="appointment_id" value="${appointmentId}">
                    <input type="hidden" name="status" value="${newStatus.toLowerCase()}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewAppointment(appointmentId) {
            alert('Appointment details for #' + appointmentId + ' - Feature coming soon!');
        }
    </script>
</body>

</html>