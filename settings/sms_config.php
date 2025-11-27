<?php
/**
 * SMS Configuration File
 * Arkesel API configuration and SMS settings
 */

// Arkesel API Configuration
define('SMS_API_KEY', 'b05TRXV3bWJ2WnBlZmNXc2lMT28');
define('SMS_API_URL', 'https://sms.arkesel.com/api/v2/sms/send');
define('SMS_SENDER_ID', 'Gadget-G'); // Max 11 characters for Arkesel

// SMS Settings
define('SMS_ENABLED', true);
define('SMS_MAX_RETRIES', 3);
define('SMS_RETRY_DELAY', 300); // 5 minutes in seconds
define('SMS_RATE_LIMIT', 100); // Max SMS per hour
define('SMS_QUEUE_ENABLED', true);

// Admin Settings
define('ADMIN_SMS_ENABLED', true);
define('ADMIN_PHONE_NUMBER', '+233551387578');
define('ADMIN_NEW_ORDER_SMS_ENABLED', true);

// Cart Abandonment Settings
define('CART_ABANDONMENT_ENABLED', true);
define('CART_ABANDONMENT_DELAY', 1800); // 30 minutes in seconds
define('CART_ABANDONMENT_REMINDERS', 2); // Max reminder SMS to send
define('CART_ABANDONMENT_INTERVAL', 3600); // 1 hour between reminders

// SMS Templates
$sms_templates = [
    'order_confirmation' => [
        'en' => "Hi {name}! Your order #{order_id} has been confirmed. Total: GH¢{amount}. Delivery: {delivery_date}. Track: {tracking_url}",
        'es' => "¡Hola {name}! Tu pedido #{order_id} ha sido confirmado. Total: GH¢{amount}. Entrega: {delivery_date}. Seguir: {tracking_url}",
        'fr' => "Salut {name}! Votre commande #{order_id} a été confirmée. Total: GH¢{amount}. Livraison: {delivery_date}. Suivre: {tracking_url}",
        'de' => "Hallo {name}! Ihre Bestellung #{order_id} wurde bestätigt. Gesamt: GH¢{amount}. Lieferung: {delivery_date}. Verfolgen: {tracking_url}"
    ],
    'order_shipped' => [
        'en' => "Great news {name}! Your order #{order_id} has been shipped and is on its way. Expected delivery: {delivery_date}",
        'es' => "¡Buenas noticias {name}! Tu pedido #{order_id} ha sido enviado. Entrega esperada: {delivery_date}",
        'fr' => "Bonne nouvelle {name}! Votre commande #{order_id} a été expédiée. Livraison prévue: {delivery_date}",
        'de' => "Gute Nachrichten {name}! Ihre Bestellung #{order_id} wurde versandt. Erwartete Lieferung: {delivery_date}"
    ],
    'order_delivered' => [
        'en' => "Hello {name}! Your order #{order_id} has been delivered successfully. Thank you for shopping with Gadget Garage!",
        'es' => "¡Hola {name}! Tu pedido #{order_id} ha sido entregado exitosamente. ¡Gracias por comprar en Gadget Garage!",
        'fr' => "Bonjour {name}! Votre commande #{order_id} a été livrée avec succès. Merci d'avoir acheté chez Gadget Garage!",
        'de' => "Hallo {name}! Ihre Bestellung #{order_id} wurde erfolgreich geliefert. Vielen Dank für Ihren Einkauf bei Gadget Garage!"
    ],
    'cart_abandonment' => [
        'en' => "Hi {name}! You left {items_count} items in your cart worth GH¢{cart_total}. Complete your purchase now: {checkout_url}",
        'es' => "¡Hola {name}! Dejaste {items_count} artículos en tu carrito por GH¢{cart_total}. Completa tu compra: {checkout_url}",
        'fr' => "Salut {name}! Vous avez laissé {items_count} articles dans votre panier pour GH¢{cart_total}. Terminez votre achat: {checkout_url}",
        'de' => "Hallo {name}! Sie haben {items_count} Artikel im Warenkorb im Wert von GH¢{cart_total} gelassen. Kaufen Sie jetzt: {checkout_url}"
    ],
    'cart_reminder' => [
        'en' => "Don't miss out {name}! Your cart items might be out of stock soon. Complete your order: {checkout_url}",
        'es' => "¡No te pierdas {name}! Tus artículos podrían agotarse pronto. Completa tu pedido: {checkout_url}",
        'fr' => "Ne ratez pas {name}! Vos articles pourraient être en rupture de stock bientôt. Terminez votre commande: {checkout_url}",
        'de' => "Verpassen Sie nicht {name}! Ihre Artikel könnten bald ausverkauft sein. Bestellung abschließen: {checkout_url}"
    ],
    'payment_received' => [
        'en' => "Payment received! Hi {name}, we've received your payment of GH¢{amount} for order #{order_id}. Processing now.",
        'es' => "¡Pago recibido! Hola {name}, hemos recibido tu pago de GH¢{amount} para el pedido #{order_id}. Procesando ahora.",
        'fr' => "Paiement reçu! Bonjour {name}, nous avons reçu votre paiement de GH¢{amount} pour la commande #{order_id}. En cours de traitement.",
        'de' => "Zahlung erhalten! Hallo {name}, wir haben Ihre Zahlung von GH¢{amount} für Bestellung #{order_id} erhalten. Wird verarbeitet."
    ],
    'welcome_registration' => [
        'en' => "Welcome to Gadget Garage, {name}! 🎉 Your account has been created successfully. Start shopping for the best tech deals today! Visit: {website_url}",
        'es' => "¡Bienvenido a Gadget Garage, {name}! 🎉 Tu cuenta se ha creado exitosamente. ¡Empieza a comprar las mejores ofertas tecnológicas hoy! Visita: {website_url}",
        'fr' => "Bienvenue chez Gadget Garage, {name}! 🎉 Votre compte a été créé avec succès. Commencez à acheter les meilleures offres technologiques aujourd'hui! Visitez: {website_url}",
        'de' => "Willkommen bei Gadget Garage, {name}! 🎉 Ihr Konto wurde erfolgreich erstellt. Beginnen Sie heute mit dem Einkauf der besten Tech-Angebote! Besuchen Sie: {website_url}"
    ],
    'admin_new_order' => [
        'en' => "🛒 NEW ORDER ALERT! Order #{order_id} from {customer_name} ({customer_phone}). Amount: GH¢{amount}. Items: {items_count}. Payment: {payment_method}. View: {admin_url}",
        'es' => "🛒 ¡ALERTA NUEVO PEDIDO! Orden #{order_id} de {customer_name} ({customer_phone}). Monto: GH¢{amount}. Artículos: {items_count}. Pago: {payment_method}. Ver: {admin_url}",
        'fr' => "🛒 ALERTE NOUVELLE COMMANDE! Commande #{order_id} de {customer_name} ({customer_phone}). Montant: GH¢{amount}. Articles: {items_count}. Paiement: {payment_method}. Voir: {admin_url}",
        'de' => "🛒 NEUE BESTELLUNG! Bestellung #{order_id} von {customer_name} ({customer_phone}). Betrag: GH¢{amount}. Artikel: {items_count}. Zahlung: {payment_method}. Ansehen: {admin_url}"
    ]
];

// SMS Priorities
define('SMS_PRIORITY_HIGH', 1);
define('SMS_PRIORITY_MEDIUM', 2);
define('SMS_PRIORITY_LOW', 3);

// SMS Types
define('SMS_TYPE_ORDER_CONFIRMATION', 'order_confirmation');
define('SMS_TYPE_ORDER_SHIPPED', 'order_shipped');
define('SMS_TYPE_ORDER_DELIVERED', 'order_delivered');
define('SMS_TYPE_CART_ABANDONMENT', 'cart_abandonment');
define('SMS_TYPE_CART_REMINDER', 'cart_reminder');
define('SMS_TYPE_PAYMENT_RECEIVED', 'payment_received');
define('SMS_TYPE_WELCOME_REGISTRATION', 'welcome_registration');
define('SMS_TYPE_ADMIN_NEW_ORDER', 'admin_new_order');
define('SMS_TYPE_APPOINTMENT_CONFIRMATION', 'appointment_confirmation');

// Phone number validation patterns
$phone_patterns = [
    'ghana' => '/^(\+233|0)[2-9][0-9]{8}$/',
    'international' => '/^\+[1-9]\d{1,14}$/'
];

// Business hours for SMS sending (24-hour format)
$business_hours = [
    'start' => '08:00',
    'end' => '20:00',
    'timezone' => 'Africa/Accra'
];

// Error messages
$sms_error_messages = [
    'invalid_phone' => 'Invalid phone number format',
    'api_error' => 'SMS API error occurred',
    'rate_limit_exceeded' => 'SMS rate limit exceeded',
    'template_not_found' => 'SMS template not found',
    'insufficient_balance' => 'Insufficient SMS balance'
];

// SMS Status codes
define('SMS_STATUS_PENDING', 'pending');
define('SMS_STATUS_SENT', 'sent');
define('SMS_STATUS_FAILED', 'failed');
define('SMS_STATUS_DELIVERED', 'delivered');
define('SMS_STATUS_QUEUED', 'queued');

// Logging settings
define('SMS_LOG_ENABLED', true);
define('SMS_LOG_LEVEL', 'info'); // debug, info, warning, error
define('SMS_LOG_FILE', __DIR__ . '/../logs/sms.log');

// URLs for links in SMS
$sms_urls = [
    'tracking_base' => 'http://169.239.251.102:442/~chelsea.somuah/Ecommerce_Final/track_order.php?order=',
    'checkout_url' => 'http://169.239.251.102:442/~chelsea.somuah/Ecommerce_Final/views/checkout.php',
    'website_url' => 'http://169.239.251.102:442/~chelsea.somuah/Ecommerce_Final/',
    'admin_orders' => 'http://169.239.251.102:442/~chelsea.somuah/Ecommerce_Final/admin/orders.php?order='
];