<?php
/**
 * Generate AI-powered abandoned cart message using OpenAI API
 * Simple implementation without database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$cartItems = $input['items'] ?? [];

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'No cart items provided']);
    exit;
}

// OpenAI API Configuration
require_once(__DIR__ . '/../ai_config_FOR_SERVER.php');
$openai_api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : getenv('OPENAI_API_KEY');
$openai_model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-3.5-turbo';
$openai_api_url = 'https://api.openai.com/v1/chat/completions';

// Prepare cart items for AI prompt
$itemList = [];
foreach ($cartItems as $item) {
    $itemList[] = $item['quantity'] . 'x ' . $item['name'] . ' (GH₵' . $item['price'] . ')';
}
$itemsText = implode(', ', $itemList);

// Create AI prompt
$prompt = "Generate a friendly, persuasive, and concise message (max 80 characters) to remind a customer about their abandoned shopping cart. 
Cart items: {$itemsText}
Make it warm, encouraging, and create urgency without being pushy. Include an emoji if appropriate.";

try {
    // Call OpenAI API
    $ch = curl_init($openai_api_url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_api_key
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $openai_model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a friendly e-commerce assistant. Generate short, persuasive messages for abandoned carts.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.7
        ]),
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('CURL Error: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('OpenAI API Error: HTTP ' . $httpCode);
    }
    
    $aiResponse = json_decode($response, true);
    
    if (isset($aiResponse['choices'][0]['message']['content'])) {
        $message = trim($aiResponse['choices'][0]['message']['content']);
        
        // Remove quotes if AI wrapped the message
        $message = trim($message, '"\'');
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        throw new Exception('Invalid response from OpenAI API');
    }
    
} catch (Exception $e) {
    // Log error but return fallback message
    error_log('OpenAI API Error: ' . $e->getMessage());
    
    // Generate fallback message
    $itemNames = array_map(function($item) {
        return $item['name'];
    }, $cartItems);
    
    $itemCount = count($cartItems);
    if ($itemCount === 1) {
        $fallbackMessage = "Don't forget! Your " . $itemNames[0] . " is still waiting. Complete your purchase now! 🛒";
    } else {
        $fallbackMessage = "You have {$itemCount} items in your cart. Don't miss out - complete your purchase! 🛒";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $fallbackMessage
    ]);
}
?>

