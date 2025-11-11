<?php
require_once(__DIR__ . '/settings/core.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Repair Services - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: #1a1a1a;
        }

        .header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .garage {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .hero-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 80px 0;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }

        .service-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .service-icon {
            width: 60px;
            height: 60px;
            background: #000000;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 1.5rem;
        }

        .service-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .service-description {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .service-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .service-features li {
            padding: 0.5rem 0;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .service-features li::before {
            content: "✓";
            color: #16a34a;
            font-weight: bold;
        }

        .service-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .book-btn {
            background: #000000;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
        }

        .book-btn:hover {
            background: #333333;
            color: white;
            transform: translateY(-1px);
        }

        .back-btn {
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .back-btn:hover {
            background: #e5e7eb;
            color: #1f2937;
        }

        .contact-section {
            background: #f8fafc;
            padding: 4rem 0;
            margin-top: 4rem;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .contact-item {
            text-align: center;
            padding: 2rem;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: #000000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a href="index.php" class="logo">
                    Gadget<span class="garage">Garage</span>
                </a>
                <nav class="d-flex gap-3">
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Home
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Professional Repair Services</h1>
            <p class="hero-subtitle">Expert technicians, genuine parts, fast turnaround times</p>
        </div>
    </section>

    <!-- Services -->
    <section class="container">
        <div class="services-grid">
            <!-- Phone Repair -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="service-title">Phone Repair</h3>
                <p class="service-description">Professional repair for all smartphone brands including iPhone, Samsung, Google Pixel, and more.</p>
                <ul class="service-features">
                    <li>Screen replacement</li>
                    <li>Battery replacement</li>
                    <li>Camera repair</li>
                    <li>Water damage recovery</li>
                    <li>Software troubleshooting</li>
                </ul>
                <div class="service-price">Starting from GHS 120</div>
                <a href="#" class="book-btn">Book Phone Repair</a>
            </div>

            <!-- Laptop Repair -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-laptop"></i>
                </div>
                <h3 class="service-title">Laptop & Desktop Repair</h3>
                <p class="service-description">Complete computer repair services for Windows, Mac, and Linux systems.</p>
                <ul class="service-features">
                    <li>Hardware diagnostics</li>
                    <li>SSD/HDD replacement</li>
                    <li>RAM upgrades</li>
                    <li>Virus removal</li>
                    <li>OS installation</li>
                </ul>
                <div class="service-price">Starting from GHS 200</div>
                <a href="#" class="book-btn">Book Computer Repair</a>
            </div>

            <!-- Tablet Repair -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-tablet-alt"></i>
                </div>
                <h3 class="service-title">Tablet Repair</h3>
                <p class="service-description">Expert repair services for iPad, Samsung tablets, and other tablet devices.</p>
                <ul class="service-features">
                    <li>Screen repair</li>
                    <li>Charging port fix</li>
                    <li>Home button repair</li>
                    <li>Speaker replacement</li>
                    <li>Software issues</li>
                </ul>
                <div class="service-price">Starting from GHS 150</div>
                <a href="#" class="book-btn">Book Tablet Repair</a>
            </div>

            <!-- Camera Repair -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-camera"></i>
                </div>
                <h3 class="service-title">Camera Repair</h3>
                <p class="service-description">Professional repair services for digital cameras, lenses, and video equipment.</p>
                <ul class="service-features">
                    <li>Lens calibration</li>
                    <li>Sensor cleaning</li>
                    <li>Button repairs</li>
                    <li>LCD screen fix</li>
                    <li>Memory card slot repair</li>
                </ul>
                <div class="service-price">Starting from GHS 300</div>
                <a href="#" class="book-btn">Book Camera Repair</a>
            </div>

            <!-- Data Recovery -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <h3 class="service-title">Data Recovery</h3>
                <p class="service-description">Recover lost data from damaged drives, phones, and other storage devices.</p>
                <ul class="service-features">
                    <li>Hard drive recovery</li>
                    <li>Phone data recovery</li>
                    <li>SD card recovery</li>
                    <li>SSD recovery</li>
                    <li>Emergency recovery</li>
                </ul>
                <div class="service-price">Starting from GHS 400</div>
                <a href="#" class="book-btn">Book Data Recovery</a>
            </div>

            <!-- Device Setup -->
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3 class="service-title">Device Setup & Support</h3>
                <p class="service-description">Complete setup and configuration services for your new devices.</p>
                <ul class="service-features">
                    <li>Initial device setup</li>
                    <li>Data transfer</li>
                    <li>App installation</li>
                    <li>Security configuration</li>
                    <li>Training & support</li>
                </ul>
                <div class="service-price">Starting from GHS 80</div>
                <a href="#" class="book-btn">Book Setup Service</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <h2 class="text-center mb-5">Contact Our Repair Center</h2>
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h4>Call Us</h4>
                    <p>+233 XX XXX XXXX</p>
                    <p>Mon-Sat: 8AM-6PM</p>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4>Email Support</h4>
                    <p>repair@gadgetgarage.com</p>
                    <p>24/7 Response</p>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4>Visit Our Store</h4>
                    <p>123 Tech Street</p>
                    <p>Accra, Ghana</p>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Turnaround Time</h4>
                    <p>Same-day service</p>
                    <p>For most repairs</p>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>