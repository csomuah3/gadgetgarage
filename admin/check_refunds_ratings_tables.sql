-- ============================================
-- DATABASE STRUCTURE CHECK FOR REFUNDS & RATINGS PAGE
-- ============================================
-- Paste these queries one by one in phpMyAdmin or your SQL client
-- to check if the required tables and columns exist

-- ============================================
-- 1. CHECK REFUND_REQUESTS TABLE STRUCTURE
-- ============================================
-- This will show all columns in the refund_requests table
DESCRIBE refund_requests;

-- Alternative: Show table structure with more details
SHOW CREATE TABLE refund_requests;

-- Check if table exists and get column info
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'refund_requests'
ORDER BY ORDINAL_POSITION;

-- ============================================
-- 2. CHECK USER_RATINGS TABLE STRUCTURE
-- ============================================
-- This will show all columns in the user_ratings table
DESCRIBE user_ratings;

-- Alternative: Show table structure with more details
SHOW CREATE TABLE user_ratings;

-- Check if table exists and get column info
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'user_ratings'
ORDER BY ORDINAL_POSITION;

-- ============================================
-- 3. CHECK IF TABLES EXIST
-- ============================================
-- Check if both tables exist
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    UPDATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('refund_requests', 'user_ratings');

-- ============================================
-- 4. SAMPLE DATA CHECK (Optional - to see data format)
-- ============================================
-- Get sample refund requests (limit to 5)
SELECT * FROM refund_requests LIMIT 5;

-- Get sample ratings (limit to 5)
SELECT * FROM user_ratings LIMIT 5;

-- ============================================
-- 5. CHECK REFUND STATUS VALUES
-- ============================================
-- See what status values exist in refund_requests
SELECT DISTINCT status FROM refund_requests;

-- Count refunds by status
SELECT 
    status,
    COUNT(*) as count
FROM refund_requests
GROUP BY status;

-- ============================================
-- 6. CHECK RATING DATA FORMAT
-- ============================================
-- See rating distribution
SELECT 
    rating,
    COUNT(*) as count
FROM user_ratings
GROUP BY rating
ORDER BY rating DESC;

-- Check if ratings are linked to products or orders
SELECT 
    'user_ratings columns' as info,
    GROUP_CONCAT(COLUMN_NAME) as columns
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'user_ratings';

-- ============================================
-- 7. CHECK RELATED TABLES (for JOINs)
-- ============================================
-- Check orders table structure (for refund order linking)
DESCRIBE orders;

-- Check customer table structure (for customer names)
DESCRIBE customer;

-- Check products table (if ratings are product-based)
DESCRIBE products;

