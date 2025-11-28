<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Registration Test</h2>";

// Include required files
require_once __DIR__ . '/settings/db_class.php';

echo "<h3>1. Database Connection Test</h3>";
$db = new db_connection();
if ($db->db_connect()) {
    echo "✅ Database connected successfully<br>";

    // Check table structure
    echo "<h3>2. Customer Table Structure</h3>";
    $result = mysqli_query($db->db, "DESCRIBE customer");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Failed to describe table: " . mysqli_error($db->db);
    }

    // Test a simple insert
    echo "<h3>3. Test Insert</h3>";
    $test_sql = "INSERT INTO customer (
        customer_name,
        customer_email,
        customer_pass,
        customer_contact,
        user_role,
        customer_country,
        customer_city
    ) VALUES (
        'Test User',
        'test" . time() . "@example.com',
        '" . password_hash('testpass', PASSWORD_DEFAULT) . "',
        '0123456789',
        1,
        'Ghana',
        'Accra'
    )";

    echo "SQL Query: <pre>" . htmlspecialchars($test_sql) . "</pre>";

    if (mysqli_query($db->db, $test_sql)) {
        $insert_id = mysqli_insert_id($db->db);
        echo "✅ Insert successful! User ID: " . $insert_id . "<br>";
    } else {
        echo "❌ Insert failed: " . mysqli_error($db->db) . "<br>";
        echo "Error number: " . mysqli_errno($db->db) . "<br>";
    }

} else {
    echo "❌ Database connection failed<br>";
}
?>