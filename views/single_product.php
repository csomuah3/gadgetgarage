<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../controllers/product_controller.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');
    require_once(__DIR__ . '/../helpers/image_helper.php');
} catch (Exception $e) {
    error_log("Single product page include error: " . $e->getMessage());
    die("Error loading required files. Please try again later.");
}

// Safe session management
try {
    $is_logged_in = check_login();
    $is_admin = false;

    if ($is_logged_in) {
        $is_admin = check_admin();
    }

    // Get cart count with error handling
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $cart_count = 0;

    try {
        $cart_count = get_cart_count_ctr($customer_id, $ip_address);
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
        $cart_count = 0; // Default to 0 if cart count fails
    }
} catch (Exception $e) {
    error_log("Session management error: " . $e->getMessage());
    $is_logged_in = false;
    $is_admin = false;
    $customer_id = null;
    $cart_count = 0;
}

// Get product ID from URL (handle both 'id' and 'pid' parameters)
$product_id = isset($_GET['pid']) ? intval($_GET['pid']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if ($product_id <= 0) {
    header('Location: all_product.php');
    exit();
}

// Get product details with error handling
try {
    $product = view_single_product_ctr($product_id);

    if (!$product) {
        error_log("Product not found: ID = " . $product_id);
        header('Location: all_product.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Product retrieval error: " . $e->getMessage());
    header('Location: all_product.php');
    exit();
}

// Get product images from product_images table
$product_images = [];
try {
    require_once(__DIR__ . '/../settings/db_class.php');
    $db = new db_connection();
    if ($db->db_connect()) {
        $conn = $db->db_conn();

        $stmt = $conn->prepare("
            SELECT image_id, image_url, image_name, is_primary, sort_order
            FROM product_images
            WHERE product_id = ?
            ORDER BY is_primary DESC, sort_order ASC, image_id ASC
        ");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $product_images[] = $row;
        }
        $stmt->close();
        
        // Debug: Log how many images were found
        error_log("Product ID $product_id - Found " . count($product_images) . " images in product_images table");
    }

    // If no images in product_images table, use the main product image as fallback
    if (empty($product_images) && !empty($product['product_image'])) {
        error_log("Product ID $product_id - Using fallback product_image");
        $product_images[] = [
            'image_id' => 0,
            'image_url' => $product['product_image'],
            'image_name' => $product['product_title'] ?? 'Product Image',
            'is_primary' => 1,
            'sort_order' => 0
        ];
    }
} catch (Exception $e) {
    error_log("Product images retrieval error: " . $e->getMessage());
    // Fallback to main product image
    if (!empty($product['product_image'])) {
        $product_images[] = [
            'image_id' => 0,
            'image_url' => $product['product_image'],
            'image_name' => $product['product_title'] ?? 'Product Image',
            'is_primary' => 1,
            'sort_order' => 0
        ];
    }
}

// Category-based pricing configuration
$categoryPricing = [
    'Smartphones' => ['excellent' => 0, 'good' => 2000, 'fair' => 3500],
    'Mobile Devices' => ['excellent' => 0, 'good' => 2000, 'fair' => 3500],
    'Tablets' => ['excellent' => 0, 'good' => 1800, 'fair' => 2500],
    'iPads' => ['excellent' => 0, 'good' => 1800, 'fair' => 2500],
    'Laptops' => ['excellent' => 0, 'good' => 3000, 'fair' => 3400],
    'Computing' => ['excellent' => 0, 'good' => 3000, 'fair' => 3400],
    'Desktops' => ['excellent' => 0, 'good' => 2000, 'fair' => 2300],
    'Cameras' => ['excellent' => 0, 'good' => 1000, 'fair' => 2000],
    'Photography & Video' => ['excellent' => 0, 'good' => 1000, 'fair' => 2000],
    'Video Equipment' => ['excellent' => 0, 'good' => 1500, 'fair' => 3000],
    'default' => ['excellent' => 0, 'good' => 1000, 'fair' => 2000]
];

// Function to calculate price based on category and condition
function calculateConditionPrice($basePrice, $category, $condition, $categoryPricing)
{
    $categoryKey = isset($categoryPricing[$category]) ? $category : 'default';
    $discount = $categoryPricing[$categoryKey][$condition];
    return max(0, $basePrice - $discount);
}

// Get category and base price
$productCategory = $product['cat_name'] ?? 'default';
$basePrice = floatval($product['product_price']);

// Calculate prices for all conditions
$excellentPrice = calculateConditionPrice($basePrice, $productCategory, 'excellent', $categoryPricing);
$goodPrice = calculateConditionPrice($basePrice, $productCategory, 'good', $categoryPricing);
$fairPrice = calculateConditionPrice($basePrice, $productCategory, 'fair', $categoryPricing);

// Calculate discounts
$goodDiscount = $basePrice - $goodPrice;
$fairDiscount = $basePrice - $fairPrice;

// Get Top Picks - start with same category, then fill with other popular products
$category_id = $product['product_cat'] ?? 0;
$related_products = [];

try {
    require_once(__DIR__ . '/../settings/db_class.php');
    $db = new db_connection();
    if ($db->db_connect()) {
        $conn = $db->db_conn();

        // First, get products from the same category (excluding current product)
        if ($category_id > 0) {
            $stmt = $conn->prepare("
                SELECT p.*, c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = ? AND p.product_id != ?
                ORDER BY p.product_id DESC
                LIMIT 4
            ");
            $stmt->bind_param('ii', $category_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $related_products[] = $row;
            }
            $stmt->close();
        }

        // If we have less than 4 products, fill with products from other categories
        $current_count = count($related_products);
        if ($current_count < 4) {
            $needed = 4 - $current_count;

            // Get product IDs we already have to exclude them
            $exclude_ids = [$product_id]; // Always exclude current product
            foreach ($related_products as $existing) {
                $exclude_ids[] = $existing['product_id'];
            }
            $exclude_placeholders = str_repeat('?,', count($exclude_ids) - 1) . '?';

            $stmt = $conn->prepare("
                SELECT p.*, c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_id NOT IN ($exclude_placeholders)
                ORDER BY p.product_id DESC
                LIMIT ?
            ");

            // Bind the exclude IDs and limit
            $types = str_repeat('i', count($exclude_ids)) . 'i';
            $values = array_merge($exclude_ids, [$needed]);
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $related_products[] = $row;
            }
            $stmt->close();
        }

        // Connection will close automatically at end of script
    }
} catch (Exception $e) {
    error_log("Top picks products error: " . $e->getMessage());
    $related_products = []; // Default to empty array if products fail
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($product['product_title']); ?> - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Color Scheme Variables */
        :root {
            --light-blue: #E8F0FE;
            --medium-blue: #4285F4;
            --dark-blue: #1A73E8;
            --navy-blue: #0D47A1;
            --off-white: #FAFAFA;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --shadow: rgba(26, 115, 232, 0.1);
            --gradient-primary: linear-gradient(135deg, var(--navy-blue) 0%, var(--dark-blue) 50%, var(--medium-blue) 100%);
            --gradient-light: linear-gradient(135deg, var(--light-blue) 0%, var(--off-white) 100%);
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background-color: #ffffff;
            color: #1a1a1a;
            overflow-x: hidden;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('http://169.239.251.102:442/~chelsea.somuah/uploads/ChatGPTImageNov19202511_50_42PM.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.45;
            z-index: -1;
            pointer-events: none;
        }

        /* Promotional Banner Styles - Same as index */
        

        

        

        

        

        .promo-timer {
            background: transparent;
            padding: 0;
            border-radius: 0;
            font-size: 1.3rem;
            font-weight: 500;
            margin: 0;
            border: none;
        }

        .promo-shop-link {
            color: white;
            text-decoration: underline;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.3s ease;
            font-size: 1.2rem;
            flex: 0 0 auto;
        }

        .promo-shop-link:hover {
            opacity: 0.8;
        }

        /* Header Styles - Same as index */
        

        .logo {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo img {
            height: 60px !important;
            width: auto !important;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        .logo .garage {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .search-container {
            position: relative;
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            background: #f8fafc;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .search-input:focus {
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1.1rem;
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .tech-revival-section {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1f2937;
        }

        .tech-revival-icon {
            font-size: 2.5rem;
            color: #10b981;
        }

        .tech-revival-text {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }

        .contact-number {
            font-size: 1rem;
            font-weight: 500;
            color: #6b7280;
            margin: 0;
            line-height: 1.2;
        }

        .product-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin: 30px 0;
        }

        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background: #f8fafc;
        }

        .product-details {
            padding: 40px;
        }

        .product-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 20px;
        }

        .product-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-weight: 500;
        }

        .meta-item i {
            color: #000000;
            font-size: 1.1rem;
        }

        .product-description {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #4a5568;
            margin-bottom: 25px;
        }

        .product-keywords {
            margin-bottom: 30px;
        }

        .keyword-tag {
            display: inline-block;
            background: #000000;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .add-to-cart-btn {
            background: #000000;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-to-cart-btn:hover {
            background: #374151;
            transform: scale(1.05);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #e2e8f0;
            color: #4a5568;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-btn:hover {
            background: #cbd5e0;
            color: #2d3748;
        }

        /* Main Navigation - Copied from index.php */
        

        .nav-menu {
            display: flex;
            align-items: center;
            width: 100%;
            padding-left: 280px;
        }

        .nav-item {
            color: #1f2937;
            text-decoration: none;
            font-weight: 600;
            padding: 16px 20px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            border-radius: 8px;
            white-space: nowrap;
        }

        .nav-item:hover {
            background: rgba(0, 128, 96, 0.1);
            color: #008060;
            transform: translateY(-2px);
        }

        .nav-item.flash-deal {
            color: #ef4444;
            font-weight: 700;
            margin-left: auto;
            padding-right: 600px;
        }

        .nav-item.flash-deal:hover {
            color: #dc2626;
        }

        /* Blue Shop by Categories Button */
        .shop-categories-btn {
            position: relative;
        }

        .categories-button {
            background: #4f63d2;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .categories-button:hover {
            background: #3d4fd1;
        }

        .categories-button i:last-child {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .shop-categories-btn:hover .categories-button i:last-child {
            transform: rotate(180deg);
        }

        .nav-item.dropdown {
            position: relative;
        }

        /* Mega Dropdown */
        .mega-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 800px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 32px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .mega-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }

        .dropdown-column h4 {
            color: #1f2937;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 8px;
        }

        .dropdown-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .dropdown-column ul li {
            margin-bottom: 8px;
        }

        .dropdown-column ul li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            transition: all 0.3s ease;
        }

        .dropdown-column ul li a:hover {
            color: #008060;
            transform: translateX(4px);
        }

        .dropdown-column ul li a i {
            color: #9ca3af;
            width: 20px;
        }

        .dropdown-column.featured {
            border-left: 2px solid #f3f4f6;
            padding-left: 24px;
        }

        .featured-item {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .featured-item img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        .featured-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .featured-text strong {
            color: #1f2937;
            font-size: 1rem;
        }

        .featured-text p {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0;
        }

        .shop-now-btn {
            background: #008060;
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 4px;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .shop-now-btn:hover {
            background: #006b4e;
            color: white;
        }

        /* Simple Dropdown */
        .simple-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 8px 0;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .simple-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .simple-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .simple-dropdown ul li a {
            color: #6b7280;
            text-decoration: none;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .simple-dropdown ul li a:hover {
            background: #f9fafb;
            color: #008060;
        }

        /* Dropdown Positioning */
        .nav-dropdown {
            position: relative;
        }

        .brands-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 20px;
            min-width: 300px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .shop-categories-btn:hover .brands-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .brands-dropdown h4 {
            margin-bottom: 15px;
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .brands-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .brands-dropdown li {
            margin-bottom: 8px;
        }

        .brands-dropdown a {
            color: #6b7280;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .brands-dropdown a:hover {
            background: #f3f4f6;
            color: #3b82f6;
        }

        /* User Interface Styles - Same as index */
        .user-actions {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .login-btn {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-1px);
            color: white;
        }

        .user-dropdown {
            position: relative;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #008060, #006b4e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.15);
            box-shadow: 0 5px 15px rgba(0, 128, 96, 0.5);
        }

        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(139, 95, 191, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
            padding: 15px 0;
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .dropdown-menu-custom.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
        }

        .dropdown-item-custom:hover {
            background: rgba(139, 95, 191, 0.1);
            color: #008060;
            transform: translateX(3px);
        }

        .dropdown-item-custom i {
            font-size: 1rem;
            width: 18px;
            text-align: center;
        }

        .dropdown-divider-custom {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(139, 95, 191, 0.2), transparent);
            margin: 8px 0;
        }

        .header-icon {
            position: relative;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .header-icon:hover {
            background: rgba(139, 95, 191, 0.1);
            transform: scale(1.1);
        }

        .wishlist-badge,
        .cart-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Language and Theme Toggle Styles */
        .language-selector,
        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .toggle-switch {
            position: relative;
            width: 40px;
            height: 20px;
            background: #cbd5e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: #008060;
        }

        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(20px);
        }


        .side-card {
            display: grid;
            grid-template-columns: 1fr 80px;
            gap: 16px;
            padding: 24px;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .side-card.yellow {
            background: #fbbf24;
            color: #1f2937;
        }

        .side-card.green {
            background: #22c55e;
            color: white;
        }

        .side-copy {
            display: grid;
            align-content: center;
            gap: 8px;
        }

        .side-title {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .side-price {
            font-size: 12px;
            margin: 0;
            opacity: 0.9;
        }

        .side-price .price {
            font-weight: 700;
            font-size: 14px;
        }

        .side-media {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .side-banners {
                grid-template-rows: none;
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .main-banner {
                grid-template-columns: 1fr;
                padding: 28px;
            }

            .banner-media {
                order: -1;
            }

            .side-banners {
                grid-template-columns: 1fr;
            }
        }

        .hero-actions .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            border-width: 2px;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-item {
            color: #64748b;
        }

        .breadcrumb-item.active {
            color: #000000;
            font-weight: 600;
        }

        .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
            color: #cbd5e0;
        }

        .product-id {
            background: #f8fafc;
            color: #64748b;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .share-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .share-btn.facebook {
            background: #1877f2;
        }

        .share-btn.twitter {
            background: #1da1f2;
        }

        .share-btn.whatsapp {
            background: #25d366;
        }

        .share-btn:hover {
            transform: scale(1.1);
        }

        /* Condition Selection Styles */
        .condition-selection {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }

        .condition-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .condition-option {
            margin-bottom: 0 !important;
        }

        .condition-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 25px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            width: 100%;
        }

        .condition-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .condition-label span {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 4px;
            color: #1a202c;
        }

        .condition-label small {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.2;
        }

        .condition-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a202c;
        }

        .condition-discount {
            font-size: 0.9rem;
            color: #dc2626;
            margin-top: 4px;
        }

        .excellent-label i {
            color: #22c55e;
        }

        .good-label i {
            color: #3b82f6;
        }

        .fair-label i {
            color: #f59e0b;
        }

        .condition-option input[type="radio"]:checked+.condition-label {
            border-color: #000000;
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .condition-option input[type="radio"] {
            display: none;
        }

        /* Price Section Styles */
        .price-section {
            margin-bottom: 25px;
        }

        .price-breakdown {
            margin-top: 10px;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 8px;
            border: 1px solid #bae6fd;
        }

        .original-price {
            color: #64748b;
            text-decoration: line-through;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .discount-amount {
            color: #dc2626;
            font-weight: 600;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .product-details {
                padding: 20px;
            }

            .product-title {
                font-size: 1.8rem;
            }

            .product-price {
                font-size: 2rem;
            }

            .product-meta {
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .add-to-cart-btn {
                justify-content: center;
            }

            .condition-options {
                flex-direction: column;
            }

            .condition-label {
                min-width: auto;
                padding: 15px;
            }
        }

        /* Product Gallery Styles */
        .product-gallery {
            position: relative;
            display: flex !important;
            height: 100%;
            min-height: 500px;
            background: #f8f9fa;
            border-radius: 12px;
            overflow: visible;
            border: 2px solid #e9ecef;
        }

        .thumbnail-container {
            width: 110px;
            background: #f8f9fa;
            border-right: 2px solid #dee2e6;
            display: flex !important;
            flex-direction: column;
            visibility: visible !important;
            min-height: 400px;
        }

        .thumbnail-list {
            display: flex !important;
            flex-direction: column;
            padding: 10px 5px;
            gap: 12px;
            overflow-y: auto;
            max-height: 100%;
            visibility: visible !important;
            align-items: center;
        }

        .thumbnail-item {
            width: 80px;
            display: block !important;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 3px solid #dee2e6;
            transition: all 0.3s ease;
            position: relative;
            background: white;
        }

        .thumbnail-item:hover {
            border-color: #4285F4;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(66, 133, 244, 0.3);
        }

        .thumbnail-item.active {
            border-color: #4285F4;
            border-width: 4px;
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.4);
        }

        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .main-image-container {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            overflow: visible;
            min-height: 500px;
        }

        .main-product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 0;
            cursor: zoom-in;
            transition: all 0.3s ease;
            display: block;
        }

        /* Magnifying Glass Styles */
        .magnify-container {
            position: relative;
            display: inline-block;
            overflow: visible;
            border-radius: 8px;
        }

        .magnify-lens {
            position: absolute;
            border: 4px solid #4285F4;
            border-radius: 50%;
            cursor: zoom-in;
            width: 150px;
            height: 150px;
            opacity: 0 !important;
            transition: opacity 0.3s ease;
            pointer-events: none;
            background: rgba(66, 133, 244, 0.3);
            box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.5), 0 6px 20px rgba(0, 0, 0, 0.4);
            z-index: 10000;
            backdrop-filter: blur(2px);
            display: block;
        }

        .magnify-lens.active {
            opacity: 1 !important;
            display: block !important;
        }

        .magnify-result {
            position: fixed !important;
            top: 150px !important;
            right: 50px !important;
            width: 350px !important;
            height: 350px !important;
            border: 5px solid #4285F4 !important;
            border-radius: 12px;
            background: white !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4) !important;
            overflow: hidden;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: all 0.3s ease;
            z-index: 999999 !important;
            display: block;
        }

        .magnify-result.active {
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
        }

        .magnify-result img {
            width: auto;
            height: auto;
            position: absolute;
        }

        /* Thumbnail Gallery Styles */
        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            overflow-x: auto;
        }

        .thumbnail-item {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            opacity: 0.7;
        }

        .thumbnail-item:hover {
            border-color: #4285F4;
            transform: scale(1.05);
            opacity: 1;
        }

        .thumbnail-item.active {
            border-color: #4285F4;
            opacity: 1;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.3);
        }

        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .gallery-arrow:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: translateY(-50%) scale(1.1);
        }

        .gallery-arrow-left {
            left: 20px;
        }

        .gallery-arrow-right {
            right: 20px;
        }

        /* Scroll arrows for thumbnails */
        .thumbnail-scroll-up,
        .thumbnail-scroll-down {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }

        .thumbnail-scroll-up {
            top: 5px;
        }

        .thumbnail-scroll-down {
            bottom: 5px;
        }

        @media (max-width: 768px) {
            .product-gallery {
                flex-direction: column-reverse;
                height: auto;
            }

            .thumbnail-container {
                width: 100%;
                height: 100px;
                border-right: none;
                border-top: 1px solid #dee2e6;
            }

            .thumbnail-list {
                flex-direction: row;
                overflow-x: auto;
                overflow-y: hidden;
                padding: 10px;
            }

            .thumbnail-item {
                flex-shrink: 0;
            }

            .main-image-container {
                min-height: 400px;
            }
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }

        .scroll-to-top.show {
            display: flex;
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            background: linear-gradient(135deg, #2563EB, #1E3A5F);
            transform: translateX(-50%) translateY(-3px);
            box-shadow: 0 6px 16px rgba(30, 58, 95, 0.4);
        }

        .scroll-to-top:active {
            transform: translateX(-50%) translateY(-1px);
        }

        @media (max-width: 768px) {
            .scroll-to-top {
                bottom: 20px;
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4" id="product-details">

        <div class="product-container">
            <div class="row g-0">
                <div class="col-lg-6">
                    <!-- Product Image Gallery -->
                    <div class="product-gallery">
                        
                        <!-- Main Image Display with Magnifying Glass -->
                        <div class="main-image-container">
                            <div class="magnify-container" id="magnifyContainer">
                                <?php
                                // Get the primary image or first available image
                                $primary_image = !empty($product_images) ? $product_images[0] : null;
                                if ($primary_image) {
                                    $image_url = get_product_image_url($primary_image['image_url'], $primary_image['image_name'], '600x400');
                                } else {
                                    $image_url = generate_placeholder_url($product['product_title'] ?? 'Product', '600x400');
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>"
                                     id="mainProductImage"
                                     alt="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>"
                                     class="main-product-image"
                                     data-product-id="<?php echo $product['product_id']; ?>"
                                     data-product-title="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>">

                                <!-- Magnifying Lens -->
                                <div class="magnify-lens" id="magnifyLens"></div>

                                <!-- Magnified Result Window -->
                                <div class="magnify-result" id="magnifyResult">
                                    <img src="<?php echo htmlspecialchars($image_url); ?>"
                                         id="magnifyResultImage"
                                         alt="Magnified view">
                                </div>
                            </div>

                            <!-- Navigation Arrows (if needed) -->
                            <button class="gallery-arrow gallery-arrow-left" onclick="previousImage()" style="display: none;">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button class="gallery-arrow gallery-arrow-right" onclick="nextImage()" style="display: none;">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>

                        <!-- Thumbnail Gallery (Left Side) -->
                        <div class="thumbnail-container">
                            <div class="thumbnail-list" id="thumbnailList">
                                <?php if (!empty($product_images)): ?>
                                    <?php 
                                    // Debug output
                                    echo "<!-- Found " . count($product_images) . " product images -->\n";
                                    ?>
                                    <?php foreach ($product_images as $index => $image): ?>
                                        <?php
                                        $thumb_url = get_product_image_url($image['image_url'], $image['image_name'], '80x80');
                                        $active_class = $index === 0 ? 'active' : '';
                                        ?>
                                        <div class="thumbnail-item <?php echo $active_class; ?>"
                                             onclick="changeMainImage(<?php echo $index; ?>)"
                                             data-image-url="<?php echo htmlspecialchars($image['image_url']); ?>"
                                             data-thumb-url="<?php echo htmlspecialchars($thumb_url); ?>"
                                             data-index="<?php echo $index; ?>">
                                            <img src="<?php echo htmlspecialchars($thumb_url); ?>"
                                                 alt="<?php echo htmlspecialchars($image['image_name']); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="product-details" style="padding: 40px; background: #4f46e5; color: white; height: 100%;">
                        <!-- Special Offer Header -->
                        <div style="margin-bottom: 20px;">
                            <span style="background: white; color: #4f46e5; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">SPECIAL OFFER</span>
                        </div>

                        <!-- Product Title -->
                        <h1 style="color: white; font-size: 2.2rem; font-weight: 800; margin-bottom: 15px; line-height: 1.2;"><?php echo htmlspecialchars($product['product_title']); ?></h1>

                        <!-- Product Description -->
                        <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; margin-bottom: 25px; line-height: 1.6;">
                            <?php
                            $description = $product['product_desc'] ?? 'The ultimate professional device with advanced features. Perfect for intensive workflows and high-performance tasks.';
                            echo htmlspecialchars(strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description);
                            ?>
                        </p>

                        <!-- Key Features -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: white; margin-bottom: 15px; font-weight: 600;">Key Features</h5>
                            <?php
                            // Generate features based on product category and brand
                            $category = $product['cat_name'] ?? 'Electronic';
                            $brand = $product['brand_name'] ?? 'Premium';
                            $features = [
                                '• ' . ucfirst($brand) . ' brand with premium quality',
                                '• ' . ucfirst($category) . ' device specifications',
                                '• High-performance components',
                                '• Professional-grade reliability',
                                '• Advanced connectivity options'
                            ];
                            foreach ($features as $feature): ?>
                                <div style="color: rgba(255,255,255,0.95); margin-bottom: 8px; display: flex; align-items: center;">
                                    <i class="fas fa-check" style="color: #10b981; margin-right: 12px; font-size: 0.9rem;"></i>
                                    <?php echo $feature; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Select Condition -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: white; margin-bottom: 20px; font-weight: 600;">Select Condition</h5>

                            <!-- Excellent Condition -->
                            <div style="background: rgba(255,255,255,0.15); border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" id="excellent-option" data-condition="excellent" data-price="<?php echo $excellentPrice; ?>" onclick="selectCondition('excellent')">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">Excellent Condition</div>
                                        <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Like new, no visible wear</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.1rem; font-weight: 700; color: white;">GH₵<?php echo number_format($excellentPrice, 0); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Good Condition -->
                            <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" id="good-option" data-condition="good" data-price="<?php echo $goodPrice; ?>" onclick="selectCondition('good')">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">Good Condition</div>
                                        <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Minor scratches, fully functional</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.1rem; font-weight: 700; color: white;">GH₵<?php echo number_format($goodPrice, 0); ?></div>
                                        <?php if ($goodDiscount > 0): ?>
                                            <div style="color: #10b981; font-size: 0.85rem;">-GH₵<?php echo number_format($goodDiscount, 0); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Fair Condition -->
                            <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" id="fair-option" data-condition="fair" data-price="<?php echo $fairPrice; ?>" onclick="selectCondition('fair')">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">Fair Condition</div>
                                        <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Visible wear, works perfectly</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.1rem; font-weight: 700; color: white;">GH₵<?php echo number_format($fairPrice, 0); ?></div>
                                        <?php if ($fairDiscount > 0): ?>
                                            <div style="color: #10b981; font-size: 0.85rem;">-GH₵<?php echo number_format($fairDiscount, 0); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Display -->
                        <div style="margin-bottom: 30px;">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 8px;">
                                <span id="currentPrice" style="color: white; font-size: 2.5rem; font-weight: 800;">GH₵<?php echo number_format($product['product_price'], 0); ?></span>
                                <span id="originalPrice" style="color: rgba(255,255,255,0.6); font-size: 1.5rem; text-decoration: line-through; display: none;">GH₵<?php echo number_format($product['product_price'] * 1.13, 0); ?></span>
                                <span id="discountBadge" style="background: #ef4444; color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; display: none;">13% off</span>
                            </div>
                            <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Limited time offer - While supplies last</div>
                        </div>

                        <!-- Add to Cart Button -->
                        <button onclick="addToCartWithCondition(<?php echo $product['product_id']; ?>)" id="addToCartBtn"
                            style="width: 100%; background: white; color: #4f46e5; border: none; padding: 18px; border-radius: 12px; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 20px;">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart - GH₵<span id="cartButtonPrice"><?php echo number_format($product['product_price'], 0); ?></span>
                        </button>

                        <div style="color: rgba(255,255,255,0.7); font-size: 0.85rem; text-align: center;">
                            <i class="fas fa-shield-alt" style="margin-right: 5px;"></i>
                            Secure checkout • Free delivery • 30-day return policy
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="all_product.php" class="btn btn-outline-primary me-3">
                    <i class="fas fa-grid-3x3"></i> View All Products
                </a>
                <a href="product_search_result.php?query=<?php echo urlencode($product['cat_name'] ?? ''); ?>" class="btn btn-outline-success">
                    <i class="fas fa-search"></i> Similar Products
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/dark-mode.js"></script>
    <script src="js/cart.js"></script>
    <script>
        // Global variables for condition selection
        let selectedCondition = 'excellent';
        let selectedPrice = <?php echo floatval($product['product_price']); ?>;
        let originalPrice = <?php echo floatval($product['product_price']); ?>;
        let stockQuantity = <?php echo isset($product['stock_quantity']) ? intval($product['stock_quantity']) : 0; ?>;

        // Price calculation data
        const priceData = {
            excellent: <?php echo $excellentPrice; ?>,
            good: <?php echo $goodPrice; ?>,
            fair: <?php echo $fairPrice; ?>
        };

        // Product images array from PHP
        const productImages = <?php echo json_encode($product_images); ?>;
        let currentImageIndex = 0;
        

        // Magnifying Glass Function
        function initializeMagnifyingGlass() {
            try {
                console.log('🔍 Initializing magnifying glass...');
                const magnifyContainer = document.getElementById('magnifyContainer');
                const mainImage = document.getElementById('mainProductImage');
                const magnifyLens = document.getElementById('magnifyLens');
                const magnifyResult = document.getElementById('magnifyResult');
                const magnifyResultImage = document.getElementById('magnifyResultImage');

                console.log('Elements found:', {
                    container: !!magnifyContainer,
                    image: !!mainImage,
                    lens: !!magnifyLens,
                    result: !!magnifyResult,
                    resultImage: !!magnifyResultImage
                });

                if (!magnifyContainer || !mainImage || !magnifyLens || !magnifyResult || !magnifyResultImage) {
                    console.error('❌ Missing magnifying glass elements');
                    return;
                }

                console.log('✅ All elements found, setting up magnifying glass');

            // Magnifying glass functionality
            function magnify() {
                let cx, cy;

                // Ensure magnifyResultImage has the same source as main image
                if (magnifyResultImage.src !== mainImage.src) {
                    magnifyResultImage.src = mainImage.src;
                }

                // Calculate the ratio between result DIV and lens
                cx = magnifyResult.offsetWidth / magnifyLens.offsetWidth;
                cy = magnifyResult.offsetHeight / magnifyLens.offsetHeight;

                // Set background properties for the result DIV
                magnifyResultImage.style.width = (mainImage.width * cx) + "px";
                magnifyResultImage.style.height = (mainImage.height * cx) + "px";

                // Mouse move function
                function moveMagnifier(e) {
                    e.preventDefault();

                    // Get the cursor's x and y positions
                    const pos = getCursorPos(e);
                    let x = pos.x;
                    let y = pos.y;
                    

                    // Prevent the magnifying glass from being positioned outside the image
                    if (x > mainImage.width - (magnifyLens.offsetWidth / 2)) { x = mainImage.width - (magnifyLens.offsetWidth / 2); }
                    if (x < magnifyLens.offsetWidth / 2) { x = magnifyLens.offsetWidth / 2; }
                    if (y > mainImage.height - (magnifyLens.offsetHeight / 2)) { y = mainImage.height - (magnifyLens.offsetHeight / 2); }
                    if (y < magnifyLens.offsetHeight / 2) { y = magnifyLens.offsetHeight / 2; }

                    // Set the position of the magnifying glass
                    magnifyLens.style.left = (x - magnifyLens.offsetWidth / 2) + "px";
                    magnifyLens.style.top = (y - magnifyLens.offsetHeight / 2) + "px";

                    // Display what the magnifying glass "sees"
                    magnifyResultImage.style.left = ((x * cx) - magnifyResult.offsetWidth / 2) * -1 + "px";
                    magnifyResultImage.style.top = ((y * cy) - magnifyResult.offsetHeight / 2) * -1 + "px";
                }

                function getCursorPos(e) {
                    const a = mainImage.getBoundingClientRect();
                    let x = e.pageX - a.left - window.pageXOffset;
                    let y = e.pageY - a.top - window.pageYOffset;
                    return {x: x, y: y};
                }

                // Show magnifying glass on mouse enter
                function showMagnifier() {
                    console.log('🔍 SHOW magnifier triggered');
                    magnifyLens.classList.add('active');
                    magnifyResult.classList.add('active');
                }

                // Hide magnifying glass on mouse leave
                function hideMagnifier() {
                    console.log('🔍 HIDE magnifier triggered');
                    magnifyLens.classList.remove('active');
                    magnifyResult.classList.remove('active');
                }

                // Add event listeners
                console.log('🔍 Adding event listeners...');
                magnifyContainer.addEventListener('mouseenter', showMagnifier);
                magnifyContainer.addEventListener('mouseleave', hideMagnifier);
                magnifyContainer.addEventListener('mousemove', moveMagnifier);
                console.log('✅ Event listeners added');

                // Touch events for mobile
                magnifyContainer.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    showMagnifier();
                });

                magnifyContainer.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    hideMagnifier();
                });

                magnifyContainer.addEventListener('touchmove', function(e) {
                    e.preventDefault();
                    const touch = e.touches[0];
                    const mouseEvent = new MouseEvent('mousemove', {
                        clientX: touch.clientX,
                        clientY: touch.clientY,
                        pageX: touch.pageX,
                        pageY: touch.pageY
                    });
                    moveMagnifier(mouseEvent);
                });
            }

            // Initialize magnifying glass
            magnify();

            } catch (error) {
                console.error('Error initializing magnifying glass:', error);
            }
        }

        // Change main image function
        function changeMainImage(index) {
            if (index < 0 || index >= productImages.length) return;

            currentImageIndex = index;
            const selectedImage = productImages[index];
            const mainImage = document.getElementById('mainProductImage');
            const magnifyResultImage = document.getElementById('magnifyResultImage');

            if (mainImage && selectedImage) {
                // Update main image
                const newImageUrl = generateImageUrl(selectedImage.image_url, selectedImage.image_name, '600x400');
                mainImage.src = newImageUrl;
                mainImage.alt = selectedImage.image_name;

                // Update magnified image
                if (magnifyResultImage) {
                    magnifyResultImage.src = newImageUrl;
                }

                // Update thumbnail active state
                document.querySelectorAll('.thumbnail-item').forEach((thumb, thumbIndex) => {
                    thumb.classList.toggle('active', thumbIndex === index);
                });

                // Re-initialize magnifying glass for new image
                setTimeout(() => {
                    initializeMagnifyingGlass();
                }, 100);
            }
        }

        // Helper function to generate image URLs (similar to PHP function)
        function generateImageUrl(imagePath, imageName, size) {
            // Simple URL generation - adjust based on your image helper function
            return imagePath; // You may need to adjust this based on your get_product_image_url function
        }

        // SIMPLE TEST FUNCTION - for debugging
        window.testClick = function() {
            alert('Button click is working!');
        };

        // GLOBAL function definition - make sure it's available to onclick handlers
        window.selectCondition = function(condition, price = null) {
            // Basic alert to test if function is called
            console.log('=== selectCondition CALLED ===');
            console.log('Condition:', condition);
            console.log('Price:', price);

            // Show visual feedback immediately
            alert('Condition selected: ' + condition);

            try {
                // Validate condition
                if (!['excellent', 'good', 'fair'].includes(condition)) {
                    console.error('Invalid condition:', condition);
                    alert('Invalid condition: ' + condition);
                    return;
                }

                selectedCondition = condition;
                // Use price from priceData if not provided
                selectedPrice = price !== null ? parseFloat(price) : priceData[condition];

                console.log('Updated selectedCondition:', selectedCondition, 'selectedPrice:', selectedPrice);
            } catch (error) {
                console.error('Error in selectCondition:', error);
                alert('Error: ' + error.message);
                return;
            }

            // Update visual selection - reset all options first
            const allOptions = document.querySelectorAll('[data-condition]');
            allOptions.forEach(option => {
                option.style.background = 'rgba(255,255,255,0.1)';
                option.style.border = 'none';
                option.style.transform = 'scale(1)';
            });

            // Highlight selected option
            const selectedOption = document.querySelector(`[data-condition="${condition}"]`);
            if (selectedOption) {
                selectedOption.style.background = 'rgba(255,255,255,0.3)';
                selectedOption.style.border = '2px solid #10b981';
                selectedOption.style.transform = 'scale(1.02)';
                console.log('Selected option visually updated:', condition);
            }

            // Update pricing display
            const currentPrice = document.getElementById('currentPrice');
            const cartButtonPrice = document.getElementById('cartButtonPrice');

            if (currentPrice) {
                currentPrice.textContent = 'GH₵' + Math.round(selectedPrice).toLocaleString();
            }
            if (cartButtonPrice) {
                cartButtonPrice.textContent = Math.round(selectedPrice).toLocaleString();
            }

            // Show/hide discount information
            const originalPriceElement = document.getElementById('originalPrice');
            const discountBadge = document.getElementById('discountBadge');

            if (condition !== 'excellent') {
                if (originalPriceElement) originalPriceElement.style.display = 'inline';
                if (discountBadge) discountBadge.style.display = 'inline';

                const discountAmount = originalPrice - selectedPrice;
                const discountPercent = Math.round((discountAmount / originalPrice) * 100);
                if (discountBadge) discountBadge.textContent = discountPercent + '% off';
                if (originalPriceElement) originalPriceElement.textContent = 'GH₵' + Math.round(originalPrice).toLocaleString();
            } else {
                if (originalPriceElement) originalPriceElement.style.display = 'none';
                if (discountBadge) discountBadge.style.display = 'none';
            }

            console.log('Price display updated. Current price:', selectedPrice);
        };

        // Initialize condition selection
        function initializeConditionSelection() {
            console.log('Initializing condition selection with prices:', priceData);
            selectCondition('excellent');
            updateCartButtonState();
        }

        // Update cart button state based on stock
        function updateCartButtonState() {
            const btn = document.getElementById('addToCartBtn');
            if (!btn) return;

            if (stockQuantity <= 0) {
                btn.innerHTML = '<i class="fas fa-times-circle"></i> Out of Stock';
                btn.style.background = '#ef4444';
                btn.style.color = 'white';
                btn.style.cursor = 'not-allowed';
                btn.style.opacity = '0.8';
                btn.disabled = true;
            } else if (stockQuantity <= 5) {
                // Low stock warning
                btn.innerHTML = `<i class="fas fa-shopping-cart"></i> Add to Cart (${stockQuantity} left) - GH₵<span id="cartButtonPrice"><?php echo number_format($product['product_price'], 0); ?></span>`;
                btn.style.background = '#f59e0b';
                btn.style.color = 'white';
            }
        }

        // Enhanced Add to Cart Modal Function
        function showEnhancedAddToCartModal(productId, productName, productPrice) {
            // Get current product image URL
            const productImageElement = document.querySelector('.main-product-image');
            let productImage = '';
            if (productImageElement && productImageElement.src) {
                productImage = productImageElement.src;
            }

            // Remove existing modal
            const existingModal = document.getElementById('addToCartModal');
            if (existingModal) existingModal.remove();

            const modal = document.createElement('div');
            modal.id = 'addToCartModal';
            modal.className = 'cart-modal-overlay';
            modal.innerHTML = `
                <div class="cart-modal">
                    <div class="cart-modal-header">
                        <h3><i class="fas fa-shopping-cart"></i> Add to Cart</h3>
                        <button class="cart-modal-close" onclick="closeEnhancedAddToCartModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="cart-modal-body">
                        <div class="product-preview">
                            <img src="${productImage || generatePlaceholderUrl(productName, '80x80')}" alt="${productName}" class="product-image">
                            <div class="product-info">
                                <h4>${productName}</h4>
                                <div class="price-display">
                                    <span class="current-price">GH₵ <span id="modalPrice">${selectedPrice.toFixed(2)}</span></span>
                                </div>
                                <div class="condition-info" style="margin-top: 8px; font-size: 0.9rem; color: #6b7280;">
                                    <span id="modalCondition">${selectedCondition.charAt(0).toUpperCase() + selectedCondition.slice(1)} Condition</span>
                                </div>
                            </div>
                        </div>
                        <div class="quantity-controls">
                            <label>Quantity:</label>
                            <div class="quantity-input-group">
                                <button class="quantity-btn minus" onclick="updateEnhancedModalQuantity(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="modalQuantity" value="1" min="1" max="99" readonly>
                                <button class="quantity-btn plus" onclick="updateEnhancedModalQuantity(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="total-price">
                            <strong>Total: GH₵ <span id="modalTotal">${selectedPrice.toFixed(2)}</span></strong>
                        </div>
                    </div>
                    <div class="cart-modal-footer">
                        <button class="btn btn-secondary" onclick="closeEnhancedAddToCartModal()">Cancel</button>
                        <button class="btn btn-primary" onclick="confirmEnhancedAddToCart(${productId})" id="confirmAddBtn">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Store modal data
            window.enhancedModalData = {
                productId: productId,
                productName: productName,
                unitPrice: selectedPrice,
                quantity: 1,
                condition: selectedCondition
            };

            // Show modal with animation
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function updateEnhancedModalQuantity(change) {
            const quantityInput = document.getElementById('modalQuantity');
            const totalElement = document.getElementById('modalTotal');

            if (!quantityInput || !window.enhancedModalData) return;

            let newQuantity = window.enhancedModalData.quantity + change;

            // Enforce minimum of 1 and maximum of 99
            if (newQuantity < 1) newQuantity = 1;
            if (newQuantity > 99) newQuantity = 99;

            window.enhancedModalData.quantity = newQuantity;
            quantityInput.value = newQuantity;

            // Update total price
            const total = (window.enhancedModalData.unitPrice * newQuantity).toFixed(2);
            totalElement.textContent = total;

            // Add visual feedback
            quantityInput.style.transform = 'scale(1.1)';
            setTimeout(() => quantityInput.style.transform = 'scale(1)', 200);
        }

        function confirmEnhancedAddToCart(productId) {
            if (!window.enhancedModalData) return;

            const confirmBtn = document.getElementById('confirmAddBtn');
            const originalText = confirmBtn.innerHTML;

            // Show loading state
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            confirmBtn.disabled = true;

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', window.enhancedModalData.quantity);
            formData.append('condition', window.enhancedModalData.condition);
            formData.append('final_price', window.enhancedModalData.unitPrice);

            fetch('../actions/add_to_cart_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success animation
                        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Added!';
                        confirmBtn.classList.add('btn-success');
                        confirmBtn.classList.remove('btn-primary');

                        updateCartBadge(data.cart_count);

                        // Show success notification with quantity info
                        showNotification(`Added ${window.enhancedModalData.quantity} item(s) to cart successfully!`, 'success');

                        // Close modal after delay
                        setTimeout(() => {
                            closeEnhancedAddToCartModal();
                        }, 1500);
                    } else {
                        confirmBtn.innerHTML = originalText;
                        confirmBtn.disabled = false;
                        showNotification(data.message || 'Failed to add product to cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    confirmBtn.innerHTML = originalText;
                    confirmBtn.disabled = false;
                    showNotification('An error occurred. Please try again.', 'error');
                });
        }

        function closeEnhancedAddToCartModal() {
            const modal = document.getElementById('addToCartModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
            window.enhancedModalData = null;
        }

        // New add to cart function for condition-based pricing
        function addToCartWithCondition(productId) {
            console.log('Add to cart called with:', {
                productId: productId,
                selectedCondition: selectedCondition,
                selectedPrice: selectedPrice,
                stockQuantity: stockQuantity
            });

            // Check if product is out of stock
            if (stockQuantity <= 0) {
                Swal.fire({
                    title: 'Out of Stock!',
                    text: 'Sorry, this product is currently out of stock. Please check back later or contact us for availability.',
                    icon: 'warning',
                    iconColor: '#f59e0b',
                    confirmButtonText: 'Understood',
                    confirmButtonColor: '#4f46e5',
                    background: '#ffffff',
                    color: '#1f2937',
                    customClass: {
                        popup: 'swal-out-of-stock-popup',
                        title: 'swal-out-of-stock-title',
                        content: 'swal-out-of-stock-content'
                    }
                });
                return;
            }

            if (!selectedCondition || selectedPrice <= 0) {
                console.error('Invalid selection:', {
                    selectedCondition,
                    selectedPrice
                });
                showNotification('Please select a condition first', 'error');
                return;
            }

            const btn = document.getElementById('addToCartBtn');
            if (!btn) {
                showNotification('Add to cart button not found', 'error');
                return;
            }

            const originalText = btn.innerHTML;

            // Show loading state
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('condition', selectedCondition);
            formData.append('final_price', selectedPrice);

            fetch('../actions/add_to_cart_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = '<i class="fas fa-check"></i> Added Successfully!';
                        btn.style.background = '#10b981';

                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.style.background = 'white';
                            btn.disabled = false;
                        }, 2500);

                        // Show SweetAlert cart popup positioned on the right side
                        showSweetCartPopup(data);
                        updateCartBadge(data.cart_count);
                    } else {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        showNotification(data.message || 'Failed to add product to cart', 'error');
                    }
                })
                .catch(error => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    showNotification('An error occurred. Please try again.', 'error');
                });
        }


        function showCart() {
            window.location.href = 'cart.php';
        }

        // Update cart badge (shared with cart.js)
        function updateCartBadge(count) {
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                if (count > 0) {
                    cartBadge.textContent = count;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }

        // Show notification (simple toast)
        function showNotification(message, type = 'info') {
            const existing = document.querySelector('.notification-toast');
            if (existing) existing.remove();
            const notification = document.createElement('div');
            notification.className = `notification-toast alert alert-${type} position-fixed`;
            notification.style.cssText = `
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 2000);
        }

        function shareProduct(platform) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);

            let shareUrl = '';

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${title}%20${url}`;
                    break;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }

        // Gallery Image Loading System
        let productImages = [];
        let currentImageIndex = 0;

        function loadProductImage() {
            const img = document.querySelector('.main-product-image');
            if (!img) {
                // Fallback for old product-image class
                const oldImg = document.querySelector('.product-image');
                if (oldImg) {
                    loadSingleImage(oldImg);
                    return;
                }
            }

            const productId = img.getAttribute('data-product-id');
            const productTitle = img.getAttribute('data-product-title');

            // Load all product images from new gallery system
            fetch(`actions/upload_product_image_action.php?action=get_product_gallery&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.images && data.images.length > 0) {
                        productImages = data.images.map(image => ({
                            url: image.url,
                            filename: image.filename,
                            alt_text: image.alt_text,
                            is_primary: image.is_primary,
                            order: image.order
                        }));
                    } else {
                        // Fallback to product's main image or placeholder
                        const productImage = img.getAttribute('data-product-image');
                        if (productImage && productImage !== '' && productImage !== 'null') {
                            const mainImageUrl = img.src; // Use the already processed image URL
                            productImages = [{
                                url: mainImageUrl,
                                filename: productImage,
                                alt_text: productTitle,
                                is_primary: true,
                                order: 0
                            }];
                        } else {
                            // Use placeholder if no image available
                            const placeholderUrl = generatePlaceholderUrl(productTitle, '600x400');
                            productImages = [{
                                url: placeholderUrl,
                                filename: 'placeholder',
                                alt_text: productTitle,
                                is_primary: true,
                                order: 0
                            }];
                        }
                    }

                    // Sort images: primary first, then by order
                    productImages.sort((a, b) => {
                        if (a.is_primary && !b.is_primary) return -1;
                        if (!a.is_primary && b.is_primary) return 1;
                        return (a.order || 0) - (b.order || 0);
                    });

                    updateGalleryDisplay();
                })
                .catch(error => {
                    console.log('Gallery load error - using fallback:', error);
                    // Try to use the main image first, then placeholder
                    const productImage = img.getAttribute('data-product-image');
                    if (productImage && productImage !== '' && productImage !== 'null') {
                        const mainImageUrl = img.src; // Use the already processed image URL
                        productImages = [{
                            url: mainImageUrl,
                            filename: productImage,
                            alt_text: productTitle,
                            is_primary: true,
                            order: 0
                        }];
                    } else {
                        // Use placeholder if no image available
                        const placeholderUrl = generatePlaceholderUrl(productTitle, '600x400');
                        productImages = [{
                            url: placeholderUrl,
                            filename: 'placeholder',
                            alt_text: productTitle,
                            is_primary: true,
                            order: 0
                        }];
                    }
                    updateGalleryDisplay();
                });
        }

        function loadSingleImage(img) {
            // Fallback function for compatibility with old single image structure
            const heroImg = document.querySelector('.product-hero-image');
            const productId = img.getAttribute('data-product-id');
            const productTitle = img.getAttribute('data-product-title');

            fetch(`actions/upload_product_image_action.php?action=get_image_url&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.url) {
                        img.src = data.url;
                        if (heroImg) heroImg.src = data.url;
                    } else {
                        const placeholderUrl = generatePlaceholderUrl(productTitle, '600x400');
                        img.src = placeholderUrl;
                        if (heroImg) heroImg.src = placeholderUrl;
                    }
                });
        }

        function updateGalleryDisplay() {
            const mainImg = document.querySelector('.main-product-image');
            const thumbnailList = document.querySelector('#thumbnailList');

            if (!mainImg || !thumbnailList || productImages.length === 0) return;

            // Set main image
            const currentImage = productImages[currentImageIndex];
            mainImg.src = currentImage.url;
            mainImg.alt = currentImage.alt_text || 'Product Image';

            // Update thumbnails (always show thumbnails, even for single image for consistency)
            let thumbnailsHtml = '';
            productImages.forEach((image, index) => {
                const activeClass = index === currentImageIndex ? 'active' : '';
                const fallbackUrl = generatePlaceholderUrl(image.alt_text || 'Product Image', '80x80');
                thumbnailsHtml += `
                    <div class="thumbnail-item ${activeClass}" onclick="selectImage(${index})" title="${image.alt_text || 'Product Image'}">
                        <img src="${image.url}" alt="${image.alt_text || 'Product Image'}" loading="lazy" onerror="this.onerror=null; this.src='${fallbackUrl}';">
                    </div>
                `;
            });
            thumbnailList.innerHTML = thumbnailsHtml;

            // Show/hide navigation arrows based on number of images
            const leftArrow = document.querySelector('.gallery-arrow-left');
            const rightArrow = document.querySelector('.gallery-arrow-right');

            if (productImages.length > 1) {
                if (leftArrow) leftArrow.style.display = 'flex';
                if (rightArrow) rightArrow.style.display = 'flex';

                // Update arrow icons for better UX
                if (leftArrow) leftArrow.querySelector('i').className = 'fas fa-chevron-left';
                if (rightArrow) rightArrow.querySelector('i').className = 'fas fa-chevron-right';
            } else {
                if (leftArrow) leftArrow.style.display = 'none';
                if (rightArrow) rightArrow.style.display = 'none';
            }

            // Add image counter indicator if multiple images
            updateImageCounter();
        }

        function updateImageCounter() {
            if (productImages.length <= 1) return;

            // Add or update image counter
            let counter = document.querySelector('.image-counter');
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'image-counter';
                counter.style.cssText = `
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 6px 12px;
                    border-radius: 15px;
                    font-size: 0.8rem;
                    font-weight: 600;
                    z-index: 20;
                `;
                document.querySelector('.main-image-container').appendChild(counter);
            }

            counter.textContent = `${currentImageIndex + 1} / ${productImages.length}`;
        }

        function selectImage(index) {
            if (index >= 0 && index < productImages.length) {
                currentImageIndex = index;
                updateGalleryDisplay();
            }
        }

        function previousImage() {
            if (productImages.length > 1) {
                currentImageIndex = currentImageIndex > 0 ? currentImageIndex - 1 : productImages.length - 1;
                updateGalleryDisplay();
            }
        }

        function nextImage() {
            if (productImages.length > 1) {
                currentImageIndex = currentImageIndex < productImages.length - 1 ? currentImageIndex + 1 : 0;
                updateGalleryDisplay();
            }
        }

        function generatePlaceholderUrl(text, size = '600x400') {
            const [width, height] = size.split('x').map(Number);
            const safeText = (text || 'Gadget Garage').substring(0, 32).replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
                <rect width="100%" height="100%" fill="#8b5fbf"/>
                <rect x="1" y="1" width="${width - 2}" height="${height - 2}" fill="none" stroke="#6c3fb6" stroke-width="2"/>
                <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="${Math.max(Math.floor(height * 0.12), 16)}" fill="#ffffff" text-anchor="middle" dominant-baseline="middle">${safeText}</text>
            </svg>`;
            return `data:image/svg+xml;base64,${btoa(unescape(encodeURIComponent(svg)))}`;
        }

        // Condition-based pricing configuration
        const categoryPricing = {
            'Smartphones': { // Mobile Devices/Smartphones
                'excellent': 0,
                'good': 2000,
                'fair': 3500
            },
            'Mobile Devices': { // Alternative name for smartphones
                'excellent': 0,
                'good': 2000,
                'fair': 3500
            },
            'Tablets': { // iPads/Tablets
                'excellent': 0,
                'good': 1800,
                'fair': 2500
            },
            'iPads': { // Alternative name for tablets
                'excellent': 0,
                'good': 1800,
                'fair': 2500
            },
            'Laptops': {
                'excellent': 0,
                'good': 3000,
                'fair': 3400
            },
            'Computing': { // Alternative name for laptops
                'excellent': 0,
                'good': 3000,
                'fair': 3400
            },
            'Desktops': {
                'excellent': 0,
                'good': 2000,
                'fair': 2300
            },
            'Cameras': { // Photography & Video/Cameras
                'excellent': 0,
                'good': 1000,
                'fair': 2000
            },
            'Photography & Video': { // Alternative name for cameras
                'excellent': 0,
                'good': 1000,
                'fair': 2000
            },
            'Video Equipment': {
                'excellent': 0,
                'good': 1500,
                'fair': 3000
            },
            'default': { // Default for any other category
                'excellent': 0,
                'good': 1000,
                'fair': 2000
            }
        };

        // Get product data
        const productCategory = '<?php echo addslashes($product['cat_name']); ?>';

        // Price calculation function
        function calculatePrice(condition) {
            const categoryKey = categoryPricing[productCategory] ? productCategory : 'default';
            const discount = categoryPricing[categoryKey][condition];
            const finalPrice = originalPrice - discount;

            return {
                finalPrice: Math.max(finalPrice, 0), // Ensure price doesn't go negative
                discount: discount
            };
        }

        // Update price display
        function updatePriceDisplay(condition) {
            const priceData = calculatePrice(condition);
            const displayPrice = document.getElementById('displayPrice');
            const priceBreakdown = document.getElementById('priceBreakdown');
            const originalPriceSpan = document.getElementById('originalPrice');
            const discountAmount = document.getElementById('discountAmount');

            displayPrice.textContent = `GH₵ ${priceData.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            if (priceData.discount > 0) {
                priceBreakdown.style.display = 'block';
                originalPriceSpan.textContent = `GH₵ ${originalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                discountAmount.textContent = `Discount: -GH₵ ${priceData.discount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            } else {
                priceBreakdown.style.display = 'none';
            }
        }

        // Initialize condition selection for the new design (duplicate removal)
        // This function is now handled above

        // Clean magnifying glass initialization

        // Initialize page components
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 DOM Content Loaded');

            // Load product image first
            loadProductImage();

            // Initialize magnifying glass after image loads
            const mainImage = document.getElementById('mainProductImage');
            if (mainImage) {
                if (mainImage.complete) {
                    console.log('📷 Image already loaded, initializing magnifier');
                    initializeMagnifyingGlass();
                } else {
                    console.log('📷 Waiting for image to load...');
                    mainImage.onload = function() {
                        console.log('📷 Image loaded, initializing magnifier');
                        initializeMagnifyingGlass();
                    };
                    // Fallback timeout
                    setTimeout(() => {
                        console.log('📷 Fallback timeout, initializing magnifier anyway');
                        initializeMagnifyingGlass();
                    }, 2000);
                }
            } else {
                console.error('❌ Main image not found');
            }


            // Initialize condition-based pricing
            console.log('Initializing condition selection');
            initializeConditionSelection();

            // Add event listeners for condition selection
            const conditionOptions = document.querySelectorAll('[data-condition]');
            console.log('Found condition options:', conditionOptions.length);

            conditionOptions.forEach((option, index) => {
                const condition = option.getAttribute('data-condition');

                console.log(`Setting up listeners for option ${index + 1}:`, condition);

                // Visual feedback on hover
                option.addEventListener('mouseenter', function() {
                    if (selectedCondition !== condition) {
                        this.style.background = 'rgba(255,255,255,0.2)';
                    }
                });

                option.addEventListener('mouseleave', function() {
                    if (selectedCondition !== condition) {
                        this.style.background = 'rgba(255,255,255,0.1)';
                    }
                });

                // Add click listener as backup for onclick handler
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Click event fired for condition:', condition);
                    if (typeof window.selectCondition === 'function') {
                        window.selectCondition(condition);
                    } else {
                        console.error('selectCondition function not available');
                    }
                });
            });

            // Animate product details on load
            const productDetails = document.querySelector('.product-details');
            if (productDetails) {
                productDetails.style.opacity = '0';
                productDetails.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    productDetails.style.transition = 'all 0.6s ease';
                    productDetails.style.opacity = '1';
                    productDetails.style.transform = 'translateY(0)';
                }, 200);
            }

        });

        // Live chat functionality
        function toggleLiveChat() {
            const chatPanel = document.getElementById('chatPanel');
            chatPanel.classList.toggle('active');
        }

        function sendChatMessage() {
            const chatInput = document.querySelector('.chat-input');
            const chatBody = document.querySelector('.chat-body');
            const message = chatInput.value.trim();

            if (message) {
                const userMessage = document.createElement('div');
                userMessage.className = 'chat-message user';
                userMessage.innerHTML = `<p style="background: #000000; color: white; padding: 12px 16px; border-radius: 18px; margin: 0; font-size: 0.9rem; text-align: right;">${message}</p>`;
                chatBody.appendChild(userMessage);

                chatInput.value = '';

                setTimeout(() => {
                    const botMessage = document.createElement('div');
                    botMessage.className = 'chat-message bot';
                    botMessage.innerHTML = `<p>Thank you! Let me help you with this product. Our team will assist you shortly.</p>`;
                    chatBody.appendChild(botMessage);
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 1000);

                chatBody.scrollTop = chatBody.scrollHeight;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.querySelector('.chat-input');
            const chatSend = document.querySelector('.chat-send');

            if (chatInput && chatSend) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendChatMessage();
                    }
                });

                chatSend.addEventListener('click', sendChatMessage);
            }
        });

        // Dropdown navigation functions with timeout delays
        let dropdownTimeout;
        let shopDropdownTimeout;
        let moreDropdownTimeout;
        let userDropdownTimeout;

        function showDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideDropdown() {
            const dropdown = document.getElementById('shopDropdown');
            if (dropdown) {
                clearTimeout(dropdownTimeout);
                dropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        function showShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                shopDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        // Make More dropdown functions globally available for inline handlers
        window.showMoreDropdown = function() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) {
                clearTimeout(moreDropdownTimeout);
                dropdown.classList.add('show');
            }
        };

        window.hideMoreDropdown = function() {
            const dropdown = document.getElementById('moreDropdown');
            if (dropdown) {
                clearTimeout(moreDropdownTimeout);
                moreDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        };

        function showUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            if (dropdown) {
                clearTimeout(userDropdownTimeout);
                userDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        // Enhanced dropdown behavior
        document.addEventListener('DOMContentLoaded', function() {
            const shopCategoriesBtn = document.querySelector('.shop-categories-btn');
            const brandsDropdown = document.getElementById('shopDropdown');

            if (shopCategoriesBtn && brandsDropdown) {
                shopCategoriesBtn.addEventListener('mouseenter', showDropdown);
                shopCategoriesBtn.addEventListener('mouseleave', hideDropdown);
                brandsDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(dropdownTimeout);
                });
                brandsDropdown.addEventListener('mouseleave', hideDropdown);
            }

            const userAvatar = document.querySelector('.user-avatar');
            const userDropdown = document.getElementById('userDropdownMenu');

            if (userAvatar && userDropdown) {
                userAvatar.addEventListener('mouseenter', showUserDropdown);
                userAvatar.addEventListener('mouseleave', hideUserDropdown);
                userDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(userDropdownTimeout);
                });
                userDropdown.addEventListener('mouseleave', hideUserDropdown);
            }
        });

        // Timer functionality
        function updateTimer() {
            const timerElement = document.getElementById('promoTimer');
            if (timerElement) {
                const now = new Date().getTime();
                const nextDay = new Date();
                nextDay.setDate(nextDay.getDate() + 1);
                nextDay.setHours(0, 0, 0, 0);

                const distance = nextDay.getTime() - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                timerElement.innerHTML = days + "d:" +
                    (hours < 10 ? "0" : "") + hours + "h:" +
                    (minutes < 10 ? "0" : "") + minutes + "m:" +
                    (seconds < 10 ? "0" : "") + seconds + "s";
            }
        }

        // Update timer every second
        setInterval(updateTimer, 1000);
        updateTimer(); // Initial call

        // User dropdown functionality
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdownMenu');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdownMenu');
            const avatar = document.querySelector('.user-avatar');

            if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Account page navigation
        function goToAccount() {
            window.location.href = '../views/account.php';
        }

        // Language change functionality
        function changeLanguage(lang) {
            console.log('Language changed to:', lang);
        }

        // Theme toggle functionality
        function toggleTheme() {
            const toggleSwitch = document.getElementById('themeToggle');
            const body = document.body;

            body.classList.toggle('dark-mode');
            toggleSwitch.classList.toggle('active');

            const isDarkMode = body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
        }

        // Load theme preference on page load
        document.addEventListener('DOMContentLoaded', function() {
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            const toggleSwitch = document.getElementById('themeToggle');

            if (isDarkMode) {
                document.body.classList.add('dark-mode');
                if (toggleSwitch) {
                    toggleSwitch.classList.add('active');
                }
            }
        });

        // Shop Dropdown Functions
        function showShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                dropdown.classList.add('show');
            }
        }

        function hideShopDropdown() {
            const dropdown = document.getElementById('shopCategoryDropdown');
            if (dropdown) {
                clearTimeout(shopDropdownTimeout);
                shopDropdownTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            }
        }

        // Timeout variables (duplicate removed - already defined above)
        let shopDropdownTimeout;
        let moreDropdownTimeout;

        // SweetAlert Cart Popup - positioned on right side under navbar
        function showSweetCartPopup(data) {
            console.log('showSweetCartPopup called with data:', data);

            // Check if SweetAlert is available
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not loaded');
                alert('Item added to cart successfully!'); // Fallback
                return;
            }

            // Get product image - try multiple methods
            let productImage = '';

            // Method 1: From response data
            if (data.product_image) {
                productImage = data.product_image;
            }

            // Method 2: From main product image element
            if (!productImage) {
                const productImageElement = document.querySelector('.main-product-image');
                if (productImageElement && productImageElement.src) {
                    productImage = productImageElement.src;
                }
            }

            // Method 3: From data attribute
            if (!productImage) {
                const productImageElement = document.querySelector('.main-product-image');
                if (productImageElement) {
                    const dataImage = productImageElement.getAttribute('data-product-image');
                    if (dataImage) {
                        // Build full URL if needed
                        productImage = dataImage.startsWith('http') ? dataImage : 'http://169.239.251.102:442/~chelsea.somuah/uploads/' + dataImage;
                    }
                }
            }

            // Method 4: Generate placeholder if still no image
            if (!productImage) {
                const productName = data.product_name || 'Product';
                productImage = generatePlaceholderUrl(productName, '60x60');
            }

            console.log('Product image resolved to:', productImage);

            // Create cart state display
            const cartStateHTML = `
                <div style="display: flex; gap: 12px; align-items: flex-start; text-align: left;">
                    <img src="${productImage}" alt="${data.product_name}"
                         style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 2px solid #e2e8f0;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #1f2937;">${data.product_name}</h4>
                        ${data.condition ? `<span style="background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-right: 6px;">${data.condition}</span>` : ''}
                        <span style="background: #f3e8ff; color: #7c3aed; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600;">Qty: ${data.quantity || 1}</span>
                        <div style="margin-top: 8px; font-size: 16px; font-weight: 700; color: #059669;">GH₵${parseFloat(data.final_price || data.product_price).toLocaleString()}</div>
                    </div>
                </div>
                <hr style="margin: 16px 0; border: 1px solid #e2e8f0;">
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #6b7280; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-shopping-bag" style="color: #8b5cf6;"></i> Cart Items
                        </span>
                        <span style="font-weight: 600; color: #374151;">${data.cart_count || 0}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #6b7280;">Subtotal</span>
                        <span style="font-weight: 700; color: #059669; font-size: 16px;">GH₵${parseFloat(data.cart_total || '0').toLocaleString()}</span>
                    </div>
                    ${parseFloat(data.cart_total || '0') > 200 ? '<div style="margin-top: 8px; padding: 6px; background: #dcfdf7; color: #065f46; border-radius: 6px; font-size: 12px; font-weight: 600; text-align: center;"><i class="fas fa-shipping-fast"></i> Free Standard Shipping Earned!</div>' : ''}
                </div>
            `;

            try {
                Swal.fire({
                    title: '<i class="fas fa-check-circle" style="color: #10b981;"></i> Added to Cart!',
                    html: cartStateHTML,
                    width: 420,
                    position: 'top-end',
                    toast: false,
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-shopping-cart"></i> View Cart (' + (data.cart_count || 0) + ')',
                    cancelButtonText: 'Continue Shopping',
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#6b7280',
                    timer: 8000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'swal-cart-popup',
                        title: 'swal-cart-title',
                        htmlContainer: 'swal-cart-content'
                    },
                    didOpen: (popup) => {
                        // Position the popup under the navbar
                        popup.style.marginTop = '80px'; // Adjust based on your navbar height
                        popup.style.marginRight = '20px';
                    },
                    willClose: () => {
                        // Optional: Add any cleanup here
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Go to cart page
                        window.location.href = '../views/cart.php';
                    }
                    // If cancelled or auto-closed, user continues shopping
                });

                // Add custom CSS for better styling
                const customStyle = document.createElement('style');
                customStyle.textContent = `
                .swal-cart-popup {
                    border-radius: 16px !important;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
                    border: 1px solid #e2e8f0 !important;
                }
                .swal-cart-title {
                    font-size: 18px !important;
                    font-weight: 600 !important;
                    color: #1f2937 !important;
                    margin-bottom: 16px !important;
                }
                .swal-cart-content {
                    font-size: 14px !important;
                    line-height: 1.5 !important;
                }
                .swal2-confirm {
                    border-radius: 8px !important;
                    font-weight: 600 !important;
                    padding: 10px 20px !important;
                }
                .swal2-cancel {
                    border-radius: 8px !important;
                    font-weight: 600 !important;
                    padding: 10px 20px !important;
                }
                .swal2-timer-progress-bar {
                    background: #4f46e5 !important;
                }

                /* Out of Stock Alert Styles */
                .swal-out-of-stock-popup {
                    border-radius: 20px !important;
                    box-shadow: 0 25px 80px rgba(245, 158, 11, 0.15) !important;
                    border: 2px solid #fbbf24 !important;
                }
                .swal-out-of-stock-title {
                    font-size: 24px !important;
                    font-weight: 700 !important;
                    color: #92400e !important;
                    margin-bottom: 20px !important;
                }
                .swal-out-of-stock-content {
                    font-size: 16px !important;
                    line-height: 1.6 !important;
                    color: #374151 !important;
                }
            `;
                document.head.appendChild(customStyle);

                // Remove the style after the popup is closed to avoid accumulation
                setTimeout(() => {
                    if (customStyle.parentNode) {
                        customStyle.remove();
                    }
                }, 10000);

            } catch (error) {
                console.error('Error showing SweetAlert popup:', error);
                // Fallback notification
                alert(`Added "${data.product_name}" to cart! Cart total: GH₵${parseFloat(data.cart_total || '0').toLocaleString()}`);
            }
        }

        // Test SweetAlert function for debugging
        function testSweetAlert() {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Test', 'SweetAlert is working!', 'success');
            } else {
                console.error('SweetAlert not available');
            }
        }

        // Test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, SweetAlert available:', typeof Swal !== 'undefined');
        });
    </script>

    <style>
        /* Cart Popup Styles */
        .cart-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .cart-popup-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .cart-popup {
            background: white;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.8);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .cart-popup-overlay.show .cart-popup {
            transform: scale(1);
        }

        .cart-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .cart-popup-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .cart-popup-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.2s ease;
        }

        .cart-popup-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .cart-popup-body {
            padding: 20px;
        }

        .added-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .item-details h4 {
            margin: 0 0 8px 0;
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .item-specs {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .condition-badge {
            background: #3b82f6;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .quantity-badge {
            background: #6b7280;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .item-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #10b981;
        }

        .cart-summary {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }

        .cart-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .cart-count {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-weight: 500;
        }

        .subtotal {
            color: #1f2937;
        }

        .shipping-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-align: center;
            font-weight: 500;
        }

        .cart-popup-footer {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 0 0 12px 12px;
        }

        .cart-popup-footer .btn {
            flex: 1;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-outline {
            background: white;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }

        .btn-outline:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
        }

        /* Footer Styles */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 0;
        }

        .footer-brand {
            margin-bottom: 30px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo img {
            height: 50px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .footer-logo .garage {
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .footer-description {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social-link:hover {
            background: #2563EB;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 24px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 14px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #2563EB;
            transform: translateX(4px);
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 40px 0 20px;
        }

        .footer-bottom {
            padding-top: 20px;
        }

        .copyright {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }

        /* Newsletter Signup Section */
        .newsletter-signup-section {
            background: transparent;
            padding: 0;
            text-align: left;
            max-width: 100%;
            height: fit-content;
        }

        .newsletter-title {
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .newsletter-form {
            display: flex;
            width: 100%;
            margin: 0 0 15px 0;
            gap: 0;
            border-radius: 50px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .newsletter-input {
            flex: 1;
            padding: 14px 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1a1a1a;
            background: #e5e7eb;
        }

        .newsletter-input::placeholder {
            color: #6b7280;
        }

        .newsletter-submit-btn {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border: none;
            background: #9ca3af;
            color: #ffffff;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            padding: 0;
        }

        .newsletter-submit-btn:hover {
            background: #6b7280;
            transform: scale(1.05);
        }

        .newsletter-disclaimer {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 8px 0 0 0;
            text-align: left;
        }

        .newsletter-disclaimer a {
            color: #2563EB;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .newsletter-disclaimer a:hover {
            color: #1d4ed8;
        }

        @media (max-width: 991px) {
            .newsletter-signup-section {
                margin-top: 20px;
            }
        }

        /* Live Chat Widget */
        .live-chat-widget {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
        }

        .chat-trigger {
            width: 60px;
            height: 60px;
            background: #000000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .chat-trigger:hover {
            background: #374151;
            transform: scale(1.1);
        }

        .chat-panel {
            position: absolute;
            bottom: 80px;
            left: 0;
            width: 350px;
            height: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            display: none;
            flex-direction: column;
        }

        .chat-panel.active {
            display: flex;
        }

        .chat-header {
            padding: 16px 20px;
            background: #000000;
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
        }

        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .chat-message {
            margin-bottom: 16px;
        }

        .chat-message.bot p {
            background: #f3f4f6;
            padding: 12px 16px;
            border-radius: 18px;
            margin: 0;
            color: #374151;
            font-size: 0.9rem;
        }

        .chat-footer {
            padding: 16px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 25px;
            outline: none;
            font-size: 0.9rem;
        }

        .chat-input:focus {
            border-color: #000000;
        }

        .chat-send {
            width: 40px;
            height: 40px;
            background: #000000;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .chat-send:hover {
            background: #374151;
        }

        @media (max-width: 768px) {
            .chat-panel {
                width: calc(100vw - 40px);
                height: 400px;
            }

            .live-chat-widget {
                bottom: 15px;
                left: 15px;
            }
        }

        /* Related Products Section */
        .related-products-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            margin-top: 60px;
        }

        .related-products-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .related-products-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .related-products-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 2px;
        }

        .related-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .related-product-card {
            background: var(--pure-white);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 16px var(--shadow);
            transition: all 0.4s ease;
            cursor: pointer;
            border: 1px solid var(--border-light);
            position: relative;
            width: 100%;
            min-height: 450px;
        }

        .related-product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 32px var(--shadow-hover);
            border-color: var(--royal-blue);
        }

        .related-product-image-container {
            position: relative;
            width: 100%;
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        .related-product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.4s ease;
        }

        .related-product-card:hover .related-product-image {
            transform: scale(1.1);
        }

        .related-product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gradient-primary);
            color: var(--pure-white);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            box-shadow: 0 2px 8px var(--shadow);
        }

        .related-product-content {
            padding: 25px;
            position: relative;
            z-index: 2;
        }

        .related-product-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #000000;
            margin-bottom: 15px;
        }

        .related-product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .related-meta-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            font-size: 0.85rem;
            color: #000000;
            font-weight: 500;
        }

        .related-add-to-cart-btn {
            width: 100%;
            padding: 15px;
            background: var(--gradient-primary);
            color: var(--pure-white);
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        .related-add-to-cart-btn:hover {
            background: linear-gradient(135deg, var(--royal-blue) 0%, var(--navy-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--shadow-hover);
        }

        @media (max-width: 768px) {
            .related-products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .related-products-title {
                font-size: 2rem;
            }
        }
    </style>

    <!-- Related Products Section -->
    <?php if (!empty($related_products)): ?>
        <section class="related-products-section">
            <div class="container-fluid">
                <div class="related-products-header">
                    <h2 class="related-products-title">Top Picks for You</h2>
                </div>
                <div class="related-products-grid">
                    <?php foreach ($related_products as $related_product):
                        $related_image_url = get_product_image_url($related_product['product_image'] ?? '', $related_product['product_title'] ?? 'Product', '400x300');
                        $related_fallback_url = generate_placeholder_url($related_product['product_title'] ?? 'Product', '400x300');
                    ?>
                        <div class="related-product-card" onclick="window.location.href='single_product.php?id=<?php echo $related_product['product_id']; ?>'">
                            <div class="related-product-image-container">
                                <img src="<?php echo htmlspecialchars($related_image_url); ?>"
                                    alt="<?php echo htmlspecialchars($related_product['product_title']); ?>"
                                    class="related-product-image"
                                    data-product-id="<?php echo $related_product['product_id']; ?>"
                                    data-product-image="<?php echo htmlspecialchars($related_product['product_image'] ?? ''); ?>"
                                    data-product-title="<?php echo htmlspecialchars($related_product['product_title']); ?>"
                                    onerror="this.src='<?php echo htmlspecialchars($related_fallback_url); ?>'; this.onerror=null;">
                                <div class="related-product-badge">New</div>
                            </div>
                            <div class="related-product-content">
                                <h5 class="related-product-title"><?php echo htmlspecialchars($related_product['product_title']); ?></h5>
                                <div class="related-product-price">GH₵ <?php echo number_format(floatval($related_product['product_price']), 2); ?></div>
                                <div class="related-product-meta">
                                    <span class="related-meta-tag">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($related_product['cat_name'] ?? 'N/A'); ?>
                                    </span>
                                    <span class="related-meta-tag">
                                        <i class="fas fa-store"></i>
                                        <?php echo htmlspecialchars($related_product['brand_name'] ?? 'N/A'); ?>
                                    </span>
                                </div>
                                <button class="related-add-to-cart-btn" onclick="event.stopPropagation(); showAddToCartModal(<?php echo $related_product['product_id']; ?>, '<?php echo addslashes($related_product['product_title']); ?>', <?php echo $related_product['product_price']; ?>, '<?php echo htmlspecialchars($related_image_url); ?>')">
                                    <i class="fas fa-shopping-cart"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row align-items-start">
                    <!-- First Column: Logo and Social -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-brand">
                            <div class="footer-logo" style="margin-bottom: 20px;">
                                <img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
                                    alt="Gadget Garage">
                            </div>
                            <p class="footer-description">Your trusted partner for premium tech devices, expert repairs, and innovative solutions.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- Navigation Links -->
                    <div class="col-lg-5 col-md-12">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Get Help</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Help Center</a></li>
                                    <li><a href="contact.php">Track Order</a></li>
                                    <li><a href="terms_conditions.php">Shipping Info</a></li>
                                    <li><a href="terms_conditions.php">Returns</a></li>
                                    <li><a href="contact.php">Contact Us</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Company</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Careers</a></li>
                                    <li><a href="contact.php">About</a></li>
                                    <li><a href="contact.php">Stores</a></li>
                                    <li><a href="contact.php">Want to Collab?</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <h5 class="footer-title">Quick Links</h5>
                                <ul class="footer-links">
                                    <li><a href="contact.php">Size Guide</a></li>
                                    <li><a href="contact.php">Sitemap</a></li>
                                    <li><a href="contact.php">Gift Cards</a></li>
                                    <li><a href="contact.php">Check Gift Card Balance</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Right Side: Email Signup Form -->
                    <div class="col-lg-4 col-md-12 mb-4">
                        <div class="newsletter-signup-section">
                            <h3 class="newsletter-title">SIGN UP FOR DISCOUNTS + UPDATES</h3>
                            <form class="newsletter-form" id="newsletterForm">
                                <input type="text" class="newsletter-input" placeholder="Phone Number or Email" required>
                                <button type="submit" class="newsletter-submit-btn">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                            <p class="newsletter-disclaimer">
                                By signing up for email, you agree to Gadget Garage's <a href="terms_conditions.php">Terms of Service</a> and <a href="legal.php">Privacy Policy</a>.
                            </p>
                            <p class="newsletter-disclaimer">
                                By submitting your phone number, you agree to receive recurring automated promotional and personalized marketing text messages (e.g. cart reminders) from Gadget Garage at the cell number used when signing up. Consent is not a condition of any purchase. Reply HELP for help and STOP to cancel. Msg frequency varies. Msg & data rates may apply. <a href="terms_conditions.php">View Terms</a> & <a href="legal.php">Privacy</a>.
                            </p>
                        </div>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-12 text-center">
                            <p class="copyright">&copy; 2024 Gadget Garage. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Live Chat Widget -->
    <div class="live-chat-widget" id="liveChatWidget">
        <div class="chat-trigger" onclick="toggleLiveChat()">
            <i class="fas fa-comments"></i>
        </div>
        <div class="chat-panel" id="chatPanel">
            <div class="chat-header">
                <h4>Live Chat</h4>
                <button class="chat-close" onclick="toggleLiveChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chat-body">
                <div class="chat-message bot">
                    <p>Hi! Interested in this product? I'd be happy to help you with any questions!</p>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" class="chat-input" placeholder="Ask about this product...">
                <button class="chat-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

<!-- EMERGENCY STANDALONE CONDITION SELECTION SCRIPT -->
<script type="text/javascript">
console.log('=== EMERGENCY SCRIPT LOADING ===');

// Simple condition selection
function selectCondition(condition) {
    console.log('selectCondition called:', condition);

    // Simple visual feedback
    var allOptions = document.querySelectorAll('[data-condition]');
    console.log('Found condition options:', allOptions.length);

    for (var i = 0; i < allOptions.length; i++) {
        allOptions[i].style.background = 'rgba(255,255,255,0.1)';
        allOptions[i].style.border = 'none';
    }

    var selectedOption = document.querySelector('[data-condition="' + condition + '"]');
    if (selectedOption) {
        selectedOption.style.background = 'rgba(255,255,255,0.3)';
        selectedOption.style.border = '2px solid #10b981';
        console.log('Applied selected styling to:', condition);
    } else {
        console.log('Could not find option for:', condition);
    }

    // Update price display
    var priceElement = document.getElementById('currentPrice');
    if (priceElement) {
        var priceData = {
            'excellent': <?php echo $excellentPrice; ?>,
            'good': <?php echo $goodPrice; ?>,
            'fair': <?php echo $fairPrice; ?>
        };
        var newPrice = priceData[condition];
        priceElement.textContent = 'GH₵' + Math.round(newPrice).toLocaleString();
        console.log('Updated price to:', newPrice);
    }
}

// Simple add to cart function
function addToCartWithCondition(productId) {
    console.log('Add to cart called:', productId);

    // Get current condition and price
    var condition = 'excellent'; // default
    var price = <?php echo $excellentPrice; ?>; // default price

    // Try to find selected condition
    var selectedElement = document.querySelector('[data-condition][style*="border: 2px solid"]');
    if (selectedElement) {
        condition = selectedElement.getAttribute('data-condition');
        var priceData = {
            'excellent': <?php echo $excellentPrice; ?>,
            'good': <?php echo $goodPrice; ?>,
            'fair': <?php echo $fairPrice; ?>
        };
        price = priceData[condition];
    }

    console.log('Adding to cart with condition:', condition, 'price:', price);

    // Create form data
    var formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    formData.append('condition', condition);
    formData.append('price', price);

    // Send to cart action
    fetch('../actions/add_to_cart_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Cart response:', data);
        if (data.status === 'success' || data.success) {
            // Use the beautiful popup from cart.js if available
            if (typeof showAddedToCartPopup === 'function') {
                // Transform our data to match the expected format
                var popupData = {
                    product_name: data.product_name || '<?php echo addslashes($product['product_title']); ?>',
                    product_image: '<?php echo addslashes(get_product_image_url($product['product_image'], $product['product_title'])); ?>',
                    condition: condition,
                    quantity: 1,
                    final_price: price,
                    product_price: price,
                    cart_count: data.cart_count || 0,
                    cart_total: data.cart_total || '0'
                };
                showAddedToCartPopup(popupData);
            } else {
                // Fallback to SweetAlert if cart.js not loaded
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Added to Cart!',
                        text: 'Product has been added to your cart.',
                        icon: 'success',
                        timer: 2000
                    });
                } else {
                    alert('Product added to cart successfully!');
                }
            }

            // Update cart count if element exists
            var cartBadge = document.getElementById('cartBadge');
            if (cartBadge && (data.cart_count || data.cart_count === 0)) {
                cartBadge.textContent = data.cart_count;
                cartBadge.style.display = data.cart_count > 0 ? 'inline' : 'none';
            }
        } else {
            // Show error message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to add product to cart',
                    icon: 'error'
                });
            } else {
                alert(data.message || 'Failed to add product to cart');
            }
        }
    })
    .catch(error => {
        console.error('Add to cart error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'An error occurred. Please try again.',
                icon: 'error'
            });
        } else {
            alert('An error occurred. Please try again.');
        }
    });
}

// Beautiful cart popup function (from cart.js)
function showAddedToCartPopup(data) {
    // Remove existing popup
    var existingPopup = document.getElementById('addedToCartPopup');
    if (existingPopup) existingPopup.remove();

    var safeProductName = (data.product_name || 'Product').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    var productImage = data.product_image || '';

    var popup = document.createElement('div');
    popup.id = 'addedToCartPopup';
    popup.className = 'cart-popup-overlay';
    popup.innerHTML =
        '<div class="cart-popup">' +
            '<div class="cart-popup-header">' +
                '<h3><i class="fas fa-check-circle text-success"></i> Added to Cart!</h3>' +
                '<button class="cart-popup-close" onclick="closeAddedToCartPopup()">' +
                    '<i class="fas fa-times"></i>' +
                '</button>' +
            '</div>' +
            '<div class="cart-popup-body">' +
                '<div class="added-item">' +
                    '<img src="' + productImage + '" alt="' + safeProductName + '" class="item-image">' +
                    '<div class="item-details">' +
                        '<h4>' + data.product_name + '</h4>' +
                        '<div class="item-specs">' +
                            (data.condition ? '<span class="condition-badge">' + data.condition + ' Condition</span>' : '') +
                            '<span class="quantity-badge">Qty: ' + (data.quantity || 1) + '</span>' +
                        '</div>' +
                        '<div class="item-price">GH₵' + Math.round(parseFloat(data.final_price || data.product_price)).toLocaleString() + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="cart-summary">' +
                    '<div class="cart-info">' +
                        '<div class="cart-count">' +
                            '<i class="fas fa-shopping-bag"></i>' +
                            '<span>Cart (' + (data.cart_count || 0) + ')</span>' +
                        '</div>' +
                        '<div class="subtotal">' +
                            '<span>Subtotal: <strong>GH₵' + Math.round(parseFloat(data.cart_total || '0')).toLocaleString() + '</strong></span>' +
                        '</div>' +
                    '</div>' +
                    (parseFloat(data.cart_total || '0') > 200 ? '<div class="shipping-badge"><i class="fas fa-shipping-fast"></i> You earned Free Standard Shipping!</div>' : '') +
                '</div>' +
            '</div>' +
            '<div class="cart-popup-footer">' +
                '<button class="btn btn-outline" onclick="continueShopping()">Continue Shopping</button>' +
                '<button class="btn btn-primary" onclick="viewCart()">' +
                    '<i class="fas fa-shopping-cart"></i> View Cart (' + (data.cart_count || 0) + ')' +
                '</button>' +
            '</div>' +
        '</div>';

    document.body.appendChild(popup);

    // Show popup with animation
    setTimeout(function() {
        popup.classList.add('show');
    }, 10);

    // Auto close after 8 seconds
    setTimeout(function() {
        if (document.getElementById('addedToCartPopup')) {
            closeAddedToCartPopup();
        }
    }, 8000);
}

function closeAddedToCartPopup() {
    var popup = document.getElementById('addedToCartPopup');
    if (popup) {
        popup.classList.remove('show');
        setTimeout(function() {
            popup.remove();
        }, 300);
    }
}

function viewCart() {
    window.location.href = '../views/cart.php';
}

function continueShopping() {
    window.location.href = '../index.php';
}

// Make functions globally available
window.selectCondition = selectCondition;
window.addToCartWithCondition = addToCartWithCondition;
window.showAddedToCartPopup = showAddedToCartPopup;
window.closeAddedToCartPopup = closeAddedToCartPopup;
window.viewCart = viewCart;
window.continueShopping = continueShopping;

console.log('=== CONDITION SELECTION SCRIPT LOADED ===');
console.log('selectCondition available:', typeof window.selectCondition);
console.log('addToCartWithCondition available:', typeof window.addToCartWithCondition);

// Scroll to Top Button Functionality
document.addEventListener('DOMContentLoaded', function() {
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');
    
    if (scrollToTopBtn) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });

        // Scroll to top when button is clicked
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});
</script>

    <!-- AI Recommendations Section -->
    <?php include '../includes/ai_recommendations_section.php'; ?>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>

</html>