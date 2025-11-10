<!DOCTYPE html>
<html lang="en">
<?php
// --- server-side handler (kept minimal) ---
session_start();
require_once __DIR__ . '/../controllers/user_controller.php';

$reg_success = '';
$reg_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read posted fields
    $name         = trim($_POST['name']         ?? '');
    $email        = trim($_POST['email']        ?? '');
    $password     =        $_POST['password']   ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $country      = trim($_POST['country']      ?? '');
    $city         = trim($_POST['city']         ?? '');
    $role         = (int)($_POST['role']        ?? 1);

    // call your existing controller
    $res = register_user_ctr($name, $email, $password, $phone_number, $country, $city, $role);

    if (is_array($res) && ($res['status'] ?? '') === 'success') {
        $reg_success = $res['message'] ?? 'Registration successful. You can now log in.';
        // If you prefer an immediate redirect to login, uncomment:
        // header('Location: login.php'); exit;
    } else {
        $reg_error = is_array($res) ? ($res['message'] ?? 'Registration failed') : 'Registration failed';
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>Register - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Shapes */
        body::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: 10%;
            left: 10%;
            animation: float1 6s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: 10%;
            right: 15%;
            animation: float2 8s ease-in-out infinite reverse;
        }

        @keyframes float1 {

            0%,
            100% {
                transform: translateY(0px) translateX(0px);
            }

            33% {
                transform: translateY(-30px) translateX(20px);
            }

            66% {
                transform: translateY(20px) translateX(-15px);
            }
        }

        @keyframes float2 {

            0%,
            100% {
                transform: translateY(0px) translateX(0px);
            }

            50% {
                transform: translateY(-25px) translateX(25px);
            }
        }

        /* Main Container */
        .register-container {
            position: absolute;
            left: 45%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            z-index: 10;
            padding: 40px 35px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-header {
            margin-bottom: 30px;
        }

        .form-header .circle-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .form-title {
            font-size: 2rem;
            color: #8b5fbf;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 400;
        }

        /* Form Styling */
        .form-group,
        .mb-3 {
            margin-bottom: 18px;
        }

        .form-group label,
        .form-label {
            display: block;
            font-size: 1rem;
            color: #666;
            margin-bottom: 8px;
            font-weight: 400;
        }

        .form-control {
            width: 100%;
            padding: 12px 18px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control::placeholder {
            color: #bbb;
            font-weight: 300;
            opacity: 0.7;
        }

        .form-control:focus {
            border-color: #8b5fbf;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
            background: white;
        }

        /* Password field with eye icon */
        .password-container {
            position: relative;
        }

        .password-container .form-control {
            padding-right: 50px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #8b5fbf;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #764ba2;
        }

        /* Radio Button Styling */
        .form-check {
            margin-bottom: 10px;
        }

        .form-check-input {
            accent-color: #8b5fbf;
        }

        .form-check-label {
            color: #666;
            font-size: 0.95rem;
            margin-left: 8px;
        }

        /* Submit Button */
        .btn-custom,
        .register-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-custom:hover,
        .register-btn:hover {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 95, 191, 0.3);
            color: white;
        }

        .btn-custom:disabled,
        .register-btn:disabled {
            opacity: 0.6;
            transform: none;
        }

        /* Alert Messages */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            font-size: 1rem;
            color: #666;
            margin-top: 20px;
        }

        .login-link a,
        .highlight {
            color: #8b5fbf;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover,
        .highlight:hover {
            text-decoration: underline;
            color: #764ba2;
        }

        /* Right Side - Massive Circle (145% bigger) */
        .brand-side {
            position: absolute;
            right: -450px;
            top: 50%;
            transform: translateY(-50%);
            width: 1305px;
            height: 1305px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            overflow: hidden;
            z-index: 3;
        }

        /* Animated Circles in Massive Circle */
        .brand-side::before {
            content: '';
            position: absolute;
            width: 450px;
            height: 350px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: 15%;
            left: 15%;
            animation: rotate 20s linear infinite;
        }

        .brand-side::after {
            content: '';
            position: absolute;
            width: 650px;
            height: 250px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: 25%;
            right: 25%;
            animation: rotate 25s linear infinite reverse;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Main Circle Animation inside Massive Circle */
        .main-circle {
            width: 480px;
            height: 480px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            left: -5%;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
            }
        }

        .food-hub-text {
            font-size: 4.5rem;
            font-weight: 700;
            position: relative;
            z-index: 2;
            line-height: 0.9;
        }

        .brand-description {
            font-size: 1.6rem;
            opacity: 0.9;
            max-width: 450px;
            left: -5%;
            line-height: 1.5;
            position: relative;
            z-index: 2;
            margin-bottom: 40px;
        }

        .learn-more-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 18px 40px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
        }

        .learn-more-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
        }

        /* Hide Bootstrap card elements */
        .card,
        .card-header,
        .card-body,
        .card-footer {
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .container,
        .row,
        .col-md-6 {
            margin: 0 !important;
            padding: 0 !important;
            max-width: none !important;
            flex: none !important;
            width: auto !important;
        }

        /* Responsive Design - Based on howtolook example */
        @media (max-width: 1024px) {
            .brand-side {
                right: -350px;
                width: 1000px;
                height: 1000px;
            }

            .register-container {
                width: 500px;
                padding: 35px 30px;
                left: 40%;
            }
        }

        @media (max-width: 768px) {
            .register-container {
                left: 50%;
                transform: translate(-50%, -50%);
                width: 420px;
                padding: 30px 25px;
                background: rgba(255, 255, 255, 0.95);
                z-index: 10;
                max-height: 85vh;
                overflow-y: auto;
            }

            .brand-side {
                right: -300px;
                width: 800px;
                height: 800px;
                z-index: 1;
            }

            .main-circle {
                width: 200px;
                height: 200px;
                left: -8%;
            }

            .food-hub-text {
                font-size: 2.5rem;
            }

            .brand-description {
                font-size: 1.1rem;
                max-width: 300px;
                left: -8%;
            }

            .learn-more-btn {
                padding: 12px 24px;
                font-size: 1rem;
                left: -8%;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                width: 370px;
                padding: 25px 20px;
                left: 50%;
                transform: translate(-50%, -50%);
                max-height: 90vh;
            }

            .form-title {
                font-size: 1.6rem;
                margin-bottom: 20px;
            }

            .form-control {
                padding: 10px 14px;
                font-size: 0.95rem;
            }

            .password-container .form-control {
                padding-right: 45px;
            }

            .password-toggle {
                right: 12px;
                font-size: 1rem;
            }

            .btn-custom {
                padding: 14px;
                font-size: 1rem;
            }

            .d-flex.justify-content-start {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .form-check {
                margin-bottom: 8px;
            }

            .mb-3 {
                margin-bottom: 16px;
            }

            .brand-side {
                right: -250px;
                width: 650px;
                height: 650px;
            }

            .main-circle {
                width: 150px;
                height: 150px;
                left: -10%;
            }

            .food-hub-text {
                font-size: 2rem;
            }

            .brand-description {
                font-size: 1rem;
                max-width: 250px;
                left: -10%;
            }

            .learn-more-btn {
                padding: 10px 20px;
                font-size: 0.9rem;
                left: -10%;
            }
        }

        @media (max-width: 375px) {
            .register-container {
                width: 340px;
                padding: 20px 18px;
            }

            .form-title {
                font-size: 1.4rem;
            }

            .form-control {
                padding: 9px 12px;
                font-size: 0.9rem;
            }

            .brand-side {
                right: -200px;
                width: 550px;
                height: 550px;
            }

            .main-circle {
                width: 120px;
                height: 120px;
            }

            .food-hub-text {
                font-size: 1.6rem;
            }

            .brand-description {
                font-size: 0.9rem;
                max-width: 200px;
            }
        }

        /* Small form adjustments for better fit */
        .d-flex.justify-content-start {
            gap: 20px;
        }

        .text-muted {
            font-size: 0.85rem;
            color: #999 !important;
        }

        /* Loading State */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>


<body>
    <!-- Register Form Container (Positioned to almost touch circle) -->
    <div class="register-container animate__animated animate__fadeIn">
        <div class="form-header">
            <div class="circle-icon"></div>
            <div class="form-title">Join Food Hub Today!</div>
        </div>

        <!-- Server-side alerts -->
        <?php if (!empty($reg_success)): ?>
            <div class="alert alert-success text-center" role="alert">
                <?= htmlspecialchars($reg_success) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($reg_error)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($reg_error) ?>
            </div>
        <?php endif; ?>

        <!-- Client-side error (inline JS will use this) -->
        <div id="register-error" class="alert alert-danger text-center" style="display:none;" role="alert"></div>

        <!-- form: now posts to this same file -->
        <form id="register-form" method="POST" action="">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" id="email" name="email" class="form-control" placeholder="your@email.com" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                    </button>
                </div>
                <small class="text-muted">Password must be at least 6 characters</small>
            </div>
            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" class="form-control" placeholder="+233 55 123 4567" required>
            </div>
            <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <select id="country" name="country" class="form-control" required>
                    <option value="">Select Country</option>
                    <option value="Ghana" selected>Ghana</option>
                    <option value="Nigeria">Nigeria</option>
                    <option value="South Africa">South Africa</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Egypt">Egypt</option>
                    <option value="Morocco">Morocco</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" id="city" name="city" class="form-control" placeholder="e.g. Accra" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Register As</label>
                <div class="d-flex justify-content-start">
                    <div class="form-check me-3">
                        <input class="form-check-input" type="radio" name="role" id="customer" value="1" checked>
                        <label class="form-check-label" for="customer">Customer</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="owner" value="2">
                        <label class="form-check-label" for="owner">Restaurant Owner</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-custom">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php" class="highlight">Login here</a>
        </div>
    </div>

    <!-- Massive Circle (Right Side) - 145% bigger -->
    <div class="brand-side">
        <div class="main-circle">
            <div class="food-hub-text">Food Hub</div>
        </div>
        <p class="brand-description">
            Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text.
        </p>
        <a href="#" class="learn-more-btn">
            Learn More
            <i class="fas fa-play"></i>
        </a>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Inline JS (validation only; normal POST submit to PHP above) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('register-form');
            if (!form) return;

            const nameEl = document.getElementById('name');
            const emailEl = document.getElementById('email');
            const passEl = document.getElementById('password');
            const phoneEl = document.getElementById('phone_number');
            const countryEl = document.getElementById('country');
            const cityEl = document.getElementById('city');
            const errorEl = document.getElementById('register-error');
            const submitBtn = form.querySelector('button[type="submit"]');

            const emailRegex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;

            function setError(msg) {
                if (errorEl) {
                    errorEl.textContent = msg;
                    errorEl.style.display = 'block';
                }
            }

            function clearError() {
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.style.display = 'none';
                }
            }

            form.addEventListener('submit', function(e) {
                clearError();

                const name = nameEl.value.trim();
                const email = emailEl.value.trim();
                const pass = passEl.value;

                if (!name) {
                    e.preventDefault();
                    setError('Full Name is required.');
                    return;
                }
                // if (!emailRegex.test(email)) {
                //     e.preventDefault();
                //     setError('Please enter a valid email address.');
                //     return;
                // }
                if (!pass) {
                    e.preventDefault();
                    setError('Password is required.');
                    return;
                }
                if (pass.length < 6) {
                    e.preventDefault();
                    setError('Password must be at least 6 characters.');
                    return;
                }
                if (!phoneEl.value.trim()) {
                    e.preventDefault();
                    setError('Phone Number is required.');
                    return;
                }
                if (!countryEl.value) {
                    e.preventDefault();
                    setError('Please select your country.');
                    return;
                }
                if (!cityEl.value.trim()) {
                    e.preventDefault();
                    setError('City is required.');
                    return;
                }

                // Valid → allow normal POST to PHP at the top
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Creating account...';
                }
            });
        });

        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-toggle-icon');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>