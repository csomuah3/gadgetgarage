<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 80vh;
            display: flex;
            align-items: center;
            color: white;
        }

        .category-card {
            transition: all 0.3s ease;
            height: 100%;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Simple Header -->
    <header class="bg-dark text-white py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                         alt="Gadget Garage" style="height: 40px;" class="me-3">
                    <h2>Gadget Garage</h2>
                </div>
                <div>
                    <a href="login/login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="cart.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Cart (0)
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="animate__animated animate__fadeInLeft">
                        <h1 class="display-4 fw-bold mb-4">Welcome to Gadget Garage</h1>
                        <p class="lead mb-4">Your one-stop shop for the latest tech gadgets and electronics</p>
                        <div class="d-flex gap-3">
                            <a href="all_product.php" class="btn btn-light btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>Shop Now
                            </a>
                            <a href="repair_services.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-tools me-2"></i>Repair Services
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center animate__animated animate__fadeInRight">
                        <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                             alt="Gadget Garage" class="img-fluid" style="max-height: 400px;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Shop by Category</h2>
                <p class="text-muted">Browse our wide selection of tech products</p>
            </div>
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card category-card text-center p-4">
                        <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                        <h5>Mobile Devices</h5>
                        <p class="text-muted mb-3">Smartphones & Tablets</p>
                        <a href="mobile_devices.php" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card category-card text-center p-4">
                        <i class="fas fa-laptop fa-3x text-primary mb-3"></i>
                        <h5>Computing</h5>
                        <p class="text-muted mb-3">Laptops & Desktops</p>
                        <a href="computing.php" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card category-card text-center p-4">
                        <i class="fas fa-camera fa-3x text-primary mb-3"></i>
                        <h5>Photography</h5>
                        <p class="text-muted mb-3">Cameras & Equipment</p>
                        <a href="photography_video.php" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card category-card text-center p-4">
                        <i class="fas fa-tools fa-3x text-primary mb-3"></i>
                        <h5>Repair Studio</h5>
                        <p class="text-muted mb-3">Professional Repair</p>
                        <a href="repair_services.php" class="btn btn-outline-primary">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="fw-bold mb-3">Need Tech Repair?</h2>
            <p class="lead mb-4">Get your devices fixed by our expert technicians</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="device_drop.php" class="btn btn-light btn-lg">
                    <i class="fas fa-hand-holding me-2"></i>Device Drop
                </a>
                <a href="repair_services.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-phone me-2"></i>Call Now: 055-138-7578
                </a>
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