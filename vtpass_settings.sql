-- VTPass API Settings for Admin Panel
-- Run this SQL to add VTPass configuration to site_settings table

INSERT INTO site_settings (setting_key, setting_value, setting_type, category, description) VALUES
('vtpass_enabled', '0', 'boolean', 'vtpass_api', 'Enable/Disable VTPass integration for airtime and data'),
('vtpass_api_url', 'https://sandbox.vtpass.com/api/', 'text', 'vtpass_api', 'VTPass API URL (sandbox: https://sandbox.vtpass.com/api/ | live: https://vtpass.com/api/)'),
('vtpass_api_key', '', 'text', 'vtpass_api', 'Your VTPass API Key from dashboard'),
('vtpass_public_key', '', 'text', 'vtpass_api', 'Your VTPass Public Key from dashboard'),
('vtpass_secret_key', '', 'text', 'vtpass_api', 'Your VTPass Secret Key from dashboard')
ON DUPLICATE KEY UPDATE
    setting_type = VALUES(setting_type),
    category = VALUES(category),
    description = VALUES(description);
