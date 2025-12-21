-- Blog System Tables
-- All users can create posts, but admin must approve them

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected', 'draft') DEFAULT 'pending',
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    shares INT DEFAULT 0,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(user_id),
    INDEX(status),
    INDEX(slug),
    INDEX(created_at)
);

CREATE TABLE IF NOT EXISTS blog_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL,
    comment TEXT NOT NULL,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(parent_id) REFERENCES blog_comments(id) ON DELETE CASCADE,
    INDEX(post_id),
    INDEX(user_id),
    INDEX(parent_id),
    INDEX(created_at)
);

CREATE TABLE IF NOT EXISTS blog_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NULL,
    comment_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY(comment_id) REFERENCES blog_comments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_like (user_id, post_id),
    UNIQUE KEY unique_comment_like (user_id, comment_id),
    INDEX(user_id),
    INDEX(post_id),
    INDEX(comment_id)
);

CREATE TABLE IF NOT EXISTS blog_shares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    platform ENUM('facebook', 'twitter', 'linkedin', 'whatsapp', 'email', 'link') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(post_id),
    INDEX(platform)
);
