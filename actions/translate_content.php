<?php
/**
 * Translate Content Action
 * Uses OpenAI API to translate content dynamically
 */

session_start();
header('Content-Type: application/json');

try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../helpers/ai_helper.php');
    require_once(__DIR__ . '/../includes/language_config.php');

    // Check if user is logged in (optional, but good for rate limiting)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request method'
        ]);
        exit;
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $content = $input['content'] ?? [];
    $target_language = $input['target_language'] ?? 'en';

    // Validate target language
    $available_languages = ['en', 'es', 'fr', 'de'];
    if (!in_array($target_language, $available_languages)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid target language'
        ]);
        exit;
    }

    // If English, return content as-is
    if ($target_language === 'en') {
        echo json_encode([
            'status' => 'success',
            'translations' => $content
        ]);
        exit;
    }

    // Validate content
    if (empty($content) || !is_array($content)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No content provided'
        ]);
        exit;
    }

    // Limit content size to avoid token limits (max 50 items per request)
    if (count($content) > 50) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Too much content. Maximum 50 items per request.'
        ]);
        exit;
    }

    // Initialize AI Helper
    $aiHelper = new AIHelper();

    // Translate content
    $translations = $aiHelper->translateContent($content, $target_language);

    // Return translations
    echo json_encode([
        'status' => 'success',
        'translations' => $translations,
        'target_language' => $target_language
    ]);

} catch (Exception $e) {
    error_log("Translation Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Translation failed: ' . $e->getMessage()
    ]);
}
?>

