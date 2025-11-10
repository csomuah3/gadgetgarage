<?php
/**
 * Product Image Upload Handler
 * Handles all product image upload functionality
 */

session_start();
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../helpers/image_helper.php');

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!check_login() || !check_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Admin privileges required.'
    ]);
    exit;
}

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'upload_single':
        handleSingleImageUpload();
        break;
    case 'upload_multiple':
        handleMultipleImageUpload();
        break;
    case 'delete_image':
        handleImageDeletion();
        break;
    case 'get_image_url':
        handleGetImageUrl();
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified.'
        ]);
        break;
}

/**
 * Handle single image upload
 */
function handleSingleImageUpload() {
    try {
        // Validate input
        if (!isset($_FILES['image']) || !isset($_POST['product_id'])) {
            throw new Exception('Missing required fields: image file or product_id');
        }

        $product_id = (int)$_POST['product_id'];
        $file = $_FILES['image'];

        // Validate file
        $uploadResult = validateAndUploadImage($file, $product_id);

        if ($uploadResult['success']) {
            // Update database with new image filename
            $updateResult = updateProductImage($product_id, $uploadResult['filename']);

            if ($updateResult) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'filename' => $uploadResult['filename'],
                    'url' => get_product_image_url($uploadResult['filename'], '', '400x300')
                ]);
            } else {
                // Delete uploaded file if database update failed
                unlink($uploadResult['filepath']);
                throw new Exception('Failed to update product image in database');
            }
        } else {
            throw new Exception($uploadResult['message']);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle multiple image upload (for future gallery feature)
 */
function handleMultipleImageUpload() {
    try {
        if (!isset($_FILES['images']) || !isset($_POST['product_id'])) {
            throw new Exception('Missing required fields');
        }

        $product_id = (int)$_POST['product_id'];
        $files = $_FILES['images'];
        $uploaded_files = [];
        $errors = [];

        // Handle multiple files
        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $result = validateAndUploadImage($file, $product_id, $i);

            if ($result['success']) {
                $uploaded_files[] = [
                    'filename' => $result['filename'],
                    'url' => get_product_image_url($result['filename'], '', '400x300')
                ];
            } else {
                $errors[] = $result['message'];
            }
        }

        echo json_encode([
            'success' => count($uploaded_files) > 0,
            'message' => count($uploaded_files) . ' images uploaded successfully',
            'uploaded_files' => $uploaded_files,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle image deletion
 */
function handleImageDeletion() {
    try {
        if (!isset($_POST['product_id']) || !isset($_POST['filename'])) {
            throw new Exception('Missing required fields');
        }

        $product_id = (int)$_POST['product_id'];
        $filename = trim($_POST['filename']);

        // Remove from database
        $updateResult = updateProductImage($product_id, '');

        if ($updateResult) {
            // Delete physical file
            $filepath = __DIR__ . '/../uploads/products/' . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to remove image from database');
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Get image URL for a product
 */
function handleGetImageUrl() {
    try {
        if (!isset($_GET['product_id'])) {
            throw new Exception('Product ID required');
        }

        $product_id = (int)$_GET['product_id'];

        // Get product image from database
        require_once(__DIR__ . '/../controllers/product_controller.php');
        $product = view_single_product_ctr($product_id);

        if ($product) {
            $image_url = get_product_image_url(
                $product['product_image'],
                $product['product_title'],
                '50x50'
            );

            if ($image_url) {
                echo json_encode([
                    'success' => true,
                    'url' => $image_url,
                    'filename' => $product['product_image']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No image found for this product'
                ]);
            }
        } else {
            throw new Exception('Product not found');
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Validate and upload image file
 */
function validateAndUploadImage($file, $product_id, $index = 0) {
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
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP allowed.');
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'product_' . $product_id . '_' . time();
        if ($index > 0) {
            $filename .= '_' . $index;
        }
        $filename .= '.' . $extension;

        // Ensure uploads directory exists
        $upload_dir = __DIR__ . '/../uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filepath = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Set proper permissions
            chmod($filepath, 0644);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath
            ];
        } else {
            throw new Exception('Failed to move uploaded file');
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Update product image in database
 */
function updateProductImage($product_id, $filename) {
    try {
        require_once(__DIR__ . '/../controllers/product_controller.php');

        // Get current product data
        $product = view_single_product_ctr($product_id);
        if (!$product) {
            return false;
        }

        // Update with new image filename
        return update_product_ctr(
            $product_id,
            $product['product_title'],
            $product['product_price'],
            $product['product_desc'],
            $filename, // New image filename
            $product['product_keywords'],
            $product['product_cat'],
            $product['product_brand']
        );

    } catch (Exception $e) {
        error_log("Failed to update product image: " . $e->getMessage());
        return false;
    }
}

/**
 * Get image info
 */
function getImageInfo($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }

    $info = getimagesize($filepath);
    return [
        'width' => $info[0],
        'height' => $info[1],
        'type' => $info[2],
        'mime' => $info['mime'],
        'size' => filesize($filepath)
    ];
}
?>