<?php
session_start();
require_once '../settings/db_class.php';

// Handle language switching
if (isset($_GET['lang'])) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Set default language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'en';
}

$lang = $_SESSION['language'];

// Translation function
function translate($key, $lang) {
    $translations = [
        'en' => [
            'site_name' => 'Gadget Garage',
            'promo_text' => '🎉 Black Friday Sale! Up to 50% OFF on all electronics! Free shipping on orders over $50! 🚚',
            'menu_home' => 'Home',
            'menu_products' => 'Products',
            'menu_smartphones' => 'Smartphones',
            'menu_laptops' => 'Laptops',
            'menu_tablets' => 'Tablets',
            'menu_accessories' => 'Accessories',
            'menu_mobile_devices' => 'Mobile Devices',
            'menu_computing' => 'Computing',
            'menu_contact' => 'Contact',
            'menu_about' => 'About',
            'menu_login' => 'Login',
            'menu_register' => 'Register',
            'welcome_back' => 'Log in to your account',
            'login_subtitle' => 'Access your Gadget Garage account to start shopping!',
            'email_placeholder' => 'Email Address',
            'password_placeholder' => 'Password',
            'login_button' => 'Sign In',
            'signing_in' => 'Signing In...',
            'register_link' => 'No account yet? Create an account',
            'forgot_password' => 'Forgot your password?',
            'footer_links' => 'Quick Links',
            'footer_categories' => 'Categories',
            'footer_support' => 'Customer Support',
            'footer_contact' => 'Contact Us',
            'footer_about' => 'About Us',
            'footer_privacy' => 'Privacy Policy',
            'footer_terms' => 'Terms of Service',
            'footer_copyright' => '© 2024 Gadget Garage. All rights reserved.',
            'search_placeholder' => 'Search products...'
        ],
        'es' => [
            'site_name' => 'Garaje de Gadgets',
            'promo_text' => '🎉 ¡Venta Black Friday! ¡Hasta 50% de descuento en todos los electrónicos! ¡Envío gratis en pedidos superiores a $50! 🚚',
            'menu_home' => 'Inicio',
            'menu_products' => 'Productos',
            'menu_smartphones' => 'Teléfonos',
            'menu_laptops' => 'Portátiles',
            'menu_tablets' => 'Tabletas',
            'menu_accessories' => 'Accesorios',
            'menu_mobile_devices' => 'Dispositivos Móviles',
            'menu_computing' => 'Computación',
            'menu_contact' => 'Contacto',
            'menu_about' => 'Acerca de',
            'menu_login' => 'Iniciar Sesión',
            'menu_register' => 'Registrarse',
            'welcome_back' => 'Inicia sesión en tu cuenta',
            'login_subtitle' => '¡Accede a tu cuenta de Garaje de Gadgets para comenzar a comprar!',
            'email_placeholder' => 'Correo Electrónico',
            'password_placeholder' => 'Contraseña',
            'login_button' => 'Iniciar Sesión',
            'signing_in' => 'Iniciando Sesión...',
            'register_link' => '¿No tienes cuenta? Crea una cuenta',
            'forgot_password' => '¿Olvidaste tu contraseña?',
            'footer_links' => 'Enlaces Rápidos',
            'footer_categories' => 'Categorías',
            'footer_support' => 'Atención al Cliente',
            'footer_contact' => 'Contáctanos',
            'footer_about' => 'Acerca de Nosotros',
            'footer_privacy' => 'Política de Privacidad',
            'footer_terms' => 'Términos de Servicio',
            'footer_copyright' => '© 2024 Garaje de Gadgets. Todos los derechos reservados.',
            'search_placeholder' => 'Buscar productos...'
        ],
        'fr' => [
            'site_name' => 'Garage Gadget',
            'promo_text' => '🎉 Vente Black Friday ! Jusqu\'à 50% de réduction sur tous les électroniques ! Livraison gratuite sur les commandes de plus de 50$ ! 🚚',
            'menu_home' => 'Accueil',
            'menu_products' => 'Produits',
            'menu_smartphones' => 'Smartphones',
            'menu_laptops' => 'Ordinateurs Portables',
            'menu_tablets' => 'Tablettes',
            'menu_accessories' => 'Accessoires',
            'menu_mobile_devices' => 'Appareils Mobiles',
            'menu_computing' => 'Informatique',
            'menu_contact' => 'Contact',
            'menu_about' => 'À Propos',
            'menu_login' => 'Connexion',
            'menu_register' => 'S\'inscrire',
            'welcome_back' => 'Connectez-vous à votre compte',
            'login_subtitle' => 'Accédez à votre compte Garage Gadget pour commencer vos achats !',
            'email_placeholder' => 'Adresse E-mail',
            'password_placeholder' => 'Mot de Passe',
            'login_button' => 'Se Connecter',
            'signing_in' => 'Connexion...',
            'register_link' => 'Pas de compte ? Créer un compte',
            'forgot_password' => 'Mot de passe oublié ?',
            'footer_links' => 'Liens Rapides',
            'footer_categories' => 'Catégories',
            'footer_support' => 'Support Client',
            'footer_contact' => 'Nous Contacter',
            'footer_about' => 'À Propos de Nous',
            'footer_privacy' => 'Politique de Confidentialité',
            'footer_terms' => 'Conditions de Service',
            'footer_copyright' => '© 2024 Garage Gadget. Tous droits réservés.',
            'search_placeholder' => 'Rechercher des produits...'
        ],
        'de' => [
            'site_name' => 'Gadget Garage',
            'promo_text' => '🎉 Black Friday Sale! Bis zu 50% Rabatt auf alle Elektronikprodukte! Kostenloser Versand bei Bestellungen über $50! 🚚',
            'menu_home' => 'Startseite',
            'menu_products' => 'Produkte',
            'menu_smartphones' => 'Smartphones',
            'menu_laptops' => 'Laptops',
            'menu_tablets' => 'Tablets',
            'menu_accessories' => 'Zubehör',
            'menu_mobile_devices' => 'Mobile Geräte',
            'menu_computing' => 'Computer',
            'menu_contact' => 'Kontakt',
            'menu_about' => 'Über Uns',
            'menu_login' => 'Anmelden',
            'menu_register' => 'Registrieren',
            'welcome_back' => 'Bei Ihrem Konto anmelden',
            'login_subtitle' => 'Greifen Sie auf Ihr Gadget Garage-Konto zu, um mit dem Einkaufen zu beginnen!',
            'email_placeholder' => 'E-Mail-Adresse',
            'password_placeholder' => 'Passwort',
            'login_button' => 'Anmelden',
            'signing_in' => 'Anmeldung...',
            'register_link' => 'Noch kein Konto? Konto erstellen',
            'forgot_password' => 'Passwort vergessen?',
            'footer_links' => 'Schnelllinks',
            'footer_categories' => 'Kategorien',
            'footer_support' => 'Kundensupport',
            'footer_contact' => 'Kontaktieren Sie Uns',
            'footer_about' => 'Über Uns',
            'footer_privacy' => 'Datenschutzrichtlinie',
            'footer_terms' => 'Nutzungsbedingungen',
            'footer_copyright' => '© 2024 Gadget Garage. Alle Rechte vorbehalten.',
            'search_placeholder' => 'Produkte suchen...'
        ]
    ];

    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Handle login
$login_error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = 'Please enter a valid email address.';
    } else {
        $db = new db_connection();

        $email_escaped = mysqli_real_escape_string($db->db_conn(), $email);
        $sql = "SELECT * FROM customer WHERE customer_email = '$email_escaped'";

        $user = $db->db_fetch_one($sql);

        if ($user && password_verify($password, $user['customer_pass'])) {
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['customer_name'];
            $_SESSION['user_email'] = $user['customer_email'];
            $_SESSION['email'] = $user['customer_email'];
            $_SESSION['role'] = $user['user_role'];
            $_SESSION['name'] = $user['customer_name'];

            $login_success = true;
        } else {
            $login_error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('menu_login', $lang); ?> - <?php echo translate('site_name', $lang); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* Promotional Banner */
        .promo-banner {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            color: white;
            text-align: center;
            padding: 10px 0;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 1040;
            font-size: 14px;
        }

        /* Main Header */
        .main-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 44px;
            z-index: 1030;
        }

        .header-content {
            padding: 15px 0;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            text-decoration: none;
        }

        .search-container {
            position: relative;
            max-width: 500px;
        }

        .search-input {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #007bff;
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #007bff;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
        }

        .search-btn:hover {
            background: #0056b3;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .language-selector select {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px;
            background: white;
        }

        .auth-buttons .btn {
            margin-left: 10px;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s;
        }

        /* Main Navigation */
        .main-nav {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 0;
            position: sticky;
            top: 85px;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            width: 100%;
            padding-left: 260px;
        }

        .nav-item {
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .nav-item:hover {
            color: #008060;
            background: rgba(0, 128, 96, 0.1);
        }

        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .simple-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 160px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .simple-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .simple-dropdown ul {
            list-style: none;
            padding: 8px 0;
            margin: 0;
        }

        .simple-dropdown li {
            padding: 0;
        }

        .simple-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .simple-dropdown a:hover {
            background: #f3f4f6;
            color: #008060;
        }

        /* Shop by Brands Button */
        .shop-categories-btn {
            position: relative;
            margin-right: 20px;
        }

        .categories-button {
            background: #008060;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .categories-button:hover {
            background: #006b4e;
        }

        .brands-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .brands-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .brands-dropdown h4 {
            padding: 12px 16px;
            margin: 0;
            font-size: 0.9rem;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .brands-dropdown ul {
            list-style: none;
            padding: 8px 0;
            margin: 0;
        }

        .brands-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .brands-dropdown a:hover {
            background: #f3f4f6;
            color: #008060;
        }

        /* Mega Dropdown for Shop */
        .mega-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .mega-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content {
            display: flex;
            padding: 20px;
        }

        .dropdown-column {
            flex: 1;
            padding: 0 15px;
        }

        .dropdown-column h4 {
            color: #1f2937;
            font-size: 1rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .dropdown-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .dropdown-column li {
            margin-bottom: 8px;
        }

        .dropdown-column a {
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .dropdown-column a:hover {
            color: #008060;
        }

        .flash-deal {
            color: #dc2626 !important;
            font-weight: 700;
            margin-left: auto;
        }

        .flash-deal:hover {
            color: #991b1b !important;
        }

        /* Login Section */
        .login-section {
            min-height: calc(100vh - 200px);
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Login Card */
        .login-card {
            background: #87ceeb;
            background-image:
                linear-gradient(90deg, rgba(255,255,255,0.15) 1px, transparent 1px),
                linear-gradient(rgba(255,255,255,0.15) 1px, transparent 1px),
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.3) 2px, transparent 2px),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.3) 2px, transparent 2px);
            background-size: 40px 40px, 40px 40px, 80px 80px, 80px 80px;
            background-position: 0 0, 0 0, 0 0, 40px 40px;
            animation: circuitFlow 15s linear infinite;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.1) 50%, transparent 60%),
                radial-gradient(circle at 30% 70%, rgba(255,255,255,0.2) 0%, transparent 50%);
            animation: circuitPulse 8s ease-in-out infinite alternate;
            pointer-events: none;
        }

        .login-card .form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }

        @keyframes circuitFlow {
            0% { background-position: 0 0, 0 0, 0 0, 40px 40px; }
            100% { background-position: 40px 40px, 40px 40px, 40px 40px, 80px 80px; }
        }

        @keyframes circuitPulse {
            0% { opacity: 0.4; }
            100% { opacity: 0.8; }
        }

        .login-card h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
            font-size: 1.8rem;
        }

        .login-card .subtitle {
            color: #6c757d;
            text-align: center;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            height: 50px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(248, 249, 250, 0.8);
        }

        .form-control:focus {
            border-color: #87ceeb;
            box-shadow: 0 0 0 0.2rem rgba(135, 206, 235, 0.25);
            background: white;
        }

        .btn-login {
            width: 100%;
            height: 55px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px) scale(0.98);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login.loading {
            background: linear-gradient(135deg, #6c757d, #495057);
            cursor: not-allowed;
            transform: none;
            animation: pulse 2s infinite;
        }

        .btn-login.loading::before {
            display: none;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 8px 32px rgba(108, 117, 125, 0.3);
            }
            50% {
                box-shadow: 0 8px 32px rgba(108, 117, 125, 0.5);
            }
            100% {
                box-shadow: 0 8px 32px rgba(108, 117, 125, 0.3);
            }
        }

        /* Loading spinner enhancement */
        .btn-loading {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-loading::before {
            content: '';
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success animation */
        .btn-login.success {
            background: linear-gradient(135deg, #56ab2f, #a8e6cf);
            animation: successPulse 0.6s ease;
        }

        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .forgot-password {
            color: #4682b4;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            text-decoration: underline;
            color: #2c5aa0;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }

        .register-link a {
            color: #4682b4;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }

        /* Fly-up animation */
        @keyframes flyUp {
            0% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) scale(0.9);
                opacity: 0;
            }
        }

        .fly-up {
            animation: flyUp 0.8s ease-in-out forwards;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px 0;
            margin-top: 50px;
        }

        .footer-section h5 {
            color: #ecf0f1;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section li {
            margin-bottom: 10px;
        }

        .footer-section a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #3498db;
        }

        .footer-bottom {
            border-top: 1px solid #34495e;
            padding: 20px 0;
            text-align: center;
            margin-top: 30px;
            color: #bdc3c7;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .promo-banner {
                font-size: 12px;
                padding: 8px 0;
            }

            .main-header .header-content {
                padding: 10px 0;
            }

            .logo {
                font-size: 1.5rem;
            }

            .search-container {
                margin: 15px 0;
            }

            .header-actions {
                justify-content: center;
                gap: 10px;
            }

            .auth-buttons .btn {
                margin: 0 5px;
                padding: 8px 15px;
                font-size: 14px;
            }

            .main-nav {
                top: auto;
                position: relative;
            }

            .login-section {
                padding: 20px 15px;
                min-height: calc(100vh - 250px);
            }

            .login-card {
                padding: 30px 20px;
                margin: 0 10px;
            }

            .login-card h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 25px 15px;
            }

            .form-control {
                height: 45px;
                font-size: 14px;
            }

            .btn-login {
                height: 45px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Promotional Banner -->
    <div class="promo-banner">
        <?php echo translate('promo_text', $lang); ?>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="row align-items-center">
                    <!-- Logo -->
                    <div class="col-lg-3 col-md-6 col-12 text-center text-md-start">
                        <a href="../index.php" class="logo">
                            <i class="fas fa-bolt"></i> <?php echo translate('site_name', $lang); ?>
                        </a>
                    </div>

                    <!-- Search Bar -->
                    <div class="col-lg-6 col-md-12 col-12">
                        <div class="search-container mx-auto">
                            <input type="text" class="search-input" placeholder="<?php echo translate('search_placeholder', $lang); ?>">
                            <button class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Header Actions -->
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="header-actions justify-content-center justify-content-lg-end">
                            <!-- Language Selector -->
                            <div class="language-selector">
                                <select onchange="location = this.value;">
                                    <option value="?lang=en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>EN</option>
                                    <option value="?lang=es" <?php echo $lang === 'es' ? 'selected' : ''; ?>>ES</option>
                                    <option value="?lang=fr" <?php echo $lang === 'fr' ? 'selected' : ''; ?>>FR</option>
                                    <option value="?lang=de" <?php echo $lang === 'de' ? 'selected' : ''; ?>>DE</option>
                                </select>
                            </div>

                            <!-- Auth Buttons -->
                            <div class="auth-buttons">
                                <a href="login.php" class="btn btn-primary"><?php echo translate('menu_login', $lang); ?></a>
                                <a href="../register.php" class="btn btn-outline-primary"><?php echo translate('menu_register', $lang); ?></a>
                            </div>
                        </div>
                    </div>
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
                        <span>SHOP BY BRANDS</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="brands-dropdown" id="shopDropdown">
                        <h4>All Brands</h4>
                        <ul>
                            <li><a href="../all_product.php"><i class="fas fa-tag"></i> Apple</a></li>
                            <li><a href="../all_product.php"><i class="fas fa-tag"></i> Samsung</a></li>
                            <li><a href="../all_product.php"><i class="fas fa-tag"></i> HP</a></li>
                            <li><a href="../all_product.php"><i class="fas fa-tag"></i> Dell</a></li>
                            <li><a href="../all_product.php"><i class="fas fa-tag"></i> Sony</a></li>
                            <li><a href="../all_product.php"><i class="fas fa-tag"></i> Canon</a></li>
                        </ul>
                    </div>
                </div>

                <a href="../index.php" class="nav-item"><span>HOME</span></a>

                <!-- Shop Dropdown -->
                <div class="nav-dropdown" onmouseenter="showShopDropdown()" onmouseleave="hideShopDropdown()">
                    <a href="#" class="nav-item">
                        <span>SHOP</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="mega-dropdown" id="shopCategoryDropdown">
                        <div class="dropdown-content">
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../mobile_devices.php" style="text-decoration: none; color: inherit;">
                                        <span>Mobile Devices</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span>Smartphones</span></a></li>
                                    <li><a href="../all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span>iPads</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../computing.php" style="text-decoration: none; color: inherit;">
                                        <span>Computing</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span>Laptops</span></a></li>
                                    <li><a href="../all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span>Desktops</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="../photography_video.php" style="text-decoration: none; color: inherit;">
                                        <span>Photography & Video</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="../all_product.php?category=cameras"><i class="fas fa-camera"></i> <span>Cameras</span></a></li>
                                    <li><a href="../all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span>Video Equipment</span></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="../repair_services.php" class="nav-item"><span>REPAIR STUDIO</span></a>
                <a href="../device_drop.php" class="nav-item"><span>DEVICE DROP</span></a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span>MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="../contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                            <li><a href="../terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="../flash_deals.php" class="nav-item flash-deal">⚡ <span>FLASH DEAL</span></a>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="login-section">
        <div class="login-card" id="loginCard">
            <div class="form-container">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <a href="../index.php" class="logo text-decoration-none">
                        <i class="fas fa-bolt"></i> <?php echo translate('site_name', $lang); ?>
                    </a>
                </div>

                <h2><?php echo translate('welcome_back', $lang); ?></h2>
                <p class="subtitle"><?php echo translate('login_subtitle', $lang); ?></p>

                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $login_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($login_success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Login successful! Redirecting...
                    </div>
                    <script>
                        // Add success animation to button first
                        const loginBtn = document.getElementById('loginBtn');
                        loginBtn.classList.remove('loading');
                        loginBtn.classList.add('success');
                        loginBtn.innerHTML = '<i class="fas fa-check me-2"></i>Login Successful!';

                        setTimeout(function() {
                            document.getElementById('loginCard').classList.add('fly-up');
                            setTimeout(function() {
                                window.location.href = '../index.php';
                            }, 800);
                        }, 1000);
                    </script>
                <?php else: ?>

                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="<?php echo translate('email_placeholder', $lang); ?>" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="<?php echo translate('password_placeholder', $lang); ?>" required>
                        <div class="text-end mt-2">
                            <a href="#" class="forgot-password"><?php echo translate('forgot_password', $lang); ?></a>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            <?php echo translate('login_button', $lang); ?>
                        </span>
                        <span class="btn-loading" style="display: none;">
                            <?php echo translate('signing_in', $lang); ?>
                        </span>
                    </button>
                </form>

                <div class="register-link">
                    <?php echo translate('register_link', $lang); ?> <a href="../register.php"><?php echo translate('menu_register', $lang); ?></a>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5><?php echo translate('footer_links', $lang); ?></h5>
                        <ul>
                            <li><a href="../index.php"><?php echo translate('menu_home', $lang); ?></a></li>
                            <li><a href="#about"><?php echo translate('footer_about', $lang); ?></a></li>
                            <li><a href="../contact.php"><?php echo translate('footer_contact', $lang); ?></a></li>
                            <li><a href="#privacy"><?php echo translate('footer_privacy', $lang); ?></a></li>
                            <li><a href="#terms"><?php echo translate('footer_terms', $lang); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5><?php echo translate('footer_categories', $lang); ?></h5>
                        <ul>
                            <li><a href="../mobile_devices.php?category=smartphones"><?php echo translate('menu_smartphones', $lang); ?></a></li>
                            <li><a href="../computing.php?category=laptops"><?php echo translate('menu_laptops', $lang); ?></a></li>
                            <li><a href="../mobile_devices.php?category=tablets"><?php echo translate('menu_tablets', $lang); ?></a></li>
                            <li><a href="../mobile_devices.php?category=accessories"><?php echo translate('menu_accessories', $lang); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5><?php echo translate('footer_support', $lang); ?></h5>
                        <ul>
                            <li><a href="../contact.php"><?php echo translate('footer_contact', $lang); ?></a></li>
                            <li><a href="#faq">FAQ</a></li>
                            <li><a href="#shipping">Shipping Info</a></li>
                            <li><a href="#returns">Returns</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5><?php echo translate('site_name', $lang); ?></h5>
                        <p>Your trusted destination for the latest gadgets and electronics. Quality products, competitive prices, and exceptional service.</p>
                        <div class="social-links mt-3">
                            <a href="#" class="me-3"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p><?php echo translate('footer_copyright', $lang); ?></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle form submission with enhanced animations
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('loginBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');

            // Add loading state with animation
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            // Smooth transition to loading state
            btnText.style.opacity = '0';
            btnText.style.transform = 'translateY(-10px)';

            setTimeout(() => {
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline-flex';
                btnLoading.style.opacity = '0';
                btnLoading.style.transform = 'translateY(10px)';

                // Animate loading text in
                setTimeout(() => {
                    btnLoading.style.opacity = '1';
                    btnLoading.style.transform = 'translateY(0)';
                }, 50);
            }, 200);
        });

        // Add input focus animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'all 0.3s ease';
            });

            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Add button hover effects
        document.getElementById('loginBtn').addEventListener('mouseenter', function() {
            if (!this.disabled) {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            }
        });

        document.getElementById('loginBtn').addEventListener('mouseleave', function() {
            if (!this.disabled) {
                this.style.transform = 'translateY(0) scale(1)';
            }
        });

        // Navigation dropdown functions
        function showDropdown() {
            document.getElementById('shopDropdown').classList.add('show');
        }

        function hideDropdown() {
            document.getElementById('shopDropdown').classList.remove('show');
        }

        function showShopDropdown() {
            document.getElementById('shopCategoryDropdown').classList.add('show');
        }

        function hideShopDropdown() {
            document.getElementById('shopCategoryDropdown').classList.remove('show');
        }

        function showMoreDropdown() {
            document.getElementById('moreDropdown').classList.add('show');
        }

        function hideMoreDropdown() {
            document.getElementById('moreDropdown').classList.remove('show');
        }
    </script>
</body>
</html>