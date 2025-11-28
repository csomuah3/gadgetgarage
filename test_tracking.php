<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/settings/core.php';

echo json_encode([
    'status' => 'success',
    'message' => 'Test endpoint working',
    'session_data' => $_SESSION,
    'post_data' => $_POST,
    'database_constants' => [
        'SERVER' => defined('SERVER') ? SERVER : 'not defined',
        'DATABASE' => defined('DATABASE') ? DATABASE : 'not defined',
        'USERNAME' => defined('USERNAME') ? USERNAME : 'not defined'
    ]
]);
?>