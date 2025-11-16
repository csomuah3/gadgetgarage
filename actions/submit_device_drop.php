<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response header
header('Content-Type: application/json');

try {
    // Log incoming request for debugging
    error_log('Device Drop Request received');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r(array_keys($_FILES), true));

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

    // Handle reasons checkbox array
    $reasons = [];
    if (isset($_POST['reasons']) && is_array($_POST['reasons'])) {
        $reasons = $_POST['reasons'];
    }
    $description = !empty($reasons) ? implode(', ', $reasons) : null;

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

    // Handle uploaded images
    if (!empty($_FILES)) {
        $upload_dir = '../uploads/device_drop/';

        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $image_sql = "INSERT INTO device_drop_images (request_id, image_url, original_filename) VALUES (?, ?, ?)";
        $image_stmt = $db->db_conn()->prepare($image_sql);

        if (!$image_stmt) {
            throw new Exception('Image database prepare failed: ' . $db->db_conn()->error);
        }

        foreach ($_FILES as $key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($file['tmp_name']);

                if (!in_array($file_type, $allowed_types)) {
                    continue; // Skip invalid files
                }

                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    continue; // Skip large files
                }

                // Generate unique filename
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename = 'device_drop_' . $request_id . '_' . uniqid() . '.' . $extension;
                $filepath = $upload_dir . $new_filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    chmod($filepath, 0644);

                    // Save to database
                    $relative_path = 'uploads/device_drop/' . $new_filename;
                    $image_stmt->bind_param('iss', $request_id, $relative_path, $file['name']);

                    if (!$image_stmt->execute()) {
                        error_log('Image insert failed: ' . $image_stmt->error);
                        // Delete uploaded file if database insert failed
                        unlink($filepath);
                    }
                } else {
                    error_log('Failed to move uploaded file: ' . $file['name']);
                }
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
        $message .= "Asking Price: GH₵ " . number_format($asking_price, 2) . "\n";
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
    // Log detailed error for debugging
    error_log('Device Drop Error: ' . $e->getMessage());
    error_log('POST data: ' . print_r($_POST, true));
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Return user-friendly error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'There was an error processing your request. Please try again.',
        'debug' => $e->getMessage() // Remove this in production
    ]);
}
?>