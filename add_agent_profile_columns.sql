-- Add agent profile columns to users table
-- Run this SQL script to add agent profile fields to the users table

ALTER TABLE users
ADD COLUMN date_of_birth DATE DEFAULT NULL COMMENT 'Agent date of birth for age calculation',
ADD COLUMN gender ENUM('male', 'female') DEFAULT NULL COMMENT 'Agent gender',
ADD COLUMN state VARCHAR(50) DEFAULT NULL COMMENT 'Agent state of residence',
ADD COLUMN lga VARCHAR(100) DEFAULT NULL COMMENT 'Agent local government area',
ADD COLUMN occupation VARCHAR(100) DEFAULT NULL COMMENT 'Agent occupation/profession',
ADD COLUMN education_qualification VARCHAR(100) DEFAULT NULL COMMENT 'Agent highest education qualification',
ADD COLUMN employment_status ENUM('employed', 'unemployed') DEFAULT NULL COMMENT 'Agent employment status',
ADD COLUMN income_range VARCHAR(50) DEFAULT NULL COMMENT 'Agent monthly income range';

-- Create indexes for agent filtering
CREATE INDEX idx_agent_date_of_birth ON users (date_of_birth);
CREATE INDEX idx_agent_gender ON users (gender);
CREATE INDEX idx_agent_state ON users (state);
CREATE INDEX idx_agent_occupation ON users (occupation);
CREATE INDEX idx_agent_education ON users (education_qualification);
CREATE INDEX idx_agent_employment ON users (employment_status);
CREATE INDEX idx_agent_income ON users (income_range);






