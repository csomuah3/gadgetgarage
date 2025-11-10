<?php
/**
 * Upload Configuration
 * Centralized configuration for file uploads using server-based paths
 */

/**
 * Get the base upload directory path
 * Uses server document root to avoid hardcoded paths
 */
function get_upload_base_path() {
    // Get the application root directory dynamically
    $app_root = $_SERVER['DOCUMENT_ROOT'] . '/REGISTER_SAMPLE';
    return $app_root . '/uploads';
}

/**
 * Get the web-accessible base URL for uploads
 */
function get_upload_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/REGISTER_SAMPLE/uploads';
}

/**
 * Get upload configuration for different file types
 */
function get_upload_config() {
    $base_path = get_upload_base_path();
    $base_url = get_upload_base_url();

    return [
        'profiles' => [
            'path' => $base_path . '/profiles',
            'url' => $base_url . '/profiles',
            'max_size' => 2 * 1024 * 1024, // 2MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
            'mime_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
        ],
        'products' => [
            'path' => $base_path . '/products',
            'url' => $base_url . '/products',
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'mime_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
        ]
    ];
}

/**
 * Ensure upload directories exist
 */
function ensure_upload_directories() {
    $config = get_upload_config();
    $results = [];

    foreach ($config as $type => $settings) {
        if (!is_dir($settings['path'])) {
            $created = mkdir($settings['path'], 0755, true);
            $results[$type] = $created;
        } else {
            $results[$type] = true;
        }
    }

    return $results;
}

/**
 * Get upload path for specific type
 */
function get_upload_path($type) {
    $config = get_upload_config();
    return isset($config[$type]) ? $config[$type]['path'] : null;
}

/**
 * Get upload URL for specific type
 */
function get_upload_url($type) {
    $config = get_upload_config();
    return isset($config[$type]) ? $config[$type]['url'] : null;
}

/**
 * Get file constraints for specific type
 */
function get_upload_constraints($type) {
    $config = get_upload_config();
    if (!isset($config[$type])) {
        return null;
    }

    return [
        'max_size' => $config[$type]['max_size'],
        'allowed_types' => $config[$type]['allowed_types'],
        'mime_types' => $config[$type]['mime_types']
    ];
}

/**
 * Validate uploaded file against constraints
 */
function validate_upload($file, $type) {
    $constraints = get_upload_constraints($type);
    if (!$constraints) {
        return ['valid' => false, 'error' => 'Invalid upload type'];
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    // Check file size
    if ($file['size'] > $constraints['max_size']) {
        $max_mb = round($constraints['max_size'] / (1024 * 1024), 1);
        return ['valid' => false, 'error' => "File too large. Maximum {$max_mb}MB allowed."];
    }

    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $constraints['allowed_types'])) {
        $allowed = implode(', ', array_map('strtoupper', $constraints['allowed_types']));
        return ['valid' => false, 'error' => "Invalid file type. Only {$allowed} allowed."];
    }

    // Check MIME type
    $mime_type = mime_content_type($file['tmp_name']);
    if (!in_array($mime_type, $constraints['mime_types'])) {
        return ['valid' => false, 'error' => 'Invalid file format.'];
    }

    return ['valid' => true, 'extension' => $extension];
}

/**
 * Generate unique filename
 */
function generate_upload_filename($type, $original_name, $prefix = '', $suffix = '') {
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $timestamp = time();

    $filename = $type;
    if (!empty($prefix)) {
        $filename .= '_' . $prefix;
    }
    if (!empty($suffix)) {
        $filename .= '_' . $suffix;
    }
    $filename .= '_' . $timestamp . '.' . $extension;

    return $filename;
}

// Initialize upload directories when this file is included
ensure_upload_directories();
?>