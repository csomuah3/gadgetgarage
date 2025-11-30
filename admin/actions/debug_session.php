<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../settings/core.php';

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'is_logged_in' => function_exists('check_login') ? check_login() : false,
    'is_admin' => function_exists('check_admin') ? check_admin() : false,
    'user_role' => function_exists('get_user_role') ? get_user_role() : null,
    'admin_role_constant' => defined('ADMIN_ROLE_ID') ? ADMIN_ROLE_ID : 'undefined'
], JSON_PRETTY_PRINT);
?>