<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response header
header('Content-Type: application/json');

try {
    // Include database connection
    require_once('../settings/db_class.php');

    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Validate required fields
    $required_fields = [
        'device_type', 'device_brand', 'device_model', 'condition',
        'first_name', 'last_name', 'email', 'phone'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    // Sanitize input data
    $device_type = trim($_POST['device_type']);
    $device_brand = trim($_POST['device_brand']);
    $device_model = trim($_POST['device_model']);
    $condition = trim($_POST['condition']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $asking_price = isset($_POST['asking_price']) && !empty($_POST['asking_price']) ?
                   floatval($_POST['asking_price']) : null;
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pickup_address = isset($_POST['address']) ? trim($_POST['address']) : null;

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Validate condition
    if (!in_array($condition, ['excellent', 'good', 'fair'])) {
        throw new Exception('Invalid condition value');
    }

    // Create database connection
    $db = new db_connection();

    // Insert device drop request
    $sql = "INSERT INTO device_drop_requests (
        device_type, device_brand, device_model, condition_status,
        description, asking_price, first_name, last_name, email, phone, pickup_address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->db_conn()->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $db->db_conn()->error);
    }

    $stmt->bind_param('sssssdsssss',
        $device_type, $device_brand, $device_model, $condition,
        $description, $asking_price, $first_name, $last_name, $email, $phone, $pickup_address
    );

    if (!$stmt->execute()) {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }

    $request_id = $db->db_conn()->insert_id;

    // Handle image URLs if provided
    if (isset($_POST['image_urls']) && is_array($_POST['image_urls'])) {
        $image_urls = $_POST['image_urls'];
        $image_filenames = $_POST['image_filenames'] ?? [];

        $image_sql = "INSERT INTO device_drop_images (request_id, image_url, original_filename) VALUES (?, ?, ?)";
        $image_stmt = $db->db_conn()->prepare($image_sql);

        if (!$image_stmt) {
            throw new Exception('Image database prepare failed: ' . $db->db_conn()->error);
        }

        foreach ($image_urls as $index => $image_url) {
            $filename = isset($image_filenames[$index]) ? $image_filenames[$index] : "image_$index";

            $image_stmt->bind_param('iss', $request_id, $image_url, $filename);

            if (!$image_stmt->execute()) {
                error_log('Image insert failed: ' . $image_stmt->error);
                // Continue with other images instead of failing completely
            }
        }

        $image_stmt->close();
    }

    $stmt->close();

    // Send email notification (optional)
    $subject = "New Device Drop Request - Request #$request_id";
    $message = "A new device drop request has been submitted.\n\n";
    $message .= "Request ID: $request_id\n";
    $message .= "Device: $device_brand $device_model ($device_type)\n";
    $message .= "Condition: $condition\n";
    $message .= "Customer: $first_name $last_name\n";
    $message .= "Email: $email\n";
    $message .= "Phone: $phone\n";
    if ($asking_price) {
        $message .= "Asking Price: $" . number_format($asking_price, 2) . "\n";
    }
    if ($description) {
        $message .= "Description: $description\n";
    }

    // Uncomment below to send email notifications
    // mail('admin@gadgetgarage.com', $subject, $message);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Device drop request submitted successfully',
        'request_id' => $request_id
    ]);

} catch (Exception $e) {
    // Log error
    error_log('Device Drop Error: ' . $e->getMessage());

    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>