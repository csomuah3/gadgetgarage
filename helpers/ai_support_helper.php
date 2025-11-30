<?php
/**
 * AI Support Helper
 * Automatically manages and responds to support messages using AI
 */

require_once(__DIR__ . '/ai_helper.php');
require_once(__DIR__ . '/../settings/db_class.php');

class AISupportHelper {
    private $aiHelper;
    private $db;
    
    // Topics the AI can handle automatically
    private $autoHandleTopics = [
        'order',
        'device_drop',
        'repair',
        'general',
        'billing'
    ];
    
    // Topics that require human intervention
    private $humanRequiredTopics = [
        'device_quality', // Device quality issues need human review
        'tech_revival',   // Tech revival issues need coordination
        'account'         // Account issues may need verification
    ];
    
    public function __construct() {
        $this->aiHelper = new AIHelper();
        $this->db = new db_connection();
    }
    
    /**
     * Analyze a support message and generate an appropriate AI response
     * 
     * @param array $messageData - Support message data
     * @return array - Response with AI-generated answer and metadata
     */
    public function analyzeAndRespond($messageData) {
        $subject = $messageData['subject'] ?? 'general';
        $customerMessage = $messageData['message'] ?? '';
        $customerName = $messageData['customer_name'] ?? 'Customer';
        
        // Check if this topic should be handled by AI or escalated to humans
        if (in_array($subject, $this->humanRequiredTopics)) {
            return [
                'should_respond' => false,
                'escalate_to_human' => true,
                'reason' => 'This topic requires human intervention',
                'priority' => 'high'
            ];
        }
        
        // Generate AI response
        try {
            $aiResponse = $this->generateSupportResponse($subject, $customerMessage, $customerName);
            
            return [
                'should_respond' => true,
                'response_text' => $aiResponse['response'],
                'confidence' => $aiResponse['confidence'],
                'escalate_to_human' => $aiResponse['escalate'] ?? false,
                'priority' => $aiResponse['priority'] ?? 'normal',
                'suggested_status' => $aiResponse['escalate'] ? 'in_progress' : 'resolved'
            ];
        } catch (Exception $e) {
            error_log("AI Support Error: " . $e->getMessage());
            return [
                'should_respond' => false,
                'escalate_to_human' => true,
                'reason' => 'AI processing error',
                'priority' => 'normal'
            ];
        }
    }
    
    /**
     * Generate an AI-powered support response
     * 
     * @param string $subject - Message subject/topic
     * @param string $customerMessage - Customer's message
     * @param string $customerName - Customer's name
     * @return array - Response data
     */
    private function generateSupportResponse($subject, $customerMessage, $customerName) {
        $prompt = "You are a professional customer support agent for Gadget Garage, a tech store in Accra, Ghana.\n\n";
        $prompt .= "Customer Name: {$customerName}\n";
        $prompt .= "Topic: {$subject}\n";
        $prompt .= "Customer Message: {$customerMessage}\n\n";
        
        $prompt .= "Context about Gadget Garage:\n";
        $prompt .= "- We sell refurbished smartphones, tablets, laptops, cameras, and video equipment\n";
        $prompt .= "- We offer repair services (screen repairs, battery replacements, etc.)\n";
        $prompt .= "- We have a Device Drop service for trading in old devices (minimum GH₵3000)\n";
        $prompt .= "- We have Tech Revival service - call 055-138-7578 for device recycling\n";
        $prompt .= "- All devices come with quality guarantees\n";
        $prompt .= "- We process refunds and returns for defective products\n\n";
        
        $prompt .= "Guidelines for your response:\n";
        $prompt .= "- Be professional, friendly, and empathetic\n";
        $prompt .= "- Respond in clear, simple language\n";
        $prompt .= "- Provide specific, actionable solutions\n";
        $prompt .= "- If you cannot resolve the issue, politely suggest escalating to a human agent\n";
        $prompt .= "- For order issues, offer to check order status or process refunds\n";
        $prompt .= "- For device quality issues, offer replacement or return options\n";
        $prompt .= "- For repair questions, provide information about our Repair Studio services\n";
        $prompt .= "- For Device Drop questions, explain our trade-in service and minimum quote of GH₵3000\n";
        $prompt .= "- Keep responses concise (2-4 sentences maximum)\n\n";
        
        $prompt .= "Respond ONLY in valid JSON format with this exact structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"response\": \"Your helpful response to the customer\",\n";
        $prompt .= "  \"confidence\": \"high\" or \"medium\" or \"low\",\n";
        $prompt .= "  \"escalate\": true or false (true if this needs human review),\n";
        $prompt .= "  \"priority\": \"urgent\" or \"high\" or \"normal\" or \"low\"\n";
        $prompt .= "}\n\n";
        
        $prompt .= "IMPORTANT: Only escalate if the issue:\n";
        $prompt .= "- Requires account verification or sensitive information\n";
        $prompt .= "- Involves refunds over GH₵5000\n";
        $prompt .= "- Has legal implications\n";
        $prompt .= "- Is very complex and requires detailed investigation\n";
        $prompt .= "- Customer explicitly requests to speak with a human\n";
        
        try {
            $response = $this->aiHelper->callOpenAI($prompt, 500);
            $responseData = json_decode($response, true);
            
            // Validate response structure
            if (!$responseData || !isset($responseData['response'])) {
                throw new Exception("Invalid AI response format");
            }
            
            // Default values if not provided
            $responseData['confidence'] = $responseData['confidence'] ?? 'medium';
            $responseData['escalate'] = $responseData['escalate'] ?? false;
            $responseData['priority'] = $responseData['priority'] ?? 'normal';
            
            return $responseData;
        } catch (Exception $e) {
            error_log("AI Response Generation Error: " . $e->getMessage());
            // Return a safe default response
            return [
                'response' => "Thank you for contacting Gadget Garage. We have received your message and one of our support team members will review it shortly and get back to you. For urgent matters, please call us at 055-138-7578.",
                'confidence' => 'low',
                'escalate' => true,
                'priority' => 'normal'
            ];
        }
    }
    
    /**
     * Save AI response to the database
     * 
     * @param int $messageId - Support message ID
     * @param array $aiResponse - AI response data
     * @param bool $isAutoSent - Whether response was automatically sent
     * @return bool - Success status
     */
    public function saveAIResponse($messageId, $aiResponse, $isAutoSent = true) {
        if (!$this->db->db_connect()) {
            return false;
        }
        
        $messageId = intval($messageId);
        $responseText = mysqli_real_escape_string($this->db->db_conn(), $aiResponse['response_text']);
        $confidence = mysqli_real_escape_string($this->db->db_conn(), $aiResponse['confidence'] ?? 'medium');
        $isAutoSent = $isAutoSent ? 1 : 0;
        
        // Check if support_responses table exists
        $checkTable = "SHOW TABLES LIKE 'support_responses'";
        $tableExists = $this->db->db_fetch_one($checkTable);
        
        if ($tableExists) {
            // Insert into support_responses table
            $sql = "INSERT INTO support_responses (message_id, response_text, admin_id, response_date, is_ai_response, ai_confidence)
                    VALUES ($messageId, '$responseText', NULL, NOW(), $isAutoSent, '$confidence')";
        } else {
            // Update support_messages table directly
            $sql = "UPDATE support_messages 
                    SET admin_response = '$responseText',
                        status = 'resolved',
                        response_date = NOW(),
                        updated_at = NOW()
                    WHERE message_id = $messageId";
        }
        
        $result = $this->db->db_write_query($sql);
        
        if ($result && $isAutoSent) {
            // Update message status to show it was auto-resolved
            $updateStatus = "UPDATE support_messages 
                           SET status = '" . ($aiResponse['escalate_to_human'] ? 'in_progress' : 'resolved') . "',
                               priority = '" . mysqli_real_escape_string($this->db->db_conn(), $aiResponse['priority'] ?? 'normal') . "',
                               updated_at = NOW()
                           WHERE message_id = $messageId";
            $this->db->db_write_query($updateStatus);
        }
        
        return $result;
    }
    
    /**
     * Check if a message should be auto-responded to
     * 
     * @param array $messageData - Support message data
     * @return bool - Whether to auto-respond
     */
    public function shouldAutoRespond($messageData) {
        $subject = $messageData['subject'] ?? 'general';
        
        // Don't auto-respond to topics that require humans
        if (in_array($subject, $this->humanRequiredTopics)) {
            return false;
        }
        
        // Auto-respond to common queries
        if (in_array($subject, $this->autoHandleTopics)) {
            return true;
        }
        
        // Default: don't auto-respond (let humans handle it)
        return false;
    }
    
    /**
     * Process a support message with AI
     * This is the main function to call when a new message is received
     * 
     * @param int $messageId - Support message ID
     * @return array - Processing result
     */
    public function processSupportMessage($messageId) {
        if (!$this->db->db_connect()) {
            return [
                'success' => false,
                'message' => 'Database connection failed'
            ];
        }
        
        // Get message data
        $messageId = intval($messageId);
        $sql = "SELECT * FROM support_messages WHERE message_id = $messageId";
        $messageData = $this->db->db_fetch_one($sql);
        
        if (!$messageData) {
            return [
                'success' => false,
                'message' => 'Message not found'
            ];
        }
        
        // Check if already responded
        if (!empty($messageData['admin_response']) || $messageData['status'] === 'resolved') {
            return [
                'success' => false,
                'message' => 'Message already responded to'
            ];
        }
        
        // Analyze and generate response
        $analysis = $this->analyzeAndRespond($messageData);
        
        if (!$analysis['should_respond']) {
            return [
                'success' => true,
                'auto_responded' => false,
                'escalated' => $analysis['escalate_to_human'] ?? false,
                'reason' => $analysis['reason'] ?? 'Requires human review'
            ];
        }
        
        // Save AI response
        $saved = $this->saveAIResponse($messageId, $analysis, true);
        
        if ($saved) {
            return [
                'success' => true,
                'auto_responded' => true,
                'response_text' => $analysis['response_text'],
                'escalated' => $analysis['escalate_to_human'] ?? false,
                'priority' => $analysis['priority'] ?? 'normal'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to save AI response'
            ];
        }
    }
}
?>
