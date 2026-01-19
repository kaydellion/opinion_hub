-- Create settings table for configurable pricing
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'int', 'float', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default messaging prices
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('sms_price_free', '20', 'float', 'SMS price per unit for Free plan (₦)'),
('sms_price_basic', '18', 'float', 'SMS price per unit for Basic plan (₦)'),
('sms_price_classic', '17', 'float', 'SMS price per unit for Classic plan (₦)'),
('sms_price_enterprise', '16', 'float', 'SMS price per unit for Enterprise plan (₦)'),
('email_price_free', '10', 'float', 'Email price per unit for Free plan (₦)'),
('email_price_basic', '8', 'float', 'Email price per unit for Basic plan (₦)'),
('email_price_classic', '9', 'float', 'Email price per unit for Classic plan (₦)'),
('email_price_enterprise', '8', 'float', 'Email price per unit for Enterprise plan (₦)'),
('whatsapp_price_free', '24', 'float', 'WhatsApp price per unit for Free plan (₦)'),
('whatsapp_price_basic', '22', 'float', 'WhatsApp price per unit for Basic plan (₦)'),
('whatsapp_price_classic', '21', 'float', 'WhatsApp price per unit for Classic plan (₦)'),
('whatsapp_price_enterprise', '20', 'float', 'WhatsApp price per unit for Enterprise plan (₦)'),

-- Other configurable settings
('site_name', 'Opinion Hub NG', 'string', 'Website name'),
('site_description', 'Nigeria\'s Leading Poll Platform', 'string', 'Website description'),
('contact_email', 'support@opinionhub.ng', 'string', 'Support email address'),
('maintenance_mode', '0', 'int', 'Maintenance mode (0=off, 1=on)'),
('max_upload_size', '5242880', 'int', 'Maximum file upload size in bytes (5MB default)'),
('allowed_file_types', '["jpg","jpeg","png","gif","pdf","doc","docx"]', 'json', 'Allowed file types for uploads'),

-- Subscription pricing
('subscription_price_basic_monthly', '35000', 'int', 'Basic plan monthly price (₦)'),
('subscription_price_basic_annual', '392000', 'int', 'Basic plan annual price (₦)'),
('subscription_price_classic_monthly', '65000', 'int', 'Classic plan monthly price (₦)'),
('subscription_price_classic_annual', '735000', 'int', 'Classic plan annual price (₦)'),
('subscription_price_enterprise_monthly', '100000', 'int', 'Enterprise plan monthly price (₦)'),
('subscription_price_enterprise_annual', '1050000', 'int', 'Enterprise plan annual price (₦)'),

-- Credit packages
('sms_credits_basic_monthly', '500', 'int', 'SMS credits for Basic monthly plan'),
('sms_credits_basic_annual', '5000', 'int', 'SMS credits for Basic annual plan'),
('sms_credits_classic_monthly', '1000', 'int', 'SMS credits for Classic monthly plan'),
('sms_credits_classic_annual', '10000', 'int', 'SMS credits for Classic annual plan'),
('sms_credits_enterprise_monthly', '1500', 'int', 'SMS credits for Enterprise monthly plan'),
('sms_credits_enterprise_annual', '15000', 'int', 'SMS credits for Enterprise annual plan'),

('email_credits_basic_monthly', '500', 'int', 'Email credits for Basic monthly plan'),
('email_credits_basic_annual', '5000', 'int', 'Email credits for Basic annual plan'),
('email_credits_classic_monthly', '1000', 'int', 'Email credits for Classic monthly plan'),
('email_credits_classic_annual', '10000', 'int', 'Email credits for Classic annual plan'),
('email_credits_enterprise_monthly', '1500', 'int', 'Email credits for Enterprise monthly plan'),
('email_credits_enterprise_annual', '15000', 'int', 'Email credits for Enterprise annual plan'),

('whatsapp_credits_basic_monthly', '100', 'int', 'WhatsApp credits for Basic monthly plan'),
('whatsapp_credits_basic_annual', '1000', 'int', 'WhatsApp credits for Basic annual plan'),
('whatsapp_credits_classic_monthly', '500', 'int', 'WhatsApp credits for Classic monthly plan'),
('whatsapp_credits_classic_annual', '5000', 'int', 'WhatsApp credits for Classic annual plan'),
('whatsapp_credits_enterprise_monthly', '1000', 'int', 'WhatsApp credits for Enterprise monthly plan'),
('whatsapp_credits_enterprise_annual', '10000', 'int', 'WhatsApp credits for Enterprise annual plan');;


