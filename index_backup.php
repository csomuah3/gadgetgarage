<?php
// Simple working index page
session_start();

// Basic variables
$is_logged_in = false;
$is_admin = false;
$cart_count = 0;
$categories = [];
$brands = [];
$current_language = 'en';

// Simple translation function
function t($key) {
    return $key; // Return the key itself for now
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gadget Garage - Tech Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Simple Header -->
    <header class="bg-dark text-white py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-laptop me-2"></i>Gadget Garage</h2>
                <div>
                    <a href="login/login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="cart.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Cart (<?= $cart_count ?>)
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-primary text-white py-5">
        <div class="container text-center">
            <h1 class="display-4 mb-3">Welcome to Gadget Garage</h1>
            <p class="lead">Your one-stop shop for the latest tech gadgets and electronics</p>
            <a href="all_product.php" class="btn btn-light btn-lg">Shop Now</a>
        </div>
    </section>

    <!-- Quick Navigation -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                            <h5>Mobile Devices</h5>
                            <p>Smartphones, Tablets & Accessories</p>
                            <a href="mobile_devices.php" class="btn btn-outline-primary">View All</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-laptop fa-3x text-primary mb-3"></i>
                            <h5>Computing</h5>
                            <p>Laptops, Desktops & Components</p>
                            <a href="computing.php" class="btn btn-outline-primary">View All</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-camera fa-3x text-primary mb-3"></i>
                            <h5>Photography</h5>
                            <p>Cameras & Video Equipment</p>
                            <a href="photography_video.php" class="btn btn-outline-primary">View All</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-wrench fa-3x text-primary mb-3"></i>
                            <h5>Repair Service</h5>
                            <p>Professional Tech Repair</p>
                            <a href="repair_services.php" class="btn btn-outline-primary">Learn More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2024 Gadget Garage. All rights reserved.</p>
            <p>
                <a href="contact.php" class="text-light me-3">Contact</a>
                <a href="terms_conditions.php" class="text-light">Terms & Conditions</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>