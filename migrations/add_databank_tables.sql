-- Migration: Add Databank Tables
-- Date: 2025-01-15
-- Description: Add tables required for databank functionality (poll results access, dataset downloads, cache)

-- Poll Results Access Table (Databank)
CREATE TABLE IF NOT EXISTS poll_results_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    poll_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL DEFAULT 0,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    access_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_poll (user_id, poll_id),
    INDEX(user_id),
    INDEX(poll_id)
);

-- Dataset Downloads Table (Track COMBINED vs SINGLE downloads)
CREATE TABLE IF NOT EXISTS dataset_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    poll_id INT NOT NULL,
    dataset_format ENUM('combined', 'single') NOT NULL,
    time_period ENUM('daily', 'weekly', 'monthly', 'annually') DEFAULT 'monthly',
    download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(255),
    file_size INT,
    download_count INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(poll_id),
    INDEX(dataset_format)
);

-- Dataset Cache Table (Store pre-processed combined datasets)
CREATE TABLE IF NOT EXISTS dataset_cache (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    cache_key VARCHAR(255) NOT NULL, -- format: "combined_daily", "combined_weekly", etc.
    cache_data LONGTEXT, -- JSON data for charts and trends
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_valid BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    UNIQUE KEY unique_poll_cache (poll_id, cache_key),
    INDEX(poll_id),
    INDEX(cache_key),
    INDEX(last_updated)
);

-- Insert sample data for testing (optional)
-- You can remove these INSERT statements in production

-- Sample poll results access records
-- INSERT INTO poll_results_access (user_id, poll_id, amount_paid) VALUES
-- (1, 1, 5000.00),
-- (2, 1, 5000.00);

-- Sample dataset downloads
-- INSERT INTO dataset_downloads (user_id, poll_id, dataset_format, time_period) VALUES
-- (1, 1, 'combined', 'monthly'),
-- (2, 1, 'single', 'monthly');




