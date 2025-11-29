<?php
/**
 * Get Recent Purchases for Notification Pop-ups
 * Returns recent orders with product details for display in purchase notifications
 */

require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../classes/order_class.php');
require_once(__DIR__ . '/../helpers/image_helper.php');

header('Content-Type: application/json');

try {
    $order = new Order();
    
    // Get recent orders from the last 7 days
    // Join with orderdetails and products to get product info
    $sql = "SELECT 
                o.order_id,
                o.order_date,
                od.product_id,
                od.qty,
                p.product_title,
                p.product_image,
                p.product_color,
                p.product_price,
                b.brand_name,
                c.cat_name,
                TIMESTAMPDIFF(MINUTE, 
                    CONCAT(o.order_date, ' 12:00:00'), 
                    NOW()
                ) as minutes_ago
            FROM orders o
            INNER JOIN orderdetails od ON o.order_id = od.order_id
            INNER JOIN products p ON od.product_id = p.product_id
            LEFT JOIN brands b ON p.product_brand = b.brand_id
            LEFT JOIN categories c ON p.product_cat = c.cat_id
            WHERE o.order_status IN ('completed', 'processing', 'paid')
            AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY o.order_date DESC, o.order_id DESC
            LIMIT 50";
    
    $result = $order->db_fetch_all($sql);
    
    if (!$result) {
        $result = [];
    }
    
    // Format the data for frontend
    $purchases = [];
    foreach ($result as $row) {
        // Get product image URL
        $image_url = get_product_image_url($row['product_image'], $row['product_title']);
        
        // Format time ago
        $minutes_ago = (int)$row['minutes_ago'];
        $time_ago = '';
        if ($minutes_ago < 1) {
            $time_ago = 'Just now';
        } elseif ($minutes_ago < 60) {
            $time_ago = $minutes_ago . ' ' . ($minutes_ago == 1 ? 'Minute' : 'Minutes') . ' Ago';
        } elseif ($minutes_ago < 1440) {
            $hours = floor($minutes_ago / 60);
            $time_ago = $hours . ' ' . ($hours == 1 ? 'Hour' : 'Hours') . ' Ago';
        } else {
            $days = floor($minutes_ago / 1440);
            $time_ago = $days . ' ' . ($days == 1 ? 'Day' : 'Days') . ' Ago';
        }
        
        $purchases[] = [
            'order_id' => $row['order_id'],
            'product_id' => $row['product_id'],
            'product_title' => $row['product_title'],
            'product_image' => $image_url,
            'product_color' => $row['product_color'] ?? '',
            'brand_name' => $row['brand_name'] ?? '',
            'category_name' => $row['cat_name'] ?? '',
            'time_ago' => $time_ago,
            'minutes_ago' => $minutes_ago
        ];
    }
    
    // Remove duplicates (same product purchased multiple times)
    $unique_purchases = [];
    $seen_products = [];
    foreach ($purchases as $purchase) {
        $key = $purchase['product_id'] . '_' . $purchase['minutes_ago'];
        if (!isset($seen_products[$key])) {
            $unique_purchases[] = $purchase;
            $seen_products[$key] = true;
        }
    }
    
    // Shuffle to randomize order
    shuffle($unique_purchases);
    
    echo json_encode([
        'success' => true,
        'purchases' => array_slice($unique_purchases, 0, 20) // Return max 20
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching recent purchases: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch recent purchases',
        'purchases' => []
    ]);
}

