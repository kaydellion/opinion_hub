-- Bookmarks System Tables for Opinion Hub NG
-- Add these tables to enable users to bookmark polls for later reference

-- User bookmarks table (users bookmarking polls)
CREATE TABLE user_bookmarks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    poll_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, poll_id),
    INDEX(user_id),
    INDEX(poll_id),
    INDEX(created_at)
);





