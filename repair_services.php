<?php
session_start();
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/settings/db_class.php';

// Get repair issue types
try {
    $db = new db_connection();
    $db->db_connect();
    $issue_types = $db->db_fetch_all("SELECT * FROM repair_issue_types ORDER BY issue_name");
} catch (Exception $e) {
    $issue_types = [];
    $error_message = "Unable to load repair services. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Repair Services - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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

        /* Main Content */
        .hero-section {
            padding: 4rem 0 2rem;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            color: #047857;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #065f46;
            margin-bottom: 0.5rem;
        }

        .hero-description {
            color: #6b7280;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin: 3rem 0;
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
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #047857, #059669);
        }

        .step-separator {
            width: 3rem;
            height: 2px;
            background: #e5e7eb;
        }

        /* Issue Types Grid */
        .issues-section {
            padding: 2rem 0;
            position: relative;
            z-index: 10;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #047857;
            margin-bottom: 3rem;
        }

        .issues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .issue-card {
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

        .issue-card::before {
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

        .issue-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.15);
        }

        .issue-card:hover::before {
            transform: scaleX(1);
        }

        .issue-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .issue-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #047857;
            margin-bottom: 1rem;
            text-align: center;
        }

        .issue-description {
            color: #6b7280;
            text-align: center;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .issue-price {
            text-align: center;
            margin-top: auto;
        }

        .price-range {
            font-size: 1.1rem;
            font-weight: 600;
            color: #059669;
        }

        .price-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 0.25rem;
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

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .progress-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .step-separator {
                display: none;
            }

            .issues-grid {
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
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <i class="fas fa-tools me-2" style="color: #10b981; font-size: 1.5rem;"></i>
                <span style="color: #10b981; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Professional Repair Services</span>
            </div>

            <h1 class="hero-title">Device Repair Services</h1>
            <p class="hero-subtitle">Get your device repaired by certified experts. Schedule an appointment</p>
            <p class="hero-description">within 24 hours and receive expert care.</p>

            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <span>Issue Type</span>
                </div>
                <div class="step-separator"></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <span>Specialist</span>
                </div>
                <div class="step-separator"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <span>Schedule</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Issues Section -->
    <section class="issues-section">
        <div class="container">
            <h2 class="section-title">What's wrong with your device?</h2>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="issues-grid">
                <?php foreach ($issue_types as $issue): ?>
                    <div class="issue-card" onclick="selectIssue(<?php echo $issue['issue_id']; ?>, '<?php echo htmlspecialchars($issue['issue_name']); ?>')">
                        <div class="issue-icon">
                            <i class="<?php echo htmlspecialchars($issue['icon_class']); ?>"></i>
                        </div>
                        <h3 class="issue-title"><?php echo htmlspecialchars($issue['issue_name']); ?></h3>
                        <p class="issue-description"><?php echo htmlspecialchars($issue['issue_description']); ?></p>
                        <div class="issue-price">
                            <div class="price-range">
                                GHS <?php echo number_format($issue['estimated_cost_min'], 0); ?> -
                                <?php echo number_format($issue['estimated_cost_max'], 0); ?>
                            </div>
                            <div class="price-label">Estimated Cost</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Continue Button -->
    <button class="continue-btn" id="continueBtn" onclick="proceedToSpecialist()">
        Continue
        <i class="fas fa-arrow-right ms-2"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedIssue = null;
        let selectedIssueName = '';

        function selectIssue(issueId, issueName) {
            // Remove previous selection
            document.querySelectorAll('.issue-card').forEach(card => {
                card.classList.remove('selected');
                card.style.background = '';
                card.style.border = '';
            });

            // Select current issue
            event.currentTarget.classList.add('selected');
            event.currentTarget.style.background = 'linear-gradient(135deg, #ecfdf5, #d1fae5)';
            event.currentTarget.style.border = '2px solid #10b981';

            selectedIssue = issueId;
            selectedIssueName = issueName;

            // Show continue button
            document.getElementById('continueBtn').classList.add('show');
        }

        function proceedToSpecialist() {
            if (selectedIssue) {
                window.location.href = `repair_specialist.php?issue_id=${selectedIssue}&issue_name=${encodeURIComponent(selectedIssueName)}`;
            }
        }

        // Add hover effects
        document.querySelectorAll('.issue-card').forEach(card => {
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