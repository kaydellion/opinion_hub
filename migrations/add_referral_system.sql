-- Add referral columns to users table
ALTER TABLE users 
ADD COLUMN referral_code VARCHAR(20) UNIQUE,
ADD COLUMN referred_by INT,
ADD COLUMN total_earnings DECIMAL(10, 2) DEFAULT 0,
ADD INDEX idx_referral_code (referral_code),
ADD INDEX idx_referred_by (referred_by);

-- Create agent_earnings table for tracking earnings
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

-- Add agent-related columns to users table if they don't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS agent_approval_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
ADD COLUMN IF NOT EXISTS agent_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS agent_approved_by INT,
ADD COLUMN IF NOT EXISTS sms_credits INT DEFAULT 0;
