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
    </style>
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
                        <a href="all_product.php" class="btn btn-light btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Shop Now
                        </a>
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

    <!-- Categories -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Shop by Category</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card text-center p-4">
                        <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                        <h5>Mobile Devices</h5>
                        <a href="#" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-4">
                        <i class="fas fa-laptop fa-3x text-primary mb-3"></i>
                        <h5>Computing</h5>
                        <a href="#" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-4">
                        <i class="fas fa-camera fa-3x text-primary mb-3"></i>
                        <h5>Photography</h5>
                        <a href="#" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-4">
                        <i class="fas fa-tools fa-3x text-primary mb-3"></i>
                        <h5>Repair Service</h5>
                        <a href="#" class="btn btn-outline-primary">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>