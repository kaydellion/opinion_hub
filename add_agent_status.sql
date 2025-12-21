-- Add agent_status field to users table
ALTER TABLE users 
ADD COLUMN agent_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER role,
ADD COLUMN agent_applied_at TIMESTAMP NULL AFTER agent_status,
ADD COLUMN agent_approved_at TIMESTAMP NULL AFTER agent_applied_at,
ADD COLUMN agent_approved_by INT NULL AFTER agent_approved_at;

-- Update existing agents to approved status (for backwards compatibility)
UPDATE users SET agent_status = 'approved' WHERE role = 'agent';

-- Add index for better performance
ALTER TABLE users ADD INDEX(agent_status);
