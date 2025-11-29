<?php
session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';

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
        $customer_phone = trim($_POST['customer_phone']);
        // Remove formatting from phone number (parentheses, dashes, spaces) for SMS
        $customer_phone_clean = preg_replace('/[^0-9+]/', '', $customer_phone);
        $device_info = $_POST['device_info'] ?? '';
        $issue_description = $_POST['issue_description'] ?? '';

        // Server-side validation: Check for Sunday appointments
        $dateObj = DateTime::createFromFormat('Y-m-d', $appointment_date);
        if ($dateObj && $dateObj->format('w') == 0) { // 0 = Sunday
            throw new Exception('Appointments are not available on Sundays. Please select a weekday.');
        }

        // Server-side validation: Check for appointments after 5 PM
        $timeObj = DateTime::createFromFormat('H:i:s', $appointment_time);
        if ($timeObj && $timeObj->format('H') >= 17) { // 17:00 = 5:00 PM
            throw new Exception('Appointments are not available after 5:00 PM. Please select an earlier time.');
        }

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
                        (customer_id, specialist_id, issue_id, appointment_date, appointment_time,
                         customer_phone, device_info, issue_description, status)
                        VALUES ($customer_id, $specialist_id, $issue_id, '$appointment_date', '$appointment_time',
                               '$customer_phone', '$device_info', '$issue_description', 'scheduled')";

        $result = $db->db_write_query($insert_query);

        if ($result) {
            $success_message = "Your appointment has been scheduled successfully!";
            // Get the last inserted ID using mysqli_insert_id
            $appointment_id = mysqli_insert_id($db->db_conn());
            
            // Send SMS confirmation if SMS is enabled
            if (defined('SMS_ENABLED') && SMS_ENABLED) {
                try {
                    require_once __DIR__ . '/../helpers/sms_helper.php';
                    
                    // Get customer name
                    $customer_name = 'Customer';
                    $customer_id_int = ($customer_id && $customer_id !== 'NULL') ? intval($customer_id) : null;
                    if ($customer_id_int) {
                        $customer_query = "SELECT customer_name FROM customer WHERE customer_id = $customer_id_int";
                        $customer_data = $db->db_fetch_one($customer_query);
                        if ($customer_data) {
                            $customer_name = $customer_data['customer_name'];
                        }
                    }
                    
                    // Get specialist and issue names from database
                    $specialist_query = "SELECT specialist_name FROM specialists WHERE specialist_id = $specialist_id";
                    $specialist_data = $db->db_fetch_one($specialist_query);
                    $specialist_name = $specialist_data['specialist_name'] ?? 'Our Specialist';
                    
                    $issue_query = "SELECT issue_name FROM repair_issue_types WHERE issue_id = $issue_id";
                    $issue_data = $db->db_fetch_one($issue_query);
                    $issue_name = $issue_data['issue_name'] ?? 'Device Repair';
                    
                    // Send SMS - use cleaned phone number
                    error_log("Attempting to send appointment SMS - Appointment ID: $appointment_id, Original Phone: $customer_phone, Cleaned Phone: $customer_phone_clean, Name: $customer_name");
                    $sms_sent = send_appointment_confirmation_sms(
                        $appointment_id,
                        $customer_name,
                        $customer_phone_clean, // Use cleaned phone number
                        $appointment_date,
                        $appointment_time,
                        $specialist_name,
                        $issue_name
                    );
                    
                    error_log("SMS send result: " . ($sms_sent ? 'SUCCESS' : 'FAILED'));
                    
                    if ($sms_sent) {
                        $success_message .= " A confirmation SMS has been sent to your phone.";
                    } else {
                        error_log("SMS sending failed for appointment ID: $appointment_id");
                    }
                } catch (Exception $sms_error) {
                    // SMS error is not critical, continue
                    error_log('Appointment SMS error: ' . $sms_error->getMessage());
                    error_log('SMS error stack trace: ' . $sms_error->getTraceAsString());
                }
            }
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
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
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
            background: #ffffff;
            color: #1f2937;
            min-height: 100vh;
        }

        /* Hide decorative bubbles */
        .bg-decoration,
        .bg-decoration-1,
        .bg-decoration-2 {
            display: none !important;
        }

        /* Header styles now imported from header-styles.css */

        .btn-back {
            background: #64748b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #475569;
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
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #e5e7eb;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .step.completed .step-number {
            background: #10b981;
            color: white;
        }

        .step.active .step-number {
            background: #2563EB;
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
            font-size: 2rem;
            font-weight: 700;
            color: #1e3a8a;
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
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
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
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
            font-weight: 500;
        }

        .time-slot:hover {
            border-color: #2563EB;
            background: #eff6ff;
        }

        .time-slot.selected {
            border-color: #2563EB;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .time-slot.unavailable {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-color: #f87171;
            color: #dc2626;
            cursor: not-allowed;
            opacity: 0.7;
            position: relative;
        }

        .time-slot.unavailable:hover {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-color: #f87171;
            color: #dc2626;
            transform: none;
        }

        .time-slot.unavailable::after {
            content: "Booked";
            position: absolute;
            bottom: 2px;
            right: 2px;
            background: #dc2626;
            color: white;
            font-size: 0.65rem;
            padding: 1px 4px;
            border-radius: 3px;
            font-weight: 600;
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
            color: #2563EB;
        }

        .submit-btn {
            background: linear-gradient(135deg, #2563EB, #1e40af);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s ease;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #1e40af, #2563EB);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Terms and Conditions Styling */
        .terms-conditions {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .terms-conditions .form-check {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .terms-conditions .form-check:hover {
            border-color: #2563EB;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }

        .terms-conditions .form-check-input {
            border: 2px solid #d1d5db;
            border-radius: 4px;
            margin-top: 0.25rem;
            transform: scale(1.1);
        }

        .terms-conditions .form-check-input:checked {
            background-color: #2563EB;
            border-color: #2563EB;
        }

        .terms-conditions .form-check-input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }

        .terms-conditions .form-check-label {
            font-size: 0.9rem;
            color: #374151;
            line-height: 1.5;
            margin-left: 0.5rem;
            cursor: pointer;
        }

        .terms-conditions .form-check-label:hover {
            color: #2563EB;
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

        /* Footer Styles */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 0;
        }

        .footer-brand {
            margin-bottom: 30px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo img {
            height: 50px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .footer-logo .garage {
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .footer-description {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social-link:hover {
            background: #2563EB;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 24px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 14px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #2563EB;
            transform: translateX(4px);
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 40px 0 20px;
        }

        .footer-bottom {
            padding-top: 20px;
        }

        .copyright {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }

        /* Newsletter Signup Section */
        .newsletter-signup-section {
            background: transparent;
            padding: 0;
            text-align: left;
            max-width: 100%;
            height: fit-content;
        }

        .newsletter-title {
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .newsletter-form {
            display: flex;
            width: 100%;
            margin: 0 0 15px 0;
            gap: 0;
            border-radius: 50px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .newsletter-input {
            flex: 1;
            padding: 14px 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1a1a1a;
            background: #e5e7eb;
        }

        .newsletter-input::placeholder {
            color: #6b7280;
        }

        .newsletter-submit-btn {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border: none;
            background: #9ca3af;
            color: #ffffff;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            padding: 0;
        }

        .newsletter-submit-btn:hover {
            background: #6b7280;
            transform: scale(1.05);
        }

        .newsletter-disclaimer {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 8px 0 0 0;
            text-align: left;
        }

        .newsletter-disclaimer a {
            color: #2563EB;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .newsletter-disclaimer a:hover {
            color: #1d4ed8;
        }

        @media (max-width: 991px) {
            .newsletter-signup-section {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <!-- Background Decorations -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="../index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                         alt="Gadget Garage"
                         style="height: 40px; width: auto; object-fit: contain;">
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
                        <i class="fas fa-check-circle" style="font-size: 4rem; color: #2563EB; margin-bottom: 1rem;"></i>
                        <h3 style="color: #1e3a8a; margin-bottom: 1rem;">Appointment Scheduled!</h3>
                        <p style="color: #6b7280; margin-bottom: 2rem;">Your repair appointment has been successfully scheduled. You will receive an SMS confirmation and we'll call you when it's time for your appointment.</p>
                        <a href="../index.php" class="btn btn-primary">Return to Home</a>
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
                            <label class="form-label" for="issue_description">Describe the issue with your device</label>
                            <textarea class="form-control" id="issue_description" name="issue_description" rows="4"
                                      placeholder="e.g., My iPhone 12 battery drains very fast and sometimes the phone gets hot near the camera..."></textarea>
                            <div style="margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                <button type="button" id="analyzeIssueBtn"
                                        style="background: linear-gradient(135deg, #2563EB, #1e40af); color: white; border: none; padding: 10px 18px; border-radius: 999px; font-size: 0.95rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <i class="fas fa-brain"></i>
                                    Analyze my Issue
                                </button>
                                <small class="text-muted" style="font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-info-circle"></i>
                                    AI will suggest a likely issue and an estimated repair range. This is not a final quote.
                                </small>
                            </div>
                        </div>

                        <div id="aiDiagnosisCard" style="display: none; margin-bottom: 1.5rem;">
                            <div style="border-radius: 12px; border: 1px solid #bfdbfe; background: linear-gradient(135deg, #eff6ff, #ffffff); padding: 1.25rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.75rem;">
                                    <div style="width: 34px; height: 34px; border-radius: 999px; background: #1d4ed8; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-microchip"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: #1f2937; font-size: 0.98rem;">AI Repair Assistant (Estimate)</div>
                                        <div style="font-size: 0.8rem; color: #6b7280;">This is an estimate only. Final diagnosis and pricing will be confirmed by a technician.</div>
                                    </div>
                                </div>
                                <div id="aiDiagnosisContent" style="font-size: 0.9rem; color: #111827;">
                                    <!-- Filled by JS -->
                                </div>
                            </div>
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
                            <span class="summary-value">GH₵ <?php echo number_format($issue['base_cost'], 0); ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Terms and Conditions -->
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e2e8f0;">
                            <h6 style="color: #1e3a8a; font-weight: 600; margin-bottom: 1rem;">
                                <i class="fas fa-file-contract me-2"></i>
                                Terms & Conditions
                            </h6>

                            <div class="terms-conditions">
                                <div class="form-check mb-3">
                                    <input class="form-check-input terms-checkbox" type="checkbox" id="terms1" required>
                                    <label class="form-check-label" for="terms1">
                                        I understand that I will receive a phone call from the assigned specialist at my scheduled time to discuss my device issue in detail and determine the best repair solution.
                                    </label>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input terms-checkbox" type="checkbox" id="terms2" required>
                                    <label class="form-check-label" for="terms2">
                                        I agree that the consultation call may be recorded for quality assurance and training purposes, and that pricing, timeframe, and service details will be discussed during this call.
                                    </label>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input terms-checkbox" type="checkbox" id="terms3" required>
                                    <label class="form-check-label" for="terms3">
                                        I understand that pickup/drop-off arrangements will be discussed during the consultation call, and I am responsible for coordinating device collection and delivery within Accra and surrounding areas.
                                    </label>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input terms-checkbox" type="checkbox" id="terms4" required>
                                    <label class="form-check-label" for="terms4">
                                        I acknowledge that final repair costs may vary from the base cost shown above based on the actual diagnosis, and I will be informed of any additional charges before work begins.
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let selectedDate = null;
        let selectedTime = null;

        // Handle date selection
        document.getElementById('appointment_date').addEventListener('change', function() {
            selectedDate = this.value;
            if (selectedDate) {
                const dateObj = new Date(selectedDate);
                const dayOfWeek = dateObj.getDay(); // 0 = Sunday, 1 = Monday, etc.

                // Check if selected date is a Sunday
                if (dayOfWeek === 0) {
                    // Sunday selected - show error and reset
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Sunday Not Available',
                            text: 'Appointments are not available on Sundays. Please select a weekday.',
                            icon: 'warning',
                            confirmButtonColor: '#D19C97',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Appointments are not available on Sundays. Please select a weekday.');
                    }
                    this.value = ''; // Reset the date input
                    selectedDate = null;
                    document.getElementById('selectedDate').textContent = 'Not selected';
                } else {
                    const formattedDate = dateObj.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    document.getElementById('selectedDate').textContent = formattedDate;
                }
            } else {
                document.getElementById('selectedDate').textContent = 'Not selected';
            }
            updateSubmitButton();
        });

        // Randomly mark some time slots as unavailable
        function markRandomSlotsUnavailable() {
            const timeSlots = document.querySelectorAll('.time-slot');
            const slotsToMakeUnavailable = Math.floor(Math.random() * 3) + 1; // 1-3 random slots
            const unavailableSlots = [];

            // Randomly select slots to make unavailable
            while (unavailableSlots.length < slotsToMakeUnavailable) {
                const randomIndex = Math.floor(Math.random() * timeSlots.length);
                if (!unavailableSlots.includes(randomIndex)) {
                    unavailableSlots.push(randomIndex);
                }
            }

            // Mark selected slots as unavailable
            unavailableSlots.forEach(index => {
                timeSlots[index].classList.add('unavailable');
            });
        }

        // Handle time slot selection
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                // Check if slot is unavailable
                if (this.classList.contains('unavailable')) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Time Slot Unavailable',
                            text: 'This time slot is already booked. Please select another available time.',
                            icon: 'warning',
                            confirmButtonColor: '#D19C97',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('This time slot is already booked. Please select another available time.');
                    }
                    return;
                }

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

            // Check if all terms checkboxes are checked
            const termsCheckboxes = document.querySelectorAll('.terms-checkbox');
            const allTermsChecked = Array.from(termsCheckboxes).every(checkbox => checkbox.checked);

            if (selectedDate && selectedTime && phoneInput.value.trim() && allTermsChecked) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Phone input validation
        document.getElementById('customer_phone').addEventListener('input', updateSubmitButton);

        // Terms checkbox validation
        document.addEventListener('DOMContentLoaded', function() {
            const termsCheckboxes = document.querySelectorAll('.terms-checkbox');
            termsCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSubmitButton);
            });

            // Mark random time slots as unavailable when page loads
            markRandomSlotsUnavailable();
        });

        // AI Repair Analysis
        const analyzeBtn = document.getElementById('analyzeIssueBtn');
        const issueTextarea = document.getElementById('issue_description');
        const deviceInfoInput = document.getElementById('device_info');
        const aiCard = document.getElementById('aiDiagnosisCard');
        const aiContent = document.getElementById('aiDiagnosisContent');

        if (analyzeBtn && issueTextarea) {
            analyzeBtn.addEventListener('click', function () {
                const issueText = issueTextarea.value.trim();

                if (!issueText) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Describe Your Issue',
                            text: 'Please describe the problem with your device before using AI analysis.',
                            icon: 'info',
                            confirmButtonColor: '#2563EB',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Please describe the problem with your device before using AI analysis.');
                    }
                    issueTextarea.focus();
                    return;
                }

                const originalText = analyzeBtn.innerHTML;
                analyzeBtn.disabled = true;
                analyzeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';

                const deviceInfo = deviceInfoInput ? deviceInfoInput.value.trim() : '';
                let deviceType = '';
                let brand = '';
                let model = '';

                if (deviceInfo) {
                    deviceType = 'Device';
                    const parts = deviceInfo.split(' ');
                    if (parts.length >= 1) {
                        brand = parts[0];
                    }
                    if (parts.length >= 2) {
                        model = parts.slice(1).join(' ');
                    }
                }

                let baseCost = null;
                <?php if ($issue && isset($issue['base_cost'])): ?>
                baseCost = <?php echo json_encode($issue['base_cost']); ?>;
                <?php endif; ?>

                const formData = new FormData();
                formData.append('device_type', deviceType);
                formData.append('brand', brand);
                formData.append('model', model);
                formData.append('issue_description', issueText);
                if (baseCost !== null) {
                    formData.append('base_cost', baseCost);
                }

                fetch('../actions/repair_ai_diagnosis.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.analysis) {
                            const a = data.analysis;

                            let urgencyBadge = '';
                            const urgency = (a.urgency || '').toLowerCase();
                            if (urgency === 'high') {
                                urgencyBadge = '<span style="display:inline-flex;align-items:center;gap:6px;background:#fee2e2;color:#b91c1c;padding:4px 10px;border-radius:999px;font-size:0.75rem;font-weight:600;"><i class="fas fa-exclamation-triangle"></i> High urgency</span>';
                            } else if (urgency === 'medium') {
                                urgencyBadge = '<span style="display:inline-flex;align-items:center;gap:6px;background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:999px;font-size:0.75rem;font-weight:600;"><i class="fas fa-exclamation-circle"></i> Medium urgency</span>';
                            } else if (urgency === 'low') {
                                urgencyBadge = '<span style="display:inline-flex;align-items:center;gap:6px;background:#ecfdf5;color:#047857;padding:4px 10px;border-radius:999px;font-size:0.75rem;font-weight:600;"><i class="fas fa-info-circle"></i> Low urgency</span>';
                            }

                            // **NEW: Show SweetAlert popup FIRST with AI analysis**
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: '<i class="fas fa-brain" style="color: #2563EB;"></i> AI Repair Analysis',
                                    html: `
                                        <div style="text-align: left; padding: 0.5rem;">
                                            <div style="margin-bottom: 1rem;">
                                                <div style="font-size: 0.9rem; color: #6b7280; margin-bottom: 0.25rem;">Likely Issue:</div>
                                                <div style="font-weight: 600; color: #1f2937;">${a.likely_issue || 'AI could not determine the issue clearly.'}</div>
                                            </div>
                                            <div style="margin-bottom: 1rem;">
                                                <div style="font-size: 0.9rem; color: #6b7280; margin-bottom: 0.25rem;">Recommended Repair:</div>
                                                <div style="font-weight: 600; color: #1f2937;">${a.recommended_repair_type || 'Will be confirmed during diagnosis.'}</div>
                                            </div>
                                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
                                                ${a.estimated_cost_range ? `<span style="background:#dbeafe;color:#1e40af;padding:6px 12px;border-radius:999px;font-size:0.85rem;font-weight:600;"><i class="fas fa-money-bill-wave"></i> ${a.estimated_cost_range}</span>` : ''}
                                                ${a.estimated_time ? `<span style="background:#e0e7ff;color:#4338ca;padding:6px 12px;border-radius:999px;font-size:0.85rem;font-weight:600;"><i class="fas fa-clock"></i> ${a.estimated_time}</span>` : ''}
                                            </div>
                                            ${urgencyBadge ? `<div style="margin-bottom: 1rem;">${urgencyBadge}</div>` : ''}
                                            ${a.notes ? `<div style="padding: 0.75rem; background: #f9fafb; border-left: 3px solid #2563EB; border-radius: 4px; margin-top: 1rem;"><div style="font-size: 0.85rem; color: #4b5563;"><strong>Note:</strong> ${a.notes}</div></div>` : ''}
                                        </div>
                                    `,
                                    icon: 'info',
                                    confirmButtonColor: '#2563EB',
                                    confirmButtonText: 'Got it!',
                                    width: '600px'
                                });
                            }

                            // **THEN: Display in the card below as before**
                            aiContent.innerHTML = `
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <div>
                                        <div style="font-size:0.8rem;color:#6b7280;margin-bottom:4px;">Likely issue</div>
                                        <div style="font-weight:600;">${a.likely_issue || 'AI could not determine the issue clearly.'}</div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.8rem;color:#6b7280;margin-bottom:4px;">Recommended repair type</div>
                                        <div style="font-weight:600;">${a.recommended_repair_type || 'Will be confirmed during diagnosis.'}</div>
                                    </div>
                                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:4px;">
                                        ${a.estimated_cost_range ? `<span style="background:#eff6ff;color:#1d4ed8;padding:6px 10px;border-radius:999px;font-size:0.8rem;font-weight:600;"><i class="fas fa-money-bill-wave"></i> ${a.estimated_cost_range}</span>` : ''}
                                        ${a.estimated_time ? `<span style="background:#f3f4f6;color:#374151;padding:6px 10px;border-radius:999px;font-size:0.8rem;font-weight:600;"><i class="fas fa-clock"></i> ${a.estimated_time}</span>` : ''}
                                        ${urgencyBadge}
                                    </div>
                                    ${a.notes ? `<div style="margin-top:6px;font-size:0.85rem;color:#374151;">${a.notes}</div>` : ''}
                                </div>
                            `;

                            aiCard.style.display = 'block';
                        } else {
                            const msg = data.message || 'AI could not analyze your issue. Please try again or continue without it.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'AI Assistant Unavailable',
                                    text: msg,
                                    icon: 'error',
                                    confirmButtonColor: '#2563EB',
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                alert(msg);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('AI diagnosis error:', error);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'AI Error',
                                text: 'Something went wrong while analyzing your issue. You can still schedule your appointment.',
                                icon: 'error',
                                confirmButtonColor: '#2563EB',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            alert('Something went wrong while analyzing your issue. You can still schedule your appointment.');
                        }
                    })
                    .finally(() => {
                        analyzeBtn.disabled = false;
                        analyzeBtn.innerHTML = originalText;
                    });
            });
        }

        // Form validation
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            if (!selectedDate || !selectedTime) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Incomplete Selection',
                        text: 'Please select both date and time for your appointment.',
                        icon: 'warning',
                        confirmButtonColor: '#D19C97',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({title: 'Missing Information', text: 'Please select both date and time for your appointment.', icon: 'warning', confirmButtonColor: '#ffc107', confirmButtonText: 'OK'});
                }
                return false;
            }

            const phone = document.getElementById('customer_phone').value.trim();
            if (!phone) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Phone Required',
                        text: 'Please enter your phone number.',
                        icon: 'warning',
                        confirmButtonColor: '#D19C97',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({title: 'Missing Information', text: 'Please enter your phone number.', icon: 'warning', confirmButtonColor: '#ffc107', confirmButtonText: 'OK'});
                }
                return false;
            }

            // Check if all terms are accepted
            const termsCheckboxes = document.querySelectorAll('.terms-checkbox');
            const allTermsChecked = Array.from(termsCheckboxes).every(checkbox => checkbox.checked);
            if (!allTermsChecked) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Terms Required',
                        text: 'Please accept all terms and conditions before scheduling your appointment.',
                        icon: 'warning',
                        confirmButtonColor: '#D19C97',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({title: 'Terms Required', text: 'Please accept all terms and conditions before scheduling your appointment.', icon: 'warning', confirmButtonColor: '#ffc107', confirmButtonText: 'OK'});
                }
                return false;
            }
        });
    </script>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row align-items-start">
                    <!-- First Column: Logo and Social -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-brand">
                            <div class="footer-logo" style="margin-bottom: 20px;">
                                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                                    alt="Gadget Garage">
                            </div>
                            <p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- Navigation Links -->
                    <div class="col-lg-5 col-md-12">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Get Help</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Help Center</a></li>
                                    <li><a href="contact.php">Track Order</a></li>
                                    <li><a href="terms_conditions.php">Shipping Info</a></li>
                                    <li><a href="terms_conditions.php">Returns</a></li>
                                    <li><a href="contact.php">Contact Us</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Company</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Careers</a></li>
                                    <li><a href="contact.php">About</a></li>
                                    <li><a href="contact.php">Stores</a></li>
                                    <li><a href="contact.php">Want to Collab?</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Quick Links</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Size Guide</a></li>
                                    <li><a href="contact.php">Sitemap</a></li>
                                    <li><a href="contact.php">Gift Cards</a></li>
                                    <li><a href="contact.php">Check Gift Card Balance</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Right Side: Email Signup Form -->
                    <div class="col-lg-4 col-md-12 mb-4">
                        <div class="newsletter-signup-section">
                            <h3 class="newsletter-title">SIGN UP FOR DISCOUNTS + UPDATES</h3>
                            <form class="newsletter-form" id="newsletterForm">
                                <input type="text" class="newsletter-input" placeholder="Phone Number or Email" required>
                                <button type="submit" class="newsletter-submit-btn">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                            <p class="newsletter-disclaimer">
                                By signing up for email, you agree to Gadget Garage's <a href="terms_conditions.php">Terms of Service</a> and <a href="legal.php">Privacy Policy</a>.
                            </p>
                            <p class="newsletter-disclaimer">
                                By submitting your phone number, you agree to receive recurring automated promotional and personalized marketing text messages (e.g. cart reminders) from Gadget Garage at the cell number used when signing up. Consent is not a condition of any purchase. Reply HELP for help and STOP to cancel. Msg frequency varies. Msg & data rates may apply. <a href="terms_conditions.php">View Terms</a> & <a href="legal.php">Privacy</a>.
                            </p>
                        </div>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-12 text-center">
                            <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>