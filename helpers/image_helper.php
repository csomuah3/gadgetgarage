<?php
/**
 * Image Helper Functions
 * Handles image path resolution and fallback mechanisms
 */

/**
 * Get the correct image path for a product image
 * @param string $image_filename The image filename from database
 * @param string $product_title The product title for placeholder text
 * @param string $size Optional size parameter for placeholder (default: 400x300)
 * @return string The image URL to use
 */
function get_product_image_url($image_filename, $product_title = 'Product', $size = '400x300') {
    // Server upload URL base
    $server_base_url = 'http://169.239.251.102:442/~chelsea.somuah/uploads/';

    // Clean the image filename
    $image_filename = trim($image_filename);

    // If we have an image filename, return the server URL
    if (!empty($image_filename)) {
        // If the filename already contains the full server URL, return as is
        if (strpos($image_filename, 'http://169.239.251.102:442/~chelsea.somuah/uploads/') === 0) {
            return htmlspecialchars($image_filename);
        }

        // Remove any leading path separators and build the full server URL
        $clean_filename = ltrim($image_filename, '/');
        return $server_base_url . htmlspecialchars($clean_filename);
    }

    // No image found, return placeholder URL directly
    return generate_placeholder_url($product_title, $size);
}

/**
 * Generate a placeholder image URL
 * @param string $text Text to display on placeholder
 * @param string $size Size in format "400x300"
 * @return string Placeholder URL
 */
function generate_placeholder_url($text, $size = '400x300') {
    $encoded_text = urlencode($text);
    return "https://via.placeholder.com/{$size}/008060/ffffff?text={$encoded_text}";
}

/**
 * Get JavaScript onerror handler for image fallback
 * @param string $product_title The product title for fallback
 * @param string $size Size in format "400x300"
 * @return string JavaScript onerror attribute value
 */
function get_image_onerror($product_title, $size = '400x300') {
    $placeholder_url = generate_placeholder_url($product_title, $size);
    return "this.src='{$placeholder_url}'";
}

/**
 * Check if image directories exist and are writable
 * @return array Status of each directory
 */
function check_image_directories() {
    $base_dir = __DIR__ . '/..';
    $directories = [
        'uploads/products/' => false,
        'uploads/' => false,
        'images/' => false
    ];

    foreach ($directories as $dir => $status) {
        $full_path = $base_dir . '/' . $dir;
        $directories[$dir] = [
            'exists' => is_dir($full_path),
            'writable' => is_writable($full_path),
            'path' => $full_path
        ];
    }

    return $directories;
}

/**
 * Create image directories if they don't exist
 * @return array Results of directory creation
 */
function ensure_image_directories() {
    $base_dir = __DIR__ . '/..';
    $directories = ['uploads/', 'uploads/products/', 'uploads/profiles/'];
    $results = [];

    foreach ($directories as $dir) {
        $full_path = $base_dir . '/' . $dir;
        if (!is_dir($full_path)) {
            $created = mkdir($full_path, 0755, true);
            $results[$dir] = $created;
        } else {
            $results[$dir] = true;
        }
    }

    return $results;
}
?>