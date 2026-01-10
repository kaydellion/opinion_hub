-- Following System Tables for Opinion Hub NG
-- Add these tables to enable users to follow creators and categories

-- User follows table (users following other users)
CREATE TABLE user_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX(follower_id),
    INDEX(following_id),
    INDEX(created_at)
);

-- User category follows table (users following categories)
CREATE TABLE user_category_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_follow (user_id, category_id),
    INDEX(user_id),
    INDEX(category_id),
    INDEX(created_at)
);

-- Poll Reports Table
CREATE TABLE poll_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    reported_by INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(poll_id),
    INDEX(reported_by),
    INDEX(status),
    INDEX(created_at)
);

-- Modify polls table to include suspended status
ALTER TABLE polls MODIFY COLUMN status ENUM('draft', 'active', 'paused', 'suspended', 'deleted') DEFAULT 'draft';

-- Add indexes for better performance
CREATE INDEX idx_user_follows_follower ON user_follows(follower_id);
CREATE INDEX idx_user_follows_following ON user_follows(following_id);
CREATE INDEX idx_user_category_follows_user ON user_category_follows(user_id);
CREATE INDEX idx_user_category_follows_category ON user_category_follows(category_id);

