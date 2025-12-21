-- vPay Africa Payment Gateway Migration
-- Date: <?php echo date('Y-m-d H:i:s'); ?>

-- No database schema changes required
-- The existing transactions table already supports the new payment gateway

-- Existing structure:
-- CREATE TABLE transactions (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     user_id INT NOT NULL,
--     type VARCHAR(50) NOT NULL,
--     amount DECIMAL(10,2) NOT NULL,
--     reference VARCHAR(255) NOT NULL UNIQUE,
--     status VARCHAR(20) DEFAULT 'pending',
--     payment_method VARCHAR(20) DEFAULT 'paystack', -- Now supports 'vpay'
--     metadata TEXT,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- Migration Notes:
-- 1. Old Paystack transactions remain with payment_method = 'paystack'
-- 2. New vPay transactions will have payment_method = 'vpay'
-- 3. No data migration needed
-- 4. Update configuration in connect.php with vPay Africa credentials

-- Configuration Required:
-- 1. Get API keys from https://vpay.africa
-- 2. Update connect.php:
--    - VPAY_PUBLIC_KEY
--    - VPAY_SECRET_KEY
--    - VPAY_MERCHANT_ID
-- 3. Test all payment flows
-- 4. Monitor vpay_debug.log

-- Files Updated:
-- ✓ connect.php - Added vPay configuration, disabled Paystack
-- ✓ functions.php - Added verifyVPayPayment() function
-- ✓ vpay-callback.php - New callback handler
-- ✓ client/buy-credits.php - Updated to vPay
-- ✓ client/subscription.php - Updated to vPay
-- ✓ agent/buy-sms-credits.php - Updated to vPay
-- ✓ client/pay-for-ad.php - Updated to vPay
-- ✓ client/ad-payment-callback.php - Updated to vPay
-- ✓ faq.php - Updated payment gateway mention

-- MIGRATION COMPLETE - NO SQL EXECUTION NEEDED
