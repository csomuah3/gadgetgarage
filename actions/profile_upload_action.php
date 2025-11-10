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
        // Check if file was uploaded
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
            exit;
        }

        $file = $_FILES['profile_image'];
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        $webPath = '../uploads/profiles/';

        // Ensure upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed.']);
            exit;
        }

        // Check file size (2MB limit for profile pictures)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'File too large. Maximum 2MB allowed.']);
            exit;
        }

        // Get user ID
        $userId = get_user_id();

        // Remove old profile picture if exists
        $oldFiles = glob($uploadDir . 'profile_' . $userId . '.*');
        foreach ($oldFiles as $oldFile) {
            unlink($oldFile);
        }

        // Generate filename with user ID
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $fullUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/REGISTER_SAMPLE/uploads/profiles/' . $fileName;

            // TODO: Update user profile in database with new image path
            // For now, we'll just return the file info

            echo json_encode([
                'status' => 'success',
                'message' => 'Profile picture updated successfully',
                'file_name' => $fileName,
                'web_path' => $webPath . $fileName,
                'full_url' => $fullUrl
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload file']);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Upload failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>