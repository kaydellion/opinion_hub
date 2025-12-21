-- Add message_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS message_logs (
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

-- Add messaging_credits table if it doesn't exist
CREATE TABLE IF NOT EXISTS messaging_credits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    credit_type ENUM('sms', 'email', 'whatsapp') NOT NULL,
    credits INT DEFAULT 0,
    last_purchase_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_credit(user_id, credit_type),
    INDEX(user_id)
);
