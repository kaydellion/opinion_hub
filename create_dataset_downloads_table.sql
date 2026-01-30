-- Migration script to create the dataset_downloads table
-- Run this script on the live server database to fix the missing table error
-- Command: mysql -u opinionh_opinionh -p opinionh_opinionhub_ng < create_dataset_downloads_table.sql

CREATE TABLE IF NOT EXISTS dataset_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    poll_id INT NOT NULL,
    dataset_format VARCHAR(50),
    time_period VARCHAR(50),
    download_count INT DEFAULT 1,
    download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_poll_id (poll_id),
    UNIQUE KEY unique_download (user_id, poll_id, dataset_format, time_period)
);

-- Verify table creation
SELECT 'Table dataset_downloads created successfully' as status;
DESCRIBE dataset_downloads;
