<?php
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/category_controller.php';

header('Content-Type: application/json');

if (!check_login() || !check_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = trim($_POST['category_name'] ?? '');

try {
    $res = add_category_ctr($name);
    if (!empty($res['success'])) {
        echo json_encode(['success' => true, 'message' => 'Category added', 'cat_id' => $res['cat_id'] ?? null]);
    } else {
        $code = $res['code'] ?? 'DB';
        $msg =
            $code === 'EMPTY'     ? 'Category name is required' :
            ($code === 'DUPLICATE' ? 'Category already exists' : 'Failed to add category');
        echo json_encode(['success' => false, 'message' => $msg, 'code' => $code]);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
}
