<?php
/**
 * COMPREHENSIVE Image Helper Functions
 * FORCES ALL images to use server URLs - NO EXCEPTIONS
 */

/**
 * Get the correct image path for a product image - ALWAYS RETURNS SERVER URL
 * @param string $image_filename The image filename from database
 * @param string $product_title The product title for placeholder text
 * @param string $size Optional size parameter for placeholder (default: 400x300)
 * @return string The FULL SERVER image URL to use
 */
function get_product_image_url($image_filename, $product_title = 'Product', $size = '400x300') {
    // FORCE SERVER URL - NO LOCAL PATHS ALLOWED
    $server_base_url = 'http://169.239.251.102:442/~chelsea.somuah/uploads/';

    // Clean the image filename
    $image_filename = trim($image_filename ?? '');

    // Remove any null or empty values
    if (empty($image_filename) || $image_filename === 'null' || strtolower($image_filename) === 'null') {
        return generate_placeholder_url($product_title, $size);
    }

    // If already a full URL, return it
    if (strpos($image_filename, 'http://') === 0 || strpos($image_filename, 'https://') === 0) {
        return htmlspecialchars($image_filename);
    }

    // Remove ANY local path prefixes
    $clean_filename = $image_filename;
    $clean_filename = str_replace('uploads/products/', '', $clean_filename);
    $clean_filename = str_replace('uploads/', '', $clean_filename);
    $clean_filename = str_replace('images/', '', $clean_filename);
    $clean_filename = str_replace('../', '', $clean_filename);
    $clean_filename = str_replace('./', '', $clean_filename);
    $clean_filename = ltrim($clean_filename, '/\\');

    // FORCE SERVER URL
    $full_url = $server_base_url . $clean_filename;
    return htmlspecialchars($full_url);
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