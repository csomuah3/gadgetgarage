<?php
require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/recommendation_controller.php');
require_once(__DIR__ . '/../controllers/order_controller.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!check_admin()) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

try {
    require_once(__DIR__ . '/../classes/order_class.php');
    $order = new Order();
    
    $sql = "SELECT order_id FROM orders WHERE order_status = 'completed' OR order_status = 'delivered'";
    $orders = $order->db_fetch_all($sql);
    
    $processed = 0;
    foreach ($orders as $order_data) {
        update_frequently_bought_together_ctr($order_data['order_id']);
        $processed++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Processed $processed orders",
        'processed' => $processed
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

