<?php
require_once(__DIR__ . '/settings/db_class.php');

try {
    $db = new db_connection();

    // Create product_images table
    $sql = "
        CREATE TABLE IF NOT EXISTS product_images (
            image_id INT(11) NOT NULL AUTO_INCREMENT,
            product_id INT(11) NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            image_name VARCHAR(255) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            sort_order INT(11) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (image_id),
            KEY product_id (product_id),
            KEY is_primary (is_primary),
            KEY sort_order (sort_order),
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";

    if ($db->db_write_query($sql)) {
        echo "Product images table created successfully!\n";
    } else {
        echo "Error creating table\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>