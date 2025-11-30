<?php
require_once(__DIR__ . '/../settings/ai_config.php');

class AIHelper
{

    private $api_key;
    private $model;

    public function __construct()
    {
        $this->api_key = OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
    }

    /**
     * Send request to OpenAI API
     */
    public function callOpenAI($prompt, $max_tokens = null)
    {
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
    public function compareProducts($products)
    {
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
    public function getPersonalizedRecommendation($products, $user_preferences)
    {
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
    public function summarizeProduct($product)
    {
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
    public function generateProductDescription($product_title, $brand_name, $category_name, $price, $existing_description = '')
    {
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
    public function analyzeRepairIssue($device_type, $brand, $model, $issue_description, $base_cost = null)
    {
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
    public function getProductRecommendations($all_products, $user_context = [])
    {
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
                $available = array_filter($all_products, function ($p) use ($recommended_products) {
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

    /**
     * Assess device value for trade-in/drop-off using AI
     * Returns detailed valuation with cash and store credit options
     */
    public function assessDeviceValue($device_type, $brand, $model, $condition, $issues = '')
    {
        $device_type = trim($device_type ?: 'Device');
        $brand = trim($brand ?: 'Unknown brand');
        $model = trim($model ?: '');
        $condition = trim($condition ?: 'fair');
        $issues = trim($issues ?: 'None mentioned');

        if (empty($device_type) || empty($brand)) {
            throw new Exception("Device type and brand are required for valuation.");
        }

        $prompt = "You are a device valuation expert at Gadget Garage, a tech store in Accra, Ghana.\n";
        $prompt .= "A customer wants to trade in their device for cash or store credit.\n\n";
        $prompt .= "Device Information:\n";
        $prompt .= "- Type: {$device_type}\n";
        $prompt .= "- Brand: {$brand}\n";
        if (!empty($model)) {
            $prompt .= "- Model: {$model}\n";
        }
        $prompt .= "- Condition: {$condition}\n";
        $prompt .= "- Issues/Description: {$issues}\n\n";

        $prompt .= "Valuation Guidelines:\n";
        $prompt .= "- Use realistic market prices for Ghana in Ghana cedis (GH₵)\n";
        $prompt .= "- MINIMUM QUOTE: Never quote less than GH₵3000 for any device. All valuations must be at least GH₵3000.\n";
        $prompt .= "- Consider device age, condition, market demand, and local resale value\n";
        $prompt .= "- Store credit offers 10% bonus over cash value\n";
        $prompt .= "- Be conservative but fair in valuation, ensuring minimum is met\n";
        $prompt .= "- Consider depreciation, wear and tear, and local market conditions\n\n";

        $prompt .= "CRITICAL VALUATION FACTORS (These MUST significantly influence the price):\n\n";
        $prompt .= "1. BATTERY HEALTH:\n";
        $prompt .= "   - Battery health above 80%: INCREASE the valuation by 15-25%\n";
        $prompt .= "   - Battery health 60-80%: Standard valuation (no adjustment)\n";
        $prompt .= "   - Battery health below 60%: DECREASE the valuation by 10-20%\n";
        $prompt .= "   - Extract battery health percentage from the description if mentioned (e.g., '85% battery', 'battery at 90%')\n\n";

        $prompt .= "2. YEARS OF USAGE:\n";
        $prompt .= "   - Less than 1 year old: INCREASE valuation by 20-30% (device is very new)\n";
        $prompt .= "   - 1-2 years old: INCREASE valuation by 10-15% (relatively new)\n";
        $prompt .= "   - 2-3 years old: Standard valuation (no adjustment)\n";
        $prompt .= "   - 3-4 years old: DECREASE valuation by 10-15% (older device)\n";
        $prompt .= "   - More than 4 years old: DECREASE valuation by 20-30% (significantly aged)\n";
        $prompt .= "   - Extract years of usage from the description if mentioned (e.g., 'used for 1 year', '2 years old', 'bought in 2023')\n\n";

        $prompt .= "3. REPAIR HISTORY:\n";
        $prompt .= "   - If device has been repaired before: DECREASE valuation by 15-25%\n";
        $prompt .= "   - Multiple repairs: DECREASE valuation by 25-35%\n";
        $prompt .= "   - No repair history mentioned: No adjustment (assume no repairs)\n";
        $prompt .= "   - Look for keywords in description: 'repaired', 'fixed', 'screen replaced', 'battery replaced', 'serviced', 'maintenance'\n\n";

        $prompt .= "Condition Multipliers (base multipliers, then apply the factors above):\n";
        $prompt .= "- Excellent (like new): 85-90% of current retail\n";
        $prompt .= "- Good (minor wear): 70-80% of current retail\n";
        $prompt .= "- Fair (visible wear, some issues): 50-65% of current retail\n";
        $prompt .= "- Poor (major issues, significant damage): 20-40% of current retail\n\n";

        $prompt .= "VALUATION PROCESS:\n";
        $prompt .= "1. Start with base valuation based on device type, brand, model, and condition\n";
        $prompt .= "2. Extract battery health, years of usage, and repair history from the description\n";
        $prompt .= "3. Apply adjustments based on the three critical factors above\n";
        $prompt .= "4. Ensure final value meets the GH₵3000 minimum\n";
        $prompt .= "5. In your value_reasoning, explicitly mention how battery health, years of usage, and repair history affected the price\n\n";

        $prompt .= "Respond ONLY in valid JSON with this exact structure (no explanations, no extra text):\n";
        $prompt .= "{\n";
        $prompt .= "  \"cash_value\": 250,\n";
        $prompt .= "  \"credit_value\": 275,\n";
        $prompt .= "  \"original_retail_estimate\": 800,\n";
        $prompt .= "  \"condition_grade\": \"Good\",\n";
        $prompt .= "  \"value_reasoning\": \"Brief explanation of how the value was determined\",\n";
        $prompt .= "  \"market_comparison\": \"How this compares to current market prices\",\n";
        $prompt .= "  \"recommendations\": \"Any advice for the customer about the offer\"\n";
        $prompt .= "}\n";

        try {
            $response = $this->callOpenAI($prompt, 400);
            return trim($response);
        } catch (Exception $e) {
            error_log("AI Device Valuation Error: " . $e->getMessage());
            throw new Exception("Unable to assess device value at this time. Please try again.");
        }
    }

    /**
     * Translate content to target language using OpenAI
     * @param array $content Array of text strings to translate
     * @param string $target_language Target language code (en, es, fr, de)
     * @return array Translated content in same order as input
     */
    public function translateContent($content, $target_language)
    {
        if (empty($content) || !is_array($content)) {
            return [];
        }

        // Language names mapping
        $language_names = [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German'
        ];

        $target_lang_name = $language_names[$target_language] ?? 'English';

        // Build prompt for batch translation
        $prompt = "You are a professional translator. Translate the following text content from English to {$target_lang_name}.\n\n";
        $prompt .= "IMPORTANT RULES:\n";
        $prompt .= "1. Maintain the exact same format and structure\n";
        $prompt .= "2. Keep HTML tags, placeholders, and special characters unchanged\n";
        $prompt .= "3. Preserve brand names (Gadget Garage, etc.) as-is\n";
        $prompt .= "4. Keep currency symbols and numbers unchanged\n";
        $prompt .= "5. Return ONLY a JSON array with translations in the same order\n";
        $prompt .= "6. Each translation should be a string in the array\n";
        $prompt .= "7. If text is already in the target language, return it as-is\n\n";
        $prompt .= "Content to translate (as JSON array):\n";
        $prompt .= json_encode($content, JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "Return ONLY the JSON array of translations, nothing else. No explanations, no markdown, just the array.";

        try {
            $response = $this->callOpenAI($prompt, 2000); // Higher token limit for batch translation

            // Clean response - remove markdown code blocks if present
            $response = trim($response);
            $response = preg_replace('/^```json\s*/', '', $response);
            $response = preg_replace('/^```\s*/', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
            $response = trim($response);

            // Parse JSON response
            $translations = json_decode($response, true);

            if (!is_array($translations) || count($translations) !== count($content)) {
                error_log("Translation count mismatch. Expected: " . count($content) . ", Got: " . (is_array($translations) ? count($translations) : 0));
                // Fallback: return original content
                return $content;
            }

            return $translations;
        } catch (Exception $e) {
            error_log("AI Translation Error: " . $e->getMessage());
            // Fallback: return original content
            return $content;
        }
    }
}
