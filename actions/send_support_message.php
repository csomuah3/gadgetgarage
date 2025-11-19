<?php
// Handle AJAX support message submissions from chatbot
require_once __DIR__ . '/../settings/core.php';

header('Content-Type: application/json');
ob_clean(); // Clear any existing output

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get input data - could be from chatbot or contact form
    $customer_name = trim($_POST['name'] ?? $_POST['full_name'] ?? $_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['email'] ?? $_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['phone'] ?? $_POST['customer_phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Chat Support');
    $message = trim($_POST['message'] ?? '');

    // If logged in, get user info from session
    $customer_id = null;
    if (check_login()) {
        $customer_id = $_SESSION['user_id'] ?? null;
        $customer_name = $customer_name ?: ($_SESSION['customer_name'] ?? 'Logged in User');
        $customer_email = $customer_email ?: ($_SESSION['customer_email'] ?? '');
    }

    // Validate required fields
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    if (empty($customer_name)) {
        $customer_name = 'Anonymous User';
    }

    if (empty($customer_email)) {
        $customer_email = 'noemail@guest.com';
    }

    if (empty($customer_phone)) {
        $customer_phone = '';
    }

    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_2025A_chelsea_somuah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get client IP and user agent for tracking
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Rate limiting - check if user has sent too many messages recently
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as message_count
        FROM support_messages
        WHERE (customer_email = ? OR customer_id = ?)
        AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    $stmt->execute([$customer_email, $customer_id]);
    $recent_messages = $stmt->fetch()['message_count'];

    if ($recent_messages >= 3) {
        echo json_encode(['success' => false, 'message' => 'Please wait a moment before sending another message']);
        exit;
    }

    // Insert the support message into the database
    $stmt = $pdo->prepare("
        INSERT INTO support_messages (customer_id, customer_name, customer_email, customer_phone, subject, message, status)
        VALUES (?, ?, ?, ?, ?, ?, 'new')
    ");

    $result = $stmt->execute([
        $customer_id,
        $customer_name,
        $customer_email,
        $customer_phone,
        $subject,
        $message
    ]);

    if ($result) {
        $message_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully! Our support team will respond shortly.',
            'message_id' => $message_id
        ]);
    } else {
        throw new Exception('Failed to save message to database');
    }

} catch (PDOException $e) {
    error_log("Support message database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
} catch (Exception $e) {
    error_log("Support message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending your message. Please try again.']);
}

exit();
?>