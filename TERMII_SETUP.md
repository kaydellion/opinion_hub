# Termii SMS/WhatsApp Setup Guide

## Error You're Seeing:
```
Termii SMS Failed: {"status": "error", "code": 401, "message": "Invalid API Key"}
```

## How to Fix:

### Step 1: Get Your Termii API Key

1. Go to [https://termii.com](https://termii.com)
2. Sign up or log in to your account
3. Navigate to **Dashboard → API Settings**
4. Copy your API Key

### Step 2: Configure in Database

Run this SQL in phpMyAdmin (replace `YOUR_ACTUAL_API_KEY` with your real key):

```sql
-- Insert or update Termii API Key
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('termii_api_key', 'YOUR_ACTUAL_API_KEY')
ON DUPLICATE KEY UPDATE setting_value = 'YOUR_ACTUAL_API_KEY';

-- Also set sender ID (your business name, max 11 characters)
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('termii_sender_id', 'OpinionHub')
ON DUPLICATE KEY UPDATE setting_value = 'OpinionHub';
```

### Step 3: Create message_logs Table

Run this SQL to create the missing table:

```sql
CREATE TABLE IF NOT EXISTS message_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message_type ENUM('sms', 'email', 'whatsapp') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    message_content TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    credits_used INT DEFAULT 1,
    response_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(message_type),
    INDEX(status),
    INDEX(created_at)
);
```

### Step 4: Verify Settings

Check if constants are defined in `connect.php`:

```php
// Should have these lines (they load from database):
define('TERMII_API_KEY', getSetting('termii_api_key', ''));
define('TERMII_SENDER_ID', getSetting('termii_sender_id', 'OpinionHub'));
```

### Step 5: Test

1. Go to **Messaging → Send SMS**
2. Try sending a test message
3. Check logs at: `/Applications/XAMPP/xamppfiles/logs/php_error_log`

## Pricing Info (Termii Nigeria):

- **SMS**: ~₦2-4 per message
- **WhatsApp**: ~₦5-8 per message
- **Minimum top-up**: Usually ₦1,000

## Support:

If you still get errors after setup:
- Check Termii account balance
- Verify sender ID is approved
- Ensure phone numbers are in international format (234...)

## Test Phone Number Format:

❌ Wrong: `0701996027`
✅ Correct: `2347019960275`

The system auto-converts `07019960275` to `2347019960275`.
