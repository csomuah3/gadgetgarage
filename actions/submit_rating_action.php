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

    $user_id = $_SESSION['user_id'];
    $order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
    $ease_rating = isset($input['ease_rating']) ? intval($input['ease_rating']) : 0;
    $satisfaction_rating = isset($input['satisfaction_rating']) ? intval($input['satisfaction_rating']) : 0;
    $comment = isset($input['comment']) ? trim($input['comment']) : '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Validate order_id
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }

    // Calculate average rating (use satisfaction rating as primary, or average if both provided)
    $rating = $satisfaction_rating > 0 ? $satisfaction_rating : $ease_rating;
    if ($ease_rating > 0 && $satisfaction_rating > 0) {
        $rating = round(($ease_rating + $satisfaction_rating) / 2);
    }

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }

    // Connect to database
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();

    // Check if rating already exists for this order
    $check_stmt = $conn->prepare("SELECT rating_id FROM user_ratings WHERE order_id = ? AND user_id = ?");
    $check_stmt->bind_param('ii', $order_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing rating
        $update_stmt = $conn->prepare("UPDATE user_ratings SET rating = ?, comment = ?, rating_date = NOW(), ip_address = ? WHERE order_id = ? AND user_id = ?");
        $update_stmt->bind_param('issii', $rating, $comment, $ip_address, $order_id, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Rating updated successfully',
                'rating_id' => $result->fetch_assoc()['rating_id']
            ]);
        } else {
            throw new Exception('Failed to update rating');
        }
    } else {
        // Insert new rating
        $insert_stmt = $conn->prepare("INSERT INTO user_ratings (user_id, rating, comment, order_id, rating_date, ip_address) VALUES (?, ?, ?, ?, NOW(), ?)");
        $insert_stmt->bind_param('iisis', $user_id, $rating, $comment, $order_id, $ip_address);
        
        if ($insert_stmt->execute()) {
            $rating_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'rating_id' => $rating_id
            ]);
        } else {
            throw new Exception('Failed to save rating');
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
