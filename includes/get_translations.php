<?php
/**
 * API endpoint to provide translation data to JavaScript
 */

header('Content-Type: application/json');

// Include the language configuration
require_once(__DIR__ . '/language_config.php');

// Get current language
$current_language = $_SESSION['language'] ?? 'en';

// Return translation data as JSON
$response = [
    'translations' => $translations,
    'current_language' => $current_language,
    'available_languages' => $available_languages
];

echo json_encode($response);
?>