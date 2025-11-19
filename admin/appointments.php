<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

$page_title = "Appointment Management";

// Include database connection
require_once __DIR__ . '/../settings/db_class.php';

// Handle appointment status update
$success_message = '';
$error_message = '';

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

// Get all appointments with analytics
try {
    $db = new db_connection();
    $db->db_connect();

    // Main appointments query
    $appointments_query = "SELECT
                            ra.appointment_id,
                            COALESCE(c.customer_name, 'Walk-in Customer') as customer_name,
                            COALESCE(c.customer_email, '') as customer_email,
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
                          LEFT JOIN customer c ON ra.customer_id = c.customer_id
                          LEFT JOIN specialists s ON ra.specialist_id = s.specialist_id
                          LEFT JOIN repair_issue_types rit ON ra.issue_id = rit.issue_id
                          ORDER BY ra.created_at DESC";

    $appointments = $db->db_fetch_all($appointments_query);
    if (!$appointments) $appointments = [];

    // Calculate analytics
    $total_appointments = count($appointments);
    $confirmed_appointments = count(array_filter($appointments, function($app) { return $app['status'] === 'confirmed'; }));
    $completed_appointments = count(array_filter($appointments, function($app) { return $app['status'] === 'completed'; }));
    $scheduled_appointments = count(array_filter($appointments, function($app) { return $app['status'] === 'scheduled'; }));
    $pending_appointments = count(array_filter($appointments, function($app) { return $app['status'] === 'pending'; }));
    $cancelled_appointments = count(array_filter($appointments, function($app) { return $app['status'] === 'cancelled'; }));

    // Today's appointments
    $today = date('Y-m-d');
    $today_appointments = array_filter($appointments, function($app) use ($today) {
        return $app['preferred_date'] === $today;
    });

    // This week's appointments
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));
    $week_appointments = array_filter($appointments, function($app) use ($week_start, $week_end) {
        $app_date = $app['preferred_date'];
        return $app_date >= $week_start && $app_date <= $week_end;
    });

    // Revenue from completed appointments
    $total_revenue = array_sum(array_map(function($app) {
        return $app['status'] === 'completed' ? $app['base_price'] : 0;
    }, $appointments));

    // Upcoming appointments (next 7 days)
    $upcoming_start = date('Y-m-d');
    $upcoming_end = date('Y-m-d', strtotime('+7 days'));
    $upcoming_appointments = array_filter($appointments, function($app) use ($upcoming_start, $upcoming_end) {
        $app_date = $app['preferred_date'];
        return $app_date >= $upcoming_start && $app_date <= $upcoming_end && $app['status'] !== 'cancelled';
    });

    // Sort upcoming appointments by date and time
    usort($upcoming_appointments, function($a, $b) {
        $date_cmp = strcmp($a['preferred_date'], $b['preferred_date']);
        if ($date_cmp === 0) {
            return strcmp($a['preferred_time'], $b['preferred_time']);
        }
        return $date_cmp;
    });

} catch (Exception $e) {
    $appointments = [];
    $total_appointments = 0;
    $confirmed_appointments = 0;
    $completed_appointments = 0;
    $scheduled_appointments = 0;
    $pending_appointments = 0;
    $cancelled_appointments = 0;
    $today_appointments = [];
    $week_appointments = [];
    $total_revenue = 0;
    $upcoming_appointments = [];
    $error_message = "Unable to load appointments: " . $e->getMessage();
}
?>

<?php include 'includes/admin_header.php'; ?>
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Appointment Management</h1>
    <p class="page-subtitle">Schedule, track, and manage repair appointments with advanced analytics</p>
    <nav class="breadcrumb-custom">
        <span>Home > Appointments</span>
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
                    <i class="fas fa-calendar-check fa-3x"></i>
                </div>
                <h3 class="counter text-primary" data-target="<?= $total_appointments ?>">0</h3>
                <p class="text-muted mb-0">Total Appointments</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.2s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-success mb-3">
                    <i class="fas fa-check-circle fa-3x"></i>
                </div>
                <h3 class="counter text-success" data-target="<?= $completed_appointments ?>">0</h3>
                <p class="text-muted mb-0">Completed</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.3s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-warning mb-3">
                    <i class="fas fa-clock fa-3x"></i>
                </div>
                <h3 class="counter text-warning" data-target="<?= $pending_appointments ?>">0</h3>
                <p class="text-muted mb-0">Pending</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="admin-card analytics-card" style="animation-delay: 0.4s;">
            <div class="card-body-custom text-center">
                <div class="analytics-icon text-info mb-3">
                    <i class="fas fa-dollar-sign fa-3x"></i>
                </div>
                <h3 class="counter text-info" data-target="<?= $total_revenue ?>">0</h3>
                <p class="text-muted mb-0">Total Revenue (GH₵)</p>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Widgets -->
<div class="row g-4 mb-4">
    <!-- Today's Appointments -->
    <div class="col-lg-6">
        <div class="admin-card" style="animation-delay: 0.5s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-calendar-day me-2"></i>Today's Appointments</h5>
            </div>
            <div class="card-body-custom">
                <?php if (!empty($today_appointments)): ?>
                    <div class="appointment-list">
                        <?php foreach (array_slice($today_appointments, 0, 5) as $appointment): ?>
                            <div class="appointment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($appointment['customer_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($appointment['device_type']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="time-badge"><?= htmlspecialchars($appointment['preferred_time']) ?></div>
                                        <span class="status-badge status-<?= strtolower($appointment['status']) ?> mt-1">
                                            <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($appointment['specialist_name'] !== 'Unassigned'): ?>
                                    <div class="specialist-info mt-2">
                                        <small><i class="fas fa-user-cog me-1"></i><?= htmlspecialchars($appointment['specialist_name']) ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No appointments scheduled for today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="col-lg-6">
        <div class="admin-card" style="animation-delay: 0.6s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-calendar-alt me-2"></i>Upcoming (Next 7 Days)</h5>
            </div>
            <div class="card-body-custom">
                <?php if (!empty($upcoming_appointments)): ?>
                    <div class="appointment-list">
                        <?php foreach (array_slice($upcoming_appointments, 0, 5) as $appointment): ?>
                            <div class="appointment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($appointment['customer_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($appointment['device_type']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="date-badge"><?= date('M j', strtotime($appointment['preferred_date'])) ?></div>
                                        <div class="time-badge"><?= htmlspecialchars($appointment['preferred_time']) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No upcoming appointments</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status Distribution Chart -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="admin-card" style="animation-delay: 0.7s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-doughnut me-2"></i>Appointment Status Distribution</h5>
            </div>
            <div class="card-body-custom">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Appointments Table -->
<div class="row g-4">
    <div class="col-12">
        <div class="admin-card" style="animation-delay: 0.8s;">
            <div class="card-header-custom">
                <h5><i class="fas fa-list me-2"></i>All Appointments</h5>
            </div>
            <div class="card-body-custom p-0">
                <?php if (!empty($appointments)): ?>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
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
                                <?php foreach ($appointments as $index => $appointment): ?>
                                    <tr style="animation-delay: <?= 0.9 + ($index * 0.05) ?>s;">
                                        <td><strong>#<?= htmlspecialchars($appointment['appointment_id']) ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="customer-avatar me-3">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($appointment['customer_name']) ?></strong><br>
                                                    <?php if ($appointment['customer_email']): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($appointment['customer_email']) ?></small><br>
                                                    <?php endif; ?>
                                                    <small class="text-muted"><?= htmlspecialchars($appointment['customer_phone']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($appointment['device_type']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($appointment['issue_name']) ?></small><br>
                                                <?php if ($appointment['base_price'] > 0): ?>
                                                    <span class="badge bg-info">GH₵<?= number_format($appointment['base_price'], 2) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($appointment['specialist_name'] !== 'Unassigned'): ?>
                                                    <div class="specialist-avatar me-2">
                                                        <i class="fas fa-user-cog"></i>
                                                    </div>
                                                    <span><?= htmlspecialchars($appointment['specialist_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Unassigned</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= date('M j, Y', strtotime($appointment['preferred_date'])) ?></strong><br>
                                                <small class="text-muted"><i class="fas fa-clock me-1"></i><?= htmlspecialchars($appointment['preferred_time']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                                                <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary"
                                                        onclick="updateStatus(<?= $appointment['appointment_id'] ?>, '<?= htmlspecialchars($appointment['status']) ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info"
                                                        onclick="viewAppointment(<?= $appointment['appointment_id'] ?>)">
                                                    <i class="fas fa-eye"></i>
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
                        <i class="fas fa-calendar-check fa-4x text-muted mb-3"></i>
                        <h3>No Appointments Found</h3>
                        <p class="text-muted">When customers book repair appointments, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Appointment Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="appointment_id" id="updateAppointmentId">
                    <div class="form-group">
                        <label for="newStatus" class="form-label-modern">New Status</label>
                        <select class="form-control-modern" name="status" id="newStatus" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Additional styles for appointments page */
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

.appointment-list {
    max-height: 300px;
    overflow-y: auto;
}

.appointment-item {
    padding: 15px;
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.appointment-item:last-child {
    border-bottom: none;
}

.appointment-item:hover {
    background: rgba(59, 130, 246, 0.05);
    border-radius: 8px;
    margin: 2px;
    padding: 13px;
}

.time-badge, .date-badge {
    background: var(--gradient-primary);
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 4px;
}

.specialist-info {
    color: var(--text-muted);
}

.customer-avatar, .specialist-avatar {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-confirmed {
    background: #dbeafe;
    color: #1e40af;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
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

// Status Distribution Chart
function initStatusChart() {
    const ctx = document.getElementById('statusChart').getContext('2d');

    const chartData = {
        labels: ['Completed', 'Confirmed', 'Scheduled', 'Pending', 'Cancelled'],
        datasets: [{
            data: [<?= $completed_appointments ?>, <?= $confirmed_appointments ?>, <?= $scheduled_appointments ?>, <?= $pending_appointments ?>, <?= $cancelled_appointments ?>],
            backgroundColor: [
                '#10b981',
                '#3b82f6',
                '#8b5cf6',
                '#f59e0b',
                '#ef4444'
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

// Update appointment status
function updateStatus(appointmentId, currentStatus) {
    document.getElementById('updateAppointmentId').value = appointmentId;
    document.getElementById('newStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

// View appointment details
function viewAppointment(appointmentId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Appointment Details',
            text: 'Appointment details for #' + appointmentId + ' - Feature coming soon!',
            icon: 'info',
            confirmButtonColor: '#D19C97',
            confirmButtonText: 'OK'
        });
    } else {
        Swal.fire({
            title: 'Appointment Details',
            text: 'Appointment details for #' + appointmentId + ' - Feature coming soon!',
            icon: 'info',
            confirmButtonColor: '#007bff',
            confirmButtonText: 'OK'
        });
    }
}

// Initialize animations and charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Start counter animations
    setTimeout(animateCounters, 500);

    // Initialize status chart
    setTimeout(initStatusChart, 800);

    // Add stagger animation to cards
    const cards = document.querySelectorAll('.admin-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Animate table rows
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach((row, index) => {
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 900 + (index * 50));
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?>