<?php
// Debug version to find the exact issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Starting debug...<br>";

try {
    echo "2. Including core...<br>";
    require_once(__DIR__ . '/settings/core.php');
    echo "3. Core included successfully<br>";

    echo "4. Including order controller...<br>";
    require_once(__DIR__ . '/controllers/order_controller.php');
    echo "5. Order controller included<br>";

    echo "6. Including cart controller...<br>";
    require_once(__DIR__ . '/controllers/cart_controller.php');
    echo "7. Cart controller included<br>";

    echo "8. Including wishlist controller...<br>";
    require_once(__DIR__ . '/controllers/wishlist_controller.php');
    echo "9. Wishlist controller included<br>";

    echo "10. Checking login...<br>";
    $is_logged_in = check_login();
    echo "11. Login check result: " . ($is_logged_in ? 'true' : 'false') . "<br>";

    if (!$is_logged_in) {
        echo "12. User not logged in, would redirect...<br>";
        echo "13. Session data: <pre>" . print_r($_SESSION, true) . "</pre><br>";
        // Continue with debugging even if not logged in
    }

    echo "14. Checking admin status...<br>";
    $is_admin = check_admin();
    echo "15. Admin check result: " . ($is_admin ? 'true' : 'false') . "<br>";

    echo "16. Getting customer ID...<br>";
    $customer_id = $_SESSION['user_id'] ?? 'not_set';
    echo "17. Customer ID: " . $customer_id . "<br>";

    if ($customer_id === 'not_set') {
        echo "18. Cannot proceed without customer ID<br>";
        exit;
    }

    echo "18. Getting user orders...<br>";
    $orders = get_user_orders_ctr($customer_id);
    echo "19. Orders retrieved. Count: " . (is_array($orders) ? count($orders) : 'not array') . "<br>";

    if (is_array($orders)) {
        echo "20. Orders data: <pre>" . print_r($orders, true) . "</pre><br>";
    }

    echo "21. Getting IP address...<br>";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    echo "22. IP address: " . $ip_address . "<br>";

    echo "23. Getting cart count...<br>";
    $cart_count = get_cart_count_ctr($customer_id, $ip_address);
    echo "24. Cart count: " . $cart_count . "<br>";

    echo "25. Getting wishlist count...<br>";
    $wishlist_count = get_wishlist_count_ctr($customer_id);
    echo "26. Wishlist count: " . $wishlist_count . "<br>";

    echo "27. All operations completed successfully!<br>";

} catch (Exception $e) {
    echo "ERROR at step: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>