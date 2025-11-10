<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Taste of Africa</title>
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
        .login-container {
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
        }

        /* Remove form-side since we're using login-container directly */
        .form-side {
            display: none;
        }

        .form-header {
            margin-bottom: 40px;
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
            margin-bottom: 30px;
            font-weight: 400;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 8px;
            font-weight: 400;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            outline: none;
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

        /* Remember Me and Forgot Password */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            font-size: 1rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .remember-me input[type="checkbox"] {
            accent-color: #8b5fbf;
        }

        .forgot-password {
            color: #8b5fbf;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
            color: #764ba2;
        }

        /* Login Button */
        .login-btn {
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
            margin-bottom: 30px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 95, 191, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.6;
            transform: none;
        }

        /* Social Login */
        .social-divider {
            text-align: center;
            margin: 30px 0;
            color: #999;
            font-size: 0.9rem;
        }

        .social-login {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            color: white;
        }

        .social-btn.facebook {
            background: #1877f2;
        }

        .social-btn.google {
            background: #ea4335;
        }

        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        /* Sign Up Link */
        .signup-link {
            text-align: center;
            font-size: 1rem;
            color: #666;
        }

        .signup-link a {
            color: #8b5fbf;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
            text-decoration: underline;
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

        /* Responsive Design - Based on howtolook example */
        @media (max-width: 1024px) {
            .brand-side {
                right: -350px;
                width: 1000px;
                height: 1000px;
            }

            .login-container {
                width: 500px;
                padding: 35px 30px;
                left: 40%;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                left: 50%;
                transform: translate(-50%, -50%);
                width: 400px;
                padding: 30px 25px;
                background: rgba(255, 255, 255, 0.95);
                z-index: 10;
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
            .login-container {
                width: 350px;
                padding: 25px 20px;
                left: 50%;
                transform: translate(-50%, -50%);
            }

            .form-title {
                font-size: 1.6rem;
                margin-bottom: 25px;
            }

            .form-control {
                padding: 12px 16px;
                font-size: 0.95rem;
            }

            .password-container .form-control {
                padding-right: 45px;
            }

            .password-toggle {
                right: 12px;
                font-size: 1rem;
            }

            .login-btn {
                padding: 14px;
                font-size: 1rem;
            }

            .form-options {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .social-login {
                gap: 15px;
                justify-content: center;
            }

            .social-btn {
                width: 45px;
                height: 45px;
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
            .login-container {
                width: 320px;
                padding: 20px 18px;
            }

            .form-title {
                font-size: 1.4rem;
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

        /* Loading State */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* Override Bootstrap styles that might conflict */
        .card,
        .card-header,
        .card-body,
        .card-footer {
            background: none !important;
            border: none !important;
            box-shadow: none !important;
        }

        .row,
        .col-md-6 {
            margin: 0 !important;
            padding: 0 !important;
            max-width: none !important;
            flex: none !important;
        }
    </style>
</head>

<body>
    <!-- Login Form Container (Positioned to almost touch circle) -->
    <div class="login-container animate__animated animate__fadeIn">
        <div class="form-header">
            <div class="circle-icon"></div>
            <div class="form-title">Welcome Back! Please Log In</div>
        </div>

        <form id="login-form">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="abc@xyz.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox"> Remember me
                </label>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="login-btn">Log in</button>
        </form>

        <div class="social-divider">or connect with</div>

        <div class="social-login">
            <a href="#" class="social-btn facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="social-btn google">
                <i class="fab fa-google"></i>
            </a>
        </div>

        <div class="signup-link">
            Don't have an account? <a href="register.php">Sign up</a>
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

    <script>
        $(document).ready(function() {
            $('#login-form').submit(function(e) {
                e.preventDefault();

                // Get form values
                var email = $('#email').val().trim();
                var password = $('#password').val();

                console.log('=== LOGIN ATTEMPT ===');
                console.log('Email:', email);

                // Basic validation
                if (email === '' || password === '') {
                    Swal.fire({
                        title: 'Validation Error',
                        text: 'Please fill in all fields!',
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                    return;
                }

                // Simple email validation
                if (!email.includes('@') || !email.includes('.')) {
                    Swal.fire({
                        title: 'Validation Error',
                        text: 'Please enter a valid email address!',
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                    return;
                }

                // Password length validation
                if (password.length < 6) {
                    Swal.fire({
                        title: 'Validation Error',
                        text: 'Password must be at least 6 characters long!',
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                    return;
                }

                // Show loading state
                var $btn = $('.login-btn');
                var originalText = $btn.text();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Logging in...');

                // AJAX request for login - using existing register_user_action.php
                $.ajax({
                    url: '../actions/register_user_action.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        email: email,
                        password: password
                        // Note: No 'name' field sent, so the PHP will detect this as a login request
                    },
                    success: function(response) {
                        console.log('=== SERVER RESPONSE (SUCCESS) ===');
                        console.log('Full Response:', response);
                        console.log('Status:', response.status);
                        console.log('Message:', response.message);

                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#8b5fbf',
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = '../index.php';
                            });
                        } else {
                            Swal.fire({
                                title: 'Login Failed',
                                text: response.message,
                                icon: 'error',
                                confirmButtonColor: '#8b5fbf'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('=== AJAX ERROR ===');
                        console.log('Status:', status);
                        console.log('Error:', error);
                        console.log('Response Text:', xhr.responseText);
                        console.log('Status Code:', xhr.status);

                        Swal.fire({
                            title: 'Connection Error',
                            text: 'Failed to connect to server. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#8b5fbf'
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
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