<?php
session_start();
require_once 'settings/db_class.php';

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
            'welcome_back' => 'Create Account',
            'register_subtitle' => 'Join Gadget Garage today and start shopping!',
            'first_name_placeholder' => 'First Name',
            'last_name_placeholder' => 'Last Name',
            'email_placeholder' => 'Email Address',
            'password_placeholder' => 'Password',
            'confirm_password_placeholder' => 'Confirm Password',
            'register_button' => 'Create Account',
            'creating_account' => 'Creating Account...',
            'login_link' => 'Already have an account? Login here',
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
            'welcome_back' => 'Crear Cuenta',
            'register_subtitle' => '¡Únete a Garaje de Gadgets hoy y comienza a comprar!',
            'first_name_placeholder' => 'Nombre',
            'last_name_placeholder' => 'Apellido',
            'email_placeholder' => 'Correo Electrónico',
            'password_placeholder' => 'Contraseña',
            'confirm_password_placeholder' => 'Confirmar Contraseña',
            'register_button' => 'Crear Cuenta',
            'creating_account' => 'Creando Cuenta...',
            'login_link' => '¿Ya tienes una cuenta? Inicia sesión aquí',
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
            'welcome_back' => 'Créer un Compte',
            'register_subtitle' => 'Rejoignez Garage Gadget aujourd\'hui et commencez vos achats !',
            'first_name_placeholder' => 'Prénom',
            'last_name_placeholder' => 'Nom de Famille',
            'email_placeholder' => 'Adresse E-mail',
            'password_placeholder' => 'Mot de Passe',
            'confirm_password_placeholder' => 'Confirmer le Mot de Passe',
            'register_button' => 'Créer un Compte',
            'creating_account' => 'Création du Compte...',
            'login_link' => 'Vous avez déjà un compte ? Connectez-vous ici',
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
            'welcome_back' => 'Konto Erstellen',
            'register_subtitle' => 'Treten Sie heute Gadget Garage bei und beginnen Sie mit dem Einkaufen!',
            'first_name_placeholder' => 'Vorname',
            'last_name_placeholder' => 'Nachname',
            'email_placeholder' => 'E-Mail-Adresse',
            'password_placeholder' => 'Passwort',
            'confirm_password_placeholder' => 'Passwort Bestätigen',
            'register_button' => 'Konto Erstellen',
            'creating_account' => 'Konto Wird Erstellt...',
            'login_link' => 'Haben Sie bereits ein Konto? Hier anmelden',
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

// Handle registration
$registration_error = '';
$registration_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $registration_error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $registration_error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $registration_error = 'Passwords do not match.';
    } else {
        $db = new db_connection();

        // Check if user already exists
        $email_escaped = mysqli_real_escape_string($db->db_conn(), $email);
        $check_sql = "SELECT customer_id FROM customer WHERE customer_email = '$email_escaped'";
        $existing_user = $db->db_fetch_one($check_sql);

        if ($existing_user) {
            $registration_error = 'An account with this email already exists.';
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $first_name_escaped = mysqli_real_escape_string($db->db_conn(), $first_name);
            $last_name_escaped = mysqli_real_escape_string($db->db_conn(), $last_name);
            $full_name = $first_name . ' ' . $last_name;
            $full_name_escaped = mysqli_real_escape_string($db->db_conn(), $full_name);

            $insert_sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_contact, user_role, customer_country, customer_city, customer_address, date_created)
                          VALUES ('$full_name_escaped', '$email_escaped', '$hashed_password', '', 1, 'Ghana', 'Accra', '', NOW())";

            if ($db->db_write_query($insert_sql)) {
                // Auto-login the user
                $user_id = $db->last_insert_id();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 1;
                $_SESSION['name'] = $full_name;

                $registration_success = true;
            } else {
                $registration_error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('menu_register', $lang); ?> - <?php echo translate('site_name', $lang); ?></title>
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

        /* Registration Section */
        .register-section {
            min-height: calc(100vh - 200px);
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Registration Card */
        .register-card {
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

        .register-card::before {
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

        .register-card .form-container {
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

        .register-card h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
            font-size: 1.8rem;
        }

        .register-card .subtitle {
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

        .btn-register {
            width: 100%;
            height: 55px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            box-shadow: 0 8px 32px rgba(240, 147, 251, 0.3);
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(240, 147, 251, 0.4);
        }

        .btn-register:active {
            transform: translateY(-1px) scale(0.98);
            box-shadow: 0 5px 20px rgba(240, 147, 251, 0.3);
        }

        .btn-register.loading {
            background: linear-gradient(135deg, #6c757d, #495057);
            cursor: not-allowed;
            transform: none;
            animation: pulse 2s infinite;
        }

        .btn-register.loading::before {
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
        .btn-register.success {
            background: linear-gradient(135deg, #56ab2f, #a8e6cf);
            animation: successPulse 0.6s ease;
        }

        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }

        .login-link a {
            color: #4682b4;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
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

            .register-section {
                padding: 20px 15px;
                min-height: calc(100vh - 250px);
            }

            .register-card {
                padding: 30px 20px;
                margin: 0 10px;
            }

            .register-card h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .register-card {
                padding: 25px 15px;
            }

            .form-control {
                height: 45px;
                font-size: 14px;
            }

            .btn-register {
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
                        <a href="index.php" class="logo">
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
                                <a href="login/login.php" class="btn btn-outline-primary"><?php echo translate('menu_login', $lang); ?></a>
                                <a href="register.php" class="btn btn-primary"><?php echo translate('menu_register', $lang); ?></a>
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
                            <li><a href="all_product.php"><i class="fas fa-tag"></i> Apple</a></li>
                            <li><a href="all_product.php"><i class="fas fa-tag"></i> Samsung</a></li>
                            <li><a href="all_product.php"><i class="fas fa-tag"></i> HP</a></li>
                            <li><a href="all_product.php"><i class="fas fa-tag"></i> Dell</a></li>
                            <li><a href="all_product.php"><i class="fas fa-tag"></i> Sony</a></li>
                            <li><a href="all_product.php"><i class="fas fa-tag"></i> Canon</a></li>
                        </ul>
                    </div>
                </div>

                <a href="index.php" class="nav-item"><span>HOME</span></a>

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
                                    <a href="mobile_devices.php" style="text-decoration: none; color: inherit;">
                                        <span>Mobile Devices</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=smartphones"><i class="fas fa-mobile-alt"></i> <span>Smartphones</span></a></li>
                                    <li><a href="all_product.php?category=ipads"><i class="fas fa-tablet-alt"></i> <span>iPads</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="computing.php" style="text-decoration: none; color: inherit;">
                                        <span>Computing</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=laptops"><i class="fas fa-laptop"></i> <span>Laptops</span></a></li>
                                    <li><a href="all_product.php?category=desktops"><i class="fas fa-desktop"></i> <span>Desktops</span></a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>
                                    <a href="photography_video.php" style="text-decoration: none; color: inherit;">
                                        <span>Photography & Video</span>
                                    </a>
                                </h4>
                                <ul>
                                    <li><a href="all_product.php?category=cameras"><i class="fas fa-camera"></i> <span>Cameras</span></a></li>
                                    <li><a href="all_product.php?category=video_equipment"><i class="fas fa-video"></i> <span>Video Equipment</span></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="repair_services.php" class="nav-item"><span>REPAIR STUDIO</span></a>
                <a href="device_drop.php" class="nav-item"><span>DEVICE DROP</span></a>

                <!-- More Dropdown -->
                <div class="nav-dropdown" onmouseenter="showMoreDropdown()" onmouseleave="hideMoreDropdown()">
                    <a href="#" class="nav-item">
                        <span>MORE</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="simple-dropdown" id="moreDropdown">
                        <ul>
                            <li><a href="contact.php"><i class="fas fa-phone"></i> Contact</a></li>
                            <li><a href="terms_conditions.php"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Flash Deal positioned at far right -->
                <a href="flash_deals.php" class="nav-item flash-deal">⚡ <span>FLASH DEAL</span></a>
            </div>
        </div>
    </nav>

    <!-- Registration Section -->
    <section class="register-section">
        <div class="register-card" id="registerCard">
            <div class="form-container">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <a href="index.php" class="logo text-decoration-none">
                        <i class="fas fa-bolt"></i> <?php echo translate('site_name', $lang); ?>
                    </a>
                </div>

                <h2><?php echo translate('welcome_back', $lang); ?></h2>
                <p class="subtitle"><?php echo translate('register_subtitle', $lang); ?></p>

                <?php if (!empty($registration_error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $registration_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($registration_success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Account created successfully! Redirecting...
                    </div>
                    <script>
                        // Add success animation to button first
                        const registerBtn = document.getElementById('registerBtn');
                        registerBtn.classList.remove('loading');
                        registerBtn.classList.add('success');
                        registerBtn.innerHTML = '<i class="fas fa-check me-2"></i>Account Created Successfully!';

                        setTimeout(function() {
                            document.getElementById('registerCard').classList.add('fly-up');
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 800);
                        }, 1000);
                    </script>
                <?php else: ?>

                <form method="POST" id="registerForm">
                    <div class="form-group">
                        <input type="text" class="form-control" name="first_name" placeholder="<?php echo translate('first_name_placeholder', $lang); ?>" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <input type="text" class="form-control" name="last_name" placeholder="<?php echo translate('last_name_placeholder', $lang); ?>" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="<?php echo translate('email_placeholder', $lang); ?>" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="<?php echo translate('password_placeholder', $lang); ?>" required>
                    </div>

                    <div class="form-group">
                        <input type="password" class="form-control" name="confirm_password" placeholder="<?php echo translate('confirm_password_placeholder', $lang); ?>" required>
                    </div>

                    <button type="submit" class="btn-register" id="registerBtn">
                        <span class="btn-text">
                            <i class="fas fa-user-plus me-2"></i>
                            <?php echo translate('register_button', $lang); ?>
                        </span>
                        <span class="btn-loading" style="display: none;">
                            <?php echo translate('creating_account', $lang); ?>
                        </span>
                    </button>
                </form>

                <div class="login-link">
                    <?php echo translate('login_link', $lang); ?> <a href="login/login.php"><?php echo translate('menu_login', $lang); ?></a>
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
                            <li><a href="index.php"><?php echo translate('menu_home', $lang); ?></a></li>
                            <li><a href="#about"><?php echo translate('footer_about', $lang); ?></a></li>
                            <li><a href="contact.php"><?php echo translate('footer_contact', $lang); ?></a></li>
                            <li><a href="#privacy"><?php echo translate('footer_privacy', $lang); ?></a></li>
                            <li><a href="#terms"><?php echo translate('footer_terms', $lang); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5><?php echo translate('footer_categories', $lang); ?></h5>
                        <ul>
                            <li><a href="mobile_devices.php?category=smartphones"><?php echo translate('menu_smartphones', $lang); ?></a></li>
                            <li><a href="computing.php?category=laptops"><?php echo translate('menu_laptops', $lang); ?></a></li>
                            <li><a href="mobile_devices.php?category=tablets"><?php echo translate('menu_tablets', $lang); ?></a></li>
                            <li><a href="mobile_devices.php?category=accessories"><?php echo translate('menu_accessories', $lang); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-section">
                        <h5><?php echo translate('footer_support', $lang); ?></h5>
                        <ul>
                            <li><a href="contact.php"><?php echo translate('footer_contact', $lang); ?></a></li>
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
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('registerBtn');
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
        document.getElementById('registerBtn').addEventListener('mouseenter', function() {
            if (!this.disabled) {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            }
        });

        document.getElementById('registerBtn').addEventListener('mouseleave', function() {
            if (!this.disabled) {
                this.style.transform = 'translateY(0) scale(1)';
            }
        });

        // Password confirmation validation
        document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Real-time validation feedback
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
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