-- =====================================================
-- OPINION HUB NG - UNIFIED MIGRATION SCRIPT
-- =====================================================
-- This script combines all necessary database migrations
-- Run this ONCE in phpMyAdmin or MySQL command line
-- Database: opinionhub_ng
-- =====================================================

USE opinionhub_ng;

-- =====================================================
-- 1. CORE TABLE UPDATES
-- =====================================================

-- Add agent filtering columns to polls table
ALTER TABLE polls
ADD COLUMN IF NOT EXISTS agent_age_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected age groups',
ADD COLUMN IF NOT EXISTS agent_gender_criteria TEXT DEFAULT '[\"both\"]' COMMENT 'JSON array of selected genders',
ADD COLUMN IF NOT EXISTS agent_state_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected state for location filtering',
ADD COLUMN IF NOT EXISTS agent_lga_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected LGA for location filtering',
ADD COLUMN IF NOT EXISTS agent_location_all TINYINT(1) DEFAULT 1 COMMENT 'Whether to include all Nigeria locations',
ADD COLUMN IF NOT EXISTS agent_occupation_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected occupations',
ADD COLUMN IF NOT EXISTS agent_education_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected education levels',
ADD COLUMN IF NOT EXISTS agent_employment_criteria TEXT DEFAULT '[\"both\"]' COMMENT 'JSON array of selected employment status',
ADD COLUMN IF NOT EXISTS agent_income_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected income ranges',
ADD COLUMN IF NOT EXISTS agent_commission DECIMAL(10, 2) DEFAULT 1000 AFTER cost_per_email,
ADD COLUMN IF NOT EXISTS results_for_sale BOOLEAN DEFAULT FALSE AFTER results_private,
ADD COLUMN IF NOT EXISTS results_sale_price DECIMAL(10, 2) DEFAULT 0 AFTER results_for_sale,
ADD COLUMN IF NOT EXISTS disclaimer TEXT NULL AFTER description;

-- Add agent profile columns to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS date_of_birth DATE DEFAULT NULL COMMENT 'Agent date of birth for age calculation',
ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female') DEFAULT NULL COMMENT 'Agent gender',
ADD COLUMN IF NOT EXISTS state VARCHAR(50) DEFAULT NULL COMMENT 'Agent state of residence',
ADD COLUMN IF NOT EXISTS lga VARCHAR(100) DEFAULT NULL COMMENT 'Agent local government area',
ADD COLUMN IF NOT EXISTS occupation VARCHAR(100) DEFAULT NULL COMMENT 'Agent occupation/profession',
ADD COLUMN IF NOT EXISTS education_qualification VARCHAR(100) DEFAULT NULL COMMENT 'Agent highest education qualification',
ADD COLUMN IF NOT EXISTS employment_status ENUM('employed', 'unemployed') DEFAULT NULL COMMENT 'Agent employment status',
ADD COLUMN IF NOT EXISTS income_range VARCHAR(50) DEFAULT NULL COMMENT 'Agent monthly income range',
ADD COLUMN IF NOT EXISTS payment_preference ENUM('cash', 'airtime', 'data') DEFAULT 'cash' AFTER account_number,
ADD COLUMN IF NOT EXISTS total_earnings DECIMAL(10, 2) DEFAULT 0.00 AFTER payment_preference,
ADD COLUMN IF NOT EXISTS pending_earnings DECIMAL(10, 2) DEFAULT 0.00 AFTER total_earnings,
ADD COLUMN IF NOT EXISTS paid_earnings DECIMAL(10, 2) DEFAULT 0.00 AFTER pending_earnings,
ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) UNIQUE,
ADD COLUMN IF NOT EXISTS referred_by INT,
ADD COLUMN IF NOT EXISTS agent_approval_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
ADD COLUMN IF NOT EXISTS agent_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS agent_approved_by INT,
ADD COLUMN IF NOT EXISTS sms_credits INT DEFAULT 0;

-- Add question fields to poll_questions table
ALTER TABLE poll_questions
ADD COLUMN IF NOT EXISTS question_description TEXT NULL AFTER question_text,
ADD COLUMN IF NOT EXISTS question_image VARCHAR(255) NULL AFTER question_description;

-- =====================================================
-- 2. NEW TABLES CREATION
-- =====================================================

-- Site Settings Table
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

-- Agents Table (for approval workflow)
CREATE TABLE IF NOT EXISTS agents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(user_id),
    INDEX(approval_status)
);

-- Agent Payouts Table
CREATE TABLE IF NOT EXISTS agent_payouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_type ENUM('cash', 'airtime', 'data') NOT NULL,
    payment_method VARCHAR(100),
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    reference_number VARCHAR(100),
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    notes TEXT,
    FOREIGN KEY(agent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(agent_id),
    INDEX(status)
);

-- Poll Shares Table
CREATE TABLE IF NOT EXISTS poll_shares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    agent_id INT NOT NULL,
    share_method ENUM('email', 'sms', 'whatsapp', 'link') NOT NULL,
    recipient VARCHAR(255),
    tracking_code VARCHAR(50) UNIQUE NOT NULL,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    clicks INT DEFAULT 0,
    responses INT DEFAULT 0,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(agent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(poll_id),
    INDEX(agent_id),
    INDEX(tracking_code)
);

-- Agent Tasks Table
CREATE TABLE IF NOT EXISTS agent_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_responses INT DEFAULT 100,
    commission_per_response DECIMAL(10, 2) DEFAULT 1000.00,
    status ENUM('active', 'paused', 'completed', 'expired') DEFAULT 'active',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(poll_id),
    INDEX(status)
);

-- Agent Task Assignments Table
CREATE TABLE IF NOT EXISTS agent_task_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    agent_id INT NOT NULL,
    status ENUM('assigned', 'accepted', 'in_progress', 'completed', 'rejected') DEFAULT 'assigned',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    responses_count INT DEFAULT 0,
    earnings DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY(task_id) REFERENCES agent_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY(agent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(task_id),
    INDEX(agent_id),
    INDEX(status)
);

-- Agent Earnings Table
CREATE TABLE IF NOT EXISTS agent_earnings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id INT NOT NULL,
    user_id INT,
    earning_type ENUM('poll_response', 'poll_share', 'referral', 'subscription', 'other') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    poll_id INT,
    reference VARCHAR(255),
    status ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(agent_id),
    INDEX(earning_type),
    INDEX(status),
    INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blog Posts Table
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected', 'draft') DEFAULT 'pending',
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    shares INT DEFAULT 0,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(user_id),
    INDEX(status),
    INDEX(slug),
    INDEX(created_at)
);

-- Blog Comments Table
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL,
    comment TEXT NOT NULL,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(parent_id) REFERENCES blog_comments(id) ON DELETE CASCADE,
    INDEX(post_id),
    INDEX(user_id),
    INDEX(parent_id),
    INDEX(created_at)
);

-- Blog Likes Table
CREATE TABLE IF NOT EXISTS blog_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NULL,
    comment_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY(comment_id) REFERENCES blog_comments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_like (user_id, post_id),
    UNIQUE KEY unique_comment_like (user_id, comment_id),
    INDEX(user_id),
    INDEX(post_id),
    INDEX(comment_id)
);

-- Blog Shares Table
CREATE TABLE IF NOT EXISTS blog_shares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    platform ENUM('facebook', 'twitter', 'linkedin', 'whatsapp', 'email', 'link') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(post_id),
    INDEX(platform)
);

-- Bookmarks System Table
CREATE TABLE IF NOT EXISTS bookmarks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    poll_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, poll_id),
    INDEX(user_id),
    INDEX(poll_id)
);

-- Following System Table
CREATE TABLE IF NOT EXISTS following (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_following (follower_id, following_id),
    INDEX(follower_id),
    INDEX(following_id)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(is_read),
    INDEX(created_at)
);

-- Poll Reports Table
CREATE TABLE IF NOT EXISTS poll_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason ENUM('inappropriate_content', 'spam', 'misleading', 'duplicate', 'other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(poll_id),
    INDEX(reporter_id),
    INDEX(status),
    INDEX(created_at)
);

-- Message Logs Table
CREATE TABLE IF NOT EXISTS message_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message_type ENUM('sms', 'email', 'whatsapp') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(user_id),
    INDEX(message_type),
    INDEX(status),
    INDEX(created_at)
);

-- =====================================================
-- 3. INDEXES CREATION
-- =====================================================

-- Agent filtering indexes
CREATE INDEX IF NOT EXISTS idx_polls_agent_age ON polls (agent_age_criteria(50));
CREATE INDEX IF NOT EXISTS idx_polls_agent_gender ON polls (agent_gender_criteria(50));
CREATE INDEX IF NOT EXISTS idx_polls_agent_state ON polls (agent_state_criteria);
CREATE INDEX IF NOT EXISTS idx_polls_agent_occupation ON polls (agent_occupation_criteria(100));
CREATE INDEX IF NOT EXISTS idx_polls_agent_education ON polls (agent_education_criteria(100));
CREATE INDEX IF NOT EXISTS idx_polls_agent_employment ON polls (agent_employment_criteria(50));
CREATE INDEX IF NOT EXISTS idx_polls_agent_income ON polls (agent_income_criteria(100));

CREATE INDEX IF NOT EXISTS idx_users_agent_dob ON users (date_of_birth);
CREATE INDEX IF NOT EXISTS idx_users_agent_gender ON users (gender);
CREATE INDEX IF NOT EXISTS idx_users_agent_state ON users (state);
CREATE INDEX IF NOT EXISTS idx_users_agent_occupation ON users (occupation);
CREATE INDEX IF NOT EXISTS idx_users_agent_education ON users (education_qualification);
CREATE INDEX IF NOT EXISTS idx_users_agent_employment ON users (employment_status);
CREATE INDEX IF NOT EXISTS idx_users_agent_income ON users (income_range);

-- Referral system indexes
CREATE INDEX IF NOT EXISTS idx_referral_code ON users (referral_code);
CREATE INDEX IF NOT EXISTS idx_referred_by ON users (referred_by);

-- Question image index
CREATE INDEX IF NOT EXISTS idx_question_image ON poll_questions (question_image);

-- =====================================================
-- 4. DEFAULT DATA INSERTION
-- =====================================================

-- Insert default site settings
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type, description, category) VALUES
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

-- =====================================================
-- 5. DATA UPDATES
-- =====================================================

-- Update existing polls with default values
UPDATE polls SET agent_commission = 1000 WHERE agent_commission IS NULL;
UPDATE polls SET results_for_sale = FALSE WHERE results_for_sale IS NULL;
UPDATE polls SET results_sale_price = 5000 WHERE results_sale_price IS NULL AND results_for_sale = TRUE;

-- =====================================================
-- 6. MIGRATION COMPLETION
-- =====================================================

SELECT 'UNIFIED MIGRATION COMPLETED SUCCESSFULLY!' as status;
SELECT 'All tables have been updated and new features are now available.' as message;

-- =====================================================
-- FEATURES ADDED BY THIS MIGRATION:
-- =====================================================
-- 1. Agent filtering system for polls
-- 2. Agent profile enhancements and earnings tracking
-- 3. Blog system with posts, comments, likes, and shares
-- 4. Referral system for user recruitment
-- 5. Bookmarks and following system
-- 6. Notifications system
-- 7. Message logging for SMS/Email/WhatsApp
-- 8. Site settings for admin configuration
-- 9. Question description and image support
-- 10. Poll disclaimer support
-- 11. Enhanced agent payment and payout tracking
-- 12. Agents approval workflow table
-- =====================================================