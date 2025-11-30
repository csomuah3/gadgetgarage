<?php
session_start();
require_once(__DIR__ . '/settings/core.php');

echo "<!DOCTYPE html><html><head><title>Store Credits Debug</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".box{background:white;padding:20px;margin:10px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo ".success{color:#10b981;font-weight:bold;}";
echo ".error{color:#ef4444;font-weight:bold;}";
echo ".warning{color:#f59e0b;font-weight:bold;}";
echo "table{width:100%;border-collapse:collapse;}";
echo "th,td{padding:10px;text-align:left;border-bottom:1px solid #e5e7eb;}";
echo "th{background:#f9fafb;font-weight:600;}";
echo "</style></head><body>";

echo "<h1>🔍 Store Credits Debug Page</h1>";

// Check login
echo "<div class='box'>";
echo "<h2>1. Login Status</h2>";
$is_logged_in = check_login();
if ($is_logged_in) {
    echo "<p class='success'>✅ You are logged in</p>";
    echo "<p><strong>User ID:</strong> " . $_SESSION['user_id'] . "</p>";
    echo "<p><strong>Name:</strong> " . ($_SESSION['name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Email:</strong> " . ($_SESSION['email'] ?? 'N/A') . "</p>";
} else {
    echo "<p class='error'>❌ You are NOT logged in</p>";
    echo "<p><a href='login/user_login.php'>Click here to login</a></p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Check store credits
echo "<div class='box'>";
echo "<h2>2. Store Credits Check</h2>";

$customer_id = $_SESSION['user_id'];

try {
    require_once(__DIR__ . '/helpers/store_credit_helper.php');
    $storeCreditHelper = new StoreCreditHelper();
    
    $store_credit_balance = $storeCreditHelper->getTotalAvailableCredit($customer_id);
    $available_store_credits = $storeCreditHelper->getAvailableCredits($customer_id);
    
    echo "<p><strong>Total Available Balance:</strong> <span style='font-size:24px;color:#3b82f6;'>GH₵ " . number_format($store_credit_balance, 2) . "</span></p>";
    echo "<p><strong>Number of Credit Records:</strong> " . count($available_store_credits) . "</p>";
    
    if ($store_credit_balance > 0) {
        echo "<p class='success'>✅ You have store credits! The section SHOULD show on cart page.</p>";
        
        // Calculate max usable
        $max_usable = max(0, $store_credit_balance - 500);
        echo "<p><strong>Maximum Usable (keeping GH₵ 500):</strong> GH₵ " . number_format($max_usable, 2) . "</p>";
        
        if ($max_usable > 0) {
            echo "<p class='success'>✅ You can use GH₵ " . number_format($max_usable, 2) . " in credits</p>";
        } else {
            echo "<p class='warning'>⚠️ Balance too low - you must keep minimum GH₵ 500</p>";
        }
    } else {
        echo "<p class='error'>❌ You have NO store credits</p>";
        echo "<p class='warning'>⚠️ The store credits section will NOT show on cart page</p>";
    }
    
    // Show credit details
    if (!empty($available_store_credits)) {
        echo "<h3>Your Store Credits:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Amount</th><th>Available</th><th>Source</th><th>Created</th><th>Expires</th><th>Status</th></tr>";
        
        foreach ($available_store_credits as $credit) {
            echo "<tr>";
            echo "<td>" . $credit['credit_id'] . "</td>";
            echo "<td>GH₵ " . number_format($credit['amount'], 2) . "</td>";
            echo "<td>GH₵ " . number_format($credit['available_amount'], 2) . "</td>";
            echo "<td>" . ucfirst($credit['source_type']) . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($credit['created_at'])) . "</td>";
            echo "<td>" . ($credit['expires_at'] ? date('Y-m-d', strtotime($credit['expires_at'])) : 'Never') . "</td>";
            echo "<td>" . $credit['status'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error loading store credits: " . $e->getMessage() . "</p>";
    echo "<p><small>" . $e->getTraceAsString() . "</small></p>";
}

echo "</div>";

// Check database table
echo "<div class='box'>";
echo "<h2>3. Database Table Check</h2>";

try {
    require_once(__DIR__ . '/settings/db_class.php');
    $db = new db_connection();
    $db->db_connect();
    
    // Check if table exists
    $table_check = $db->db_fetch_one("SHOW TABLES LIKE 'store_credits'");
    
    if ($table_check) {
        echo "<p class='success'>✅ store_credits table exists</p>";
        
        // Count total credits
        $count_all = $db->db_fetch_one("SELECT COUNT(*) as total FROM store_credits");
        echo "<p><strong>Total credits in database:</strong> " . $count_all['total'] . "</p>";
        
        // Count for this customer
        $count_customer = $db->db_fetch_one("SELECT COUNT(*) as total FROM store_credits WHERE customer_id = $customer_id");
        echo "<p><strong>Credits for you (customer_id $customer_id):</strong> " . $count_customer['total'] . "</p>";
        
        // Count active credits
        $count_active = $db->db_fetch_one("SELECT COUNT(*) as total FROM store_credits WHERE customer_id = $customer_id AND status = 'active'");
        echo "<p><strong>Active credits for you:</strong> " . $count_active['total'] . "</p>";
        
    } else {
        echo "<p class='error'>❌ store_credits table does NOT exist!</p>";
        echo "<p class='warning'>You need to create the store_credits table in your database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Cart page link
echo "<div class='box'>";
echo "<h2>4. Next Steps</h2>";
if ($store_credit_balance > 0) {
    echo "<p class='success'>✅ Go to cart page - you should see the store credits section!</p>";
    echo "<p><a href='views/cart.php' style='display:inline-block;background:#3b82f6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:600;'>Go to Cart Page →</a></p>";
} else {
    echo "<p class='warning'>You need to add store credits to your account first.</p>";
    echo "<p>You can add credits by:</p>";
    echo "<ul>";
    echo "<li>Submitting a device drop request and choosing 'Store Credit' as payment method</li>";
    echo "<li>Having an admin manually add credits to your account</li>";
    echo "<li>Processing a refund (credits will be issued)</li>";
    echo "</ul>";
    echo "<p><a href='views/device_drop.php' style='display:inline-block;background:#10b981;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:600;'>Submit Device Drop →</a></p>";
}
echo "</div>";

echo "</body></html>";
?>


