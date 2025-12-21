-- OpinionHub.ng Database Schema
-- Create Database
CREATE DATABASE IF NOT EXISTS opinionhub_ng;
USE opinionhub_ng;

-- Users Table (Admins, Clients, Agents, Regular Users)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'client', 'agent', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profile_image VARCHAR(255),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    occupation VARCHAR(100),
    educational_level VARCHAR(100),
    state VARCHAR(100),
    lga VARCHAR(100),
    address TEXT,
    social_facebook VARCHAR(255),
    social_twitter VARCHAR(255),
    social_instagram VARCHAR(255),
    social_linkedin VARCHAR(255),
    bio TEXT,
    bank_name VARCHAR(100),
    account_name VARCHAR(100),
    account_number VARCHAR(20),
    areas_of_interest JSON,
    languages_spoken JSON,
    employment_status ENUM('Employed', 'Unemployed'),
    monthly_income_range VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(role),
    INDEX(status)
);

-- Subscription Plans Table
CREATE TABLE subscription_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('free', 'basic', 'classic', 'enterprise') NOT NULL,
    monthly_price DECIMAL(10, 2) DEFAULT 0,
    annual_price DECIMAL(10, 2) DEFAULT 0,
    max_polls_per_month INT,
    responses_per_poll INT,
    export_data BOOLEAN DEFAULT FALSE,
    set_responders BOOLEAN DEFAULT FALSE,
    question_types TEXT,
    sms_invite_units INT DEFAULT 0,
    email_invite_units INT DEFAULT 0,
    whatsapp_invite_units INT DEFAULT 0,
    sms_cost_per_unit DECIMAL(10, 2),
    email_cost_per_unit DECIMAL(10, 2),
    whatsapp_cost_per_unit DECIMAL(10, 2),
    priority_listing INT DEFAULT 0,
    custom_branding BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Subscriptions
CREATE TABLE user_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP,
    payment_reference VARCHAR(255),
    amount_paid DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(plan_id) REFERENCES subscription_plans(id),
    INDEX(user_id),
    INDEX(status)
);

-- Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sub-Categories Table
CREATE TABLE sub_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY(category_id, slug)
);

-- Polls/Surveys Table
CREATE TABLE polls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    created_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    sub_category_id INT,
    poll_type VARCHAR(100),
    image VARCHAR(255),
    start_date DATETIME,
    end_date DATETIME,
    status ENUM('draft', 'active', 'paused', 'closed') DEFAULT 'draft',
    is_paid BOOLEAN DEFAULT FALSE,
    price_per_response DECIMAL(10, 2),
    cost_per_sms DECIMAL(10, 2),
    cost_per_whatsapp DECIMAL(10, 2),
    cost_per_email DECIMAL(10, 2),
    target_responders INT,
    allow_multiple_options BOOLEAN DEFAULT FALSE,
    require_participant_names BOOLEAN DEFAULT FALSE,
    allow_comments BOOLEAN DEFAULT FALSE,
    hide_share_button BOOLEAN DEFAULT FALSE,
    allow_multiple_votes BOOLEAN DEFAULT FALSE,
    one_vote_per_session BOOLEAN DEFAULT TRUE,
    one_vote_per_ip BOOLEAN DEFAULT FALSE,
    one_vote_per_account BOOLEAN DEFAULT TRUE,
    results_public_after_vote BOOLEAN DEFAULT FALSE,
    results_public_after_end BOOLEAN DEFAULT FALSE,
    results_private BOOLEAN DEFAULT TRUE,
    subscription_plan_access INT,
    total_responses INT DEFAULT 0,
    total_cost DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id),
    FOREIGN KEY(sub_category_id) REFERENCES sub_categories(id),
    INDEX(created_by),
    INDEX(status),
    INDEX(created_at)
);

-- Poll Questions Table
CREATE TABLE poll_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50),
    question_order INT,
    is_required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    INDEX(poll_id)
);

-- Poll Question Options (for multiple choice, rating, etc.)
CREATE TABLE poll_question_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text VARCHAR(255),
    option_order INT,
    is_correct_answer BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(question_id) REFERENCES poll_questions(id) ON DELETE CASCADE,
    INDEX(question_id)
);

-- Poll Responses Table
CREATE TABLE poll_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    respondent_id INT,
    respondent_name VARCHAR(100),
    respondent_email VARCHAR(100),
    respondent_ip VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(255),
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    INDEX(poll_id),
    INDEX(respondent_id),
    INDEX(respondent_ip)
);

-- Individual Question Responses
CREATE TABLE question_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    response_id INT NOT NULL,
    question_id INT NOT NULL,
    option_id INT,
    text_response TEXT,
    rating_value INT,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(response_id) REFERENCES poll_responses(id) ON DELETE CASCADE,
    FOREIGN KEY(question_id) REFERENCES poll_questions(id) ON DELETE CASCADE,
    INDEX(response_id),
    INDEX(question_id)
);

-- Agents Table (extends users with agent-specific data)
CREATE TABLE agents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    total_earnings DECIMAL(15, 2) DEFAULT 0,
    pending_earnings DECIMAL(15, 2) DEFAULT 0,
    tasks_completed INT DEFAULT 0,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reward_preference ENUM('cash', 'airtime', 'data') DEFAULT 'cash',
    
    -- Demographics for targeting
    age INT,
    gender ENUM('male', 'female', 'other'),
    state VARCHAR(100),
    lga VARCHAR(100),
    education_level ENUM('primary', 'secondary', 'tertiary', 'postgraduate'),
    occupation VARCHAR(255),
    interests TEXT, -- JSON array of interests
    
    -- Profile completion
    profile_completed BOOLEAN DEFAULT FALSE,
    contract_accepted BOOLEAN DEFAULT FALSE,
    contract_accepted_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(approval_status),
    INDEX(state),
    INDEX(age),
    INDEX(gender),
    INDEX(education_level)
);

-- Agent Tasks/Assignments
CREATE TABLE agent_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id INT NOT NULL,
    poll_id INT NOT NULL,
    commission_per_response DECIMAL(10, 2),
    reward_type ENUM('cash', 'airtime', 'data') DEFAULT 'cash',
    airtime_amount DECIMAL(10, 2),
    data_bundle VARCHAR(50), -- e.g., "1GB", "500MB"
    target_responses INT,
    completed_responses INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY(agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    INDEX(agent_id),
    INDEX(status)
);

-- Payment Transactions Table
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reference VARCHAR(255) UNIQUE NOT NULL,
    amount DECIMAL(15, 2),
    type ENUM('subscription', 'sms_credits', 'email_credits', 'whatsapp_credits', 'agent_payout') DEFAULT 'subscription',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    authorization_url TEXT,
    access_code VARCHAR(255),
    metadata TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(reference),
    INDEX(status)
);

-- SMS/Email Credits Table
CREATE TABLE messaging_credits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    sms_balance INT DEFAULT 0,
    email_balance INT DEFAULT 0,
    whatsapp_balance INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY(user_id)
);

-- Message Logs (track all sent messages)
CREATE TABLE message_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message_type ENUM('sms', 'email', 'whatsapp') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    message_content TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    credits_used INT DEFAULT 1,
    response_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(message_type),
    INDEX(status),
    INDEX(created_at)
);

-- Blog Articles
CREATE TABLE blog_articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(255),
    category_id INT,
    author_id INT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    tags JSON,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY(category_id) REFERENCES categories(id),
    FOREIGN KEY(author_id) REFERENCES users(id),
    INDEX(slug),
    INDEX(status)
);

-- Advertisements
CREATE TABLE advertisements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    advertiser_id INT NOT NULL,
    placement VARCHAR(100),
    ad_size VARCHAR(50),
    cost_per_view DECIMAL(10, 2),
    total_views INT DEFAULT 0,
    click_throughs INT DEFAULT 0,
    image_url VARCHAR(255),
    ad_url VARCHAR(255),
    status ENUM('pending', 'active', 'paused', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(advertiser_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(advertiser_id),
    INDEX(status)
);

-- Contact Lists for bulk messaging
CREATE TABLE contact_lists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    total_contacts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id)
);

-- Contacts in lists
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    list_id INT NOT NULL,
    name VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    whatsapp VARCHAR(20),
    custom_fields JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(list_id) REFERENCES contact_lists(id) ON DELETE CASCADE,
    INDEX(list_id),
    INDEX(phone),
    INDEX(email)
);

-- VTU Payouts tracking
CREATE TABLE vtu_payouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id INT NOT NULL,
    task_id INT,
    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(10, 2),
    product_code VARCHAR(100), -- e.g., "MTN_1000", "GLO_DATA_1GB"
    payout_type ENUM('airtime', 'data') NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    provider_reference VARCHAR(255),
    provider_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY(agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY(task_id) REFERENCES agent_tasks(id),
    INDEX(agent_id),
    INDEX(status)
);

-- Message send logs
CREATE TABLE message_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message_type ENUM('sms', 'email', 'whatsapp') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    message TEXT,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    credits_used INT DEFAULT 1,
    provider_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(message_type),
    INDEX(status)
);

-- Insert Default Subscription Plans
INSERT INTO subscription_plans (name, type, monthly_price, annual_price, max_polls_per_month, responses_per_poll, sms_invite_units, email_invite_units, whatsapp_invite_units, sms_cost_per_unit, email_cost_per_unit, whatsapp_cost_per_unit) VALUES
('Free Plan', 'free', 0, 0, 5, 500, 0, 0, 0, 12, 8, 8),
('Basic Plan', 'basic', 65000, 650000, 50, 5000, 10000, 500, 500, 10, 6, 6),
('Classic Plan', 'classic', 85000, 950000, 200, 20000, 20000, 5000, 10000, 9, 5, 5),
('Enterprise Plan', 'enterprise', 120000, 1150000, 999, 999999, 30000, 10000, 20000, 8, 4, 4);

-- Insert Sample Categories
INSERT INTO categories (name, slug, description, icon) VALUES
('Animals & Pets', 'animals-pets', 'Polls related to animals and pets', 'paw'),
('Beauty & Well-being', 'beauty-wellbeing', 'Beauty and wellness topics', 'sparkles'),
('Business Services', 'business-services', 'Business related polls', 'briefcase'),
('Education & Training', 'education-training', 'Education and learning', 'book'),
('Electronics & Technology', 'electronics-technology', 'Tech and gadgets', 'microchip'),
('Health & Medical', 'health-medical', 'Health and medical topics', 'heart'),
('Politics & Governance', 'politics-governance', 'Political and governance polls', 'landmark'),
('Sports', 'sports', 'Sports related polls', 'trophy'),
('Travel & Vacation', 'travel-vacation', 'Travel and tourism', 'plane');

-- Insert Sample Sub-Categories
INSERT INTO sub_categories (category_id, name, slug, description) VALUES
(1, 'Cats & Dogs', 'cats-dogs', 'Feline and canine related polls'),
(1, 'Pet Services', 'pet-services', 'Pet care services'),
(2, 'Cosmetics & Makeup', 'cosmetics-makeup', 'Beauty products'),
(2, 'Wellness & Spa', 'wellness-spa', 'Wellness services'),
(3, 'HR & Recruiting', 'hr-recruiting', 'Human resources'),
(3, 'Import & Export', 'import-export', 'International trade');