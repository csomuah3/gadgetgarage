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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fffe;
            color: #1a1a1a;
            line-height: 1.6;
        }

        /* Top Navigation */
        .admin-navbar {
            background: #f8fffe;
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white !important;
            font-size: 1.2rem;
            font-weight: 700;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            margin-right: 2rem;
        }

        .navbar-nav-container {
            background: white;
            border-radius: 50px;
            padding: 0.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .navbar-nav {
            display: flex;
            gap: 0;
            align-items: center;
            margin: 0;
        }

        .nav-link {
            color: #6b7280 !important;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background: #f3f4f6;
            color: #374151 !important;
        }

        .nav-link.active {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white !important;
        }

        .user-profile {
            background: linear-gradient(135deg, #008060, #006b4e);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1rem;
            font-weight: 500;
        }

        /* Page Container */
        .admin-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - 80px);
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5f3f0;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }

        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
            color: #008060;
        }

        .breadcrumb-custom a {
            color: #008060;
            text-decoration: none;
        }

        .breadcrumb-custom a:hover {
            text-decoration: underline;
        }

        /* Cards */
        .admin-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5f3f0;
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 1.25rem;
            border: none;
        }

        .card-header-custom h5 {
            margin: 0;
            font-weight: 600;
        }

        .card-body-custom {
            padding: 1.5rem;
        }

        /* Buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, #008060, #006b4e);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #006b4e, #005a42);
            transform: translateY(-1px);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 128, 96, 0.3);
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
                font-size: 0.875rem;
                padding: 0.6rem 1rem;
            }

            .navbar-brand {
                font-size: 1rem;
                padding: 0.6rem 1.2rem;
            }

            .user-profile {
                font-size: 0.875rem;
                padding: 0.6rem 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .admin-navbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .navbar-nav-container {
                order: 2;
                width: 100%;
            }

            .navbar-nav {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding: 0.2rem;
            }

            .nav-link {
                font-size: 0.8rem;
                padding: 0.5rem 1rem;
                white-space: nowrap;
            }

            .nav-link i {
                margin-right: 0.3rem;
            }

            .user-profile {
                order: 3;
                font-size: 0.8rem;
                align-self: center;
            }

            .navbar-brand {
                order: 1;
                align-self: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="admin-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-cube me-2"></i>
                GadgetGarage
            </a>

            <div class="navbar-nav-container">
                <div class="navbar-nav">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                    <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i>
                        Orders
                    </a>
                    <a href="product.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'product.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                    <a href="category.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'category.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                    <a href="brand.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'brand.php' ? 'active' : ''; ?>">
                        <i class="fas fa-trademark"></i>
                        Brands
                    </a>
                    <a href="support_messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'support_messages.php' ? 'active' : ''; ?>">
                        <i class="fas fa-headset"></i>
                        Support
                    </a>
                    <a href="appointments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar"></i>
                        Appointments
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
                        <li><a class="dropdown-item" href="../login/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Admin Content Container -->
    <div class="admin-container">