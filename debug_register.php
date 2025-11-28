<?php
// Debug registration script
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    echo json_encode(['status' => 'debug', 'message' => 'Debug script loaded']);

    require_once __DIR__ . '/settings/db_class.php';
    require_once __DIR__ . '/classes/user_class.php';
    require_once __DIR__ . '/controllers/user_controller.php';

    echo json_encode(['status' => 'debug', 'message' => 'Files included successfully']);

    // Test database connection
    $db = new db_connection();
    if (!$db->db_connect()) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit;
    }

    echo json_encode(['status' => 'debug', 'message' => 'Database connected']);

    // Test with sample data
    $test_data = [
        'name' => 'Test User ' . time(),
        'email' => 'test' . time() . '@example.com',
        'password' => 'testpass123',
        'phone_number' => '0123456789',
        'country' => 'Ghana',
        'city' => 'Accra',
        'role' => 1
    ];

    echo json_encode(['status' => 'debug', 'message' => 'Test data prepared', 'data' => $test_data]);

    // Test user class creation
    $user = new User();
    echo json_encode(['status' => 'debug', 'message' => 'User class instantiated']);

    // Test registration
    $result = $user->createUser(
        $test_data['name'],
        $test_data['email'],
        $test_data['password'],
        $test_data['phone_number'],
        $test_data['country'],
        $test_data['city'],
        $test_data['role']
    );

    echo json_encode(['status' => 'debug', 'message' => 'Registration attempted', 'result' => $result]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
} catch (Error $e) {
    echo json_encode(['status' => 'error', 'message' => 'Fatal Error: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
?>