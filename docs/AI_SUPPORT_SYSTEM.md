# AI Support Message Management System

## Overview

The AI Support System automatically manages and responds to customer support messages in your chat box. It uses OpenAI's API to understand customer queries and provide instant, helpful responses.

## How It Works

### 1. **Automatic Message Processing**
   - When a customer sends a message through the support chat, the system:
     - Saves the message to the database
     - Analyzes the message topic and content
     - Decides if AI can handle it or if human intervention is needed
     - Generates an appropriate response if suitable

### 2. **AI Response Generation**
   - The AI uses context about Gadget Garage to provide accurate answers:
     - Product information (refurbished devices)
     - Services (Repair Studio, Device Drop, Tech Revival)
     - Policies (returns, refunds, guarantees)
     - Contact information (Tech Revival: 055-138-7578)

### 3. **Smart Escalation**
   - **Auto-handled topics:**
     - Order status & refunds
     - Device Drop questions
     - Repair service questions
     - General inquiries
     - Billing questions
   
   - **Human-required topics** (automatically escalated):
     - Device quality issues (need verification)
     - Tech Revival coordination (needs scheduling)
     - Account issues (may need verification)

### 4. **Response Confidence**
   - The AI rates its confidence in each response:
     - **High**: AI is confident, response is sent automatically
     - **Medium**: AI responds but flags for human review
     - **Low**: Escalates to human immediately

## Features

### ✅ **Instant Responses**
   - Customers get immediate answers to common questions
   - 24/7 availability
   - No waiting time for simple queries

### ✅ **Intelligent Categorization**
   - Automatically categorizes messages by topic
   - Sets appropriate priority levels
   - Routes complex issues to human agents

### ✅ **Professional Quality**
   - Responses are professional, friendly, and empathetic
   - Uses your brand voice and tone
   - Provides actionable solutions

### ✅ **Seamless Integration**
   - Works with existing support message system
   - Saves all AI responses in database
   - Admins can review and override AI responses

## Technical Implementation

### Files Involved

1. **`helpers/ai_support_helper.php`**
   - Main AI support handler class
   - Analyzes messages and generates responses
   - Manages escalation logic

2. **`actions/send_support_message.php`**
   - Integrates AI processing into message submission
   - Automatically calls AI helper for new messages
   - Returns AI response to frontend

3. **`js/chatbot.js`**
   - Displays AI responses to customers
   - Shows instant response when available

### Database Structure

- **`support_messages`** table stores all messages
- **`support_responses`** table stores AI and human responses
- Fields include:
  - `is_ai_response`: Boolean flag for AI responses
  - `ai_confidence`: Confidence level of AI response
  - `status`: Message status (new, in_progress, resolved)
  - `priority`: Urgency level (urgent, high, normal, low)

## Configuration

### Enable/Disable Auto-Responses

Edit `helpers/ai_support_helper.php`:

```php
// Topics AI can handle automatically
private $autoHandleTopics = [
    'order',
    'device_drop',
    'repair',
    'general',
    'billing'
];

// Topics that require human intervention
private $humanRequiredTopics = [
    'device_quality',
    'tech_revival',
    'account'
];
```

### Customize AI Responses

Edit the prompt in `generateSupportResponse()` method to change:
- Response tone and style
- Information provided
- Escalation criteria

## Admin Review

All AI responses are saved to the database and can be reviewed in:
- **Admin Panel**: `admin/support_messages.php`
- Review AI responses
- Override if needed
- Escalate to human agents

## Benefits

1. **Reduced Response Time**: Instant answers vs. hours/days wait
2. **24/7 Availability**: Customers get help anytime
3. **Cost Savings**: Fewer support tickets requiring human intervention
4. **Consistency**: Same quality responses every time
5. **Scalability**: Handle unlimited messages simultaneously

## Monitoring

Check AI performance:
- Review auto-responded messages in admin panel
- Monitor escalation rates
- Track customer satisfaction
- Adjust AI prompts based on feedback

## Future Enhancements

- Learn from admin responses to improve
- Multi-language support
- Sentiment analysis
- Integration with order/repair systems for real-time data
- Chat history and context awareness

