-- Create message_logs table for tracking all sent messages
CREATE TABLE IF NOT EXISTS message_logs (
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
