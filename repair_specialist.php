<?php
session_start();
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/settings/db_class.php';

// Get issue details from URL
$issue_id = isset($_GET['issue_id']) ? intval($_GET['issue_id']) : 0;
$issue_name = isset($_GET['issue_name']) ? $_GET['issue_name'] : '';

if ($issue_id <= 0) {
    header('Location: repair_services.php');
    exit;
}

try {
    $db = new db_connection();
    $db->db_connect();

    // Get issue details
    $issue = $db->db_fetch_one("SELECT * FROM repair_issue_types WHERE issue_id = ?", [$issue_id]);

    if (!$issue) {
        header('Location: repair_services.php');
        exit;
    }

    // Get specialists for this issue
    $specialists_query = "SELECT s.*, si.issue_id
                         FROM specialists s
                         JOIN specialist_issues si ON s.specialist_id = si.specialist_id
                         WHERE si.issue_id = ? AND s.is_available = 1
                         ORDER BY s.rating DESC, s.experience_years DESC";
    $specialists = $db->db_fetch_all($specialists_query, [$issue_id]);

} catch (Exception $e) {
    $error_message = "Unable to load specialists. Please try again later.";
    $specialists = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Specialist - <?php echo htmlspecialchars($issue_name); ?> - Gadget Garage</title>
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
            margin-bottom: 1rem;
            text-align: center;
        }

        .issue-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 0 auto 3rem;
            max-width: 600px;
            text-align: center;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.05);
        }

        .specialists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .specialist-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .specialist-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #10b981, #34d399);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .specialist-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.15);
        }

        .specialist-card:hover::before {
            transform: scaleX(1);
        }

        .specialist-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #34d399);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            font-weight: 700;
            color: white;
        }

        .specialist-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #047857;
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
            font-size: 1.2rem;
            font-weight: 700;
            color: #047857;
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
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            position: fixed;
            bottom: 30px;
            right: 30px;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            display: none;
        }

        .continue-btn:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
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
                <a href="repair_services.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Issues
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
            <div class="issue-info">
                <h2 style="color: #047857; margin-bottom: 0.5rem;">
                    <i class="<?php echo htmlspecialchars($issue['icon_class']); ?> me-2"></i>
                    <?php echo htmlspecialchars($issue['issue_name']); ?>
                </h2>
                <p style="color: #6b7280; margin: 0;">
                    <?php echo htmlspecialchars($issue['issue_description']); ?>
                </p>
            </div>
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
                        <div class="specialist-card" onclick="selectSpecialist(<?php echo $specialist['specialist_id']; ?>, '<?php echo htmlspecialchars($specialist['specialist_name']); ?>')">
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

        function selectSpecialist(specialistId, specialistName) {
            // Remove previous selection
            document.querySelectorAll('.specialist-card').forEach(card => {
                card.classList.remove('selected');
                card.style.background = '';
                card.style.border = '';
            });

            // Select current specialist
            event.currentTarget.classList.add('selected');
            event.currentTarget.style.background = 'linear-gradient(135deg, #ecfdf5, #d1fae5)';
            event.currentTarget.style.border = '2px solid #10b981';

            selectedSpecialist = specialistId;
            selectedSpecialistName = specialistName;

            // Show continue button
            document.getElementById('continueBtn').classList.add('show');
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
                    this.style.background = 'linear-gradient(135deg, #f8fafc, #f1f5f9)';
                }
            });

            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('selected')) {
                    this.style.background = '';
                }
            });
        });
    </script>
</body>
</html>