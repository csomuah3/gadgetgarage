<?php
session_start();
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/settings/db_class.php';

// Get parameters from URL
$issue_id = isset($_GET['issue_id']) ? intval($_GET['issue_id']) : 0;
$issue_name = isset($_GET['issue_name']) ? $_GET['issue_name'] : '';
$specialist_id = isset($_GET['specialist_id']) ? intval($_GET['specialist_id']) : 0;
$specialist_name = isset($_GET['specialist_name']) ? $_GET['specialist_name'] : '';

if ($issue_id <= 0 || $specialist_id <= 0) {
    header('Location: repair_services.php');
    exit;
}

// Debug: Check if we have the required data
error_log("Debug: issue_id=$issue_id, specialist_id=$specialist_id, issue_name=$issue_name, specialist_name=$specialist_name");

// Handle appointment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    try {
        $db = new db_connection();
        $db->db_connect();

        $customer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $customer_phone = $_POST['customer_phone'];
        $device_info = $_POST['device_info'] ?? '';
        $issue_description = $_POST['issue_description'] ?? '';

        // Escape values for security
        $customer_id = $customer_id ? mysqli_real_escape_string($db->db_conn(), $customer_id) : 'NULL';
        $specialist_id = mysqli_real_escape_string($db->db_conn(), $specialist_id);
        $issue_id = mysqli_real_escape_string($db->db_conn(), $issue_id);
        $appointment_date = mysqli_real_escape_string($db->db_conn(), $appointment_date);
        $appointment_time = mysqli_real_escape_string($db->db_conn(), $appointment_time);
        $customer_phone = mysqli_real_escape_string($db->db_conn(), $customer_phone);
        $device_info = mysqli_real_escape_string($db->db_conn(), $device_info);
        $issue_description = mysqli_real_escape_string($db->db_conn(), $issue_description);

        // Insert appointment (adjusted for your existing table structure)
        $insert_query = "INSERT INTO repair_appointments
                        (customer_id, specialist_id, issue_type_id, appointment_date, appointment_time,
                         customer_phone, device_info, issue_description, status)
                        VALUES ($customer_id, $specialist_id, $issue_id, '$appointment_date', '$appointment_time',
                               '$customer_phone', '$device_info', '$issue_description', 'scheduled')";

        $result = $db->db_write_query($insert_query);

        if ($result) {
            $success_message = "Your appointment has been scheduled successfully!";
            // Get the last inserted ID using mysqli_insert_id
            $appointment_id = mysqli_insert_id($db->db_conn());
        } else {
            $error_message = "Failed to schedule appointment. Please try again.";
        }

    } catch (Exception $e) {
        $error_message = "An error occurred: " . $e->getMessage();
        // Log the error for debugging
        error_log("Repair appointment error: " . $e->getMessage());
        error_log("Query: " . $insert_query);
    }
}

try {
    $db = new db_connection();
    $db->db_connect();

    // Get issue and specialist details
    $issue = $db->db_fetch_one("SELECT * FROM repair_issue_types WHERE issue_id = $issue_id");
    $specialist = $db->db_fetch_one("SELECT * FROM specialists WHERE specialist_id = $specialist_id");

    if (!$issue || !$specialist) {
        $error_message = "Unable to load appointment details. Please try again later.";
    }

} catch (Exception $e) {
    $error_message = "Unable to load appointment details. Please try again later.";
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Import Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
            color: #065f46;
            min-height: 100vh;
        }

        /* Animated Background */
        .bg-decoration {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            opacity: 0.6;
        }

        .bg-decoration-1 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
            top: 10%;
            right: 15%;
            animation: float 8s ease-in-out infinite;
        }

        .bg-decoration-2 {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.08), rgba(16, 185, 129, 0.08));
            bottom: 20%;
            left: 10%;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(90deg); }
            50% { transform: translateY(-10px) rotate(180deg); }
            75% { transform: translateY(-15px) rotate(270deg); }
        }

        /* Header */
        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.08);
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #047857;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .garage {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .btn-back {
            background: linear-gradient(135deg, #6b7280, #9ca3af);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #4b5563, #6b7280);
            color: white;
            transform: translateY(-1px);
        }

        /* Progress Steps */
        .hero-section {
            padding: 2rem 0 1rem;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin: 2rem 0;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-weight: 500;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .step.completed .step-number {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #047857, #059669);
            color: white;
        }

        .step-separator {
            width: 3rem;
            height: 2px;
            background: #e5e7eb;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
            position: relative;
            z-index: 10;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #047857;
            margin-bottom: 2rem;
            text-align: center;
        }

        .appointment-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .appointment-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.05);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #047857;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #065f46;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .time-slot {
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .time-slot:hover {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }

        .time-slot.selected {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            color: #047857;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 500;
            color: #6b7280;
        }

        .summary-value {
            font-weight: 600;
            color: #047857;
        }

        .submit-btn {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .success-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .success-content {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            margin: 1rem;
        }

        .calendar-input {
            position: relative;
        }

        .calendar-input input[type="date"] {
            width: 100%;
        }

        @media (max-width: 768px) {
            .appointment-container {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }

            .progress-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .step-separator {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Background Decorations -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>

    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="logo">
                    Gadget<span class="garage">Garage</span>
                </a>
                <a href="repair_specialist.php?issue_id=<?php echo $issue_id; ?>&issue_name=<?php echo urlencode($issue_name); ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Specialists
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <span>Issue Type</span>
                </div>
                <div class="step-separator"></div>
                <div class="step completed">
                    <div class="step-number">2</div>
                    <span>Specialist</span>
                </div>
                <div class="step-separator"></div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <span>Schedule</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <h1 class="section-title">Pick Your Appointment Time</h1>

            <?php if (isset($success_message)): ?>
                <div class="success-modal">
                    <div class="success-content">
                        <i class="fas fa-check-circle" style="font-size: 4rem; color: #10b981; margin-bottom: 1rem;"></i>
                        <h3 style="color: #047857; margin-bottom: 1rem;">Appointment Scheduled!</h3>
                        <p style="color: #6b7280; margin-bottom: 2rem;">Your repair appointment has been successfully scheduled. You will receive an SMS confirmation and we'll call you when it's time for your appointment.</p>
                        <a href="index.php" class="btn btn-primary">Return to Home</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="appointmentForm">
                <div class="appointment-container">
                    <!-- Left Column - Date & Time Selection -->
                    <div class="appointment-card">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Select Date
                        </h3>

                        <div class="form-group">
                            <label class="form-label" for="appointment_date">Preferred Date</label>
                            <div class="calendar-input">
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date"
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                       max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>
                        </div>

                        <h3 class="card-title">
                            <i class="fas fa-clock"></i>
                            Select Time
                        </h3>

                        <div class="form-group">
                            <label class="form-label">Available Time Slots</label>
                            <div class="time-slots">
                                <div class="time-slot" data-time="09:00">9:00 AM</div>
                                <div class="time-slot" data-time="10:00">10:00 AM</div>
                                <div class="time-slot" data-time="11:00">11:00 AM</div>
                                <div class="time-slot" data-time="12:00">12:00 PM</div>
                                <div class="time-slot" data-time="13:00">1:00 PM</div>
                                <div class="time-slot" data-time="14:00">2:00 PM</div>
                                <div class="time-slot" data-time="15:00">3:00 PM</div>
                                <div class="time-slot" data-time="16:00">4:00 PM</div>
                                <div class="time-slot" data-time="17:00">5:00 PM</div>
                            </div>
                            <input type="hidden" id="appointment_time" name="appointment_time" required>
                        </div>

                        <h3 class="card-title">
                            <i class="fas fa-address-book"></i>
                            Contact Information
                        </h3>

                        <div class="form-group">
                            <label class="form-label" for="customer_phone">Phone Number (required)</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone"
                                   placeholder="0XX XXX XXXX" required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                You will receive an SMS confirmation and we'll call you when it's time for your appointment
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="device_info">Device Information (optional)</label>
                            <textarea class="form-control" id="device_info" name="device_info" rows="2"
                                      placeholder="e.g., iPhone 12 Pro, Samsung Galaxy S21, etc."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="issue_description">Additional Details (optional)</label>
                            <textarea class="form-control" id="issue_description" name="issue_description" rows="3"
                                      placeholder="Please describe any additional details about the issue..."></textarea>
                        </div>
                    </div>

                    <!-- Right Column - Appointment Summary -->
                    <div class="appointment-card">
                        <h3 class="card-title">
                            <i class="fas fa-receipt"></i>
                            Appointment Summary
                        </h3>

                        <div class="summary-item">
                            <span class="summary-label">Issue Type:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($issue_name); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Specialist:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($specialist_name); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Date:</span>
                            <span class="summary-value" id="selectedDate">Not selected</span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Time:</span>
                            <span class="summary-value" id="selectedTime">Not selected</span>
                        </div>

                        <?php if ($issue && isset($issue['base_cost'])): ?>
                        <div class="summary-item">
                            <span class="summary-label">Base Cost:</span>
                            <span class="summary-value">GHS <?php echo number_format($issue['base_cost'], 0); ?></span>
                        </div>
                        <?php endif; ?>

                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e2e8f0;">
                            <button type="submit" name="submit_appointment" class="submit-btn" id="submitBtn" disabled>
                                <i class="fas fa-calendar-check me-2"></i>
                                Schedule Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedDate = null;
        let selectedTime = null;

        // Handle date selection
        document.getElementById('appointment_date').addEventListener('change', function() {
            selectedDate = this.value;
            if (selectedDate) {
                const dateObj = new Date(selectedDate);
                const formattedDate = dateObj.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                document.getElementById('selectedDate').textContent = formattedDate;
            } else {
                document.getElementById('selectedDate').textContent = 'Not selected';
            }
            updateSubmitButton();
        });

        // Handle time slot selection
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));

                // Select current slot
                this.classList.add('selected');
                selectedTime = this.getAttribute('data-time');
                document.getElementById('appointment_time').value = selectedTime + ':00';
                document.getElementById('selectedTime').textContent = this.textContent;

                updateSubmitButton();
            });
        });

        // Update submit button state
        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            const phoneInput = document.getElementById('customer_phone');

            if (selectedDate && selectedTime && phoneInput.value.trim()) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Phone input validation
        document.getElementById('customer_phone').addEventListener('input', updateSubmitButton);

        // Form validation
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            if (!selectedDate || !selectedTime) {
                e.preventDefault();
                alert('Please select both date and time for your appointment.');
                return false;
            }

            const phone = document.getElementById('customer_phone').value.trim();
            if (!phone) {
                e.preventDefault();
                alert('Please enter your phone number.');
                return false;
            }
        });
    </script>
</body>
</html>