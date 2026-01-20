-- Setup agent filtering and profile tables
-- Run this script to add required columns for the agent filtering system

-- Add agent filtering columns to polls table
ALTER TABLE polls
ADD COLUMN agent_age_criteria TEXT DEFAULT '["all"]' COMMENT 'JSON array of selected age groups',
ADD COLUMN agent_gender_criteria TEXT DEFAULT '["both"]' COMMENT 'JSON array of selected genders',
ADD COLUMN agent_state_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected state for location filtering',
ADD COLUMN agent_lga_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected LGA for location filtering',
ADD COLUMN agent_location_all TINYINT(1) DEFAULT 1 COMMENT 'Whether to include all Nigeria locations',
ADD COLUMN agent_occupation_criteria TEXT DEFAULT '["all"]' COMMENT 'JSON array of selected occupations',
ADD COLUMN agent_education_criteria TEXT DEFAULT '["all"]' COMMENT 'JSON array of selected education levels',
ADD COLUMN agent_employment_criteria TEXT DEFAULT '["both"]' COMMENT 'JSON array of selected employment status',
ADD COLUMN agent_income_criteria TEXT DEFAULT '["all"]' COMMENT 'JSON array of selected income ranges';

-- Add agent profile columns to users table
ALTER TABLE users
ADD COLUMN date_of_birth DATE DEFAULT NULL COMMENT 'Agent date of birth for age calculation',
ADD COLUMN gender ENUM('male', 'female') DEFAULT NULL COMMENT 'Agent gender',
ADD COLUMN state VARCHAR(50) DEFAULT NULL COMMENT 'Agent state of residence',
ADD COLUMN lga VARCHAR(100) DEFAULT NULL COMMENT 'Agent local government area',
ADD COLUMN occupation VARCHAR(100) DEFAULT NULL COMMENT 'Agent occupation/profession',
ADD COLUMN education_qualification VARCHAR(100) DEFAULT NULL COMMENT 'Agent highest education qualification',
ADD COLUMN employment_status ENUM('employed', 'unemployed') DEFAULT NULL COMMENT 'Agent employment status',
ADD COLUMN income_range VARCHAR(50) DEFAULT NULL COMMENT 'Agent monthly income range';

-- Create indexes for better performance on filtering queries
CREATE INDEX idx_polls_agent_age ON polls (agent_age_criteria(50));
CREATE INDEX idx_polls_agent_gender ON polls (agent_gender_criteria(50));
CREATE INDEX idx_polls_agent_state ON polls (agent_state_criteria);
CREATE INDEX idx_polls_agent_occupation ON polls (agent_occupation_criteria(100));
CREATE INDEX idx_polls_agent_education ON polls (agent_education_criteria(100));
CREATE INDEX idx_polls_agent_employment ON polls (agent_employment_criteria(50));
CREATE INDEX idx_polls_agent_income ON polls (agent_income_criteria(100));

CREATE INDEX idx_users_agent_dob ON users (date_of_birth);
CREATE INDEX idx_users_agent_gender ON users (gender);
CREATE INDEX idx_users_agent_state ON users (state);
CREATE INDEX idx_users_agent_occupation ON users (occupation);
CREATE INDEX idx_users_agent_education ON users (education_qualification);
CREATE INDEX idx_users_agent_employment ON users (employment_status);
CREATE INDEX idx_users_agent_income ON users (income_range);





