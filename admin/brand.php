<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Manage Brands</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #f8f9ff 0%, #f1f5f9 50%, #e2e8f0 100%);
            color: #1a202c;
            padding: 0 !important;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Animated Background Circles */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .bg-circle-1 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.1), rgba(240, 147, 251, 0.1));
            top: 10%;
            right: 15%;
            animation: float1 8s ease-in-out infinite;
        }

        .bg-circle-2 {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.08), rgba(139, 95, 191, 0.08));
            bottom: 20%;
            left: 10%;
            animation: float2 10s ease-in-out infinite reverse;
        }

        @keyframes float1 {
            0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
            33% { transform: translateY(-30px) translateX(20px) rotate(120deg); }
            66% { transform: translateY(20px) translateX(-15px) rotate(240deg); }
        }

        @keyframes float2 {
            0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
            50% { transform: translateY(-25px) translateX(25px) rotate(180deg); }
        }

        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            box-shadow: 0 2px 10px rgba(139, 95, 191, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 0;
            margin-bottom: 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #8b5fbf;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .co {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Left Sidebar - Starts after header */
        .sidebar {
            position: fixed;
            left: 0;
            top: 80px;
            width: 320px;
            height: calc(100vh - 80px);
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            z-index: 999;
            padding: 20px;
            box-shadow: 4px 0 15px rgba(139, 95, 191, 0.2);
            transition: transform 0.3s ease;
        }

        .sidebar.sidebar-hidden {
            transform: translateX(-100%);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Main Content - Adjusted for wider sidebar */
        .main-content {
            margin-left: 320px;
            position: relative;
            z-index: 10;
            transition: margin-left 0.3s ease;
        }

        .main-content.sidebar-hidden {
            margin-left: 0;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggle {
            position: fixed;
            top: 90px;
            left: 10px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            z-index: 1001;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(139, 95, 191, 0.3);
        }

        .sidebar-toggle:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: scale(1.05);
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(139, 95, 191, 0.08);
            padding: 2rem !important;
            margin: 2rem auto;
            position: relative;
            z-index: 10;
        }

        .h4 {
            color: #1a202c;
            font-weight: 700;
            font-size: 2.2rem;
        }

        .btn-outline-secondary {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            color: white;
            transform: translateY(-1px);
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #8b5fbf;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 95, 191, 0.3);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(139, 95, 191, 0.05);
            font-size: 1.1rem;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: #374151;
            font-weight: 600;
            border: none;
            padding: 18px;
            font-size: 1.2rem;
        }

        .table tbody td {
            padding: 16px 18px;
            border-color: #f1f5f9;
            vertical-align: middle;
            font-size: 1.1rem;
        }

        .table tbody tr:hover {
            background: rgba(139, 95, 191, 0.05);
        }

        /* Edit Button - Regular Purple */
        .btn-edit {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 8px;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: translateY(-1px);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            color: white;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
                transform: translateX(-100%);
            }

            .sidebar.sidebar-visible {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: flex;
            }

            .container {
                margin: 1rem;
                padding: 1rem !important;
            }
        }

        @media (min-width: 769px) {
            .sidebar-toggle {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background Circles -->
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar - Always Visible -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
            <p>Manage your brands</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="category.php"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="#" class="active"><i class="fas fa-tags"></i> Brands</a></li>
            <li><a href="product.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo -->
                <a href="../index.php" class="logo">
                    FlavourHub<span class="co">co</span>
                </a>

                <!-- Header Actions -->
                <div>
                    <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</span>
                    <a href="../login/logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="p-4">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h4 mb-0">Brand Management</h1>
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm">Back to Home</a>
                </div>

                <!-- Add Brand Form -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Add New Brand</h5>
                            </div>
                            <div class="card-body">
                                <form id="addBrandForm">
                                    <div class="mb-3">
                                        <label for="brand_name" class="form-label">Brand Name</label>
                                        <input type="text" class="form-control" id="brand_name" name="brand_name" placeholder="Enter brand name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Brand</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Brands Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Brands</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="brandTable">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Brand Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Brands will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Brand Modal -->
    <div class="modal fade" id="editBrandModal" tabindex="-1" aria-labelledby="editBrandModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBrandModalLabel">Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="updateBrandForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_brand_id" name="brand_id">
                        <div class="mb-3">
                            <label for="edit_brand_name" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="edit_brand_name" name="brand_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_id" class="form-label">Category</label>
                            <select class="form-select" id="edit_category_id" name="category_id" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Brand</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/brand.js"></script>

    <script>
        // Sidebar toggle functionality
        let sidebarHidden = false;

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const toggleIcon = toggleBtn.querySelector('i');

            if (window.innerWidth <= 768) {
                // Mobile behavior: show/hide sidebar
                sidebar.classList.toggle('sidebar-visible');
                if (sidebar.classList.contains('sidebar-visible')) {
                    toggleIcon.classList.remove('fa-bars');
                    toggleIcon.classList.add('fa-times');
                } else {
                    toggleIcon.classList.remove('fa-times');
                    toggleIcon.classList.add('fa-bars');
                }
            } else {
                // Desktop behavior: slide sidebar and adjust content
                sidebarHidden = !sidebarHidden;

                if (sidebarHidden) {
                    sidebar.classList.add('sidebar-hidden');
                    mainContent.classList.add('sidebar-hidden');
                    toggleIcon.classList.remove('fa-bars');
                    toggleIcon.classList.add('fa-chevron-right');
                } else {
                    sidebar.classList.remove('sidebar-hidden');
                    mainContent.classList.remove('sidebar-hidden');
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-bars');
                }
            }
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const toggleIcon = toggleBtn.querySelector('i');

            if (window.innerWidth > 768) {
                // Desktop: restore sidebar based on sidebarHidden state
                sidebar.classList.remove('sidebar-visible');
                if (sidebarHidden) {
                    sidebar.classList.add('sidebar-hidden');
                    mainContent.classList.add('sidebar-hidden');
                    toggleIcon.classList.remove('fa-bars', 'fa-times');
                    toggleIcon.classList.add('fa-chevron-right');
                } else {
                    sidebar.classList.remove('sidebar-hidden');
                    mainContent.classList.remove('sidebar-hidden');
                    toggleIcon.classList.remove('fa-chevron-right', 'fa-times');
                    toggleIcon.classList.add('fa-bars');
                }
            } else {
                // Mobile: reset desktop states and use mobile behavior
                sidebar.classList.remove('sidebar-hidden');
                mainContent.classList.remove('sidebar-hidden');
                if (!sidebar.classList.contains('sidebar-visible')) {
                    toggleIcon.classList.remove('fa-chevron-right', 'fa-times');
                    toggleIcon.classList.add('fa-bars');
                }
            }
        });
    </script>
</body>

</html>