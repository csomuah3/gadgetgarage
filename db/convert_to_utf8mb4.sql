-- Convert products table and related tables from latin1 to utf8mb4
-- Run this in phpMyAdmin or MySQL command line

-- Convert products table
ALTER TABLE products 
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Convert product_desc column specifically (in case it needs special handling)
ALTER TABLE products 
  MODIFY product_desc VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE products 
  MODIFY product_title VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE products 
  MODIFY product_image VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE products 
  MODIFY product_keywords VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE products 
  MODIFY product_color VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Convert other related tables (optional but recommended)
ALTER TABLE categories 
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE brands 
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE customer 
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

