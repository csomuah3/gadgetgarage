<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins

require_once __DIR__ . '/../settings/db_class.php';

$page_title = "Device Drop Requests";

// Connect to database
$db = new db_connection();
$db->db_connect();
$conn = $db->db_conn();

// Helper to safely fetch single value
function dd_safe_value($row, $key, $default = 0) {
    return isset($row[$key]) ? $row[$key] : $default;
}

// ------------------------------
// DEVICE DROP ANALYTICS
// ------------------------------
try {
    // Pending requests (count)
    $pending_requests_query = "
        SELECT COUNT(*) AS pending_count
        FROM device_drop_requests
        WHERE status = 'pending'
    ";
    $pending_requests_row = $db->db_fetch_one($pending_requests_query) ?: [];
    $pending_requests_count = dd_safe_value($pending_requests_row, 'pending_count', 0);

    // Total requests (all statuses)
    $total_requests_query = "
        SELECT COUNT(*) AS total_count
        FROM device_drop_requests
    ";
    $total_requests_row = $db->db_fetch_one($total_requests_query) ?: [];
    $total_requests_count = dd_safe_value($total_requests_row, 'total_count', 0);

    // Approved requests today
    $approved_today_query = "
        SELECT COUNT(*) AS approved_today
        FROM device_drop_requests
        WHERE status = 'approved' AND DATE(updated_at) = CURDATE()
    ";
    $approved_today_row = $db->db_fetch_one($approved_today_query) ?: [];
    $approved_today_count = dd_safe_value($approved_today_row, 'approved_today', 0);

    // Total estimated value of pending requests
    $pending_value_query = "
        SELECT COALESCE(SUM(final_amount), 0) AS pending_value
        FROM device_drop_requests
        WHERE status = 'pending' AND final_amount IS NOT NULL
    ";
    $pending_value_row = $db->db_fetch_one($pending_value_query) ?: [];
    $pending_value = dd_safe_value($pending_value_row, 'pending_value', 0.0);

} catch (Exception $e) {
    error_log("Device Drop Analytics Error: " . $e->getMessage());
    $pending_requests_count = 0;
    $total_requests_count = 0;
    $approved_today_count = 0;
    $pending_value = 0.0;
}

// ------------------------------
// FETCH DEVICE DROP REQUESTS
// ------------------------------
try {
    $device_requests_query = "
        SELECT
            ddr.id,
            ddr.device_type,
            ddr.device_brand,
            ddr.device_model,
            ddr.condition_status,
            ddr.first_name,
            ddr.last_name,
            ddr.email,
            ddr.phone,
            ddr.payment_method,
            ddr.final_amount,
            ddr.ai_valuation,
            ddr.status,
            ddr.admin_notes,
            ddr.created_at,
            ddr.updated_at,
            GROUP_CONCAT(ddi.image_url) as images
        FROM device_drop_requests ddr
        LEFT JOIN device_drop_images ddi ON ddr.id = ddi.request_id
        GROUP BY ddr.id
        ORDER BY ddr.created_at DESC
    ";
    $device_requests = $db->db_fetch_all($device_requests_query) ?: [];

} catch (Exception $e) {
    error_log("Device Drop Fetch Error: " . $e->getMessage());
    $device_requests = [];
}

// Include admin header
require_once 'includes/admin_header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Device Drop Requests</h1>
            <p class="page-subtitle">Manage trade-in requests and approve payments</p>
            <nav class="breadcrumb-custom">
                <a href="index.php">Dashboard</a> / <span>Device Drop</span>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <span class="status-badge status-pending">
                <i class="fas fa-clock"></i> <?php echo $pending_requests_count; ?> Pending
            </span>
        </div>
    </div>
</div>

<!-- Analytics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div style="color: #f59e0b; font-size: 2.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 style="margin: 0; color: #1a202c; font-weight: 700;"><?php echo $total_requests_count; ?></h3>
                <p style="margin: 0; color: #64748b; font-weight: 500;">Total Requests</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div style="color: #3b82f6; font-size: 2.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 style="margin: 0; color: #1a202c; font-weight: 700;"><?php echo $pending_requests_count; ?></h3>
                <p style="margin: 0; color: #64748b; font-weight: 500;">Pending Review</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div style="color: #22c55e; font-size: 2.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 style="margin: 0; color: #1a202c; font-weight: 700;"><?php echo $approved_today_count; ?></h3>
                <p style="margin: 0; color: #64748b; font-weight: 500;">Approved Today</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="admin-card">
            <div class="card-body-custom text-center">
                <div style="color: #10b981; font-size: 2.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3 style="margin: 0; color: #1a202c; font-weight: 700;">GH₵ <?php echo number_format($pending_value, 2); ?></h3>
                <p style="margin: 0; color: #64748b; font-weight: 500;">Pending Value</p>
            </div>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="admin-card">
    <div class="card-header-custom">
        <h5><i class="fas fa-list"></i> Device Drop Requests</h5>
    </div>
    <div class="card-body-custom p-0">
        <?php if (empty($device_requests)): ?>
            <div class="text-center py-5">
                <div style="color: #94a3b8; font-size: 3rem; margin-bottom: 1rem;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h4 style="color: #64748b; margin-bottom: 0.5rem;">No Device Drop Requests</h4>
                <p style="color: #94a3b8; margin: 0;">Requests will appear here when customers submit their devices.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Customer</th>
                            <th>Payment Method</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($device_requests as $request): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="device-icon me-3" style="background: #f1f5f9; padding: 0.75rem; border-radius: 8px; color: #3b82f6;">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1a202c;">
                                            <?php echo htmlspecialchars($request['device_brand'] . ' ' . $request['device_model']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #64748b;">
                                            <?php echo ucfirst(htmlspecialchars($request['device_type'])); ?> •
                                            <?php echo ucfirst(htmlspecialchars($request['condition_status'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div style="font-weight: 600; color: #1a202c;">
                                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #64748b;">
                                        <?php echo htmlspecialchars($request['email']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge" style="<?php
                                    echo $request['payment_method'] === 'store_credit'
                                        ? 'background: #ddd6fe; color: #6d28d9;'
                                        : 'background: #dcfce7; color: #16a34a;';
                                ?>">
                                    <i class="fas fa-<?php echo $request['payment_method'] === 'store_credit' ? 'gift' : 'money-bill-wave'; ?>"></i>
                                    <?php echo $request['payment_method'] === 'store_credit' ? 'Store Credit' : 'Cash'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #1a202c;">
                                    <?php if ($request['final_amount']): ?>
                                        GH₵ <?php echo number_format($request['final_amount'], 2); ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Pending</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($request['ai_valuation']): ?>
                                <div style="font-size: 0.8rem; color: #3b82f6;">
                                    <i class="fas fa-robot"></i> AI Valued
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">
                                    <?php echo date('g:i A', strtotime($request['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm" style="background: #3b82f6; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 6px;"
                                            onclick="viewRequestDetails(<?php echo $request['id']; ?>)"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success-custom"
                                            onclick="approveRequest(<?php echo $request['id']; ?>)"
                                            title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger-custom"
                                            onclick="rejectRequest(<?php echo $request['id']; ?>)"
                                            title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

    </div> <!-- admin-container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dark-mode.js"></script>

    <script>
        // View request details
        async function viewRequestDetails(requestId) {
            try {
                const response = await fetch('actions/get_device_drop_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ request_id: requestId })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    const request = result.request;
                    const images = result.images || [];

                    let imagesHtml = '';
                    if (images.length > 0) {
                        imagesHtml = `
                            <div class="mb-3">
                                <h6 style="margin-bottom: 0.75rem; color: #374151; font-weight: 600;">Device Images</h6>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem;">
                                    ${images.map(img => `
                                        <img src="../${img.image_url}" alt="Device"
                                             style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb;">
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    Swal.fire({
                        title: `Device Drop Request #${requestId}`,
                        html: `
                            <div style="text-align: left; max-width: 600px; margin: 0 auto;">
                                ${imagesHtml}

                                <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <h6 style="margin-bottom: 0.75rem; color: #374151; font-weight: 600;">Device Information</h6>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.9rem;">
                                        <div><strong>Type:</strong> ${request.device_type}</div>
                                        <div><strong>Brand:</strong> ${request.device_brand}</div>
                                        <div><strong>Model:</strong> ${request.device_model}</div>
                                        <div><strong>Condition:</strong> ${request.condition_status}</div>
                                    </div>
                                </div>

                                <div style="background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <h6 style="margin-bottom: 0.75rem; color: #374151; font-weight: 600;">Customer Details</h6>
                                    <div style="font-size: 0.9rem;">
                                        <div style="margin-bottom: 0.25rem;"><strong>Name:</strong> ${request.first_name} ${request.last_name}</div>
                                        <div style="margin-bottom: 0.25rem;"><strong>Email:</strong> ${request.email}</div>
                                        <div><strong>Phone:</strong> ${request.phone}</div>
                                    </div>
                                </div>

                                ${request.description ? `
                                    <div style="background: #fffbeb; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                        <h6 style="margin-bottom: 0.75rem; color: #374151; font-weight: 600;">Additional Details</h6>
                                        <p style="margin: 0; font-size: 0.9rem; color: #374151;">${request.description}</p>
                                    </div>
                                ` : ''}

                                ${request.final_amount ? `
                                    <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                        <h6 style="margin-bottom: 0.75rem; color: #374151; font-weight: 600;">Valuation</h6>
                                        <div style="font-size: 0.9rem;">
                                            <div><strong>Payment Method:</strong> ${request.payment_method === 'store_credit' ? 'Store Credit' : 'Cash'}</div>
                                            <div><strong>Amount:</strong> GH₵ ${parseFloat(request.final_amount).toFixed(2)}</div>
                                            ${request.ai_valuation ? '<div style="color: #3b82f6;"><i class="fas fa-robot"></i> AI Generated Valuation</div>' : ''}
                                        </div>
                                    </div>
                                ` : ''}

                                <div style="font-size: 0.85rem; color: #6b7280; text-align: center; margin-top: 1rem;">
                                    Submitted: ${new Date(request.created_at).toLocaleDateString()} at ${new Date(request.created_at).toLocaleTimeString()}
                                </div>
                            </div>
                        `,
                        width: '800px',
                        showCloseButton: true,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal-details-popup'
                        }
                    });
                } else {
                    throw new Error(result.message || 'Failed to fetch details');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load request details: ' + error.message
                });
            }
        }

        // Approve request
        async function approveRequest(requestId) {
            const { value: notes } = await Swal.fire({
                title: 'Approve Request',
                text: 'This will process the payment and notify the customer.',
                input: 'textarea',
                inputLabel: 'Admin Notes (optional)',
                inputPlaceholder: 'Add any notes about the approval...',
                showCancelButton: true,
                confirmButtonText: 'Approve & Process',
                confirmButtonColor: '#22c55e',
                cancelButtonText: 'Cancel'
            });

            if (notes !== undefined) {
                try {
                    const response = await fetch('actions/approve_device_drop.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            request_id: requestId,
                            admin_notes: notes || ''
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Request Approved!',
                            text: result.message,
                            confirmButtonColor: '#22c55e'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(result.message || 'Failed to approve request');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Approval Failed',
                        text: error.message
                    });
                }
            }
        }

        // Reject request
        async function rejectRequest(requestId) {
            const { value: reason } = await Swal.fire({
                title: 'Reject Request',
                text: 'Please provide a reason for rejection.',
                input: 'textarea',
                inputLabel: 'Rejection Reason *',
                inputPlaceholder: 'Explain why this request is being rejected...',
                inputValidator: (value) => {
                    if (!value.trim()) {
                        return 'Please provide a reason for rejection'
                    }
                },
                showCancelButton: true,
                confirmButtonText: 'Reject Request',
                confirmButtonColor: '#ef4444',
                cancelButtonText: 'Cancel'
            });

            if (reason) {
                try {
                    const response = await fetch('actions/reject_device_drop.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            request_id: requestId,
                            admin_notes: reason
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Request Rejected',
                            text: 'The customer will be notified of the rejection.',
                            confirmButtonColor: '#3b82f6'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(result.message || 'Failed to reject request');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Rejection Failed',
                        text: error.message
                    });
                }
            }
        }
    </script>

    <style>
        .swal-details-popup {
            font-family: 'Inter', sans-serif;
        }

        .swal-details-popup .swal2-html-container {
            overflow: visible;
        }
    </style>

</body>
</html>