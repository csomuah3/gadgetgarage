<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/compare_controller.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');
require_once(__DIR__ . '/../helpers/image_helper.php');
require_once(__DIR__ . '/../helpers/ai_helper.php');

$is_logged_in = check_login();

// Redirect to login if not logged in
if (!$is_logged_in) {
    header("Location: ../login/user_login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// Get compare products
$compare_products = get_compare_products_ctr($customer_id);
$compare_count = count($compare_products);

// Get cart count for header
$cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

// Get AI analysis if we have products to compare
$ai_analysis = '';
if ($compare_count >= 2) {
    try {
        $ai_helper = new AIHelper();
        $ai_analysis = $ai_helper->compareProducts($compare_products);
    } catch (Exception $e) {
        error_log("AI Analysis Error: " . $e->getMessage());
        $ai_analysis = "⚠️ AI analysis temporarily unavailable. Please compare products manually below.";
    }
}

$categories = [];
$brands = [];

try {
    require_once(__DIR__ . '/../controllers/category_controller.php');
    $categories = get_all_categories_ctr();
} catch (Exception $e) {
    error_log("Failed to load categories: " . $e->getMessage());
}

try {
    require_once(__DIR__ . '/../controllers/brand_controller.php');
    $brands = get_all_brands_ctr();
} catch (Exception $e) {
    error_log("Failed to load brands: " . $e->getMessage());
}

$user_name = $_SESSION['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compare Products - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/account_sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }

        .compare-container {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 8px;
            text-align: center;
        }

        .page-subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            font-weight: 400;
        }

        /* AI Analysis Section */
        .ai-analysis-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #e5e7eb;
        }

        .ai-analysis-section::before {
            display: none;
        }

        .ai-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .ai-icon {
            width: 44px;
            height: 44px;
            background: #eff6ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #2563EB;
        }

        .ai-title {
            color: #1f2937;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .ai-subtitle {
            color: #64748b;
            font-size: 0.875rem;
            margin: 0;
            font-weight: 400;
        }

        .ai-content {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
            color: #374151;
            line-height: 1.8;
            border: 1px solid #e5e7eb;
            font-size: 1.15rem;
        }

        .ai-content h1,
        .ai-content h2,
        .ai-content h3 {
            color: #1e3a8a;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .ai-content h1 {
            font-size: 1.9rem;
        }

        .ai-content h2 {
            font-size: 1.6rem;
        }

        .ai-content h3 {
            font-size: 1.35rem;
        }

        .ai-content ul,
        .ai-content ol {
            margin-left: 1.25rem;
            margin-bottom: 1rem;
        }

        .ai-content li {
            margin-bottom: 0.75rem;
            font-size: 1.15rem;
        }

        .ai-content p {
            font-size: 1.15rem;
            margin-bottom: 1rem;
            line-height: 1.8;
        }

        .ai-content strong {
            color: #1e3a8a;
            font-weight: 700;
        }

        /* AI Comparison Table */
        .ai-comparison-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .ai-comparison-table th {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 16px 20px;
            text-align: left;
            font-size: 1.3rem;
            font-weight: 700;
            border-radius: 8px 8px 0 0;
        }

        .ai-comparison-table td {
            background: white;
            padding: 16px 20px;
            border-left: 4px solid #2563eb;
            font-size: 1.1rem;
            line-height: 1.7;
            vertical-align: top;
        }

        .ai-comparison-table tr:hover td {
            background: #eff6ff;
        }

        .ai-comparison-table .category-label {
            font-weight: 700;
            color: #1e3a8a;
            font-size: 1.2rem;
            width: 200px;
            background: #f1f5f9 !important;
        }

        .ai-content table {
            font-size: 1.1rem;
            width: 100%;
            border-collapse: collapse;
        }

        .ai-content td,
        .ai-content th {
            font-size: 1.1rem;
            padding: 14px 18px;
            border: 1px solid #e5e7eb;
        }

        .ai-content th {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            font-weight: 700;
        }

        .ai-content tr:nth-child(even) {
            background: #f8fafc;
        }

        .ai-loading {
            text-align: center;
            padding: 2.5rem;
            color: #64748b;
        }

        .ai-loading i {
            font-size: 48px;
            margin-bottom: 20px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Comparison Table */
        .comparison-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #e5e7eb;
        }

        .product-column {
            padding: 2rem;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-column:last-child {
            border-right: none;
        }

        .product-image-wrapper {
            width: 100%;
            height: 220px;
            background: #f8fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .product-image-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            min-height: 52px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2563EB;
            margin-bottom: 1.25rem;
        }

        .product-brand {
            display: inline-block;
            background: #eff6ff;
            color: #2563EB;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }

        .product-category {
            display: inline-block;
            background: #f0fdf4;
            color: #10b981;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            margin-bottom: 0.75rem;
            margin-left: 0.5rem;
            font-size: 0.875rem;
        }

        .product-description {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .product-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
            padding-top: 1.5rem;
        }

        .btn-view {
            flex: 1;
            background: #2563EB;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-view:hover {
            background: #1e40af;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-remove {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-remove:hover {
            background: #fecaca;
            color: #b91c1c;
        }

        /* Empty State */
        .empty-compare {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #e5e7eb;
        }

        .empty-compare i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }

        .empty-compare h3 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .empty-compare p {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .btn-browse {
            background: #2563EB;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-browse:hover {
            background: #1e40af;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .clear-all-btn {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
        }

        .clear-all-btn:hover {
            background: #fecaca;
            color: #b91c1c;
        }

        @media (max-width: 768px) {
            .product-column {
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
            }

            .product-column:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <!-- Account Layout -->
    <div class="main-content">
        <!-- Account Sidebar -->
        <?php include '../includes/account_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="content-area">
    <div class="compare-container">
        <div class="container">
            <h1 class="page-title">Compare Products</h1>
            <p class="page-subtitle">AI-Powered Product Comparison</p>

            <?php if ($compare_count >= 2): ?>
                <!-- AI Analysis Section -->
                <div class="ai-analysis-section">
                    <div class="ai-header">
                        <div class="ai-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div>
                            <h2 class="ai-title">AI Smart Analysis</h2>
                            <p class="ai-subtitle">Intelligent insights powered by OpenAI</p>
                        </div>
                    </div>

                    <div class="ai-content" id="aiContent">
                        <?php if (!empty($ai_analysis)): ?>
                            <div id="analysisText"><?php echo $ai_analysis; ?></div>
                        <?php else: ?>
                            <div class="ai-loading">
                                <i class="fas fa-spinner"></i>
                                <p>Generating AI analysis...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($compare_count > 0): ?>
                <div class="text-center mb-3">
                    <button class="clear-all-btn" onclick="clearAllCompare()">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>

                <!-- Comparison Table -->
                <div class="comparison-table">
                    <div class="row g-0">
                        <?php foreach ($compare_products as $product):
                            $product_image_url = get_product_image_url($product['product_image'] ?? '', $product['product_title'] ?? '');
                        ?>
                            <div class="col-lg-<?php echo $compare_count == 2 ? '6' : ($compare_count == 3 ? '4' : '3'); ?> col-md-6">
                                <div class="product-column">
                                    <div class="product-image-wrapper">
                                        <img src="<?= htmlspecialchars($product_image_url) ?>" alt="<?= htmlspecialchars($product['product_title']) ?>">
                                    </div>

                                    <h3 class="product-name"><?= htmlspecialchars($product['product_title']) ?></h3>
                                    <div class="product-price">GH₵<?= number_format($product['product_price'], 2) ?></div>

                                    <span class="product-brand"><?= htmlspecialchars($product['brand_name']) ?></span>
                                    <span class="product-category"><?= htmlspecialchars($product['cat_name']) ?></span>

                                    <div class="product-description">
                                        <?= nl2br(htmlspecialchars(substr($product['product_desc'], 0, 200))) ?>...
                                    </div>

                                    <div class="product-actions">
                                        <a href="single_product.php?pid=<?= $product['product_id'] ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <button class="btn-remove" onclick="removeFromCompare(<?= $product['product_id'] ?>)">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-compare">
                    <i class="fas fa-balance-scale"></i>
                    <h3>No Products to Compare</h3>
                    <p>Start adding products to your comparison list to see them here</p>
                    <a href="all_product.php" class="btn-browse">
                        <i class="fas fa-shopping-bag"></i> Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function removeFromCompare(productId) {
            Swal.fire({
                title: 'Remove from compare?',
                text: 'This product will be removed from your comparison list',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, remove it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../actions/remove_from_compare.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'product_id=' + productId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Removed!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Error', 'Failed to remove product', 'error');
                        });
                }
            });
        }

        function clearAllCompare() {
            Swal.fire({
                title: 'Clear all products?',
                text: 'This will remove all products from your comparison list',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, clear all',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    <?php foreach ($compare_products as $product): ?>
                        removeFromCompareQuiet(<?= $product['product_id'] ?>);
                    <?php endforeach; ?>

                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            });
        }

        function removeFromCompareQuiet(productId) {
            fetch('../actions/remove_from_compare.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            });
        }
    </script>
    <!-- AI Recommendations Section -->
    <?php include '../includes/ai_recommendations_section.php'; ?>
        </main>
    </div>
</body>

</html>