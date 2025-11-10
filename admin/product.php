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
    <title>Manage Products</title>
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

        .bg-circle-3 {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.06), rgba(240, 147, 251, 0.06));
            top: 60%;
            left: 70%;
            animation: float3 12s ease-in-out infinite;
        }

        .bg-circle-4 {
            width: 180px;
            height: 180px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.09), rgba(240, 147, 251, 0.09));
            top: 30%;
            left: 50%;
            animation: float1 14s ease-in-out infinite reverse;
        }

        .bg-circle-5 {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.07), rgba(139, 95, 191, 0.07));
            bottom: 40%;
            right: 25%;
            animation: float2 9s ease-in-out infinite;
        }

        .bg-circle-6 {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.05), rgba(240, 147, 251, 0.05));
            top: 80%;
            left: 80%;
            animation: float3 11s ease-in-out infinite reverse;
        }

        .bg-circle-7 {
            width: 160px;
            height: 160px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.08), rgba(139, 95, 191, 0.08));
            top: 5%;
            left: 30%;
            animation: float1 13s ease-in-out infinite;
        }

        .bg-circle-8 {
            width: 110px;
            height: 110px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.06), rgba(240, 147, 251, 0.06));
            bottom: 60%;
            right: 40%;
            animation: float2 15s ease-in-out infinite reverse;
        }

        .bg-circle-9 {
            width: 130px;
            height: 130px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.07), rgba(139, 95, 191, 0.07));
            top: 45%;
            right: 5%;
            animation: float3 10s ease-in-out infinite;
        }

        .bg-circle-10 {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.04), rgba(240, 147, 251, 0.04));
            bottom: 10%;
            left: 40%;
            animation: float1 16s ease-in-out infinite reverse;
        }

        .bg-circle-11 {
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.09), rgba(139, 95, 191, 0.09));
            top: 70%;
            left: 25%;
            animation: float2 12s ease-in-out infinite;
        }

        /* Big Animated Bubbles */
        .big-bubble-1 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.12), rgba(240, 147, 251, 0.12));
            top: 5%;
            right: 5%;
            animation: bigFloat1 18s ease-in-out infinite;
        }

        .big-bubble-2 {
            width: 250px;
            height: 250px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.10), rgba(139, 95, 191, 0.10));
            bottom: 15%;
            left: 5%;
            animation: bigFloat2 20s ease-in-out infinite reverse;
        }

        .big-bubble-3 {
            width: 280px;
            height: 280px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.11), rgba(240, 147, 251, 0.11));
            top: 25%;
            left: 75%;
            animation: bigFloat3 16s ease-in-out infinite;
        }

        .big-bubble-4 {
            width: 220px;
            height: 220px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.09), rgba(139, 95, 191, 0.09));
            top: 55%;
            right: 10%;
            animation: bigFloat1 22s ease-in-out infinite reverse;
        }

        .big-bubble-5 {
            width: 260px;
            height: 260px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.08), rgba(240, 147, 251, 0.08));
            bottom: 45%;
            left: 60%;
            animation: bigFloat2 19s ease-in-out infinite;
        }

        .big-bubble-6 {
            width: 240px;
            height: 240px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.07), rgba(139, 95, 191, 0.07));
            top: 40%;
            left: 40%;
            animation: bigFloat3 17s ease-in-out infinite reverse;
        }

        .big-bubble-7 {
            width: 290px;
            height: 290px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.10), rgba(240, 147, 251, 0.10));
            top: 75%;
            right: 30%;
            animation: bigFloat1 21s ease-in-out infinite;
        }

        .big-bubble-8 {
            width: 230px;
            height: 230px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.08), rgba(139, 95, 191, 0.08));
            bottom: 70%;
            left: 20%;
            animation: bigFloat2 15s ease-in-out infinite reverse;
        }

        .big-bubble-9 {
            width: 270px;
            height: 270px;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.09), rgba(240, 147, 251, 0.09));
            top: 10%;
            left: 50%;
            animation: bigFloat3 23s ease-in-out infinite;
        }

        .big-bubble-10 {
            width: 210px;
            height: 210px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.06), rgba(139, 95, 191, 0.06));
            bottom: 25%;
            right: 50%;
            animation: bigFloat1 14s ease-in-out infinite reverse;
        }

        @keyframes bigFloat1 {
            0%, 100% {
                transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
            }
            25% {
                transform: translateY(-40px) translateX(30px) rotate(90deg) scale(1.1);
            }
            50% {
                transform: translateY(-20px) translateX(-25px) rotate(180deg) scale(0.9);
            }
            75% {
                transform: translateY(35px) translateX(20px) rotate(270deg) scale(1.05);
            }
        }

        @keyframes bigFloat2 {
            0%, 100% {
                transform: translateY(0px) translateX(0px) scale(1);
            }
            33% {
                transform: translateY(-50px) translateX(40px) scale(1.15);
            }
            66% {
                transform: translateY(30px) translateX(-30px) scale(0.85);
            }
        }

        @keyframes bigFloat3 {
            0%, 100% {
                transform: translateY(0px) translateX(0px) rotate(0deg);
            }
            20% {
                transform: translateY(25px) translateX(-35px) rotate(72deg);
            }
            40% {
                transform: translateY(-35px) translateX(15px) rotate(144deg);
            }
            60% {
                transform: translateY(20px) translateX(40px) rotate(216deg);
            }
            80% {
                transform: translateY(-15px) translateX(-20px) rotate(288deg);
            }
        }

        @keyframes float1 {
            0%, 100% {
                transform: translateY(0px) translateX(0px) rotate(0deg);
            }
            33% {
                transform: translateY(-30px) translateX(20px) rotate(120deg);
            }
            66% {
                transform: translateY(20px) translateX(-15px) rotate(240deg);
            }
        }

        @keyframes float2 {
            0%, 100% {
                transform: translateY(0px) translateX(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-25px) translateX(25px) rotate(180deg);
            }
        }

        @keyframes float3 {
            0%, 100% {
                transform: translateY(0px) translateX(0px) scale(1);
            }
            33% {
                transform: translateY(15px) translateX(-20px) scale(1.1);
            }
            66% {
                transform: translateY(-20px) translateX(10px) scale(0.9);
            }
        }

        /* Layout */

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 80px;
            width: 320px;
            height: calc(100vh - 80px);
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            padding: 30px 20px;
            z-index: 999;
            transition: transform 0.3s ease;
            overflow-y: auto;
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
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            font-weight: 500;
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
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(139, 95, 191, 0.3);
        }

        .sidebar-toggle:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: scale(1.05);
        }

        .sidebar-toggle.sidebar-hidden {
            left: 10px;
        }

        /* Header */
        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(139, 95, 191, 0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 20px rgba(139, 95, 191, 0.08);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #8b5fbf;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo:hover {
            color: #6366f1;
            transform: scale(1.05);
        }

        .co {
            background: linear-gradient(135deg, #8b5fbf, #a855f7);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin-left: 0.5rem;
            box-shadow: 0 2px 8px rgba(139, 95, 191, 0.3);
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Cards */
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1rem;
            border: 1px solid rgba(139, 95, 191, 0.1);
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(139, 95, 191, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #8b5fbf, #6366f1);
            color: white;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Elements */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.75rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus, .form-select:focus {
            border-color: #8b5fbf;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
            background: #ffffff;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        /* Buttons */
        .btn {
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b5fbf, #6366f1);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 95, 191, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 95, 191, 0.4);
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
        }

        .btn-edit {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            transform: translateY(-1px);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        /* Table */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(139, 95, 191, 0.1);
        }

        .table {
            margin: 0;
            background: transparent;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: #374151;
            font-weight: 600;
            border: none;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
        }

        .table tbody td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(139, 95, 191, 0.05);
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 0.375rem;
            border: 2px solid #e5e7eb;
        }

        /* Upload Features */
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 4px solid #8b5fbf;
            transition: all 0.3s ease;
        }

        .profile-img:hover {
            transform: scale(1.05);
            border-color: #6366f1;
        }

        .uploaded-image-item {
            position: relative;
            margin-bottom: 1rem;
        }

        .uploaded-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .uploaded-image:hover {
            border-color: #8b5fbf;
            transform: scale(1.02);
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(139, 95, 191, 0.8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        .uploaded-image-item:hover .image-overlay {
            opacity: 1;
        }

        .copy-url-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .copy-url-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background: #f3f4f6;
        }

        .progress-bar {
            background: linear-gradient(135deg, #8b5fbf, #6366f1);
        }

        /* Responsive */
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

            .content-area {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .sidebar-toggle {
                display: flex;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                width: 250px;
            }

            .sidebar-header h3 {
                font-size: 1.4rem;
            }

            .sidebar-header p {
                font-size: 0.9rem;
            }

            .sidebar-menu a {
                font-size: 1rem;
                padding: 12px 14px;
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
    <!-- Background Circles -->
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>
    <div class="bg-circle bg-circle-3"></div>
    <div class="bg-circle bg-circle-4"></div>
    <div class="bg-circle bg-circle-5"></div>
    <div class="bg-circle bg-circle-6"></div>
    <div class="bg-circle bg-circle-7"></div>
    <div class="bg-circle bg-circle-8"></div>
    <div class="bg-circle bg-circle-9"></div>
    <div class="bg-circle bg-circle-10"></div>
    <div class="bg-circle bg-circle-11"></div>

    <!-- Big Animated Bubbles -->
    <div class="bg-circle big-bubble-1"></div>
    <div class="bg-circle big-bubble-2"></div>
    <div class="bg-circle big-bubble-3"></div>
    <div class="bg-circle big-bubble-4"></div>
    <div class="bg-circle big-bubble-5"></div>
    <div class="bg-circle big-bubble-6"></div>
    <div class="bg-circle big-bubble-7"></div>
    <div class="bg-circle big-bubble-8"></div>
    <div class="bg-circle big-bubble-9"></div>
    <div class="bg-circle big-bubble-10"></div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar - Always Visible -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
            <p>Manage your products</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="category.php"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="brand.php"><i class="fas fa-tags"></i> Brands</a></li>
            <li><a href="#" class="active"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
            <!-- Header -->
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
                            <a href="../index.php" class="btn btn-secondary btn-sm me-2">Back to Home</a>
                            <a href="../login/logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <h1 class="page-title">Product Management</h1>

                <!-- Bulk Image Upload -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-images me-2"></i>Bulk Image Upload for Products
                    </div>
                    <div class="card-body">
                        <form id="bulkUploadForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="bulk_images" class="form-label">Select Product Images</label>
                                    <input type="file" class="form-control" id="bulk_images" name="images[]" multiple accept="image/*" required>
                                    <div class="form-text">Select multiple images (JPG, PNG, GIF, WEBP). Max 5MB per file.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="image_prefix" class="form-label">Image Name Prefix</label>
                                    <input type="text" class="form-control" id="image_prefix" placeholder="e.g. product_main">
                                    <div class="form-text">Optional: Add prefix to organize images</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="fas fa-upload me-2"></i>Upload Images
                            </button>
                        </form>
                        <div id="upload-progress" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        <div id="uploaded-images" class="mt-3"></div>
                    </div>
                </div>

                <!-- Add Product Form -->
                <div class="admin-card">
                    <div class="card-header">
                        Add New Product
                    </div>
                    <div class="card-body">
                        <form id="addProductForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_title" class="form-label">Product Title</label>
                                    <input type="text" class="form-control" id="product_title" name="product_title" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="product_price" class="form-label">Price (GH₵)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="product_price" name="product_price" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="brand_id" class="form-label">Brand</label>
                                    <select class="form-select" id="brand_id" name="brand_id" required>
                                        <option value="">Select Brand</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="product_desc" class="form-label">Description</label>
                                <textarea class="form-control" id="product_desc" name="product_desc" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="product_keywords" class="form-label">Keywords</label>
                                    <input type="text" class="form-control" id="product_keywords" name="product_keywords" placeholder="Separate with commas">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Add Product
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="admin-card">
                    <div class="card-header">
                        Your Products
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table" id="productTable">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Title</th>
                                        <th>Price</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="text-center">No products found</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProductForm">
                        <input type="hidden" id="edit_product_id" name="product_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_product_title" class="form-label">Product Title</label>
                                <input type="text" class="form-control" id="edit_product_title" name="product_title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_product_price" class="form-label">Price (GH₵)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="edit_product_price" name="product_price" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit_category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_brand_id" class="form-label">Brand</label>
                                <select class="form-select" id="edit_brand_id" name="brand_id" required>
                                    <option value="">Select Brand</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_product_desc" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_product_desc" name="product_desc" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_product_image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="edit_product_image" name="product_image" accept="image/*">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_product_keywords" class="form-label">Keywords</label>
                                <input type="text" class="form-control" id="edit_product_keywords" name="product_keywords">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/product.js"></script>
    <script>
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
                    toggleBtn.classList.add('sidebar-hidden');
                    toggleIcon.classList.remove('fa-bars');
                    toggleIcon.classList.add('fa-chevron-right');
                } else {
                    sidebar.classList.remove('sidebar-hidden');
                    mainContent.classList.remove('sidebar-hidden');
                    toggleBtn.classList.remove('sidebar-hidden');
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-bars');
                }
            }
        }

        // Handle window resize to ensure proper behavior across different screen sizes
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
                toggleBtn.classList.remove('sidebar-hidden');
                if (!sidebar.classList.contains('sidebar-visible')) {
                    toggleIcon.classList.remove('fa-chevron-right', 'fa-times');
                    toggleIcon.classList.add('fa-bars');
                }
            }
        });
    </script>
</body>

</html>