-- Add payment preferences and sharing features for agents
ALTER TABLE users 
ADD COLUMN payment_preference ENUM('cash', 'airtime', 'data') DEFAULT 'cash' AFTER account_number,
ADD COLUMN total_earnings DECIMAL(10, 2) DEFAULT 0.00 AFTER payment_preference,
ADD COLUMN pending_earnings DECIMAL(10, 2) DEFAULT 0.00 AFTER total_earnings,
ADD COLUMN paid_earnings DECIMAL(10, 2) DEFAULT 0.00 AFTER pending_earnings;

-- Create agent_payouts table for payment tracking
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

-- Create poll_shares table for tracking shared polls
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

-- Create agent_tasks table for email notifications
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

-- Create agent_task_assignments table
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
