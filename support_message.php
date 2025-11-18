<?php
require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/cart_controller.php');
require_once(__DIR__ . '/controllers/support_controller.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Handle form submission
$message_sent = false;
$error_message = '';

if ($_POST && isset($_POST['send_message'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Save message to database
        error_log("Attempting to save support message: name=$name, email=$email, subject=$subject");
        $message_id = create_support_message_ctr($customer_id, $name, $email, $subject, $message);

        if ($message_id) {
            error_log("Support message saved successfully with ID: $message_id");
            $message_sent = true;
        } else {
            error_log("Failed to save support message");
            $error_message = 'There was an error sending your message. Please try again.';
        }
    }
}

// Set page variables for universal header
$page_title = 'Contact Support';
$nav_path_prefix = '';
$css_path_prefix = '';
$logo_link = 'index.php';
?>

<?php include_once 'includes/universal_header.php'; ?>

<style>
        .support-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 100px 0 50px 0;
        }

        .support-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .support-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .support-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .support-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .support-content {
            padding: 40px 30px;
        }

        .success-message {
            text-align: center;
            padding: 60px 30px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            animation: checkmark 0.6s ease;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 32px;
            color: white;
        }

        .success-message h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .success-message p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-control.is-invalid {
            border-color: #e74c3c;
        }

        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .btn-send {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-send:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }

        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .contact-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
        }

        .contact-info h4 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: #5a6c7d;
        }

        .contact-item i {
            width: 20px;
            color: #3498db;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .support-page {
                padding: 80px 15px 30px 15px;
            }

            .support-content {
                padding: 30px 20px;
            }

            .support-header {
                padding: 30px 20px;
            }

            .support-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Main Header -->
    <header class="main-header animate__animated animate__fadeInDown">
        <div class="container-fluid" style="padding: 0 120px 0 95px;">
            <div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
                <!-- Logo - Far Left -->
                <a href="index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                         alt="Gadget Garage"
                         style="height: 40px; width: auto; object-fit: contain;">
                </a>

                <!-- Center Content -->
                <div class="d-flex align-items-center" style="flex: 1; justify-content: center; gap: 60px;">
                    <!-- Search Bar -->
                    <form class="search-container" method="GET" action="product_search_result.php">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="query" class="search-input" placeholder="Search phones, laptops, cameras..." required>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <!-- Tech Revival Section -->
                    <div class="tech-revival-section">
                        <i class="fas fa-recycle tech-revival-icon"></i>
                        <div>
                            <p class="tech-revival-text">Bring Retired Tech</p>
                            <p class="contact-number">055-138-7578</p>
                        </div>
                    </div>
                </div>

                <!-- User Actions - Far Right -->
                <div class="user-actions" style="display: flex; align-items: center; gap: 12px;">
                    <span style="color: #ddd;">|</span>
                    <?php if ($is_logged_in): ?>
                        <!-- Wishlist Icon -->
                        <div class="header-icon">
                            <a href="wishlist.php" style="color: inherit; text-decoration: none;">
                                <i class="fas fa-heart"></i>
                            </a>
                        </div>

                        <!-- Cart Icon -->
                        <div class="header-icon">
                            <a href="cart.php" style="color: inherit; text-decoration: none;">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-badge" id="cartBadge" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>"><?php echo $cart_count; ?></span>
                            </a>
                        </div>

                        <!-- User Avatar Dropdown -->
                        <div class="user-dropdown">
                            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>" onclick="toggleUserDropdown()">
                                <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="dropdown-menu-custom" id="userDropdownMenu">
                                <button class="dropdown-item-custom" onclick="openProfilePictureModal()">
                                    <i class="fas fa-camera"></i>
                                    <span>Profile Picture</span>
                                </button>
                                <div class="dropdown-divider-custom"></div>
                                <a href="my_orders.php" class="dropdown-item-custom">
                                    <i class="fas fa-box"></i>
                                    <span>My Orders</span>
                                </a>
                                <div class="dropdown-divider-custom"></div>
                                <a href="wishlist.php" class="dropdown-item-custom">
                                    <i class="fas fa-heart"></i>
                                    <span>Wishlist</span>
                                </a>
                                <?php if ($is_admin): ?>
                                    <div class="dropdown-divider-custom"></div>
                                    <a href="admin/category.php" class="dropdown-item-custom">
                                        <i class="fas fa-cog"></i>
                                        <span>Admin Panel</span>
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider-custom"></div>
                                <a href="login/logout.php" class="dropdown-item-custom">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Not logged in: Register | Login -->
                        <a href="login/register.php" class="login-btn me-2">Register</a>
                        <a href="login/login.php" class="login-btn">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="support-page">
        <div class="container">
            <div class="support-container">
                <div class="support-header">
                    <h1><i class="fas fa-headset"></i> Contact Support</h1>
                    <p>We're here to help! Send us a message and we'll get back to you shortly.</p>
                </div>

                <div class="support-content">
                    <?php if ($message_sent): ?>
                        <!-- Success Message -->
                        <div class="success-message">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2>Message Sent Successfully!</h2>
                            <p>We will get back to you as soon as we receive your message. Our support team typically responds within 24 hours.</p>
                            <a href="index.php" class="btn-back">
                                <i class="fas fa-arrow-left"></i>
                                Back to Home
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Contact Form -->
                        <form method="POST" action="">
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? ($is_logged_in ? $_SESSION['name'] : '')); ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ($is_logged_in ? $_SESSION['email'] ?? '' : '')); ?>"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="subject" class="form-label">Subject *</label>
                                <select class="form-control" id="subject" name="subject" required>
                                    <option value="">Select a topic</option>
                                    <option value="order" <?php echo ($_POST['subject'] ?? '') === 'order' ? 'selected' : ''; ?>>Order Status & Refunds</option>
                                    <option value="device_quality" <?php echo ($_POST['subject'] ?? '') === 'device_quality' ? 'selected' : ''; ?>>Refurbished Device Issues</option>
                                    <option value="repair" <?php echo ($_POST['subject'] ?? '') === 'repair' ? 'selected' : ''; ?>>Repair Service Questions</option>
                                    <option value="device_drop" <?php echo ($_POST['subject'] ?? '') === 'device_drop' ? 'selected' : ''; ?>>Device Drop & Trade-ins</option>
                                    <option value="tech_revival" <?php echo ($_POST['subject'] ?? '') === 'tech_revival' ? 'selected' : ''; ?>>Tech Revival Service (055-138-7578)</option>
                                    <option value="billing" <?php echo ($_POST['subject'] ?? '') === 'billing' ? 'selected' : ''; ?>>Billing & Payment</option>
                                    <option value="account" <?php echo ($_POST['subject'] ?? '') === 'account' ? 'selected' : ''; ?>>Account Issues</option>
                                    <option value="general" <?php echo ($_POST['subject'] ?? '') === 'general' ? 'selected' : ''; ?>>General Question</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="6"
                                          placeholder="Please describe your device issue, order problem, or question in detail..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" name="send_message" class="btn-send">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>
                        </form>

                        <!-- Contact Information -->
                        <div class="contact-info">
                            <h4><i class="fas fa-info-circle"></i> Other Ways to Reach Us</h4>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>Tech Revival Hotline: 055-138-7578 (Available 24/7)</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-tools"></i>
                                <span>Repair Studio: Visit in-store for device repairs</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-recycle"></i>
                                <span>Device Drop: Bring old tech for trade-ins & recycling</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-clock"></i>
                                <span>Response Time: Within 24 hours for all inquiries</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>Email: support@gadgetgarage.com</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/chatbot.js"></script>
    <script>
        // Header dropdown functions
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            dropdown.classList.toggle('show');
        }

        function openProfilePictureModal() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Profile Picture',
                    text: 'Profile picture modal not implemented yet',
                    icon: 'info',
                    confirmButtonColor: '#D19C97',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Profile picture modal not implemented yet');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const target = event.target;
            const isDropdownButton = target.closest('.user-avatar');
            const isDropdownContent = target.closest('.dropdown-menu-custom');

            if (!isDropdownButton && !isDropdownContent) {
                document.querySelectorAll('.dropdown-menu-custom').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>