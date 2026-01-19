-- Final Migration Script for Poll Settings
-- Run this in your MySQL database to add the new poll setting columns

USE opinion_hub;

-- Add missing columns to polls table
ALTER TABLE polls
ADD COLUMN IF NOT EXISTS agent_commission DECIMAL(10, 2) DEFAULT 1000 AFTER cost_per_email,
ADD COLUMN IF NOT EXISTS results_for_sale BOOLEAN DEFAULT FALSE AFTER results_private,
ADD COLUMN IF NOT EXISTS results_sale_price DECIMAL(10, 2) DEFAULT 0 AFTER results_for_sale;

-- Update existing polls with default values
UPDATE polls SET agent_commission = 1000 WHERE agent_commission IS NULL;
UPDATE polls SET results_for_sale = FALSE WHERE results_for_sale IS NULL;
UPDATE polls SET results_sale_price = 5000 WHERE results_sale_price IS NULL AND results_for_sale = TRUE;

-- Verify the migration
SELECT 'Migration completed successfully!' as status;






