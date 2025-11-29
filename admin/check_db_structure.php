<?php
/**
 * Database Structure Checker for Refunds & Ratings Page
 * Run this file in your browser to check table structures
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';

// Check admin access
if (!check_login() || !check_admin()) {
    die("Access denied. Admin login required.");
}

$db = new db_connection();
$db->db_connect();
$conn = $db->db_conn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Check - Refunds & Ratings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            padding: 2rem;
        }
        .check-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table-check {
            font-size: 0.9rem;
        }
        .status-ok {
            color: #22c55e;
            font-weight: 600;
        }
        .status-missing {
            color: #ef4444;
            font-weight: 600;
        }
        .status-warning {
            color: #f59e0b;
            font-weight: 600;
        }
        h2 {
            color: #1e3a8a;
            margin-bottom: 1rem;
        }
        .section-title {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">
            <i class="fas fa-database"></i> Database Structure Check
            <small class="text-muted">Refunds & Ratings Tables</small>
        </h1>

        <?php
        // Function to check if table exists
        function tableExists($conn, $tableName) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
            return mysqli_num_rows($result) > 0;
        }

        // Function to get table columns
        function getTableColumns($conn, $tableName) {
            $columns = [];
            $result = mysqli_query($conn, "DESCRIBE $tableName");
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns[] = $row;
                }
            }
            return $columns;
        }

        // Function to check if column exists
        function columnExists($columns, $columnName) {
            foreach ($columns as $col) {
                if ($col['Field'] === $columnName) {
                    return true;
                }
            }
            return false;
        }

        // ============================================
        // CHECK REFUND_REQUESTS TABLE
        // ============================================
        ?>
        <div class="check-card">
            <div class="section-title">
                <h2 class="mb-0"><i class="fas fa-money-bill-wave"></i> Refund Requests Table</h2>
            </div>

            <?php
            $refundTableExists = tableExists($conn, 'refund_requests');
            
            if ($refundTableExists) {
                echo '<p class="status-ok"><i class="fas fa-check-circle"></i> Table "refund_requests" EXISTS</p>';
                $refundColumns = getTableColumns($conn, 'refund_requests');
                
                // Required columns for refunds page
                $requiredRefundColumns = [
                    'refund_id' => 'Primary key',
                    'order_id' => 'Order reference',
                    'customer_id' => 'Customer ID',
                    'first_name' => 'Customer first name',
                    'last_name' => 'Customer last name',
                    'email' => 'Customer email',
                    'phone' => 'Customer phone',
                    'refund_amount' => 'Refund amount',
                    'reason_for_refund' => 'Refund reason',
                    'status' => 'Status (pending/approved/rejected)',
                    'request_date' => 'Request date'
                ];
                
                echo '<h4 class="mt-3">Table Structure:</h4>';
                echo '<table class="table table-bordered table-check">';
                echo '<thead><tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($refundColumns as $col) {
                    $isRequired = isset($requiredRefundColumns[$col['Field']]);
                    $status = $isRequired ? '<span class="status-ok">Required</span>' : '<span class="text-muted">Optional</span>';
                    
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                
                // Check for required columns
                echo '<h4 class="mt-3">Required Columns Check:</h4>';
                echo '<ul>';
                foreach ($requiredRefundColumns as $colName => $description) {
                    $exists = columnExists($refundColumns, $colName);
                    $icon = $exists ? '<i class="fas fa-check-circle status-ok"></i>' : '<i class="fas fa-times-circle status-missing"></i>';
                    $status = $exists ? 'status-ok' : 'status-missing';
                    echo "<li class='$status'>$icon <strong>$colName</strong> - $description</li>";
                }
                echo '</ul>';
                
                // Check status values
                $statusQuery = "SELECT DISTINCT status, COUNT(*) as count FROM refund_requests GROUP BY status";
                $statusResult = mysqli_query($conn, $statusQuery);
                if ($statusResult && mysqli_num_rows($statusResult) > 0) {
                    echo '<h4 class="mt-3">Status Values Found:</h4>';
                    echo '<ul>';
                    while ($row = mysqli_fetch_assoc($statusResult)) {
                        echo '<li><strong>' . htmlspecialchars($row['status']) . '</strong>: ' . $row['count'] . ' records</li>';
                    }
                    echo '</ul>';
                }
                
            } else {
                echo '<p class="status-missing"><i class="fas fa-times-circle"></i> Table "refund_requests" DOES NOT EXIST</p>';
                echo '<p class="text-muted">You may need to create this table. Check your database migrations or SQL files.</p>';
            }
            ?>
        </div>

        <?php
        // ============================================
        // CHECK USER_RATINGS TABLE
        // ============================================
        ?>
        <div class="check-card">
            <div class="section-title">
                <h2 class="mb-0"><i class="fas fa-star"></i> User Ratings Table</h2>
            </div>

            <?php
            $ratingsTableExists = tableExists($conn, 'user_ratings');
            
            if ($ratingsTableExists) {
                echo '<p class="status-ok"><i class="fas fa-check-circle"></i> Table "user_ratings" EXISTS</p>';
                $ratingsColumns = getTableColumns($conn, 'user_ratings');
                
                // Required columns for ratings page
                $requiredRatingsColumns = [
                    'rating_id' => 'Primary key',
                    'user_id' => 'User/Customer ID',
                    'rating' => 'Rating value (1-5)',
                    'comment' => 'Rating comment/review',
                    'order_id' => 'Order ID (if linked to order)',
                    'product_id' => 'Product ID (if linked to product)',
                    'rating_date' => 'Rating date'
                ];
                
                echo '<h4 class="mt-3">Table Structure:</h4>';
                echo '<table class="table table-bordered table-check">';
                echo '<thead><tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($ratingsColumns as $col) {
                    $isRequired = isset($requiredRatingsColumns[$col['Field']]);
                    $status = $isRequired ? '<span class="status-ok">Required</span>' : '<span class="text-muted">Optional</span>';
                    
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                
                // Check for required columns
                echo '<h4 class="mt-3">Required Columns Check:</h4>';
                echo '<ul>';
                foreach ($requiredRatingsColumns as $colName => $description) {
                    $exists = columnExists($ratingsColumns, $colName);
                    $icon = $exists ? '<i class="fas fa-check-circle status-ok"></i>' : '<i class="fas fa-times-circle status-missing"></i>';
                    $status = $exists ? 'status-ok' : 'status-missing';
                    echo "<li class='$status'>$icon <strong>$colName</strong> - $description</li>";
                }
                echo '</ul>';
                
                // Check rating distribution
                $ratingDistQuery = "SELECT rating, COUNT(*) as count FROM user_ratings GROUP BY rating ORDER BY rating DESC";
                $ratingDistResult = mysqli_query($conn, $ratingDistQuery);
                if ($ratingDistResult && mysqli_num_rows($ratingDistResult) > 0) {
                    echo '<h4 class="mt-3">Rating Distribution:</h4>';
                    echo '<ul>';
                    while ($row = mysqli_fetch_assoc($ratingDistResult)) {
                        $stars = str_repeat('⭐', $row['rating']);
                        echo '<li><strong>' . $stars . ' (' . $row['rating'] . ' stars)</strong>: ' . $row['count'] . ' ratings</li>';
                    }
                    echo '</ul>';
                }
                
            } else {
                echo '<p class="status-missing"><i class="fas fa-times-circle"></i> Table "user_ratings" DOES NOT EXIST</p>';
                echo '<p class="text-muted">You may need to create this table. Check your database migrations or SQL files.</p>';
            }
            ?>
        </div>

        <?php
        // ============================================
        // SUMMARY
        // ============================================
        ?>
        <div class="check-card">
            <div class="section-title">
                <h2 class="mb-0"><i class="fas fa-clipboard-check"></i> Summary</h2>
            </div>
            
            <?php
            $allGood = $refundTableExists && $ratingsTableExists;
            
            if ($allGood) {
                echo '<div class="alert alert-success">';
                echo '<h4><i class="fas fa-check-circle"></i> All Required Tables Exist!</h4>';
                echo '<p>You can proceed with creating the Refunds & Ratings admin page.</p>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-warning">';
                echo '<h4><i class="fas fa-exclamation-triangle"></i> Missing Tables Detected</h4>';
                echo '<ul>';
                if (!$refundTableExists) {
                    echo '<li>Table "refund_requests" is missing</li>';
                }
                if (!$ratingsTableExists) {
                    echo '<li>Table "user_ratings" is missing</li>';
                }
                echo '</ul>';
                echo '<p>Please create the missing tables before proceeding.</p>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="check-card">
            <h3><i class="fas fa-info-circle"></i> Next Steps</h3>
            <ol>
                <li>Review the table structures above</li>
                <li>Verify all required columns exist</li>
                <li>Check the status values and data formats</li>
                <li>If everything looks good, proceed with page creation</li>
                <li>If tables are missing, create them first</li>
            </ol>
        </div>
    </div>
</body>
</html>

