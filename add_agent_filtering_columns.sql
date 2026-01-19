-- Add agent filtering criteria columns to polls table
-- Run this SQL script to add the new columns for agent filtering functionality

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

-- Add indexes for better performance on filtering queries
CREATE INDEX idx_agent_age ON polls (agent_age_criteria(50));
CREATE INDEX idx_agent_gender ON polls (agent_gender_criteria(50));
CREATE INDEX idx_agent_state ON polls (agent_state_criteria);
CREATE INDEX idx_agent_occupation ON polls (agent_occupation_criteria(100));
CREATE INDEX idx_agent_education ON polls (agent_education_criteria(100));
CREATE INDEX idx_agent_employment ON polls (agent_employment_criteria(50));
CREATE INDEX idx_agent_income ON polls (agent_income_criteria(100));





