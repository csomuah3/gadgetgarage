<?php
/**
 * Setup promo codes table and sample data
 */
require_once __DIR__ . '/settings/db_class.php';

try {
    $db = new db_connection();
    if (!$db->db_connect()) {
        throw new Exception('Database connection failed');
    }

    $conn = $db->db_conn();

    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/db/create_promo_codes_table.sql');

    if ($sql === false) {
        throw new Exception('Could not read SQL file');
    }

    // Split by semicolons and execute each statement
    $statements = explode(';', $sql);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            if (!$conn->query($statement)) {
                throw new Exception('SQL Error: ' . $conn->error);
            }
        }
    }

    echo "✅ Promo codes table created successfully!\n";
    echo "✅ Sample promo codes inserted!\n\n";

    // Display the inserted promo codes
    $result = $conn->query("SELECT promo_code, promo_description, discount_type, discount_value FROM promo_codes WHERE is_active = 1");

    if ($result && $result->num_rows > 0) {
        echo "Available Promo Codes:\n";
        echo "=====================\n";
        while ($row = $result->fetch_assoc()) {
            $value = $row['discount_type'] === 'percentage' ? $row['discount_value'] . '%' : 'GHS ' . $row['discount_value'];
            echo "• {$row['promo_code']} - {$row['promo_description']} ({$value})\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($db)) {
        $db->db_close();
    }
}
?>