<?php
require_once __DIR__ . '/../settings/core.php';

header('Content-Type: application/json');

// Check if user is logged in using core function
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);

    // Validate product ID
    if ($product_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a valid product']);
        exit;
    }

    // Check if images were uploaded
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        echo json_encode(['status' => 'error', 'message' => 'Please select images to upload']);
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/products/';

    // Create uploads directory if it doesn't exist (using exact same logic)
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $uploaded_images = [];
    $errors = [];
    $files = $_FILES['images'];

    // Process each uploaded file
    for ($i = 0; $i < count($files['name']); $i++) {
        // Skip empty files
        if (empty($files['name'][$i]) || $files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        // Generate proper filename using EXACT same format as existing system
        $file_extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $file_name = 'product_' . $product_id . '_' . time() . '_' . $i . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;

        // Check if file is an image with EXACT same validation as existing system
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($files['tmp_name'][$i]);

        if (in_array($file_type, $allowed_types)) {
            // Check file size (5MB max) - EXACT same check as existing system
            if ($files['size'][$i] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
                    $uploaded_images[] = [
                        'filename' => $file_name,
                        'original_name' => $files['name'][$i]
                    ];
                    // Log successful upload (same as existing system)
                    error_log("Bulk image uploaded successfully: " . $upload_path);
                } else {
                    $errors[] = "Failed to save " . $files['name'][$i];
                    error_log("Failed to move uploaded file: " . $files['name'][$i]);
                }
            } else {
                $errors[] = $files['name'][$i] . " is too large (max 5MB)";
            }
        } else {
            $errors[] = $files['name'][$i] . " has invalid file type";
        }
    }

    // Prepare response
    $response = [
        'status' => count($uploaded_images) > 0 ? 'success' : 'error',
        'message' => count($uploaded_images) . ' images uploaded successfully',
        'uploaded_images' => $uploaded_images,
        'errors' => $errors,
        'upload_count' => count($uploaded_images)
    ];

    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>