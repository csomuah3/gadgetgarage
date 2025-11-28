-- SQL Queries to Update Database from Arkesel to Brevo
-- Run these queries in your MySQL database

-- 1. Update SMS settings table if it exists
UPDATE sms_settings SET
    setting_name = 'brevo_api_key',
    setting_value = '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'
WHERE setting_name = 'arkesel_api_key';

UPDATE sms_settings SET
    setting_name = 'brevo_api_url',
    setting_value = 'https://api.brevo.com/v3/transactionalSMS/sms'
WHERE setting_name = 'arkesel_api_url';

UPDATE sms_settings SET
    setting_name = 'brevo_sender_id'
WHERE setting_name = 'arkesel_sender_id';

-- 2. Update SMS logs table if it exists - change provider reference
UPDATE sms_logs SET
    provider = 'brevo'
WHERE provider = 'arkesel';

-- 3. Update any SMS queue records
UPDATE sms_queue SET
    provider = 'brevo'
WHERE provider = 'arkesel';

-- 4. Update system settings if they exist
UPDATE settings SET
    setting_value = 'brevo'
WHERE setting_key = 'sms_provider' AND setting_value = 'arkesel';

-- 5. Insert new Brevo settings if settings table exists
INSERT IGNORE INTO sms_settings (setting_name, setting_value, description, is_active) VALUES
('brevo_api_key', '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', 'Brevo API Key', 1),
('brevo_api_url', 'https://api.brevo.com/v3/transactionalSMS/sms', 'Brevo API URL', 1),
('brevo_sender_id', 'Gadget-G', 'Brevo Sender ID', 1);

-- 6. Update any configuration references
UPDATE configuration SET
    config_value = 'brevo'
WHERE config_key = 'sms_provider' AND config_value = 'arkesel';

-- Note: Some of these tables might not exist in your database
-- Only run the queries for tables that actually exist in your system