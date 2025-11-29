<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Set response header FIRST before any output
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

try {
    // Log incoming request for debugging
    error_log('Device Drop Request received');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r(array_keys($_FILES), true));

    // Include database connection
    require_once(__DIR__ . '/../settings/db_class.php');

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

    // Handle reasons checkbox array or direct description
    $reasons = [];
    if (isset($_POST['reasons']) && is_array($_POST['reasons'])) {
        $reasons = $_POST['reasons'];
    }
    $description = null;
    if (!empty($reasons)) {
        $description = implode(', ', $reasons);
    } elseif (isset($_POST['description']) && !empty(trim($_POST['description']))) {
        $description = trim($_POST['description']);
    }

    $asking_price = isset($_POST['asking_price']) && !empty($_POST['asking_price']) ?
                   floatval($_POST['asking_price']) : null;
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    // Remove formatting from phone number (parentheses, dashes, spaces)
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    $pickup_address = isset($_POST['address']) ? trim($_POST['address']) : null;

    // Handle AI valuation data (if provided)
    $ai_valuation = isset($_POST['ai_valuation']) && !empty($_POST['ai_valuation']) ?
                   floatval($_POST['ai_valuation']) : null;
    $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : null;
    $final_amount = isset($_POST['final_amount']) && !empty($_POST['final_amount']) ?
                   floatval($_POST['final_amount']) : null;
    $condition_grade = isset($_POST['condition_grade']) ? trim($_POST['condition_grade']) : null;
    $value_reasoning = isset($_POST['value_reasoning']) ? trim($_POST['value_reasoning']) : null;

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
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    // Escape values for security
    $device_type = mysqli_real_escape_string($db->db_conn(), $device_type);
    $device_brand = mysqli_real_escape_string($db->db_conn(), $device_brand);
    $device_model = mysqli_real_escape_string($db->db_conn(), $device_model);
    $condition = mysqli_real_escape_string($db->db_conn(), $condition);
    $description = $description ? "'" . mysqli_real_escape_string($db->db_conn(), $description) . "'" : 'NULL';
    $asking_price = $asking_price !== null ? floatval($asking_price) : 'NULL';
    $first_name = mysqli_real_escape_string($db->db_conn(), $first_name);
    $last_name = mysqli_real_escape_string($db->db_conn(), $last_name);
    $email = mysqli_real_escape_string($db->db_conn(), $email);
    $phone = mysqli_real_escape_string($db->db_conn(), $phone);
    $pickup_address = $pickup_address ? "'" . mysqli_real_escape_string($db->db_conn(), $pickup_address) . "'" : 'NULL';

    // Escape AI valuation data
    $ai_valuation_sql = $ai_valuation !== null ? floatval($ai_valuation) : 'NULL';
    $payment_method_sql = $payment_method ? "'" . mysqli_real_escape_string($db->db_conn(), $payment_method) . "'" : 'NULL';
    $final_amount_sql = $final_amount !== null ? floatval($final_amount) : 'NULL';
    $condition_grade_sql = $condition_grade ? "'" . mysqli_real_escape_string($db->db_conn(), $condition_grade) . "'" : 'NULL';
    $value_reasoning_sql = $value_reasoning ? "'" . mysqli_real_escape_string($db->db_conn(), $value_reasoning) . "'" : 'NULL';

    // Insert device drop request using direct SQL
    $sql = "INSERT INTO device_drop_requests (
        device_type, device_brand, device_model, condition_status,
        description, asking_price, first_name, last_name, email, phone, pickup_address,
        ai_valuation, payment_method, final_amount, condition_grade, value_reasoning
    ) VALUES (
        '$device_type', '$device_brand', '$device_model', '$condition',
        $description, $asking_price, '$first_name', '$last_name', '$email', '$phone', $pickup_address,
        $ai_valuation_sql, $payment_method_sql, $final_amount_sql, $condition_grade_sql, $value_reasoning_sql
    )";

    if (!$db->db_write_query($sql)) {
        throw new Exception('Database insert failed: ' . mysqli_error($db->db_conn()));
    }

    $request_id = mysqli_insert_id($db->db_conn());

    // Store credit will be created only after admin approval
    // Log the request for admin review
    error_log("Device drop request #$request_id submitted - payment method: $payment_method, amount: " . ($final_amount ?: 'TBD'));

    // Handle uploaded images
    if (!empty($_FILES)) {
        $upload_dir = '../uploads/device_drop/';

        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Process all uploaded files
        foreach ($_FILES as $key => $file) {
            // Handle array of files (multiple uploads)
            if (is_array($file['error'])) {
                foreach ($file['error'] as $index => $error) {
                    if ($error === UPLOAD_ERR_OK) {
                        $this_file = [
                            'name' => $file['name'][$index],
                            'type' => $file['type'][$index],
                            'tmp_name' => $file['tmp_name'][$index],
                            'error' => $file['error'][$index],
                            'size' => $file['size'][$index]
                        ];
                        
                        // Validate file type
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        $file_type = mime_content_type($this_file['tmp_name']);

                        if (!in_array($file_type, $allowed_types)) {
                            error_log('Invalid file type: ' . $this_file['name']);
                            continue;
                        }

                        // Validate file size (max 5MB)
                        if ($this_file['size'] > 5 * 1024 * 1024) {
                            error_log('File too large: ' . $this_file['name']);
                            continue;
                        }

                        // Generate unique filename
                        $extension = strtolower(pathinfo($this_file['name'], PATHINFO_EXTENSION));
                        $new_filename = 'device_drop_' . $request_id . '_' . uniqid() . '.' . $extension;
                        $filepath = $upload_dir . $new_filename;

                        // Move uploaded file
                        if (move_uploaded_file($this_file['tmp_name'], $filepath)) {
                            chmod($filepath, 0644);

                            // Save to database
                            $relative_path = 'uploads/device_drop/' . $new_filename;
                            $image_url = mysqli_real_escape_string($db->db_conn(), $relative_path);
                            $original_filename = mysqli_real_escape_string($db->db_conn(), $this_file['name']);
                            
                            $image_sql = "INSERT INTO device_drop_images (request_id, image_url, original_filename) 
                                         VALUES ($request_id, '$image_url', '$original_filename')";
                            
                            if (!$db->db_write_query($image_sql)) {
                                error_log('Image insert failed: ' . mysqli_error($db->db_conn()));
                                unlink($filepath);
                            }
                        } else {
                            error_log('Failed to move uploaded file: ' . $this_file['name']);
                        }
                    }
                }
            } else {
                // Handle single file upload
                if ($file['error'] === UPLOAD_ERR_OK) {
                    // Validate file type
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $file_type = mime_content_type($file['tmp_name']);

                    if (!in_array($file_type, $allowed_types)) {
                        error_log('Invalid file type: ' . $file['name']);
                        continue;
                    }

                    // Validate file size (max 5MB)
                    if ($file['size'] > 5 * 1024 * 1024) {
                        error_log('File too large: ' . $file['name']);
                        continue;
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
                        $image_url = mysqli_real_escape_string($db->db_conn(), $relative_path);
                        $original_filename = mysqli_real_escape_string($db->db_conn(), $file['name']);
                        
                        $image_sql = "INSERT INTO device_drop_images (request_id, image_url, original_filename) 
                                     VALUES ($request_id, '$image_url', '$original_filename')";
                        
                        if (!$db->db_write_query($image_sql)) {
                            error_log('Image insert failed: ' . mysqli_error($db->db_conn()));
                            unlink($filepath);
                        }
                    } else {
                        error_log('Failed to move uploaded file: ' . $file['name']);
                    }
                }
            }
        }
    }

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

    // Clear output buffer before sending JSON
    ob_clean();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Device drop request submitted successfully',
        'request_id' => $request_id
    ]);
    
    // End output buffering
    ob_end_flush();
    exit;

} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
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
} catch (Error $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log PHP 7+ errors
    error_log('Device Drop Fatal Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
    
    ob_end_flush();
    exit;
}

// End output buffering if we get here (shouldn't happen)
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>