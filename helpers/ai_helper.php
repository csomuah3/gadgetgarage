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
    
    /**
     * Generate product description using AI
     */
    public function generateProductDescription($product_title, $brand_name, $category_name, $price, $existing_description = '') {
        $prompt = "Generate a compelling, SEO-friendly product description for an e-commerce website.\n\n";
        $prompt .= "Product Details:\n";
        $prompt .= "- Name: {$product_title}\n";
        $prompt .= "- Brand: {$brand_name}\n";
        $prompt .= "- Category: {$category_name}\n";
        $prompt .= "- Price: GH₵" . number_format($price, 2) . "\n";
        
        if (!empty($existing_description)) {
            $prompt .= "- Current Description: {$existing_description}\n\n";
            $prompt .= "Enhance and expand this description. Make it more engaging, detailed, and SEO-optimized while keeping the original information.\n\n";
        } else {
            $prompt .= "\n";
            $prompt .= "Create a professional product description that:\n";
            $prompt .= "1. Highlights key features and benefits\n";
            $prompt .= "2. Appeals to potential customers\n";
            $prompt .= "3. Is SEO-friendly with relevant keywords\n";
            $prompt .= "4. Is 150-250 words long\n";
            $prompt .= "5. Uses persuasive but honest language\n";
            $prompt .= "6. Mentions who the product is best for\n\n";
        }
        
        $prompt .= "Write in a professional, customer-friendly tone. Focus on value and benefits. Do not include HTML tags or markdown formatting - just plain text.";
        
        try {
            $description = $this->callOpenAI($prompt, 400);
            return trim($description);
        } catch (Exception $e) {
            error_log("AI Description Generation Error: " . $e->getMessage());
            throw new Exception("Failed to generate description: " . $e->getMessage());
        }
    }
    
    /**
     * Analyze a device repair issue and suggest likely diagnosis
     * Returns a JSON string with a fixed structure
     */
    public function analyzeRepairIssue($device_type, $brand, $model, $issue_description, $base_cost = null) {
        $device_type = trim($device_type ?: 'Device');
        $brand = trim($brand ?: 'Unknown brand');
        $model = trim($model ?: '');
        $issue_description = trim($issue_description ?: '');
        
        if (empty($issue_description)) {
            throw new Exception("Issue description is required for analysis.");
        }
        
        $prompt = "You are a senior repair technician at a repair shop called Gadget Garage in Accra, Ghana.\n";
        $prompt .= "A customer has scheduled a remote repair consultation.\n\n";
        $prompt .= "Device details:\n";
        $prompt .= "- Type: {$device_type}\n";
        $prompt .= "- Brand: {$brand}\n";
        if (!empty($model)) {
            $prompt .= "- Model: {$model}\n";
        }
        $prompt .= "\nCustomer issue description (in their own words):\n\"{$issue_description}\"\n\n";
        
        $prompt .= "The shop offers these main repair categories:\n";
        $prompt .= "- Screen replacement (for cracked, unresponsive, or display issues)\n";
        $prompt .= "- Battery replacement (for fast drain, random shutdowns, overheating, swollen battery)\n";
        $prompt .= "- Charging port / power issues (for not charging, loose port, cable must be bent)\n";
        $prompt .= "- Board-level / advanced diagnosis (for water damage, no power, boot loop, random behaviour)\n";
        $prompt .= "- Software / OS issues (for slow phone, app crashes, virus, update problems)\n";
        $prompt .= "- Camera or speaker/microphone issues\n\n";
        
        if (!empty($base_cost)) {
            $prompt .= "The base consultation cost for this type of issue is about GH₵" . number_format($base_cost, 0) . ".\n";
        }
        
        $prompt .= "\nIMPORTANT:\n";
        $prompt .= "- You are only giving an ESTIMATE, not a final quote.\n";
        $prompt .= "- Use realistic prices for Accra, Ghana in Ghana cedis (GH₵).\n";
        $prompt .= "- Always assume the device still needs physical diagnosis before final pricing.\n\n";
        
        $prompt .= "Respond ONLY in valid JSON with this exact structure (no explanations, no extra text):\n";
        $prompt .= "{\n";
        $prompt .= "  \"likely_issue\": \"short text explaining what is probably wrong\",\n";
        $prompt .= "  \"recommended_repair_type\": \"one of: screen replacement, battery replacement, charging port / power issues, board-level / advanced diagnosis, software / OS issues, camera issue, speaker / microphone issue\",\n";
        $prompt .= "  \"estimated_cost_range\": \"a realistic price range in Ghana cedis, e.g. 'GH₵300 - GH₵600'\",\n";
        $prompt .= "  \"estimated_time\": \"how long the repair usually takes, e.g. '1-2 hours', '1-2 days'\",\n";
        $prompt .= "  \"urgency\": \"low, medium, or high based on the risk of further damage or data loss\",\n";
        $prompt .= "  \"notes\": \"1-3 short sentences with friendly advice for the customer and what to expect during diagnosis\"\n";
        $prompt .= "}\n";
        
        try {
            $response = $this->callOpenAI($prompt, 400);
            return trim($response);
        } catch (Exception $e) {
            error_log("AI Repair Analysis Error: " . $e->getMessage());
            throw new Exception("Unable to analyze issue at this time. Please try again.");
        }
    }
    
    /**
     * Get AI-powered product recommendations
     * Analyzes user context and suggests relevant products
     */
    public function getProductRecommendations($all_products, $user_context = []) {
        if (empty($all_products)) {
            return [];
        }
        
        // Build context information
        $context_info = "Customer Context:\n";
        
        if (!empty($user_context['cart_items'])) {
            $context_info .= "- Items in cart: " . implode(', ', array_column($user_context['cart_items'], 'product_title')) . "\n";
        }
        
        if (!empty($user_context['wishlist_items'])) {
            $context_info .= "- Wishlist items: " . implode(', ', array_column($user_context['wishlist_items'], 'product_title')) . "\n";
        }
        
        if (!empty($user_context['viewed_products'])) {
            $context_info .= "- Recently viewed: " . implode(', ', array_slice($user_context['viewed_products'], 0, 5)) . "\n";
        }
        
        if (!empty($user_context['current_product'])) {
            $context_info .= "- Currently viewing: {$user_context['current_product']['product_title']}\n";
        }
        
        if (empty($context_info) || $context_info === "Customer Context:\n") {
            $context_info = "New customer with no browsing history.\n";
        }
        
        // Limit products for analysis (too many would be expensive)
        $products_to_analyze = array_slice($all_products, 0, 50);
        
        $prompt = "You are a product recommendation engine for an e-commerce site called 'Gadget Garage' that sells tech products.\n\n";
        $prompt .= $context_info . "\n";
        $prompt .= "Available Products (showing sample):\n\n";
        
        foreach (array_slice($products_to_analyze, 0, 20) as $index => $product) {
            $prompt .= ($index + 1) . ". {$product['product_title']} - GH₵" . number_format($product['product_price'], 2) . "\n";
            $prompt .= "   Brand: {$product['brand_name']}, Category: {$product['cat_name']}\n";
            $prompt .= "   " . substr($product['product_desc'], 0, 100) . "...\n\n";
        }
        
        $prompt .= "Based on the customer's context, recommend 4 products that would be most relevant and appealing.\n";
        $prompt .= "Return ONLY a comma-separated list of product titles (exactly as they appear above), nothing else.\n";
        $prompt .= "Example format: iPhone 15 Pro, Samsung Galaxy S24, MacBook Pro 14\"\n";
        $prompt .= "Focus on products that complement what they're interested in or similar quality/price range.";
        
        try {
            $response = $this->callOpenAI($prompt, 200);
            $recommended_titles = array_map('trim', explode(',', $response));
            
            // Find matching products
            $recommended_products = [];
            foreach ($recommended_titles as $title) {
                foreach ($all_products as $product) {
                    if (stripos($product['product_title'], $title) !== false || stripos($title, $product['product_title']) !== false) {
                        if (!in_array($product['product_id'], array_column($recommended_products, 'product_id'))) {
                            $recommended_products[] = $product;
                            if (count($recommended_products) >= 4) break 2;
                        }
                    }
                }
            }
            
            // If AI didn't return enough, fill with random products
            if (count($recommended_products) < 4) {
                $remaining = 4 - count($recommended_products);
                $available = array_filter($all_products, function($p) use ($recommended_products) {
                    return !in_array($p['product_id'], array_column($recommended_products, 'product_id'));
                });
                shuffle($available);
                $recommended_products = array_merge($recommended_products, array_slice($available, 0, $remaining));
            }
            
            return array_slice($recommended_products, 0, 4);
        } catch (Exception $e) {
            error_log("AI Recommendation Error: " . $e->getMessage());
            // Fallback: return random products
            shuffle($all_products);
            return array_slice($all_products, 0, 4);
        }
    }
}
?>

