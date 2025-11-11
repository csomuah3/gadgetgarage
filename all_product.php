<?php
require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/product_controller.php');
require_once(__DIR__ . '/controllers/category_controller.php');
require_once(__DIR__ . '/controllers/brand_controller.php');
require_once(__DIR__ . '/controllers/cart_controller.php');
require_once(__DIR__ . '/helpers/image_helper.php');

$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['customer_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);

// Get all products, categories, and brands
$products = view_all_products_ctr();
$categories = get_all_categories_ctr();
$brands = get_all_brands_ctr();

// Pagination settings
$products_per_page = 10;
$total_products = count($products);
$total_pages = ceil($total_products / $products_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;
$products_to_display = array_slice($products, $offset, $products_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Products - FlavorHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            color: #1a202c;
            position: relative;
            overflow-x: hidden;
        }

        /* Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 20%, rgba(139, 95, 191, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(240, 147, 251, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(139, 95, 191, 0.03) 0%, transparent 50%);
            z-index: -1;
        }

        .main-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            box-shadow: 0 4px 20px rgba(139, 95, 191, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 15px 0;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #8b5fbf;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-title {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border-radius: 2px;
        }

        /* Sidebar Layout Styles */
        .filters-sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(139, 95, 191, 0.1);
        }

        .filter-title {
            color: #8b5fbf;
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-close {
            background: none;
            border: none;
            color: #666;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .filter-close:hover {
            background: rgba(139, 95, 191, 0.1);
            color: #8b5fbf;
        }

        .filter-subtitle {
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 12px;
            display: block;
        }

        .filter-group {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(139, 95, 191, 0.1);
        }

        .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        /* Search Input Styles */
        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
        }

        .search-input:focus {
            outline: none;
            border-color: #8b5fbf;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b5fbf;
            font-size: 0.9rem;
        }

        /* Checkbox Styles */
        .checkbox-group {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .checkbox-group::-webkit-scrollbar {
            width: 4px;
        }

        .checkbox-group::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .checkbox-group::-webkit-scrollbar-thumb {
            background: #8b5fbf;
            border-radius: 2px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            cursor: pointer;
            font-size: 0.9rem;
            color: #555;
            transition: all 0.3s ease;
            margin: 0;
        }

        .checkbox-item:hover {
            color: #8b5fbf;
            background: rgba(139, 95, 191, 0.05);
            border-radius: 5px;
            padding-left: 5px;
        }

        .checkbox-item input[type="checkbox"] {
            display: none;
        }

        .checkbox-custom {
            width: 16px;
            height: 16px;
            border: 2px solid #ddd;
            border-radius: 3px;
            margin-right: 10px;
            position: relative;
            transition: all 0.3s ease;
        }

        .checkbox-item input[type="checkbox"]:checked+.checkbox-custom {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border-color: #8b5fbf;
        }

        .checkbox-item input[type="checkbox"]:checked+.checkbox-custom::after {
            content: '✓';
            position: absolute;
            top: -2px;
            left: 2px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        /* Filter Actions */
        .filter-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid rgba(139, 95, 191, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .apply-filters-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .apply-filters-btn:hover {
            background: linear-gradient(135deg, #218838, #1a936f);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }

        /* Mobile Styles */
        @media (max-width: 991px) {
            #filterSidebar {
                position: fixed;
                left: -100%;
                top: 0;
                width: 320px;
                height: 100vh;
                z-index: 9999;
                transition: all 0.3s ease;
                background: white;
            }

            #filterSidebar.show {
                left: 0;
            }

            .filters-sidebar {
                height: 100vh;
                max-height: none;
                border-radius: 0;
                position: static;
                top: auto;
            }

            .filter-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                display: none;
            }

            .filter-overlay.show {
                display: block;
            }
        }

        /* Rating Filter Styles */
        .rating-filter {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rating-option {
            display: flex;
            align-items: center;
        }

        .rating-option input[type="radio"] {
            display: none;
        }

        .rating-option label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .rating-option label:hover {
            background: rgba(139, 95, 191, 0.1);
        }

        .rating-option input[type="radio"]:checked+label {
            background: rgba(139, 95, 191, 0.2);
            color: #8b5fbf;
        }

        .stars {
            display: flex;
            gap: 2px;
        }

        .stars i {
            color: #ffd700;
            font-size: 14px;
        }

        .rating-text {
            font-size: 14px;
            color: #666;
        }

        /* Price Range Slider Styles */
        .price-slider-container {
            padding: 10px 0;
        }

        .price-slider-track {
            position: relative;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin: 10px 0 20px 0;
        }

        .price-slider-range {
            position: absolute;
            height: 6px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border-radius: 3px;
            left: 0%;
            right: 0%;
        }

        .price-slider {
            position: absolute;
            top: -2px;
            width: 100%;
            height: 10px;
            background: transparent;
            outline: none;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
        }

        .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: #8b5fbf;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-slider::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: #8b5fbf;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #8b5fbf;
        }

        .price-separator {
            color: #666;
        }

        /* Tag Filter Styles */
        .tag-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tag-btn:hover {
            background: rgba(139, 95, 191, 0.1);
            border-color: #8b5fbf;
            color: #8b5fbf;
        }

        .tag-btn.active {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border-color: #8b5fbf;
            color: white;
        }

        /* Size Filter Styles */
        .size-filters {
            display: flex;
            gap: 8px;
        }

        .size-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 70px;
            text-align: center;
        }

        .size-btn:hover {
            background: rgba(139, 95, 191, 0.1);
            border-color: #8b5fbf;
            color: #8b5fbf;
        }

        .size-btn.active {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            border-color: #8b5fbf;
            color: white;
        }

        /* Color Filter Styles */
        .color-filters {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .color-btn {
            background: none;
            border: 2px solid transparent;
            padding: 4px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .color-btn:hover {
            border-color: #8b5fbf;
            transform: scale(1.1);
        }

        .color-btn.active {
            border-color: #8b5fbf;
            background: rgba(139, 95, 191, 0.1);
        }

        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .color-circle.all-colors {
            background: conic-gradient(from 0deg,
                    #ff0000 0deg 60deg,
                    #ffff00 60deg 120deg,
                    #00ff00 120deg 180deg,
                    #00ffff 180deg 240deg,
                    #0000ff 240deg 300deg,
                    #ff00ff 300deg 360deg);
        }

        /* Clear Filters Button */
        .clear-filters-container {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(139, 95, 191, 0.1);
        }

        .filter-select,
        .filter-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(10px);
        }

        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: #8b5fbf;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(139, 95, 191, 0.1);
            transform: translateY(-2px);
        }

        .price-input {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(10px);
        }

        .price-input:focus {
            outline: none;
            border-color: #8b5fbf;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .preset-btn {
            border-radius: 8px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.85rem;
            padding: 6px 12px;
            transition: all 0.3s ease;
        }

        .preset-btn:hover,
        .preset-btn.active {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border-color: #8b5fbf;
        }

        .clear-filters-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .clear-filters-btn:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .stats-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(139, 95, 191, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .product-count {
            font-weight: 600;
            color: #8b5fbf;
            font-size: 1.1rem;
        }

        .view-toggle {
            display: flex;
            gap: 8px;
        }

        .view-btn {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-btn.active,
        .view-btn:hover {
            background: #8b5fbf;
            color: white;
            border-color: #8b5fbf;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .product-grid.list-view {
            grid-template-columns: 1fr;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
            transition: all 0.4s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.05), rgba(240, 147, 251, 0.05));
            opacity: 0;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 16px 48px rgba(139, 95, 191, 0.25);
        }

        .product-card:hover::before {
            opacity: 1;
        }

        .product-image-container {
            position: relative;
            width: 100%;
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.4s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.1);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .product-content {
            padding: 25px;
            position: relative;
            z-index: 2;
        }

        .product-title {
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

        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .meta-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(139, 95, 191, 0.1);
            border-radius: 20px;
            font-size: 0.85rem;
            color: #8b5fbf;
            font-weight: 500;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
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
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.4);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 50px 0;
        }

        .page-btn {
            padding: 12px 18px;
            border: 2px solid rgba(139, 95, 191, 0.2);
            background: rgba(255, 255, 255, 0.9);
            color: #8b5fbf;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .page-btn:hover,
        .page-btn.active {
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 95, 191, 0.3);
        }

        .no-products {
            text-align: center;
            padding: 80px 20px;
            color: #64748b;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.1);
        }

        .no-products-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #8b5fbf, #f093fb);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(139, 95, 191, 0.3);
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #764ba2, #8b5fbf);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.4);
        }

        .hero-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(139, 95, 191, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-actions .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            border-width: 2px;
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 5px;
        }

        .suggestion-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .suggestion-item:hover {
            background: linear-gradient(135deg, #f8f9ff, #f0f2ff);
            color: #8b5fbf;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-icon {
            color: #8b5fbf;
            font-size: 0.9rem;
        }

        .checkbox-dropdown {
            position: relative;
            width: 100%;
        }

        .dropdown-toggle {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1rem;
            color: #4a5568;
            transition: all 0.3s ease;
        }

        .dropdown-toggle:hover {
            border-color: #8b5fbf;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .dropdown-toggle.active {
            border-color: #8b5fbf;
            box-shadow: 0 0 0 3px rgba(139, 95, 191, 0.1);
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
            color: #8b5fbf;
        }

        .dropdown-toggle.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(139, 95, 191, 0.15);
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 5px;
            display: none;
        }

        .dropdown-content.show {
            display: block;
        }

        .checkbox-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .checkbox-item:hover {
            background: linear-gradient(135deg, #f8f9ff, #f0f2ff);
        }

        .checkbox-item:last-child {
            border-bottom: none;
        }

        .checkbox-item input[type="checkbox"] {
            margin-right: 10px;
            width: 16px;
            height: 16px;
            accent-color: #8b5fbf;
            cursor: pointer;
        }

        .checkbox-item label {
            cursor: pointer;
            color: #4a5568;
            font-weight: 500;
            margin: 0;
        }

        .checkbox-item:hover label {
            color: #8b5fbf;
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .bubble {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(139, 95, 191, 0.1), rgba(240, 147, 251, 0.1));
            animation: float 6s ease-in-out infinite;
        }

        .bubble:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .bubble:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 20%;
            right: 15%;
            animation-delay: 2s;
        }

        .bubble:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        .bubble:nth-child(4) {
            width: 120px;
            height: 120px;
            bottom: 20%;
            right: 10%;
            animation-delay: 1s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .filters-section {
                padding: 20px;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-2">
                    <a href="index.php" class="logo">
                        <i class="fas fa-utensils"></i>
                        <span>FlavorHub</span>
                    </a>
                </div>
                <div class="col-lg-8 text-end">
                    <h1 class="mb-0" style="color: #8b5fbf; font-weight: 700;">All Products</h1>
                </div>
                <div class="col-lg-2 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <!-- Cart Icon -->
                        <a href="cart.php" class="cart-icon position-relative">
                            <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: #8b5fbf;"></i>
                            <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartBadge" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>">
                                <?php echo $cart_count; ?>
                            </span>
                        </a>

                        <?php if ($is_logged_in): ?>
                            <a href="my_orders.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-box"></i> My Orders
                            </a>
                            <a href="login/logout.php" class="btn btn-outline-danger">Logout</a>
                        <?php else: ?>
                            <a href="login/login.php" class="btn btn-outline-primary">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <div class="container-fluid mt-4">
        <!-- Hero Bar -->
        <div class="hero-bar mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
                <h1 class="page-title mb-0">All Products</h1>
                <div class="hero-actions">
                    <!-- Mobile filter toggle -->
                    <button class="btn btn-outline-primary d-lg-none" id="mobileFilterToggle">
                        <i class="fas fa-filter"></i>
                        Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Sidebar - Filters -->
            <div class="col-lg-3 col-md-4" id="filterSidebar">
                <div class="filters-sidebar">
                    <div class="filter-header">
                        <h3 class="filter-title">
                            <i class="fas fa-sliders-h"></i>
                            Filter Products
                        </h3>
                        <button class="filter-close d-lg-none" id="closeFilters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="filter-group">
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInput" placeholder="Search products..." autocomplete="off">
                            <i class="fas fa-search search-icon"></i>
                            <div id="searchSuggestions" class="search-suggestions" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Rating</h6>
                        <div class="rating-filter">
                            <div class="rating-option" data-rating="5">
                                <input type="radio" id="rating_5" name="rating_filter" value="5">
                                <label for="rating_5">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                    <span class="rating-text">5 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="4">
                                <input type="radio" id="rating_4" name="rating_filter" value="4">
                                <label for="rating_4">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">4 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="3">
                                <input type="radio" id="rating_3" name="rating_filter" value="3">
                                <label for="rating_3">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">3 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="2">
                                <input type="radio" id="rating_2" name="rating_filter" value="2">
                                <label for="rating_2">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">2 Star</span>
                                </label>
                            </div>
                            <div class="rating-option" data-rating="1">
                                <input type="radio" id="rating_1" name="rating_filter" value="1">
                                <label for="rating_1">
                                    <div class="stars">
                                        <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <span class="rating-text">1 Star</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Price Range</h6>
                        <div class="price-slider-container">
                            <div class="price-slider-track">
                                <div class="price-slider-range" id="priceRange"></div>
                                <input type="range" class="price-slider" id="minPriceSlider" min="0" max="500" value="0" step="1">
                                <input type="range" class="price-slider" id="maxPriceSlider" min="0" max="500" value="500" step="1">
                            </div>
                            <div class="price-display">
                                <span class="price-min" id="priceMinDisplay">$0</span>
                                <span class="price-separator">-</span>
                                <span class="price-max" id="priceMaxDisplay">$500</span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter by Category -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Category</h6>
                        <div class="tag-filters" id="categoryTags">
                            <button class="tag-btn active" data-category="" id="category_all_btn">All</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="tag-btn" data-category="<?php echo $category['cat_id']; ?>" id="category_btn_<?php echo $category['cat_id']; ?>">
                                    <?php echo htmlspecialchars($category['cat_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by Brand -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Brand</h6>
                        <div class="tag-filters" id="brandTags">
                            <button class="tag-btn active" data-brand="" id="brand_all_btn">All</button>
                            <?php foreach ($brands as $brand): ?>
                                <button class="tag-btn" data-brand="<?php echo $brand['brand_id']; ?>" id="brand_btn_<?php echo $brand['brand_id']; ?>">
                                    <?php echo htmlspecialchars($brand['brand_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by Size -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Size</h6>
                        <div class="size-filters">
                            <button class="size-btn active" data-size="">All</button>
                            <button class="size-btn" data-size="large">Large</button>
                            <button class="size-btn" data-size="medium">Medium</button>
                            <button class="size-btn" data-size="small">Small</button>
                        </div>
                    </div>

                    <!-- Filter by Color -->
                    <div class="filter-group">
                        <h6 class="filter-subtitle">Filter By Color</h6>
                        <div class="color-filters">
                            <button class="color-btn active" data-color="" title="All Colors">
                                <span class="color-circle all-colors"></span>
                            </button>
                            <button class="color-btn" data-color="blue" title="Blue">
                                <span class="color-circle" style="background-color: #0066cc;"></span>
                            </button>
                            <button class="color-btn" data-color="gray" title="Gray">
                                <span class="color-circle" style="background-color: #808080;"></span>
                            </button>
                            <button class="color-btn" data-color="green" title="Green">
                                <span class="color-circle" style="background-color: #00aa00;"></span>
                            </button>
                            <button class="color-btn" data-color="red" title="Red">
                                <span class="color-circle" style="background-color: #dd0000;"></span>
                            </button>
                            <button class="color-btn" data-color="yellow" title="Yellow">
                                <span class="color-circle" style="background-color: #ffdd00;"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Apply/Clear Filters Buttons -->
                    <div class="filter-actions">
                        <button class="apply-filters-btn" id="applyFilters" style="display: none;">
                            <i class="fas fa-check"></i>
                            Apply Filters
                        </button>
                        <button class="clear-filters-btn" id="clearFilters">
                            <i class="fas fa-times"></i>
                            Clear All Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Content - Products -->
            <div class="col-lg-9 col-md-8" id="productContent">
                <div class="stats-bar">
                    <div class="product-count">
                        <i class="fas fa-box"></i>
                        Showing <?php echo count($products_to_display); ?> of <?php echo $total_products; ?> products
                    </div>
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('grid')" title="Grid View">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" onclick="toggleView('list')" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <div id="productsContainer">
                    <?php if (empty($products_to_display)): ?>
                        <div class="no-products">
                            <div class="no-products-icon">📦</div>
                            <h3>No Products Found</h3>
                            <p>There are no products available at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="product-grid" id="productGrid">
                            <?php foreach ($products_to_display as $product): ?>
                                <div class="product-card" onclick="viewProduct(<?php echo $product['product_id']; ?>)">
                                    <div class="product-image-container">
                                        <img src=""
                                            alt="<?php echo htmlspecialchars($product['product_title']); ?>"
                                            class="product-image"
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            data-product-image="<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>"
                                            data-product-title="<?php echo htmlspecialchars($product['product_title']); ?>">
                                        <div class="product-badge">New</div>
                                    </div>
                                    <div class="product-content">
                                        <h5 class="product-title"><?php echo htmlspecialchars($product['product_title']); ?></h5>
                                        <div class="product-price">$<?php echo number_format($product['product_price'], 2); ?></div>
                                        <div class="product-meta">
                                            <span class="meta-tag">
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars($product['cat_name'] ?? 'N/A'); ?>
                                            </span>
                                            <span class="meta-tag">
                                                <i class="fas fa-store"></i>
                                                <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?>
                                            </span>
                                        </div>
                                        <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(<?php echo $product['product_id']; ?>)">
                                            <i class="fas fa-shopping-cart"></i>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?>" class="page-btn">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>"
                                        class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?>" class="page-btn">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cart.js"></script>
    <script>
        function viewProduct(productId) {
            window.location.href = 'single_product.php?id=' + productId;
        }

        function addToCart(productId) {
            // Add visual feedback
            const btn = event.target.closest('.add-to-cart-btn');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-check"></i> Added!';
            btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = 'linear-gradient(135deg, #8b5fbf, #f093fb)';
            }, 1500);

            // Here you would normally send AJAX request to add to cart
            console.log('Add to cart functionality - Product ID: ' + productId);

            // Update cart count
            updateCartCount();
        }

        function showCart() {
            alert('Cart functionality will be implemented soon!\nThis will show your cart items.');
        }

        function updateCartCount() {
            // This would normally get the actual cart count from storage/database
            const cartCountElement = document.getElementById('cartCount');
            let currentCount = parseInt(cartCountElement.textContent);
            cartCountElement.textContent = currentCount + 1;
        }

        function toggleView(viewType) {
            const productGrid = document.getElementById('productGrid');
            const viewBtns = document.querySelectorAll('.view-btn');

            // Update button states
            viewBtns.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');

            // Update grid layout
            if (viewType === 'list') {
                productGrid.classList.add('list-view');
            } else {
                productGrid.classList.remove('list-view');
            }
        }

        // Image Loading System
        function loadProductImages() {
            document.querySelectorAll('.product-image').forEach(img => {
                const productId = img.getAttribute('data-product-id');
                const productImage = img.getAttribute('data-product-image');
                const productTitle = img.getAttribute('data-product-title');

                // Load image using the dedicated action
                fetch(`actions/upload_product_image_action.php?action=get_image_url&product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.url) {
                            img.src = data.url;
                        } else {
                            // Use placeholder
                            img.src = generatePlaceholderUrl(productTitle);
                        }
                    })
                    .catch(error => {
                        console.log('Image load error for product', productId, '- using placeholder');
                        img.src = generatePlaceholderUrl(productTitle);
                    });
            });
        }

        function generatePlaceholderUrl(text, size = '320x240') {
            const encodedText = encodeURIComponent(text);
            return `https://via.placeholder.com/${size}/8b5fbf/ffffff?text=${encodedText}`;
        }

        // Autocomplete functionality
        let allProducts = <?php echo json_encode($products); ?>;

        function showSearchSuggestions(query) {
            const suggestions = document.getElementById('searchSuggestions');
            const filteredProducts = allProducts.filter(product =>
                product.product_title.toLowerCase().includes(query.toLowerCase()) ||
                (product.product_keywords && product.product_keywords.toLowerCase().includes(query.toLowerCase()))
            ).slice(0, 5); // Show only top 5 suggestions

            if (filteredProducts.length > 0) {
                let suggestionsHTML = '';
                filteredProducts.forEach(product => {
                    suggestionsHTML += `
                        <div class="suggestion-item" onclick="selectSuggestion('${product.product_title}', ${product.product_id})">
                            <i class="fas fa-search suggestion-icon"></i>
                            <span>${highlightMatch(product.product_title, query)}</span>
                        </div>
                    `;
                });

                // Add "Search for..." option
                suggestionsHTML += `
                    <div class="suggestion-item" onclick="performSearchFor('${query}')">
                        <i class="fas fa-arrow-right suggestion-icon"></i>
                        <span>Search for "<strong>${query}</strong>"</span>
                    </div>
                `;

                suggestions.innerHTML = suggestionsHTML;
                suggestions.style.display = 'block';
            } else {
                suggestions.innerHTML = `
                    <div class="suggestion-item" onclick="performSearchFor('${query}')">
                        <i class="fas fa-search suggestion-icon"></i>
                        <span>Search for "<strong>${query}</strong>"</span>
                    </div>
                `;
                suggestions.style.display = 'block';
            }
        }

        function hideSearchSuggestions() {
            document.getElementById('searchSuggestions').style.display = 'none';
        }

        function highlightMatch(text, query) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }

        function selectSuggestion(productTitle, productId) {
            document.getElementById('searchInput').value = productTitle;
            hideSearchSuggestions();
            applyFilters();
        }

        function performSearchFor(query) {
            document.getElementById('searchInput').value = query;
            hideSearchSuggestions();
            applyFilters();
        }

        function performInstantSearch() {
            applyFilters();
        }

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-autocomplete-container')) {
                hideSearchSuggestions();
            }
        });

        // New Filter System with Apply Button
        let filtersChanged = false;
        let initialState = null;

        function initNewFilters() {
            // Store initial state
            captureInitialState();

            // Initialize all filter components
            initPriceSlider();
            initRatingFilter();
            initCategoryFilter();
            initTagFilters();
            initSizeFilters();
            initColorFilters();
            initMobileFilters();
        }

        function captureInitialState() {
            initialState = {
                search: '',
                rating: '',
                minPrice: 0,
                maxPrice: 500,
                categories: [''],
                brand: '',
                size: '',
                color: ''
            };
        }

        function showApplyButton() {
            if (!filtersChanged) {
                filtersChanged = true;
                const applyBtn = document.getElementById('applyFilters');
                applyBtn.style.display = 'flex';
                applyBtn.classList.add('animate__animated', 'animate__fadeInUp');
            }
        }

        function hideApplyButton() {
            filtersChanged = false;
            const applyBtn = document.getElementById('applyFilters');
            applyBtn.style.display = 'none';
        }

        function initPriceSlider() {
            const minSlider = document.getElementById('minPriceSlider');
            const maxSlider = document.getElementById('maxPriceSlider');
            const minDisplay = document.getElementById('priceMinDisplay');
            const maxDisplay = document.getElementById('priceMaxDisplay');
            const rangeDisplay = document.getElementById('priceRange');

            function updatePriceDisplay() {
                const minVal = parseInt(minSlider.value);
                const maxVal = parseInt(maxSlider.value);

                // Ensure min is not greater than max
                if (minVal > maxVal - 10) {
                    minSlider.value = maxVal - 10;
                }

                if (maxVal < minVal + 10) {
                    maxSlider.value = minVal + 10;
                }

                const finalMin = parseInt(minSlider.value);
                const finalMax = parseInt(maxSlider.value);

                // Always update the display in real-time
                minDisplay.textContent = `$${finalMin}`;
                maxDisplay.textContent = `$${finalMax}`;

                // Update range display
                const minPercent = (finalMin / parseInt(minSlider.max)) * 100;
                const maxPercent = (finalMax / parseInt(maxSlider.max)) * 100;

                rangeDisplay.style.left = `${minPercent}%`;
                rangeDisplay.style.right = `${100 - maxPercent}%`;
            }

            function checkForChanges() {
                const finalMin = parseInt(minSlider.value);
                const finalMax = parseInt(maxSlider.value);

                // Show apply button if values changed from initial (only if initialState exists)
                if (initialState && (finalMin !== initialState.minPrice || finalMax !== initialState.maxPrice)) {
                    showApplyButton();
                }
            }

            // Real-time display updates
            minSlider.addEventListener('input', updatePriceDisplay);
            maxSlider.addEventListener('input', updatePriceDisplay);

            // Check for changes on mouse up or touch end
            minSlider.addEventListener('change', checkForChanges);
            maxSlider.addEventListener('change', checkForChanges);

            // Initialize
            updatePriceDisplay();
        }

        function initRatingFilter() {
            const ratingInputs = document.querySelectorAll('input[name="rating_filter"]');

            ratingInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (initialState && this.value !== initialState.rating) {
                        showApplyButton();
                    }
                });
            });
        }

        function initCategoryFilter() {
            // Category filters using tag buttons (same as brand filter)
            const categoryBtns = document.querySelectorAll('#categoryTags .tag-btn');
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active from all category buttons
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedCategory = this.getAttribute('data-category');
                    if (initialState && selectedCategory !== initialState.categories[0]) {
                        showApplyButton();
                    }
                });
            });
        }

        function initTagFilters() {
            // Brand filters
            const brandBtns = document.querySelectorAll('#brandTags .tag-btn');
            brandBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active from all brand buttons
                    brandBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedBrand = this.getAttribute('data-brand');
                    if (initialState && selectedBrand !== initialState.brand) {
                        showApplyButton();
                    }
                });
            });
        }

        function initSizeFilters() {
            const sizeBtns = document.querySelectorAll('.size-btn');
            sizeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active from all size buttons
                    sizeBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedSize = this.getAttribute('data-size');
                    if (initialState && selectedSize !== initialState.size) {
                        showApplyButton();
                    }
                });
            });
        }

        function initColorFilters() {
            const colorBtns = document.querySelectorAll('.color-btn');
            colorBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active from all color buttons
                    colorBtns.forEach(b => b.classList.remove('active'));
                    // Add active to clicked button
                    this.classList.add('active');

                    const selectedColor = this.getAttribute('data-color');
                    if (initialState && selectedColor !== initialState.color) {
                        showApplyButton();
                    }
                });
            });
        }

        function initMobileFilters() {
            const mobileToggle = document.getElementById('mobileFilterToggle');
            const closeFilters = document.getElementById('closeFilters');
            const filterSidebar = document.getElementById('filterSidebar');

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    filterSidebar.classList.add('show');
                    // Add overlay
                    const overlay = document.createElement('div');
                    overlay.className = 'filter-overlay show';
                    document.body.appendChild(overlay);

                    overlay.addEventListener('click', function() {
                        filterSidebar.classList.remove('show');
                        overlay.remove();
                    });
                });
            }

            if (closeFilters) {
                closeFilters.addEventListener('click', function() {
                    filterSidebar.classList.remove('show');
                    const overlay = document.querySelector('.filter-overlay');
                    if (overlay) overlay.remove();
                });
            }
        }

        // Updated Filter functionality for new design with multiple categories
        function executeFilters() {
            // Get search query
            const searchInput = document.getElementById('searchInput');
            const searchQuery = searchInput.value;

            // Get selected category (single selection like brand)
            const activeCategory = document.querySelector('#categoryTags .tag-btn.active');
            const categoryId = activeCategory ? activeCategory.getAttribute('data-category') : '';

            // Get selected brand
            const activeBrand = document.querySelector('#brandTags .tag-btn.active');
            const brandId = activeBrand ? activeBrand.getAttribute('data-brand') : '';

            // Get price range from sliders
            const minPrice = parseInt(document.getElementById('minPriceSlider').value);
            const maxPrice = parseInt(document.getElementById('maxPriceSlider').value);

            // Get rating filter
            const selectedRating = document.querySelector('input[name="rating_filter"]:checked');
            const rating = selectedRating ? selectedRating.value : '';

            // Get size filter
            const activeSize = document.querySelector('.size-btn.active');
            const size = activeSize ? activeSize.getAttribute('data-size') : '';

            // Get color filter
            const activeColor = document.querySelector('.color-btn.active');
            const color = activeColor ? activeColor.getAttribute('data-color') : '';

            const params = new URLSearchParams();

            // Add filters to params
            if (searchQuery) params.append('query', searchQuery);

            // Add single category
            if (categoryId) params.append('cat_ids[]', categoryId);

            if (brandId) params.append('brand_ids[]', brandId);
            if (minPrice > 0) params.append('min_price', minPrice);
            if (maxPrice < 500) params.append('max_price', maxPrice);
            if (rating) params.append('rating', rating);
            if (size) params.append('size', size);
            if (color) params.append('color', color);

            params.append('action', 'combined_filter');

            console.log('Sending filter params:', params.toString());

            // Show loading state
            const applyBtn = document.getElementById('applyFilters');
            const originalText = applyBtn.innerHTML;
            applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
            applyBtn.disabled = true;

            fetch('actions/product_actions.php?' + params.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Filter response:', data);
                    updateProductGrid(data);
                    // Reload images for filtered products
                    setTimeout(loadProductImages, 100);
                    // Hide apply button after successful application
                    hideApplyButton();
                    // Update initial state
                    updateInitialState();
                })
                .catch(error => {
                    console.error('Filter Error:', error);
                    alert('Error applying filters. Please try again.');
                })
                .finally(() => {
                    applyBtn.innerHTML = originalText;
                    applyBtn.disabled = false;
                });
        }

        function updateInitialState() {
            // Update the initial state to current values to prevent showing apply button again
            const searchInput = document.getElementById('searchInput');
            const selectedRating = document.querySelector('input[name="rating_filter"]:checked');
            const activeCategory = document.querySelector('#categoryTags .tag-btn.active');
            const activeBrand = document.querySelector('#brandTags .tag-btn.active');
            const activeSize = document.querySelector('.size-btn.active');
            const activeColor = document.querySelector('.color-btn.active');

            initialState = {
                search: searchInput.value,
                rating: selectedRating ? selectedRating.value : '',
                minPrice: parseInt(document.getElementById('minPriceSlider').value),
                maxPrice: parseInt(document.getElementById('maxPriceSlider').value),
                categories: [activeCategory ? activeCategory.getAttribute('data-category') : ''],
                brand: activeBrand ? activeBrand.getAttribute('data-brand') : '',
                size: activeSize ? activeSize.getAttribute('data-size') : '',
                color: activeColor ? activeColor.getAttribute('data-color') : ''
            };
        }

        function updateProductGrid(products) {
            const productGrid = document.getElementById('productGrid');
            const productCount = document.querySelector('.product-count');

            // Update product count
            productCount.innerHTML = `<i class="fas fa-box"></i> Showing ${products.length} products`;

            if (products.length === 0) {
                productGrid.innerHTML = `
                    <div class="no-products" style="grid-column: 1 / -1;">
                        <div class="no-products-icon">🔍</div>
                        <h3>No Products Found</h3>
                        <p>Try adjusting your filters or search terms.</p>
                    </div>
                `;
            } else {
                productGrid.innerHTML = products.map(product => {
                    return `
        <div class="product-card" onclick="viewProduct(${product.product_id})">
            <div class="product-image-container">
                <img src="${product.product_image || ''}"
                     alt="${product.product_title}"
                     class="product-image"
                     data-product-id="${product.product_id}"
                     data-product-image="${product.product_image || ''}"
                     data-product-title="${product.product_title}">
                <div class="product-badge">New</div>
            </div>
            <div class="product-content">
                <h5 class="product-title">${product.product_title}</h5>
                <div class="product-price">$${parseFloat(product.product_price).toFixed(2)}</div>
                <div class="product-meta">
                    <span class="meta-tag">
                        <i class="fas fa-tag"></i>
                        ${product.cat_name || 'N/A'}
                    </span>
                    <span class="meta-tag">
                        <i class="fas fa-store"></i>
                        ${product.brand_name || 'N/A'}
                    </span>
                </div>
                <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.product_id})">
                    <i class="fas fa-shopping-cart"></i>
                    Add to Cart
                </button>
            </div>
        </div>
    `;
                }).join('');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearFilters = document.getElementById('clearFilters');
            const applyFiltersBtn = document.getElementById('applyFilters');

            // Initialize new filter system
            setTimeout(() => {
                initNewFilters();
            }, 100);

            // Load product images
            loadProductImages();

            // Apply Filters button click handler
            applyFiltersBtn.addEventListener('click', function() {
                executeFilters();
            });

            // Search input change detection
            searchInput.addEventListener('input', function() {
                if (initialState && this.value !== initialState.search) {
                    showApplyButton();
                }
            });

            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);

                const query = this.value.trim();

                if (query.length >= 2) {
                    // Show autocomplete suggestions
                    showSearchSuggestions(query);
                    searchTimeout = setTimeout(applyFilters, 500);
                } else {
                    hideSearchSuggestions();
                    if (query.length === 0) {
                        searchTimeout = setTimeout(applyFilters, 500);
                    }
                }
            });


            clearFilters.addEventListener('click', function() {
                // Reset search input
                searchInput.value = '';

                // Reset rating filter
                document.querySelectorAll('input[name="rating_filter"]').forEach(input => {
                    input.checked = false;
                });

                // Reset price sliders
                document.getElementById('minPriceSlider').value = 0;
                document.getElementById('maxPriceSlider').value = 500;
                document.getElementById('priceMinDisplay').textContent = '$0';
                document.getElementById('priceMaxDisplay').textContent = '$500';
                document.getElementById('priceRange').style.left = '0%';
                document.getElementById('priceRange').style.right = '0%';

                // Reset category filter - activate "All" button
                document.querySelectorAll('#categoryTags .tag-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.getElementById('category_all_btn').classList.add('active');

                // Reset brand filter - activate "All" button
                document.querySelectorAll('#brandTags .tag-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.getElementById('brand_all_btn').classList.add('active');

                // Reset size filter - activate first "All" button
                document.querySelectorAll('.size-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector('.size-btn[data-size=""]').classList.add('active');

                // Reset color filter - activate first "All" button
                document.querySelectorAll('.color-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector('.color-btn[data-color=""]').classList.add('active');

                // Hide search suggestions
                hideSearchSuggestions();

                // Reset initial state
                captureInitialState();

                // Hide apply button
                hideApplyButton();

                // Apply filters (which will show all products)
                executeFilters();
            });
        });
    </script>
</body>

</html>