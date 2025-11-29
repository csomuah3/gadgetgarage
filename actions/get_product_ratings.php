<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../settings/db_class.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Check if user is logged in
    if (!check_login()) {
        throw new Exception('You must be logged in to view ratings');
    }

    $customer_id = $_SESSION['user_id'];
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }

    // Connect to database
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();

    // Get all ratings for this order
    $sql = "SELECT product_id, rating, comment, product_condition, product_price, created_at, updated_at
            FROM product_ratings
            WHERE order_id = $order_id AND customer_id = $customer_id";

    $result = $db->db_fetch_all($sql);
    
    // Organize ratings by product_id for easy lookup
    $ratings = [];
    if ($result && is_array($result)) {
        foreach ($result as $rating) {
            $ratings[$rating['product_id']] = $rating;
        }
    }

    echo json_encode([
        'success' => true,
        'ratings' => $ratings
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'ratings' => []
    ]);
} finally {
    if (isset($db)) {
        $db->db_close();
    }
}
?>

