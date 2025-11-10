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
    <title>Manage Categories</title>
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

            0%,
            100% {
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

            0%,
            100% {
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

            0%,
            100% {
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

            0%,
            100% {
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

            0%,
            100% {
                transform: translateY(0px) translateX(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-25px) translateX(25px) rotate(180deg);
            }
        }

        @keyframes float3 {

            0%,
            100% {
                transform: translateY(0px) translateX(0px) scale(1);
            }

            33% {
                transform: translateY(15px) translateX(-20px) scale(1.1);
            }

            66% {
                transform: translateY(-20px) translateX(10px) scale(0.9);
            }
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

        .search-container {
            position: relative;
            flex: 1;
            max-width: 500px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .search-input:focus {
            outline: none;
            border-color: #8b5fbf;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b5fbf;
            font-size: 1.1rem;
        }

        .search-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: translateY(-50%) scale(1.05);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            position: relative;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #4b5563;
            cursor: pointer;
        }

        .header-icon:hover {
            background: rgba(139, 95, 191, 0.1);
            color: #8b5fbf;
        }

        .cart-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, #f093fb, #8b5fbf);
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(139, 95, 191, 0.3);
        }

        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.2);
            border: 1px solid rgba(139, 95, 191, 0.1);
            padding: 8px 0;
            min-width: 120px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
        }

        .user-dropdown:hover .dropdown-menu-custom {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item-custom {
            display: block;
            padding: 8px 16px;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .dropdown-item-custom:hover {
            background: rgba(139, 95, 191, 0.1);
            color: #8b5fbf;
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

        .sidebar-toggle.sidebar-hidden {
            left: 10px;
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

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
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

            .header-container {
                flex-direction: column;
                gap: 16px;
            }

            .search-container {
                order: 3;
                max-width: none;
            }

            .container {
                margin: 1rem;
                padding: 1rem !important;
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
    <!-- Animated Background Circles -->
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
            <p>Manage your food categories</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="#" class="active"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="brand.php"><i class="fas fa-tags"></i> Brands</a></li>
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
            <div class="d-flex align-items-center justify-content-between header-container">
                <!-- Logo -->
                <a href="../index.php" class="logo">
                    flavorhub<span class="co">co</span>
                </a>

                <!-- Search Bar -->
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search food in FlavorHub">
                    <button class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Notifications -->
                    <div class="header-icon">
                        <i class="fas fa-bell"></i>
                    </div>

                    <!-- About/Help -->
                    <span class="d-none d-md-inline text-muted">About</span>
                    <span class="d-none d-md-inline text-muted">Help</span>
                    <span class="d-none d-md-inline text-muted">Contact</span>

                    <!-- Cart -->
                    <div class="header-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge">0</span>
                    </div>

                    <!-- User Avatar with Dropdown -->
                    <div class="user-dropdown">
                        <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['name'] ?? 'Chef') ?>">
                            <?= strtoupper(substr($_SESSION['name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <div class="dropdown-menu-custom">
                            <a href="../login/logout.php" class="dropdown-item-custom">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="p-4">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 mb-0">Categories</h1>
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm">Back to Home</a>
                </div>

                <form id="addForm" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="category_name" placeholder="New category name" required>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary">Add Category</button>
                    </div>
                    <div class="col-12">
                        <div id="addMsg" class="small"></div>
                    </div>
                </form>

                <table class="table table-striped align-middle" id="catTable">
                    <thead>
                        <tr>
                            <th style="width:70%">Name</th>
                            <th style="width:30%">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/category.js"></script>

    <script>
        // Search functionality
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        document.querySelector('.search-btn').addEventListener('click', performSearch);

        function performSearch() {
            const query = document.querySelector('.search-input').value.trim();
            if (query) {
                console.log('Searching for:', query);
                alert('Search functionality will be implemented here: ' + query);
            }
        }

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