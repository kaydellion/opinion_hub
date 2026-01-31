-- Add payment information columns to users table
-- Run this if the columns don't already exist

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS mobile_money_provider VARCHAR(50) DEFAULT NULL COMMENT 'Mobile money provider for payouts',
ADD COLUMN IF NOT EXISTS mobile_money_number VARCHAR(15) DEFAULT NULL COMMENT 'Mobile money number for payouts';

-- Update payment_preference enum to include new options
ALTER TABLE users 
MODIFY COLUMN payment_preference ENUM('bank_transfer', 'mobile_money', 'airtime', 'data') DEFAULT NULL;
