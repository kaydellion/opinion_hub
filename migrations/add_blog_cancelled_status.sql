-- Add 'cancelled' status to blog_posts status enum
ALTER TABLE blog_posts MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled') DEFAULT 'draft';
