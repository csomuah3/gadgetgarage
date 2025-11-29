-- ============================================
-- QUICK STRUCTURE CHECK - Run these 2 queries
-- ============================================

-- 1. Check refund_requests table structure
DESCRIBE refund_requests;

-- 2. Check user_ratings table structure  
DESCRIBE user_ratings;

-- 3. (Optional) Check product_ratings if it's different
DESCRIBE product_ratings;

-- 4. See sample data from refund_requests
SELECT * FROM refund_requests LIMIT 3;

-- 5. See sample data from user_ratings
SELECT * FROM user_ratings LIMIT 3;

