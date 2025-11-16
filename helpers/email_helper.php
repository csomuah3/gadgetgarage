<?php
/**
 * Email Helper Functions
 * Handles email notifications for support messages and other notifications
 */

/**
 * Send email notification to admin when new support message is received
 */
function send_support_notification_email($message_details) {
    $admin_email = "support@gadgetgarage.com"; // Change this to your actual admin email
    $subject = "[Gadget Garage Support] New Customer Message - " . ucfirst(str_replace('_', ' ', $message_details['subject']));

    $priority_labels = [
        'urgent' => '🔴 URGENT',
        'high' => '🟡 HIGH',
        'normal' => '🔵 NORMAL',
        'low' => '⚪ LOW'
    ];

    $subject_labels = [
        'order' => 'Order Status & Refunds',
        'device_quality' => 'Refurbished Device Issues',
        'repair' => 'Repair Service Questions',
        'device_drop' => 'Device Drop & Trade-ins',
        'tech_revival' => 'Tech Revival Service',
        'billing' => 'Billing & Payment',
        'account' => 'Account Issues',
        'general' => 'General Question'
    ];

    $priority = $priority_labels[$message_details['priority']] ?? $message_details['priority'];
    $category = $subject_labels[$message_details['subject']] ?? $message_details['subject'];

    $message_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #008060, #006b4e); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
            .message-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #008060; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 12px 24px; background: #008060; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .info-row { margin: 8px 0; }
            .label { font-weight: bold; color: #008060; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🛠️ New Support Message - Gadget Garage</h1>
                <p>A customer has sent a new support message that requires attention.</p>
            </div>

            <div class='content'>
                <div class='info-row'>
                    <span class='label'>Priority:</span> {$priority}
                </div>
                <div class='info-row'>
                    <span class='label'>Category:</span> {$category}
                </div>
                <div class='info-row'>
                    <span class='label'>Customer:</span> {$message_details['customer_name']}
                </div>
                <div class='info-row'>
                    <span class='label'>Email:</span> {$message_details['customer_email']}
                </div>
                <div class='info-row'>
                    <span class='label'>Submitted:</span> " . date('M j, Y g:i A', strtotime($message_details['created_at'])) . "
                </div>

                <div class='message-box'>
                    <h3>Customer Message:</h3>
                    <p>" . nl2br(htmlspecialchars($message_details['message'])) . "</p>
                </div>

                <div style='text-align: center; margin: 20px 0;'>
                    <a href='" . get_base_url() . "/admin/support_messages.php' class='button'>
                        View in Admin Panel
                    </a>
                </div>

                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>
                    <strong>Quick Response Guidelines:</strong>
                    <ul>
                        <li><strong>Device Quality Issues:</strong> Prioritize replacement/return processing</li>
                        <li><strong>Tech Revival Calls:</strong> Schedule callback within 2 hours</li>
                        <li><strong>Repair Questions:</strong> Provide clear pricing and timeline</li>
                        <li><strong>Order Issues:</strong> Check status and provide tracking info</li>
                    </ul>
                </div>
            </div>

            <div class='footer'>
                <p>This is an automated notification from Gadget Garage Support System</p>
                <p>Log in to the admin panel to respond to this message</p>
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Gadget Garage Support System <noreply@gadgetgarage.com>" . "\r\n";
    $headers .= "Reply-To: {$message_details['customer_email']}" . "\r\n";

    return mail($admin_email, $subject, $message_body, $headers);
}

/**
 * Send confirmation email to customer when their message is received
 */
function send_customer_confirmation_email($message_details) {
    $customer_email = $message_details['customer_email'];
    $customer_name = $message_details['customer_name'];
    $subject = "Message Received - Gadget Garage Support";

    $subject_labels = [
        'order' => 'Order Status & Refunds',
        'device_quality' => 'Refurbished Device Issues',
        'repair' => 'Repair Service Questions',
        'device_drop' => 'Device Drop & Trade-ins',
        'tech_revival' => 'Tech Revival Service (055-138-7578)',
        'billing' => 'Billing & Payment',
        'account' => 'Account Issues',
        'general' => 'General Question'
    ];

    $category = $subject_labels[$message_details['subject']] ?? $message_details['subject'];

    $message_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #008060, #006b4e); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
            .message-summary { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #008060; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .contact-info { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .logo { font-size: 1.5em; font-weight: bold; }
            .garage { background: #fff; color: #008060; padding: 2px 6px; border-radius: 4px; margin-left: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>Gadget<span class='garage'>Garage</span></div>
                <h2>✅ Message Received Successfully!</h2>
                <p>We'll get back to you as soon as we receive your message</p>
            </div>

            <div class='content'>
                <p>Hi <strong>{$customer_name}</strong>,</p>

                <p>Thank you for contacting Gadget Garage support! We have successfully received your message and our team will review it shortly.</p>

                <div class='message-summary'>
                    <h3>Your Message Details:</h3>
                    <p><strong>Category:</strong> {$category}</p>
                    <p><strong>Submitted:</strong> " . date('M j, Y g:i A', strtotime($message_details['created_at'])) . "</p>
                    <p><strong>Reference ID:</strong> #GG" . str_pad($message_details['message_id'], 6, '0', STR_PAD_LEFT) . "</p>
                </div>

                <div class='contact-info'>
                    <h4>🚀 Quick Support Options:</h4>
                    <ul>
                        <li><strong>Tech Revival Hotline:</strong> 055-138-7578 (Available 24/7)</li>
                        <li><strong>Repair Studio:</strong> Visit us in-store for device repairs</li>
                        <li><strong>Device Drop:</strong> Bring old tech for trade-ins & recycling</li>
                    </ul>
                </div>

                <p><strong>Response Time:</strong> We typically respond to all inquiries within 24 hours. For urgent device issues, please call our Tech Revival hotline.</p>

                <p>Thank you for choosing Gadget Garage!</p>

                <p>Best regards,<br>
                <strong>Gadget Garage Support Team</strong><br>
                <em>Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</em></p>
            </div>

            <div class='footer'>
                <p>This is an automated confirmation from Gadget Garage</p>
                <p>Please do not reply to this email - use our support system for fastest response</p>
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Gadget Garage Support <support@gadgetgarage.com>" . "\r\n";
    $headers .= "Reply-To: support@gadgetgarage.com" . "\r\n";

    return mail($customer_email, $subject, $message_body, $headers);
}

/**
 * Get base URL for links in emails
 */
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['REQUEST_URI']);
    return $protocol . $domain . $path;
}

/**
 * Send response notification to customer when admin responds
 */
function send_response_notification_email($message_details) {
    $customer_email = $message_details['customer_email'];
    $customer_name = $message_details['customer_name'];
    $subject = "Response to Your Support Message - Gadget Garage";

    $message_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #008060, #006b4e); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
            .response-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #008060; }
            .original-message { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #6c757d; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .logo { font-size: 1.5em; font-weight: bold; }
            .garage { background: #fff; color: #008060; padding: 2px 6px; border-radius: 4px; margin-left: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>Gadget<span class='garage'>Garage</span></div>
                <h2>📧 We've Responded to Your Message</h2>
                <p>Our support team has provided a response to your inquiry</p>
            </div>

            <div class='content'>
                <p>Hi <strong>{$customer_name}</strong>,</p>

                <p>Our support team has reviewed your message and provided a response below:</p>

                <div class='response-box'>
                    <h3>🛠️ Support Team Response:</h3>
                    <p>" . nl2br(htmlspecialchars($message_details['admin_response'])) . "</p>
                    <small><strong>Responded on:</strong> " . date('M j, Y g:i A', strtotime($message_details['response_date'])) . "</small>
                </div>

                <div class='original-message'>
                    <h4>📝 Your Original Message:</h4>
                    <p>" . nl2br(htmlspecialchars($message_details['message'])) . "</p>
                </div>

                <p>If you need further assistance, please feel free to contact us again or call our Tech Revival hotline at <strong>055-138-7578</strong>.</p>

                <p>Thank you for choosing Gadget Garage!</p>

                <p>Best regards,<br>
                <strong>Gadget Garage Support Team</strong></p>
            </div>

            <div class='footer'>
                <p>Reference ID: #GG" . str_pad($message_details['message_id'], 6, '0', STR_PAD_LEFT) . "</p>
                <p>For fastest support, contact us through our website or call 055-138-7578</p>
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Gadget Garage Support <support@gadgetgarage.com>" . "\r\n";
    $headers .= "Reply-To: support@gadgetgarage.com" . "\r\n";

    return mail($customer_email, $subject, $message_body, $headers);
}
?>