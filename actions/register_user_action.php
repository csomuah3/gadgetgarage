<?php
// Start output buffering to catch any stray output
ob_start();

session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../controllers/user_controller.php';

// Check if this is a login request (no name field means login)
$isLogin = !isset($_POST['name']) || empty($_POST['name']);

if ($isLogin) {
    // HANDLE LOGIN REQUEST
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            exit;
        }

        $result = login_customer_ctr($email, $password);

        if ($result['status'] === 'success' && isset($result['user_data'])) {
            // Normalize to ints/strings you actually use
            $uid  = (int)$result['user_data']['customer_id'];
            $role = (int)$result['user_data']['user_role']; // 1=user, 2=admin (per your lab)

            // Harden the session a bit
            session_regenerate_id(true);

            $_SESSION['user_id'] = $uid;
            $_SESSION['role']    = $role;
            $_SESSION['name']    = $result['user_data']['customer_name'];
            $_SESSION['email']   = $result['user_data']['customer_email'];

            // Add lightweight fields back to response for client logic
            $result['role'] = $role;

            // Optional: compute redirect server-side (keeps client super simple)
            $result['redirect'] = ($role === 2) ? '../admin/index.php' : '../index.php';

            // Never return the raw user_data to the client
            unset($result['user_data']);
        }

        ob_clean();
        echo json_encode($result);
        exit;
    } catch (Throwable $e) {
        error_log("Login error: " . $e->getMessage());
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Login failed. Please try again.']);
        exit;
    }
} else {
    // HANDLE REGISTRATION REQUEST (unchanged)
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }

        $name         = trim($_POST['name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $password     = $_POST['password'] ?? '';
        $phone_number = trim($_POST['phone_number'] ?? '');
        $country      = trim($_POST['country'] ?? '');
        $city         = trim($_POST['city'] ?? '');
        $role         = (int)($_POST['role'] ?? 1);

        // Debug: Log the received role value
        error_log("Register action - received role: " . $role . " (type: " . gettype($role) . ")");

        if (empty($name) || empty($email) || empty($password) || empty($phone_number) || empty($country) || empty($city)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            exit;
        }
        if (strlen($password) < 6) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long']);
            exit;
        }
        if (!preg_match('/^[0-9+\-\s()]+$/', $phone_number)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format']);
            exit;
        }
        if (!in_array($role, [1, 2], true)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid role selected']);
            exit;
        }

        error_log("Register action - About to call register_user_ctr with: name=$name, email=$email, phone=$phone_number, country=$country, city=$city, role=$role");

        $res = register_user_ctr($name, $email, $password, $phone_number, $country, $city, $role);
        error_log("Register action - controller returned: " . json_encode($res));

        // Force success response for testing
        if (!$res || !is_array($res)) {
            error_log("Register action - Got unexpected result type: " . gettype($res));
            $res = ['status' => 'error', 'message' => 'Unexpected registration result'];
        }

        if (is_bool($res)) {
            $res = $res
                ? ['status' => 'success', 'message' => 'Registered successfully']
                : ['status' => 'error', 'message' => 'Failed to register'];
        }

        error_log("Register action - final response before SMS: " . json_encode($res));

        // Send welcome SMS if registration was successful
        if ($res['status'] === 'success') {
            try {
                require_once __DIR__ . '/../settings/sms_config.php';

                if (defined('SMS_ENABLED') && SMS_ENABLED) {
                    require_once __DIR__ . '/../helpers/sms_helper.php';

                    // Get the customer ID from the registration result
                    $customer_id = $res['user_id'] ?? null;

                    // If user_id not in result, query database
                    if (!$customer_id) {
                        require_once __DIR__ . '/../settings/db_class.php';
                        $db = new db_connection();
                        if ($db->db_connect()) {
                            $email_escaped = mysqli_real_escape_string($db->db_conn(), $email);
                            $customer_query = "SELECT customer_id FROM customer WHERE customer_email = '$email_escaped' ORDER BY customer_id DESC LIMIT 1";
                            $customer_result = $db->db_fetch_one($customer_query);
                            
                            if ($customer_result) {
                                $customer_id = $customer_result['customer_id'];
                            }
                        }
                    }

                    if ($customer_id) {
                        $sms_sent = send_welcome_registration_sms(
                            $customer_id,
                            $name,
                            $phone_number
                        );

                        if ($sms_sent) {
                            error_log('Welcome SMS sent successfully to new user: ' . $email . ' (ID: ' . $customer_id . ')');
                        } else {
                            error_log('Welcome SMS failed for new user: ' . $email . ' (ID: ' . $customer_id . ')');
                        }
                    } else {
                        error_log('Welcome SMS skipped - could not get customer_id for: ' . $email);
                    }
                }
            } catch (Exception $sms_error) {
                // Don't fail registration if SMS fails
                error_log('Welcome SMS error during registration: ' . $sms_error->getMessage());
            }
        }

        error_log("Register action - FINAL RESPONSE TO CLIENT: " . json_encode($res));
        
        // Clean any buffered output and send JSON
        ob_clean();
        echo json_encode($res);
        exit; // Make sure nothing else is output
    } catch (Throwable $e) {
        error_log("Registration error: " . $e->getMessage());
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
        exit;
    }
}
