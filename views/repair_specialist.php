<?php
// Error reporting - log errors but don't display them
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors
ini_set('log_errors', 1);

// Set custom error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<h1>Fatal Error:</h1>";
        echo "<pre>";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
        echo "</pre>";
    }
});

session_start();

// Check if required files exist
$required_files = [
    __DIR__ . '/../settings/core.php',
    __DIR__ . '/../settings/db_class.php',
    __DIR__ . '/../controllers/cart_controller.php',
    __DIR__ . '/../controllers/wishlist_controller.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("Required file missing: " . basename($file));
    }
}

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/wishlist_controller.php');

// Check login status
$is_logged_in = false;
$is_admin = false;

try {
    $is_logged_in = check_login();
    if ($is_logged_in) {
        $is_admin = check_admin();
    }
} catch (Exception $e) {
    error_log("Login check error: " . $e->getMessage());
    $is_logged_in = false;
}

// Get cart count
$cart_count = 0;
try {
    $customer_id = $is_logged_in ? ($_SESSION['user_id'] ?? null) : null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (function_exists('get_cart_count_ctr')) {
        $cart_count = get_cart_count_ctr($customer_id, $ip_address);
    }
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
    $cart_count = 0;
}

// Get wishlist count
$wishlist_count = 0;
try {
    if ($is_logged_in && function_exists('get_wishlist_count_ctr')) {
        $customer_id = $_SESSION['user_id'] ?? null;
        if ($customer_id) {
            $wishlist_count = get_wishlist_count_ctr($customer_id);
        }
    }
} catch (Exception $e) {
    error_log("Wishlist count error: " . $e->getMessage());
    $wishlist_count = 0;
}

// Get issue details from URL
$issue_id = isset($_GET['issue_id']) ? intval($_GET['issue_id']) : 0;
$issue_name = isset($_GET['issue_name']) ? $_GET['issue_name'] : '';

if ($issue_id <= 0) {
    // Redirect to repair services page - same directory
    if (!headers_sent()) {
        header('Location: repair_services.php');
        exit;
    } else {
        echo '<script>window.location.href = "repair_services.php";</script>';
        exit;
    }
}

// Initialize variables
$issue = null;
$specialists = [];
$error_message = null;

try {
    $db = new db_connection();
    
    if (!$db->db_connect()) {
        throw new Exception("Database connection failed");
    }

    // Get issue details - escape the issue_id
    $issue_id_escaped = intval($issue_id); // Already validated as int, but ensure it's safe
    $issue = $db->db_fetch_one("SELECT * FROM repair_issue_types WHERE issue_id = $issue_id_escaped");

    if (!$issue || $issue === false) {
        // If issue not found, redirect back to repair services - same directory
        if (!headers_sent()) {
            header('Location: repair_services.php');
            exit;
        } else {
            echo '<script>window.location.href = "repair_services.php";</script>';
            exit;
        }
    }

    // Get specialists for this issue - escape the issue_id
    $specialists_query = "SELECT s.*, si.issue_id
                         FROM specialists s
                         JOIN specialist_issues si ON s.specialist_id = si.specialist_id
                         WHERE si.issue_id = $issue_id_escaped AND s.is_available = 1
                         ORDER BY s.rating DESC, s.experience_years DESC";
    $specialists_result = $db->db_fetch_all($specialists_query);
    
    // If db_fetch_all returns false or null, set to empty array
    if ($specialists_result === false || $specialists_result === null) {
        $specialists = [];
    } else {
        $specialists = $specialists_result;
    }

} catch (Exception $e) {
    error_log("Repair specialist error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $error_message = "Unable to load specialists. Please try again later.";
    $specialists = [];
    // Don't set $issue to null if we already have it from URL params
    if (!$issue) {
        $issue = null;
    }
} catch (Error $e) {
    // Catch PHP 7+ errors (TypeError, etc.)
    error_log("Repair specialist fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $error_message = "A system error occurred. Please try again later.";
    $specialists = [];
    if (!$issue) {
        $issue = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Specialist - <?php echo htmlspecialchars($issue_name); ?> - Gadget Garage</title>
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
            background-color: var(--pure-white, #ffffff);
            color: #1f2937;
            min-height: 100vh;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #065079 0%, #a6cfed 33%, #006ab8 66%, #70c2ff 100%);
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.97;
            z-index: -1;
            pointer-events: none;
        }

        /* Hide decorative bubbles */
        .bg-decoration,
        .bg-decoration-1,
        .bg-decoration-2 {
            display: none !important;
        }

        /* Promo Banner */
        

        

        .promo-text {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .countdown-timer {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .countdown-timer .time-unit {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            min-width: 24px;
            text-align: center;
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

        /* Header styles are now in header.css */

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
            margin-bottom: 1rem;
            text-align: center;
        }

        .issue-info {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 0 auto 3rem;
            max-width: 600px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .specialists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .specialist-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
        }

        .specialist-card::before {
            display: none;
        }

        .specialist-card:hover {
            border-color: #2563EB;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        .specialist-card.selected {
            border-color: #2563EB;
            background: #eff6ff;
        }

        .specialist-avatar {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563EB, #1e40af);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
        }

        .specialist-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .specialist-specialization {
            color: #6b7280;
            text-align: center;
            margin-bottom: 1.5rem;
            font-style: italic;
        }

        .specialist-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 1.5rem;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2563EB;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 2px;
            margin-bottom: 1rem;
        }

        .star {
            color: #fbbf24;
            font-size: 1rem;
        }

        .star.empty {
            color: #e5e7eb;
        }

        .continue-btn {
            background: linear-gradient(135deg, #2563EB, #1e40af);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            position: fixed;
            bottom: 30px;
            right: 30px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.2s ease;
            z-index: 1000;
            display: none;
        }

        .continue-btn:hover {
            background: linear-gradient(135deg, #1e40af, #2563EB);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        .continue-btn.show {
            display: block;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .no-specialists {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .no-specialists i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .progress-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .step-separator {
                display: none;
            }

            .specialists-grid {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .continue-btn {
                bottom: 20px;
                right: 20px;
                left: 20px;
                width: auto;
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

    <!-- Back to Issues Button -->
    <div class="container mt-3">
        <a href="repair_services.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Issues
        </a>
    </div>

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
                <div class="step active">
                    <div class="step-number">2</div>
                    <span>Specialist</span>
                </div>
                <div class="step-separator"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <span>Schedule</span>
                </div>
            </div>

            <!-- Issue Info -->
            <?php if ($issue): ?>
            <div class="issue-info">
                <h2 style="color: #1e3a8a; margin-bottom: 0.5rem;">
                    <i class="<?php echo htmlspecialchars($issue['icon_class'] ?? 'fas fa-tools'); ?> me-2"></i>
                    <?php echo htmlspecialchars($issue['issue_name'] ?? $issue_name); ?>
                </h2>
                <p style="color: #6b7280; margin: 0;">
                    <?php echo htmlspecialchars($issue['issue_description'] ?? 'Select a specialist to help with your repair issue.'); ?>
                </p>
            </div>
            <?php else: ?>
            <div class="issue-info">
                <h2 style="color: #1e3a8a; margin-bottom: 0.5rem;">
                    <i class="fas fa-tools me-2"></i>
                    <?php echo htmlspecialchars($issue_name); ?>
                </h2>
                <p style="color: #6b7280; margin: 0;">
                    Select a specialist to help with your repair issue.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Specialists Section -->
    <section class="main-content">
        <div class="container">
            <h1 class="section-title">Choose Your Specialist</h1>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($specialists)): ?>
                <div class="no-specialists">
                    <i class="fas fa-user-times"></i>
                    <h4>No Specialists Available</h4>
                    <p>We're sorry, but there are no specialists available for this issue type at the moment.<br>
                       Please contact us directly or try again later.</p>
                    <a href="repair_services.php" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-arrow-left"></i> Choose Different Issue
                    </a>
                </div>
            <?php else: ?>
                <div class="specialists-grid">
                    <?php foreach ($specialists as $specialist): ?>
                        <div class="specialist-card" onclick="selectSpecialist(<?php echo $specialist['specialist_id']; ?>, '<?php echo htmlspecialchars($specialist['specialist_name']); ?>', this)">
                            <div class="specialist-avatar">
                                <?php echo strtoupper(substr($specialist['specialist_name'], 0, 2)); ?>
                            </div>

                            <h3 class="specialist-name"><?php echo htmlspecialchars($specialist['specialist_name']); ?></h3>
                            <p class="specialist-specialization"><?php echo htmlspecialchars($specialist['specialization']); ?></p>

                            <div class="specialist-stats">
                                <div class="stat">
                                    <div class="stat-value"><?php echo $specialist['experience_years']; ?>+</div>
                                    <div class="stat-label">Years Experience</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?php echo number_format($specialist['rating'], 1); ?></div>
                                    <div class="stat-label">Rating</div>
                                </div>
                            </div>

                            <div class="rating-stars">
                                <?php
                                $rating = $specialist['rating'];
                                $fullStars = floor($rating);
                                $halfStar = ($rating - $fullStars) >= 0.5;
                                $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

                                for ($i = 0; $i < $fullStars; $i++) {
                                    echo '<i class="fas fa-star star"></i>';
                                }
                                if ($halfStar) {
                                    echo '<i class="fas fa-star-half-alt star"></i>';
                                }
                                for ($i = 0; $i < $emptyStars; $i++) {
                                    echo '<i class="fas fa-star star empty"></i>';
                                }
                                ?>
                            </div>

                            <?php if ($specialist['specialist_email']): ?>
                                <p style="text-align: center; color: #6b7280; font-size: 0.9rem; margin: 0;">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?php echo htmlspecialchars($specialist['specialist_email']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Continue Button -->
    <button class="continue-btn" id="continueBtn" onclick="proceedToSchedule()">
        Continue
        <i class="fas fa-arrow-right ms-2"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSpecialist = null;
        let selectedSpecialistName = '';

        function selectSpecialist(specialistId, specialistName, element) {
            // Remove previous selection
            document.querySelectorAll('.specialist-card').forEach(card => {
                card.classList.remove('selected');
                card.style.background = '';
                card.style.border = '';
            });

            // Select current specialist
            const card = element || event?.currentTarget;
            if (card) {
                card.classList.add('selected');
                card.style.background = '#eff6ff';
                card.style.border = '2px solid #2563EB';
            }

            selectedSpecialist = specialistId;
            selectedSpecialistName = specialistName;

            // Show continue button
            const continueBtn = document.getElementById('continueBtn');
            if (continueBtn) {
                continueBtn.classList.add('show');
            }
        }

        function proceedToSchedule() {
            if (selectedSpecialist) {
                const urlParams = new URLSearchParams(window.location.search);
                const issueId = urlParams.get('issue_id');
                const issueName = urlParams.get('issue_name');

                window.location.href = `repair_schedule.php?issue_id=${issueId}&issue_name=${encodeURIComponent(issueName)}&specialist_id=${selectedSpecialist}&specialist_name=${encodeURIComponent(selectedSpecialistName)}`;
            }
        }

        // Add hover effects
        document.querySelectorAll('.specialist-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('selected')) {
                    this.style.background = '#f8fafc';
                }
            });

            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('selected')) {
                    this.style.background = '';
                }
            });
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