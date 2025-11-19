<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/product_controller.php';

header('Content-Type: application/json');

// Check if user is logged in using core function
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_title = trim($_POST['product_title'] ?? '');
    $product_price = (float)($_POST['product_price'] ?? 0);
    $product_desc = trim($_POST['product_desc'] ?? '');

    // Handle primary image upload (backward compatibility)
    $product_image = '';
    $uploaded_images = [];

    // Handle single image upload (for backward compatibility)
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadSingleImage($_FILES['product_image'], 'primary');
        if ($upload_result['success']) {
            $product_image = $upload_result['filename'];
            $uploaded_images[] = [
                'filename' => $upload_result['filename'],
                'is_primary' => true,
                'order' => 0
            ];
        } else {
            echo json_encode(['status' => 'error', 'message' => $upload_result['message']]);
            exit;
        }
    }

    // Handle multiple images upload (new functionality)
    if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
        $multiple_files = $_FILES['product_images'];

        for ($i = 0; $i < count($multiple_files['name']); $i++) {
            if ($multiple_files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $multiple_files['name'][$i],
                    'type' => $multiple_files['type'][$i],
                    'tmp_name' => $multiple_files['tmp_name'][$i],
                    'error' => $multiple_files['error'][$i],
                    'size' => $multiple_files['size'][$i]
                ];

                $is_primary = empty($uploaded_images); // First image becomes primary if no single image
                $upload_result = uploadSingleImage($file, $is_primary ? 'primary' : 'gallery');

                if ($upload_result['success']) {
                    if ($is_primary && empty($product_image)) {
                        $product_image = $upload_result['filename'];
                    }

                    $uploaded_images[] = [
                        'filename' => $upload_result['filename'],
                        'is_primary' => $is_primary,
                        'order' => count($uploaded_images)
                    ];
                }
            }
        }
    }
    $product_keywords = trim($_POST['product_keywords'] ?? '');
    $product_color = trim($_POST['product_color'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 10);

    // Validate input
    if (empty($product_title)) {
        echo json_encode(['status' => 'error', 'message' => 'Product title is required']);
        exit;
    }

    if ($product_price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product price must be greater than 0']);
        exit;
    }

    if ($category_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a valid category']);
        exit;
    }

    if ($brand_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a valid brand']);
        exit;
    }

    try {
        // Add the product first
        $result = add_product_ctr($product_title, $product_price, $product_desc, $product_image, $product_keywords, $product_color, $category_id, $brand_id, $stock_quantity);

        if ($result['status'] === 'success' && !empty($uploaded_images)) {
            // Get the new product ID
            $product_id = $result['product_id'];

            // Save all images to the product_images table
            $image_save_errors = [];
            foreach ($uploaded_images as $image) {
                $image_result = saveProductImageToDatabase($product_id, $image['filename'], $image['is_primary'], $image['order']);
                if (!$image_result) {
                    $image_save_errors[] = "Failed to save image: " . $image['filename'];
                }
            }

            if (!empty($image_save_errors)) {
                $result['warnings'] = $image_save_errors;
            }

            $result['uploaded_images_count'] = count($uploaded_images);
        }

        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add product: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

/**
 * Upload a single image file to external server
 */
function uploadSingleImage($file, $type = 'gallery') {
    try {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $file['error']);
        }

        // Validate file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }

        // Upload to external server
        $server_upload_url = 'http://169.239.251.102:442/~chelsea.somuah/upload.php';

        // Generate proper filename with product prefix
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_name = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;

        // Create cURL file upload
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $server_upload_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile($file['tmp_name'], $file_type, $file_name)
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        // Log the response for debugging
        error_log("Upload response: " . $response);
        error_log("HTTP Code: " . $httpCode);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode === 200) {
            // If we got a 200 response, consider it successful regardless of response format
            if (!empty($response)) {
                // Try to parse as JSON, but don't fail if it's not JSON
                $uploadResult = json_decode($response, true);
                if ($uploadResult && isset($uploadResult['status'])) {
                    if ($uploadResult['status'] === 'success') {
                        return [
                            'success' => true,
                            'filename' => $file_name,
                            'server_response' => $uploadResult
                        ];
                    } else {
                        throw new Exception('Server error: ' . ($uploadResult['message'] ?? 'Upload failed'));
                    }
                } else {
                    // Response is not JSON or doesn't have status, but HTTP 200 means success
                    return [
                        'success' => true,
                        'filename' => $file_name,
                        'server_response' => substr($response, 0, 200)
                    ];
                }
            } else {
                // Empty response but HTTP 200 - assume success
                return [
                    'success' => true,
                    'filename' => $file_name,
                    'server_response' => 'Empty response but HTTP 200'
                ];
            }
        } else {
            throw new Exception("Server returned HTTP $httpCode. Response: " . substr($response, 0, 200));
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Save product image to database
 */
function saveProductImageToDatabase($product_id, $filename, $is_primary = false, $image_order = 0) {
    require_once __DIR__ . '/../settings/connection.php';

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_2025A_chelsea_somuah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            INSERT INTO product_images (product_id, image_filename, is_primary, image_order)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([$product_id, $filename, $is_primary, $image_order]);
    } catch (Exception $e) {
        error_log("Failed to save product image to database: " . $e->getMessage());
        return false;
    }
}
?>