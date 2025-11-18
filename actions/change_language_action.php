<?php
/**
 * Change Language Action
 * Handles language switching requests
 */

session_start();
header('Content-Type: application/json');

try {
    // Get the requested language
    $language = $_POST['language'] ?? $_GET['language'] ?? 'en';

    // Available languages
    $available_languages = ['en', 'es', 'fr', 'de'];

    // Validate language
    if (!in_array($language, $available_languages)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid language selected'
        ]);
        exit;
    }

    // Set the language in session
    $_SESSION['language'] = $language;

    // Success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Language changed successfully',
        'language' => $language
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to change language: ' . $e->getMessage()
    ]);
}
?>