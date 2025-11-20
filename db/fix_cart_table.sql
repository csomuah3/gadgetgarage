-- Add condition and price columns to cart table for condition-based pricing
ALTER TABLE cart
ADD COLUMN condition_type VARCHAR(20) DEFAULT 'excellent',
ADD COLUMN final_price DECIMAL(10,2) DEFAULT 0.00;