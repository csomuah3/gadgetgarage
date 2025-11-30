<?php
// Test file to add store credits to your account
session_start();
require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/helpers/store_credit_helper.php');

// Check if logged in
if (!check_login()) {
    die("Please <a href='login/user_login.php'>login</a> first to add test store credits.");
}

$customer_id = $_SESSION['user_id'];
$storeCreditHelper = new StoreCreditHelper();

// Check current balance
$current_balance = $storeCreditHelper->getTotalAvailableCredit($customer_id);

echo "<h1>Store Credits Test Page</h1>";
echo "<p><strong>Customer ID:</strong> $customer_id</p>";
echo "<p><strong>Current Balance:</strong> GH₵ " . number_format($current_balance, 2) . "</p>";
echo "<hr>";

// Add test credits if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credits'])) {
    $amount = floatval($_POST['amount']);
    
    if ($amount > 0) {
        $result = $storeCreditHelper->createStoreCredit(
            $customer_id,
            $amount,
            'manual',
            'TEST-' . time(),
            'Test store credit for testing cart functionality',
            365 // Expires in 1 year
        );
        
        if ($result) {
            echo "<div style='background: #10b981; color: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "✅ Successfully added GH₵ " . number_format($amount, 2) . " in store credits!";
            echo "</div>";
            
            // Refresh balance
            $current_balance = $storeCreditHelper->getTotalAvailableCredit($customer_id);
            echo "<p><strong>New Balance:</strong> GH₵ " . number_format($current_balance, 2) . "</p>";
        } else {
            echo "<div style='background: #ef4444; color: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "❌ Failed to add store credits.";
            echo "</div>";
        }
    }
}

// Show existing credits
$credits = $storeCreditHelper->getAvailableCredits($customer_id);
if (!empty($credits)) {
    echo "<h2>Your Store Credits:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th>Credit ID</th><th>Amount</th><th>Available</th><th>Source</th><th>Created</th><th>Expires</th>";
    echo "</tr>";
    
    foreach ($credits as $credit) {
        echo "<tr>";
        echo "<td>" . $credit['credit_id'] . "</td>";
        echo "<td>GH₵ " . number_format($credit['amount'], 2) . "</td>";
        echo "<td>GH₵ " . number_format($credit['available_amount'], 2) . "</td>";
        echo "<td>" . ucfirst($credit['source_type']) . "</td>";
        echo "<td>" . $credit['created_at'] . "</td>";
        echo "<td>" . ($credit['expires_at'] ?? 'Never') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

?>

<hr>
<h2>Add Test Store Credits</h2>
<form method="POST" style="max-width: 400px;">
    <div style="margin-bottom: 15px;">
        <label for="amount" style="display: block; margin-bottom: 5px;"><strong>Amount (GH₵):</strong></label>
        <input type="number" id="amount" name="amount" value="1000" step="0.01" min="1" 
               style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 16px;">
    </div>
    
    <button type="submit" name="add_credits" 
            style="background: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px;">
        Add Store Credits
    </button>
</form>

<hr>
<p><a href="views/cart.php" style="color: #3b82f6; text-decoration: none; font-weight: 600;">← Go to Cart Page</a></p>
<p><a href="views/store_credits.php" style="color: #3b82f6; text-decoration: none; font-weight: 600;">View Store Credits Page →</a></p>

<style>
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        background: #f9fafb;
    }
    
    h1 {
        color: #1f2937;
        margin-bottom: 10px;
    }
    
    h2 {
        color: #374151;
        margin-top: 30px;
        margin-bottom: 15px;
    }
    
    button:hover {
        background: #2563eb !important;
        transform: translateY(-1px);
    }
</style>


