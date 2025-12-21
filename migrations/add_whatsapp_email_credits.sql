-- Add WhatsApp and Email credits to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS whatsapp_credits INT DEFAULT 0 AFTER sms_credits,
ADD COLUMN IF NOT EXISTS email_credits INT DEFAULT 0 AFTER whatsapp_credits;

-- Update existing NULL values to 0
UPDATE users SET whatsapp_credits = 0 WHERE whatsapp_credits IS NULL;
UPDATE users SET email_credits = 0 WHERE email_credits IS NULL;
