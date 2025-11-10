<?php
require_once __DIR__ . '/settings/db_class.php';

try {
    $db = new db_connection();
    $connection = $db->db_connect();

    if (!$connection) {
        die("Database connection failed");
    }

    echo "<h2>Setting up Products Table</h2>";

    // Check if products table exists
    $sql = "SHOW TABLES LIKE 'products'";
    $result = $db->db_fetch_one($sql);

    if (!$result) {
        echo "<p>Creating products table...</p>";

        $createTableSQL = "
            CREATE TABLE products (
                product_id INT(11) NOT NULL AUTO_INCREMENT,
                product_title VARCHAR(255) NOT NULL,
                product_price DECIMAL(10,2) NOT NULL,
                promo_percentage INT(3) DEFAULT 0,
                product_desc TEXT,
                product_image VARCHAR(255),
                product_keywords VARCHAR(255),
                product_cat INT(11) NOT NULL,
                product_brand INT(11) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (product_id),
                KEY product_cat (product_cat),
                KEY product_brand (product_brand)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        if ($db->db_write_query($createTableSQL)) {
            echo "<p style='color: green;'>✅ Products table created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create products table</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Products table already exists</p>";
    }

    // Show table structure
    $sql = "DESCRIBE products";
    $columns = $db->db_fetch_all($sql);
    if ($columns) {
        echo "<h3>Products table structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<p><a href='admin/product.php' style='background: #8b5fbf; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Product Management</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>