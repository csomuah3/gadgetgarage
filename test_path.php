<?php
echo "<h2>Path Debug Information</h2>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h3>File Existence Check:</h3>";
$files_to_check = [
    'settings/core.php',
    'controllers/cart_controller.php',
    'helpers/image_helper.php',
    'includes/language_config.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    $exists = file_exists($full_path);
    echo "<p><strong>$file:</strong> " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . " ($full_path)</p>";
}

echo "<h3>Try Basic PHP:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
?>