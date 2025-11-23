<?php
// Submit rating action
session_start();
require_once('../settings/connection.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['rating']) || !is_numeric($input['rating']) || $input['rating'] < 1 || $input['rating'] > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit();
}

$user_id = $_SESSION['user_id'];
$rating = intval($input['rating']);
$comment = isset($input['comment']) ? trim($input['comment']) : '';
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    // Get database connection
    $conn = db_connection();

    // Check if user has already rated recently (prevent spam)
    $checkQuery = "SELECT rating_id FROM user_ratings WHERE user_id = ? AND rating_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted a rating recently. Please wait before submitting another.']);
        exit();
    }

    // Insert the rating
    $insertQuery = "INSERT INTO user_ratings (user_id, rating, comment, ip_address, rating_date) VALUES (?, ?, ?, ?, NOW())";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iiss", $user_id, $rating, $comment, $ip_address);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit rating']);
    }

    $insertStmt->close();
    $checkStmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Rating submission error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while submitting your rating']);
}
?>