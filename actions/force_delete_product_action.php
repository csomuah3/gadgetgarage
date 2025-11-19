<?php
/**
 * Force Delete Product Action
 * Deletes a product even if it's in someone's cart
 */

require_once(__DIR__ . '/../settings/connection.php');
require_once(__DIR__ . '/../settings/core.php');

// Check if user is admin
if (!check_login() || !check_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$product_id = $_POST['product_id'] ?? null;

if (!$product_id || !is_numeric($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

try {
    // Begin transaction
    $connection->autocommit(false);

    // Get product info before deletion
    $product_query = "SELECT product_title, product_image FROM products WHERE product_id = ?";
    $product_stmt = $connection->prepare($product_query);
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();

    if ($product_result->num_rows === 0) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    $product = $product_result->fetch_assoc();
    $product_title = $product['product_title'];
    $product_image = $product['product_image'];

    // 1. Remove from all carts (both customer and guest carts)
    $delete_cart_query = "DELETE FROM cart WHERE product_id = ?";
    $delete_cart_stmt = $connection->prepare($delete_cart_query);
    $delete_cart_stmt->bind_param("i", $product_id);
    $delete_cart_result = $delete_cart_stmt->execute();

    if (!$delete_cart_result) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to remove product from carts']);
        exit();
    }

    $carts_affected = $delete_cart_stmt->affected_rows;

    // 2. Remove from order details if exists (optional - you might want to keep order history)
    // Uncomment the lines below if you want to remove from order history as well
    /*
    $delete_order_details_query = "DELETE FROM order_details WHERE product_id = ?";
    $delete_order_details_stmt = $connection->prepare($delete_order_details_query);
    $delete_order_details_stmt->bind_param("i", $product_id);
    $delete_order_details_stmt->execute();
    */

    // 3. Remove product images relationship if exists
    $delete_product_images_query = "DELETE FROM product_images WHERE product_id = ?";
    $delete_product_images_stmt = $connection->prepare($delete_product_images_query);
    $delete_product_images_stmt->bind_param("i", $product_id);
    $delete_product_images_stmt->execute();

    // 4. Finally, delete the product itself
    $delete_product_query = "DELETE FROM products WHERE product_id = ?";
    $delete_product_stmt = $connection->prepare($delete_product_query);
    $delete_product_stmt->bind_param("i", $product_id);
    $delete_product_result = $delete_product_stmt->execute();

    if (!$delete_product_result) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
        exit();
    }

    // 5. Try to delete the physical image file (optional)
    if (!empty($product_image)) {
        $image_paths = [
            __DIR__ . '/../uploads/products/' . $product_image,
            __DIR__ . '/../uploads/' . $product_image,
            __DIR__ . '/../images/' . $product_image
        ];

        foreach ($image_paths as $image_path) {
            if (file_exists($image_path)) {
                @unlink($image_path); // @ suppresses errors if file can't be deleted
                break;
            }
        }
    }

    // Commit transaction
    $connection->commit();

    // Log the deletion for admin records
    error_log("Admin " . $_SESSION['user_id'] . " force deleted product ID: $product_id ($product_title) - removed from $carts_affected carts");

    echo json_encode([
        'success' => true,
        'message' => "Product '$product_title' successfully deleted",
        'details' => "Removed from $carts_affected shopping carts"
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $connection->rollback();
    error_log("Force delete product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);

} finally {
    // Reset autocommit
    $connection->autocommit(true);
}
?>