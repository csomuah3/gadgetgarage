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

    // No image found, return placeholder data URL directly
    return generate_placeholder_url($product_title, $size);
}

/**
 * Generate a placeholder image URL
 * @param string $text Text to display on placeholder
 * @param string $size Size in format "400x300"
 * @return string Placeholder URL
 */
function generate_placeholder_url($text, $size = '400x300') {
    $text = $text ?: 'Gadget Garage';
    $size_parts = explode('x', strtolower($size));
    $width = isset($size_parts[0]) ? (int)$size_parts[0] : 400;
    $height = isset($size_parts[1]) ? (int)$size_parts[1] : 300;

    $sanitized_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d">
            <rect width="100%%" height="100%%" fill="#eef2ff"/>
            <rect x="1" y="1" width="%3$d" height="%4$d" fill="none" stroke="#cbd5f5" stroke-width="2"/>
            <text x="50%%" y="50%%" font-family="Arial, sans-serif" font-size="%5$d" fill="#1f2937" text-anchor="middle" dominant-baseline="middle">%6$s</text>
        </svg>',
        $width,
        $height,
        max($width - 2, 0),
        max($height - 2, 0),
        max((int)($height * 0.12), 12),
        $sanitized_text
    );

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Get JavaScript onerror handler for image fallback
 * @param string $product_title The product title for fallback
 * @param string $size Size in format "400x300"
 * @return string JavaScript onerror attribute value
 */
function get_image_onerror($product_title, $size = '400x300') {
    $data_uri = generate_placeholder_url($product_title, $size);
    return "this.src='{$data_uri}'";
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