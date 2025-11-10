<?php
require_once __DIR__ . '/../settings/core.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!check_login()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if files were uploaded
        if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
            echo json_encode(['status' => 'error', 'message' => 'No files selected']);
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/products/';
        $webPath = '../uploads/products/';
        $uploadedFiles = [];
        $errors = [];
        $imagePrefix = trim($_POST['image_prefix'] ?? '');

        // Ensure upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Process each file
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $fileName = $_FILES['images']['name'][$i];
            $fileTmpName = $_FILES['images']['tmp_name'][$i];
            $fileSize = $_FILES['images']['size'][$i];
            $fileError = $_FILES['images']['error'][$i];

            // Skip empty files
            if (empty($fileName)) continue;

            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading $fileName";
                continue;
            }

            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedTypes)) {
                $errors[] = "$fileName: Invalid file type. Only JPG, PNG, GIF, WEBP allowed.";
                continue;
            }

            // Check file size (5MB limit)
            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = "$fileName: File too large. Maximum 5MB allowed.";
                continue;
            }

            // Generate unique filename with optional prefix
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $prefix = !empty($imagePrefix) ? $imagePrefix . '_' : '';
            $uniqueName = $prefix . $baseName . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $uniqueName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'file_name' => $uniqueName,
                    'web_path' => $webPath . $uniqueName,
                    'full_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/REGISTER_SAMPLE/uploads/products/' . $uniqueName
                ];
            } else {
                $errors[] = "Failed to upload $fileName";
            }
        }

        // Return response
        if (!empty($uploadedFiles)) {
            $response = [
                'status' => 'success',
                'message' => count($uploadedFiles) . ' files uploaded successfully',
                'files' => $uploadedFiles
            ];

            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }

            echo json_encode($response);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No files were uploaded successfully',
                'errors' => $errors
            ]);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Upload failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>