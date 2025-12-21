-- Add poll payment and pricing fields
-- This migration adds fields to support client payment for polls and agent earnings

-- Add payment-related fields to polls table
ALTER TABLE polls 
ADD COLUMN IF NOT EXISTS is_paid_poll BOOLEAN DEFAULT FALSE COMMENT 'Whether this poll requires payment from client',
ADD COLUMN IF NOT EXISTS poll_cost DECIMAL(10, 2) DEFAULT 0 COMMENT 'Total cost client pays for this poll',
ADD COLUMN IF NOT EXISTS cost_per_response DECIMAL(10, 2) DEFAULT 100 COMMENT 'Cost per individual response',
ADD COLUMN IF NOT EXISTS agent_commission DECIMAL(10, 2) DEFAULT 1000 COMMENT 'Amount agent earns per completed response',
ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'paid', 'partially_paid') DEFAULT 'unpaid' COMMENT 'Client payment status',
ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(255) COMMENT 'Payment transaction reference',
ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL COMMENT 'When client paid for poll',
ADD COLUMN IF NOT EXISTS total_paid DECIMAL(10, 2) DEFAULT 0 COMMENT 'Total amount client has paid',
ADD INDEX idx_payment_status (payment_status),
ADD INDEX idx_is_paid_poll (is_paid_poll);

-- Ensure agent_earnings table exists with proper structure
CREATE TABLE IF NOT EXISTS agent_earnings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id INT NOT NULL,
    poll_id INT,
    user_id INT,
    earning_type ENUM('poll_response', 'poll_share', 'referral', 'bonus', 'other') DEFAULT 'poll_response',
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    reference VARCHAR(255),
    status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY(agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE SET NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(agent_id),
    INDEX(poll_id),
    INDEX(earning_type),
    INDEX(status),
    INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add earnings tracking to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS total_earnings DECIMAL(10, 2) DEFAULT 0 COMMENT 'Total earnings ever (for agents)',
ADD COLUMN IF NOT EXISTS pending_earnings DECIMAL(10, 2) DEFAULT 0 COMMENT 'Pending earnings (for agents)',
ADD COLUMN IF NOT EXISTS paid_earnings DECIMAL(10, 2) DEFAULT 0 COMMENT 'Paid earnings (for agents)',
ADD COLUMN IF NOT EXISTS sms_credits INT DEFAULT 0 COMMENT 'SMS credits balance',
ADD INDEX idx_total_earnings (total_earnings);

-- Update message_logs to support delivery tracking
ALTER TABLE message_logs
ADD COLUMN IF NOT EXISTS delivery_status ENUM('pending', 'sent', 'delivered', 'failed', 'unknown') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS failed_reason TEXT,
ADD COLUMN IF NOT EXISTS message_id VARCHAR(255) COMMENT 'Provider message ID for tracking',
ADD INDEX idx_delivery_status (delivery_status),
ADD INDEX idx_message_id (message_id);

-- Create poll_results_access table for databank (paid results feature)
CREATE TABLE IF NOT EXISTS poll_results_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    user_id INT NOT NULL,
    access_type ENUM('owner', 'purchased', 'admin', 'free') DEFAULT 'purchased',
    amount_paid DECIMAL(10, 2) DEFAULT 0,
    payment_reference VARCHAR(255),
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    downloads_count INT DEFAULT 0,
    last_accessed_at TIMESTAMP NULL,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_poll_user (poll_id, user_id),
    INDEX(poll_id),
    INDEX(user_id),
    INDEX(access_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add databank fields to polls
ALTER TABLE polls
ADD COLUMN IF NOT EXISTS results_for_sale BOOLEAN DEFAULT FALSE COMMENT 'Whether results are for sale in databank',
ADD COLUMN IF NOT EXISTS results_sale_price DECIMAL(10, 2) DEFAULT 0 COMMENT 'Price to purchase results',
ADD INDEX idx_results_for_sale (results_for_sale);

-- Migration completed successfully
