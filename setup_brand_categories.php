<?php
require_once __DIR__ . '/settings/db_class.php';

try {
    $db = new db_connection();
    $connection = $db->db_connect();

    if (!$connection) {
        die("Database connection failed");
    }

    echo "<h2>Setting up Brand Categories System</h2>";

    // First, check current brands table structure
    $sql = "SHOW COLUMNS FROM brands";
    $columns = $db->db_fetch_all($sql);

    echo "<h3>Current brands table structure:</h3>";
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
    echo "</table><br>";

    // Add user_id column to brands table if it doesn't exist
    $has_user_id = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'user_id') {
            $has_user_id = true;
            break;
        }
    }

    if (!$has_user_id) {
        echo "<p>Adding user_id column to brands table...</p>";
        $sql = "ALTER TABLE brands ADD COLUMN user_id INT(11) NOT NULL DEFAULT 1";
        if ($db->db_write_query($sql)) {
            echo "<p style='color: green;'>✅ Added user_id column to brands table</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add user_id column</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ user_id column already exists in brands table</p>";
    }

    // Check if brand_categories junction table exists
    $sql = "SHOW TABLES LIKE 'brand_categories'";
    $result = $db->db_fetch_one($sql);

    if (!$result) {
        echo "<p>Creating brand_categories junction table...</p>";

        $createTableSQL = "
            CREATE TABLE brand_categories (
                brand_id INT(11) NOT NULL,
                category_id INT(11) NOT NULL,
                PRIMARY KEY (brand_id, category_id),
                FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(cat_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        if ($db->db_write_query($createTableSQL)) {
            echo "<p style='color: green;'>✅ brand_categories table created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create brand_categories table</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ brand_categories table already exists</p>";
    }

    // If brands table had a category_id column, migrate data to junction table
    $has_category_id = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'category_id') {
            $has_category_id = true;
            break;
        }
    }

    if ($has_category_id) {
        echo "<p>Migrating existing brand-category relationships...</p>";

        // Get all brands with category_id
        $sql = "SELECT brand_id, category_id FROM brands WHERE category_id IS NOT NULL AND category_id > 0";
        $brands = $db->db_fetch_all($sql);

        if ($brands) {
            foreach ($brands as $brand) {
                // Insert into junction table (ignore duplicates)
                $insertSQL = "INSERT IGNORE INTO brand_categories (brand_id, category_id)
                             VALUES ({$brand['brand_id']}, {$brand['category_id']})";
                $db->db_write_query($insertSQL);
            }
            echo "<p style='color: green;'>✅ Migrated " . count($brands) . " brand-category relationships</p>";

            // Now we can drop the category_id column from brands
            echo "<p>Removing category_id column from brands table...</p>";
            $sql = "ALTER TABLE brands DROP COLUMN category_id";
            if ($db->db_write_query($sql)) {
                echo "<p style='color: green;'>✅ Removed category_id column from brands table</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to remove category_id column</p>";
            }
        }
    }

    // Show final table structures
    echo "<h3>Final brand_categories table structure:</h3>";
    $sql = "DESCRIBE brand_categories";
    $columns = $db->db_fetch_all($sql);
    if ($columns) {
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

    echo "<h3>Updated brands table structure:</h3>";
    $sql = "DESCRIBE brands";
    $columns = $db->db_fetch_all($sql);
    if ($columns) {
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

    echo "<p style='color: green; font-weight: bold;'>✅ Brand categories system setup complete!</p>";
    echo "<p><a href='admin/brand.php' style='background: #8b5fbf; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Brand Management</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>