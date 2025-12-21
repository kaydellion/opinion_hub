-- Add additional site configuration settings that admin can edit
INSERT INTO site_settings (setting_key, setting_value, setting_type, description, category) VALUES

-- Site Configuration
('site_name', 'Opinion Hub NG', 'text', 'Website name/title', 'site_config'),
('site_tagline', 'What Gets Measured, Gets Done!', 'text', 'Site tagline/slogan', 'site_config'),
('site_email', 'hello@opinionhub.ng', 'text', 'Primary site email', 'site_config'),
('site_phone', '+234 (0) 803 3782 777', 'text', 'Primary site phone number', 'site_config'),
('site_url', 'http://localhost/opinion/', 'text', 'Base URL of the website (include trailing slash)', 'site_config'),
('site_favicon', 'favicon.jpg', 'text', 'Favicon filename (place in root directory)', 'site_config'),
('site_logo', 'logo.png', 'text', 'Logo filename (place in root directory or assets folder)', 'site_config'),

-- API Keys - Paystack
('paystack_public_key', 'pk_test_YOUR_PAYSTACK_PUBLIC_KEY', 'text', 'Paystack public key from dashboard.paystack.com', 'payment_api'),
('paystack_secret_key', 'sk_test_YOUR_PAYSTACK_SECRET_KEY', 'text', 'Paystack secret key (keep secure!)', 'payment_api'),
('paystack_callback_url', 'payment-callback.php', 'text', 'Paystack payment callback URL (relative to site URL)', 'payment_api'),

-- API Keys - Termii SMS
('termii_api_key', 'YOUR_TERMII_API_KEY', 'text', 'Termii API key from termii.com', 'sms_api'),
('termii_sender_id', 'OpinionHub', 'text', 'SMS sender ID (11 characters max)', 'sms_api'),

-- API Keys - Brevo Email
('brevo_api_key', 'YOUR_BREVO_API_KEY', 'text', 'Brevo (Sendinblue) API key from brevo.com', 'email_api'),
('brevo_from_email', 'noreply@opinionhub.ng', 'text', 'Email address for sending emails', 'email_api'),
('brevo_from_name', 'Opinion Hub NG', 'text', 'Name shown as email sender', 'email_api'),

-- API Keys - WhatsApp
('whatsapp_api_url', 'YOUR_WHATSAPP_API_URL', 'text', 'WhatsApp Business API endpoint', 'whatsapp_api'),
('whatsapp_api_key', 'YOUR_WHATSAPP_API_KEY', 'text', 'WhatsApp Business API key', 'whatsapp_api');
