<?php
// Simple test for promo code validation
header('Content-Type: application/json');

try {
    require_once('settings/core.php');
    require_once('settings/db_class.php');

    echo "Testing promo code validation...\n";

    // Test database connection
    $db = new db_connection();
    if (!$db->db_connect()) {
        echo "Database connection failed\n";
        exit;
    }

    $conn = $db->db_conn();
    echo "Database connection successful\n";

    // Test if promo_codes table exists
    $result = $conn->query("SHOW TABLES LIKE 'promo_codes'");
    if ($result->num_rows > 0) {
        echo "promo_codes table exists\n";

        // Check promo codes in table
        $result = $conn->query("SELECT * FROM promo_codes WHERE is_active = 1");
        echo "Active promo codes: " . $result->num_rows . "\n";

        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['promo_code'] . " (" . $row['discount_type'] . ": " . $row['discount_value'] . ")\n";
        }
    } else {
        echo "promo_codes table does NOT exist\n";
    }

    // Test specific promo code
    $promo_code = 'BLACKFRIDAY20';
    $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE promo_code = ? AND is_active = 1");
    $stmt->bind_param('s', $promo_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "BLACKFRIDAY20 promo code found!\n";
        $promo = $result->fetch_assoc();
        echo "Type: " . $promo['discount_type'] . ", Value: " . $promo['discount_value'] . "\n";
    } else {
        echo "BLACKFRIDAY20 promo code NOT found\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>