<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/product_controller.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $product_title = trim($_POST['product_title'] ?? '');
    $product_price = (float)($_POST['product_price'] ?? 0);
    $product_desc = trim($_POST['product_desc'] ?? '');
    $product_keywords = trim($_POST['product_keywords'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);

    // Get existing product data first
    require_once __DIR__ . '/../controllers/product_controller.php';
    $existing_product = get_product_by_id_ctr($product_id);
    if (!$existing_product) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit;
    }

    // Handle file upload - keep existing image if no new image uploaded
    $product_image = $existing_product['product_image']; // Keep existing image by default

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        // Upload to server using curl
        $server_upload_url = 'http://169.239.251.102:442/~chelsea.somuah/upload.php';

        // Generate proper filename with product prefix
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $file_name = 'product_' . $product_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;

        // Check if file is an image with more thorough validation
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['product_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            // Check file size (5MB max)
            if ($_FILES['product_image']['size'] <= 5 * 1024 * 1024) {
                // Upload to server using curl
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $server_upload_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => [
                        'file' => new CURLFile($_FILES['product_image']['tmp_name'], $file_type, $file_name)
                    ]
                ]);

                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($httpCode === 200 && $response) {
                    $uploadResult = json_decode($response, true);
                    if ($uploadResult && $uploadResult['status'] === 'success') {
                        $product_image = $file_name;
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image to server: ' . ($uploadResult['message'] ?? 'Unknown error')]);
                        exit;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Server upload failed']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Image file too large. Maximum 5MB allowed.']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']);
            exit;
        }
    }

    // Validate input
    if ($product_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
        exit;
    }

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
        $result = update_product_ctr($product_id, $product_title, $product_price, $product_desc, $product_image, $product_keywords, $category_id, $brand_id);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update product: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>