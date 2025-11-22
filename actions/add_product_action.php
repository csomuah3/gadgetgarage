<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/product_controller.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!check_login()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get and validate form data
    $product_title = trim($_POST['product_title'] ?? '');
    $product_price = floatval($_POST['product_price'] ?? 0);
    $product_desc = trim($_POST['product_desc'] ?? '');
    $product_keywords = trim($_POST['product_keywords'] ?? '');
    $product_color = trim($_POST['product_color'] ?? '');
    $product_cat = intval($_POST['product_cat'] ?? 0);
    $product_brand = intval($_POST['product_brand'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);

    // Validate required fields
    if (empty($product_title)) {
        echo json_encode(['status' => 'error', 'message' => 'Product title is required']);
        exit;
    }

    if ($product_price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product price must be greater than 0']);
        exit;
    }

    if ($product_cat <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a valid category']);
        exit;
    }

    if ($product_brand <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a valid brand']);
        exit;
    }

    if ($stock_quantity < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Stock quantity cannot be negative']);
        exit;
    }

    // Handle image upload
    $product_image = '';
    $upload_warnings = [];

    // Handle main image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        error_log("Processing main image upload: " . $_FILES['product_image']['name']);
        $upload_result = uploadImageToServer($_FILES['product_image']);
        if ($upload_result['success']) {
            $product_image = $upload_result['filename'];
            error_log("Main image uploaded successfully: " . $product_image);
        } else {
            $upload_warnings[] = 'Main image upload failed: ' . $upload_result['message'];
            error_log("Main image upload failed: " . $upload_result['message']);
        }
    } else if (isset($_FILES['product_image'])) {
        error_log("Main image upload error code: " . $_FILES['product_image']['error']);
    }

    // If no main image uploaded, use placeholder
    if (empty($product_image)) {
        $product_image = 'placeholder.jpg';
        error_log("Using placeholder image");
    }

    // Add the product to database
    error_log("Adding product to database with data: " . json_encode([
        'title' => $product_title,
        'price' => $product_price,
        'desc' => substr($product_desc, 0, 50) . '...',
        'image' => $product_image,
        'keywords' => $product_keywords,
        'color' => $product_color,
        'category' => $product_cat,
        'brand' => $product_brand,
        'stock' => $stock_quantity
    ]));

    $result = add_product_ctr(
        $product_title,
        $product_price,
        $product_desc,
        $product_image,
        $product_keywords,
        $product_color,
        $product_cat,
        $product_brand,
        $stock_quantity
    );

    error_log("Product insertion result: " . json_encode($result));

    if ($result['status'] === 'success') {
        // Handle additional images if uploaded
        if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
            $additional_images = handleMultipleImageUpload($_FILES['product_images']);
            if (!empty($additional_images)) {
                $result['additional_images'] = $additional_images;
                $result['message'] .= ' (' . count($additional_images) . ' additional images uploaded)';
            }
        }

        // Add warnings if any
        if (!empty($upload_warnings)) {
            $result['warnings'] = $upload_warnings;
        }

        // Set session success message for page reload
        $_SESSION['success_message'] = $result['message'];

        echo json_encode($result);
    } else {
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Add product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Upload single image to external server
 */
function uploadImageToServer($file) {
    try {
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
                UPLOAD_ERR_PARTIAL => 'File upload was interrupted',
                UPLOAD_ERR_NO_FILE => 'No file was selected',
                UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Server cannot write to disk',
                UPLOAD_ERR_EXTENSION => 'File upload blocked by server extension'
            ];

            throw new Exception($error_messages[$file['error']] ?? 'Unknown upload error');
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed');
        }

        // Generate unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;

        // Upload to external server
        $upload_url = 'http://169.239.251.102:442/~chelsea.somuah/upload.php';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $upload_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'uploadedFile' => new CURLFile($file['tmp_name'], $file_type, $filename)
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        // Log for debugging
        error_log("Upload attempt - HTTP Code: $httpCode, Response: " . substr($response, 0, 200));

        if ($error) {
            throw new Exception('Network error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Upload server returned HTTP $httpCode");
        }

        // Check for error indicators in response
        if (!empty($response) && (
            strpos($response, 'class="error"') !== false ||
            strpos($response, 'Upload failed') !== false ||
            strpos($response, 'Error:') !== false
        )) {
            throw new Exception('Server rejected the file upload');
        }

        return [
            'success' => true,
            'filename' => $filename,
            'server_response' => 'File uploaded successfully'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Handle multiple image uploads
 */
function handleMultipleImageUpload($files) {
    $uploaded_images = [];

    if (!is_array($files['name'])) {
        return $uploaded_images;
    }

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $upload_result = uploadImageToServer($file);
            if ($upload_result['success']) {
                $uploaded_images[] = $upload_result['filename'];
            }
        }
    }

    return $uploaded_images;
}
?>