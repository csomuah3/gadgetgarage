<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../settings/db_class.php');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if user is logged in
    if (!check_login()) {
        throw new Exception('You must be logged in to submit a rating');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $customer_id = $_SESSION['user_id'];
    $order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
    $product_id = isset($input['product_id']) ? intval($input['product_id']) : 0;
    $rating = isset($input['rating']) ? intval($input['rating']) : 0;
    $comment = isset($input['comment']) ? trim($input['comment']) : '';
    $product_condition = isset($input['product_condition']) ? trim($input['product_condition']) : null;
    $product_price = isset($input['product_price']) ? floatval($input['product_price']) : null;

    // Validate inputs
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }

    // Verify order belongs to customer
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();
    
    // Check if order belongs to customer
    $order_check = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND customer_id = ?");
    $order_check->bind_param('ii', $order_id, $customer_id);
    $order_check->execute();
    $order_result = $order_check->get_result();
    
    if ($order_result->num_rows === 0) {
        throw new Exception('Order not found or access denied');
    }

    // Check if product is in this order
    $product_check = $conn->prepare("SELECT od.product_id, p.product_price FROM orderdetails od 
                                     JOIN products p ON od.product_id = p.product_id 
                                     WHERE od.order_id = ? AND od.product_id = ?");
    $product_check->bind_param('ii', $order_id, $product_id);
    $product_check->execute();
    $product_result = $product_check->get_result();
    
    if ($product_result->num_rows === 0) {
        throw new Exception('Product not found in this order');
    }

    $product_data = $product_result->fetch_assoc();
    
    // Use actual product price if not provided
    if ($product_price === null) {
        $product_price = floatval($product_data['product_price']);
    }

    // Escape values
    $comment_escaped = mysqli_real_escape_string($conn, $comment);
    $product_condition_escaped = $product_condition ? "'" . mysqli_real_escape_string($conn, $product_condition) . "'" : 'NULL';

    // Check if rating already exists
    $check_stmt = $conn->prepare("SELECT rating_id FROM product_ratings WHERE order_id = ? AND product_id = ? AND customer_id = ?");
    $check_stmt->bind_param('iii', $order_id, $product_id, $customer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing rating
        $rating_row = $result->fetch_assoc();
        $rating_id = $rating_row['rating_id'];
        
        $update_sql = "UPDATE product_ratings SET 
                       rating = $rating, 
                       comment = '$comment_escaped',
                       product_condition = $product_condition_escaped,
                       product_price = $product_price,
                       updated_at = NOW()
                       WHERE rating_id = $rating_id";
        
        if ($db->db_write_query($update_sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Rating updated successfully',
                'rating_id' => $rating_id
            ]);
        } else {
            throw new Exception('Failed to update rating');
        }
    } else {
        // Insert new rating
        $insert_sql = "INSERT INTO product_ratings 
                      (order_id, product_id, customer_id, rating, comment, product_condition, product_price, created_at, updated_at) 
                      VALUES ($order_id, $product_id, $customer_id, $rating, '$comment_escaped', $product_condition_escaped, $product_price, NOW(), NOW())";
        
        if ($db->db_write_query($insert_sql)) {
            $rating_id = mysqli_insert_id($conn);
            echo json_encode([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'rating_id' => $rating_id
            ]);
        } else {
            throw new Exception('Failed to save rating: ' . mysqli_error($conn));
        }
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db->db_close();
    }
}
?>

