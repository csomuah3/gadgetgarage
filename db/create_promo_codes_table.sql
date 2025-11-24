-- Create promo_codes table for discount functionality
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `promo_id` int(11) NOT NULL AUTO_INCREMENT,
  `promo_code` varchar(50) NOT NULL UNIQUE,
  `promo_description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `end_date` datetime DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`promo_id`),
  KEY `idx_promo_code` (`promo_code`),
  KEY `idx_active_codes` (`is_active`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample promo codes
INSERT INTO `promo_codes` (`promo_code`, `promo_description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `end_date`) VALUES
('BLACKFRIDAY20', 'Black Friday 20% Off', 'percentage', 20.00, 50.00, 100.00, '2025-12-31 23:59:59'),
('SAVE10', 'Save 10% Off', 'percentage', 10.00, 0.00, NULL, '2025-12-31 23:59:59'),
('WELCOME15', 'Welcome 15% Off', 'percentage', 15.00, 25.00, 50.00, '2025-12-31 23:59:59'),
('NEWUSER', 'New User 25% Off', 'percentage', 25.00, 100.00, 200.00, '2025-12-31 23:59:59'),
('FLAT50', 'Flat GHS 50 Off', 'fixed', 50.00, 200.00, NULL, '2025-12-31 23:59:59');