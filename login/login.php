<?php
session_start();
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../controllers/brand_controller.php');

// If user is already logged in, redirect to index
if (check_login()) {
    header("Location: ../index.php");
    exit();
}

$is_logged_in = false;
$is_admin = false;
$cart_count = 0;

// Get all brands for dropdown
try {
    $brands = get_all_brands_ctr();
    if (!$brands) $brands = [];
} catch (Exception $e) {
    $brands = [];
    error_log("Error fetching brands: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-translate="login_title">Login - Gadget Garage</title>
    <meta name="description" data-translate="login_description" content="Log in to your Gadget Garage account to access premium tech devices and exclusive deals.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="../includes/header-styles.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ffffff;
            color: #1a1a1a;
            overflow-x: hidden;
        }

        /* Promotional Banner Styles */
        .promo-banner {
            background: linear-gradient(90deg, #16a085, #f39c12);
            color: white;
            text-align: center;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .promo-banner .fas {
            font-size: 16px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-5px);
            }
            60% {
                transform: translateY(-3px);
            }
        }

        /* Header Styles */
        .main-header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 44px;
            z-index: 1000;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Login Form Section */
        .login-section {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            position: relative;
            background: #f8fafc;
        }

        /* Circuit Board Pattern Background */
        .circuit-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #2dd4bf;
            background-image:
                /* Main circuit lines */
                linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px),
                linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
                /* Circuit nodes */
                radial-gradient(circle at 25% 25%, rgba(255,255,255,0.2) 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, rgba(255,255,255,0.2) 2px, transparent 2px),
                radial-gradient(circle at 75% 25%, rgba(255,255,255,0.15) 1px, transparent 1px),
                radial-gradient(circle at 25% 75%, rgba(255,255,255,0.15) 1px, transparent 1px),
                /* Connection lines */
                linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.1) 40%, rgba(255,255,255,0.1) 60%, transparent 60%),
                linear-gradient(-45deg, transparent 40%, rgba(255,255,255,0.1) 40%, rgba(255,255,255,0.1) 60%, transparent 60%);
            background-size:
                40px 40px,
                40px 40px,
                80px 80px,
                80px 80px,
                60px 60px,
                60px 60px,
                120px 120px,
                120px 120px;
            animation: circuitFlow 20s linear infinite;
        }

        @keyframes circuitFlow {
            0% {
                background-position: 0px 0px, 0px 0px, 0px 0px, 0px 0px, 0px 0px, 0px 0px, 0px 0px, 0px 0px;
            }
            100% {
                background-position: 40px 40px, 40px 40px, 80px 80px, 80px 80px, 60px 60px, 60px 60px, 120px 120px, 120px 120px;
            }
        }

        /* Login Form Container */
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.15),
                0 10px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow:
                0 35px 80px rgba(0, 0, 0, 0.2),
                0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .login-title {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
            text-align: center;
            margin-bottom: 40px;
            letter-spacing: -0.02em;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }

        .forgot-password a {
            color: #6b7280;
            text-decoration: underline;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #3b82f6;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Fly-up Animation */
        .fly-up {
            animation: flyUp 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes flyUp {
            0% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            50% {
                transform: translateY(-30px) scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: translateY(-100px) scale(0.9);
                opacity: 0;
            }
        }

        .signup-link {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .signup-link a {
            color: #3b82f6;
            text-decoration: underline;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: #1d4ed8;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Error Messages */
        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 0.9rem;
            display: none;
        }

        /* Success Messages */
        .success-message {
            background: #f0fdf4;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #16a34a;
            font-size: 0.9rem;
            display: none;
        }

        /* Dark Mode Promotional Banner Styles */
        @media (prefers-color-scheme: dark) {
            .promo-banner {
                background: linear-gradient(90deg, #1a202c, #2d3748);
                color: #f7fafc;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                margin: 20px;
                padding: 40px 30px;
            }

            .login-title {
                font-size: 1.75rem;
            }

            .main-header {
                top: 36px;
            }
        }
    </style>
</head>

<body>
    <!-- Promotional Banner -->
    <div class="promo-banner">
        <i class="fas fa-shipping-fast"></i>
        <span data-translate="free_next_day_delivery">Free Next Day Delivery on Orders Above GH₵2,000!</span>
    </div>

    <!-- Main Header -->
    <header class="main-header animate__animated animate__fadeInDown">
        <div class="container-fluid" style="padding: 0 120px 0 95px;">
            <div class="d-flex align-items-center w-100 header-container" style="justify-content: space-between;">
                <!-- Logo - Far Left -->
                <a href="../index.php" class="logo">
                    <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                         alt="Gadget Garage"
                         style="height: 40px; width: auto; object-fit: contain;">
                </a>

                <!-- Center Content -->
                <div class="d-flex align-items-center" style="flex: 1; justify-content: center; gap: 60px;">
                    <!-- Search Bar -->
                    <form class="search-container" method="GET" action="../product_search_result.php">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="query" class="search-input" data-translate="search_placeholder" placeholder="Search phones, laptops, cameras..." required>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <!-- Tech Revival Section -->
                    <div class="tech-revival-section">
                        <i class="fas fa-recycle tech-revival-icon"></i>
                        <div>
                            <p class="tech-revival-text" data-translate="bring_retired_tech">Bring Retired Tech</p>
                            <p class="contact-number">055-138-7578</p>
                        </div>
                    </div>
                </div>

                <!-- User Actions - Far Right -->
                <div class="user-actions" style="display: flex; align-items: center; gap: 12px;">
                    <span style="color: #ddd;">|</span>
                    <!-- Not logged in: Register | Login -->
                    <a href="register.php" class="login-btn me-2" data-translate="register">Register</a>
                    <span style="color: #008060; font-weight: 600;" data-translate="login">Login</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="container-fluid px-0">
            <div class="nav-menu">
                <!-- Shop by Brands Button -->
                <div class="shop-categories-btn" onmouseenter="showDropdown()" onmouseleave="hideDropdown()">
                    <button class="categories-button">
                        <i class="fas fa-tags"></i>
                        <span data-translate="shop_by_brands">SHOP BY BRANDS</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="brands-dropdown" id="shopDropdown">
                        <h4 data-translate="all_brands">All Brands</h4>
                        <ul>
                            <?php if (!empty($brands)): ?>
                                <?php foreach ($brands as $brand): ?>
                                    <li><a href="../all_product.php?brand=<?php echo urlencode($brand['brand_id']); ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($brand['brand_name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="../all_product.php"><i class="fas fa-tag"></i> All Products</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <a href="../index.php" class="nav-item" data-translate="home">HOME</a>

                <!-- Shop Dropdown -->
                <div class="nav-dropdown" onmouseenter="showShopDropdown()" onmouseleave="hideShopDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="shop">SHOP</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="mega-dropdown" id="shopCategoryDropdown">
                        <div class="dropdown-content">
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../mobile_devices.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="mobile_devices">Mobile Devices</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span data-translate="smartphones">Smartphones</span></a></li>
                                    <li><a href="../all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span data-translate="ipads">iPads</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../computing.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="computing">Computing</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span data-translate="laptops">Laptops</span></a></li>
                                    <li><a href="../all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span data-translate="desktops">Desktops</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../photography_video.php" style="text-decoration: none; color: inherit;">
                                        <span data-translate="photography_video">Photography & Video</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=cameras"><i class="fas fa-camera"></i> <span data-translate="cameras">Cameras</span></a></li>
                                    <li><a href="../all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span data-translate="video_equipment">Video Equipment</span></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="../repair_services.php" class="nav-item" data-translate="repair_studio">REPAIR STUDIO</a>
                <a href="../device_drop.php" class="nav-item" data-translate="device_drop">DEVICE DROP</a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span data-translate="more">MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="../contact.php"><i class="fas fa-phone"></i> <span data-translate="contact_us">Contact</span></a></li>
                            <li><a href="../terms_conditions.php"><i class="fas fa-file-contract"></i> <span data-translate="terms_conditions">Terms & Conditions</span></a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="../flash_deals.php" class="nav-item flash-deal">⚡ <span data-translate="flash_deal">FLASH DEAL</span></a>
            </div>
        </div>
    </nav>

    <!-- Login Form Section -->
    <section class="login-section">
        <div class="circuit-background"></div>
        <div class="login-container" id="loginContainer">
            <h1 class="login-title" data-translate="login_to_account">Log in to your account</h1>

            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>

            <form id="loginForm" method="POST" action="login_user_action.php">
                <div class="form-group">
                    <label for="email" class="form-label" data-translate="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required
                           data-translate="email_placeholder" placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label" data-translate="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required
                           data-translate="password_placeholder" placeholder="Enter your password">
                    <div class="forgot-password">
                        <a href="#" data-translate="forgot_password">Forgot your password?</a>
                    </div>
                </div>

                <button type="submit" class="login-btn" id="loginButton">
                    <div class="loading-spinner" id="loadingSpinner"></div>
                    <span data-translate="sign_in">SIGN IN</span>
                </button>
            </form>

            <div class="signup-link">
                <span data-translate="no_account">No account yet?</span>
                <a href="register.php" data-translate="create_account">Create an account</a>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/header.js"></script>

    <script>
        // Login form handling with fly-up animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const loginButton = document.getElementById('loginButton');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const loginContainer = document.getElementById('loginContainer');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            // Hide any existing messages
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            // Show loading state
            loadingSpinner.style.display = 'inline-block';
            loginButton.disabled = true;

            const formData = new FormData(this);

            fetch('login_user_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loadingSpinner.style.display = 'none';
                loginButton.disabled = false;

                if (data.status === 'success') {
                    // Show success message
                    successMessage.textContent = data.message || 'Login successful!';
                    successMessage.style.display = 'block';

                    // Add fly-up animation
                    loginContainer.classList.add('fly-up');

                    // Redirect after animation
                    setTimeout(() => {
                        window.location.href = '../index.php';
                    }, 800);
                } else {
                    // Show error message
                    errorMessage.textContent = data.message || 'Login failed. Please try again.';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingSpinner.style.display = 'none';
                loginButton.disabled = false;
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.style.display = 'block';
            });
        });

        // Translation System
        const translations = {
            en: {
                "login_title": "Login - Gadget Garage",
                "login_description": "Log in to your Gadget Garage account to access premium tech devices and exclusive deals.",
                "free_next_day_delivery": "Free Next Day Delivery on Orders Above GH₵2,000!",
                "search_placeholder": "Search phones, laptops, cameras...",
                "bring_retired_tech": "Bring Retired Tech",
                "register": "Register",
                "login": "Login",
                "shop_by_brands": "SHOP BY BRANDS",
                "all_brands": "All Brands",
                "home": "HOME",
                "shop": "SHOP",
                "mobile_devices": "Mobile Devices",
                "computing": "Computing",
                "photography_video": "Photography & Video",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "laptops": "Laptops",
                "desktops": "Desktops",
                "cameras": "Cameras",
                "video_equipment": "Video Equipment",
                "repair_studio": "REPAIR STUDIO",
                "device_drop": "DEVICE DROP",
                "more": "MORE",
                "contact_us": "Contact",
                "terms_conditions": "Terms & Conditions",
                "flash_deal": "FLASH DEAL",
                "login_to_account": "Log in to your account",
                "email": "Email",
                "password": "Password",
                "email_placeholder": "Enter your email",
                "password_placeholder": "Enter your password",
                "forgot_password": "Forgot your password?",
                "sign_in": "SIGN IN",
                "no_account": "No account yet?",
                "create_account": "Create an account"
            },
            es: {
                "login_title": "Iniciar Sesión - Gadget Garage",
                "login_description": "Inicia sesión en tu cuenta de Gadget Garage para acceder a dispositivos tecnológicos premium y ofertas exclusivas.",
                "free_next_day_delivery": "¡Entrega Gratis al Día Siguiente en Pedidos Superiores a GH₵2,000!",
                "search_placeholder": "Buscar teléfonos, laptops, cámaras...",
                "bring_retired_tech": "Traer Tecnología Retirada",
                "register": "Registrarse",
                "login": "Iniciar Sesión",
                "shop_by_brands": "COMPRAR POR MARCAS",
                "all_brands": "Todas las Marcas",
                "home": "INICIO",
                "shop": "TIENDA",
                "mobile_devices": "Dispositivos Móviles",
                "computing": "Informática",
                "photography_video": "Fotografía y Video",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "laptops": "Laptops",
                "desktops": "Escritorios",
                "cameras": "Cámaras",
                "video_equipment": "Equipo de Video",
                "repair_studio": "ESTUDIO DE REPARACIÓN",
                "device_drop": "ENTREGA DE DISPOSITIVO",
                "more": "MÁS",
                "contact_us": "Contáctanos",
                "terms_conditions": "Términos y Condiciones",
                "flash_deal": "OFERTA FLASH",
                "login_to_account": "Inicia sesión en tu cuenta",
                "email": "Correo Electrónico",
                "password": "Contraseña",
                "email_placeholder": "Ingresa tu correo electrónico",
                "password_placeholder": "Ingresa tu contraseña",
                "forgot_password": "¿Olvidaste tu contraseña?",
                "sign_in": "INICIAR SESIÓN",
                "no_account": "¿No tienes cuenta?",
                "create_account": "Crear una cuenta"
            },
            fr: {
                "login_title": "Connexion - Gadget Garage",
                "login_description": "Connectez-vous à votre compte Gadget Garage pour accéder aux appareils technologiques premium et aux offres exclusives.",
                "free_next_day_delivery": "Livraison Gratuite le Lendemain sur Commandes Supérieures à GH₵2,000!",
                "search_placeholder": "Rechercher téléphones, ordinateurs, appareils photo...",
                "bring_retired_tech": "Apporter de la Technologie Retraite",
                "register": "S'inscrire",
                "login": "Connexion",
                "shop_by_brands": "ACHETER PAR MARQUES",
                "all_brands": "Toutes les Marques",
                "home": "ACCUEIL",
                "shop": "BOUTIQUE",
                "mobile_devices": "Appareils Mobiles",
                "computing": "Informatique",
                "photography_video": "Photo et Vidéo",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "laptops": "Ordinateurs Portables",
                "desktops": "Ordinateurs de Bureau",
                "cameras": "Appareils Photo",
                "video_equipment": "Équipement Vidéo",
                "repair_studio": "STUDIO DE RÉPARATION",
                "device_drop": "DÉPÔT D'APPAREIL",
                "more": "PLUS",
                "contact_us": "Nous Contacter",
                "terms_conditions": "Termes et Conditions",
                "flash_deal": "VENTE FLASH",
                "login_to_account": "Connectez-vous à votre compte",
                "email": "Email",
                "password": "Mot de Passe",
                "email_placeholder": "Entrez votre email",
                "password_placeholder": "Entrez votre mot de passe",
                "forgot_password": "Mot de passe oublié?",
                "sign_in": "SE CONNECTER",
                "no_account": "Pas de compte?",
                "create_account": "Créer un compte"
            },
            de: {
                "login_title": "Anmelden - Gadget Garage",
                "login_description": "Melden Sie sich bei Ihrem Gadget Garage-Konto an, um auf Premium-Tech-Geräte und exklusive Angebote zuzugreifen.",
                "free_next_day_delivery": "Kostenlose Lieferung am nächsten Tag bei Bestellungen über GH₵2,000!",
                "search_placeholder": "Telefone, Laptops, Kameras suchen...",
                "bring_retired_tech": "Alte Technologie Bringen",
                "register": "Registrieren",
                "login": "Anmelden",
                "shop_by_brands": "NACH MARKEN EINKAUFEN",
                "all_brands": "Alle Marken",
                "home": "STARTSEITE",
                "shop": "SHOP",
                "mobile_devices": "Mobile Geräte",
                "computing": "Computer",
                "photography_video": "Foto & Video",
                "smartphones": "Smartphones",
                "ipads": "iPads",
                "laptops": "Laptops",
                "desktops": "Desktop-Computer",
                "cameras": "Kameras",
                "video_equipment": "Video-Ausrüstung",
                "repair_studio": "REPARATUR STUDIO",
                "device_drop": "GERÄT ABGEBEN",
                "more": "MEHR",
                "contact_us": "Kontakt",
                "terms_conditions": "Geschäftsbedingungen",
                "flash_deal": "BLITZ ANGEBOT",
                "login_to_account": "Bei Ihrem Konto anmelden",
                "email": "E-Mail",
                "password": "Passwort",
                "email_placeholder": "E-Mail eingeben",
                "password_placeholder": "Passwort eingeben",
                "forgot_password": "Passwort vergessen?",
                "sign_in": "ANMELDEN",
                "no_account": "Noch kein Konto?",
                "create_account": "Konto erstellen"
            }
        };

        function translate(key, language = null) {
            const lang = language || localStorage.getItem('selectedLanguage') || 'en';
            return translations[lang] && translations[lang][key] ? translations[lang][key] : translations.en[key] || key;
        }

        function applyTranslations() {
            const currentLang = localStorage.getItem('selectedLanguage') || 'en';

            document.querySelectorAll('[data-translate]').forEach(element => {
                const key = element.getAttribute('data-translate');
                const translation = translate(key, currentLang);

                if (element.tagName === 'INPUT' && (element.type === 'text' || element.type === 'email' || element.type === 'password')) {
                    element.placeholder = translation;
                } else if (element.tagName === 'TITLE') {
                    element.textContent = translation;
                } else if (element.tagName === 'META' && element.getAttribute('name') === 'description') {
                    element.setAttribute('content', translation);
                } else {
                    element.textContent = translation;
                }
            });
        }

        function changeLanguage(language) {
            localStorage.setItem('selectedLanguage', language);
            applyTranslations();
        }

        // Initialize translations on page load
        document.addEventListener('DOMContentLoaded', function() {
            applyTranslations();
        });
    </script>
</body>
</html>