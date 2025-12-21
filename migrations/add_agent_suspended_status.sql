-- Add 'suspended' status to agent_status enum if not exists
ALTER TABLE users MODIFY COLUMN agent_status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending';
