<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../helpers/sms_helper.php';
require_once __DIR__ . '/../classes/sms_class.php';

// Check if user is logged in and is admin
if (!check_login() || !check_admin()) {
    header("Location: ../login/login.php");
    exit;
}

// Get SMS statistics
$stats = get_sms_statistics(30);
$sms_service = new SMSService();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'send_test_sms':
                $phone = $_POST['test_phone'] ?? '';
                if (empty($phone)) {
                    throw new Exception('Phone number is required');
                }

                $formatted_phone = format_phone_number($phone);
                if (!$formatted_phone) {
                    throw new Exception('Invalid phone number format');
                }

                $test_message = "Test SMS from Gadget Garage admin panel. Time: " . date('Y-m-d H:i:s');
                $result = $sms_service->sendCustomSMS($formatted_phone, $test_message, SMS_PRIORITY_HIGH);

                $message = 'Test SMS sent successfully!';
                $message_type = 'success';
                break;

            case 'send_custom_sms':
                $phone = $_POST['custom_phone'] ?? '';
                $custom_message = $_POST['custom_message'] ?? '';

                if (empty($phone) || empty($custom_message)) {
                    throw new Exception('Phone number and message are required');
                }

                $formatted_phone = format_phone_number($phone);
                if (!$formatted_phone) {
                    throw new Exception('Invalid phone number format');
                }

                $result = $sms_service->sendCustomSMS($formatted_phone, $custom_message, SMS_PRIORITY_MEDIUM);
                $message = 'Custom SMS sent successfully!';
                $message_type = 'success';
                break;

            case 'cleanup_logs':
                $days = intval($_POST['cleanup_days'] ?? 90);
                $result = cleanup_old_sms_logs($days);
                $message = $result ? "Old SMS logs cleaned up successfully!" : "Failed to cleanup logs";
                $message_type = $result ? 'success' : 'error';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Get account balance
try {
    $balance = $sms_service->getAccountBalance();
} catch (Exception $e) {
    $balance = 'Error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">SMS Management</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- SMS Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Account Balance</h5>
                            <h3><?= htmlspecialchars($balance) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">SMS Sent (30 days)</h5>
                            <h3><?= array_sum(array_column($stats, 'total_sent')) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Success Rate</h5>
                            <?php
                            $total_sent = array_sum(array_column($stats, 'total_sent'));
                            $successful = array_sum(array_column($stats, 'successful'));
                            $success_rate = $total_sent > 0 ? round(($successful / $total_sent) * 100, 1) : 0;
                            ?>
                            <h3><?= $success_rate ?>%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Failed SMS</h5>
                            <h3><?= array_sum(array_column($stats, 'failed')) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMS Actions -->
            <div class="row">
                <!-- Send Test SMS -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Send Test SMS</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="send_test_sms">
                                <div class="mb-3">
                                    <label for="test_phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="test_phone" name="test_phone"
                                           placeholder="+233xxxxxxxxx or 0xxxxxxxxx" required>
                                    <small class="form-text text-muted">Enter Ghana phone number</small>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Test SMS</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Send Custom SMS -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Send Custom SMS</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="send_custom_sms">
                                <div class="mb-3">
                                    <label for="custom_phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="custom_phone" name="custom_phone"
                                           placeholder="+233xxxxxxxxx or 0xxxxxxxxx" required>
                                </div>
                                <div class="mb-3">
                                    <label for="custom_message" class="form-label">Message</label>
                                    <textarea class="form-control" id="custom_message" name="custom_message"
                                              rows="3" maxlength="160" required></textarea>
                                    <small class="form-text text-muted">Max 160 characters</small>
                                </div>
                                <button type="submit" class="btn btn-success">Send Custom SMS</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMS Configuration -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>SMS Configuration</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>SMS Enabled:</strong></td>
                                    <td><?= SMS_ENABLED ? 'Yes' : 'No' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Sender ID:</strong></td>
                                    <td><?= SMS_SENDER_ID ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Rate Limit:</strong></td>
                                    <td><?= SMS_RATE_LIMIT ?> SMS/hour</td>
                                </tr>
                                <tr>
                                    <td><strong>Cart Abandonment:</strong></td>
                                    <td><?= CART_ABANDONMENT_ENABLED ? 'Enabled' : 'Disabled' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Abandonment Delay:</strong></td>
                                    <td><?= CART_ABANDONMENT_DELAY / 60 ?> minutes</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- System Maintenance -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>System Maintenance</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="cleanup_logs">
                                <div class="mb-3">
                                    <label for="cleanup_days" class="form-label">Delete logs older than (days):</label>
                                    <input type="number" class="form-control" id="cleanup_days" name="cleanup_days"
                                           value="90" min="1" max="365">
                                </div>
                                <button type="submit" class="btn btn-warning"
                                        onclick="return confirm('Are you sure you want to delete old SMS logs?')">
                                    Cleanup Old Logs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMS Statistics Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>SMS Statistics (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($stats)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>SMS Type</th>
                                                <th>Total Sent</th>
                                                <th>Successful</th>
                                                <th>Failed</th>
                                                <th>Success Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats as $stat): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($stat['send_date']) ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($stat['sms_type']) ?></span></td>
                                                    <td><?= $stat['total_sent'] ?></td>
                                                    <td><span class="text-success"><?= $stat['successful'] ?></span></td>
                                                    <td><span class="text-danger"><?= $stat['failed'] ?></span></td>
                                                    <td>
                                                        <?php
                                                        $rate = $stat['total_sent'] > 0 ? round(($stat['successful'] / $stat['total_sent']) * 100, 1) : 0;
                                                        $color = $rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="text-<?= $color ?>"><?= $rate ?>%</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No SMS statistics available for the last 30 days.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter for custom message
document.getElementById('custom_message').addEventListener('input', function() {
    const maxLength = 160;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;

    // Find or create character counter
    let counter = this.parentElement.querySelector('.char-counter');
    if (!counter) {
        counter = document.createElement('small');
        counter.className = 'char-counter form-text';
        this.parentElement.appendChild(counter);
    }

    counter.textContent = `${remaining} characters remaining`;
    counter.className = `char-counter form-text ${remaining < 20 ? 'text-warning' : 'text-muted'}`;
});
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>