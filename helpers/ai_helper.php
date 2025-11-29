<?php
require_once(__DIR__ . '/../settings/ai_config.php');

class AIHelper {
    
    private $api_key;
    private $model;
    
    public function __construct() {
        $this->api_key = OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
    }
    
    /**
     * Send request to OpenAI API
     */
    public function callOpenAI($prompt, $max_tokens = null) {
        if (empty($this->api_key)) {
            throw new Exception("OpenAI API key not configured");
        }
        
        $max_tokens = $max_tokens ?? OPENAI_MAX_TOKENS;
        
        // Prepare the request data
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful product comparison expert for an e-commerce site called Gadget Garage. Provide clear, concise, and helpful comparisons for tech products and gadgets. Use simple language that customers can understand.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => OPENAI_TEMPERATURE
        ];
        
        // Initialize cURL
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => OPENAI_TIMEOUT
        ]);
        
        // Execute request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: " . $error);
        }
        
        curl_close($ch);
        
        // Parse response
        $result = json_decode($response, true);
        
        if ($http_code !== 200) {
            $error_message = $result['error']['message'] ?? 'Unknown error';
            throw new Exception("OpenAI API error (HTTP $http_code): " . $error_message);
        }
        
        // Extract the AI's response
        return $result['choices'][0]['message']['content'] ?? '';
    }
    
    /**
     * Generate product comparison analysis
     */
    public function compareProducts($products) {
        if (count($products) < 2) {
            return "Please select at least 2 products to compare.";
        }
        
        // Build the prompt for AI
        $prompt = "I'm comparing these tech products for a customer:\n\n";
        
        foreach ($products as $index => $product) {
            $num = $index + 1;
            $prompt .= "**Product {$num}:**\n";
            $prompt .= "- Name: {$product['product_title']}\n";
            $prompt .= "- Price: GH₵" . number_format($product['product_price'], 2) . "\n";
            $prompt .= "- Brand: {$product['brand_name']}\n";
            $prompt .= "- Category: {$product['cat_name']}\n";
            $prompt .= "- Description: " . substr($product['product_desc'], 0, 200) . "...\n\n";
        }
        
        $prompt .= "Please provide a side-by-side comparison in an HTML table format.\n\n";
        $prompt .= "Create an HTML table with these comparison categories as rows:\n";
        $prompt .= "- Key Features\n";
        $prompt .= "- Best For\n";
        $prompt .= "- Pros\n";
        $prompt .= "- Cons\n";
        $prompt .= "- Value for Money\n";
        $prompt .= "- Recommendation\n\n";
        $prompt .= "Each product should have its own column. Use <table class='ai-comparison-table'> and make it customer-friendly.\n";
        $prompt .= "Keep descriptions concise (2-3 sentences per cell). Focus on practical differences that matter to buyers.";
        
        try {
            $analysis = $this->callOpenAI($prompt, 700);
            return $analysis;
        } catch (Exception $e) {
            error_log("AI Comparison Error: " . $e->getMessage());
            return "⚠️ Unable to generate AI analysis at this time. Please try again later.\n\n**Error:** " . $e->getMessage();
        }
    }
    
    /**
     * Get personalized recommendation based on user preferences
     */
    public function getPersonalizedRecommendation($products, $user_preferences) {
        if (empty($user_preferences)) {
            return $this->compareProducts($products);
        }
        
        $prompt = "**Customer's needs:** {$user_preferences}\n\n";
        $prompt .= "**Available products:**\n\n";
        
        foreach ($products as $index => $product) {
            $num = $index + 1;
            $prompt .= "{$num}. **{$product['product_title']}** - GH₵" . number_format($product['product_price'], 2) . "\n";
            $prompt .= "   Brand: {$product['brand_name']}\n";
            $prompt .= "   " . substr($product['product_desc'], 0, 150) . "...\n\n";
        }
        
        $prompt .= "Based on the customer's specific needs, which product would you recommend and why? ";
        $prompt .= "Be specific, explain the benefits, and mention any trade-offs. Keep it under 200 words.";
        
        try {
            return $this->callOpenAI($prompt, 400);
        } catch (Exception $e) {
            error_log("AI Recommendation Error: " . $e->getMessage());
            return "Unable to generate personalized recommendation at this time.";
        }
    }
    
    /**
     * Generate a quick summary for a single product
     */
    public function summarizeProduct($product) {
        $prompt = "Summarize this product in 2-3 sentences for a customer:\n\n";
        $prompt .= "**{$product['product_title']}**\n";
        $prompt .= "Price: GH₵" . number_format($product['product_price'], 2) . "\n";
        $prompt .= "Brand: {$product['brand_name']}\n";
        $prompt .= "Description: {$product['product_desc']}\n\n";
        $prompt .= "Focus on key features and who it's best for. Be concise and helpful.";
        
        try {
            return $this->callOpenAI($prompt, 150);
        } catch (Exception $e) {
            error_log("AI Summary Error: " . $e->getMessage());
            return "A quality product from {$product['brand_name']}.";
        }
    }
}
?>

