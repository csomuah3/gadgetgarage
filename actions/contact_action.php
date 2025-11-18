<?php
require_once __DIR__ . '/../settings/core.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Sanitize and validate input data
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate required fields
if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit;
}

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email address is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

if (empty($subject)) {
    echo json_encode(['success' => false, 'message' => 'Subject is required']);
    exit;
}

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

// Additional validation
if (strlen($full_name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Full name must be less than 100 characters']);
    exit;
}

if (strlen($message) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Message must be less than 2000 characters']);
    exit;
}

if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
    exit;
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_2025A_chelsea_somuah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if support_messages table exists, if not create it
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS support_messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            subject VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        )
    ";

    $pdo->exec($createTableSQL);

    // Get client IP and user agent for tracking
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Rate limiting - check if user has sent too many messages recently
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as message_count
        FROM support_messages
        WHERE (email = ? OR ip_address = ?)
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$email, $ip_address]);
    $recent_messages = $stmt->fetch()['message_count'];

    if ($recent_messages >= 5) {
        echo json_encode(['success' => false, 'message' => 'Too many messages sent recently. Please wait before sending another message.']);
        exit;
    }

    // Insert the contact message into the database
    $stmt = $pdo->prepare("
        INSERT INTO support_messages (full_name, email, phone, subject, message, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $full_name,
        $email,
        $phone,
        $subject,
        $message,
        $ip_address,
        $user_agent
    ]);

    if ($result) {
        // Get the inserted message ID for reference
        $message_id = $pdo->lastInsertId();

        // Send email notification (optional - requires mail configuration)
        $email_sent = sendNotificationEmail($full_name, $email, $subject, $message, $message_id);

        // Log the successful contact form submission
        error_log("Contact form submission: ID {$message_id}, Email: {$email}, Subject: {$subject}");

        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your message! We will get back to you within 24 hours.',
            'message_id' => $message_id
        ]);
    } else {
        throw new Exception('Failed to save message to database');
    }

} catch (PDOException $e) {
    error_log("Contact form database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your message. Please try again.']);
}

/**
 * Send notification email (optional function)
 * This function can be enhanced with proper SMTP configuration
 */
function sendNotificationEmail($full_name, $email, $subject, $message, $message_id) {
    try {
        // Admin notification email
        $admin_email = "admin@gadgetgarage.gh"; // Change this to actual admin email
        $email_subject = "New Contact Form Submission - {$subject}";

        $email_body = "
            New contact form submission received:

            Message ID: {$message_id}
            Name: {$full_name}
            Email: {$email}
            Subject: {$subject}

            Message:
            {$message}

            ---
            Submitted at: " . date('Y-m-d H:i:s') . "
        ";

        $headers = [
            'From: noreply@gadgetgarage.gh',
            'Reply-To: ' . $email,
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8'
        ];

        // Use PHP's mail function (requires server mail configuration)
        // return mail($admin_email, $email_subject, $email_body, implode("\r\n", $headers));

        // For now, just return true (email functionality can be implemented later)
        return true;

    } catch (Exception $e) {
        error_log("Contact form email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize input to prevent XSS
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate subject options
 */
function isValidSubject($subject) {
    $valid_subjects = [
        'general', 'support', 'sales', 'repair', 'warranty', 'feedback', 'other'
    ];
    return in_array($subject, $valid_subjects);
}
?>