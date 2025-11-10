<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../controllers/user_controller.php';

// Check if this is a login request (no name field means login)
$isLogin = !isset($_POST['name']) || empty($_POST['name']);

if ($isLogin) {
    // HANDLE LOGIN REQUEST
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
            $result['redirect'] = ($role === 2) ? '../admin/category.php' : '../index.php';

            // Never return the raw user_data to the client
            unset($result['user_data']);
        }

        echo json_encode($result);
    } catch (Throwable $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Login failed. Please try again.']);
    }
} else {
    // HANDLE REGISTRATION REQUEST (unchanged)
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

        if (empty($name) || empty($email) || empty($password) || empty($phone_number) || empty($country) || empty($city)) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long']);
            exit;
        }
        if (!preg_match('/^[0-9+\-\s()]+$/', $phone_number)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format']);
            exit;
        }
        if (!in_array($role, [1, 2], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid role selected']);
            exit;
        }

        $res = register_user_ctr($name, $email, $password, $phone_number, $country, $city, $role);

        if (is_bool($res)) {
            $res = $res
                ? ['status' => 'success', 'message' => 'Registered successfully']
                : ['status' => 'error', 'message' => 'Failed to register'];
        }

        echo json_encode($res);
    } catch (Throwable $e) {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    }
}
