<?php
require_once __DIR__ . '/../settings/core.php';

header('Content-Type: application/json');

echo json_encode([
    'session_data' => $_SESSION,
    'session_id' => session_id(),
    'check_login' => check_login(),
    'check_admin' => check_admin(),
    'user_id' => get_user_id(),
    'user_role' => get_user_role(),
    'admin_role_id' => ADMIN_ROLE_ID,
    'session_status' => session_status(),
    'session_status_text' => [
        PHP_SESSION_DISABLED => 'PHP_SESSION_DISABLED',
        PHP_SESSION_NONE => 'PHP_SESSION_NONE',
        PHP_SESSION_ACTIVE => 'PHP_SESSION_ACTIVE'
    ][session_status()] ?? 'UNKNOWN'
]);
?>