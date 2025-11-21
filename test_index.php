<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing basic PHP...\n";

// Test if files exist
$files_to_test = [
    'settings/core.php',
    'controllers/cart_controller.php',
    'helpers/image_helper.php',
    'includes/language_config.php',
    'includes/header.php'
];

foreach ($files_to_test as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "File $file: " . ($exists ? "EXISTS" : "NOT FOUND") . "\n";
}

echo "\nTesting includes...\n";

try {
    require_once(__DIR__ . '/settings/core.php');
    echo "core.php loaded successfully\n";
} catch (Exception $e) {
    echo "Error loading core.php: " . $e->getMessage() . "\n";
}

echo "\nBasic test complete.";
?>