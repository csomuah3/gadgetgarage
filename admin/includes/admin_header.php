<?php
// Admin header with navigation - to be included in all admin pages
if (!check_login() || !check_admin()) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> - GadgetGarage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        :root {
            --primary-navy: #1a202c;
            --electric-blue: #3b82f6;
            --accent-orange: #f59e0b;
            --platinum: #e2e8f0;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --gradient-primary: linear-gradient(135deg, var(--primary-navy), var(--electric-blue));
            --gradient-accent: linear-gradient(135deg, var(--electric-blue), var(--accent-orange));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: var(--primary-navy);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('http://169.239.251.102:442/~chelsea.somuah/uploads/ChatGPTImageNov19202511_50_42PM.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.45;
            z-index: -1;
            pointer-events: none;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(26, 32, 44, 0.05) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundFloat 20s ease-in-out infinite;
        }

        @keyframes backgroundFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Top Navigation */
        .admin-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 0.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .admin-navbar .d-flex {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        .navbar-brand {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white !important;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            margin-right: 2rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .navbar-brand::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .navbar-brand:hover::before {
            left: 100%;
        }

        .navbar-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .garage-accent {
            background: rgba(245, 158, 11, 0.2);
            padding: 0.1rem 0.3rem;
            border-radius: 4px;
            margin-left: 2px;
        }

        .navbar-nav-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 0.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
        }

        .navbar-nav {
            display: flex !important;
            flex-direction: row !important;
            gap: 0.25rem;
            align-items: center;
            margin: 0;
            flex-wrap: nowrap !important;
            width: 100% !important;
        }

        .nav-link {
            color: #64748b !important;
            text-decoration: none;
            padding: 0.875rem 1.5rem;
            border-radius: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--gradient-accent);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover {
            background: rgba(59, 130, 246, 0.08);
            color: var(--electric-blue) !important;
            transform: translateY(-1px);
        }

        .nav-link:hover::before {
            width: 80%;
        }

        .nav-link.active {
            background: var(--gradient-primary);
            color: white !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
        }

        .nav-link.active::before {
            display: none;
        }

        .user-profile {
            background: var(--gradient-accent);
            padding: 0.875rem 1.5rem;
            border-radius: 14px;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .user-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
        }

        /* Page Container */
        .admin-container {
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
            min-height: calc(100vh - 80px);
            position: relative;
        }

        .page-header {
            margin-bottom: 2.5rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
            animation: slideInFromTop 0.6s ease;
        }

        @keyframes slideInFromTop {
            0% {
                opacity: 0;
                transform: translateY(-30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-navy);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 500;
        }

        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
            color: var(--electric-blue);
            font-weight: 500;
        }

        .breadcrumb-custom a {
            color: var(--electric-blue);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb-custom a:hover {
            color: var(--accent-orange);
        }

        /* Modern Cards */
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .card-header-custom {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .card-header-custom::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.8s ease;
        }

        .card-header-custom:hover::after {
            left: 100%;
        }

        .card-header-custom h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .card-body-custom {
            padding: 2rem;
        }

        /* Modern Buttons */
        .btn-primary-custom {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-success-custom {
            background: #22c55e;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .btn-danger-custom {
            background: #ef4444;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .btn-warning-custom {
            background: #f59e0b;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Tables */
        .table-custom {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5f3f0;
        }

        .table-custom thead {
            background: #f0fdf9;
        }

        .table-custom th {
            background: #f0fdf9;
            color: #1a1a1a;
            font-weight: 600;
            padding: 1rem;
            border: none;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom td {
            padding: 1rem;
            border-bottom: 1px solid #f0fdf9;
            vertical-align: middle;
        }

        .table-custom tbody tr:hover {
            background: #f0fdf9;
        }

        /* Forms */
        .form-group-custom {
            margin-bottom: 1.5rem;
        }

        .form-label-custom {
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control-custom {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5f3f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #008060;
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
        }

        /* Alerts */
        .alert-success-custom {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-danger-custom {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .admin-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .nav-link {
                font-size: 0.8rem;
                padding: 0.7rem 1rem;
            }

            .navbar-brand {
                font-size: 1rem;
                padding: 0.6rem 1.2rem;
                margin-right: 1rem;
            }

            .user-profile {
                font-size: 0.8rem;
                padding: 0.7rem 1.2rem;
                margin-left: 1rem;
            }

            .navbar-nav-container {
                flex: 1;
                margin: 0 0.5rem;
            }
        }

        @media (max-width: 768px) {
            .admin-navbar {
                padding: 0.75rem 1rem;
                min-height: auto;
            }

            .admin-navbar > .d-flex {
                flex-wrap: nowrap !important;
                align-items: center;
            }

            .navbar-nav-container {
                flex: 1;
                margin: 0 0.5rem;
                min-width: 0;
            }

            .navbar-nav {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding: 0.3rem;
                gap: 0.1rem;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .navbar-nav::-webkit-scrollbar {
                display: none;
            }

            .nav-link {
                font-size: 0.75rem;
                padding: 0.6rem 0.8rem;
                white-space: nowrap;
                flex-shrink: 0;
                min-width: auto;
            }

            .nav-link i {
                margin-right: 0.3rem;
                font-size: 0.8rem;
            }

            .user-profile {
                font-size: 0.7rem;
                padding: 0.6rem 0.8rem;
                margin-left: 0.5rem;
                flex-shrink: 0;
            }

            .navbar-brand {
                font-size: 0.85rem;
                padding: 0.6rem 1rem;
                margin-right: 0.5rem;
                flex-shrink: 0;
            }

            .garage-accent {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .navbar-brand span {
                display: none;
            }

            .nav-link span {
                display: none;
            }

            .nav-link {
                padding: 0.6rem;
            }

            .nav-link i {
                margin: 0;
                font-size: 1rem;
            }
        }

        /* Force horizontal navigation - override any conflicting CSS */
        .admin-navbar,
        .admin-navbar .d-flex,
        .admin-navbar .navbar-nav-container,
        .admin-navbar .navbar-nav {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
        }

        .admin-navbar .navbar-nav .nav-link {
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }

        /* Prevent Bootstrap from overriding */
        .navbar-nav .nav-link {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="admin-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <a href="index.php" class="navbar-brand">
                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png"
                     alt="GadgetGarage"
                     style="height: 35px; width: auto; object-fit: contain;">
            </a>

            <div class="navbar-nav-container">
                <div class="navbar-nav">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Orders</span>
                    </a>
                    <a href="product.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'product.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                    <a href="category.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'category.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                    <a href="brand.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'brand.php' ? 'active' : ''; ?>">
                        <i class="fas fa-trademark"></i>
                        <span>Brands</span>
                    </a>
                    <a href="support_messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'support_messages.php' ? 'active' : ''; ?>">
                        <i class="fas fa-headset"></i>
                        <span>Support</span>
                    </a>
                    <a href="appointments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Appointments</span>
                    </a>
                    <a href="../index.php?view_customer=1" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Customer Homepage</span>
                    </a>
                </div>
            </div>

            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Admin'); ?></span>
                <div class="dropdown">
                    <button class="btn btn-link text-white dropdown-toggle border-0 p-0 ms-2" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <div class="dropdown-item d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-moon me-2"></i>Dark Mode
                                </div>
                                <div class="toggle-switch" id="themeToggle" onclick="toggleTheme()">
                                    <div class="toggle-slider"></div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../login/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Admin Content Container -->
    <div class="admin-container">