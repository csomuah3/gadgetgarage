<?php
/**
 * Backfill Store Credits Script
 * Creates store credits for approved device drops that don't have credits yet
 * Run this once to fix existing approved device drops
 */

session_start();
require_once __DIR__ . '/../settings/core.php';
require_admin(); // Only admins can run this

require_once __DIR__ . '/../settings/db_class.php';

$db = new db_connection();
if (!$db->db_connect()) {
    die("Database connection failed");
}

echo "<h2>Backfilling Store Credits for Approved Device Drops</h2>";
echo "<pre>";

// Get all approved device drops with store_credit payment method that don't have store credits yet
$sql = "SELECT 
            ddr.id as request_id,
            ddr.email,
            ddr.final_amount,
            ddr.payment_method,
            ddr.status,
            ddr.created_at,
            c.customer_id
        FROM device_drop_requests ddr
        LEFT JOIN customer c ON c.customer_email = ddr.email
        LEFT JOIN store_credits sc ON sc.device_drop_id = ddr.id
        WHERE ddr.status = 'approved'
        AND ddr.payment_method = 'store_credit'
        AND ddr.final_amount > 0
        AND sc.credit_id IS NULL
        ORDER BY ddr.id ASC";

$requests = $db->db_fetch_all($sql) ?: [];

if (empty($requests)) {
    echo "No device drops found that need store credits.\n";
    echo "All approved store credit device drops already have credits created.\n";
    exit;
}

echo "Found " . count($requests) . " approved device drops that need store credits:\n\n";

$success_count = 0;
$failed_count = 0;
$skipped_count = 0;

foreach ($requests as $request) {
    $request_id = $request['request_id'];
    $email = $request['email'];
    $final_amount = floatval($request['final_amount']);
    $customer_id = $request['customer_id'];
    
    echo "Processing Request #$request_id:\n";
    echo "  Email: $email\n";
    echo "  Amount: GH₵ " . number_format($final_amount, 2) . "\n";
    
    if (!$customer_id) {
        echo "  ❌ SKIPPED: Customer not found for email: $email\n";
        $skipped_count++;
        echo "\n";
        continue;
    }
    
    echo "  Customer ID: $customer_id\n";
    
    // Check if credit already exists (double-check)
    $check_sql = "SELECT credit_id FROM store_credits WHERE device_drop_id = $request_id LIMIT 1";
    $existing = $db->db_fetch_one($check_sql);
    
    if ($existing) {
        echo "  ⚠️  SKIPPED: Store credit already exists for this device drop\n";
        $skipped_count++;
        echo "\n";
        continue;
    }
    
    // Generate credit reference ID
    $credit_reference = 'DDC' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
    
    // Insert store credit using correct database schema
    $credit_sql = "INSERT INTO store_credits (
        customer_id, 
        credit_amount, 
        remaining_amount, 
        source, 
        device_drop_id,
        admin_notes, 
        status, 
        expires_at, 
        created_at, 
        admin_verified, 
        verified_at
    ) VALUES (
        $customer_id,
        $final_amount,
        $final_amount,
        '$credit_reference',
        $request_id,
        'Store credit from device drop request #$request_id (backfilled)',
        'active',
        DATE_ADD(NOW(), INTERVAL 1 YEAR),
        NOW(),
        1,
        NOW()
    )";
    
    if ($db->db_write_query($credit_sql)) {
        $credit_id = mysqli_insert_id($db->db_conn());
        echo "  ✅ SUCCESS: Store credit created (Credit ID: $credit_id)\n";
        $success_count++;
    } else {
        $error = mysqli_error($db->db_conn());
        echo "  ❌ FAILED: " . $error . "\n";
        $failed_count++;
    }
    
    echo "\n";
}

echo "\n";
echo "========================================\n";
echo "SUMMARY:\n";
echo "========================================\n";
echo "Total processed: " . count($requests) . "\n";
echo "✅ Successfully created: $success_count\n";
echo "❌ Failed: $failed_count\n";
echo "⚠️  Skipped: $skipped_count\n";
echo "========================================\n";

echo "</pre>";
?>

