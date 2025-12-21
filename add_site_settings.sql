-- Create site_settings table for admin-configurable values
CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(setting_key),
    INDEX(category)
);

-- Insert default values for agent-related settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description, category) VALUES
-- Agent Commission & Payment
('agent_commission_per_poll', '1000', 'number', 'Amount (₦) agents earn per completed poll response', 'agent_earnings'),
('agent_min_payout', '5000', 'number', 'Minimum amount (₦) for agent payout requests', 'agent_earnings'),
('agent_payment_processing_days', '5', 'number', 'Number of working days to process agent payouts', 'agent_earnings'),

-- Agent Approval
('agent_approval_timeline_hours', '48', 'number', 'Expected hours for agent application approval', 'agent_approval'),
('agent_auto_approval', 'false', 'boolean', 'Automatically approve agent applications without review', 'agent_approval'),

-- Poll Settings
('poll_response_required_login', 'true', 'boolean', 'Require users to login before answering polls', 'polls'),
('poll_default_duration_days', '30', 'number', 'Default duration for new polls in days', 'polls'),

-- Email Settings
('email_from_name', 'Opinion Hub NG', 'text', 'Sender name for system emails', 'email'),
('email_from_address', 'hello@opinionhub.ng', 'text', 'Sender email address for system emails', 'email'),
('email_enabled', 'true', 'boolean', 'Enable/disable email notifications', 'email'),

-- SMS Settings
('sms_enabled', 'false', 'boolean', 'Enable/disable SMS notifications', 'sms'),
('sms_sender_id', 'OpinionHub', 'text', 'SMS sender ID/name', 'sms'),

-- Company Info
('company_name', 'Foraminifera Market Research Limited', 'text', 'Full company legal name', 'company'),
('company_address', '61-65 Egbe-Isolo Road, Iyana Ejigbo Shopping Arcade, Block A, Suite 19, Ejigbo, Lagos', 'text', 'Company physical address', 'company'),
('company_phone', '+234 (0) 803 3782 777', 'text', 'Primary contact phone number', 'company'),
('company_email', 'hello@opinionhub.ng', 'text', 'Primary contact email address', 'company'),

-- Advertisement Rates (per view)
('ad_rate_top_banner', '5', 'number', 'Top banner ad rate per view (₦)', 'advertising'),
('ad_rate_sidebar', '3', 'number', 'Sidebar ad rate per view (₦)', 'advertising'),
('ad_rate_footer', '1.5', 'number', 'Footer ad rate per view (₦)', 'advertising'),
('ad_rate_in_poll', '4', 'number', 'In-poll ad rate per view (₦)', 'advertising'),

-- Pricing Plans (monthly cost / response limit)
('plan_basic_cost', '65000', 'number', 'Basic plan monthly cost (₦)', 'pricing'),
('plan_basic_responses', '650000', 'number', 'Basic plan response limit', 'pricing'),
('plan_classic_cost', '85000', 'number', 'Classic plan monthly cost (₦)', 'pricing'),
('plan_classic_responses', '950000', 'number', 'Classic plan response limit', 'pricing'),
('plan_enterprise_cost', '120000', 'number', 'Enterprise plan monthly cost (₦)', 'pricing'),
('plan_enterprise_responses', '1150000', 'number', 'Enterprise plan response limit', 'pricing'),

-- Platform Settings
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode (disable public access)', 'system'),
('registration_enabled', 'true', 'boolean', 'Allow new user registrations', 'system'),
('agent_registration_enabled', 'true', 'boolean', 'Allow users to apply as agents', 'system');
