<?php
try {
    require_once(__DIR__ . '/../settings/core.php');
    require_once(__DIR__ . '/../helpers/store_credit_helper.php');
    require_once(__DIR__ . '/../controllers/cart_controller.php');

    $is_logged_in = check_login();
    $customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!$is_logged_in) {
        header("Location: ../login/login.php");
        exit;
    }

    // Get store credits
    $storeCreditHelper = new StoreCreditHelper();
    $store_credits = $storeCreditHelper->getAvailableCredits($customer_id);
    $total_balance = $storeCreditHelper->getTotalAvailableCredit($customer_id);

    // Get cart count for header
    $cart_count = get_cart_count_ctr($customer_id, $ip_address) ?: 0;

    // Get user's name for welcome message
    $user_name = $_SESSION['name'] ?? 'User';
    $first_name = explode(' ', $user_name)[0];

    // Get categories and brands for navigation
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

} catch (Exception $e) {
    die("Critical error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store Credits - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/page-background.css" rel="stylesheet">
    <link href="../includes/account_sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #1a202c;
            line-height: 1.6;
        }

        .account-layout {
            display: flex;
            min-height: calc(100vh - 140px);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            position: relative;
            margin-top: 0;
        }

        .account-content {
            flex: 1;
            padding: 30px;
            max-width: calc(100% - 240px);
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }

        .store-credits-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .balance-card {
            background: linear-gradient(135deg, #2563EB 0%, #1E3A5F 100%);
            border-radius: 16px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            text-align: center;
        }

        .balance-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .balance-amount {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .balance-subtitle {
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .credits-section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 20px;
        }

        .credit-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #2563EB;
            transition: all 0.3s ease;
        }

        .credit-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .credit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .credit-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2563EB;
        }

        .credit-source {
            font-size: 0.9rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .credit-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .credit-description {
            color: #4b5563;
            font-size: 0.95rem;
        }

        .credit-available {
            font-size: 0.9rem;
            color: #10b981;
            font-weight: 600;
        }

        .credit-expiry {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #374151;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563EB, #1E3A5F);
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            color: white;
        }

        @media (max-width: 768px) {
            .account-content {
                max-width: 100%;
                padding: 20px;
            }

            .balance-amount {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="page-background">
    <?php include '../includes/header.php'; ?>
    
    <!-- Account Layout -->
    <div class="account-layout">
        <!-- Account Sidebar -->
        <?php include '../includes/account_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="account-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-credit-card me-2"></i>
                    Store Credits
                </h1>
                <p class="page-subtitle">View and manage your store credit balance</p>
            </div>

            <div class="store-credits-container">
                <!-- Balance Card -->
                <div class="balance-card">
                    <div class="balance-label">Total Available Balance</div>
                    <div class="balance-amount">GH₵ <?php echo number_format($total_balance, 2); ?></div>
                    <div class="balance-subtitle">
                        <i class="fas fa-info-circle me-1"></i>
                        Use your store credits at checkout to save on your next purchase
                    </div>
                </div>

                <?php if (empty($store_credits)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-credit-card"></i>
                        <h3>No Store Credits Available</h3>
                        <p>You don't have any store credits yet. Earn store credits by trading in your devices through our Device Drop service.</p>
                        <a href="device_drop.php" class="btn-primary">
                            <i class="fas fa-recycle me-2"></i>
                            Trade in Your Device
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Credits List -->
                    <div class="credits-section-title">Available Credits</div>
                    <?php foreach ($store_credits as $credit): 
                        $available = floatval($credit['available_amount']);
                        $expiry = $credit['expires_at'] ? new DateTime($credit['expires_at']) : null;
                    ?>
                        <div class="credit-item">
                            <div class="credit-header">
                                <div>
                                    <div class="credit-amount">GH₵ <?php echo number_format($available, 2); ?></div>
                                    <div class="credit-source">
                                        <?php 
                                        $source_types = [
                                            'device_drop' => 'Device Drop',
                                            'refund' => 'Refund',
                                            'promotion' => 'Promotion',
                                            'manual' => 'Manual Credit'
                                        ];
                                        echo $source_types[$credit['source_type']] ?? ucfirst($credit['source_type']);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="credit-description">
                                <?php 
                                $description = $credit['description'] ?? 'Store Credit';
                                // Remove "Auto-generated from device drop request #0" text
                                $description = preg_replace('/Auto-generated from device drop request #0/i', '', $description);
                                $description = preg_replace('/Auto-generated from device drop request #\d+/i', '', $description);
                                $description = trim($description);
                                // If description is empty after removal, use a default
                                if (empty($description)) {
                                    $description = 'Store Credit';
                                }
                                echo htmlspecialchars($description); 
                                ?>
                            </div>
                            <div class="credit-details">
                                <div>
                                    <?php if ($expiry): ?>
                                        <div class="credit-expiry">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            Expires: <?php echo $expiry->format('M d, Y'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="credit-expiry">
                                            <i class="fas fa-infinity me-1"></i>
                                            No expiration
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="credit-available">
                                    Available: GH₵ <?php echo number_format($available, 2); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-4 text-center">
                        <a href="device_drop.php" class="btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Earn More Store Credits
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

