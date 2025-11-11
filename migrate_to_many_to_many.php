<?php
/**
 * Migration Script: Convert Brand-Category relationship from One-to-Many to Many-to-Many
 * This script safely migrates existing data without losing any information
 */

require_once __DIR__ . '/settings/db_class.php';

try {
    $db = new db_connection();
    $connection = $db->db_connect();

    if (!$connection) {
        die("Database connection failed");
    }

    echo "<h2>🔄 Migrating to Many-to-Many Brand-Category System</h2>";
    echo "<div style='font-family: Arial; line-height: 1.6;'>";

    // Step 1: Create brand_categories junction table
    echo "<h3>Step 1: Creating brand_categories junction table</h3>";

    $sql_check = "SHOW TABLES LIKE 'brand_categories'";
    $result = $db->db_fetch_one($sql_check);

    if (!$result) {
        $sql_create_junction = "
        CREATE TABLE brand_categories (
            id INT(11) NOT NULL AUTO_INCREMENT,
            brand_id INT(11) NOT NULL,
            category_id INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_brand_category (brand_id, category_id),
            FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(cat_id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_brand_id (brand_id),
            INDEX idx_category_id (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if ($db->db_write_query($sql_create_junction)) {
            echo "✅ brand_categories junction table created successfully<br>";
        } else {
            throw new Exception("Failed to create brand_categories table");
        }
    } else {
        echo "ℹ️ brand_categories table already exists<br>";
    }

    // Step 2: Check current brands table structure
    echo "<h3>Step 2: Analyzing current brands table</h3>";
    $sql = "SHOW COLUMNS FROM brands";
    $columns = $db->db_fetch_all($sql);

    $has_category_id = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'category_id') {
            $has_category_id = true;
            break;
        }
    }

    if ($has_category_id) {
        echo "✅ Found existing category_id column in brands table<br>";

        // Step 3: Migrate existing brand-category relationships
        echo "<h3>Step 3: Migrating existing brand-category relationships</h3>";

        $sql = "SELECT brand_id, category_id FROM brands WHERE category_id IS NOT NULL AND category_id > 0";
        $existing_brands = $db->db_fetch_all($sql);

        $migrated_count = 0;
        if ($existing_brands) {
            foreach ($existing_brands as $brand) {
                $brand_id = (int)$brand['brand_id'];
                $category_id = (int)$brand['category_id'];

                // Insert into junction table (ignore duplicates)
                $sql_insert = "INSERT IGNORE INTO brand_categories (brand_id, category_id)
                              VALUES ($brand_id, $category_id)";

                if ($db->db_write_query($sql_insert)) {
                    $migrated_count++;
                }
            }
            echo "✅ Migrated $migrated_count brand-category relationships<br>";
        } else {
            echo "ℹ️ No existing brand-category relationships to migrate<br>";
        }

        // Step 4: Remove category_id column from brands table (optional)
        echo "<h3>Step 4: Removing category_id column from brands table</h3>";
        echo "<p style='background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;'>";
        echo "<strong>⚠️ Warning:</strong> This will remove the category_id column from the brands table. ";
        echo "All relationships have been migrated to the junction table.";
        echo "</p>";

        // Uncomment the next lines if you want to automatically remove the column
        /*
        $sql_drop_column = "ALTER TABLE brands DROP COLUMN category_id";
        if ($db->db_write_query($sql_drop_column)) {
            echo "✅ Removed category_id column from brands table<br>";
        } else {
            echo "❌ Failed to remove category_id column<br>";
        }
        */
        echo "📝 <strong>Manual Action Required:</strong> Run this SQL command when you're ready:<br>";
        echo "<code style='background: #f8f9fa; padding: 5px; display: block; margin: 10px 0;'>ALTER TABLE brands DROP COLUMN category_id;</code>";

    } else {
        echo "ℹ️ No category_id column found in brands table - already migrated or using different structure<br>";
    }

    // Step 5: Verify migration
    echo "<h3>Step 5: Verification</h3>";

    // Count brands
    $sql = "SELECT COUNT(*) as brand_count FROM brands";
    $brand_result = $db->db_fetch_one($sql);
    echo "📊 Total brands: " . ($brand_result['brand_count'] ?? 0) . "<br>";

    // Count categories
    $sql = "SELECT COUNT(*) as category_count FROM categories";
    $category_result = $db->db_fetch_one($sql);
    echo "📊 Total categories: " . ($category_result['category_count'] ?? 0) . "<br>";

    // Count brand-category relationships
    $sql = "SELECT COUNT(*) as relationship_count FROM brand_categories";
    $relationship_result = $db->db_fetch_one($sql);
    echo "📊 Brand-Category relationships: " . ($relationship_result['relationship_count'] ?? 0) . "<br>";

    // Show sample data
    echo "<h3>Sample Brand-Category Relationships</h3>";
    $sql = "SELECT b.brand_name, c.cat_name
            FROM brand_categories bc
            JOIN brands b ON bc.brand_id = b.brand_id
            JOIN categories c ON bc.category_id = c.cat_id
            LIMIT 10";
    $sample_data = $db->db_fetch_all($sql);

    if ($sample_data) {
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'><th style='border: 1px solid #dee2e6; padding: 8px;'>Brand</th><th style='border: 1px solid #dee2e6; padding: 8px;'>Category</th></tr>";
        foreach ($sample_data as $row) {
            echo "<tr><td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['brand_name']}</td><td style='border: 1px solid #dee2e6; padding: 8px;'>{$row['cat_name']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "No relationships found yet.<br>";
    }

    echo "<h3 style='color: green;'>✅ Migration Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Update your PHP code to use the many-to-many relationship</li>";
    echo "<li>Test the new functionality</li>";
    echo "<li>Remove the category_id column from brands table when satisfied</li>";
    echo "</ol>";

    echo "<p><a href='admin/brand.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Go to Brand Management</a></p>";

    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>