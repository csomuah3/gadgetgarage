<?php
/**
 * Language Configuration
 * Manages multiple language support for the e-commerce site
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Available languages
$available_languages = [
    'en' => [
        'name' => 'English',
        'flag' => '🇬🇧',
        'code' => 'EN'
    ],
    'es' => [
        'name' => 'Español',
        'flag' => '🇪🇸',
        'code' => 'ES'
    ],
    'fr' => [
        'name' => 'Français',
        'flag' => '🇫🇷',
        'code' => 'FR'
    ],
    'de' => [
        'name' => 'Deutsch',
        'flag' => '🇩🇪',
        'code' => 'DE'
    ]
];

// Set default language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'en';
}

// Get current language
$current_language = $_SESSION['language'];

// Validate current language
if (!array_key_exists($current_language, $available_languages)) {
    $_SESSION['language'] = 'en';
    $current_language = 'en';
}

/**
 * Get translation for a key
 */
function translate($key) {
    global $translations, $current_language;

    // If translations not loaded, load them
    if (!isset($translations)) {
        load_translations();
    }

    // Return translation if exists, otherwise return the key
    return $translations[$current_language][$key] ?? $translations['en'][$key] ?? $key;
}

/**
 * Shorthand function for translation
 */
function t($key) {
    return translate($key);
}

/**
 * Load translations
 */
function load_translations() {
    global $translations;

    $translations = [
        'en' => [
            // Header
            'search_placeholder' => 'Search phones, laptops, cameras...',
            'tech_revival' => 'Bring Retired Tech',
            'home' => 'HOME',
            'shop' => 'SHOP',
            'mobile_devices' => 'Mobile Devices',
            'smartphones' => 'Smartphones',
            'ipads' => 'iPads',
            'computing' => 'Computing',
            'laptops' => 'Laptops',
            'desktops' => 'Desktops',
            'photography_video' => 'Photography & Video',
            'cameras' => 'Cameras',
            'video_equipment' => 'Video Equipment',
            'shop_all' => 'Shop All',
            'new_arrivals' => 'New Arrivals',
            'latest_tech_gadgets' => 'Latest tech gadgets',
            'shop_now' => 'Shop Now',
            'repair_studio' => 'REPAIR STUDIO',
            'device_drop' => 'DEVICE DROP',
            'more' => 'MORE',
            'contact' => 'Contact',
            'terms_conditions' => 'Terms & Conditions',
            'flash_deal' => '⚡ FLASH DEAL',
            'shop_by_brands' => 'SHOP BY BRANDS',
            'all_brands' => 'All Brands',
            'all_products' => 'All Products',

            // User menu
            'profile_picture' => 'Profile Picture',
            'language' => 'Language',
            'dark_mode' => 'Dark Mode',
            'my_orders' => 'My Orders',
            'wishlist' => 'Wishlist',
            'notifications' => 'Notifications',
            'admin_panel' => 'Admin Panel',
            'logout' => 'Logout',
            'register' => 'Register',
            'login' => 'Login',

            // Common
            'welcome' => 'Welcome',
            'add_to_cart' => 'Add to Cart',
            'buy_now' => 'Buy Now',
            'price' => 'Price',
            'quantity' => 'Quantity',
            'total' => 'Total',
            'subtotal' => 'Subtotal',
            'checkout' => 'Checkout',
            'continue_shopping' => 'Continue Shopping',
            'view_cart' => 'View Cart',
            'empty_cart' => 'Empty Cart',
            'remove' => 'Remove',
            'update' => 'Update',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'yes' => 'Yes',
            'no' => 'No',
            'ok' => 'OK',
            'error' => 'Error',
            'success' => 'Success',
            'warning' => 'Warning',
            'info' => 'Information',
            'loading' => 'Loading...',
        ],

        'es' => [
            // Header
            'search_placeholder' => 'Buscar teléfonos, laptops, cámaras...',
            'tech_revival' => 'Traiga Tecnología Retirada',
            'home' => 'INICIO',
            'shop' => 'TIENDA',
            'mobile_devices' => 'Dispositivos Móviles',
            'smartphones' => 'Teléfonos Inteligentes',
            'ipads' => 'iPads',
            'computing' => 'Computación',
            'laptops' => 'Portátiles',
            'desktops' => 'Escritorio',
            'photography_video' => 'Fotografía y Video',
            'cameras' => 'Cámaras',
            'video_equipment' => 'Equipo de Video',
            'shop_all' => 'Comprar Todo',
            'new_arrivals' => 'Nuevas Llegadas',
            'latest_tech_gadgets' => 'Últimos gadgets tecnológicos',
            'shop_now' => 'Comprar Ahora',
            'repair_studio' => 'ESTUDIO DE REPARACIÓN',
            'device_drop' => 'ENTREGA DE DISPOSITIVO',
            'more' => 'MÁS',
            'contact' => 'Contacto',
            'terms_conditions' => 'Términos y Condiciones',
            'flash_deal' => '⚡ OFERTA FLASH',
            'shop_by_brands' => 'COMPRAR POR MARCAS',
            'all_brands' => 'Todas las Marcas',
            'all_products' => 'Todos los Productos',

            // User menu
            'profile_picture' => 'Foto de Perfil',
            'language' => 'Idioma',
            'dark_mode' => 'Modo Oscuro',
            'my_orders' => 'Mis Pedidos',
            'wishlist' => 'Lista de Deseos',
            'notifications' => 'Notificaciones',
            'admin_panel' => 'Panel de Admin',
            'logout' => 'Cerrar Sesión',
            'register' => 'Registrarse',
            'login' => 'Iniciar Sesión',

            // Common
            'welcome' => 'Bienvenido',
            'add_to_cart' => 'Agregar al Carrito',
            'buy_now' => 'Comprar Ahora',
            'price' => 'Precio',
            'quantity' => 'Cantidad',
            'total' => 'Total',
            'subtotal' => 'Subtotal',
            'checkout' => 'Pagar',
            'continue_shopping' => 'Seguir Comprando',
            'view_cart' => 'Ver Carrito',
            'empty_cart' => 'Vaciar Carrito',
            'remove' => 'Eliminar',
            'update' => 'Actualizar',
            'save' => 'Guardar',
            'cancel' => 'Cancelar',
            'yes' => 'Sí',
            'no' => 'No',
            'ok' => 'OK',
            'error' => 'Error',
            'success' => 'Éxito',
            'warning' => 'Advertencia',
            'info' => 'Información',
            'loading' => 'Cargando...',
        ],

        'fr' => [
            // Header
            'search_placeholder' => 'Rechercher téléphones, ordinateurs portables, caméras...',
            'tech_revival' => 'Apportez la Technologie Retirée',
            'home' => 'ACCUEIL',
            'shop' => 'BOUTIQUE',
            'mobile_devices' => 'Appareils Mobiles',
            'smartphones' => 'Smartphones',
            'ipads' => 'iPads',
            'computing' => 'Informatique',
            'laptops' => 'Ordinateurs Portables',
            'desktops' => 'Ordinateurs de Bureau',
            'photography_video' => 'Photographie et Vidéo',
            'cameras' => 'Caméras',
            'video_equipment' => 'Équipement Vidéo',
            'shop_all' => 'Tout Acheter',
            'new_arrivals' => 'Nouvelles Arrivées',
            'latest_tech_gadgets' => 'Derniers gadgets technologiques',
            'shop_now' => 'Acheter Maintenant',
            'repair_studio' => 'STUDIO DE RÉPARATION',
            'device_drop' => 'DÉPÔT D\'APPAREIL',
            'more' => 'PLUS',
            'contact' => 'Contact',
            'terms_conditions' => 'Termes et Conditions',
            'flash_deal' => '⚡ OFFRE FLASH',
            'shop_by_brands' => 'ACHETER PAR MARQUES',
            'all_brands' => 'Toutes les Marques',
            'all_products' => 'Tous les Produits',

            // User menu
            'profile_picture' => 'Photo de Profil',
            'language' => 'Langue',
            'dark_mode' => 'Mode Sombre',
            'my_orders' => 'Mes Commandes',
            'wishlist' => 'Liste de Souhaits',
            'notifications' => 'Notifications',
            'admin_panel' => 'Panneau Admin',
            'logout' => 'Se Déconnecter',
            'register' => 'S\'inscrire',
            'login' => 'Se Connecter',

            // Common
            'welcome' => 'Bienvenue',
            'add_to_cart' => 'Ajouter au Panier',
            'buy_now' => 'Acheter Maintenant',
            'price' => 'Prix',
            'quantity' => 'Quantité',
            'total' => 'Total',
            'subtotal' => 'Sous-total',
            'checkout' => 'Commander',
            'continue_shopping' => 'Continuer les Achats',
            'view_cart' => 'Voir le Panier',
            'empty_cart' => 'Vider le Panier',
            'remove' => 'Supprimer',
            'update' => 'Mettre à Jour',
            'save' => 'Sauvegarder',
            'cancel' => 'Annuler',
            'yes' => 'Oui',
            'no' => 'Non',
            'ok' => 'OK',
            'error' => 'Erreur',
            'success' => 'Succès',
            'warning' => 'Avertissement',
            'info' => 'Information',
            'loading' => 'Chargement...',
        ],

        'de' => [
            // Header
            'search_placeholder' => 'Suchen Sie Telefone, Laptops, Kameras...',
            'tech_revival' => 'Bringen Sie Ausgemusterte Technik',
            'home' => 'STARTSEITE',
            'shop' => 'SHOP',
            'mobile_devices' => 'Mobile Geräte',
            'smartphones' => 'Smartphones',
            'ipads' => 'iPads',
            'computing' => 'Computing',
            'laptops' => 'Laptops',
            'desktops' => 'Desktops',
            'photography_video' => 'Fotografie & Video',
            'cameras' => 'Kameras',
            'video_equipment' => 'Videoausrüstung',
            'shop_all' => 'Alles Einkaufen',
            'new_arrivals' => 'Neue Ankünfte',
            'latest_tech_gadgets' => 'Neueste Tech-Gadgets',
            'shop_now' => 'Jetzt Einkaufen',
            'repair_studio' => 'REPARATUR STUDIO',
            'device_drop' => 'GERÄT ABGABE',
            'more' => 'MEHR',
            'contact' => 'Kontakt',
            'terms_conditions' => 'Geschäftsbedingungen',
            'flash_deal' => '⚡ BLITZ ANGEBOT',
            'shop_by_brands' => 'NACH MARKEN EINKAUFEN',
            'all_brands' => 'Alle Marken',
            'all_products' => 'Alle Produkte',

            // User menu
            'profile_picture' => 'Profilbild',
            'language' => 'Sprache',
            'dark_mode' => 'Dunkler Modus',
            'my_orders' => 'Meine Bestellungen',
            'wishlist' => 'Wunschliste',
            'notifications' => 'Benachrichtigungen',
            'admin_panel' => 'Admin Panel',
            'logout' => 'Abmelden',
            'register' => 'Registrieren',
            'login' => 'Anmelden',

            // Common
            'welcome' => 'Willkommen',
            'add_to_cart' => 'In den Warenkorb',
            'buy_now' => 'Jetzt Kaufen',
            'price' => 'Preis',
            'quantity' => 'Menge',
            'total' => 'Gesamt',
            'subtotal' => 'Zwischensumme',
            'checkout' => 'Zur Kasse',
            'continue_shopping' => 'Weiter Einkaufen',
            'view_cart' => 'Warenkorb Anzeigen',
            'empty_cart' => 'Warenkorb Leeren',
            'remove' => 'Entfernen',
            'update' => 'Aktualisieren',
            'save' => 'Speichern',
            'cancel' => 'Abbrechen',
            'yes' => 'Ja',
            'no' => 'Nein',
            'ok' => 'OK',
            'error' => 'Fehler',
            'success' => 'Erfolg',
            'warning' => 'Warnung',
            'info' => 'Information',
            'loading' => 'Wird geladen...',
        ]
    ];
}

// Load translations
load_translations();
?>